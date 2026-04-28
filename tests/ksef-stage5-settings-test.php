<?php

declare(strict_types=1);

$admin = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$template = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/settings.php');

if ($admin === '' || $template === '') {
    throw new RuntimeException('Unable to load files for KSeF stage-5 settings test.');
}

$assertions = 0;
$fragments = [
    [$admin, "update_option('erp_omd_ksef_sync_hub_enabled'", 'Admin should persist KSeF Sync Hub enabled option.'],
    [$admin, "update_option('erp_omd_ksef_sync_hub_env'", 'Admin should persist KSeF Sync Hub environment option.'],
    [$admin, "update_option('erp_omd_ksef_sync_hub_mode'", 'Admin should persist KSeF Sync Hub mode option.'],
    [$admin, "update_option('erp_omd_ksef_api_base_url'", 'Admin should persist KSeF API base URL option.'],
    [$admin, "update_option('erp_omd_ksef_sync_hub_context_identifier'", 'Admin should persist KSeF context identifier option.'],
    [$admin, "update_option('erp_omd_ksef_sync_hub_ap_token_enc'", 'Admin should persist encrypted AP token option.'],
    [$admin, "update_option('erp_omd_ksef_public_key_' . strtolower(\$ksef_sync_hub_env)", 'Admin should persist public key per environment option.'],
    [$admin, "update_option('erp_omd_ksef_sync_subject_types'", 'Admin should persist subject types option.'],
    [$admin, "handle_ksef_sync_hub_dry_run_action", 'Admin should implement KSeF Sync Hub dry-run handler.'],
    [$admin, "handle_ksef_sync_hub_fetch_public_key_action", 'Admin should implement KSeF Sync Hub public key fetch handler.'],
    [$admin, "handle_ksef_sync_hub_apply_env_defaults_action", 'Admin should implement KSeF Sync Hub env-default base URL handler.'],
    [$template, 'name="ksef_sync_hub_mode"', 'Settings template should expose sync mode field.'],
    [$template, 'name="ksef_sync_hub_context_identifier"', 'Settings template should expose context identifier field.'],
    [$template, 'name="ksef_sync_hub_ap_token"', 'Settings template should expose AP token field.'],
    [$template, 'name="ksef_sync_hub_public_key_pem"', 'Settings template should expose public key field.'],
    [$template, 'erp-omd-ksef-sync-hub-fetch-public-key-form', 'Settings template should expose public key fetch form.'],
    [$template, 'Pobierz klucz publiczny MF z API', 'Settings template should expose public key fetch CTA.'],
    [$template, 'erp-omd-ksef-sync-hub-apply-env-defaults-form', 'Settings template should expose env defaults form.'],
    [$template, 'Ustaw domyślny Base URL wg środowiska', 'Settings template should expose env defaults CTA.'],
    [$template, 'name="ksef_sync_subject_types"', 'Settings template should expose subject types field.'],
    [$template, 'name="ksef_api_base_url"', 'Settings template should expose API base URL field.'],
    [$template, 'Dry-run connector check', 'Settings template should expose dry-run CTA.'],
];

foreach ($fragments as [$source, $fragment, $message]) {
    $assertions++;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException($message . ' Missing fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "KSeF stage-5 settings test passed.\n";
