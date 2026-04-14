<?php

declare(strict_types=1);

$source = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-rest-api.php');
if ($source === '') {
    throw new RuntimeException('Unable to read REST API source file.');
}

$requiredFragments = [
    "'/suppliers'",
    "'/suppliers/(?P<id>\\d+)'",
    "'/cost-invoices'",
    "'/cost-invoices/(?P<id>\\d+)'",
    "'/cost-invoices/(?P<id>\\d+)/moderate'",
    "'/ksef/import'",
    "'/client-portal/projects/(?P<id>\\\\d+)/finance'",
    'function register_supplier_routes()',
    'function list_suppliers(',
    'function create_supplier(',
    'function list_cost_invoices(',
    'function create_cost_invoice(',
    'function update_cost_invoice(',
    'function moderate_cost_invoice(',
    'function import_ksef_documents(',
    'function get_client_portal_project_finance(',
];

$assertions = 0;
foreach ($requiredFragments as $fragment) {
    $assertions++;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException('Missing REST API fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "REST cost invoice route test passed.\n";
