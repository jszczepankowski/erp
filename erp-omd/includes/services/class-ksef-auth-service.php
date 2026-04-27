<?php

class ERP_OMD_KSeF_Auth_Service implements ERP_OMD_KSeF_Auth_Provider_Interface
{
    /** @var mixed */
    private $connector;

    /** @var ERP_OMD_KSeF_Auth_Storage */
    private $storage;

    /** @var ERP_OMD_KSeF_Public_Key_Service */
    private $public_key_service;

    /**
     * @param mixed $connector
     * @param ERP_OMD_KSeF_Auth_Storage|null $storage
     * @param ERP_OMD_KSeF_Public_Key_Service|null $public_key_service
     */
    public function __construct($connector, $storage = null, $public_key_service = null)
    {
        $this->connector = $connector;
        $this->storage = $storage instanceof ERP_OMD_KSeF_Auth_Storage ? $storage : new ERP_OMD_KSeF_Auth_Storage();
        $this->public_key_service = $public_key_service instanceof ERP_OMD_KSeF_Public_Key_Service ? $public_key_service : new ERP_OMD_KSeF_Public_Key_Service();
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

        $payload = [
            'challenge' => $challenge,
            'contextIdentifier' => (string) $context_identifier,
            'encryptedToken' => (string) ($encryption['encrypted_token'] ?? ''),
        ];

        return $this->request('POST', '/auth/ksef-token', [
            'Content-Type' => 'application/json',
        ], $payload, $environment);
    }

    public function get_auth_status($environment, $reference_number, $authentication_token)
    {
        return $this->request('GET', '/auth/' . rawurlencode((string) $reference_number), [
            'Authorization' => 'Bearer ' . trim((string) $authentication_token),
        ], null, $environment);
    }

    public function redeem_token($environment, $authentication_token)
    {
        $response = $this->request('POST', '/auth/token/redeem', [
            'Authorization' => 'Bearer ' . trim((string) $authentication_token),
            'Content-Type' => 'application/json',
        ], [], $environment);

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
        $response = $this->request('POST', '/auth/token/refresh', [
            'Authorization' => 'Bearer ' . trim((string) $refresh_token),
            'Content-Type' => 'application/json',
        ], [], $environment);

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

        $authentication_token = (string) (($auth['json']['authenticationToken'] ?? $auth['json']['authentication_token'] ?? ''));
        if ($authentication_token === '') {
            return new WP_Error('erp_omd_ksef_authentication_token_missing', __('Brak authenticationToken w odpowiedzi auth.', 'erp-omd'));
        }

        $redeem = $this->redeem_token($environment, $authentication_token);
        if ($redeem instanceof WP_Error) {
            return $redeem;
        }

        $stored = $this->storage->get_tokens($environment);

        return [
            'ok' => true,
            'source' => 'reauth',
            'access_token' => (string) ($stored['access_token'] ?? ''),
            'refresh_token' => (string) ($stored['refresh_token'] ?? ''),
        ];
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
        return $this->connector->request($method, $path, $headers, $body);
    }

    /**
     * @param array<string,mixed> $json
     * @return array<string,mixed>
     */
    private function extract_tokens(array $json)
    {
        $access = (string) ($json['accessToken'] ?? $json['access_token'] ?? '');
        $refresh = (string) ($json['refreshToken'] ?? $json['refresh_token'] ?? '');
        if ($access === '' || $refresh === '') {
            return ['ok' => false, 'data' => []];
        }

        return [
            'ok' => true,
            'data' => [
                'token_type' => (string) ($json['tokenType'] ?? $json['token_type'] ?? 'Bearer'),
                'access_token' => $access,
                'refresh_token' => $refresh,
                'access_expires_at' => $this->build_expiration((int) ($json['accessTokenExpiresIn'] ?? $json['access_token_expires_in'] ?? 3600)),
                'refresh_expires_at' => $this->build_expiration((int) ($json['refreshTokenExpiresIn'] ?? $json['refresh_token_expires_in'] ?? 86400)),
            ],
        ];
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
}
