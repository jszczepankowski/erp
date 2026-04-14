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

$invoice_repository = new ERP_OMD_Cost_Invoice_Repository_Fake();
$audit_repository = new ERP_OMD_Cost_Invoice_Audit_Repository_Fake();
$service = new ERP_OMD_Cost_Invoice_Workflow_Service($invoice_repository, $audit_repository);
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
        'status' => 'zaimportowana',
    ]
);

$assertions++;
if (($created['ok'] ?? false) !== true || (int) ($created['invoice_id'] ?? 0) <= 0) {
    throw new RuntimeException('Expected create_invoice() to persist valid invoice data.');
}

$updated = $service->update_invoice(
    (int) $created['invoice_id'],
    ['status' => 'weryfikacja', 'gross_amount' => '100.00'],
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

echo "Assertions: {$assertions}\n";
echo "Cost invoice workflow service test passed.\n";
