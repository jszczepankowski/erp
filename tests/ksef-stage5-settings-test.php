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
    [$admin, "update_option('erp_omd_ksef_api_base_url'", 'Admin should persist KSeF API base URL option.'],
    [$admin, "update_option('erp_omd_ksef_sync_subject_types'", 'Admin should persist subject types option.'],
    [$admin, "handle_ksef_sync_hub_dry_run_action", 'Admin should implement KSeF Sync Hub dry-run handler.'],
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
