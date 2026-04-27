<?php

declare(strict_types=1);

$cron = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-cron-manager.php');
$autoloader = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-autoloader.php');
$installer = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-installer.php');
$incrementalServiceExists = file_exists(__DIR__ . '/../erp-omd/includes/services/class-ksef-incremental-sync-service.php');
$connectorExists = file_exists(__DIR__ . '/../erp-omd/includes/services/class-ksef-connector.php');

if ($cron === '' || $autoloader === '' || $installer === '') {
    throw new RuntimeException('Unable to load files for KSeF API sync stage-2 bootstrap test.');
}

$assertions = 0;

$assertions++;
if (! $incrementalServiceExists) {
    throw new RuntimeException('KSeF incremental sync service file should exist in stage 2.');
}

$assertions++;
if (! $connectorExists) {
    throw new RuntimeException('KSeF connector file should exist in stage 2.');
}

$presentFragments = [
    [$cron, 'KSEF_INCREMENTAL_SYNC_HOOK', 'Cron should contain KSeF incremental sync hook.'],
    [$cron, 'run_ksef_incremental_sync', 'Cron should contain KSeF incremental sync runner.'],
    [$autoloader, 'ERP_OMD_KSeF_Incremental_Sync_Service', 'Autoloader should register incremental sync service.'],
    [$installer, 'erp_omd_ksef_sync_state', 'Installer should create KSeF sync state table.'],
    [$installer, 'env_company_subject', 'KSeF sync state table should enforce env/company/subject uniqueness.'],
    [$cron, "get_option('erp_omd_ksef_sync_hub_env', 'TEST')", 'Cron should resolve KSeF Sync Hub environment from option.'],
];

foreach ($presentFragments as [$source, $fragment, $message]) {
    $assertions++;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException($message . ' Missing fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "KSeF API sync stage-2 bootstrap test passed.\n";
