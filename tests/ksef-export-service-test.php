<?php

declare(strict_types=1);

require_once __DIR__ . '/../erp-omd/includes/services/class-ksef-export-service.php';

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

class ERP_OMD_KSeF_Connector_Export_Fake
{
    /** @var array<string,mixed> */
    public $responses = [];

    public function request($method, $path, array $headers = [], $body = null)
    {
        $key = strtoupper((string) $method) . ' ' . (string) $path;
        if (! array_key_exists($key, $this->responses)) {
            return new WP_Error('missing_response', 'Missing response for ' . $key);
        }

        $response = $this->responses[$key];
        if (is_callable($response)) {
            return $response($headers, $body);
        }

        return $response;
    }
}

$assertions = 0;

$connector = new ERP_OMD_KSeF_Connector_Export_Fake();
$connector->responses['POST /invoices/exports'] = ['code' => 200, 'json' => ['referenceNumber' => 'REF-1']];
$connector->responses['GET /invoices/exports/REF-1'] = ['code' => 200, 'json' => [
    'status' => 'completed',
    'isTruncated' => true,
    'lastPermanentStorageDate' => '2026-04-01T10:00:00Z',
    'permanentStorageHwmDate' => '2026-04-01T11:00:00Z',
    'parts' => [1, 2],
]];

$service = new ERP_OMD_KSeF_Export_Service($connector, 2);
$result = $service->run_incremental_export('TEST', 'subject1', '2026-04-01T09:00:00Z', '2026-04-01T12:00:00Z');
$assertions++;
if (($result['ok'] ?? false) !== true || ($result['next_hwm'] ?? '') !== '2026-04-01T10:00:00Z') {
    throw new RuntimeException('Expected truncated export to advance HWM to lastPermanentStorageDate.');
}

$connector2 = new ERP_OMD_KSeF_Connector_Export_Fake();
$connector2->responses['POST /invoices/exports'] = ['code' => 200, 'json' => ['referenceNumber' => 'REF-2']];
$connector2->responses['GET /invoices/exports/REF-2'] = ['code' => 200, 'json' => [
    'status' => 'completed',
    'isTruncated' => false,
    'lastPermanentStorageDate' => '2026-04-02T10:00:00Z',
    'permanentStorageHwmDate' => '2026-04-02T11:00:00Z',
    'parts' => [],
]];

$service2 = new ERP_OMD_KSeF_Export_Service($connector2, 2);
$result2 = $service2->run_incremental_export('TEST', 'subject1', '2026-04-02T09:00:00Z', '2026-04-02T12:00:00Z');
$assertions++;
if (($result2['ok'] ?? false) !== true || ($result2['next_hwm'] ?? '') !== '2026-04-02T11:00:00Z') {
    throw new RuntimeException('Expected full export to advance HWM to permanentStorageHwmDate.');
}

echo "Assertions: {$assertions}\n";
echo "KSeF export service stage-3 test passed.\n";
