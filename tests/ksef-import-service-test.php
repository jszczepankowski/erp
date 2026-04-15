<?php

declare(strict_types=1);

require_once __DIR__ . '/../erp-omd/includes/services/class-ksef-import-service.php';

class ERP_OMD_Cost_Invoice_Workflow_Service_Fake
{
    /** @var array<int,array<string,mixed>> */
    public $created_payloads = [];

    /** @var array<int,array<string,mixed>> */
    public $updated_payloads = [];

    public function create_invoice(array $payload)
    {
        $this->created_payloads[] = $payload;
        if (($payload['invoice_number'] ?? '') === 'FAIL') {
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

$workflow = new ERP_OMD_Cost_Invoice_Workflow_Service_Fake();
$service = new ERP_OMD_KSeF_Import_Service($workflow);
$assertions = 0;

$result = $service->import_documents([
    ['supplier_id' => 1, 'project_id' => 2, 'invoice_number' => 'OK/1', 'issue_date' => '2026-04-14', 'gross_amount' => 123.45],
    ['supplier_id' => 1, 'project_id' => 2, 'invoice_number' => 'FAIL', 'issue_date' => '2026-04-14', 'gross_amount' => 50.00],
], 91);

$assertions++;
if (($result['total'] ?? 0) !== 2 || ($result['imported'] ?? 0) !== 1 || ($result['failed'] ?? 0) !== 1) {
    throw new RuntimeException('Expected import summary for 1 success and 1 failure.');
}

$assertions++;
if (($workflow->created_payloads[0]['status'] ?? '') !== 'zaimportowana' || ($workflow->created_payloads[0]['source'] ?? '') !== 'ksef') {
    throw new RuntimeException('Expected KSeF import to enforce zaimportowana status and ksef source.');
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
