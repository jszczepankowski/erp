<?php

declare(strict_types=1);

$syncService = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/services/class-ksef-api-sync-service.php');
$connector = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/services/class-ksef-connector.php');
$cron = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-cron-manager.php');
$admin = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$settingsTemplate = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/settings.php');
$autoloader = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-autoloader.php');

if ($syncService === '' || $connector === '' || $cron === '' || $admin === '' || $settingsTemplate === '' || $autoloader === '') {
    throw new RuntimeException('Unable to load files for KSeF API sync test.');
}

$assertions = 0;
$fragments = [
    [$syncService, 'class ERP_OMD_KSeF_API_Sync_Service', 'Sync service class should exist.'],
    [$syncService, "const OPTION_ENVIRONMENT = 'erp_omd_ksef_environment';", 'Sync service should expose environment option.'],
    [$syncService, 'function run_connector_check(array $params = [])', 'Sync service should expose connector dry-run method.'],
    [$syncService, 'function fetch_and_store_token_encryption_public_key()', 'Sync service should expose public key fetch method.'],
    [$syncService, 'function extract_certificate_items(', 'Sync service should normalize certificate payloads.'],
    [$syncService, 'function redeem_access_token_from_ap_token()', 'Sync service should support AP auth flow.'],
    [$syncService, '/auth/challenge', 'Sync service should call challenge endpoint.'],
    [$syncService, '/auth/ksef-token', 'Sync service should call ksef-token endpoint.'],
    [$syncService, '/auth/token/redeem', 'Sync service should redeem authentication token.'],
    [$syncService, '/invoices/query/metadata', 'Sync service should query KSeF invoice metadata.'],
    [$syncService, '/invoices/ksef/', 'Sync service should fetch full XML by KSeF reference.'],
    [$connector, 'class ERP_OMD_KSeF_Connector', 'Dedicated logical connector should exist.'],
    [$connector, 'function request($method, $path, array $headers = [], $body = null, $timeout = 25)', 'Connector should expose HTTP request method.'],
    [$connector, 'private $prefix_candidates;', 'Connector should keep prefix candidates.'],
    [$connector, "['/v2', '/api/v2', ''];", 'Connector should try KSeF URL prefixes.'],
    [$autoloader, "'ERP_OMD_KSeF_Connector' => 'includes/services/class-ksef-connector.php'", 'Autoloader should register connector class.'],
    [$cron, "const KSEF_API_SYNC_HOOK = 'erp_omd_ksef_api_sync';", 'Cron manager should define KSeF sync hook.'],
    [$admin, "case 'ksef_api_sync_now':", 'Admin runtime should handle manual KSeF sync action.'],
    [$admin, "case 'ksef_fetch_public_key':", 'Admin runtime should handle KSeF public key fetch action.'],
    [$admin, "case 'ksef_connector_check_now':", 'Admin runtime should handle KSeF connector check action.'],
    [$settingsTemplate, 'name="ksef_api_environment"', 'Settings should expose KSeF environment selector.'],
    [$settingsTemplate, 'id="erp-omd-ksef-connector-check-form"', 'Settings should include connector check form.'],
    [$settingsTemplate, 'id="erp-omd-ksef-fetch-public-key-form"', 'Settings should include public key fetch form.'],
];

foreach ($fragments as [$source, $fragment, $message]) {
    $assertions++;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException($message . ' Missing fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "KSeF API sync service test passed.\n";
