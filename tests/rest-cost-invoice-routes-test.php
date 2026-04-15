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
    "'/cost-invoices/(?P<id>\\d+)/audit'",
    "'/ksef/import'",
    "'/ksef/sales/(?P<sales_id>\\d+)/attach'",
    "'/ksef/cost/import-xml'",
    "'/client-portal/projects/(?P<id>\\\\d+)/finance'",
    'function register_supplier_routes()',
    'function list_suppliers(',
    'function create_supplier(',
    'function delete_supplier(',
    'function list_cost_invoices(',
    'function create_cost_invoice(',
    'function update_cost_invoice(',
    'function delete_cost_invoice(',
    'function moderate_cost_invoice(',
    'function list_cost_invoice_audit(',
    'function import_ksef_documents(',
    'function list_ksef_sales_documents(',
    'function attach_ksef_sales_document(',
    'function import_ksef_sales_xml(',
    'function import_ksef_cost_xml(',
    'function list_ksef_moderation_queue(',
    'function moderate_ksef_queue_entry(',
    'function bulk_moderate_ksef_queue_entries(',
    'sanitize_cost_invoice_payload(WP_REST_Request $request, array $existing = null)',
    'supplier_description',
    'erp_omd_supplier_contact_invalid',
    'function is_supplier_category_allowed(',
    'function validate_supplier_contact_fields(',
    'function request_param_or_default(',
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
