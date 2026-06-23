<?php

declare(strict_types=1);

$service = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/services/class-ksef-import-service.php');
$admin = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$costTemplate = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/cost-invoices.php');
$clientTemplate = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/clients.php');

if ($service === '' || $admin === '' || $costTemplate === '' || $clientTemplate === '') {
    throw new RuntimeException('Unable to load KSeF sales client suggestion sources.');
}

$expectedFragments = [
    [$service, 'suggested_client', 'Sales import should return suggested client payload on unmatched buyer NIP.'],
    [$service, 'function build_suggested_client_from_sales_document(', 'Service should build suggested client data from sales invoice document.'],
    [$service, "'buyer_name' => \$buyer_name", 'Parser should extract buyer name from invoice XML.'],
    [$service, "'buyer_street' => \$buyer_address_line1", 'Parser should extract buyer address line from invoice XML.'],
    [$admin, 'function build_ksef_sales_suggested_client_url(', 'Admin runtime should build a client creation URL from suggested invoice data.'],
    [$admin, "'erp_omd_prefill_client' => 1", 'Suggested client URL should flag client form prefill.'],
    [$admin, "\$suggested_client_url = \$this->build_ksef_sales_suggested_client_url(\$suggested_client);", 'Sales XML handler should attach suggested client URL to import failures.'],
    [$admin, "! empty(\$_GET['erp_omd_prefill_client'])", 'Client screen should read the prefill flag.'],
    [$costTemplate, "Utwórz klienta z faktury", 'KSeF sales screen should show a create-client action.'],
    [$admin, "case 'create_client_from_ksef_sales_invoice'", 'Admin runtime should expose an inline create-client action for sales import suggestions.'],
    [$admin, 'function handle_create_client_from_ksef_sales_invoice_action()', 'Admin runtime should create suggested clients directly from the KSeF sales screen.'],
    [$admin, 'function build_ksef_sales_suggested_client_query_args(', 'Admin runtime should preserve suggested client fields in redirect query args.'],
    [$costTemplate, 'name="erp_omd_action" value="create_client_from_ksef_sales_invoice"', 'KSeF sales notice should render a direct create-client form.'],
    [$costTemplate, 'Proponowany klient:', 'KSeF sales notice should display the suggested client summary.'],
    [$costTemplate, 'ksef_sales_client_created', 'KSeF sales screen should confirm direct client creation.'],
    [$clientTemplate, 'Formularz klienta został uzupełniony danymi z faktury sprzedażowej', 'Client screen should explain prefilled invoice data.'],
];

foreach ($expectedFragments as [$source, $fragment, $message]) {
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException($message . ' Missing fragment: ' . $fragment);
    }
}

echo "KSeF sales client suggestion test passed.\n";
