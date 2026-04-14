<?php

declare(strict_types=1);

if (! function_exists('__')) {
    function __($text)
    {
        return $text;
    }
}

require_once __DIR__ . '/../erp-omd/includes/services/class-cost-invoice-workflow-service.php';

$service = new ERP_OMD_Cost_Invoice_Workflow_Service();
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

echo "Assertions: {$assertions}\n";
echo "Cost invoice workflow service test passed.\n";
