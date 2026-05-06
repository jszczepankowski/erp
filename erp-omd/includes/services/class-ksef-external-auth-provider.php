<?php

class ERP_OMD_KSeF_External_Auth_Provider implements ERP_OMD_KSeF_Auth_Provider_Interface
{
    private $storage;
    private $gateway_base_url;
    private $gateway_api_key;

    public function __construct($gateway_base_url, $gateway_api_key = '', $storage = null)
    {
        $this->gateway_base_url = untrailingslashit(trim((string) $gateway_base_url));
        $this->gateway_api_key = trim((string) $gateway_api_key);
        $this->storage = $storage instanceof ERP_OMD_KSeF_Auth_Storage ? $storage : new ERP_OMD_KSeF_Auth_Storage();
    }

    public function get_challenge($environment) { return new WP_Error('erp_omd_ksef_gateway_not_supported', 'Gateway mode does not expose get_challenge.'); }
    public function authenticate_with_ksef_token($environment, $ksef_token, $context_identifier) { return new WP_Error('erp_omd_ksef_gateway_not_supported', 'Gateway mode does not expose authenticate_with_ksef_token.'); }
    public function get_auth_status($environment, $reference_number, $authentication_token) { return new WP_Error('erp_omd_ksef_gateway_not_supported', 'Gateway mode does not expose get_auth_status.'); }
    public function redeem_token($environment, $authentication_token) { return new WP_Error('erp_omd_ksef_gateway_not_supported', 'Gateway mode does not expose redeem_token.'); }
    public function refresh_access_token($environment, $refresh_token) { return new WP_Error('erp_omd_ksef_gateway_not_supported', 'Gateway mode does not expose refresh_access_token.'); }

    public function ensure_access_token($environment, $ksef_token, $context_identifier)
    {
        if ($this->gateway_base_url === '') {
            return new WP_Error('erp_omd_ksef_gateway_url_missing', __('Brak URL gateway KSeF.', 'erp-omd'));
        }

        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];
        if ($this->gateway_api_key !== '') {
            $headers['X-Api-Key'] = $this->gateway_api_key;
        }

        $payload = [
            'environment' => strtoupper((string) $environment),
            'contextIdentifier' => (string) $context_identifier,
            'apToken' => (string) $ksef_token,
        ];

        $response = wp_remote_post($this->gateway_base_url . '/auth/access-token', [
            'headers' => $headers,
            'body' => wp_json_encode($payload),
            'timeout' => 45,
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('erp_omd_ksef_gateway_request_failed', (string) $response->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code >= 400 || ! is_array($body)) {
            return new WP_Error('erp_omd_ksef_gateway_http_' . $code, __('Gateway KSeF zwrócił błąd.', 'erp-omd'));
        }

        $access = trim((string) ($body['access_token'] ?? $body['accessToken'] ?? ''));
        $refresh = trim((string) ($body['refresh_token'] ?? $body['refreshToken'] ?? ''));
        if ($access === '') {
            return new WP_Error('erp_omd_ksef_gateway_invalid_payload', __('Gateway nie zwrócił access tokenu.', 'erp-omd'));
        }

        $this->storage->save_tokens((string) $environment, [
            'token_type' => 'Bearer',
            'access_token' => $access,
            'refresh_token' => $refresh,
            'access_expires_at' => (string) ($body['access_expires_at'] ?? gmdate('c', time() + 3600)),
            'refresh_expires_at' => (string) ($body['refresh_expires_at'] ?? gmdate('c', time() + 86400)),
        ]);

        return ['ok' => true, 'source' => 'external_gateway', 'access_token' => $access, 'refresh_token' => $refresh];
    }
}
