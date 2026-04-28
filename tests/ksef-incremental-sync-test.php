<?php

declare(strict_types=1);

require_once __DIR__ . '/../erp-omd/includes/services/class-ksef-incremental-sync-service.php';

if (! function_exists('get_option')) {
    function get_option($key, $default = false)
    {
        return $GLOBALS['erp_omd_ksef_sync_options'][$key] ?? $default;
    }
}

if (! function_exists('update_option')) {
    function update_option($key, $value)
    {
        $GLOBALS['erp_omd_ksef_sync_options'][$key] = $value;
        return true;
    }
}


if (! function_exists('sanitize_key')) {
    function sanitize_key($key)
    {
        $key = strtolower((string) $key);
        return preg_replace('/[^a-z0-9_\-]/', '', $key) ?: '';
    }
}

if (! defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

if (! function_exists('wp_generate_password')) {
    function wp_generate_password($length = 12)
    {
        return substr(str_repeat('abc123', 4), 0, (int) $length);
    }
}

class ERP_OMD_KSeF_Incremental_Sync_Service_Fake extends ERP_OMD_KSeF_Incremental_Sync_Service
{
    /** @var array<int,array<string,mixed>> */
    private $results;

    /** @var int */
    private $idx = 0;

    public function __construct(array $results, $sleep_callback = null)
    {
        parent::__construct(120, [11, 22, 33], null, null, $sleep_callback, 60);
        $this->results = $results;
    }

    protected function perform_sync_iteration($environment)
    {
        $result = $this->results[$this->idx] ?? ['ok' => true];
        $this->idx++;
        return $result;
    }
}

$GLOBALS['erp_omd_ksef_sync_options'] = [];
$assertions = 0;

$service = new ERP_OMD_KSeF_Incremental_Sync_Service_Fake([
    ['ok' => false, 'http_code' => 429, 'retry_after' => 19, 'error_code' => 'rate_limited'],
    ['ok' => true],
]);

$run = $service->run_scheduled_sync('TEST', 3);
$assertions++;
if (($run['ok'] ?? false) !== true || ($run['status'] ?? '') !== 'synced' || (int) ($run['attempts'] ?? 0) !== 2) {
    throw new RuntimeException('Expected retryable first attempt and success on second attempt.');
}

$sleepCalls = [];
$serviceWithSleep = new ERP_OMD_KSeF_Incremental_Sync_Service_Fake([
    ['ok' => false, 'http_code' => 429, 'retry_after' => 19, 'error_code' => 'rate_limited'],
    ['ok' => true],
], static function ($seconds) use (&$sleepCalls) {
    $sleepCalls[] = (int) $seconds;
});
$serviceWithSleep->run_scheduled_sync('TEST', 3);
$assertions++;
if ($sleepCalls !== [19]) {
    throw new RuntimeException('Expected retry flow to pause using retry_after before next incremental sync attempt.');
}

$lockKey = ERP_OMD_KSeF_Incremental_Sync_Service::LOCK_OPTION_PREFIX . 'test';
$lockState = (array) ($GLOBALS['erp_omd_ksef_sync_options'][$lockKey] ?? []);
$assertions++;
if ((string) ($lockState['token'] ?? '') !== '' || (int) ($lockState['expires_at'] ?? -1) !== 0) {
    throw new RuntimeException('Expected lock to be released after run_scheduled_sync().');
}

$state = (array) ($GLOBALS['erp_omd_ksef_sync_options']['erp_omd_ksef_sync_state_test'] ?? []);
$assertions++;
if (($state['status'] ?? '') !== 'synced') {
    throw new RuntimeException('Expected sync state option to be updated with synced status.');
}

$lockedService = new ERP_OMD_KSeF_Incremental_Sync_Service_Fake([
    ['ok' => true],
]);

$GLOBALS['erp_omd_ksef_sync_options'][$lockKey] = [
    'token' => 'existing',
    'expires_at' => time() + 3600,
    'updated_at' => gmdate('Y-m-d H:i:s'),
];

$locked = $lockedService->run_scheduled_sync('TEST', 2);
$assertions++;
if (($locked['status'] ?? '') !== 'locked') {
    throw new RuntimeException('Expected locked status when an active lock already exists.');
}


class ERP_OMD_KSeF_Export_Service_Fake
{
    /** @var array<int,array<string,mixed>> */
    public $calls = [];

    public function run_incremental_export($environment, $subject_type, $from_hwm, $to_hwm)
    {
        $this->calls[] = [
            'environment' => $environment,
            'subject_type' => $subject_type,
            'from_hwm' => $from_hwm,
            'to_hwm' => $to_hwm,
        ];

        return [
            'ok' => true,
            'status' => 'completed',
            'next_hwm' => '2026-04-10T10:00:00Z',
            'documents' => [
                [
                    'invoice_number' => 'FV-' . strtoupper((string) $subject_type),
                    'ksef_reference_number' => 'REF-' . strtoupper((string) $subject_type),
                    'issue_date' => '2026-04-10',
                    'gross_amount' => 123.45,
                    'seller_nip' => '1111111111',
                    'buyer_nip' => '2222222222',
                ],
            ],
        ];
    }
}

class ERP_OMD_KSeF_Import_Service_Fake
{
    /** @var array<int,array<string,mixed>> */
    public $batches = [];

    public function import_documents(array $documents, $user_id)
    {
        $this->batches[] = ['documents' => $documents, 'user_id' => $user_id];
        return [
            'total' => count($documents),
            'imported' => count($documents),
            'failed' => 0,
            'duplicates' => 0,
            'conflicts' => 0,
            'errors' => [],
        ];
    }
}

$GLOBALS['erp_omd_ksef_sync_options'][$lockKey] = [
    'token' => '',
    'expires_at' => 0,
    'updated_at' => gmdate('Y-m-d H:i:s'),
];

$GLOBALS['erp_omd_ksef_sync_options']['erp_omd_ksef_sync_subject_types'] = ['seller', 'buyer'];
$exportFake = new ERP_OMD_KSeF_Export_Service_Fake();
$importFake = new ERP_OMD_KSeF_Import_Service_Fake();
$serviceWithExport = new ERP_OMD_KSeF_Incremental_Sync_Service(120, [11, 22, 33], $exportFake, $importFake);
$integrationRun = $serviceWithExport->run_scheduled_sync('TEST', 1);
$assertions++;
if (($integrationRun['ok'] ?? false) !== true || count($exportFake->calls) !== 2) {
    throw new RuntimeException('Expected stage-3 incremental sync to execute export service for each subject type.');
}

$assertions++;
if (($exportFake->calls[0]['subject_type'] ?? '') !== 'Subject1' || ($exportFake->calls[1]['subject_type'] ?? '') !== 'Subject2') {
    throw new RuntimeException('Expected aliases seller/buyer to be normalized to Subject1/Subject2 for API export.');
}

$assertions++;
if (($GLOBALS['erp_omd_ksef_sync_options']['erp_omd_ksef_sync_hwm_test_subject1'] ?? '') !== '2026-04-10T10:00:00Z') {
    throw new RuntimeException('Expected Subject1 HWM checkpoint to be updated from export result.');
}

$assertions++;
if (count($importFake->batches) !== 2 || (($importFake->batches[0]['documents'][0]['api_sync_source'] ?? '') !== 'ksef_sync_hub')) {
    throw new RuntimeException('Expected stage-4 bridge to map export documents and forward them to import service.');
}

$GLOBALS['erp_omd_ksef_sync_options']['erp_omd_ksef_sync_subject_types'] = ['subject1', 'subject2', 'subject3', 'subjectauthorized', 'subject1'];
$exportFakeLimited = new ERP_OMD_KSeF_Export_Service_Fake();
$importFakeLimited = new ERP_OMD_KSeF_Import_Service_Fake();
$serviceWithExportLimit = new ERP_OMD_KSeF_Incremental_Sync_Service(120, [11, 22, 33], $exportFakeLimited, $importFakeLimited);
$serviceWithExportLimit->run_scheduled_sync('TEST', 1);
$assertions++;
if (count($exportFakeLimited->calls) !== 4) {
    throw new RuntimeException('Expected incremental sync to cap exports per run to 4 subject types (<=20 exports/h with 15-min schedule).');
}

echo "Assertions: {$assertions}\n";
echo "KSeF incremental sync stage-3 test passed.\n";
