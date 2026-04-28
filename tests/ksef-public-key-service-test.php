<?php

declare(strict_types=1);

require_once __DIR__ . '/../erp-omd/includes/services/class-ksef-public-key-service.php';

if (! class_exists('WP_Error')) {
    class WP_Error
    {
        private $code;
        private $message;

        public function __construct($code = '', $message = '')
        {
            $this->code = $code;
            $this->message = $message;
        }

        public function get_error_code()
        {
            return $this->code;
        }

        public function get_error_message()
        {
            return $this->message;
        }
    }
}

if (! function_exists('__')) {
    function __($text)
    {
        return $text;
    }
}

$assertions = 0;

$keyResource = openssl_pkey_new([
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
    'private_key_bits' => 2048,
]);
if (! $keyResource) {
    throw new RuntimeException('Unable to generate RSA key pair for public-key service test.');
}

openssl_pkey_export($keyResource, $privateKeyPem);
$details = openssl_pkey_get_details($keyResource);
$publicKeyPem = (string) ($details['key'] ?? '');

$service = new ERP_OMD_KSeF_Public_Key_Service();
$result = $service->encrypt_ksef_token_payload('TOKEN-ABC', $publicKeyPem, 1700000000000);
$assertions++;
if ($result instanceof WP_Error || ($result['ok'] ?? false) !== true || trim((string) ($result['encrypted_token'] ?? '')) === '') {
    throw new RuntimeException('Expected public-key service to produce encrypted token payload.');
}

$encrypted = base64_decode((string) $result['encrypted_token'], true);
if (! is_string($encrypted) || $encrypted === '') {
    throw new RuntimeException('Encrypted token should be valid base64.');
}

$em = '';
$decryptedOk = openssl_private_decrypt($encrypted, $em, $privateKeyPem, OPENSSL_NO_PADDING);
if (! $decryptedOk || $em === '') {
    throw new RuntimeException('Unable to decrypt OAEP payload with private key.');
}

$oaepDecodeSha256 = static function (string $encoded): string {
    $k = strlen($encoded);
    $hLen = 32;
    if ($k < (2 * $hLen + 2) || $encoded[0] !== "\x00") {
        throw new RuntimeException('Invalid OAEP encoded payload.');
    }

    $maskedSeed = substr($encoded, 1, $hLen);
    $maskedDb = substr($encoded, 1 + $hLen);
    $mgf1 = static function (string $seed, int $length): string {
        $mask = '';
        $counter = 0;
        while (strlen($mask) < $length) {
            $mask .= hash('sha256', $seed . pack('N', $counter), true);
            $counter++;
        }

        return substr($mask, 0, $length);
    };
    $binXor = static function (string $left, string $right): string {
        $out = '';
        $len = min(strlen($left), strlen($right));
        for ($i = 0; $i < $len; $i++) {
            $out .= $left[$i] ^ $right[$i];
        }

        return $out;
    };

    $seedMask = $mgf1($maskedDb, $hLen);
    $seed = $binXor($maskedSeed, $seedMask);
    $dbMask = $mgf1($seed, $k - $hLen - 1);
    $db = $binXor($maskedDb, $dbMask);

    $lHash = hash('sha256', '', true);
    if (substr($db, 0, $hLen) !== $lHash) {
        throw new RuntimeException('OAEP label hash mismatch (expected SHA-256).');
    }

    $rest = substr($db, $hLen);
    $separatorPos = strpos($rest, "\x01");
    if ($separatorPos === false) {
        throw new RuntimeException('OAEP separator not found.');
    }

    return substr($rest, $separatorPos + 1);
};

$decodedMessage = $oaepDecodeSha256($em);
$assertions++;
if ($decodedMessage !== 'TOKEN-ABC|1700000000000') {
    throw new RuntimeException('Expected decoded OAEP payload to match token|timestamp plaintext.');
}

echo "Assertions: {$assertions}\n";
echo "KSeF public key service test passed.\n";

