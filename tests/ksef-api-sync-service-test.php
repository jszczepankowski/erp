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
    [$syncService, "const OPTION_AP_TOKEN_ENC = 'erp_omd_ksef_ap_token_enc';", 'Sync service should expose AP token option constant.'],
    [$syncService, "const OPTION_PUBLIC_KEY_PEM = 'erp_omd_ksef_public_key_pem';", 'Sync service should expose public key option constant.'],
    [$syncService, 'function run_scheduled_sync()', 'Sync service should expose scheduled sync entrypoint.'],
    [$syncService, 'function sync(array $params = [])', 'Sync service should expose manual sync entrypoint.'],
    [$syncService, 'function fetch_and_store_token_encryption_public_key()', 'Sync service should support fetching MF public key certificates directly from KSeF API.'],
    [$syncService, '/api/v2/security/public-key-certificates', 'Sync service should call KSeF public key certificates endpoint.'],
    [$syncService, 'KsefTokenEncryption', 'Sync service should select certificate intended for token encryption.'],
    [$syncService, "Token KSeF wymaga osobnego flow uwierzytelnienia", 'Sync service should validate token format with actionable message.'],
    [$syncService, "Brak accessToken KSeF API. Uzupełnij accessToken JWT, refreshToken lub token KSeF z AP + NIP.", 'Sync service should instruct to provide access/refresh/AP token options.'],
    [$syncService, 'Szczegóły AP flow', 'Sync service should include AP-flow diagnostics in missing token error.'],
    [$syncService, "'https://api.ksef.mf.gov.pl'", 'Sync service should default to official KSeF API base URL.'],
    [$syncService, "OPTION_API_BASE_URL = 'erp_omd_ksef_api_base_url';", 'Sync service should expose configurable KSeF API base URL option.'],
    [$syncService, "OPTION_ENVIRONMENT = 'erp_omd_ksef_environment';", 'Sync service should expose configurable KSeF environment option.'],
    [$syncService, "'KSeF-Token' => \$token", 'Sync service should send KSeF token header.'],
    [$syncService, 'function request_with_endpoint_fallback(', 'Sync service should support endpoint fallbacks between /api/v2 and /v2 variants.'],
    [$syncService, '/v2/auth/challenge', 'Sync service should support /v2 challenge endpoint variant.'],
    [$syncService, '/invoices/ksef/', 'Sync service should support downloading full invoice XML by KSeF number.'],
    [$syncService, 'function enrich_documents_with_xml(', 'Sync service should enrich metadata with XML details when available.'],
    [$syncService, 'extract_http_error_message(array $payload)', 'Sync service should parse API error payload details.'],
    [$syncService, 'function refresh_access_token()', 'Sync service should support refresh token flow on 401.'],
    [$syncService, 'function redeem_access_token_from_ap_token()', 'Sync service should support AP token auth flow.'],
    [$syncService, '/api/v2/auth/challenge', 'Sync service should call challenge endpoint.'],
    [$syncService, '/api/v2/auth/ksef-token', 'Sync service should call ksef-token auth endpoint.'],
    [$syncService, "'Nip', 'nip', 'onip'", 'Sync service should try contextIdentifier.type variants (starting with Nip) for compatibility.'],
    [$syncService, '/api/v2/auth/', 'Sync service should poll auth operation status endpoint before redeeming token.'],
    [$syncService, '/api/v2/auth/token/redeem', 'Sync service should redeem authentication token to API tokens.'],
    [$syncService, 'function wait_for_authentication_ready(', 'Sync service should wait for async authentication operation completion.'],
    [$syncService, 'function normalize_challenge_timestamp_millis(', 'Sync service should normalize challenge timestamp to epoch milliseconds.'],
    [$syncService, 'function encrypt_ap_token(', 'Sync service should encrypt AP token with challenge timestamp.'],
    [$syncService, 'rsa_oaep_md:sha256', 'Sync service should prefer OAEP SHA-256 encryption for AP token payload.'],
    [$syncService, 'function encrypt_with_openssl_cli_oaep_sha256(', 'Sync service should provide OpenSSL CLI OAEP SHA-256 fallback/enhancement.'],
    [$syncService, 'function resolve_public_key(', 'Sync service should resolve public key from PEM/certificate payload.'],
    [$syncService, "Błąd pobierania metadanych KSeF (HTTP %1\$d): %2\$s", 'Sync service should expose HTTP code in sync error message.'],
    [$cron, "const KSEF_API_SYNC_HOOK = 'erp_omd_ksef_api_sync';", 'Cron manager should define KSeF API sync hook.'],
    [$cron, "add_action(self::KSEF_API_SYNC_HOOK, [__CLASS__, 'run_ksef_api_sync']);", 'Cron manager should register KSeF API sync action.'],
    [$cron, "wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', self::KSEF_API_SYNC_HOOK);", 'Cron manager should schedule KSeF API sync hourly.'],
    [$cron, 'function run_ksef_api_sync()', 'Cron manager should implement KSeF API sync runner.'],
    [$admin, "case 'ksef_api_sync_now':", 'Admin runtime should handle manual KSeF sync action.'],
    [$admin, "case 'ksef_fetch_public_key':", 'Admin runtime should handle manual fetch of MF public key.'],
    [$admin, 'function handle_ksef_api_sync_now()', 'Admin runtime should expose manual KSeF sync handler.'],
    [$admin, 'function handle_ksef_fetch_public_key()', 'Admin runtime should expose public key fetch handler.'],
    [$admin, "ksef_api_token_clear", 'Admin runtime should allow clearing persisted KSeF access token.'],
    [$admin, "ksef_api_refresh_token_clear", 'Admin runtime should allow clearing persisted KSeF refresh token.'],
    [$settingsTemplate, 'name="ksef_api_enabled"', 'Settings template should expose KSeF API toggle.'],
    [$settingsTemplate, 'name="ksef_api_token"', 'Settings template should expose KSeF API token field.'],
    [$settingsTemplate, 'name="ksef_api_token_clear"', 'Settings template should expose clear access token checkbox.'],
    [$settingsTemplate, 'name="ksef_api_refresh_token"', 'Settings template should expose KSeF API refresh token field.'],
    [$settingsTemplate, 'name="ksef_api_refresh_token_clear"', 'Settings template should expose clear refresh token checkbox.'],
    [$settingsTemplate, 'name="ksef_ap_token"', 'Settings template should expose AP token field.'],
    [$settingsTemplate, 'name="ksef_public_key_pem"', 'Settings template should expose KSeF public key field.'],
    [$settingsTemplate, 'name="ksef_api_environment"', 'Settings template should expose KSeF environment selector.'],
    [$settingsTemplate, 'Pobierz klucz publiczny KSeF (MF)', 'Settings template should expose one-click MF public key fetch button.'],
    [$settingsTemplate, 'id="erp-omd-ksef-fetch-public-key-form"', 'Settings template should include hidden form for public key fetch action.'],
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
