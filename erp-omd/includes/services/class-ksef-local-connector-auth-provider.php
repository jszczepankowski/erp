<?php

class ERP_OMD_KSeF_Local_Connector_Auth_Provider implements ERP_OMD_KSeF_Auth_Provider_Interface
{
    private $storage;
    private $base_url;

    public function __construct($base_url = '', $storage = null)
    {
        $this->base_url = (string) $base_url;
        $this->storage = $storage instanceof ERP_OMD_KSeF_Auth_Storage ? $storage : new ERP_OMD_KSeF_Auth_Storage();
    }

    public function get_challenge($environment) { return new WP_Error('erp_omd_ksef_local_not_supported', 'Local connector provider does not expose get_challenge.'); }
    public function authenticate_with_ksef_token($environment, $ksef_token, $context_identifier) { return new WP_Error('erp_omd_ksef_local_not_supported', 'Local connector provider does not expose authenticate_with_ksef_token.'); }
    public function get_auth_status($environment, $reference_number, $authentication_token) { return new WP_Error('erp_omd_ksef_local_not_supported', 'Local connector provider does not expose get_auth_status.'); }
    public function redeem_token($environment, $authentication_token) { return new WP_Error('erp_omd_ksef_local_not_supported', 'Local connector provider does not expose redeem_token.'); }
    public function refresh_access_token($environment, $refresh_token) { return new WP_Error('erp_omd_ksef_local_not_supported', 'Local connector provider does not expose refresh_access_token.'); }

    public function ensure_access_token($environment, $ksef_token, $context_identifier)
    {
        if (! class_exists('KSeF_Auth')) {
            return new WP_Error('erp_omd_ksef_local_connector_missing', __('Nie wykryto lokalnego konektora KSeF (klasa KSeF_Auth).', 'erp-omd'));
        }

        try {
            $auth = new KSeF_Auth($this->base_url !== '' ? $this->base_url : null);
            $result = (array) $auth->authenticate((string) $context_identifier, (string) $ksef_token);
        } catch (Exception $e) {
            return new WP_Error('erp_omd_ksef_local_connector_failed', (string) $e->getMessage());
        }

        $access = trim((string) ($result['access_token'] ?? $result['accessToken'] ?? ''));
        $refresh = trim((string) ($result['refresh_token'] ?? $result['refreshToken'] ?? ''));
        if ($access === '') {
            return new WP_Error('erp_omd_ksef_local_connector_invalid', __('Lokalny konektor nie zwrócił access tokenu.', 'erp-omd'));
        }

        $this->storage->save_tokens((string) $environment, [
            'token_type' => 'Bearer',
            'access_token' => $access,
            'refresh_token' => $refresh,
            'access_expires_at' => gmdate('c', time() + 3600),
            'refresh_expires_at' => gmdate('c', time() + 86400),
        ]);

        return [
            'ok' => true,
            'source' => 'local_connector',
            'access_token' => $access,
            'refresh_token' => $refresh,
        ];
    }
}
