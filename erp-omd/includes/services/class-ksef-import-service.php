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

    /** @var mixed */
    private $workflow_service;

    /** @var mixed */
    private $invoice_repository;

    /** @var mixed */
    private $audit_repository;

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
     */
    public function __construct($workflow_service, $invoice_repository = null, $audit_repository = null, $retry_max_window_seconds = null, array $retry_schedule_seconds = null)
    {
        $this->workflow_service = $workflow_service;
        $this->invoice_repository = $invoice_repository;
        $this->audit_repository = $audit_repository;
        $this->retry_max_window_seconds = is_int($retry_max_window_seconds) && $retry_max_window_seconds > 0 ? $retry_max_window_seconds : 90 * 60;
        $this->retry_schedule_seconds = $retry_schedule_seconds ?: [300, 900, 1800, 3600, 5400];
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

        foreach ($queue as &$item) {
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
