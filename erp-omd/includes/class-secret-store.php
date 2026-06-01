<?php

class ERP_OMD_Secret_Store
{
    const FORMAT_PREFIX = 'v1:';

    public static function encrypt($raw_value)
    {
        $raw_value = (string) $raw_value;
        if ($raw_value === '' || ! function_exists('openssl_encrypt') || ! function_exists('random_bytes')) {
            return $raw_value;
        }

        $key = self::encryption_key();
        $iv = random_bytes(12);
        $tag = '';
        $encrypted = openssl_encrypt($raw_value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if (! is_string($encrypted) || $encrypted === '' || ! is_string($tag) || $tag === '') {
            return self::encrypt_legacy($raw_value);
        }

        return self::FORMAT_PREFIX . base64_encode($iv) . ':' . base64_encode($tag) . ':' . base64_encode($encrypted);
    }

    public static function decrypt($encrypted)
    {
        $encrypted = (string) $encrypted;
        if ($encrypted === '' || ! function_exists('openssl_decrypt')) {
            return $encrypted;
        }

        if (strpos($encrypted, self::FORMAT_PREFIX) === 0) {
            $decrypted = self::decrypt_v1($encrypted);
            return $decrypted === null ? $encrypted : $decrypted;
        }

        return self::decrypt_legacy($encrypted);
    }

    private static function decrypt_v1($encrypted)
    {
        $payload = substr((string) $encrypted, strlen(self::FORMAT_PREFIX));
        $parts = explode(':', $payload);
        if (count($parts) !== 3) {
            return null;
        }

        [$iv_encoded, $tag_encoded, $ciphertext_encoded] = $parts;
        $iv = base64_decode($iv_encoded, true);
        $tag = base64_decode($tag_encoded, true);
        $ciphertext = base64_decode($ciphertext_encoded, true);
        if (! is_string($iv) || ! is_string($tag) || ! is_string($ciphertext) || $iv === '' || $tag === '' || $ciphertext === '') {
            return null;
        }

        $decrypted = openssl_decrypt($ciphertext, 'aes-256-gcm', self::encryption_key(), OPENSSL_RAW_DATA, $iv, $tag);
        return is_string($decrypted) ? $decrypted : null;
    }

    private static function encrypt_legacy($raw_value)
    {
        if (! function_exists('openssl_encrypt')) {
            return (string) $raw_value;
        }

        $key = self::encryption_key();
        $iv = self::legacy_iv();
        $encrypted = openssl_encrypt((string) $raw_value, 'AES-256-CBC', $key, 0, $iv);

        return is_string($encrypted) ? $encrypted : (string) $raw_value;
    }

    private static function decrypt_legacy($encrypted)
    {
        $key = self::encryption_key();
        $iv = self::legacy_iv();
        $decrypted = openssl_decrypt((string) $encrypted, 'AES-256-CBC', $key, 0, $iv);

        return is_string($decrypted) ? $decrypted : (string) $encrypted;
    }

    private static function encryption_key()
    {
        return hash('sha256', (string) wp_salt('auth'), true);
    }

    private static function legacy_iv()
    {
        return substr(hash('sha256', (string) wp_salt('secure_auth')), 0, 16);
    }
}
