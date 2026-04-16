<?php

declare(strict_types=1);

$syncService = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/services/class-ksef-api-sync-service.php');
$cron = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-cron-manager.php');
$admin = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$settingsTemplate = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/settings.php');

if ($syncService === '' || $cron === '' || $admin === '' || $settingsTemplate === '') {
    throw new RuntimeException('Unable to load files for KSeF API sync test.');
}

$assertions = 0;
$fragments = [
    [$syncService, "class ERP_OMD_KSeF_API_Sync_Service", 'Sync service class should exist.'],
    [$syncService, "const OPTION_TOKEN_ENC = 'erp_omd_ksef_api_token_enc';", 'Sync service should expose token option constant.'],
    [$syncService, "const OPTION_REFRESH_TOKEN_ENC = 'erp_omd_ksef_api_refresh_token_enc';", 'Sync service should expose refresh token option constant.'],
    [$syncService, 'function run_scheduled_sync()', 'Sync service should expose scheduled sync entrypoint.'],
    [$syncService, 'function sync(array $params = [])', 'Sync service should expose manual sync entrypoint.'],
    [$syncService, "Token KSeF wymaga osobnego flow uwierzytelnienia", 'Sync service should validate token format with actionable message.'],
    [$syncService, "Brak accessToken KSeF API. Uzupełnij accessToken JWT lub refreshToken.", 'Sync service should instruct to provide access or refresh token.'],
    [$syncService, "'https://api.ksef.mf.gov.pl'", 'Sync service should default to official KSeF API base URL.'],
    [$syncService, "OPTION_API_BASE_URL = 'erp_omd_ksef_api_base_url';", 'Sync service should expose configurable KSeF API base URL option.'],
    [$syncService, "'KSeF-Token' => \$token", 'Sync service should send KSeF token header.'],
    [$syncService, 'extract_http_error_message(array $payload)', 'Sync service should parse API error payload details.'],
    [$syncService, 'function refresh_access_token()', 'Sync service should support refresh token flow on 401.'],
    [$syncService, "Błąd pobierania metadanych KSeF (HTTP %1\$d): %2\$s", 'Sync service should expose HTTP code in sync error message.'],
    [$cron, "const KSEF_API_SYNC_HOOK = 'erp_omd_ksef_api_sync';", 'Cron manager should define KSeF API sync hook.'],
    [$cron, "add_action(self::KSEF_API_SYNC_HOOK, [__CLASS__, 'run_ksef_api_sync']);", 'Cron manager should register KSeF API sync action.'],
    [$cron, "wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', self::KSEF_API_SYNC_HOOK);", 'Cron manager should schedule KSeF API sync hourly.'],
    [$cron, 'function run_ksef_api_sync()', 'Cron manager should implement KSeF API sync runner.'],
    [$admin, "case 'ksef_api_sync_now':", 'Admin runtime should handle manual KSeF sync action.'],
    [$admin, 'function handle_ksef_api_sync_now()', 'Admin runtime should expose manual KSeF sync handler.'],
    [$settingsTemplate, 'name="ksef_api_enabled"', 'Settings template should expose KSeF API toggle.'],
    [$settingsTemplate, 'name="ksef_api_token"', 'Settings template should expose KSeF API token field.'],
    [$settingsTemplate, 'name="ksef_api_refresh_token"', 'Settings template should expose KSeF API refresh token field.'],
    [$settingsTemplate, 'id="erp-omd-ksef-api-sync-now-form"', 'Settings template should expose manual KSeF sync form.'],
    [$settingsTemplate, 'name="ksef_sync_scope"', 'Settings template should allow manual sync scope selection.'],
];

foreach ($fragments as [$source, $fragment, $message]) {
    $assertions++;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException($message . ' Missing fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "KSeF API sync service test passed.\n";
