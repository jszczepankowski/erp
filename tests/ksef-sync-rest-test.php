<?php

declare(strict_types=1);

$rest = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-rest-api.php');

if ($rest === '') {
    throw new RuntimeException('Unable to load class-rest-api.php for KSeF sync REST test.');
}

$assertions = 0;
$fragments = [
    [$rest, "'/ksef/sync/status'", 'REST should expose KSeF sync status route.'],
    [$rest, "'/ksef/sync/run'", 'REST should expose KSeF sync run route.'],
    [$rest, 'function ksef_sync_status(', 'REST should implement KSeF sync status callback.'],
    [$rest, 'function run_ksef_sync_now(', 'REST should implement KSeF sync run callback.'],
    [$rest, 'new ERP_OMD_KSeF_Incremental_Sync_Service(null, null, $export_service, $import_service)', 'REST manual run should wire export+import services.'],
    [$rest, "'erp_omd_ksef_sync_hub_enabled'", 'REST status should expose whether KSeF Sync Hub is enabled.'],
];

foreach ($fragments as [$source, $fragment, $message]) {
    $assertions++;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException($message . ' Missing fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "KSeF sync REST test passed.\n";
