<?php

class ERP_OMD_KSeF_Auth_Service implements ERP_OMD_KSeF_Auth_Provider_Interface
{
    /** @var mixed */
    private $connector;

    /** @var ERP_OMD_KSeF_Auth_Storage */
    private $storage;

    /** @var ERP_OMD_KSeF_Public_Key_Service */
    private $public_key_service;

    /** @var array<int,int> */
    private $auth_status_poll_delays_seconds;

    /** @var callable */
    private $sleep_callback;

    /**
     * @param mixed $connector
     * @param ERP_OMD_KSeF_Auth_Storage|null $storage
     * @param ERP_OMD_KSeF_Public_Key_Service|null $public_key_service
     * @param array<int,int>|null $auth_status_poll_delays_seconds
     * @param callable|null $sleep_callback
     */
    public function __construct($connector, $storage = null, $public_key_service = null, array $auth_status_poll_delays_seconds = null, $sleep_callback = null)
    {
        $this->connector = $connector;
        $this->storage = $storage instanceof ERP_OMD_KSeF_Auth_Storage ? $storage : new ERP_OMD_KSeF_Auth_Storage();
        $this->public_key_service = $public_key_service instanceof ERP_OMD_KSeF_Public_Key_Service ? $public_key_service : new ERP_OMD_KSeF_Public_Key_Service();
        $this->auth_status_poll_delays_seconds = $this->normalize_poll_delays($auth_status_poll_delays_seconds ?: [1, 2, 4, 8, 16, 30]);
        $this->sleep_callback = is_callable($sleep_callback)
            ? $sleep_callback
            : static function ($seconds) {
                $seconds = max(0, (int) $seconds);
                if ($seconds > 0) {
                    sleep($seconds);
                }
            };
    }

    public function get_challenge($environment)
    {
        return $this->request('POST', '/auth/challenge', [], null, $environment);
    }

    public function authenticate_with_ksef_token($environment, $ksef_token, $context_identifier)
    {
        $challenge_response = $this->get_challenge($environment);
        if ($challenge_response instanceof WP_Error) {
            return $challenge_response;
        }

        $challenge = (string) ($challenge_response['json']['challenge'] ?? '');
        if ($challenge === '') {
            return new WP_Error('erp_omd_ksef_challenge_missing', __('Brak challenge w odpowiedzi KSeF.', 'erp-omd'));
        }

        $public_key = $this->public_key_service->get_encryption_public_key($environment);
        if ($public_key instanceof WP_Error) {
            return $public_key;
        }

        $encryption = $this->public_key_service->encrypt_ksef_token_payload((string) $ksef_token, $public_key);
        if ($encryption instanceof WP_Error) {
            return $encryption;
        }

        $context_payload = $this->build_context_identifier_payload($context_identifier);
        if ($context_payload instanceof WP_Error) {
            return $context_payload;
        }

        $encrypted_token = (string) ($encryption['encrypted_token'] ?? '');
        $xml_payload = $this->build_auth_token_request_xml($challenge, $context_payload, $encrypted_token);
        $json_payload = [
            'challenge' => (string) $challenge,
            'contextIdentifier' => [
                'type' => (string) ($context_payload['type'] ?? ''),
                'value' => (string) ($context_payload['value'] ?? ''),
            ],
            'encryptedToken' => $encrypted_token,
        ];

        $attempts = [
            [
                'headers' => ['Content-Type' => 'application/xml'],
                'body' => $xml_payload,
                'label' => 'application/xml',
            ],
            [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => $json_payload,
                'label' => 'application/json',
            ],
        ];

        $last_error = null;
        foreach ($attempts as $attempt) {
            $response = $this->request('POST', '/auth/ksef-token', (array) ($attempt['headers'] ?? []), $attempt['body'] ?? null, $environment);
            if (! ($response instanceof WP_Error)) {
                return $response;
            }

            $last_error = $response;
            if ((string) $response->get_error_code() !== 'ksef_http_415') {
                return $response;
            }
        }

        if ($last_error instanceof WP_Error) {
            return new WP_Error(
                (string) $last_error->get_error_code(),
                (string) $last_error->get_error_message() . ' | auth_content_type_attempts: application/xml,application/json'
            );
        }

        return new WP_Error('erp_omd_ksef_auth_ksef_token_request_failed', __('Nie udało się wysłać żądania /auth/ksef-token.', 'erp-omd'));
    }

    public function get_auth_status($environment, $reference_number, $authentication_token = '')
    {
        $headers = [];
        $authentication_token = trim((string) $authentication_token);
        if ($authentication_token !== '') {
            $headers['Authorization'] = 'Bearer ' . $authentication_token;
        }

        return $this->request('GET', '/auth/' . rawurlencode((string) $reference_number), $headers, null, $environment);
    }

    public function redeem_token($environment, $authentication_token)
    {
        $response = $this->request_token_exchange('/auth/token/redeem', $authentication_token, $environment, true);

        if ($response instanceof WP_Error) {
            return $response;
        }

        $tokens = $this->extract_tokens((array) ($response['json'] ?? []));
        if (! $tokens['ok']) {
            return new WP_Error('erp_omd_ksef_redeem_invalid_payload', __('Brak accessToken/refreshToken w odpowiedzi redeem.', 'erp-omd'));
        }

        $this->storage->save_tokens($environment, $tokens['data']);
        return $response;
    }

    public function refresh_access_token($environment, $refresh_token)
    {
        $response = $this->request_token_exchange('/auth/token/refresh', $refresh_token, $environment);

        if ($response instanceof WP_Error) {
            return $response;
        }

        $tokens = $this->extract_tokens((array) ($response['json'] ?? []));
        if (! $tokens['ok']) {
            return new WP_Error('erp_omd_ksef_refresh_invalid_payload', __('Brak accessToken/refreshToken w odpowiedzi refresh.', 'erp-omd'));
        }

        $this->storage->save_tokens($environment, $tokens['data']);
        return $response;
    }

    /**
     * @param string $path
     * @param string $token
     * @param string $environment
     * @param bool $single_use_token
     * @return array<string,mixed>|WP_Error
     */
    private function request_token_exchange($path, $token, $environment, $single_use_token = false)
    {
        $raw_token = trim((string) $token);
        if ($single_use_token) {
            $attempts = [
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $raw_token,
                    ],
                    'body' => null,
                    'label' => 'bearer-no-body',
                ],
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $raw_token,
                        'Content-Type' => 'application/json',
                    ],
                    'body' => [],
                    'label' => 'bearer-json-empty',
                ],
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $raw_token,
                        // Compatibility hint: some environments/documentation variants refer to AuthenticationToken explicitly.
                        'AuthenticationToken' => $raw_token,
                    ],
                    'body' => null,
                    'label' => 'bearer-authentication-token-header',
                ],
            ];

            $single_use_attempt_log = [];
            $last_error = null;
            foreach ($attempts as $attempt) {
                $single_use_attempt_log[] = (string) ($attempt['label'] ?? 'unknown');
                $response = $this->request('POST', (string) $path, (array) ($attempt['headers'] ?? []), $attempt['body'] ?? null, $environment);
                $code = (string) ($response instanceof WP_Error ? $response->get_error_code() : '');
                if (! ($response instanceof WP_Error) || ! in_array($code, ['ksef_http_400', 'ksef_http_415', 'ksef_http_422'], true)) {
                    return $response;
                }
                $last_error = $response;
            }

            if ($last_error instanceof WP_Error) {
                return new WP_Error(
                    (string) $last_error->get_error_code(),
                    (string) $last_error->get_error_message() . ' | single_use_token_exchange_attempts: ' . implode(';', $single_use_attempt_log)
                );
            }

            return new WP_Error('erp_omd_ksef_redeem_failed', __('Nie udało się wymienić jednorazowego authenticationToken na JWT.', 'erp-omd'));
        }

        $authorization_candidates = [
            'Bearer ' . $raw_token,
        ];
        if ($raw_token !== '') {
            $authorization_candidates[] = $raw_token;
        }
        $authorization_candidates = array_values(array_unique(array_filter($authorization_candidates, 'strlen')));

        $attempts = [];
        $last_error = null;
        foreach ($authorization_candidates as $authorization_value) {
            $base_headers = [
                'Authorization' => (string) $authorization_value,
            ];

            $attempts[] = 'auth=' . (strpos($authorization_value, 'Bearer ') === 0 ? 'bearer' : 'raw') . ',body=none';
            $response = $this->request('POST', (string) $path, $base_headers, null, $environment);
            if (! ($response instanceof WP_Error) || (string) $response->get_error_code() !== 'ksef_http_400') {
                return $response;
            }
            $last_error = $response;

            $json_headers = $base_headers;
            $json_headers['Content-Type'] = 'application/json';
            $attempts[] = 'auth=' . (strpos($authorization_value, 'Bearer ') === 0 ? 'bearer' : 'raw') . ',body=json-empty';
            $response = $this->request('POST', (string) $path, $json_headers, [], $environment);
            if (! ($response instanceof WP_Error) || (string) $response->get_error_code() !== 'ksef_http_400') {
                return $response;
            }
            $last_error = $response;
        }

        if ($last_error instanceof WP_Error) {
            return new WP_Error(
                (string) $last_error->get_error_code(),
                (string) $last_error->get_error_message() . ' | token_exchange_attempts: ' . implode(';', $attempts)
            );
        }

        return new WP_Error('erp_omd_ksef_token_exchange_failed', __('Nie udało się wykonać wymiany tokenu KSeF.', 'erp-omd'));
    }

    public function ensure_access_token($environment, $ksef_token, $context_identifier)
    {
        $saved = $this->storage->get_tokens($environment);
        $access_token = trim((string) ($saved['access_token'] ?? ''));
        if ($access_token !== '' && ! $this->is_expired((string) ($saved['access_expires_at'] ?? ''))) {
            return [
                'ok' => true,
                'source' => 'cache',
                'access_token' => $access_token,
                'refresh_token' => (string) ($saved['refresh_token'] ?? ''),
            ];
        }

        $refresh_token = trim((string) ($saved['refresh_token'] ?? ''));
        if ($refresh_token !== '') {
            $refresh = $this->refresh_access_token($environment, $refresh_token);
            if (! ($refresh instanceof WP_Error)) {
                $stored = $this->storage->get_tokens($environment);
                if (trim((string) ($stored['access_token'] ?? '')) !== '') {
                    return [
                        'ok' => true,
                        'source' => 'refresh',
                        'access_token' => (string) $stored['access_token'],
                        'refresh_token' => (string) ($stored['refresh_token'] ?? ''),
                    ];
                }
            }
        }

        $auth = $this->authenticate_with_ksef_token($environment, $ksef_token, $context_identifier);
        if ($auth instanceof WP_Error) {
            return $auth;
        }

        $auth_payload = (array) ($auth['json'] ?? []);
        $authentication_token = $this->extract_authentication_token($auth_payload);
        if ($authentication_token === '') {
            $authentication_token = $this->extract_authentication_token_from_headers((array) ($auth['headers'] ?? []));
        }
        $reference_number = (string) (($auth_payload['referenceNumber'] ?? $auth_payload['reference_number'] ?? ''));
        $status_ready = $this->is_auth_status_ready($auth_payload);
        $last_status_value = $this->extract_auth_status_value($auth_payload);

        if ($reference_number !== '') {
            $attempts = count($this->auth_status_poll_delays_seconds) + 1;
            for ($attempt = 0; $attempt < $attempts; $attempt++) {
                $status_payload = $attempt === 0 ? $auth_payload : [];
                if ($attempt > 0) {
                    $status = $this->get_auth_status($environment, $reference_number, $authentication_token);
                    if ($status instanceof WP_Error) {
                        return $status;
                    }
                    $status_payload = (array) ($status['json'] ?? []);
                    if ($authentication_token === '') {
                        $authentication_token = $this->extract_authentication_token_from_headers((array) ($status['headers'] ?? []));
                    }
                }
                $status_value = $this->extract_auth_status_value($status_payload);
                if ($status_value !== '') {
                    $last_status_value = $status_value;
                }

                $candidate_token = $this->extract_authentication_token($status_payload);
                if ($candidate_token !== '') {
                    $authentication_token = $candidate_token;
                }

                if ($authentication_token !== '' && $this->is_auth_status_ready($status_payload)) {
                    $status_ready = true;
                    break;
                }

                if ($attempt < $attempts - 1) {
                    $this->pause_before_next_auth_status_poll($attempt);
                }
            }
        }

        $redeem_error = null;
        if ($authentication_token !== '') {
            $redeem = $this->redeem_token($environment, $authentication_token);
            if (! ($redeem instanceof WP_Error)) {
                $stored = $this->storage->get_tokens($environment);
                return [
                    'ok' => true,
                    'source' => 'reauth',
                    'access_token' => (string) ($stored['access_token'] ?? ''),
                    'refresh_token' => (string) ($stored['refresh_token'] ?? ''),
                ];
            }
            $redeem_error = $redeem;
        }

        if ($authentication_token === '' || ! $status_ready) {
            $upstream_code = (string) ($auth_payload['code'] ?? $auth_payload['errorCode'] ?? $auth_payload['error_code'] ?? 'erp_omd_ksef_authentication_token_missing');
            $upstream_message = (string) ($auth_payload['description'] ?? $auth_payload['message'] ?? $auth_payload['title'] ?? __('Brak gotowego authenticationToken po zakończeniu auth status.', 'erp-omd'));
            $upstream_message .= ' ' . __('Sprawdź, czy status uwierzytelniania nie jest nadal w toku i ponów po dłuższym czasie (weryfikacja OCSP/CRL może trwać).', 'erp-omd');
            if ($last_status_value !== '') {
                $upstream_message .= ' | auth_status=' . $last_status_value;
            }
            if ($redeem_error instanceof WP_Error) {
                return $redeem_error;
            }
            return new WP_Error($upstream_code, $upstream_message);
        }

        if ($redeem_error instanceof WP_Error) {
            return $redeem_error;
        }

        return new WP_Error('erp_omd_ksef_redeem_failed', __('Nie udało się wymienić authenticationToken na JWT.', 'erp-omd'));
    }

    /**
     * @param string $method
     * @param string $path
     * @param array<string,string> $headers
     * @param array<string,mixed>|null $body
     * @param string $environment
     * @return array<string,mixed>|WP_Error
     */
    private function request($method, $path, array $headers, $body, $environment)
    {
        $headers['X-Environment'] = $this->normalize_environment($environment);
        if (empty($headers['Accept'])) {
            $headers['Accept'] = 'application/json';
        }
        $headers['X-Error-Format'] = 'problem-details';
        $response = $this->connector->request($method, $path, $headers, $body);
        if ($response instanceof WP_Error) {
            return $response;
        }

        $code = (int) ($response['code'] ?? 0);
        if ($code >= 400) {
            return $this->build_api_error_from_response((array) $response, (string) $method, (string) $path, $body, $headers);
        }

        return $response;
    }

    /**
     * @param array<string,mixed> $json
     * @return array<string,mixed>
     */
    private function extract_tokens(array $json)
    {
        $access_token_payload = $json['accessToken'] ?? $json['access_token'] ?? '';
        $refresh_token_payload = $json['refreshToken'] ?? $json['refresh_token'] ?? '';
        $access = $this->extract_token_value($access_token_payload);
        $refresh = $this->extract_token_value($refresh_token_payload);
        if ($access === '' || $refresh === '') {
            return ['ok' => false, 'data' => []];
        }

        $access_valid_until = $this->extract_token_valid_until($access_token_payload);
        $refresh_valid_until = $this->extract_token_valid_until($refresh_token_payload);
        $access_expires_at = $access_valid_until !== '' ? $access_valid_until : $this->build_expiration((int) ($json['accessTokenExpiresIn'] ?? $json['access_token_expires_in'] ?? 3600));
        $refresh_expires_at = $refresh_valid_until !== '' ? $refresh_valid_until : $this->build_expiration((int) ($json['refreshTokenExpiresIn'] ?? $json['refresh_token_expires_in'] ?? 86400));

        return [
            'ok' => true,
            'data' => [
                'token_type' => (string) ($json['tokenType'] ?? $json['token_type'] ?? 'Bearer'),
                'access_token' => $access,
                'refresh_token' => $refresh,
                'access_expires_at' => $access_expires_at,
                'refresh_expires_at' => $refresh_expires_at,
            ],
        ];
    }

    /**
     * @param mixed $payload
     * @return string
     */
    private function extract_token_value($payload)
    {
        if (is_array($payload)) {
            return trim((string) ($payload['token'] ?? $payload['Token'] ?? ''));
        }

        return trim((string) $payload);
    }

    /**
     * @param mixed $payload
     * @return string
     */
    private function extract_token_valid_until($payload)
    {
        if (! is_array($payload)) {
            return '';
        }

        $value = trim((string) ($payload['validUntil'] ?? $payload['valid_until'] ?? ''));
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);
        if (! is_int($timestamp) || $timestamp <= 0) {
            return '';
        }

        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    /**
     * @param int $seconds
     * @return string
     */
    private function build_expiration($seconds)
    {
        $seconds = max(60, (int) $seconds);
        return gmdate('Y-m-d H:i:s', time() + $seconds);
    }

    /**
     * @param string $expires_at
     * @return bool
     */
    private function is_expired($expires_at)
    {
        $expires_at = trim((string) $expires_at);
        if ($expires_at === '') {
            return true;
        }

        $timestamp = strtotime($expires_at . ' UTC');
        if (! is_int($timestamp) || $timestamp <= 0) {
            return true;
        }

        return $timestamp <= time();
    }

    /**
     * @param string $environment
     * @return string
     */
    private function normalize_environment($environment)
    {
        $env = strtoupper(trim((string) $environment));
        return in_array($env, ['TEST', 'DEMO', 'PRD'], true) ? $env : 'TEST';
    }

    /**
     * @param array<int,int> $delays
     * @return array<int,int>
     */
    private function normalize_poll_delays(array $delays)
    {
        $normalized = [];
        foreach ($delays as $delay) {
            $value = max(0, (int) $delay);
            if ($value > 0) {
                $normalized[] = $value;
            }
        }

        return $normalized !== [] ? $normalized : [1, 2, 4, 8, 16, 30];
    }

    /**
     * @param int $attempt
     * @return void
     */
    private function pause_before_next_auth_status_poll($attempt)
    {
        $seconds = (int) ($this->auth_status_poll_delays_seconds[(int) $attempt] ?? 0);
        if ($seconds <= 0) {
            return;
        }

        call_user_func($this->sleep_callback, $seconds);
    }

    /**
     * @param array<string,mixed> $payload
     * @return bool
     */
    private function is_auth_status_ready(array $payload)
    {
        $status = $this->extract_auth_status_value($payload);
        if ($status === '') {
            return false;
        }

        return in_array($status, ['completed', 'finished', 'authorized', 'authenticated', 'success'], true);
    }

    /**
     * @param array<string,mixed> $payload
     * @return string
     */
    private function extract_auth_status_value(array $payload)
    {
        $candidates = [
            $payload['status'] ?? null,
            $payload['phase'] ?? null,
            $payload['operationStatus'] ?? null,
            $payload['authStatus'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->normalize_status_candidate($candidate);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return '';
    }

    /**
     * @param mixed $candidate
     * @return string
     */
    private function normalize_status_candidate($candidate)
    {
        if (is_string($candidate) || is_numeric($candidate)) {
            return strtolower(trim((string) $candidate));
        }

        if (! is_array($candidate)) {
            return '';
        }

        $keys = ['status', 'phase', 'operationStatus', 'code', 'name', 'value', 'state'];
        foreach ($keys as $key) {
            if (! array_key_exists($key, $candidate)) {
                continue;
            }

            $value = $this->normalize_status_candidate($candidate[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @param string|array<string,mixed> $context_identifier
     * @return array<string,string>|WP_Error
     */
    private function build_context_identifier_payload($context_identifier)
    {
        if (is_array($context_identifier)) {
            $type = $this->normalize_context_identifier_type((string) ($context_identifier['type'] ?? ''));
            $value = trim((string) ($context_identifier['value'] ?? ''));
            if ($type !== '' && $value !== '') {
                return ['type' => $type, 'value' => $value];
            }
        }

        $raw = trim((string) $context_identifier);
        if ($raw === '') {
            return new WP_Error('erp_omd_ksef_context_identifier_missing', __('ContextIdentifier nie może być pusty.', 'erp-omd'));
        }

        if (strpos($raw, ':') !== false) {
            [$type_raw, $value_raw] = array_pad(explode(':', $raw, 2), 2, '');
            $type = $this->normalize_context_identifier_type((string) $type_raw);
            $value = trim((string) $value_raw);
            if ($type !== '' && $value !== '') {
                return ['type' => $type, 'value' => $value];
            }
        }

        $nip = preg_replace('/[^0-9]/', '', $raw);
        if (is_string($nip) && strlen($nip) === 10) {
            return ['type' => 'Nip', 'value' => $nip];
        }

        return ['type' => 'InternalId', 'value' => $raw];
    }

    /**
     * @param string $type
     * @return string
     */
    private function normalize_context_identifier_type($type)
    {
        $raw = trim((string) $type);
        if ($raw === '') {
            return '';
        }

        $key = strtolower(preg_replace('/[^a-z0-9]/i', '', $raw) ?: '');
        $aliases = [
            'nip' => 'Nip',
            'plnip' => 'Nip',
            'internalid' => 'InternalId',
        ];

        if (isset($aliases[$key])) {
            return $aliases[$key];
        }

        return ucfirst(strtolower($raw));
    }

    /**
     * @param array<string,mixed> $response
     * @param string $method
     * @param string $path
     * @param array<string,mixed>|null $body
     * @param array<string,string> $request_headers
     * @return WP_Error
     */
    private function build_api_error_from_response(array $response, $method = '', $path = '', $body = null, array $request_headers = [])
    {
        $json = (array) ($response['json'] ?? []);
        $http_code = (int) ($response['code'] ?? 0);

        $error_code = (string) (
            $json['code']
            ?? $json['errorCode']
            ?? $json['error_code']
            ?? $json['reasonCode']
            ?? ('ksef_http_' . ($http_code > 0 ? $http_code : 'error'))
        );
        $error_message = (string) (
            $json['description']
            ?? $json['detail']
            ?? $json['message']
            ?? $json['title']
            ?? ('KSeF auth request failed with HTTP ' . ($http_code > 0 ? $http_code : 0))
        );

        $details = (array) ($json['details'] ?? []);
        if ($details !== []) {
            $error_message .= ' | ' . implode(' | ', array_map('strval', $details));
        }

        $endpoint = trim(strtoupper(trim((string) $method)) . ' ' . trim((string) $path));
        if ($endpoint !== '') {
            $error_message .= ' | endpoint: ' . $endpoint;
        }

        $response_headers = (array) ($response['headers'] ?? []);
        $retry_after = trim((string) ($response_headers['retry-after'] ?? $response_headers['Retry-After'] ?? ''));
        $www_authenticate = trim((string) ($response_headers['www-authenticate'] ?? $response_headers['WWW-Authenticate'] ?? ''));
        if ($retry_after !== '' || $www_authenticate !== '') {
            $parts = [];
            if ($retry_after !== '') {
                $parts[] = 'retry_after=' . $retry_after;
            }
            if ($www_authenticate !== '') {
                $parts[] = 'www_authenticate=' . $www_authenticate;
            }
            $error_message .= ' | response_headers: ' . implode(', ', $parts);
        }

        if (strpos($path, '/auth/token/') === 0) {
            $authorization = trim((string) ($request_headers['Authorization'] ?? ''));
            $auth_token = '';
            if (strpos($authorization, 'Bearer ') === 0) {
                $auth_token = substr($authorization, 7);
            } else {
                $auth_token = $authorization;
            }
            $explicit_authentication_token = trim((string) ($request_headers['AuthenticationToken'] ?? ''));
            $error_message .= ' | token_exchange_request:'
                . ' has_bearer=' . (strpos($authorization, 'Bearer ') === 0 ? 'yes' : 'no')
                . ', bearer_len=' . strlen((string) $auth_token)
                . ', auth_token_header_len=' . strlen($explicit_authentication_token)
                . ', body_present=' . (is_array($body) ? 'yes' : 'no');
        }

        if ($path === '/auth/ksef-token') {
            $context_type = '';
            $context_value = '';
            $encrypted_token = '';
            $challenge = '';
            if (is_array($body)) {
                $context = (array) ($body['contextIdentifier'] ?? []);
                $context_type = trim((string) ($context['type'] ?? ''));
                $context_value = trim((string) ($context['value'] ?? ''));
                $encrypted_token = (string) ($body['encryptedToken'] ?? '');
                $challenge = (string) ($body['challenge'] ?? '');
            } elseif (is_string($body)) {
                if (preg_match('/<Challenge>([^<]*)<\\/Challenge>/i', $body, $match) === 1) {
                    $challenge = trim((string) ($match[1] ?? ''));
                }
                if (preg_match('/<ContextIdentifier\\s+Type=\"([^\"]+)\"[^>]*>([^<]*)<\\/ContextIdentifier>/i', $body, $match) === 1) {
                    $context_type = trim((string) ($match[1] ?? ''));
                    $context_value = trim((string) ($match[2] ?? ''));
                }
                if (preg_match('/<EncryptedToken>([^<]*)<\\/EncryptedToken>/i', $body, $match) === 1) {
                    $encrypted_token = trim((string) ($match[1] ?? ''));
                }
            }
            $error_message .= ' | auth_payload:'
                . ' context_type=' . ($context_type !== '' ? $context_type : 'empty')
                . ', context_value_len=' . strlen($context_value)
                . ', encrypted_token_len=' . strlen($encrypted_token)
                . ', challenge_len=' . strlen($challenge);

            $context_digits = preg_replace('/[^0-9]/', '', $context_value);
            if ($context_type === 'InternalId' && is_string($context_digits) && strlen($context_digits) > 0 && strlen($context_digits) !== 10) {
                $error_message .= ' | hint: ustaw ContextIdentifier jako Nip:XXXXXXXXXX (10 cyfr NIP) lub jawnie InternalId:<wartosc> jeżeli wymagane.';
            }
        }

        if ($error_message === '' || strpos($error_message, 'KSeF auth request failed with HTTP') === 0) {
            $raw_body = trim((string) ($response['raw_body'] ?? ''));
            if ($raw_body !== '') {
                $error_message .= ' | raw: ' . mb_substr($raw_body, 0, 300);
            }
        }

        return new WP_Error($error_code, $error_message);
    }

    /**
     * @param array<string,mixed> $payload
     * @return string
     */
    private function extract_authentication_token(array $payload)
    {
        return trim((string) (
            $payload['authenticationToken']['token']
            ?? $payload['authenticationToken']['value']
            ?? $payload['authenticationToken']['Token']
            ?? $payload['authenticationToken']
            ?? $payload['authenticationTokenValue']
            ?? $payload['authToken']
            ?? $payload['authentication_token']['token']
            ?? $payload['authentication_token']['value']
            ?? $payload['authentication_token']
            ?? $payload['authentication_token_value']
            ?? $payload['token']
            ?? ''
        ));
    }

    /**
     * @param array<string,mixed> $headers
     * @return string
     */
    private function extract_authentication_token_from_headers(array $headers)
    {
        $candidates = [
            (string) ($headers['authentication-token'] ?? ''),
            (string) ($headers['authenticationtoken'] ?? ''),
            (string) ($headers['x-authentication-token'] ?? ''),
            (string) ($headers['x-auth-token'] ?? ''),
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        $authorization = trim((string) ($headers['authorization'] ?? ''));
        if (strpos($authorization, 'Bearer ') === 0) {
            return trim((string) substr($authorization, 7));
        }

        return '';
    }

    /**
     * @param string $challenge
     * @param array<string,string> $context_payload
     * @param string $encrypted_token
     * @return string
     */
    private function build_auth_token_request_xml($challenge, array $context_payload, $encrypted_token)
    {
        $challenge = htmlspecialchars((string) $challenge, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $context_type = htmlspecialchars((string) ($context_payload['type'] ?? ''), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $context_value = htmlspecialchars((string) ($context_payload['value'] ?? ''), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $encrypted_token = htmlspecialchars((string) $encrypted_token, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<AuthTokenRequest xmlns="http://ksef.mf.gov.pl/auth/types/2022/01/26/1.0">'
            . '<Challenge>' . $challenge . '</Challenge>'
            . '<ContextIdentifier Type="' . $context_type . '">' . $context_value . '</ContextIdentifier>'
            . '<EncryptedToken>' . $encrypted_token . '</EncryptedToken>'
            . '</AuthTokenRequest>';
    }
}
