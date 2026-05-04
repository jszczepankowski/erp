<?php

declare(strict_types=1);

$incremental = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/services/class-ksef-incremental-sync-service.php');
$importService = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/services/class-ksef-import-service.php');
$cron = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-cron-manager.php');

if ($incremental === '' || $importService === '' || $cron === '') {
    throw new RuntimeException('Unable to load files for KSeF stage-4 import bridge contract test.');
}

$assertions = 0;
$fragments = [
    [$incremental, 'extract_documents_for_import', 'Incremental sync should map export payload to import documents.'],
    [$incremental, "method_exists(\$this->import_service, 'import_documents')", 'Incremental sync should call import service when available.'],
    [$incremental, "'api_sync_source' => 'ksef_sync_hub'", 'Incremental sync should mark documents with KSeF Sync Hub source marker.'],
    [$importService, "'api_sync_source' => (string) (\$document['api_sync_source'] ?? '')", 'Import mapping should persist API sync source in invoice payload.'],
    [$cron, 'new ERP_OMD_KSeF_Import_Service(', 'Cron should wire stage-4 import bridge into scheduled sync.'],
];

foreach ($fragments as [$source, $fragment, $message]) {
    $assertions++;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException($message . ' Missing fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "KSeF stage-4 import bridge contract test passed.\n";
