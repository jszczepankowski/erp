<?php

class ERP_OMD_KSeF_API_Sync_Service
{
    const OPTION_TOKEN_ENC = 'erp_omd_ksef_api_token_enc';
    const OPTION_ENABLED = 'erp_omd_ksef_api_enabled';
    const OPTION_MODE = 'erp_omd_ksef_sync_mode';
    const OPTION_REGISTRATION_DATE = 'erp_omd_ksef_registration_date';
    const OPTION_BACKFILL_DAYS = 'erp_omd_ksef_backfill_days';
    const OPTION_LAST_SYNC_AT = 'erp_omd_ksef_api_last_sync_at';
    const OPTION_LAST_ERROR = 'erp_omd_ksef_api_last_error';
    const OPTION_LAST_RESULT = 'erp_omd_ksef_api_last_result';
    const OPTION_LAST_CURSOR = 'erp_omd_ksef_api_last_cursor';
    const OPTION_ALERT_AFTER_HOURS = 'erp_omd_ksef_api_alert_after_hours';

    private $import_service;
    private $company_nip;

    public function __construct(ERP_OMD_KSeF_Import_Service $import_service, $company_nip = '')
    {
        $this->import_service = $import_service;
        $this->company_nip = preg_replace('/[^0-9]/', '', (string) $company_nip);
    }

    public function run_scheduled_sync()
    {
        if (! (bool) get_option(self::OPTION_ENABLED, false)) {
            return ['ok' => true, 'skipped' => true, 'reason' => 'disabled'];
        }

        return $this->sync([
            'scope' => 'both',
            'mode' => (string) get_option(self::OPTION_MODE, 'from_now'),
            'force_now' => false,
        ]);
    }

    public function sync(array $params = [])
    {
        $token = trim((string) $this->decrypt_value((string) get_option(self::OPTION_TOKEN_ENC, '')));
        if ($token === '') {
            return $this->fail(__('Brak tokenu KSeF API.', 'erp-omd'));
        }

        $scope = in_array((string) ($params['scope'] ?? 'both'), ['cost', 'sales', 'both'], true)
            ? (string) ($params['scope'] ?? 'both')
            : 'both';
        $mode = in_array((string) ($params['mode'] ?? 'from_now'), ['from_now', 'backfill', 'all'], true)
            ? (string) ($params['mode'] ?? 'from_now')
            : 'from_now';

        $window = $this->build_window($mode, $params);
        $documents = $this->fetch_documents($token, $window['from'], $window['to']);
        if (is_wp_error($documents)) {
            return $this->fail($documents->get_error_message());
        }

        $normalized = $this->normalize_documents((array) $documents, $scope);
        $result = $this->import_service->import_documents($normalized, (int) get_current_user_id());

        $payload = [
            'ok' => true,
            'scope' => $scope,
            'mode' => $mode,
            'from' => $window['from'],
            'to' => $window['to'],
            'fetched' => count($normalized),
            'imported' => (int) ($result['imported'] ?? 0),
            'failed' => (int) ($result['failed'] ?? 0),
            'last_error' => '',
        ];
        update_option(self::OPTION_LAST_SYNC_AT, current_time('mysql'));
        update_option(self::OPTION_LAST_ERROR, '');
        update_option(self::OPTION_LAST_RESULT, $payload, false);
        update_option(self::OPTION_LAST_CURSOR, $window['to']);

        return $payload;
    }

    private function build_window($mode, array $params)
    {
        $to = current_time('mysql');
        $to_date = current_time('Y-m-d');
        if ($mode === 'backfill') {
            $days = max(1, min(90, (int) ($params['backfill_days'] ?? get_option(self::OPTION_BACKFILL_DAYS, 30))));
            $from = gmdate('Y-m-d H:i:s', strtotime($to . ' -' . $days . ' days'));
            return ['from' => $from, 'to' => $to];
        }
        if ($mode === 'all') {
            $registration_date = sanitize_text_field((string) get_option(self::OPTION_REGISTRATION_DATE, ''));
            $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $registration_date) === 1 ? ($registration_date . ' 00:00:00') : ($to_date . ' 00:00:00');
            $days = max(1, min(90, (int) get_option(self::OPTION_BACKFILL_DAYS, 90)));
            $cap_to = gmdate('Y-m-d H:i:s', strtotime($from . ' +' . $days . ' days'));
            return ['from' => $from, 'to' => min($to, $cap_to)];
        }

        $cursor = sanitize_text_field((string) get_option(self::OPTION_LAST_CURSOR, ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $cursor) === 1) {
            return ['from' => $cursor, 'to' => $to];
        }

        return ['from' => $to_date . ' 00:00:00', 'to' => $to];
    }

    private function fetch_documents($token, $from, $to)
    {
        $body = [
            'queryCriteria' => [
                'invoiceDateFrom' => str_replace(' ', 'T', $from),
                'invoiceDateTo' => str_replace(' ', 'T', $to),
            ],
            'pageOffset' => 0,
            'pageSize' => 100,
        ];
        $response = wp_remote_post('https://ksefapi.mf.gov.pl/api/v2/invoices/query/metadata', [
            'timeout' => 25,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'KSeF-Token' => $token,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code >= 400) {
            return new WP_Error('erp_omd_ksef_sync_http_error', (string) ($payload['message'] ?? __('Błąd pobierania metadanych KSeF.', 'erp-omd')));
        }

        $items = $payload['invoices'] ?? $payload['items'] ?? [];
        return is_array($items) ? $items : [];
    }

    private function normalize_documents(array $items, $scope)
    {
        $documents = [];
        foreach ($items as $item) {
            $row = is_array($item) ? $item : [];
            $invoice_number = (string) ($row['invoiceNumber'] ?? $row['number'] ?? '');
            $ksef_reference = (string) ($row['ksefReferenceNumber'] ?? $row['ksefNumber'] ?? $row['referenceNumber'] ?? '');
            $buyer_nip = preg_replace('/[^0-9]/', '', (string) ($row['buyerNip'] ?? $row['buyerTaxNumber'] ?? ''));
            $seller_nip = preg_replace('/[^0-9]/', '', (string) ($row['sellerNip'] ?? $row['sellerTaxNumber'] ?? ''));
            $seller_name = trim((string) ($row['sellerName'] ?? $row['seller']['name'] ?? ''));
            $issue_date = (string) ($row['issueDate'] ?? $row['invoiceDate'] ?? '');
            if (strpos($issue_date, 'T') !== false) {
                $issue_date = substr($issue_date, 0, 10);
            }

            if ($invoice_number === '' && $ksef_reference === '') {
                continue;
            }
            $kind = $this->resolve_kind($buyer_nip, $seller_nip);
            if ($scope === 'cost' && $kind !== 'cost') {
                continue;
            }
            if ($scope === 'sales' && $kind !== 'sales') {
                continue;
            }

            $documents[] = [
                'invoice_number' => $invoice_number,
                'issue_date' => $issue_date,
                'buyer_nip' => $buyer_nip,
                'seller_nip' => $seller_nip,
                'seller_name' => $seller_name,
                'our_company_nip' => $this->company_nip,
                'ksef_reference_number' => $ksef_reference,
                'net_amount' => (float) ($row['netAmount'] ?? 0),
                'vat_amount' => (float) ($row['vatAmount'] ?? 0),
                'gross_amount' => (float) ($row['grossAmount'] ?? 0),
            ];
        }

        return $documents;
    }

    private function resolve_kind($buyer_nip, $seller_nip)
    {
        if ($this->company_nip !== '' && $buyer_nip === $this->company_nip) {
            return 'cost';
        }
        if ($this->company_nip !== '' && $seller_nip === $this->company_nip) {
            return 'sales';
        }

        return 'unknown';
    }

    private function fail($message)
    {
        $payload = [
            'ok' => false,
            'last_error' => (string) $message,
            'run_at' => current_time('mysql'),
        ];
        update_option(self::OPTION_LAST_ERROR, (string) $message);
        update_option(self::OPTION_LAST_RESULT, $payload, false);

        return $payload;
    }

    private function decrypt_value($value)
    {
        if ($value === '') {
            return '';
        }
        $key = hash('sha256', (string) wp_salt('auth'), true);
        $iv = substr(hash('sha256', (string) wp_salt('secure_auth')), 0, 16);
        $decoded = base64_decode((string) $value, true);
        if ($decoded === false) {
            return '';
        }
        $plain = openssl_decrypt($decoded, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return is_string($plain) ? $plain : '';
    }
}
