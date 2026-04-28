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

        $encoded = $this->oaep_sha256_encode($plain, $public_key);
        if ($encoded instanceof WP_Error) {
            return $encoded;
        }

        $encrypted = '';
        $ok = openssl_public_encrypt($encoded, $encrypted, $public_key, OPENSSL_NO_PADDING);
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

    /**
     * OAEP (SHA-256) encoding, then RSA no-padding encryption.
     *
     * @param string $message
     * @param string $public_key
     * @return string|WP_Error
     */
    private function oaep_sha256_encode($message, $public_key)
    {
        $details = openssl_pkey_get_details(openssl_pkey_get_public($public_key));
        $bits = (int) ($details['bits'] ?? 0);
        $k = (int) floor($bits / 8);
        if ($k <= 0) {
            return new WP_Error('erp_omd_ksef_public_key_invalid', __('Nieprawidłowy klucz publiczny KSeF.', 'erp-omd'));
        }

        $hash_length = 32; // SHA-256
        $message = (string) $message;
        if (strlen($message) > ($k - (2 * $hash_length) - 2)) {
            return new WP_Error('erp_omd_ksef_token_too_long', __('Token KSeF jest zbyt długi dla klucza publicznego.', 'erp-omd'));
        }

        $label_hash = hash('sha256', '', true);
        $ps = str_repeat("\x00", $k - strlen($message) - (2 * $hash_length) - 2);
        $db = $label_hash . $ps . "\x01" . $message;
        $seed = random_bytes($hash_length);

        $db_mask = $this->mgf1_sha256($seed, $k - $hash_length - 1);
        $masked_db = $this->binary_xor($db, $db_mask);
        $seed_mask = $this->mgf1_sha256($masked_db, $hash_length);
        $masked_seed = $this->binary_xor($seed, $seed_mask);

        return "\x00" . $masked_seed . $masked_db;
    }

    /**
     * @param string $seed
     * @param int $length
     * @return string
     */
    private function mgf1_sha256($seed, $length)
    {
        $mask = '';
        $counter = 0;
        while (strlen($mask) < $length) {
            $counter_bytes = pack('N', $counter);
            $mask .= hash('sha256', $seed . $counter_bytes, true);
            $counter++;
        }

        return substr($mask, 0, $length);
    }

    /**
     * @param string $left
     * @param string $right
     * @return string
     */
    private function binary_xor($left, $right)
    {
        $result = '';
        $length = min(strlen($left), strlen($right));
        for ($i = 0; $i < $length; $i++) {
            $result .= $left[$i] ^ $right[$i];
        }

        return $result;
    }
}
