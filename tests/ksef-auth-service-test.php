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

class ERP_OMD_KSeF_Connector_Redeem_Single_Use_Fake extends ERP_OMD_KSeF_Connector_Fake
{
    /** @var int */
    public $redeem_calls = 0;

    public function request($method, $path, array $headers = [], $body = null)
    {
        if (strtoupper((string) $method) === 'POST' && $path === '/auth/token/redeem') {
            $this->redeem_calls++;
            return ['code' => 401, 'json' => ['description' => 'Wymagane jest uwierzytelnienie.']];
        }

        return parent::request($method, $path, $headers, $body);
    }
}

class ERP_OMD_KSeF_Connector_Auth_Content_Type_Fallback_Fake extends ERP_OMD_KSeF_Connector_Fake
{
    public function request($method, $path, array $headers = [], $body = null)
    {
        $this->requests[] = ['method' => strtoupper((string) $method), 'path' => $path, 'headers' => $headers, 'body' => $body];

        if (strtoupper((string) $method) === 'POST' && $path === '/auth/ksef-token') {
            $content_type = strtolower(trim((string) ($headers['Content-Type'] ?? '')));
            if ($content_type === 'application/xml') {
                return ['code' => 415, 'json' => ['description' => 'Unsupported Media Type']];
            }
        }

        $method = strtoupper((string) $method);
        $key = $method . ' ' . $path;
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
$connector->responses['POST /auth/ksef-token'] = ['code' => 200, 'json' => ['authenticationToken' => ['token' => 'AUTH-1'], 'referenceNumber' => 'REF-1', 'status' => 'completed']];
$connector->responses['GET /auth/REF-1'] = ['code' => 200, 'json' => ['authenticationToken' => ['token' => 'AUTH-1'], 'status' => 'completed']];
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

$redeemRequest = null;
foreach ($connector->requests as $request) {
    if (($request['method'] ?? '') === 'POST' && ($request['path'] ?? '') === '/auth/token/redeem') {
        $redeemRequest = $request;
        break;
    }
}
$redeemHeaders = is_array($redeemRequest['headers'] ?? null) ? (array) $redeemRequest['headers'] : [];
$hasContentType = false;
foreach ($redeemHeaders as $headerName => $headerValue) {
    if (strtolower((string) $headerName) === 'content-type') {
        $hasContentType = true;
        break;
    }
}
$redeemBodyIsNull = is_array($redeemRequest) && array_key_exists('body', $redeemRequest) ? $redeemRequest['body'] === null : false;
$redeemAuthenticationTokenHeader = trim((string) ($redeemHeaders['AuthenticationToken'] ?? ''));
$assertions++;
if (! is_array($redeemRequest) || $hasContentType || ! $redeemBodyIsNull || $redeemAuthenticationTokenHeader === '') {
    throw new RuntimeException('Expected redeem request to carry bearer + AuthenticationToken headers and no request body/content-type.');
}

$authRequestBody = (string) ($connector->requests[1]['body'] ?? '');
$assertions++;
if (strpos($authRequestBody, '<ContextIdentifier Type="Nip">1111111111</ContextIdentifier>') === false) {
    throw new RuntimeException('Expected auth request XML to contain normalized ContextIdentifier for KSeF API.');
}

$connectorTypeAlias = new ERP_OMD_KSeF_Connector_Fake();
$connectorTypeAlias->responses['POST /auth/challenge'] = ['code' => 200, 'json' => ['challenge' => 'CHALLENGE-TYPE']];
$connectorTypeAlias->responses['POST /auth/ksef-token'] = ['code' => 400, 'json' => ['description' => 'invalid request']];
$storageTypeAlias = new ERP_OMD_KSeF_Auth_Storage();
$serviceTypeAlias = new ERP_OMD_KSeF_Auth_Service($connectorTypeAlias, $storageTypeAlias, $publicKeyService);
$serviceTypeAlias->authenticate_with_ksef_token('TEST', 'KSEF-TOKEN-TYPE', 'NIP:1111111111');
$aliasRequestBody = (string) ($connectorTypeAlias->requests[1]['body'] ?? '');
$assertions++;
if (strpos($aliasRequestBody, 'Type="Nip"') === false) {
    throw new RuntimeException('Expected contextIdentifier aliases such as NIP to be normalized to Nip in auth XML.');
}

$connectorAsync = new ERP_OMD_KSeF_Connector_Fake();
$connectorAsync->responses['POST /auth/challenge'] = ['code' => 200, 'json' => ['challenge' => 'CHALLENGE-2']];
$connectorAsync->responses['POST /auth/ksef-token'] = ['code' => 202, 'json' => ['referenceNumber' => 'REF-ASYNC-1', 'authenticationToken' => ['token' => 'AUTH-ASYNC-1']]];
$connectorAsync->responses['POST /auth/token/redeem'] = ['code' => 200, 'json' => [
    'accessToken' => 'ACCESS-ASYNC-1',
    'refreshToken' => 'REFRESH-ASYNC-1',
    'accessTokenExpiresIn' => 120,
    'refreshTokenExpiresIn' => 3600,
]];

$storageAsync = new ERP_OMD_KSeF_Auth_Storage();
$storageAsync->clear_tokens('TEST');
$asyncStatusCalls = 0;
$asyncStatusAuthorizationHeaders = [];
$pollSleeps = [];
$connectorAsync->responses['GET /auth/REF-ASYNC-1'] = [
    'code' => 200,
    'json' => [],
];
$serviceAsync = new ERP_OMD_KSeF_Auth_Service(
    new class($connectorAsync, $asyncStatusCalls, $asyncStatusAuthorizationHeaders) {
        /** @var ERP_OMD_KSeF_Connector_Fake */
        private $inner;

        /** @var int */
        private $status_calls;

        /** @var array<int,string> */
        private $status_authorization_headers;

        public function __construct($inner, &$status_calls, &$status_authorization_headers)
        {
            $this->inner = $inner;
            $this->status_calls = &$status_calls;
            $this->status_authorization_headers = &$status_authorization_headers;
        }

        public function request($method, $path, array $headers = [], $body = null)
        {
            if ($method === 'GET' && $path === '/auth/REF-ASYNC-1') {
                $this->status_calls++;
                $this->status_authorization_headers[] = (string) ($headers['Authorization'] ?? '');
                if ($this->status_calls < 2) {
                    return ['code' => 200, 'json' => []];
                }

                return ['code' => 200, 'json' => ['authenticationToken' => ['token' => 'AUTH-ASYNC-1'], 'status' => 'completed']];
            }

            return $this->inner->request($method, $path, $headers, $body);
        }
    },
    $storageAsync,
    $publicKeyService,
    [2, 5],
    static function ($seconds) use (&$pollSleeps) {
        $pollSleeps[] = (int) $seconds;
    }
);
$asyncResult = $serviceAsync->ensure_access_token('TEST', 'KSEF-TOKEN-2', '1111111111');
$assertions++;
if ($asyncResult instanceof WP_Error || ($asyncResult['ok'] ?? false) !== true || ($asyncResult['source'] ?? '') !== 'reauth') {
    throw new RuntimeException('Expected async auth status fallback to resolve authenticationToken and redeem tokens.');
}
$assertions++;
if ($asyncStatusCalls !== 2) {
    throw new RuntimeException('Expected auth status polling backoff flow to retry until token is available.');
}
$assertions++;
if ($asyncStatusAuthorizationHeaders !== ['Bearer AUTH-ASYNC-1', 'Bearer AUTH-ASYNC-1']) {
    throw new RuntimeException('Expected auth status polling to pass current authenticationToken as Bearer authorization header.');
}
$assertions++;
if ($pollSleeps !== [2, 5]) {
    throw new RuntimeException('Expected auth status polling to use configured backoff delays between attempts.');
}

$connectorError = new ERP_OMD_KSeF_Connector_Fake();
$connectorError->responses['POST /auth/challenge'] = ['code' => 200, 'json' => ['challenge' => 'CHALLENGE-ERR']];
$connectorError->responses['POST /auth/ksef-token'] = ['code' => 403, 'json' => [
    'reasonCode' => 'context-type-not-allowed',
    'detail' => 'Operacja nie jest dostępna dla uwierzytelnionego typu kontekstu.',
]];
$storageError = new ERP_OMD_KSeF_Auth_Storage();
$storageError->clear_tokens('TEST');
$serviceError = new ERP_OMD_KSeF_Auth_Service($connectorError, $storageError, $publicKeyService);
$errorResult = $serviceError->ensure_access_token('TEST', 'KSEF-TOKEN-3', '123456789');
$assertions++;
if (! ($errorResult instanceof WP_Error) || (string) $errorResult->get_error_code() !== 'context-type-not-allowed') {
    throw new RuntimeException('Expected auth service to propagate KSeF API error details for non-2xx responses.');
}
$assertions++;
if (strpos((string) $errorResult->get_error_message(), 'endpoint: POST /auth/ksef-token') === false) {
    throw new RuntimeException('Expected auth error message to include endpoint details for API diagnostics.');
}
$assertions++;
if (strpos((string) $errorResult->get_error_message(), 'hint: ustaw ContextIdentifier jako Nip:XXXXXXXXXX') === false) {
    throw new RuntimeException('Expected auth diagnostics hint for likely invalid InternalId context format.');
}

$connectorRedeemSingleUse = new ERP_OMD_KSeF_Connector_Redeem_Single_Use_Fake();
$connectorRedeemSingleUse->responses['POST /auth/challenge'] = ['code' => 200, 'json' => ['challenge' => 'CHALLENGE-REDEEM-SINGLE']];
$connectorRedeemSingleUse->responses['POST /auth/ksef-token'] = ['code' => 200, 'json' => ['authenticationToken' => ['token' => 'AUTH-REDEEM-SINGLE'], 'status' => 'completed']];
$storageRedeemSingleUse = new ERP_OMD_KSeF_Auth_Storage();
$storageRedeemSingleUse->clear_tokens('TEST');
$serviceRedeemSingleUse = new ERP_OMD_KSeF_Auth_Service($connectorRedeemSingleUse, $storageRedeemSingleUse, $publicKeyService);
$singleUseResult = $serviceRedeemSingleUse->ensure_access_token('TEST', 'KSEF-TOKEN-4', '1111111111');
$assertions++;
if (! ($singleUseResult instanceof WP_Error) || (string) $singleUseResult->get_error_code() !== 'ksef_http_401') {
    throw new RuntimeException('Expected redeem single-use flow to propagate 401 without multi-attempt retries.');
}
$assertions++;
if ($connectorRedeemSingleUse->redeem_calls !== 1) {
    throw new RuntimeException('Expected redeem endpoint to be called exactly once (single-use token).');
}

$connectorObjectTokens = new ERP_OMD_KSeF_Connector_Fake();
$connectorObjectTokens->responses['POST /auth/challenge'] = ['code' => 200, 'json' => ['challenge' => 'CHALLENGE-OBJECT']];
$connectorObjectTokens->responses['POST /auth/ksef-token'] = ['code' => 200, 'json' => ['authenticationToken' => ['token' => 'AUTH-OBJECT'], 'status' => 'completed']];
$connectorObjectTokens->responses['POST /auth/token/redeem'] = ['code' => 200, 'json' => [
    'accessToken' => ['token' => 'ACCESS-OBJECT', 'validUntil' => '2030-01-01T12:00:00+00:00'],
    'refreshToken' => ['token' => 'REFRESH-OBJECT', 'validUntil' => '2030-01-02T12:00:00+00:00'],
]];
$storageObjectTokens = new ERP_OMD_KSeF_Auth_Storage();
$storageObjectTokens->clear_tokens('TEST');
$serviceObjectTokens = new ERP_OMD_KSeF_Auth_Service($connectorObjectTokens, $storageObjectTokens, $publicKeyService);
$objectResult = $serviceObjectTokens->ensure_access_token('TEST', 'KSEF-TOKEN-OBJECT', '1111111111');
$assertions++;
if ($objectResult instanceof WP_Error || ($objectResult['ok'] ?? false) !== true) {
    throw new RuntimeException('Expected auth service to accept object-based access/refresh token payloads from redeem response.');
}
$storedObjectTokens = $storageObjectTokens->get_tokens('TEST');
$assertions++;
if (($storedObjectTokens['access_token'] ?? '') !== 'ACCESS-OBJECT' || ($storedObjectTokens['refresh_token'] ?? '') !== 'REFRESH-OBJECT') {
    throw new RuntimeException('Expected object-based token payload to be persisted as plain token strings.');
}

$connectorContentTypeFallback = new ERP_OMD_KSeF_Connector_Auth_Content_Type_Fallback_Fake();
$connectorContentTypeFallback->responses['POST /auth/challenge'] = ['code' => 200, 'json' => ['challenge' => 'CHALLENGE-415']];
$connectorContentTypeFallback->responses['POST /auth/ksef-token'] = ['code' => 200, 'json' => ['authenticationToken' => ['token' => 'AUTH-415'], 'status' => 'completed']];
$connectorContentTypeFallback->responses['POST /auth/token/redeem'] = ['code' => 200, 'json' => [
    'accessToken' => 'ACCESS-415',
    'refreshToken' => 'REFRESH-415',
    'accessTokenExpiresIn' => 120,
    'refreshTokenExpiresIn' => 3600,
]];
$storageContentTypeFallback = new ERP_OMD_KSeF_Auth_Storage();
$storageContentTypeFallback->clear_tokens('TEST');
$serviceContentTypeFallback = new ERP_OMD_KSeF_Auth_Service($connectorContentTypeFallback, $storageContentTypeFallback, $publicKeyService);
$contentTypeResult = $serviceContentTypeFallback->ensure_access_token('TEST', 'KSEF-TOKEN-415', '1111111111');
$assertions++;
if ($contentTypeResult instanceof WP_Error || ($contentTypeResult['ok'] ?? false) !== true) {
    throw new RuntimeException('Expected auth flow to retry /auth/ksef-token with JSON body after 415 for XML.');
}
$authKsefTokenRequests = array_values(array_filter($connectorContentTypeFallback->requests, static function ($request) {
    return (($request['method'] ?? '') === 'POST') && (($request['path'] ?? '') === '/auth/ksef-token');
}));
$assertions++;
if (count($authKsefTokenRequests) !== 2) {
    throw new RuntimeException('Expected exactly two /auth/ksef-token attempts for content-type fallback.');
}
$firstAttemptContentType = strtolower((string) (($authKsefTokenRequests[0]['headers']['Content-Type'] ?? '')));
$secondAttemptContentType = strtolower((string) (($authKsefTokenRequests[1]['headers']['Content-Type'] ?? '')));
$secondAttemptBody = $authKsefTokenRequests[1]['body'] ?? null;
$assertions++;
if (
    $firstAttemptContentType !== 'application/xml'
    || $secondAttemptContentType !== 'application/json'
    || ! is_array($secondAttemptBody)
    || (string) ($secondAttemptBody['contextIdentifier']['type'] ?? '') !== 'Nip'
) {
    throw new RuntimeException('Expected /auth/ksef-token fallback sequence: XML first, then JSON payload with contextIdentifier.');
}

echo "OK ({$assertions} assertions)\n";
