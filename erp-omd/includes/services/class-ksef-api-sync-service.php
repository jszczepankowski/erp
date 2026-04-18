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
    const OPTION_ENVIRONMENT = 'erp_omd_ksef_environment';

    /** @var ERP_OMD_KSeF_Import_Service */
    private $import_service;

    /** @var string */
    private $company_nip;

    /** @var string */
    private $last_diagnostic = '';

    /** @var ERP_OMD_KSeF_Connector */
    private $connector;

    public function __construct(ERP_OMD_KSeF_Import_Service $import_service, $company_nip = '', ERP_OMD_KSeF_Connector $connector = null)
    {
        $this->import_service = $import_service;
        $this->company_nip = preg_replace('/[^0-9]/', '', (string) $company_nip);
        $this->connector = $connector ?: new ERP_OMD_KSeF_Connector($this->api_base_url());
    }

    public function run_scheduled_sync()
    {
        if (! (bool) get_option(self::OPTION_ENABLED, false)) {
            return ['ok' => true, 'skipped' => true, 'reason' => 'disabled'];
        }

        return $this->sync([
            'scope' => 'both',
            'mode' => (string) get_option(self::OPTION_MODE, 'from_now'),
        ]);
    }

    public function run_connector_check(array $params = [])
    {
        $token = $this->resolve_access_token();
        if ($token === '') {
            return ['ok' => false, 'last_error' => $this->last_diagnostic !== '' ? $this->last_diagnostic : __('Brak tokenu do testu połączenia KSeF.', 'erp-omd')];
        }

        $minutes = max(5, min(1440, (int) ($params['lookback_minutes'] ?? 120)));
        $to = current_time('mysql');
        $from = gmdate('Y-m-d H:i:s', strtotime($to . ' -' . $minutes . ' minutes'));

        $metadata = $this->query_invoice_metadata($token, $from, $to, 0, 10);
        if (is_wp_error($metadata)) {
            return ['ok' => false, 'last_error' => $metadata->get_error_message()];
        }

        return [
            'ok' => true,
            'environment' => sanitize_key((string) get_option(self::OPTION_ENVIRONMENT, 'prod')),
            'base_url' => $this->api_base_url(),
            'from' => $from,
            'to' => $to,
            'fetched' => count((array) ($metadata['items'] ?? [])),
        ];
    }

    public function sync(array $params = [])
    {
        $token = $this->resolve_access_token();
        if ($token === '') {
            return $this->fail(__('Brak accessToken KSeF API. Uzupełnij accessToken JWT, refreshToken lub token KSeF z AP + NIP.', 'erp-omd'));
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

    public function fetch_and_store_token_encryption_public_key()
    {
        $response = $this->connector->request('GET', '/security/public-key-certificates', [
            'Content-Type' => 'application/json',
        ], null, 20);

        if (is_wp_error($response)) {
            return ['ok' => false, 'message' => $response->get_error_message()];
        }
        $code = (int) ($response['code'] ?? 0);
        $payload = is_array($response['json'] ?? null) ? (array) $response['json'] : [];
        if ($code >= 400) {
            return ['ok' => false, 'message' => $this->http_response_diagnostic($payload, $code, __('Błąd pobierania certyfikatów klucza publicznego KSeF.', 'erp-omd'))];
        }

        $items = $this->extract_certificate_items($payload);
        if ($items === []) {
            return ['ok' => false, 'message' => __('KSeF nie zwrócił listy certyfikatów klucza publicznego.', 'erp-omd')];
        }

        $selected = $this->find_token_encryption_certificate($items);
        if (! is_array($selected)) {
            return ['ok' => false, 'message' => __('Nie znaleziono certyfikatu KSeF z usage=KsefTokenEncryption.', 'erp-omd')];
        }

        $raw_certificate = trim((string) ($selected['certificate'] ?? $selected['cert'] ?? $selected['publicKey'] ?? $selected['publicKeyCertificate'] ?? ''));
        $pem = $this->normalize_certificate_to_pem($raw_certificate);
        if ($pem === '') {
            return ['ok' => false, 'message' => __('Nie udało się znormalizować certyfikatu KSeF do formatu PEM.', 'erp-omd')];
        }
        update_option(self::OPTION_PUBLIC_KEY_PEM, $pem);

        return ['ok' => true, 'message' => __('Pobrano i zapisano klucz publiczny KSeF (MF) dla usage=KsefTokenEncryption.', 'erp-omd')];
    }

    private function resolve_access_token()
    {
        $this->last_diagnostic = '';
        $access_token = trim((string) $this->decrypt_value((string) get_option(self::OPTION_TOKEN_ENC, '')));
        if ($access_token !== '' && substr_count($access_token, '.') >= 2) {
            return $access_token;
        }

        $refreshed = $this->refresh_access_token();
        if ($refreshed !== '') {
            return $refreshed;
        }

        $redeemed = $this->redeem_access_token_from_ap_token();
        if ($redeemed !== '') {
            return $redeemed;
        }

        return '';
    }

    private function refresh_access_token()
    {
        $refresh_token = trim((string) $this->decrypt_value((string) get_option(self::OPTION_REFRESH_TOKEN_ENC, '')));
        if ($refresh_token === '') {
            return '';
        }
        $response = $this->connector->request('POST', '/auth/token/refresh', [
            'Authorization' => 'Bearer ' . $refresh_token,
            'Content-Type' => 'application/json',
        ], []);
        if (is_wp_error($response)) {
            $this->last_diagnostic = $response->get_error_message();
            return '';
        }

        $payload = is_array($response['json'] ?? null) ? (array) $response['json'] : [];
        $token = trim((string) ($payload['accessToken']['token'] ?? $payload['accessToken'] ?? ''));
        if ($token === '') {
            $this->last_diagnostic = __('Odpowiedź refresh token nie zawiera accessToken.', 'erp-omd');
            return '';
        }

        update_option(self::OPTION_TOKEN_ENC, $this->encrypt_value($token));

        return $token;
    }

    private function redeem_access_token_from_ap_token()
    {
        $ap_token = trim((string) $this->decrypt_value((string) get_option(self::OPTION_AP_TOKEN_ENC, '')));
        $public_key_pem = trim((string) get_option(self::OPTION_PUBLIC_KEY_PEM, ''));
        $company_nip = preg_replace('/[^0-9]/', '', (string) $this->company_nip);
        if ($ap_token === '' || $public_key_pem === '' || $company_nip === '') {
            return '';
        }

        $challenge_response = $this->connector->request('POST', '/auth/challenge', [
            'Content-Type' => 'application/json',
        ], []);
        if (is_wp_error($challenge_response)) {
            $this->last_diagnostic = $challenge_response->get_error_message();
            return '';
        }

        $challenge_payload = is_array($challenge_response['json'] ?? null) ? (array) $challenge_response['json'] : [];
        $challenge = trim((string) ($challenge_payload['challenge'] ?? ''));
        $timestamp = $this->normalize_challenge_timestamp_millis($challenge_payload['timestamp'] ?? '');
        if ($challenge === '' || $timestamp === '') {
            $this->last_diagnostic = __('Challenge KSeF nie zawiera challenge/timestamp.', 'erp-omd');
            return '';
        }

        $encrypted_token = $this->encrypt_ap_token($ap_token, $timestamp, $public_key_pem);
        if ($encrypted_token === '') {
            return '';
        }

        $auth_response = $this->connector->request('POST', '/auth/ksef-token', [
            'Content-Type' => 'application/json',
        ], [
            'challenge' => $challenge,
            'contextIdentifier' => ['type' => 'Nip', 'value' => $company_nip],
            'encryptedToken' => $encrypted_token,
        ]);
        if (is_wp_error($auth_response)) {
            $this->last_diagnostic = $auth_response->get_error_message();
            return '';
        }

        $auth_payload = is_array($auth_response['json'] ?? null) ? (array) $auth_response['json'] : [];
        $authentication_token = trim((string) ($auth_payload['authenticationToken']['token'] ?? $auth_payload['authenticationToken'] ?? ''));
        $reference_number = trim((string) ($auth_payload['referenceNumber'] ?? ''));
        if ($authentication_token === '' || $reference_number === '') {
            $this->last_diagnostic = __('Odpowiedź /auth/ksef-token nie zawiera authenticationToken/referenceNumber.', 'erp-omd');
            return '';
        }

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $status_response = $this->connector->request('GET', '/auth/' . rawurlencode($reference_number), [
                'Authorization' => 'Bearer ' . $authentication_token,
                'Content-Type' => 'application/json',
            ], null, 20);
            if (is_wp_error($status_response)) {
                $this->last_diagnostic = $status_response->get_error_message();
                return '';
            }
            $status_payload = is_array($status_response['json'] ?? null) ? (array) $status_response['json'] : [];
            $status_code = (int) ($status_payload['status']['code'] ?? 0);
            if ($status_code >= 200 && $status_code < 400 && $status_code !== 100) {
                break;
            }
            if ($status_code >= 400) {
                $this->last_diagnostic = trim((string) ($status_payload['status']['description'] ?? __('Błąd statusu autoryzacji.', 'erp-omd')));
                return '';
            }
            sleep(1);
        }

        $redeem_response = $this->connector->request('POST', '/auth/token/redeem', [
            'Authorization' => 'Bearer ' . $authentication_token,
            'Content-Type' => 'application/json',
        ], []);
        if (is_wp_error($redeem_response)) {
            $this->last_diagnostic = $redeem_response->get_error_message();
            return '';
        }

        $redeem_payload = is_array($redeem_response['json'] ?? null) ? (array) $redeem_response['json'] : [];
        $access_token = trim((string) ($redeem_payload['accessToken']['token'] ?? $redeem_payload['accessToken'] ?? ''));
        $refresh_token = trim((string) ($redeem_payload['refreshToken']['token'] ?? $redeem_payload['refreshToken'] ?? ''));
        if ($access_token === '') {
            return '';
        }

        update_option(self::OPTION_TOKEN_ENC, $this->encrypt_value($access_token));
        if ($refresh_token !== '') {
            update_option(self::OPTION_REFRESH_TOKEN_ENC, $this->encrypt_value($refresh_token));
        }

        return $access_token;
    }

    private function fetch_documents($token, $from, $to)
    {
        $all_items = [];
        $page_offset = 0;
        $page_size = 100;

        for ($page = 0; $page < 20; $page++) {
            $metadata = $this->query_invoice_metadata($token, $from, $to, $page_offset, $page_size);
            if (is_wp_error($metadata)) {
                return $metadata;
            }
            $items = (array) ($metadata['items'] ?? []);
            if ($items === []) {
                break;
            }
            foreach ($items as $item) {
                $all_items[] = $this->enrich_item_with_xml((array) $item, $token);
            }
            if (count($items) < $page_size) {
                break;
            }
            $page_offset += $page_size;
        }

        return $all_items;
    }

    private function query_invoice_metadata($token, $from, $to, $page_offset, $page_size)
    {
        $response = $this->connector->request('POST', '/invoices/query/metadata', [
            'Authorization' => 'Bearer ' . $token,
            'KSeF-Token' => $token,
            'Content-Type' => 'application/json',
        ], [
            'queryCriteria' => [
                'invoiceDateFrom' => str_replace(' ', 'T', (string) $from),
                'invoiceDateTo' => str_replace(' ', 'T', (string) $to),
            ],
            'pageOffset' => (int) $page_offset,
            'pageSize' => (int) $page_size,
        ], 25);

        if (is_wp_error($response)) {
            return $response;
        }
        $code = (int) ($response['code'] ?? 0);
        $payload = is_array($response['json'] ?? null) ? (array) $response['json'] : [];
        if ($code >= 400) {
            return new WP_Error('erp_omd_ksef_sync_http_error', sprintf(__('Błąd pobierania metadanych KSeF (HTTP %1$d): %2$s', 'erp-omd'), $code, $this->extract_http_error_message($payload)));
        }

        return [
            'items' => is_array($payload['invoices'] ?? null)
                ? $payload['invoices']
                : (is_array($payload['items'] ?? null) ? $payload['items'] : []),
        ];
    }

    private function enrich_item_with_xml(array $item, $token)
    {
        $ksef_reference = trim((string) ($item['ksefReferenceNumber'] ?? $item['ksefNumber'] ?? $item['referenceNumber'] ?? ''));
        if ($ksef_reference === '') {
            return $item;
        }

        $response = $this->connector->request('GET', '/invoices/ksef/' . rawurlencode($ksef_reference), [
            'Authorization' => 'Bearer ' . $token,
            'KSeF-Token' => $token,
            'Content-Type' => 'application/json',
        ], null, 25);

        if (is_wp_error($response)) {
            return $item;
        }

        $raw_body = trim((string) ($response['raw_body'] ?? ''));
        $xml_content = '';
        if ($raw_body !== '' && strpos($raw_body, '<') === 0) {
            $xml_content = $raw_body;
        } else {
            $payload = is_array($response['json'] ?? null) ? (array) $response['json'] : [];
            $xml_field = trim((string) ($payload['invoiceXml'] ?? $payload['xmlContent'] ?? $payload['xml'] ?? ''));
            if ($xml_field !== '') {
                $decoded = base64_decode($xml_field, true);
                $xml_content = (is_string($decoded) && trim($decoded) !== '') ? trim($decoded) : $xml_field;
            }
        }
        if ($xml_content === '') {
            return $item;
        }

        $item['xmlContent'] = $xml_content;
        $parsed = $this->parse_xml_summary($xml_content);
        if (! empty($parsed['invoice_number'])) {
            $item['invoiceNumber'] = $parsed['invoice_number'];
        }
        if (! empty($parsed['issue_date'])) {
            $item['issueDate'] = $parsed['issue_date'];
        }
        if (! empty($parsed['buyer_nip'])) {
            $item['buyerNip'] = $parsed['buyer_nip'];
        }
        if (! empty($parsed['seller_nip'])) {
            $item['sellerNip'] = $parsed['seller_nip'];
        }
        if (! empty($parsed['seller_name'])) {
            $item['sellerName'] = $parsed['seller_name'];
        }

        return $item;
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
            $kind = $this->resolve_kind($buyer_nip, $seller_nip);
            if ($scope === 'cost' && $kind !== 'cost') {
                continue;
            }
            if ($scope === 'sales' && $kind !== 'sales') {
                continue;
            }
            if ($invoice_number === '' && $ksef_reference === '') {
                continue;
            }

            $issue_date = (string) ($row['issueDate'] ?? $row['invoiceDate'] ?? '');
            if (strpos($issue_date, 'T') !== false) {
                $issue_date = substr($issue_date, 0, 10);
            }

            $documents[] = [
                'invoice_number' => $invoice_number,
                'issue_date' => $issue_date,
                'buyer_nip' => $buyer_nip,
                'seller_nip' => $seller_nip,
                'seller_name' => trim((string) ($row['sellerName'] ?? $row['seller']['name'] ?? '')),
                'our_company_nip' => $this->company_nip,
                'ksef_reference_number' => $ksef_reference,
                'net_amount' => (float) ($row['netAmount'] ?? 0),
                'vat_amount' => (float) ($row['vatAmount'] ?? 0),
                'gross_amount' => (float) ($row['grossAmount'] ?? 0),
                'xml_content' => (string) ($row['xmlContent'] ?? ''),
            ];
        }

        return $documents;
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
            return ['from' => $from, 'to' => $to];
        }

        $cursor = sanitize_text_field((string) get_option(self::OPTION_LAST_CURSOR, ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $cursor) === 1) {
            return ['from' => $cursor, 'to' => $to];
        }

        return ['from' => $to_date . ' 00:00:00', 'to' => $to];
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

    private function parse_xml_summary($xml_content)
    {
        if (! function_exists('simplexml_load_string')) {
            return [];
        }
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string((string) $xml_content);
        if (! ($xml instanceof SimpleXMLElement)) {
            return [];
        }

        return [
            'invoice_number' => $this->xpath_first_text($xml, ['//*[local-name()="P_2"]']),
            'issue_date' => $this->xpath_first_text($xml, ['//*[local-name()="P_1"]']),
            'buyer_nip' => $this->xpath_first_text($xml, ['//*[local-name()="Podmiot2"]//*[local-name()="NIP"]']),
            'seller_nip' => $this->xpath_first_text($xml, ['//*[local-name()="Podmiot1"]//*[local-name()="NIP"]']),
            'seller_name' => $this->xpath_first_text($xml, ['//*[local-name()="Podmiot1"]//*[local-name()="Nazwa"]', '//*[local-name()="Podmiot1"]//*[local-name()="PelnaNazwa"]']),
        ];
    }

    private function xpath_first_text(SimpleXMLElement $xml, array $paths)
    {
        foreach ($paths as $path) {
            $nodes = $xml->xpath((string) $path);
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

    private function extract_certificate_items($payload)
    {
        if (! is_array($payload)) {
            return [];
        }
        $candidates = [
            $payload['certificates'] ?? null,
            $payload['items'] ?? null,
            $payload['data'] ?? null,
            $payload['result'] ?? null,
            $payload['content'] ?? null,
            $payload,
        ];
        foreach ($candidates as $candidate) {
            if (! is_array($candidate)) {
                continue;
            }
            $normalized = $this->normalize_certificate_list($candidate);
            if ($normalized !== []) {
                return $normalized;
            }
        }

        return [];
    }

    private function normalize_certificate_list(array $candidate)
    {
        $is_assoc = array_keys($candidate) !== range(0, count($candidate) - 1);
        if ($is_assoc && $this->looks_like_certificate_record($candidate)) {
            return [$candidate];
        }
        if ($is_assoc) {
            foreach ($candidate as $row) {
                if (is_array($row)) {
                    $normalized = $this->normalize_certificate_list($row);
                    if ($normalized !== []) {
                        return $normalized;
                    }
                }
            }
            return [];
        }

        $items = [];
        foreach ($candidate as $row) {
            if (is_array($row) && $this->looks_like_certificate_record($row)) {
                $items[] = $row;
            }
        }

        return $items;
    }

    private function looks_like_certificate_record(array $row)
    {
        return isset($row['usage']) || isset($row['usages']) || isset($row['certificate']) || isset($row['publicKey']) || isset($row['publicKeyCertificate']);
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
            foreach ((array) $usages as $usage) {
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

    private function encrypt_ap_token($ap_token, $timestamp, $public_key_pem)
    {
        $public_key = openssl_pkey_get_public($public_key_pem);
        if ($public_key === false) {
            $x509 = openssl_x509_read($public_key_pem);
            if ($x509 !== false) {
                $public_key = openssl_pkey_get_public($x509);
            }
        }
        if ($public_key === false) {
            $this->last_diagnostic = __('Nieprawidłowy klucz publiczny KSeF.', 'erp-omd');
            return '';
        }

        $plain = (string) $ap_token . '|' . (string) $timestamp;
        $encrypted = '';
        $ok = openssl_public_encrypt($plain, $encrypted, $public_key, OPENSSL_PKCS1_OAEP_PADDING);
        if (! $ok || $encrypted === '') {
            $this->last_diagnostic = __('Nie udało się zaszyfrować tokenu AP.', 'erp-omd');
            return '';
        }

        return base64_encode($encrypted);
    }

    private function normalize_challenge_timestamp_millis($timestamp)
    {
        if (is_int($timestamp) || is_float($timestamp) || (is_string($timestamp) && is_numeric($timestamp))) {
            $value = (int) $timestamp;
            if ($value > 0 && $value < 2000000000) {
                $value *= 1000;
            }
            return $value > 0 ? (string) $value : '';
        }

        $parsed = strtotime((string) $timestamp);
        if ($parsed === false || $parsed <= 0) {
            return '';
        }

        return (string) ($parsed * 1000);
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
        ];
        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return __('Nieznany błąd KSeF.', 'erp-omd');
    }

    private function http_response_diagnostic($payload, $http_code, $fallback)
    {
        $message = is_array($payload) ? $this->extract_http_error_message($payload) : '';
        if ($message === '') {
            $message = (string) $fallback;
        }

        return sprintf('HTTP %1$d: %2$s', (int) $http_code, $message);
    }

    private function api_base_url()
    {
        $environment = sanitize_key((string) get_option(self::OPTION_ENVIRONMENT, 'prod'));
        $defaults = [
            'prod' => 'https://api.ksef.mf.gov.pl',
            'test' => 'https://ksef-test.mf.gov.pl',
            'demo' => 'https://ksef-demo.mf.gov.pl',
        ];
        $default_base = $defaults[$environment] ?? 'https://api.ksef.mf.gov.pl';

        $api_base_url = trim((string) get_option(self::OPTION_API_BASE_URL, $default_base));
        if ($api_base_url === '' || ! wp_http_validate_url($api_base_url)) {
            return $default_base;
        }

        return rtrim($api_base_url, '/');
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

    private function encrypt_value($value)
    {
        $key = hash('sha256', (string) wp_salt('auth'), true);
        $iv = substr(hash('sha256', (string) wp_salt('secure_auth')), 0, 16);
        $cipher = openssl_encrypt((string) $value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return $cipher !== false ? base64_encode($cipher) : '';
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
}
