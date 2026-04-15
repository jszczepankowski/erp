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
    [$service, 'OPTION_SALES_INBOX', 'Service should persist dedicated sales inbox.'],
    [$service, 'function match_client_for_sales_document(', 'Service should map client by NIP.'],
    [$rest, "'/ksef/sales'", 'REST should expose sales list route.'],
    [$rest, "'/ksef/sales/import-xml'", 'REST should expose sales XML import route.'],
    [$rest, 'function import_ksef_sales_xml(', 'REST should expose sales XML callback.'],
    [$template, "'ksef-sales'", 'Admin template should provide sales KSeF tab.'],
    [$template, 'name="ksef_sales_xml_content"', 'Admin template should provide XML textarea.'],
    [$template, 'name="ksef_sales_xml_file"', 'Admin template should provide XML file upload.'],
    [$admin, "case 'import_ksef_sales_xml'", 'Admin runtime should process XML import action.'],
    [$admin, 'function read_ksef_sales_xml_from_request(', 'Admin runtime should read XML from textarea or file upload.'],
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
