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

    public function __construct(array $results)
    {
        parent::__construct(120, [11, 22, 33]);
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

echo "Assertions: {$assertions}\n";
echo "KSeF incremental sync stage-2 test passed.\n";
