<?php

declare(strict_types=1);

$importService = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/services/class-ksef-import-service.php');
$admin = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$settingsTemplate = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/settings.php');
$apiSyncService = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/services/class-ksef-api-sync-service.php');

if ($importService === '' || $admin === '' || $settingsTemplate === '' || $apiSyncService === '') {
    throw new RuntimeException('Unable to load files for KSeF auto-create supplier test.');
}

$assertions = 0;
$fragments = [
    [$importService, "const OPTION_AUTO_CREATE_SUPPLIER = 'erp_omd_ksef_auto_create_supplier';", 'Import service should expose auto-create supplier option.'],
    [$importService, "get_option(self::OPTION_AUTO_CREATE_SUPPLIER, false)", 'Import service should read auto-create supplier option.'],
    [$importService, "\$this->supplier_repository->create([", 'Import service should create supplier for unmatched NIP when option is enabled.'],
    [$importService, "'seller_name' => \$seller_name", 'XML parser should expose seller_name for auto-create supplier.'],
    [$apiSyncService, "'seller_name' => \$seller_name", 'API sync normalization should expose seller_name for auto-create supplier.'],
    [$admin, "ERP_OMD_KSeF_Import_Service::OPTION_AUTO_CREATE_SUPPLIER", 'Admin runtime should save/read auto-create supplier option.'],
    [$settingsTemplate, 'name="ksef_auto_create_supplier"', 'Settings template should expose auto-create supplier checkbox.'],
];

foreach ($fragments as [$source, $fragment, $message]) {
    $assertions++;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException($message . ' Missing fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "KSeF auto-create supplier test passed.\n";
