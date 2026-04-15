<?php

declare(strict_types=1);

require_once __DIR__ . '/../erp-omd/includes/services/class-ksef-import-service.php';

if (! function_exists('__')) {
    function __($text)
    {
        return $text;
    }
}

if (! function_exists('get_option')) {
    function get_option($key, $default = false)
    {
        return $GLOBALS['erp_omd_test_options_s606'][$key] ?? $default;
    }
}

if (! function_exists('update_option')) {
    function update_option($key, $value)
    {
        $GLOBALS['erp_omd_test_options_s606'][$key] = $value;
        return true;
    }
}

class ERP_OMD_Workflow_Fake_S606
{
    public function create_invoice(array $payload)
    {
        return ['ok' => true, 'invoice_id' => 1, 'errors' => []];
    }
}

class ERP_OMD_Cost_Invoice_Repository_Fake_S606
{
    public function find_by_ksef_reference($ksef_reference_number)
    {
        return null;
    }

    public function find_by_supplier_and_invoice_number($supplier_id, $invoice_number)
    {
        return null;
    }
}

class ERP_OMD_Cost_Invoice_Audit_Repository_Fake_S606
{
    public function insert_many(array $rows)
    {
        return count($rows);
    }
}

class ERP_OMD_Supplier_Repository_Fake_S606
{
    public function find_by_nip($nip)
    {
        return [];
    }
}

class ERP_OMD_Client_Repository_Fake_S606
{
    /** @var array<string,array<int,array<string,mixed>>> */
    private $matches;

    public function __construct(array $matches)
    {
        $this->matches = $matches;
    }

    public function find_by_nip($nip)
    {
        $nip = preg_replace('/[^0-9]/', '', (string) $nip);
        if (! is_string($nip)) {
            return [];
        }

        return (array) ($this->matches[$nip] ?? []);
    }
}

$GLOBALS['erp_omd_test_options_s606'] = [
    'erp_omd_company_nip' => '1111111111',
    ERP_OMD_KSeF_Import_Service::OPTION_RETRY_QUEUE => [],
    ERP_OMD_KSeF_Import_Service::OPTION_SALES_INBOX => [],
];

$service = new ERP_OMD_KSeF_Import_Service(
    new ERP_OMD_Workflow_Fake_S606(),
    new ERP_OMD_Cost_Invoice_Repository_Fake_S606(),
    new ERP_OMD_Cost_Invoice_Audit_Repository_Fake_S606(),
    null,
    null,
    new ERP_OMD_Supplier_Repository_Fake_S606(),
    new ERP_OMD_Client_Repository_Fake_S606([
        '5555555555' => [['id' => 77, 'name' => 'Client One']],
        '6666666666' => [['id' => 1], ['id' => 2]],
    ])
);

$assertions = 0;

$import = $service->import_documents([
    [
        'invoice_number' => 'SALE/2026/01',
        'issue_date' => '2026-04-15',
        'ksef_reference_number' => 'S606-REF-1',
        'buyer_nip' => '555-555-55-55',
        'seller_nip' => '1111111111',
        'gross_amount' => 123.00,
    ],
], 9);

$assertions++;
if ((int) ($import['imported'] ?? 0) !== 1) {
    throw new RuntimeException('S6-06 E2E: expected sales import to finish as imported.');
}

$rows = $service->list_sales_inbox();
$assertions++;
if (count($rows) !== 1) {
    throw new RuntimeException('S6-06 E2E: expected sales document in dedicated sales inbox.');
}

$assertions++;
if ((int) ($rows[0]['client_id'] ?? 0) !== 77) {
    throw new RuntimeException('S6-06 E2E: expected automatic client match by buyer NIP.');
}

$assertions++;
if ((int) ($rows[0]['project_id'] ?? -1) !== 0) {
    throw new RuntimeException('S6-06 E2E: expected empty project assignment awaiting manual attach.');
}

$xml = '<?xml version="1.0"?>
<Fa>
  <Naglowek><P_1>2026-04-15</P_1><P_2>SALE/XML/01</P_2></Naglowek>
  <Podmiot1><DaneIdentyfikacyjne><NIP>1111111111</NIP></DaneIdentyfikacyjne></Podmiot1>
  <Podmiot2><DaneIdentyfikacyjne><NIP>5555555555</NIP></DaneIdentyfikacyjne></Podmiot2>
  <NumerKSeF>S606-XML-REF-1</NumerKSeF>
  <FaCtrl><B>100</B><V>23</V><WartoscFaktury>123</WartoscFaktury></FaCtrl>
</Fa>';
$xmlImport = $service->import_sales_xml($xml, 9);

$assertions++;
if ((int) ($xmlImport['imported'] ?? 0) !== 1 || count($service->list_sales_inbox()) !== 2) {
    throw new RuntimeException('S6-06 E2E: expected manual XML sales import to append into sales inbox.');
}

$conflictImport = $service->attempt_import_document([
    'invoice_number' => 'SALE/2026/NO-UNIQUE',
    'ksef_reference_number' => 'S606-REF-CONFLICT',
    'buyer_nip' => '6666666666',
    'seller_nip' => '1111111111',
], 9, false);

$assertions++;
if (($conflictImport['status'] ?? '') !== ERP_OMD_KSeF_Import_Service::IMPORT_STATUS_CONFLICT) {
    throw new RuntimeException('S6-06 E2E: expected multi-client NIP match to go into conflict/manual flow.');
}

echo "Assertions: {$assertions}\n";
echo "S6-06 sales import E2E test passed.\n";
