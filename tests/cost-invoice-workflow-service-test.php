<?php

declare(strict_types=1);

if (! function_exists('__')) {
    function __($text)
    {
        return $text;
    }
}

require_once __DIR__ . '/../erp-omd/includes/services/class-cost-invoice-workflow-service.php';

class ERP_OMD_Cost_Invoice_Repository_Fake
{
    /** @var array<int,array<string,mixed>> */
    public $rows = [];

    /** @var int */
    public $next_id = 1;

    public function for_supplier($supplier_id)
    {
        return array_values(array_filter($this->rows, static function ($row) use ($supplier_id) {
            return (int) ($row['supplier_id'] ?? 0) === (int) $supplier_id;
        }));
    }

    public function create(array $data)
    {
        $id = $this->next_id++;
        $this->rows[$id] = array_merge($data, ['id' => $id]);

        return $id;
    }

    public function find($id)
    {
        return $this->rows[(int) $id] ?? null;
    }

    public function update($id, array $data)
    {
        if (! isset($this->rows[(int) $id])) {
            return false;
        }

        $this->rows[(int) $id] = array_merge($this->rows[(int) $id], $data, ['id' => (int) $id]);
        return 1;
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
    public function find($id)
    {
        return (int) $id === 2 ? ['id' => 2] : null;
    }
}

class ERP_OMD_Project_Repository_Fake
{
    public function find($id)
    {
        return (int) $id === 9 ? ['id' => 9] : null;
    }
}

$invoice_repository = new ERP_OMD_Cost_Invoice_Repository_Fake();
$audit_repository = new ERP_OMD_Cost_Invoice_Audit_Repository_Fake();
$service = new ERP_OMD_Cost_Invoice_Workflow_Service(
    $invoice_repository,
    $audit_repository,
    new ERP_OMD_Supplier_Repository_Fake(),
    new ERP_OMD_Project_Repository_Fake()
);
$assertions = 0;

$assertions++;
if (! $service->can_transition('zaimportowana', 'weryfikacja')) {
    throw new RuntimeException('Expected transition zaimportowana -> weryfikacja to be valid.');
}

$assertions++;
if ($service->can_transition('zaimportowana', 'przypisana')) {
    throw new RuntimeException('Expected transition zaimportowana -> przypisana to be invalid.');
}

$errors = $service->validate_invoice_data(
    [
        'id' => 11,
        'supplier_id' => 2,
        'project_id' => 9,
        'invoice_number' => 'FV/2026/04/11',
        'issue_date' => '2026-04-11',
        'net_amount' => 100.00,
        'vat_amount' => 23.00,
        'gross_amount' => 123.00,
        'status' => 'weryfikacja',
    ],
    [
        ['id' => 3, 'invoice_number' => 'FV/2026/04/10'],
        ['id' => 4, 'invoice_number' => 'FV/2026/04/11'],
    ],
    ['id' => 11, 'status' => 'zaimportowana']
);

$assertions++;
if (count($errors) !== 1 || strpos($errors[0], 'unikalny') === false) {
    throw new RuntimeException('Expected duplicate invoice number error for supplier scope.');
}

$auditRows = $service->build_critical_audit_entries(
    [
        'id' => 11,
        'status' => 'weryfikacja',
        'project_id' => 9,
        'supplier_id' => 2,
        'net_amount' => '1200.00',
        'vat_amount' => '276.00',
        'gross_amount' => '1476.00',
    ],
    [
        'id' => 11,
        'status' => 'zatwierdzona',
        'project_id' => 9,
        'supplier_id' => 2,
        'net_amount' => '1200.00',
        'vat_amount' => '276.00',
        'gross_amount' => '1476.00',
    ],
    51
);

$assertions++;
if (count($auditRows) !== 1 || ($auditRows[0]['field_name'] ?? '') !== 'status') {
    throw new RuntimeException('Expected one audit row for status change.');
}

$assertions++;
if ((int) ($auditRows[0]['changed_by_user_id'] ?? 0) !== 51) {
    throw new RuntimeException('Expected changed_by_user_id to be propagated to audit rows.');
}

$created = $service->create_invoice(
    [
        'supplier_id' => 2,
        'project_id' => 9,
        'invoice_number' => 'FV/2026/04/12',
        'issue_date' => '2026-04-12',
        'net_amount' => 200.00,
        'vat_amount' => 46.00,
        'gross_amount' => 246.00,
        'status' => 'zaimportowana',
    ]
);

$assertions++;
if (($created['ok'] ?? false) !== true || (int) ($created['invoice_id'] ?? 0) <= 0) {
    throw new RuntimeException('Expected create_invoice() to persist valid invoice data.');
}

$updated = $service->update_invoice(
    (int) $created['invoice_id'],
    ['status' => 'weryfikacja'],
    77
);

$assertions++;
if (($updated['ok'] ?? false) !== true || count((array) ($updated['audit_rows'] ?? [])) < 1) {
    throw new RuntimeException('Expected update_invoice() to return audit rows for critical changes.');
}

$assertions++;
if (count($audit_repository->inserted_rows) < 1) {
    throw new RuntimeException('Expected update_invoice() to persist audit rows.');
}

$invalidRelationErrors = $service->validate_invoice_data(
    [
        'supplier_id' => 999,
        'project_id' => 999,
        'invoice_number' => 'FV/INVALID',
        'issue_date' => '2026-04-01',
        'net_amount' => 0,
        'vat_amount' => 0,
        'gross_amount' => 0,
        'status' => 'zaimportowana',
    ]
);

$assertions++;
if (count($invalidRelationErrors) < 2) {
    throw new RuntimeException('Expected relation errors for non-existing supplier and project.');
}

$invalidAmountsErrors = $service->validate_invoice_data(
    [
        'supplier_id' => 2,
        'project_id' => 9,
        'invoice_number' => 'FV/INVALID/2',
        'issue_date' => '2026-15-40',
        'net_amount' => -10,
        'vat_amount' => 5,
        'gross_amount' => 1,
        'status' => 'zaimportowana',
    ]
);

$assertions++;
if (count($invalidAmountsErrors) < 3) {
    throw new RuntimeException('Expected date, non-negative and gross-total validation errors.');
}

$invoice_repository->rows[999] = [
    'id' => 999,
    'supplier_id' => 2,
    'project_id' => 9,
    'invoice_number' => 'FV/KSEF/EXISTING',
    'issue_date' => '2026-04-13',
    'status' => 'zaimportowana',
    'source' => 'ksef',
    'ksef_reference_number' => 'KSEF-REF-123',
    'net_amount' => 10.00,
    'vat_amount' => 2.30,
    'gross_amount' => 12.30,
];

$ksefErrors = $service->validate_invoice_data(
    [
        'supplier_id' => 2,
        'project_id' => 9,
        'invoice_number' => 'FV/KSEF/NEW',
        'issue_date' => '2026-04-14',
        'status' => 'zaimportowana',
        'source' => 'ksef',
        'ksef_reference_number' => 'KSEF-REF-123',
        'net_amount' => 100.00,
        'vat_amount' => 23.00,
        'gross_amount' => 123.00,
    ]
);

$assertions++;
if (count($ksefErrors) < 1 || strpos(implode(' ', $ksefErrors), 'KSeF') === false) {
    throw new RuntimeException('Expected KSeF reference uniqueness validation error.');
}


$ksefProjectOptionalErrors = $service->validate_invoice_data(
    [
        'supplier_id' => 2,
        'project_id' => 0,
        'invoice_number' => 'FV/KSEF/NO-PROJECT',
        'issue_date' => '2026-04-14',
        'status' => 'zaimportowana',
        'source' => 'ksef',
        'ksef_reference_number' => 'KSEF-REF-NEW-OPTIONAL',
        'net_amount' => 100.00,
        'vat_amount' => 23.00,
        'gross_amount' => 123.00,
    ]
);

$assertions++;
if (count($ksefProjectOptionalErrors) !== 0) {
    throw new RuntimeException('Expected KSeF invoice validation to allow empty project_id during import.');
}

$ksefReferenceOptionalErrors = $service->validate_invoice_data(
    [
        'supplier_id' => 2,
        'project_id' => 0,
        'invoice_number' => 'FV/KSEF/NO-REF',
        'issue_date' => '2026-04-14',
        'status' => 'zaimportowana',
        'source' => 'ksef',
        'ksef_reference_number' => '',
        'net_amount' => 100.00,
        'vat_amount' => 23.00,
        'gross_amount' => 123.00,
    ]
);

$assertions++;
if (count($ksefReferenceOptionalErrors) !== 0) {
    throw new RuntimeException('Expected KSeF invoice validation to allow empty KSeF reference for manual imports.');
}

$relationLockErrors = $service->validate_invoice_data(
    [
        'id' => 1200,
        'supplier_id' => 3,
        'project_id' => 10,
        'invoice_number' => 'FV/LOCK/1',
        'issue_date' => '2026-04-14',
        'status' => 'przypisana',
        'net_amount' => 100,
        'vat_amount' => 23,
        'gross_amount' => 123,
    ],
    [],
    [
        'id' => 1200,
        'supplier_id' => 2,
        'project_id' => 9,
        'status' => 'przypisana',
    ]
);

$assertions++;
if (count($relationLockErrors) < 2) {
    throw new RuntimeException('Expected relation lock errors when changing supplier/project on przypisana status.');
}

echo "Assertions: {$assertions}\n";
echo "Cost invoice workflow service test passed.\n";
