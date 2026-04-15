<?php

class ERP_OMD_KSeF_Import_Service
{
    const IMPORT_KIND_COST = 'cost';
    const IMPORT_KIND_SALES = 'sales';

    const IMPORT_STATUS_IMPORTED = 'imported';
    const IMPORT_STATUS_CONFLICT = 'conflict';
    const IMPORT_STATUS_MANUAL_REQUIRED = 'manual_required';
    const IMPORT_STATUS_RETRYING = 'retrying';

    const OPTION_RETRY_QUEUE = 'erp_omd_ksef_retry_queue';
    const OPTION_MODERATION_AUDIT = 'erp_omd_ksef_moderation_audit';
    const OPTION_SALES_INBOX = 'erp_omd_ksef_sales_inbox';

    /** @var mixed */
    private $workflow_service;

    /** @var mixed */
    private $invoice_repository;

    /** @var mixed */
    private $audit_repository;

    /** @var mixed */
    private $supplier_repository;

    /** @var mixed */
    private $client_repository;

    /** @var int */
    private $retry_max_window_seconds;

    /** @var array<int,int> */
    private $retry_schedule_seconds;

    /**
     * @param mixed $workflow_service
     * @param mixed $invoice_repository
     * @param mixed $audit_repository
     * @param int|null $retry_max_window_seconds
     * @param array<int,int>|null $retry_schedule_seconds
     * @param mixed $supplier_repository
     * @param mixed $client_repository
     */
    public function __construct($workflow_service, $invoice_repository = null, $audit_repository = null, $retry_max_window_seconds = null, array $retry_schedule_seconds = null, $supplier_repository = null, $client_repository = null)
    {
        $this->workflow_service = $workflow_service;
        $this->invoice_repository = $invoice_repository;
        $this->audit_repository = $audit_repository;
        $this->retry_max_window_seconds = is_int($retry_max_window_seconds) && $retry_max_window_seconds > 0 ? $retry_max_window_seconds : 90 * 60;
        $this->retry_schedule_seconds = $retry_schedule_seconds ?: [300, 900, 1800, 3600, 5400];
        $this->supplier_repository = $supplier_repository;
        $this->client_repository = $client_repository;
    }

    /**
     * @param array<int,array<string,mixed>> $documents
     * @param int $user_id
     * @return array<string,mixed>
     */
    public function import_documents(array $documents, $user_id)
    {
        $imported = 0;
        $failed = 0;
        $errors = [];
        $conflicts = 0;
        $duplicates = 0;

        foreach ($documents as $index => $document) {
            $attempt = $this->attempt_import_document((array) $document, (int) $user_id, true);
            $status = (string) ($attempt['status'] ?? '');

            if ($status === self::IMPORT_STATUS_IMPORTED) {
                $imported++;
                continue;
            }

            if ($status === 'duplicate') {
                $duplicates++;
                continue;
            }

            if ($status === self::IMPORT_STATUS_CONFLICT) {
                $conflicts++;
            } else {
                $failed++;
            }

            $errors[] = [
                'index' => $index,
                'invoice_number' => (string) ($attempt['invoice_number'] ?? ''),
                'status' => $status,
                'kind' => (string) ($attempt['kind'] ?? ''),
                'errors' => (array) ($attempt['errors'] ?? []),
            ];
        }

        return [
            'total' => count($documents),
            'imported' => $imported,
            'failed' => $failed,
            'duplicates' => $duplicates,
            'conflicts' => $conflicts,
            'errors' => $errors,
        ];
    }

    /**
     * @param array<string,mixed> $document
     * @param int $user_id
     * @param bool $enqueue_retry
     * @return array<string,mixed>
     */
    public function attempt_import_document(array $document, $user_id, $enqueue_retry = true)
    {
        $classification = $this->classify_document($document);
        if (! (bool) ($classification['ok'] ?? false)) {
            $errors = (array) ($classification['errors'] ?? [__('Nie udało się sklasyfikować dokumentu KSeF.', 'erp-omd')]);
            if ($enqueue_retry) {
                $this->enqueue_retry($document, (int) $user_id, implode(' ', $errors));
            }

            return [
                'status' => self::IMPORT_STATUS_MANUAL_REQUIRED,
                'kind' => '',
                'invoice_number' => (string) ($document['invoice_number'] ?? ''),
                'errors' => $errors,
            ];
        }

        $kind = (string) ($classification['kind'] ?? '');

        if ($kind === self::IMPORT_KIND_COST) {
            $supplier_match = $this->match_supplier_for_cost_document($document);
            if (! (bool) ($supplier_match['ok'] ?? false)) {
                $errors = (array) ($supplier_match['errors'] ?? [__('Nie udało się dopasować dostawcy po NIP.', 'erp-omd')]);
                $status = (string) ($supplier_match['status'] ?? self::IMPORT_STATUS_MANUAL_REQUIRED);

                return [
                    'status' => $status,
                    'kind' => $kind,
                    'invoice_number' => (string) ($document['invoice_number'] ?? ''),
                    'errors' => $errors,
                ];
            }

            $document['supplier_id'] = (int) ($supplier_match['supplier_id'] ?? 0);
        }

        if ($kind === self::IMPORT_KIND_SALES) {
            $sales_result = $this->register_sales_document($document, (int) $user_id);
            return [
                'status' => (string) ($sales_result['status'] ?? self::IMPORT_STATUS_MANUAL_REQUIRED),
                'kind' => $kind,
                'invoice_number' => (string) ($document['invoice_number'] ?? ''),
                'errors' => (array) ($sales_result['errors'] ?? []),
            ];
        }

        $payload = $this->map_ksef_document_to_invoice($document, (int) $user_id, $kind);

        $duplicate = $this->detect_duplicate($payload);
        if ((bool) ($duplicate['is_primary_duplicate'] ?? false)) {
            return [
                'status' => 'duplicate',
                'kind' => $kind,
                'invoice_number' => (string) ($payload['invoice_number'] ?? ''),
                'errors' => [],
            ];
        }

        if ((bool) ($duplicate['is_fallback_conflict'] ?? false)) {
            $conflict_reason = __('Wykryto konflikt idempotencji: supplier_id + invoice_number.', 'erp-omd');
            $this->record_audit_reason($duplicate, $conflict_reason, (int) $user_id);

            return [
                'status' => self::IMPORT_STATUS_CONFLICT,
                'kind' => $kind,
                'invoice_number' => (string) ($payload['invoice_number'] ?? ''),
                'errors' => [$conflict_reason],
            ];
        }

        $result = $this->workflow_service->create_invoice($payload);
        if ((bool) ($result['ok'] ?? false)) {
            return [
                'status' => self::IMPORT_STATUS_IMPORTED,
                'kind' => $kind,
                'invoice_number' => (string) ($payload['invoice_number'] ?? ''),
                'errors' => [],
            ];
        }

        $errors = (array) ($result['errors'] ?? [__('Błąd importu KSeF.', 'erp-omd')]);
        if ($enqueue_retry) {
            $this->enqueue_retry($document, (int) $user_id, implode(' ', $errors));
        }

        return [
            'status' => self::IMPORT_STATUS_MANUAL_REQUIRED,
            'kind' => $kind,
            'invoice_number' => (string) ($payload['invoice_number'] ?? ''),
            'errors' => $errors,
        ];
    }

    /**
     * @param int $batch_limit
     * @param string|null $now_mysql
     * @return array<string,int>
     */
    public function process_retry_queue($batch_limit = 20, $now_mysql = null)
    {
        $now_mysql = (string) ($now_mysql ?: $this->now());
        $queue = $this->load_retry_queue();
        $processed = 0;
        $imported = 0;
        $manual_required = 0;
        $still_retrying = 0;

        foreach ($queue as $index => &$item) {
            if ($processed >= max(1, (int) $batch_limit)) {
                break;
            }

            $status = (string) ($item['status'] ?? self::IMPORT_STATUS_RETRYING);
            if ($status !== self::IMPORT_STATUS_RETRYING) {
                continue;
            }

            $next_retry_at = (string) ($item['next_retry_at'] ?? '');
            if ($next_retry_at !== '' && $this->to_timestamp($next_retry_at) > $this->to_timestamp($now_mysql)) {
                continue;
            }

            $processed++;
            $attempt = $this->attempt_import_document((array) ($item['document'] ?? []), (int) ($item['user_id'] ?? 0), false);
            if ((string) ($attempt['status'] ?? '') === self::IMPORT_STATUS_IMPORTED || (string) ($attempt['status'] ?? '') === 'duplicate') {
                $item['status'] = self::IMPORT_STATUS_IMPORTED;
                $item['last_error'] = '';
                $item['last_retry_at'] = $now_mysql;
                $item['next_retry_at'] = '';
                $imported++;
                continue;
            }

            if ((string) ($attempt['status'] ?? '') === self::IMPORT_STATUS_CONFLICT) {
                $item['status'] = self::IMPORT_STATUS_MANUAL_REQUIRED;
                $item['last_error'] = implode(' ', (array) ($attempt['errors'] ?? []));
                $item['last_retry_at'] = $now_mysql;
                $item['next_retry_at'] = '';
                $manual_required++;
                continue;
            }

            $decision = $this->build_retry_decision([
                'retry_attempts' => (int) ($item['retry_attempts'] ?? 0),
                'first_failed_at' => (string) ($item['first_failed_at'] ?? ''),
                'last_error' => implode(' ', (array) ($attempt['errors'] ?? [])),
            ], $now_mysql);

            $item['status'] = (string) ($decision['status'] ?? self::IMPORT_STATUS_MANUAL_REQUIRED);
            $item['retry_attempts'] = (int) ($decision['retry_attempts'] ?? ((int) ($item['retry_attempts'] ?? 0) + 1));
            $item['first_failed_at'] = (string) ($decision['first_failed_at'] ?? $item['first_failed_at'] ?? $now_mysql);
            $item['last_error'] = (string) ($decision['last_error'] ?? '');
            $item['last_retry_at'] = (string) ($decision['last_retry_at'] ?? $now_mysql);
            $item['next_retry_at'] = (string) ($decision['next_retry_at'] ?? '');

            if ($item['status'] === self::IMPORT_STATUS_MANUAL_REQUIRED) {
                $manual_required++;
            } else {
                $still_retrying++;
            }
        }
        unset($item);

        $this->save_retry_queue($queue);

        return [
            'processed' => $processed,
            'imported' => $imported,
            'manual_required' => $manual_required,
            'retrying' => $still_retrying,
        ];
    }

    /**
     * @param array<string,mixed> $document
     * @param int $user_id
     * @param string $last_error
     * @return void
     */
    public function enqueue_retry(array $document, $user_id, $last_error)
    {
        $queue = $this->load_retry_queue();
        $retry_key = $this->build_retry_key($document);

        foreach ($queue as &$row) {
            if ((string) ($row['retry_key'] ?? '') !== $retry_key) {
                continue;
            }

            if ((string) ($row['status'] ?? '') === self::IMPORT_STATUS_IMPORTED) {
                return;
            }

            $decision = $this->build_retry_decision([
                'retry_attempts' => (int) ($row['retry_attempts'] ?? 0),
                'first_failed_at' => (string) ($row['first_failed_at'] ?? ''),
                'last_error' => (string) $last_error,
            ]);

            $row['status'] = (string) ($decision['status'] ?? self::IMPORT_STATUS_MANUAL_REQUIRED);
            $row['retry_attempts'] = (int) ($decision['retry_attempts'] ?? 1);
            $row['first_failed_at'] = (string) ($decision['first_failed_at'] ?? $this->now());
            $row['last_error'] = (string) ($decision['last_error'] ?? '');
            $row['last_retry_at'] = (string) ($decision['last_retry_at'] ?? $this->now());
            $row['next_retry_at'] = (string) ($decision['next_retry_at'] ?? '');
            $this->save_retry_queue($queue);
            return;
        }
        unset($row);

        $decision = $this->build_retry_decision([
            'retry_attempts' => 0,
            'first_failed_at' => '',
            'last_error' => (string) $last_error,
        ]);

        $queue[] = [
            'retry_key' => $retry_key,
            'document' => $document,
            'user_id' => (int) $user_id,
            'status' => (string) ($decision['status'] ?? self::IMPORT_STATUS_RETRYING),
            'retry_attempts' => (int) ($decision['retry_attempts'] ?? 1),
            'first_failed_at' => (string) ($decision['first_failed_at'] ?? $this->now()),
            'last_error' => (string) ($decision['last_error'] ?? ''),
            'last_retry_at' => (string) ($decision['last_retry_at'] ?? $this->now()),
            'next_retry_at' => (string) ($decision['next_retry_at'] ?? ''),
        ];

        $this->save_retry_queue($queue);
    }

    /**
     * @param array<string,mixed> $document
     * @return array<string,mixed>
     */
    private function match_supplier_for_cost_document(array $document)
    {
        $supplier_id = (int) ($document['supplier_id'] ?? 0);
        if ($supplier_id > 0) {
            return ['ok' => true, 'supplier_id' => $supplier_id, 'status' => self::IMPORT_STATUS_IMPORTED, 'errors' => []];
        }

        if (! $this->supplier_repository || ! method_exists($this->supplier_repository, 'find_by_nip')) {
            return ['ok' => false, 'supplier_id' => 0, 'status' => self::IMPORT_STATUS_MANUAL_REQUIRED, 'errors' => [__('Repozytorium dostawców nie wspiera wyszukiwania po NIP.', 'erp-omd')]];
        }

        $supplier_nip = $this->extract_nip_value($document, ['seller_nip', 'sprzedawca_nip', 'seller', 'podmiot1', 'podmiot1_nip']);
        if ($supplier_nip === '') {
            return ['ok' => false, 'supplier_id' => 0, 'status' => self::IMPORT_STATUS_MANUAL_REQUIRED, 'errors' => [__('Brak NIP sprzedawcy do dopasowania dostawcy.', 'erp-omd')]];
        }

        $matches = (array) $this->supplier_repository->find_by_nip($supplier_nip);
        if (count($matches) === 1) {
            return ['ok' => true, 'supplier_id' => (int) ($matches[0]['id'] ?? 0), 'status' => self::IMPORT_STATUS_IMPORTED, 'errors' => []];
        }

        if (count($matches) > 1) {
            return ['ok' => false, 'supplier_id' => 0, 'status' => self::IMPORT_STATUS_CONFLICT, 'errors' => [__('Wiele dopasowań dostawcy po NIP. Wymagana moderacja manualna.', 'erp-omd')]];
        }

        return ['ok' => false, 'supplier_id' => 0, 'status' => self::IMPORT_STATUS_MANUAL_REQUIRED, 'errors' => [__('Brak dopasowania dostawcy po NIP. Wymagana moderacja manualna.', 'erp-omd')]];
    }



    /**
     * @param array<string,mixed> $document
     * @param int $user_id
     * @return array<string,mixed>
     */
    private function register_sales_document(array $document, $user_id)
    {
        $client_match = $this->match_client_for_sales_document($document);
        if (! (bool) ($client_match['ok'] ?? false)) {
            return [
                'status' => (string) ($client_match['status'] ?? self::IMPORT_STATUS_MANUAL_REQUIRED),
                'errors' => (array) ($client_match['errors'] ?? []),
            ];
        }

        $rows = $this->load_sales_inbox();
        $ksef_reference = trim((string) ($document['ksef_reference_number'] ?? ''));
        $invoice_number = trim((string) ($document['invoice_number'] ?? ''));

        foreach ($rows as $row) {
            if ($ksef_reference !== '' && (string) ($row['ksef_reference_number'] ?? '') === $ksef_reference) {
                return ['status' => 'duplicate', 'errors' => []];
            }

            if ($ksef_reference === '' && (string) ($row['invoice_number'] ?? '') === $invoice_number && (int) ($row['client_id'] ?? 0) === (int) ($client_match['client_id'] ?? 0)) {
                return ['status' => self::IMPORT_STATUS_CONFLICT, 'errors' => [__('Wykryto konflikt sprzedażowej faktury KSeF (client_id + invoice_number).', 'erp-omd')]];
            }
        }

        $rows[] = [
            'id' => $this->next_sales_inbox_id($rows),
            'invoice_number' => $invoice_number,
            'issue_date' => (string) ($document['issue_date'] ?? ''),
            'ksef_reference_number' => $ksef_reference,
            'buyer_nip' => $this->extract_nip_value($document, ['buyer_nip', 'nabywca_nip', 'buyer', 'podmiot2', 'podmiot2_nip']),
            'seller_nip' => $this->extract_nip_value($document, ['seller_nip', 'sprzedawca_nip', 'seller', 'podmiot1', 'podmiot1_nip']),
            'net_amount' => (float) ($document['net_amount'] ?? 0),
            'vat_amount' => (float) ($document['vat_amount'] ?? 0),
            'gross_amount' => (float) ($document['gross_amount'] ?? 0),
            'client_id' => (int) ($client_match['client_id'] ?? 0),
            'project_id' => 0,
            'status' => 'ready',
            'source' => 'ksef_sales',
            'created_by_user_id' => (int) $user_id,
            'created_at' => $this->now(),
        ];

        $this->save_sales_inbox($rows);

        return ['status' => self::IMPORT_STATUS_IMPORTED, 'errors' => []];
    }

    /**
     * @param array<string,mixed> $document
     * @return array<string,mixed>
     */
    private function match_client_for_sales_document(array $document)
    {
        if (! $this->client_repository || ! method_exists($this->client_repository, 'find_by_nip')) {
            return ['ok' => false, 'status' => self::IMPORT_STATUS_MANUAL_REQUIRED, 'errors' => [__('Repozytorium klientów nie wspiera wyszukiwania po NIP.', 'erp-omd')]];
        }

        $buyer_nip = $this->extract_nip_value($document, ['buyer_nip', 'nabywca_nip', 'buyer', 'podmiot2', 'podmiot2_nip']);
        if ($buyer_nip === '') {
            return ['ok' => false, 'status' => self::IMPORT_STATUS_MANUAL_REQUIRED, 'errors' => [__('Brak NIP nabywcy do mapowania klienta.', 'erp-omd')]];
        }

        $matches = (array) $this->client_repository->find_by_nip($buyer_nip);
        if (count($matches) === 1) {
            return ['ok' => true, 'client_id' => (int) ($matches[0]['id'] ?? 0), 'status' => 'ready', 'errors' => []];
        }

        if (count($matches) > 1) {
            return ['ok' => false, 'status' => self::IMPORT_STATUS_CONFLICT, 'errors' => [__('Wiele dopasowań klienta po NIP. Wymagana moderacja manualna.', 'erp-omd')]];
        }

        return ['ok' => false, 'status' => self::IMPORT_STATUS_MANUAL_REQUIRED, 'errors' => [__('Brak dopasowania klienta po NIP. Wymagana moderacja manualna.', 'erp-omd')]];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function list_sales_inbox()
    {
        return $this->load_sales_inbox();
    }

    /**
     * @param string $xml_content
     * @param int $user_id
     * @return array<string,mixed>
     */
    public function import_sales_xml($xml_content, $user_id)
    {
        $document = $this->parse_ksef_xml_to_document((string) $xml_content);
        if (! is_array($document) || $document === []) {
            return ['total' => 1, 'imported' => 0, 'failed' => 1, 'errors' => [['index' => 0, 'invoice_number' => '', 'status' => self::IMPORT_STATUS_MANUAL_REQUIRED, 'errors' => [__('Nie udało się sparsować XML KSeF.', 'erp-omd')]]]];
        }

        return $this->import_documents([$document], (int) $user_id);
    }

    /**
     * @param string $xml_content
     * @param int $user_id
     * @return array<string,mixed>
     */
    public function import_cost_xml($xml_content, $user_id)
    {
        $document = $this->parse_ksef_xml_to_document((string) $xml_content);
        if (! is_array($document) || $document === []) {
            return ['total' => 1, 'imported' => 0, 'failed' => 1, 'errors' => [['index' => 0, 'invoice_number' => '', 'status' => self::IMPORT_STATUS_MANUAL_REQUIRED, 'errors' => [__('Nie udało się sparsować XML KSeF.', 'erp-omd')]]]];
        }

        return $this->import_documents([$document], (int) $user_id);
    }

    /**
     * @param string $xml_content
     * @return array<string,mixed>
     */
    private function parse_ksef_xml_to_document($xml_content)
    {
        if (! function_exists('simplexml_load_string')) {
            return [];
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_content);
        if (! ($xml instanceof SimpleXMLElement)) {
            return [];
        }

        $invoice_number = $this->xpath_first_text($xml, ['//*[local-name()="P_2"]']);
        $issue_date = $this->xpath_first_text($xml, [
            '//*[local-name()="P_1"]',
            '//*[local-name()="issueDate"]',
            '//*[local-name()="IssueDate"]',
            '//*[local-name()="DataWystawienia"]',
        ]);
        $issue_date = $this->normalize_issue_date($issue_date);
        $buyer_nip = $this->xpath_first_text($xml, ['//*[local-name()="Podmiot2"]//*[local-name()="NIP"]']);
        $seller_nip = $this->xpath_first_text($xml, ['//*[local-name()="Podmiot1"]//*[local-name()="NIP"]']);
        $ksef_reference = $this->xpath_first_text($xml, ['//*[local-name()="NumerKSeF"]']);

        $net_amount = $this->xpath_first_decimal($xml, [
            '//*[local-name()="FaCtrl"]//*[local-name()="B"]',
            '//*[local-name()="P_13_1"]',
            '//*[local-name()="P_13_2"]',
            '//*[local-name()="P_13_3"]',
            '//*[local-name()="P_13_4"]',
            '//*[local-name()="P_13_5"]',
        ]);
        if ($net_amount <= 0) {
            $net_amount = $this->xpath_sum_decimals($xml, [
                '//*[local-name()="FaWiersz"]//*[local-name()="P_11"]',
                '//*[local-name()="FaWiersz"]//*[local-name()="P_11A"]',
            ]);
        }

        $vat_amount = $this->xpath_first_decimal($xml, [
            '//*[local-name()="FaCtrl"]//*[local-name()="V"]',
            '//*[local-name()="P_14_1"]',
            '//*[local-name()="P_14_2"]',
            '//*[local-name()="P_14_3"]',
            '//*[local-name()="P_14_4"]',
            '//*[local-name()="P_14_5"]',
        ]);

        $gross_amount = $this->xpath_first_decimal($xml, [
            '//*[local-name()="FaCtrl"]//*[local-name()="WartoscFaktury"]',
            '//*[local-name()="P_15"]',
        ]);
        if ($gross_amount <= 0) {
            $gross_amount = $net_amount + $vat_amount;
        }

        $vat_rate = $this->xpath_first_decimal($xml, [
            '//*[local-name()="FaWiersz"]//*[local-name()="P_12"]',
        ]);
        if ($vat_rate <= 0 && $net_amount > 0 && $vat_amount > 0) {
            $vat_rate = round(($vat_amount / $net_amount) * 100, 2);
        }

        if ($invoice_number === '' && $ksef_reference === '') {
            return [];
        }

        return [
            'invoice_number' => $invoice_number,
            'issue_date' => $issue_date,
            'buyer_nip' => $buyer_nip,
            'seller_nip' => $seller_nip,
            'ksef_reference_number' => $ksef_reference,
            'net_amount' => $net_amount,
            'vat_amount' => $vat_amount,
            'gross_amount' => $gross_amount,
            'vat_rate' => $vat_rate,
        ];
    }

    /**
     * @param SimpleXMLElement $xml
     * @param array<int,string> $paths
     * @return string
     */
    private function xpath_first_text(SimpleXMLElement $xml, array $paths)
    {
        foreach ($paths as $path) {
            $nodes = $xml->xpath($path);
            if (! is_array($nodes) || $nodes === []) {
                continue;
            }

            foreach ($nodes as $node) {
                $value = trim((string) $node);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    /**
     * @param SimpleXMLElement $xml
     * @param array<int,string> $paths
     * @return float
     */
    private function xpath_first_decimal(SimpleXMLElement $xml, array $paths)
    {
        foreach ($paths as $path) {
            $nodes = $xml->xpath($path);
            if (! is_array($nodes) || $nodes === []) {
                continue;
            }

            foreach ($nodes as $node) {
                $value = $this->parse_decimal((string) $node);
                if ($value !== 0.0) {
                    return $value;
                }
            }
        }

        return 0.0;
    }

    /**
     * @param SimpleXMLElement $xml
     * @param array<int,string> $paths
     * @return float
     */
    private function xpath_sum_decimals(SimpleXMLElement $xml, array $paths)
    {
        $sum = 0.0;
        foreach ($paths as $path) {
            $nodes = $xml->xpath($path);
            if (! is_array($nodes) || $nodes === []) {
                continue;
            }

            foreach ($nodes as $node) {
                $sum += $this->parse_decimal((string) $node);
            }
        }

        return $sum;
    }

    /**
     * @param string $value
     * @return float
     */
    private function parse_decimal($value)
    {
        $normalized = preg_replace('/[^0-9,.\-]/', '', (string) $value);
        if (! is_string($normalized) || $normalized === '') {
            return 0.0;
        }

        if (strpos($normalized, ',') !== false && strpos($normalized, '.') === false) {
            $normalized = str_replace(',', '.', $normalized);
        } elseif (strpos($normalized, ',') !== false && strpos($normalized, '.') !== false) {
            $normalized = str_replace(',', '', $normalized);
        }

        return (float) $normalized;
    }

    /**
     * @param string $value
     * @return string
     */
    private function normalize_issue_date($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})[T ]/', $value, $matches) === 1) {
            return (string) ($matches[1] ?? '');
        }

        return $value;
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function list_moderation_queue(array $filters = [])
    {
        $queue = $this->load_retry_queue();
        $wanted_status = trim((string) ($filters['status'] ?? ''));
        $rows = [];

        foreach ($queue as $item) {
            $normalized_status = $this->normalize_moderation_status((string) ($item['status'] ?? ''));
            if ($wanted_status !== '' && $normalized_status !== $wanted_status) {
                continue;
            }

            $rows[] = [
                'retry_key' => (string) ($item['retry_key'] ?? ''),
                'status' => $normalized_status,
                'retry_attempts' => (int) ($item['retry_attempts'] ?? 0),
                'last_error' => (string) ($item['last_error'] ?? ''),
                'last_retry_at' => (string) ($item['last_retry_at'] ?? ''),
                'next_retry_at' => (string) ($item['next_retry_at'] ?? ''),
                'document' => (array) ($item['document'] ?? []),
                'user_id' => (int) ($item['user_id'] ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * @param string $retry_key
     * @param string $action
     * @param array<string,mixed> $payload
     * @param int $user_id
     * @return array<string,mixed>
     */
    public function moderate_queue_entry($retry_key, $action, array $payload, $user_id)
    {
        $retry_key = trim((string) $retry_key);
        $action = trim((string) $action);
        $queue = $this->load_retry_queue();

        foreach ($queue as $index => &$item) {
            if ((string) ($item['retry_key'] ?? '') !== $retry_key) {
                continue;
            }

            if ($action === 'assign_supplier') {
                $item['document']['supplier_id'] = max(0, (int) ($payload['supplier_id'] ?? 0));
                $item['status'] = self::IMPORT_STATUS_MANUAL_REQUIRED;
                $item['last_error'] = '';
            } elseif ($action === 'assign_project') {
                $item['document']['project_id'] = max(0, (int) ($payload['project_id'] ?? 0));
                $item['status'] = self::IMPORT_STATUS_MANUAL_REQUIRED;
                $item['last_error'] = '';
            } elseif ($action === 'approve') {
                $result = $this->attempt_import_document((array) ($item['document'] ?? []), (int) ($item['user_id'] ?? 0), false);
                $item['status'] = (string) (($result['status'] ?? '') === self::IMPORT_STATUS_IMPORTED || ($result['status'] ?? '') === 'duplicate' ? self::IMPORT_STATUS_IMPORTED : self::IMPORT_STATUS_MANUAL_REQUIRED);
                $item['last_error'] = implode(' ', (array) ($result['errors'] ?? []));
                $item['last_retry_at'] = $this->now();
                $item['next_retry_at'] = '';
            } elseif ($action === 'reject') {
                $item['status'] = 'rejected';
                $item['last_error'] = __('Odrzucone manualnie przez operatora.', 'erp-omd');
                $item['last_retry_at'] = $this->now();
                $item['next_retry_at'] = '';
            } elseif ($action === 'delete') {
                $deleted_item = $item;
                $deleted_item['status'] = 'deleted';
                $deleted_item['last_error'] = __('Usunięte manualnie z kolejki przez operatora.', 'erp-omd');
                unset($queue[$index]);
                $queue = array_values($queue);

                $this->append_moderation_audit($retry_key, $action, $payload, (int) $user_id, $deleted_item);
                $this->save_retry_queue($queue);

                return ['ok' => true, 'item' => $deleted_item, 'errors' => []];
            } else {
                return ['ok' => false, 'errors' => [__('Nieobsługiwana akcja moderacji KSeF.', 'erp-omd')]];
            }

            $this->append_moderation_audit($retry_key, $action, $payload, (int) $user_id, $item);
            $this->save_retry_queue($queue);

            return ['ok' => true, 'item' => $item, 'errors' => []];
        }
        unset($item);

        return ['ok' => false, 'errors' => [__('Nie znaleziono wpisu kolejki moderacji KSeF.', 'erp-omd')]];
    }

    /**
     * @param array<int,string> $retry_keys
     * @param string $action
     * @param array<string,mixed> $payload
     * @param int $user_id
     * @return array<string,mixed>
     */
    public function bulk_moderate_queue_entries(array $retry_keys, $action, array $payload, $user_id)
    {
        $done = 0;
        $errors = [];

        foreach ($retry_keys as $retry_key) {
            $result = $this->moderate_queue_entry((string) $retry_key, $action, $payload, (int) $user_id);
            if ((bool) ($result['ok'] ?? false)) {
                $done++;
                continue;
            }

            $errors[] = [
                'retry_key' => (string) $retry_key,
                'errors' => (array) ($result['errors'] ?? []),
            ];
        }

        return ['ok' => $errors === [], 'processed' => $done, 'errors' => $errors];
    }

    /**
     * @param array<string,mixed> $document
     * @return array<string,mixed>
     */
    public function classify_document(array $document)
    {
        $buyer_nip = $this->extract_nip_value($document, ['buyer_nip', 'nabywca_nip', 'buyer', 'podmiot2', 'podmiot2_nip']);
        $seller_nip = $this->extract_nip_value($document, ['seller_nip', 'sprzedawca_nip', 'seller', 'podmiot1', 'podmiot1_nip']);
        $our_nip = $this->normalize_nip((string) ($document['our_company_nip'] ?? $document['company_nip'] ?? $this->get_our_company_nip()));

        if ($our_nip === '') {
            return [
                'ok' => false,
                'kind' => '',
                'errors' => [__('Brak NIP naszej firmy do klasyfikacji dokumentu KSeF.', 'erp-omd')],
            ];
        }

        if ($buyer_nip === $our_nip) {
            return ['ok' => true, 'kind' => self::IMPORT_KIND_COST, 'errors' => []];
        }

        if ($seller_nip === $our_nip) {
            return ['ok' => true, 'kind' => self::IMPORT_KIND_SALES, 'errors' => []];
        }

        return [
            'ok' => false,
            'kind' => '',
            'errors' => [__('Nie rozpoznano roli NIP naszej firmy (Nabywca/Sprzedawca).', 'erp-omd')],
        ];
    }

    /**
     * @param int $invoice_id
     * @param array<string,mixed> $moderation_payload
     * @param int $user_id
     * @return array<string,mixed>
     */
    public function moderate_imported_invoice($invoice_id, array $moderation_payload, $user_id)
    {
        $payload = [
            'status' => (string) ($moderation_payload['status'] ?? ''),
            'supplier_id' => (int) ($moderation_payload['supplier_id'] ?? 0),
            'project_id' => (int) ($moderation_payload['project_id'] ?? 0),
            'invoice_number' => (string) ($moderation_payload['invoice_number'] ?? ''),
            'issue_date' => (string) ($moderation_payload['issue_date'] ?? ''),
            'net_amount' => (float) ($moderation_payload['net_amount'] ?? 0),
            'vat_amount' => (float) ($moderation_payload['vat_amount'] ?? 0),
            'gross_amount' => (float) ($moderation_payload['gross_amount'] ?? 0),
            'source' => 'ksef',
            'updated_by_user_id' => (int) $user_id,
        ];

        return $this->workflow_service->update_invoice((int) $invoice_id, $payload, (int) $user_id);
    }

    /**
     * @param array<string,mixed> $document
     * @param int $user_id
     * @param string $kind
     * @return array<string,mixed>
     */
    private function map_ksef_document_to_invoice(array $document, $user_id, $kind)
    {
        $project_id = (int) ($document['project_id'] ?? 0);
        if ($kind === self::IMPORT_KIND_COST) {
            $project_id = 0;
        }

        return [
            'supplier_id' => (int) ($document['supplier_id'] ?? 0),
            'project_id' => $project_id,
            'invoice_number' => (string) ($document['invoice_number'] ?? ''),
            'issue_date' => (string) ($document['issue_date'] ?? ''),
            'status' => 'zaimportowana',
            'net_amount' => (float) ($document['net_amount'] ?? 0),
            'vat_amount' => (float) ($document['vat_amount'] ?? 0),
            'gross_amount' => (float) ($document['gross_amount'] ?? 0),
            'source' => 'ksef',
            'ksef_reference_number' => (string) ($document['ksef_reference_number'] ?? ''),
            'document_kind' => $kind,
            'created_by_user_id' => (int) $user_id,
            'updated_by_user_id' => (int) $user_id,
        ];
    }

    /**
     * @param array<string,mixed> $invoice_payload
     * @return array<string,mixed>
     */
    private function detect_duplicate(array $invoice_payload)
    {
        $result = [
            'is_primary_duplicate' => false,
            'is_fallback_conflict' => false,
            'existing_invoice' => null,
        ];

        if ($this->invoice_repository && method_exists($this->invoice_repository, 'find_by_ksef_reference')) {
            $existing_by_reference = $this->invoice_repository->find_by_ksef_reference((string) ($invoice_payload['ksef_reference_number'] ?? ''));
            if (is_array($existing_by_reference) && $existing_by_reference !== []) {
                $result['is_primary_duplicate'] = true;
                $result['existing_invoice'] = $existing_by_reference;
                return $result;
            }
        }

        if ($this->invoice_repository && method_exists($this->invoice_repository, 'find_by_supplier_and_invoice_number')) {
            $existing_by_fallback = $this->invoice_repository->find_by_supplier_and_invoice_number(
                (int) ($invoice_payload['supplier_id'] ?? 0),
                (string) ($invoice_payload['invoice_number'] ?? '')
            );
            if (is_array($existing_by_fallback) && $existing_by_fallback !== []) {
                $result['is_fallback_conflict'] = true;
                $result['existing_invoice'] = $existing_by_fallback;
                return $result;
            }
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $duplicate_info
     * @param string $reason
     * @param int $user_id
     * @return void
     */
    private function record_audit_reason(array $duplicate_info, $reason, $user_id)
    {
        if (! $this->audit_repository || ! method_exists($this->audit_repository, 'insert_many')) {
            return;
        }

        $invoice_id = (int) (($duplicate_info['existing_invoice']['id'] ?? 0));
        if ($invoice_id <= 0) {
            return;
        }

        $this->audit_repository->insert_many([
            [
                'invoice_id' => $invoice_id,
                'field_name' => 'ksef_import_conflict_reason',
                'before_value' => '',
                'after_value' => (string) $reason,
                'changed_by_user_id' => (int) $user_id,
                'changed_at' => $this->now(),
            ],
        ]);
    }

    /**
     * @param array<string,mixed> $state
     * @param string|null $now_mysql
     * @return array<string,mixed>
     */
    public function build_retry_decision(array $state, $now_mysql = null)
    {
        $now_timestamp = $this->to_timestamp($now_mysql ?: $this->now());
        $first_failed_at = (string) ($state['first_failed_at'] ?? '');
        $first_failed_timestamp = $this->to_timestamp($first_failed_at);
        if ($first_failed_timestamp <= 0) {
            $first_failed_timestamp = $now_timestamp;
            $first_failed_at = gmdate('Y-m-d H:i:s', $first_failed_timestamp);
        }

        $attempts = max(0, (int) ($state['retry_attempts'] ?? 0));
        $elapsed_seconds = max(0, $now_timestamp - $first_failed_timestamp);

        $decision = [
            'status' => self::IMPORT_STATUS_RETRYING,
            'retry_attempts' => $attempts + 1,
            'first_failed_at' => $first_failed_at,
            'last_error' => (string) ($state['last_error'] ?? ''),
            'last_retry_at' => gmdate('Y-m-d H:i:s', $now_timestamp),
            'next_retry_at' => '',
            'elapsed_seconds' => $elapsed_seconds,
            'should_retry' => true,
        ];

        if ($elapsed_seconds >= $this->retry_max_window_seconds) {
            $decision['status'] = self::IMPORT_STATUS_MANUAL_REQUIRED;
            $decision['should_retry'] = false;
            return $decision;
        }

        $schedule_index = min($attempts, count($this->retry_schedule_seconds) - 1);
        $next_delay = (int) $this->retry_schedule_seconds[$schedule_index];
        $next_retry_ts = $now_timestamp + max(60, $next_delay);

        if (($next_retry_ts - $first_failed_timestamp) > $this->retry_max_window_seconds) {
            $decision['status'] = self::IMPORT_STATUS_MANUAL_REQUIRED;
            $decision['should_retry'] = false;
            return $decision;
        }

        $decision['next_retry_at'] = gmdate('Y-m-d H:i:s', $next_retry_ts);
        return $decision;
    }

    /**
     * @param array<string,mixed> $document
     * @param array<int,string> $keys
     * @return string
     */
    private function extract_nip_value(array $document, array $keys)
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $document)) {
                continue;
            }

            $value = $document[$key];
            if (is_array($value)) {
                $nip = (string) ($value['nip'] ?? $value['NIP'] ?? '');
                $normalized = $this->normalize_nip($nip);
                if ($normalized !== '') {
                    return $normalized;
                }
                continue;
            }

            $normalized = $this->normalize_nip((string) $value);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return '';
    }

    /**
     * @param string $nip
     * @return string
     */
    private function normalize_nip($nip)
    {
        $nip = preg_replace('/[^0-9]/', '', (string) $nip);
        return is_string($nip) ? $nip : '';
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function load_retry_queue()
    {
        if (! function_exists('get_option')) {
            return [];
        }

        $queue = (array) get_option(self::OPTION_RETRY_QUEUE, []);
        return array_values(array_filter($queue, static function ($item) {
            return is_array($item) && ! empty($item['retry_key']);
        }));
    }

    /**
     * @param array<int,array<string,mixed>> $queue
     * @return void
     */
    private function save_retry_queue(array $queue)
    {
        if (! function_exists('update_option')) {
            return;
        }

        update_option(self::OPTION_RETRY_QUEUE, array_values($queue), false);
    }

    /**
     * @param array<string,mixed> $document
     * @return string
     */
    private function build_retry_key(array $document)
    {
        $reference = trim((string) ($document['ksef_reference_number'] ?? ''));
        if ($reference !== '') {
            return 'ref:' . mb_strtolower($reference);
        }

        return 'fallback:' . (int) ($document['supplier_id'] ?? 0) . ':' . mb_strtolower(trim((string) ($document['invoice_number'] ?? '')));
    }

    /**
     * @return string
     */
    private function get_our_company_nip()
    {
        if (! function_exists('get_option')) {
            return '';
        }

        return (string) get_option('erp_omd_company_nip', '');
    }


    /**
     * @param string $raw_status
     * @return string
     */
    private function normalize_moderation_status($raw_status)
    {
        $raw_status = trim((string) $raw_status);
        if ($raw_status === self::IMPORT_STATUS_RETRYING) {
            return 'new';
        }
        if ($raw_status === self::IMPORT_STATUS_IMPORTED) {
            return 'ready';
        }
        if ($raw_status === '') {
            return 'manual_required';
        }

        return $raw_status;
    }

    /**
     * @param string $retry_key
     * @param string $action
     * @param array<string,mixed> $payload
     * @param int $user_id
     * @param array<string,mixed> $after_item
     * @return void
     */
    private function append_moderation_audit($retry_key, $action, array $payload, $user_id, array $after_item)
    {
        if (! function_exists('get_option') || ! function_exists('update_option')) {
            return;
        }

        $log = (array) get_option(self::OPTION_MODERATION_AUDIT, []);
        $log[] = [
            'retry_key' => (string) $retry_key,
            'action' => (string) $action,
            'payload' => $payload,
            'user_id' => (int) $user_id,
            'status_after' => (string) ($after_item['status'] ?? ''),
            'created_at' => $this->now(),
        ];

        if (count($log) > 500) {
            $log = array_slice($log, -500);
        }

        update_option(self::OPTION_MODERATION_AUDIT, $log, false);
    }


    /**
     * @return array<int,array<string,mixed>>
     */
    private function load_sales_inbox()
    {
        if (! function_exists('get_option')) {
            return [];
        }

        return (array) get_option(self::OPTION_SALES_INBOX, []);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return void
     */
    private function save_sales_inbox(array $rows)
    {
        if (! function_exists('update_option')) {
            return;
        }

        update_option(self::OPTION_SALES_INBOX, array_values($rows), false);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return int
     */
    private function next_sales_inbox_id(array $rows)
    {
        $max_id = 0;
        foreach ($rows as $row) {
            $max_id = max($max_id, (int) ($row['id'] ?? 0));
        }

        return $max_id + 1;
    }

    /**
     * @return string
     */
    private function now()
    {
        if (function_exists('current_time')) {
            return (string) current_time('mysql');
        }

        return gmdate('Y-m-d H:i:s');
    }

    /**
     * @param string $date_value
     * @return int
     */
    private function to_timestamp($date_value)
    {
        $date_value = trim((string) $date_value);
        if ($date_value === '') {
            return 0;
        }

        $timestamp = strtotime($date_value . ' UTC');
        if ($timestamp === false) {
            return 0;
        }

        return (int) $timestamp;
    }
}
