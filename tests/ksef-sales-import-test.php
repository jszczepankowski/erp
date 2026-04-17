<?php

declare(strict_types=1);

$service = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/services/class-ksef-import-service.php');
$rest = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-rest-api.php');
$template = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/cost-invoices.php');
$admin = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$clientRepo = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/repositories/class-client-repository.php');

if ($service === '' || $rest === '' || $template === '' || $admin === '' || $clientRepo === '') {
    throw new RuntimeException('Unable to load one of files for KSeF sales import test.');
}

$assertions = 0;
$fragments = [
    [$service, 'function register_sales_document(', 'Service should register sales docs.'],
    [$service, 'function import_sales_xml(', 'Service should expose XML manual import.'],
    [$service, 'function attach_sales_document_to_project(', 'Service should support manual project attachment for sales invoices.'],
    [$service, 'function has_final_sales_invoice_for_project(', 'Service should expose final-sales validation helper.'],
    [$service, 'OPTION_SALES_INBOX', 'Service should persist dedicated sales inbox.'],
    [$service, 'function match_client_for_sales_document(', 'Service should map client by NIP.'],
    [$rest, "'/ksef/sales'", 'REST should expose sales list route.'],
    [$rest, "'/ksef/sales/(?P<sales_id>\\d+)/attach'", 'REST should expose sales attach route.'],
    [$rest, "'/ksef/sales/import-xml'", 'REST should expose sales XML import route.'],
    [$rest, "'/ksef/cost/import-xml'", 'REST should expose cost XML import route.'],
    [$rest, 'function import_ksef_sales_xml(', 'REST should expose sales XML callback.'],
    [$rest, 'function attach_ksef_sales_document(', 'REST should expose sales attach callback.'],
    [$rest, 'function import_ksef_cost_xml(', 'REST should expose cost XML callback.'],
    [$template, "'ksef-sales'", 'Admin template should provide sales KSeF tab.'],
    [$template, "'ksef-cost'", 'Admin template should provide cost KSeF tab.'],
    [$template, 'name="ksef_sales_xml_content"', 'Admin template should provide XML textarea.'],
    [$template, 'name="sales_id"', 'Admin template should expose sales attach form.'],
    [$template, 'name="is_final"', 'Admin template should allow marking sales invoice as final.'],
    [$template, 'name="ksef_sales_xml_files[]"', 'Admin template should provide bulk sales XML file upload.'],
    [$template, 'name="ksef_sales_description"', 'Admin template should provide optional sales invoice description field.'],
    [$template, 'name="ksef_cost_xml_content"', 'Admin template should provide cost XML textarea.'],
    [$template, 'name="ksef_cost_xml_files[]"', 'Admin template should provide bulk cost XML file upload.'],
    [$template, '$ksef_cost_invoices', 'Admin template should render cost KSeF imported list.'],
    [$template, "esc_html_e('Brak zaimportowanych kosztowych dokumentów KSeF.', 'erp-omd')", 'Admin template should show empty state for cost KSeF list.'],
    [$admin, "case 'import_ksef_sales_xml'", 'Admin runtime should process XML import action.'],
    [$admin, "case 'attach_ksef_sales_invoice'", 'Admin runtime should process sales attachment action.'],
    [$admin, "case 'import_ksef_cost_xml'", 'Admin runtime should process cost XML import action.'],
    [$admin, '$ksef_cost_invoices = array_values(array_filter((array) $cost_invoices', 'Admin runtime should prepare cost KSeF list.'],
    [$admin, 'function read_ksef_xml_from_request(', 'Admin runtime should read XML from textarea or file upload.'],
    [$admin, 'function read_ksef_xml_batch_from_request(', 'Admin runtime should support batch cost XML import.'],
    [$service, "'description' => trim((string) (\$document['description'] ?? ''))", 'Sales inbox rows should persist invoice description.'],
    [$clientRepo, 'function find_by_nip(', 'Client repository should support NIP mapping.'],
];

foreach ($fragments as [$source, $fragment, $message]) {
    $assertions++;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException($message . ' Missing fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "KSeF sales import test passed.\n";
