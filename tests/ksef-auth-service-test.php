<?php

declare(strict_types=1);

require_once __DIR__ . '/../erp-omd/includes/contracts/interface-ksef-auth-provider.php';
require_once __DIR__ . '/../erp-omd/includes/services/class-ksef-auth-storage.php';
require_once __DIR__ . '/../erp-omd/includes/services/class-ksef-public-key-service.php';
require_once __DIR__ . '/../erp-omd/includes/services/class-ksef-auth-service.php';

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

if (! function_exists('get_option')) {
    function get_option($key, $default = false)
    {
        return $GLOBALS['erp_omd_auth_service_options'][$key] ?? $default;
    }
}

if (! function_exists('update_option')) {
    function update_option($key, $value)
    {
        $GLOBALS['erp_omd_auth_service_options'][$key] = $value;
        return true;
    }
}


class ERP_OMD_KSeF_Public_Key_Service_Fake extends ERP_OMD_KSeF_Public_Key_Service
{
    public function __construct()
    {
        parent::__construct(function () {
            return 'FAKE-KEY';
        });
    }

    public function encrypt_ksef_token_payload($ksef_token, $public_key, $timestamp_ms = null)
    {
        return [
            'ok' => true,
            'timestamp_ms' => $timestamp_ms !== null ? (int) $timestamp_ms : 1700000000000,
            'plain' => (string) $ksef_token . '|1700000000000',
            'encrypted_token' => base64_encode('encrypted:' . (string) $ksef_token),
        ];
    }
}

class ERP_OMD_KSeF_Connector_Fake
{
    /** @var array<string,array<string,mixed>> */
    public $responses = [];

    /** @var array<int,array<string,mixed>> */
    public $requests = [];

    public function request($method, $path, array $headers = [], $body = null)
    {
        $method = strtoupper((string) $method);
        $key = $method . ' ' . $path;
        $this->requests[] = ['method' => $method, 'path' => $path, 'headers' => $headers, 'body' => $body];

        if (isset($this->responses[$key])) {
            return $this->responses[$key];
        }

        return new WP_Error('missing_response', 'Missing fake response for ' . $key);
    }
}

$GLOBALS['erp_omd_auth_service_options'] = [];
$assertions = 0;

$connector = new ERP_OMD_KSeF_Connector_Fake();
$connector->responses['POST /auth/challenge'] = ['code' => 200, 'json' => ['challenge' => 'CHALLENGE-1']];
$connector->responses['POST /auth/ksef-token'] = ['code' => 200, 'json' => ['authenticationToken' => ['token' => 'AUTH-1'], 'referenceNumber' => 'REF-1']];
$connector->responses['POST /auth/token/redeem'] = ['code' => 200, 'json' => [
    'accessToken' => 'ACCESS-1',
    'refreshToken' => 'REFRESH-1',
    'accessTokenExpiresIn' => 120,
    'refreshTokenExpiresIn' => 3600,
]];
$connector->responses['POST /auth/token/refresh'] = ['code' => 200, 'json' => [
    'accessToken' => 'ACCESS-2',
    'refreshToken' => 'REFRESH-2',
    'accessTokenExpiresIn' => 120,
    'refreshTokenExpiresIn' => 3600,
]];

$storage = new ERP_OMD_KSeF_Auth_Storage();

$publicKeyService = new ERP_OMD_KSeF_Public_Key_Service_Fake();

$service = new ERP_OMD_KSeF_Auth_Service($connector, $storage, $publicKeyService);

$result = $service->ensure_access_token('TEST', 'KSEF-TOKEN-1', 'PLNIP-1111111111');
$assertions++;
if ($result instanceof WP_Error || ($result['ok'] ?? false) !== true || ($result['source'] ?? '') !== 'reauth') {
    throw new RuntimeException('Expected first ensure_access_token() call to use full auth flow.');
}

$stored = $storage->get_tokens('TEST');
$assertions++;
if (($stored['access_token'] ?? '') !== 'ACCESS-1' || ($stored['refresh_token'] ?? '') !== 'REFRESH-1') {
    throw new RuntimeException('Expected redeem_token() to persist access/refresh tokens.');
}

$cacheResult = $service->ensure_access_token('TEST', 'KSEF-TOKEN-1', 'PLNIP-1111111111');
$assertions++;
if (($cacheResult['source'] ?? '') !== 'cache' || ($cacheResult['access_token'] ?? '') !== 'ACCESS-1') {
    throw new RuntimeException('Expected cached token to be returned when not expired.');
}

$storage->save_tokens('TEST', [
    'access_token' => 'ACCESS-OLD',
    'refresh_token' => 'REFRESH-1',
    'access_expires_at' => gmdate('Y-m-d H:i:s', time() - 60),
    'refresh_expires_at' => gmdate('Y-m-d H:i:s', time() + 3600),
]);

$refreshResult = $service->ensure_access_token('TEST', 'KSEF-TOKEN-1', 'PLNIP-1111111111');
$assertions++;
if (($refreshResult['source'] ?? '') !== 'refresh' || ($refreshResult['access_token'] ?? '') !== 'ACCESS-2') {
    throw new RuntimeException('Expected refresh flow to update access token after expiry.');
}

$assertions++;
if (count($connector->requests) < 3) {
    throw new RuntimeException('Expected connector requests to include challenge/auth/redeem/refresh calls.');
}

$authRequestBody = (array) ($connector->requests[1]['body'] ?? []);
$assertions++;
if (($authRequestBody['contextIdentifier']['type'] ?? '') !== 'Nip' || ($authRequestBody['contextIdentifier']['value'] ?? '') !== '1111111111') {
    throw new RuntimeException('Expected contextIdentifier payload to be normalized to object format required by KSeF API.');
}

$connectorAsync = new ERP_OMD_KSeF_Connector_Fake();
$connectorAsync->responses['POST /auth/challenge'] = ['code' => 200, 'json' => ['challenge' => 'CHALLENGE-2']];
$connectorAsync->responses['POST /auth/ksef-token'] = ['code' => 202, 'json' => ['referenceNumber' => 'REF-ASYNC-1']];
$connectorAsync->responses['GET /auth/REF-ASYNC-1'] = ['code' => 200, 'json' => ['authenticationToken' => ['token' => 'AUTH-ASYNC-1']]];
$connectorAsync->responses['POST /auth/token/redeem'] = ['code' => 200, 'json' => [
    'accessToken' => 'ACCESS-ASYNC-1',
    'refreshToken' => 'REFRESH-ASYNC-1',
    'accessTokenExpiresIn' => 120,
    'refreshTokenExpiresIn' => 3600,
]];

$storageAsync = new ERP_OMD_KSeF_Auth_Storage();
$storageAsync->clear_tokens('TEST');
$serviceAsync = new ERP_OMD_KSeF_Auth_Service($connectorAsync, $storageAsync, $publicKeyService);
$asyncResult = $serviceAsync->ensure_access_token('TEST', 'KSEF-TOKEN-2', '1111111111');
$assertions++;
if ($asyncResult instanceof WP_Error || ($asyncResult['ok'] ?? false) !== true || ($asyncResult['source'] ?? '') !== 'reauth') {
    throw new RuntimeException('Expected async auth status fallback to resolve authenticationToken and redeem tokens.');
}

echo "OK ({$assertions} assertions)\n";
