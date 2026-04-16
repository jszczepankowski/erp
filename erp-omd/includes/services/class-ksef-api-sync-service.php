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
    private $auth_diagnostic = '';

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
        $this->auth_diagnostic = '';
        $stored_token = trim((string) $this->decrypt_value((string) get_option(self::OPTION_TOKEN_ENC, '')));
        $token = '';
        $ap_token_fallback = '';
        if ($stored_token !== '') {
            if (substr_count($stored_token, '.') >= 2) {
                $token = $stored_token;
            } else {
                $ap_token_fallback = $stored_token;
            }
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
        if ($token === '' && $ap_token_fallback !== '') {
            $redeemed = $this->redeem_access_token_from_ap_token_value($ap_token_fallback);
            if ($redeemed !== '') {
                $token = $redeemed;
            }
        }
        if ($token === '') {
            $message = __('Brak accessToken KSeF API. Uzupełnij accessToken JWT, refreshToken lub token KSeF z AP + NIP.', 'erp-omd');
            if ($ap_token_fallback !== '') {
                $message .= ' ' . __('Podany token nie jest accessToken JWT. Token KSeF wymaga osobnego flow uwierzytelnienia (challenge + encryptedToken) w API KSeF 2.0.', 'erp-omd');
            }
            if ($this->auth_diagnostic !== '') {
                $message .= ' ' . sprintf(__('Szczegóły AP flow: %s', 'erp-omd'), $this->auth_diagnostic);
            }
            return $this->fail($message);
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
        if (is_wp_error($documents) && (int) $documents->get_error_data('http_code') === 401) {
            $redeemed = $this->redeem_access_token_from_ap_token();
            if ($redeemed !== '') {
                $token = $redeemed;
                $documents = $this->fetch_documents($token, $window['from'], $window['to']);
            }
        }
        if (is_wp_error($documents) && (int) $documents->get_error_data('http_code') === 401 && $ap_token_fallback !== '') {
            $redeemed = $this->redeem_access_token_from_ap_token_value($ap_token_fallback);
            if ($redeemed !== '') {
                $token = $redeemed;
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

    public function fetch_and_store_token_encryption_public_key()
    {
        $endpoint = rtrim($this->api_base_url(), '/') . '/security/public-key-certificates';
        $response = wp_remote_get($endpoint, [
            'timeout' => 20,
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        if (is_wp_error($response)) {
            return [
                'ok' => false,
                'message' => sprintf(__('Nie udało się pobrać kluczy publicznych KSeF: %s', 'erp-omd'), $response->get_error_message()),
            ];
        }
        $response_code = (int) wp_remote_retrieve_response_code($response);
        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($response_code >= 400) {
            return [
                'ok' => false,
                'message' => $this->http_response_diagnostic($payload, $response_code, __('Błąd pobierania certyfikatów klucza publicznego KSeF.', 'erp-omd')),
            ];
        }

        $items = $payload['certificates'] ?? $payload['items'] ?? $payload;
        if (! is_array($items)) {
            return ['ok' => false, 'message' => __('KSeF nie zwrócił listy certyfikatów klucza publicznego.', 'erp-omd')];
        }
        $selected = $this->find_token_encryption_certificate($items);
        if (! is_array($selected)) {
            return ['ok' => false, 'message' => __('Nie znaleziono certyfikatu KSeF z usage=KsefTokenEncryption.', 'erp-omd')];
        }

        $raw_certificate = trim((string) ($selected['certificate'] ?? $selected['cert'] ?? $selected['publicKey'] ?? $selected['publicKeyCertificate'] ?? ''));
        if ($raw_certificate === '') {
            return ['ok' => false, 'message' => __('Wybrany rekord certyfikatu KSeF nie zawiera pola certificate/publicKey.', 'erp-omd')];
        }
        $pem = $this->normalize_certificate_to_pem($raw_certificate);
        if ($pem === '') {
            return ['ok' => false, 'message' => __('Nie udało się znormalizować certyfikatu KSeF do formatu PEM.', 'erp-omd')];
        }

        $public_key = $this->resolve_public_key($pem);
        if ($public_key === false) {
            return ['ok' => false, 'message' => __('Pobrany certyfikat KSeF nie zawiera prawidłowego klucza publicznego.', 'erp-omd')];
        }
        $details = openssl_pkey_get_details($public_key);
        $this->free_openssl_key($public_key);
        if ((int) ($details['type'] ?? -1) !== OPENSSL_KEYTYPE_RSA) {
            return ['ok' => false, 'message' => __('Pobrany certyfikat KSeF nie zawiera klucza RSA wymaganego dla encryptedToken.', 'erp-omd')];
        }

        update_option(self::OPTION_PUBLIC_KEY_PEM, $pem);

        return [
            'ok' => true,
            'message' => __('Pobrano i zapisano klucz publiczny KSeF (MF) dla usage=KsefTokenEncryption.', 'erp-omd'),
        ];
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
        $endpoint = rtrim($this->api_base_url(), '/') . '/invoices/query/metadata';
        $page_size = 100;
        $page_offset = 0;
        $continuation_token = '';
        $all_items = [];
        $max_pages = 200;

        for ($page = 0; $page < $max_pages; $page++) {
            $body = [
                'queryCriteria' => [
                    'invoiceDateFrom' => str_replace(' ', 'T', $from),
                    'invoiceDateTo' => str_replace(' ', 'T', $to),
                ],
                'pageOffset' => $page_offset,
                'pageSize' => $page_size,
            ];

            $headers = [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ];
            if ($continuation_token !== '') {
                $headers['x-continuation-token'] = $continuation_token;
            }

            $response = wp_remote_post($endpoint, [
                'timeout' => 25,
                'headers' => $headers,
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
            if (! is_array($items)) {
                $items = [];
            }
            foreach ($items as $item) {
                $all_items[] = $item;
            }

            $next_token = trim((string) wp_remote_retrieve_header($response, 'x-continuation-token'));
            if ($next_token === '') {
                if (count($items) < $page_size) {
                    break;
                }
                $page_offset += $page_size;
                continue;
            }

            if ($next_token === $continuation_token) {
                break;
            }
            $continuation_token = $next_token;
        }

        return $all_items;
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
        $endpoint = rtrim($this->api_base_url(), '/') . '/auth/token/refresh';
        $response = wp_remote_post($endpoint, [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $refresh_token,
                'Content-Type' => 'application/json',
            ],
            'body' => '{}',
        ]);
        if (is_wp_error($response)) {
            $this->auth_diagnostic = $this->http_response_diagnostic($response, 0, __('Błąd odświeżania refreshToken.', 'erp-omd'));
            return '';
        }
        $response_code = (int) wp_remote_retrieve_response_code($response);
        if ($response_code >= 400) {
            $payload = json_decode((string) wp_remote_retrieve_body($response), true);
            $this->auth_diagnostic = $this->http_response_diagnostic($payload, $response_code, __('Błąd odświeżania refreshToken.', 'erp-omd'));
            return '';
        }
        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        $new_access_token = trim((string) ($payload['accessToken']['token'] ?? $payload['accessToken'] ?? ''));
        if ($new_access_token === '') {
            $this->auth_diagnostic = __('Odpowiedź refreshToken nie zawiera accessToken.', 'erp-omd');
            return '';
        }
        update_option(self::OPTION_TOKEN_ENC, $this->encrypt_value($new_access_token));
        return $new_access_token;
    }

    private function redeem_access_token_from_ap_token()
    {
        $ap_token = trim((string) $this->decrypt_value((string) get_option(self::OPTION_AP_TOKEN_ENC, '')));
        return $this->redeem_access_token_from_ap_token_value($ap_token);
    }

    private function redeem_access_token_from_ap_token_value($ap_token)
    {
        $ap_token = trim((string) $ap_token);
        $public_key_pem = trim((string) get_option(self::OPTION_PUBLIC_KEY_PEM, ''));
        if ($public_key_pem === '') {
            $cert_result = $this->fetch_and_store_token_encryption_public_key();
            if (! (bool) ($cert_result['ok'] ?? false)) {
                $this->auth_diagnostic = (string) ($cert_result['message'] ?? __('Brak klucza publicznego KSeF.', 'erp-omd'));
                return '';
            }
            $public_key_pem = trim((string) get_option(self::OPTION_PUBLIC_KEY_PEM, ''));
        }
        $company_nip = preg_replace('/[^0-9]/', '', (string) $this->company_nip);
        if ($ap_token === '' || $public_key_pem === '' || $company_nip === '') {
            $this->auth_diagnostic = __('Brak wymaganych danych do AP flow (token AP, PEM lub NIP).', 'erp-omd');
            return '';
        }
        $challenge_row = $this->request_challenge();
        if (! is_array($challenge_row)) {
            return '';
        }
        $challenge = (string) ($challenge_row['challenge'] ?? '');
        $timestamp = $this->normalize_challenge_timestamp_millis($challenge_row['timestamp'] ?? '');
        if ($challenge === '' || $timestamp === '') {
            $this->auth_diagnostic = __('Challenge KSeF nie zawiera challenge/timestamp.', 'erp-omd');
            return '';
        }
        $encrypted_token = $this->encrypt_ap_token($ap_token, $timestamp, $public_key_pem);
        if ($encrypted_token === '') {
            $this->auth_diagnostic = __('Nie udało się zaszyfrować tokenu AP (sprawdź PEM).', 'erp-omd');
            return '';
        }
        $auth_payload = $this->authenticate_with_ksef_token($challenge, $company_nip, $encrypted_token);
        $authentication_token = trim((string) ($auth_payload['authentication_token'] ?? ''));
        $reference_number = trim((string) ($auth_payload['reference_number'] ?? ''));
        if ($authentication_token === '' || $reference_number === '') {
            if ($this->auth_diagnostic === '') {
                $this->auth_diagnostic = __('Odpowiedź /auth/ksef-token nie zawiera authenticationToken/referenceNumber.', 'erp-omd');
            }
            return '';
        }
        if (! $this->wait_for_authentication_ready($reference_number, $authentication_token)) {
            return '';
        }
        $redeemed = $this->redeem_authentication_token($authentication_token);
        if (! is_array($redeemed)) {
            return '';
        }
        $new_access_token = trim((string) ($redeemed['accessToken'] ?? $redeemed['accessToken']['token'] ?? ''));
        $new_refresh_token = trim((string) ($redeemed['refreshToken'] ?? $redeemed['refreshToken']['token'] ?? ''));
        if ($new_access_token === '') {
            $this->auth_diagnostic = __('Odpowiedź /auth/token/redeem nie zawiera accessToken.', 'erp-omd');
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
        $endpoint = rtrim($this->api_base_url(), '/') . '/auth/challenge';
        $response = wp_remote_post($endpoint, [
            'timeout' => 20,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '{}',
        ]);
        if (is_wp_error($response)) {
            $this->auth_diagnostic = $this->http_response_diagnostic($response, 0, __('Błąd /auth/challenge.', 'erp-omd'));
            return [];
        }
        $response_code = (int) wp_remote_retrieve_response_code($response);
        if ($response_code >= 400) {
            $payload = json_decode((string) wp_remote_retrieve_body($response), true);
            $this->auth_diagnostic = $this->http_response_diagnostic($payload, $response_code, __('Błąd /auth/challenge.', 'erp-omd'));
            return [];
        }
        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        return is_array($payload) ? $payload : [];
    }

    private function authenticate_with_ksef_token($challenge, $company_nip, $encrypted_token)
    {
        $endpoint = rtrim($this->api_base_url(), '/') . '/auth/ksef-token';
        $context_types = ['Nip', 'nip', 'onip'];
        $last_diagnostic = '';

        foreach ($context_types as $context_type) {
            $payload = [
                'challenge' => (string) $challenge,
                'contextIdentifier' => ['type' => (string) $context_type, 'value' => (string) $company_nip],
                'encryptedToken' => (string) $encrypted_token,
            ];
            $response = wp_remote_post($endpoint, [
                'timeout' => 20,
                'headers' => ['Content-Type' => 'application/json'],
                'body' => wp_json_encode($payload),
            ]);
            if (is_wp_error($response)) {
                $last_diagnostic = $this->http_response_diagnostic($response, 0, __('Błąd /auth/ksef-token.', 'erp-omd'));
                continue;
            }
            $response_code = (int) wp_remote_retrieve_response_code($response);
            if ($response_code >= 400) {
                $payload = json_decode((string) wp_remote_retrieve_body($response), true);
                $last_diagnostic = $this->http_response_diagnostic(
                    $payload,
                    $response_code,
                    sprintf(__('Błąd /auth/ksef-token (contextIdentifier.type=%s).', 'erp-omd'), $context_type)
                );
                continue;
            }
            $row = json_decode((string) wp_remote_retrieve_body($response), true);
            if (! is_array($row)) {
                continue;
            }

            return [
                'authentication_token' => trim((string) ($row['authenticationToken']['token'] ?? $row['authenticationToken'] ?? '')),
                'reference_number' => trim((string) ($row['referenceNumber'] ?? '')),
            ];
        }

        $this->auth_diagnostic = $last_diagnostic !== '' ? $last_diagnostic : __('Błąd /auth/ksef-token.', 'erp-omd');

        return [];
    }

    private function redeem_authentication_token($authentication_token)
    {
        $endpoint = rtrim($this->api_base_url(), '/') . '/auth/token/redeem';
        $response = wp_remote_post($endpoint, [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . (string) $authentication_token,
                'Content-Type' => 'application/json',
            ],
            'body' => '{}',
        ]);
        if (is_wp_error($response)) {
            $this->auth_diagnostic = $this->http_response_diagnostic($response, 0, __('Błąd /auth/token/redeem.', 'erp-omd'));
            return [];
        }
        $response_code = (int) wp_remote_retrieve_response_code($response);
        if ($response_code >= 400) {
            $payload = json_decode((string) wp_remote_retrieve_body($response), true);
            $this->auth_diagnostic = $this->http_response_diagnostic($payload, $response_code, __('Błąd /auth/token/redeem.', 'erp-omd'));
            return [];
        }
        $row = json_decode((string) wp_remote_retrieve_body($response), true);
        return is_array($row) ? $row : [];
    }

    private function encrypt_ap_token($ap_token, $timestamp, $public_key_pem)
    {
        $plain = (string) $ap_token . '|' . (string) $timestamp;
        $public_key = $this->resolve_public_key($public_key_pem);
        if ($public_key === false) {
            $this->auth_diagnostic = __('Nieprawidłowy PEM — nie udało się odczytać klucza publicznego/certyfikatu.', 'erp-omd');
            return '';
        }
        $key_details = openssl_pkey_get_details($public_key);
        $public_key_export = is_array($key_details) ? (string) ($key_details['key'] ?? '') : '';

        if ($public_key_export !== '') {
            $encrypted_cli = $this->encrypt_with_openssl_cli_oaep_sha256($plain, $public_key_export);
            if ($encrypted_cli !== '') {
                $this->free_openssl_key($public_key);
                return $encrypted_cli;
            }
        }

        $encrypted = '';
        $ok = openssl_public_encrypt($plain, $encrypted, $public_key, OPENSSL_PKCS1_OAEP_PADDING);
        $this->free_openssl_key($public_key);
        if (! $ok || $encrypted === '') {
            $this->auth_diagnostic = __('Nie udało się zaszyfrować tokenu AP. Upewnij się, że środowisko wspiera RSA OAEP SHA-256.', 'erp-omd');
            return '';
        }
        return base64_encode($encrypted);
    }

    private function encrypt_with_openssl_cli_oaep_sha256($plain, $public_key_pem)
    {
        if (! function_exists('proc_open')) {
            return '';
        }

        $tmp_dir = sys_get_temp_dir();
        $key_file = tempnam($tmp_dir, 'erp_omd_ksef_key_');
        $in_file = tempnam($tmp_dir, 'erp_omd_ksef_in_');
        if ($key_file === false || $in_file === false) {
            return '';
        }

        file_put_contents($key_file, (string) $public_key_pem);
        file_put_contents($in_file, (string) $plain);

        $cmd = [
            'openssl',
            'pkeyutl',
            '-encrypt',
            '-pubin',
            '-inkey',
            $key_file,
            '-in',
            $in_file,
            '-pkeyopt',
            'rsa_padding_mode:oaep',
            '-pkeyopt',
            'rsa_oaep_md:sha256',
            '-pkeyopt',
            'rsa_mgf1_md:sha256',
        ];

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($cmd, $descriptors, $pipes);
        if (! is_resource($process)) {
            @unlink($key_file);
            @unlink($in_file);
            return '';
        }

        fclose($pipes[0]);
        $encrypted = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit_code = proc_close($process);

        @unlink($key_file);
        @unlink($in_file);

        if ($exit_code !== 0 || $encrypted === false || $encrypted === '') {
            $stderr = trim((string) $stderr);
            if ($stderr !== '') {
                $this->auth_diagnostic = sprintf(__('Błąd szyfrowania OpenSSL (OAEP SHA-256): %s', 'erp-omd'), $stderr);
            }
            return '';
        }

        return base64_encode($encrypted);
    }

    private function wait_for_authentication_ready($reference_number, $authentication_token)
    {
        $reference_number = trim((string) $reference_number);
        $authentication_token = trim((string) $authentication_token);
        if ($reference_number === '' || $authentication_token === '') {
            return false;
        }
        $endpoint = rtrim($this->api_base_url(), '/') . '/auth/' . rawurlencode($reference_number);
        $max_checks = 20;
        for ($attempt = 0; $attempt < $max_checks; $attempt++) {
            $response = wp_remote_get($endpoint, [
                'timeout' => 20,
                'headers' => [
                    'Authorization' => 'Bearer ' . $authentication_token,
                    'Content-Type' => 'application/json',
                ],
            ]);
            if (is_wp_error($response)) {
                $this->auth_diagnostic = $this->http_response_diagnostic($response, 0, __('Błąd /auth/{referenceNumber}.', 'erp-omd'));
                return false;
            }
            $response_code = (int) wp_remote_retrieve_response_code($response);
            if ($response_code >= 400) {
                $payload = json_decode((string) wp_remote_retrieve_body($response), true);
                $this->auth_diagnostic = $this->http_response_diagnostic($payload, $response_code, __('Błąd /auth/{referenceNumber}.', 'erp-omd'));
                return false;
            }
            $row = json_decode((string) wp_remote_retrieve_body($response), true);
            $status_code = (int) ($row['status']['code'] ?? 0);
            if ($status_code >= 200 && $status_code < 400 && $status_code !== 100) {
                return true;
            }
            if ($status_code >= 400) {
                $message = trim((string) ($row['status']['description'] ?? ''));
                $details = isset($row['status']['details']) && is_array($row['status']['details']) ? implode(', ', array_map('strval', $row['status']['details'])) : '';
                $diagnostic = trim(sprintf(__('Status autoryzacji %1$d: %2$s %3$s', 'erp-omd'), $status_code, $message, $details));
                if ($status_code === 450 && stripos($diagnostic, 'Invalid timestamp') !== false) {
                    $diagnostic .= ' ' . __('Sprawdź czas serwera (UTC), poprawność challenge/timestamp oraz czy token AP i klucz publiczny PEM są z tego samego środowiska KSeF (prod/test).', 'erp-omd');
                }
                $this->auth_diagnostic = $diagnostic;
                return false;
            }
            sleep(1);
        }

        $this->auth_diagnostic = __('Timeout oczekiwania na zakończenie autoryzacji KSeF.', 'erp-omd');
        return false;
    }

    private function find_token_encryption_certificate(array $items)
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $usages = $item['usage'] ?? $item['usages'] ?? [];
            if (is_string($usages)) {
                $usages = [$usages];
            }
            if (! is_array($usages)) {
                continue;
            }
            foreach ($usages as $usage) {
                if (strcasecmp(trim((string) $usage), 'KsefTokenEncryption') === 0) {
                    return $item;
                }
            }
        }

        return null;
    }

    private function normalize_certificate_to_pem($certificate)
    {
        $certificate = trim((string) $certificate);
        if ($certificate === '') {
            return '';
        }
        $certificate = str_replace(["\r\n", "\r"], "\n", $certificate);
        $certificate = str_replace('\\n', "\n", $certificate);
        if (strpos($certificate, 'BEGIN') !== false) {
            return trim($certificate);
        }
        $base64 = preg_replace('/\s+/', '', $certificate);
        if ($base64 === '') {
            return '';
        }

        return "-----BEGIN CERTIFICATE-----\n" . chunk_split($base64, 64, "\n") . "-----END CERTIFICATE-----";
    }

    private function resolve_public_key($public_key_pem)
    {
        $pem = trim((string) $public_key_pem);
        if ($pem === '') {
            return false;
        }
        $pem = str_replace(["\r\n", "\r"], "\n", $pem);
        $pem = str_replace('\\n', "\n", $pem);
        $pem = trim($pem, "\"' \n\t");

        $public_key = openssl_pkey_get_public($pem);
        if ($public_key !== false) {
            return $public_key;
        }

        $x509 = openssl_x509_read($pem);
        if ($x509 !== false) {
            $public_from_cert = openssl_pkey_get_public($x509);
            if (is_resource($x509)) {
                openssl_x509_free($x509);
            }
            if ($public_from_cert !== false) {
                return $public_from_cert;
            }
        }

        if (strpos($pem, 'BEGIN') === false && preg_match('/^[A-Za-z0-9+\/=\s]+$/', $pem)) {
            $wrapped = "-----BEGIN CERTIFICATE-----\n" . chunk_split(preg_replace('/\s+/', '', $pem), 64, "\n") . "-----END CERTIFICATE-----";
            $x509 = openssl_x509_read($wrapped);
            if ($x509 !== false) {
                $public_from_cert = openssl_pkey_get_public($x509);
                if (is_resource($x509)) {
                    openssl_x509_free($x509);
                }
                if ($public_from_cert !== false) {
                    return $public_from_cert;
                }
            }
        }

        return false;
    }

    private function free_openssl_key($key)
    {
        if (is_resource($key)) {
            openssl_free_key($key);
            return;
        }
        if (is_object($key) && get_class($key) === 'OpenSSLAsymmetricKey' && function_exists('openssl_pkey_free')) {
            openssl_pkey_free($key);
        }
    }

    private function http_response_diagnostic($response_or_payload, $http_code, $fallback)
    {
        if (is_wp_error($response_or_payload)) {
            return trim((string) $response_or_payload->get_error_message());
        }
        $payload = is_array($response_or_payload) ? $response_or_payload : [];
        $message = $this->extract_http_error_message($payload);
        if ($message === '') {
            $message = (string) $fallback;
        }

        return sprintf('HTTP %1$d: %2$s', (int) $http_code, trim((string) $message));
    }

    private function normalize_challenge_timestamp_millis($timestamp)
    {
        $timestamp_string = trim((string) $timestamp);
        if ($timestamp_string === '') {
            return '';
        }

        if (preg_match('/\/Date\((\d+)\)\//', $timestamp_string, $date_match) === 1) {
            return (string) ((int) $date_match[1]);
        }

        if (is_int($timestamp) || is_float($timestamp) || (is_string($timestamp) && is_numeric($timestamp))) {
            $digits = preg_replace('/[^0-9]/', '', $timestamp_string);
            if ($digits === '') {
                return '';
            }
            if (strlen($digits) >= 13) {
                return (string) ((int) substr($digits, 0, 13));
            }

            return (string) (((int) $digits) * 1000);
        }

        if (preg_match('/\.(\d+)(?:Z|[+\-]\d{2}:\d{2})?$/', $timestamp_string, $millis_match) === 1) {
            $fractional = substr(str_pad((string) $millis_match[1], 3, '0'), 0, 3);
            $timestamp_without_fraction = preg_replace('/\.(\d+)(Z|[+\-]\d{2}:\d{2})?$/', '$2', $timestamp_string);
            $parsed = strtotime((string) $timestamp_without_fraction);
            if ($parsed === false || $parsed <= 0) {
                return '';
            }

            return (string) ((($parsed * 1000) + (int) $fractional));
        }

        $parsed = strtotime($timestamp_string);
        if ($parsed === false || $parsed <= 0) {
            return '';
        }

        return (string) ($parsed * 1000);
    }

    private function api_base_url()
    {
        $api_base_url = trim((string) get_option(self::OPTION_API_BASE_URL, 'https://api.ksef.mf.gov.pl/v2'));
        if ($api_base_url === '' || ! wp_http_validate_url($api_base_url)) {
            $api_base_url = 'https://api.ksef.mf.gov.pl/v2';
        }
        $api_base_url = rtrim($api_base_url, '/');
        if (substr($api_base_url, -7) === '/api/v2') {
            return $api_base_url;
        }
        if (substr($api_base_url, -3) === '/v2') {
            return $api_base_url;
        }

        return $api_base_url . '/v2';
    }

    private function encrypt_value($value)
    {
        $key = hash('sha256', (string) wp_salt('auth'), true);
        $iv = substr(hash('sha256', (string) wp_salt('secure_auth')), 0, 16);
        $cipher = openssl_encrypt((string) $value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return $cipher !== false ? base64_encode($cipher) : '';
    }
}
