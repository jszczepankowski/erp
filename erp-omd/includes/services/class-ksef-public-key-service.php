<?php

class ERP_OMD_KSeF_Public_Key_Service
{
    const OPTION_PREFIX = 'erp_omd_ksef_public_key_';

    /** @var callable|null */
    private $key_provider;

    /**
     * @param callable|null $key_provider
     */
    public function __construct($key_provider = null)
    {
        $this->key_provider = is_callable($key_provider) ? $key_provider : null;
    }

    /**
     * @param string $environment
     * @return string|WP_Error
     */
    public function get_encryption_public_key($environment)
    {
        $environment = $this->normalize_environment($environment);

        if ($this->key_provider !== null) {
            $result = call_user_func($this->key_provider, $environment);
            if ($result instanceof WP_Error) {
                return $result;
            }

            if (is_string($result) && trim($result) !== '') {
                return $result;
            }
        }

        $key = (string) get_option(self::OPTION_PREFIX . strtolower($environment), '');
        if ($key === '') {
            return new WP_Error('erp_omd_ksef_public_key_missing', __('Brak klucza publicznego KSeF dla wskazanego środowiska.', 'erp-omd'));
        }

        return $key;
    }

    /**
     * @param string $ksef_token
     * @param string $public_key
     * @param int|null $timestamp_ms
     * @return array<string,mixed>|WP_Error
     */
    public function encrypt_ksef_token_payload($ksef_token, $public_key, $timestamp_ms = null)
    {
        $token = trim((string) $ksef_token);
        if ($token === '') {
            return new WP_Error('erp_omd_ksef_token_empty', __('Token KSeF nie może być pusty.', 'erp-omd'));
        }

        $timestamp_ms = $timestamp_ms !== null ? (int) $timestamp_ms : (int) round(microtime(true) * 1000);
        $plain = $token . '|' . $timestamp_ms;

        $encrypted = '';
        $ok = openssl_public_encrypt($plain, $encrypted, $public_key, OPENSSL_PKCS1_OAEP_PADDING);
        if (! $ok || $encrypted === '') {
            return new WP_Error('erp_omd_ksef_token_encrypt_failed', __('Nie udało się zaszyfrować tokenu KSeF.', 'erp-omd'));
        }

        return [
            'ok' => true,
            'timestamp_ms' => $timestamp_ms,
            'plain' => $plain,
            'encrypted_token' => base64_encode($encrypted),
        ];
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
