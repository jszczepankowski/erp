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
        return $GLOBALS['erp_omd_test_options'][$key] ?? $default;
    }
}

if (! function_exists('update_option')) {
    function update_option($key, $value)
    {
        $GLOBALS['erp_omd_test_options'][$key] = $value;
        return true;
    }
}

class ERP_OMD_Cost_Invoice_Workflow_Service_Fake
{
    /** @var array<int,array<string,mixed>> */
    public $created_payloads = [];

    /** @var array<int,array<string,mixed>> */
    public $updated_payloads = [];

    public function create_invoice(array $payload)
    {
        $this->created_payloads[] = $payload;
        if (($payload['invoice_number'] ?? '') === 'FAIL' || ($payload['invoice_number'] ?? '') === 'RETRY-ME') {
            return ['ok' => false, 'errors' => ['forced failure']];
        }

        return ['ok' => true, 'invoice_id' => count($this->created_payloads), 'errors' => []];
    }

    public function update_invoice($invoice_id, array $payload, $user_id)
    {
        $this->updated_payloads[] = ['invoice_id' => $invoice_id, 'payload' => $payload, 'user_id' => $user_id];
        return ['ok' => true, 'invoice_id' => (int) $invoice_id, 'errors' => []];
    }
}

class ERP_OMD_Cost_Invoice_Repository_Fake
{
    /** @var array<int,array<string,mixed>> */
    public $rows = [];

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function find_by_ksef_reference($ksef_reference_number)
    {
        $needle = trim((string) $ksef_reference_number);
        if ($needle === '') {
            return null;
        }

        foreach ($this->rows as $row) {
            if ((string) ($row['ksef_reference_number'] ?? '') === $needle) {
                return $row;
            }
        }

        return null;
    }

    public function find_by_supplier_and_invoice_number($supplier_id, $invoice_number)
    {
        $supplier_id = (int) $supplier_id;
        $invoice_number = mb_strtolower(trim((string) $invoice_number));

        foreach ($this->rows as $row) {
            if ((int) ($row['supplier_id'] ?? 0) === $supplier_id && mb_strtolower((string) ($row['invoice_number'] ?? '')) === $invoice_number) {
                return $row;
            }
        }

        return null;
    }
}

class ERP_OMD_Cost_Invoice_Audit_Repository_Fake
{
    /** @var array<int,array<string,mixed>> */
    public $inserted_rows = [];

    public function insert_many(array $rows)
    {
        foreach ($rows as $row) {
            $this->inserted_rows[] = $row;
        }

        return count($rows);
    }
}


class ERP_OMD_Supplier_Repository_Fake
{
    /** @var array<string,array<int,array<string,mixed>>> */
    private $matches = [];

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


class ERP_OMD_Client_Repository_Fake
{
    /** @var array<string,array<int,array<string,mixed>>> */
    private $matches = [];

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

$GLOBALS['erp_omd_test_options'] = [
    'erp_omd_company_nip' => '1111111111',
    ERP_OMD_KSeF_Import_Service::OPTION_RETRY_QUEUE => [],
];

$workflow = new ERP_OMD_Cost_Invoice_Workflow_Service_Fake();
$repo = new ERP_OMD_Cost_Invoice_Repository_Fake([
    ['id' => 20, 'supplier_id' => 7, 'invoice_number' => 'FV/2026/1', 'ksef_reference_number' => 'EXISTS-REF'],
    ['id' => 21, 'supplier_id' => 5, 'invoice_number' => 'DUP-SUPP', 'ksef_reference_number' => 'OTHER-REF'],
]);
$audit = new ERP_OMD_Cost_Invoice_Audit_Repository_Fake();
$suppliers = new ERP_OMD_Supplier_Repository_Fake([
    '2222222222' => [['id' => 1, 'nip' => '2222222222']],
    '3333333333' => [['id' => 6, 'nip' => '3333333333'], ['id' => 7, 'nip' => '3333333333']],
]);
$clients = new ERP_OMD_Client_Repository_Fake([
    '5555555555' => [['id' => 9, 'nip' => '5555555555']],
]);
$service = new ERP_OMD_KSeF_Import_Service($workflow, $repo, $audit, null, null, $suppliers, $clients);
$assertions = 0;

$result = $service->import_documents([
    [
        'supplier_id' => 1,
        'project_id' => 99,
        'invoice_number' => 'OK/1',
        'issue_date' => '2026-04-14',
        'gross_amount' => 123.45,
        'ksef_reference_number' => 'NEW-REF',
        'buyer_nip' => '111-111-11-11',
        'seller_nip' => '2222222222',
    ],
    [
        'supplier_id' => 7,
        'invoice_number' => 'ANY',
        'ksef_reference_number' => 'EXISTS-REF',
        'buyer_nip' => '1111111111',
        'seller_nip' => '2222222222',
    ],
    [
        'supplier_id' => 5,
        'invoice_number' => 'dup-supp',
        'ksef_reference_number' => 'NEW-REF-2',
        'buyer_nip' => '1111111111',
        'seller_nip' => '2222222222',
    ],
    [
        'supplier_id' => 1,
        'invoice_number' => 'MANUAL',
        'ksef_reference_number' => 'NEW-REF-3',
        'buyer_nip' => '9999999999',
        'seller_nip' => '8888888888',
    ],
], 91);

$assertions++;
if (($result['total'] ?? 0) !== 4 || ($result['imported'] ?? 0) !== 1 || ($result['duplicates'] ?? 0) !== 1 || ($result['conflicts'] ?? 0) !== 1 || ($result['failed'] ?? 0) !== 1) {
    throw new RuntimeException('Expected 1 imported, 1 primary duplicate, 1 fallback conflict and 1 manual-required failure.');
}

$assertions++;
if (($workflow->created_payloads[0]['document_kind'] ?? '') !== ERP_OMD_KSeF_Import_Service::IMPORT_KIND_COST || (int) ($workflow->created_payloads[0]['project_id'] ?? -1) !== 0) {
    throw new RuntimeException('Expected cost classification and empty project assignment for imported cost invoice.');
}

$assertions++;
if (count($audit->inserted_rows) !== 1 || (string) ($audit->inserted_rows[0]['field_name'] ?? '') !== 'ksef_import_conflict_reason') {
    throw new RuntimeException('Expected fallback conflict to be audited.');
}

$assertions++;
$manualErrors = (array) ($result['errors'][1]['errors'] ?? []);
if (($result['errors'][1]['status'] ?? '') !== ERP_OMD_KSeF_Import_Service::IMPORT_STATUS_MANUAL_REQUIRED || strpos(implode(' ', $manualErrors), 'Nie rozpoznano roli NIP') === false) {
    throw new RuntimeException('Expected unclassified document to be marked as manual_required with readable error.');
}


$singleMatchImport = $service->attempt_import_document([
    'invoice_number' => 'SUPP-SINGLE',
    'ksef_reference_number' => 'NEW-SUPP-SINGLE',
    'buyer_nip' => '1111111111',
    'seller_nip' => '2222222222',
], 91, false);
$assertions++;
if (($singleMatchImport['status'] ?? '') !== ERP_OMD_KSeF_Import_Service::IMPORT_STATUS_IMPORTED || (int) ($workflow->created_payloads[1]['supplier_id'] ?? 0) !== 1) {
    throw new RuntimeException('Expected single supplier NIP match to auto-assign supplier_id for cost invoice.');
}

$multiMatchImport = $service->attempt_import_document([
    'invoice_number' => 'SUPP-MULTI',
    'ksef_reference_number' => 'NEW-SUPP-MULTI',
    'buyer_nip' => '1111111111',
    'seller_nip' => '3333333333',
], 91, false);
$assertions++;
if (($multiMatchImport['status'] ?? '') !== ERP_OMD_KSeF_Import_Service::IMPORT_STATUS_CONFLICT) {
    throw new RuntimeException('Expected multi supplier NIP match to produce conflict/manual path.');
}

$noMatchImport = $service->attempt_import_document([
    'invoice_number' => 'SUPP-NONE',
    'ksef_reference_number' => 'NEW-SUPP-NONE',
    'buyer_nip' => '1111111111',
    'seller_nip' => '4444444444',
], 91, false);
$assertions++;
if (($noMatchImport['status'] ?? '') !== ERP_OMD_KSeF_Import_Service::IMPORT_STATUS_MANUAL_REQUIRED) {
    throw new RuntimeException('Expected no supplier NIP match to produce manual_required path.');
}


$salesImport = $service->attempt_import_document([
    'invoice_number' => 'SALES-1',
    'ksef_reference_number' => 'SALES-REF-1',
    'buyer_nip' => '5555555555',
    'seller_nip' => '1111111111',
], 91, false);
$assertions++;
if (($salesImport['status'] ?? '') !== ERP_OMD_KSeF_Import_Service::IMPORT_STATUS_IMPORTED || count($service->list_sales_inbox()) !== 1) {
    throw new RuntimeException('Expected sales KSeF document to be registered in dedicated sales inbox.');
}

$xmlImport = $service->import_sales_xml('<?xml version="1.0"?><Fa><Naglowek><P_1>2026-04-15</P_1><P_2>XML/SALE/1</P_2></Naglowek><Podmiot1><DaneIdentyfikacyjne><NIP>1111111111</NIP></DaneIdentyfikacyjne></Podmiot1><Podmiot2><DaneIdentyfikacyjne><NIP>5555555555</NIP></DaneIdentyfikacyjne></Podmiot2><NumerKSeF>XML-SALES-REF</NumerKSeF><FaCtrl><B>100</B><V>23</V><WartoscFaktury>123</WartoscFaktury></FaCtrl></Fa>', 91);
$assertions++;
if ((int) ($xmlImport['imported'] ?? 0) !== 1 || count($service->list_sales_inbox()) < 2) {
    throw new RuntimeException('Expected manual sales XML import to append row into sales inbox.');
}

$classification = $service->classify_document([
    'buyer_nip' => '555-55-55-555',
    'seller_nip' => '1111111111',
]);
$assertions++;
if (($classification['kind'] ?? '') !== ERP_OMD_KSeF_Import_Service::IMPORT_KIND_SALES) {
    throw new RuntimeException('Expected seller-side classification to mark document as sales.');
}

$retrying = $service->build_retry_decision([
    'retry_attempts' => 2,
    'first_failed_at' => '2026-04-15 08:00:00',
    'last_error' => 'timeout',
], '2026-04-15 08:10:00');

$assertions++;
if (($retrying['status'] ?? '') !== ERP_OMD_KSeF_Import_Service::IMPORT_STATUS_RETRYING || ($retrying['should_retry'] ?? false) !== true || ($retrying['next_retry_at'] ?? '') === '') {
    throw new RuntimeException('Expected retry decision inside 90-minute window.');
}

$manual = $service->build_retry_decision([
    'retry_attempts' => 6,
    'first_failed_at' => '2026-04-15 08:00:00',
    'last_error' => 'timeout',
], '2026-04-15 09:45:00');

$assertions++;
if (($manual['status'] ?? '') !== ERP_OMD_KSeF_Import_Service::IMPORT_STATUS_MANUAL_REQUIRED || ($manual['should_retry'] ?? true) !== false) {
    throw new RuntimeException('Expected manual_required after 90 minutes of unsuccessful retries.');
}

$service->enqueue_retry([
    'supplier_id' => 1,
    'invoice_number' => 'RETRY-ME',
    'ksef_reference_number' => 'RETRY-REF-1',
    'buyer_nip' => '1111111111',
    'seller_nip' => '2222222222',
], 91, 'forced failure');

$queue = (array) ($GLOBALS['erp_omd_test_options'][ERP_OMD_KSeF_Import_Service::OPTION_RETRY_QUEUE] ?? []);
$assertions++;
$retryEntryFound = false;
foreach ($queue as $queueRow) {
    if ((string) ($queueRow['retry_key'] ?? '') !== 'ref:retry-ref-1') {
        continue;
    }

    $retryEntryFound = (string) ($queueRow['status'] ?? '') === ERP_OMD_KSeF_Import_Service::IMPORT_STATUS_RETRYING;
    break;
}
if (! $retryEntryFound) {
    throw new RuntimeException('Expected enqueue_retry() to persist retry queue state for RETRY-REF-1.');
}

$retryNow = '';
foreach ($queue as $queueRow) {
    if ((string) ($queueRow['retry_key'] ?? '') === 'ref:retry-ref-1') {
        $retryNow = (string) ($queueRow['next_retry_at'] ?? '');
        break;
    }
}
if ($retryNow === '') {
    $retryNow = '2026-04-15 09:00:00';
}

$retryRun = $service->process_retry_queue(20, $retryNow);
$assertions++;
if ((int) ($retryRun['processed'] ?? 0) < 1) {
    throw new RuntimeException('Expected process_retry_queue() to process at least one due retry entry.');
}

$queueAfterRun = (array) ($GLOBALS['erp_omd_test_options'][ERP_OMD_KSeF_Import_Service::OPTION_RETRY_QUEUE] ?? []);
$assertions++;
$updatedRetryAttempts = false;
foreach ($queueAfterRun as $queueRow) {
    if ((string) ($queueRow['retry_key'] ?? '') !== 'ref:retry-ref-1') {
        continue;
    }

    $updatedRetryAttempts = (int) ($queueRow['retry_attempts'] ?? 0) >= 2;
    break;
}
if (! $updatedRetryAttempts) {
    throw new RuntimeException('Expected retry queue state to update retry attempts for RETRY-REF-1 after failed retry run.');
}

$moderation = $service->moderate_imported_invoice(15, ['status' => 'weryfikacja', 'supplier_id' => 5, 'project_id' => 8, 'invoice_number' => 'FV-15'], 44);

$assertions++;
if (($moderation['ok'] ?? false) !== true || count($workflow->updated_payloads) !== 1) {
    throw new RuntimeException('Expected moderation to call workflow update exactly once.');
}

$assertions++;
if (($workflow->updated_payloads[0]['payload']['source'] ?? '') !== 'ksef') {
    throw new RuntimeException('Expected moderation updates to keep ksef source.');
}

echo "Assertions: {$assertions}\n";
echo "KSeF import service test passed.\n";
