<?php

class ERP_OMD_KSeF_API_Sync_Service
{
    const OPTION_TOKEN_ENC = 'erp_omd_ksef_api_token_enc';
    const OPTION_REFRESH_TOKEN_ENC = 'erp_omd_ksef_api_refresh_token_enc';
    const OPTION_AP_TOKEN_ENC = 'erp_omd_ksef_ap_token_enc';
    const OPTION_PUBLIC_KEY_PEM = 'erp_omd_ksef_public_key_pem';
    const OPTION_ENABLED = 'erp_omd_ksef_api_enabled';
    const OPTION_MODE = 'erp_omd_ksef_sync_mode';
    const OPTION_REGISTRATION_DATE = 'erp_omd_ksef_registration_date';
    const OPTION_BACKFILL_DAYS = 'erp_omd_ksef_backfill_days';
    const OPTION_LAST_SYNC_AT = 'erp_omd_ksef_api_last_sync_at';
    const OPTION_LAST_ERROR = 'erp_omd_ksef_api_last_error';
    const OPTION_LAST_RESULT = 'erp_omd_ksef_api_last_result';
    const OPTION_LAST_CURSOR = 'erp_omd_ksef_api_last_cursor';
    const OPTION_ALERT_AFTER_HOURS = 'erp_omd_ksef_api_alert_after_hours';
    const OPTION_API_BASE_URL = 'erp_omd_ksef_api_base_url';

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
        if ($token !== '' && substr_count($token, '.') < 2) {
            $token = '';
        }
        if ($token === '') {
            $refreshed = $this->refresh_access_token();
            if ($refreshed !== '') {
                $token = $refreshed;
            }
        }
        if ($token === '') {
            $redeemed = $this->redeem_access_token_from_ap_token();
            if ($redeemed !== '') {
                $token = $redeemed;
            }
        }
        if ($token === '') {
            return $this->fail(__('Brak accessToken KSeF API. Uzupełnij accessToken JWT, refreshToken lub token KSeF z AP + NIP.', 'erp-omd'));
        }
        if (substr_count($token, '.') < 2) {
            return $this->fail(__('Podany token nie jest accessToken JWT. Token KSeF wymaga osobnego flow uwierzytelnienia (challenge + encryptedToken) w API KSeF 2.0.', 'erp-omd'));
        }

        $scope = in_array((string) ($params['scope'] ?? 'both'), ['cost', 'sales', 'both'], true)
            ? (string) ($params['scope'] ?? 'both')
            : 'both';
        $mode = in_array((string) ($params['mode'] ?? 'from_now'), ['from_now', 'backfill', 'all'], true)
            ? (string) ($params['mode'] ?? 'from_now')
            : 'from_now';

        $window = $this->build_window($mode, $params);
        $documents = $this->fetch_documents($token, $window['from'], $window['to']);
        if (is_wp_error($documents) && (int) $documents->get_error_data('http_code') === 401) {
            $refreshed = $this->refresh_access_token();
            if ($refreshed !== '') {
                $token = $refreshed;
                $documents = $this->fetch_documents($token, $window['from'], $window['to']);
            }
        }
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
        $api_base_url = trim((string) get_option(self::OPTION_API_BASE_URL, 'https://api.ksef.mf.gov.pl'));
        if ($api_base_url === '' || ! wp_http_validate_url($api_base_url)) {
            $api_base_url = 'https://api.ksef.mf.gov.pl';
        }
        $endpoint = rtrim($api_base_url, '/') . '/api/v2/invoices/query/metadata';
        $body = [
            'queryCriteria' => [
                'invoiceDateFrom' => str_replace(' ', 'T', $from),
                'invoiceDateTo' => str_replace(' ', 'T', $to),
            ],
            'pageOffset' => 0,
            'pageSize' => 100,
        ];
        $response = wp_remote_post($endpoint, [
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
            $message = $this->extract_http_error_message($payload);
            if ($message === '') {
                $body_preview = trim((string) wp_remote_retrieve_body($response));
                if ($body_preview !== '') {
                    $message = mb_substr($body_preview, 0, 300);
                }
            }
            if ($message === '') {
                $message = __('Błąd pobierania metadanych KSeF.', 'erp-omd');
            }
            return new WP_Error(
                'erp_omd_ksef_sync_http_error',
                sprintf(__('Błąd pobierania metadanych KSeF (HTTP %1$d): %2$s', 'erp-omd'), $code, $message),
                ['http_code' => $code]
            );
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

    private function extract_http_error_message(array $payload)
    {
        $candidates = [
            (string) ($payload['message'] ?? ''),
            (string) ($payload['detail'] ?? ''),
            (string) ($payload['error'] ?? ''),
            (string) ($payload['error_description'] ?? ''),
            (string) ($payload['title'] ?? ''),
            (string) ($payload['description'] ?? ''),
            (string) ($payload['errors'][0]['message'] ?? ''),
            (string) ($payload['violations'][0]['message'] ?? ''),
        ];
        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    private function refresh_access_token()
    {
        $refresh_token = trim((string) $this->decrypt_value((string) get_option(self::OPTION_REFRESH_TOKEN_ENC, '')));
        if ($refresh_token === '') {
            return '';
        }
        $api_base_url = trim((string) get_option(self::OPTION_API_BASE_URL, 'https://api.ksef.mf.gov.pl'));
        if ($api_base_url === '' || ! wp_http_validate_url($api_base_url)) {
            $api_base_url = 'https://api.ksef.mf.gov.pl';
        }
        $endpoint = rtrim($api_base_url, '/') . '/api/v2/auth/token/refresh';
        $response = wp_remote_post($endpoint, [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $refresh_token,
                'Content-Type' => 'application/json',
            ],
            'body' => '{}',
        ]);
        if (is_wp_error($response)) {
            return '';
        }
        if ((int) wp_remote_retrieve_response_code($response) >= 400) {
            return '';
        }
        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        $new_access_token = trim((string) ($payload['accessToken']['token'] ?? $payload['accessToken'] ?? ''));
        if ($new_access_token === '') {
            return '';
        }
        update_option(self::OPTION_TOKEN_ENC, $this->encrypt_value($new_access_token));
        return $new_access_token;
    }

    private function redeem_access_token_from_ap_token()
    {
        $ap_token = trim((string) $this->decrypt_value((string) get_option(self::OPTION_AP_TOKEN_ENC, '')));
        $public_key_pem = trim((string) get_option(self::OPTION_PUBLIC_KEY_PEM, ''));
        $company_nip = preg_replace('/[^0-9]/', '', (string) $this->company_nip);
        if ($ap_token === '' || $public_key_pem === '' || $company_nip === '') {
            return '';
        }
        $challenge_row = $this->request_challenge();
        if (! is_array($challenge_row)) {
            return '';
        }
        $challenge = (string) ($challenge_row['challenge'] ?? '');
        $timestamp = (string) ($challenge_row['timestamp'] ?? '');
        if ($challenge === '' || $timestamp === '') {
            return '';
        }
        $encrypted_token = $this->encrypt_ap_token($ap_token, $timestamp, $public_key_pem);
        if ($encrypted_token === '') {
            return '';
        }
        $authentication_token = $this->authenticate_with_ksef_token($challenge, $company_nip, $encrypted_token);
        if ($authentication_token === '') {
            return '';
        }
        $redeemed = $this->redeem_authentication_token($authentication_token);
        if (! is_array($redeemed)) {
            return '';
        }
        $new_access_token = trim((string) ($redeemed['accessToken'] ?? $redeemed['accessToken']['token'] ?? ''));
        $new_refresh_token = trim((string) ($redeemed['refreshToken'] ?? $redeemed['refreshToken']['token'] ?? ''));
        if ($new_access_token === '') {
            return '';
        }
        update_option(self::OPTION_TOKEN_ENC, $this->encrypt_value($new_access_token));
        if ($new_refresh_token !== '') {
            update_option(self::OPTION_REFRESH_TOKEN_ENC, $this->encrypt_value($new_refresh_token));
        }
        return $new_access_token;
    }

    private function request_challenge()
    {
        $endpoint = rtrim($this->api_base_url(), '/') . '/api/v2/auth/challenge';
        $response = wp_remote_post($endpoint, [
            'timeout' => 20,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '{}',
        ]);
        if (is_wp_error($response)) {
            return [];
        }
        if ((int) wp_remote_retrieve_response_code($response) >= 400) {
            return [];
        }
        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        return is_array($payload) ? $payload : [];
    }

    private function authenticate_with_ksef_token($challenge, $company_nip, $encrypted_token)
    {
        $endpoint = rtrim($this->api_base_url(), '/') . '/api/v2/auth/ksef-token';
        $payload = [
            'challenge' => (string) $challenge,
            'contextIdentifier' => ['type' => 'onip', 'value' => (string) $company_nip],
            'encryptedToken' => (string) $encrypted_token,
        ];
        $response = wp_remote_post($endpoint, [
            'timeout' => 20,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode($payload),
        ]);
        if (is_wp_error($response)) {
            return '';
        }
        if ((int) wp_remote_retrieve_response_code($response) >= 400) {
            return '';
        }
        $row = json_decode((string) wp_remote_retrieve_body($response), true);
        return trim((string) ($row['authenticationToken']['token'] ?? $row['authenticationToken'] ?? ''));
    }

    private function redeem_authentication_token($authentication_token)
    {
        $endpoint = rtrim($this->api_base_url(), '/') . '/api/v2/auth/token/redeem';
        $response = wp_remote_post($endpoint, [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . (string) $authentication_token,
                'Content-Type' => 'application/json',
            ],
            'body' => '{}',
        ]);
        if (is_wp_error($response)) {
            return [];
        }
        if ((int) wp_remote_retrieve_response_code($response) >= 400) {
            return [];
        }
        $row = json_decode((string) wp_remote_retrieve_body($response), true);
        return is_array($row) ? $row : [];
    }

    private function encrypt_ap_token($ap_token, $timestamp, $public_key_pem)
    {
        $plain = (string) $ap_token . '|' . (string) $timestamp;
        $public_key = openssl_pkey_get_public((string) $public_key_pem);
        if ($public_key === false) {
            return '';
        }
        $encrypted = '';
        $ok = openssl_public_encrypt($plain, $encrypted, $public_key, OPENSSL_PKCS1_OAEP_PADDING);
        if (is_resource($public_key)) {
            openssl_free_key($public_key);
        }
        if (! $ok || $encrypted === '') {
            return '';
        }
        return base64_encode($encrypted);
    }

    private function api_base_url()
    {
        $api_base_url = trim((string) get_option(self::OPTION_API_BASE_URL, 'https://api.ksef.mf.gov.pl'));
        if ($api_base_url === '' || ! wp_http_validate_url($api_base_url)) {
            $api_base_url = 'https://api.ksef.mf.gov.pl';
        }
        return $api_base_url;
    }

    private function encrypt_value($value)
    {
        $key = hash('sha256', (string) wp_salt('auth'), true);
        $iv = substr(hash('sha256', (string) wp_salt('secure_auth')), 0, 16);
        $cipher = openssl_encrypt((string) $value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return $cipher !== false ? base64_encode($cipher) : '';
    }
}
