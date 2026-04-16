<?php

declare(strict_types=1);

$adminSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$templateSource = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/settings.php');
$serviceSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/services/class-ksef-import-service.php');
$restSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-rest-api.php');

if ($adminSource === '' || $templateSource === '' || $serviceSource === '' || $restSource === '') {
    throw new RuntimeException('Failed to load one of sources required for company NIP settings test.');
}

$assertions = 0;
$checks = [
    [$adminSource, "get_option('erp_omd_company_nip'", 'Admin should read company NIP option.'],
    [$adminSource, "update_option('erp_omd_company_nip'", 'Admin should save company NIP option.'],
    [$templateSource, "name=\"company_nip\"", 'Settings template should expose company_nip field.'],
    [$serviceSource, "get_option('erp_omd_company_nip'", 'KSeF service should fallback to saved company NIP option.'],
    [$restSource, "get_option('erp_omd_company_nip', '')", 'REST sanitization should fallback to saved company NIP option.'],
];

foreach ($checks as [$source, $fragment, $message]) {
    $assertions++;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException($message);
    }
}

echo "Assertions: {$assertions}\n";
echo "Settings company NIP test passed.\n";
