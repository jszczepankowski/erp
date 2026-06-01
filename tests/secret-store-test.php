<?php

declare(strict_types=1);

if (! function_exists('wp_salt')) {
    function wp_salt($scheme = 'auth')
    {
        return 'test-salt-' . (string) $scheme;
    }
}

require_once __DIR__ . '/../erp-omd/includes/class-secret-store.php';

$secret = 'client-secret-value';
$encryptedA = ERP_OMD_Secret_Store::encrypt($secret);
$encryptedB = ERP_OMD_Secret_Store::encrypt($secret);

if ($encryptedA === $secret || $encryptedB === $secret) {
    throw new RuntimeException('Secret store should encrypt non-empty secrets when OpenSSL is available.');
}

if (function_exists('random_bytes') && $encryptedA === $encryptedB) {
    throw new RuntimeException('Secret store encryption should use a random nonce per value.');
}

if (ERP_OMD_Secret_Store::decrypt($encryptedA) !== $secret || ERP_OMD_Secret_Store::decrypt($encryptedB) !== $secret) {
    throw new RuntimeException('Secret store should decrypt v1 encrypted values.');
}

$key = hash('sha256', (string) wp_salt('auth'), true);
$iv = substr(hash('sha256', (string) wp_salt('secure_auth')), 0, 16);
$legacy = openssl_encrypt($secret, 'AES-256-CBC', $key, 0, $iv);
if (! is_string($legacy) || ERP_OMD_Secret_Store::decrypt($legacy) !== $secret) {
    throw new RuntimeException('Secret store should keep decrypting legacy CBC values.');
}

echo "Assertions: 5\n";
echo "Secret store test passed.\n";
