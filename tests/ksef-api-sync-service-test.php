<?php

declare(strict_types=1);

$cron = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-cron-manager.php');
$admin = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$settingsTemplate = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/settings.php');
$autoloader = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-autoloader.php');
$syncServiceExists = file_exists(__DIR__ . '/../erp-omd/includes/services/class-ksef-api-sync-service.php');
$connectorExists = file_exists(__DIR__ . '/../erp-omd/includes/services/class-ksef-connector.php');

if ($cron === '' || $admin === '' || $settingsTemplate === '' || $autoloader === '') {
    throw new RuntimeException('Unable to load files for KSeF API sync removal test.');
}

$assertions = 0;

$assertions++;
if ($syncServiceExists) {
    throw new RuntimeException('KSeF API sync service file should be removed.');
}

$assertions++;
if ($connectorExists) {
    throw new RuntimeException('KSeF connector file should be removed.');
}

$absentFragments = [
    [$cron, 'KSEF_API_SYNC_HOOK', 'Cron should not contain KSeF API sync hook.'],
    [$cron, 'run_ksef_api_sync', 'Cron should not contain KSeF API sync runner.'],
    [$admin, "case 'ksef_api_sync_now':", 'Admin runtime should not handle manual KSeF API sync action.'],
    [$admin, "case 'ksef_fetch_public_key':", 'Admin runtime should not handle KSeF public key fetch action.'],
    [$admin, "case 'ksef_connector_check_now':", 'Admin runtime should not handle KSeF connector check action.'],
    [$settingsTemplate, 'name="ksef_api_enabled"', 'Settings should not expose KSeF API toggle.'],
    [$settingsTemplate, 'name="ksef_api_token"', 'Settings should not expose KSeF API token field.'],
    [$settingsTemplate, 'id="erp-omd-ksef-api-sync-now-form"', 'Settings should not expose KSeF API sync form.'],
    [$autoloader, 'ERP_OMD_KSeF_API_Sync_Service', 'Autoloader should not register removed API sync service.'],
    [$autoloader, 'ERP_OMD_KSeF_Connector', 'Autoloader should not register removed connector service.'],
];

foreach ($absentFragments as [$source, $fragment, $message]) {
    $assertions++;
    if (strpos($source, $fragment) !== false) {
        throw new RuntimeException($message . ' Unexpected fragment: ' . $fragment);
    }
}

$presentFragments = [
    [$settingsTemplate, 'name="ksef_auto_create_supplier"', 'Settings should keep manual KSeF document handling option.'],
    [$cron, 'KSEF_RETRY_PIPELINE_HOOK', 'Cron should keep retry pipeline for document workflow.'],
];

foreach ($presentFragments as [$source, $fragment, $message]) {
    $assertions++;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException($message . ' Missing fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "KSeF API sync removal test passed.\n";