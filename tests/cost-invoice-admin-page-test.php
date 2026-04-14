<?php

declare(strict_types=1);

$runtimeSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$templateSource = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/cost-invoices.php');

if ($runtimeSource === '' || $templateSource === '') {
    throw new RuntimeException('Unable to read admin runtime/template source.');
}

$runtimeFragments = [
    "'erp-omd-cost-invoices'",
    'render_cost_invoices',
    "case 'save_supplier'",
    "case 'save_cost_invoice'",
    'function handle_supplier_save(',
    'function handle_cost_invoice_save(',
    'function redirect_cost_invoice_page(',
    'normalize_supplier_categories',
    'supplier_category_invalid',
    'selected_supplier_id',
    'selected_invoice',
    'project_supplier_pairs',
    'supplier_categories',
];

$templateFragments = [
    "name=\"erp_omd_action\" value=\"save_supplier\"",
    "name=\"erp_omd_action\" value=\"save_cost_invoice\"",
    'Lista faktur kosztowych',
    'Audit faktury',
    'name="supplier_id"',
    'name="cost_invoice_id"',
    'Edytuj fakturę kosztową',
    'Dostawcy',
    'Relacje projekt ↔ dostawca (E3)',
    'supplier_category',
    'supplier_description',
    'supplier_company',
    'supplier_nip',
    'supplier_email',
    'supplier_phone',
    'supplier_city',
    'supplier_street',
    'supplier_apartment_number',
    'supplier_postal_code',
    'supplier_country',
    'supplier_categories_dictionary',
];

$assertions = 0;
foreach ($runtimeFragments as $fragment) {
    $assertions++;
    if (strpos($runtimeSource, $fragment) === false) {
        throw new RuntimeException('Missing admin runtime fragment: ' . $fragment);
    }
}

foreach ($templateFragments as $fragment) {
    $assertions++;
    if (strpos($templateSource, $fragment) === false) {
        throw new RuntimeException('Missing admin template fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "Cost invoice admin page test passed.\n";
