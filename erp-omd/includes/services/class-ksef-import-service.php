<?php

class ERP_OMD_KSeF_Import_Service
{
    /** @var mixed */
    private $workflow_service;

    /**
     * @param mixed $workflow_service
     */
    public function __construct($workflow_service)
    {
        $this->workflow_service = $workflow_service;
    }

    /**
     * @param array<int,array<string,mixed>> $documents
     * @param int $user_id
     * @return array<string,mixed>
     */
    public function import_documents(array $documents, $user_id)
    {
        $imported = 0;
        $errors = [];

        foreach ($documents as $index => $document) {
            $payload = $this->map_ksef_document_to_invoice($document, (int) $user_id);
            $result = $this->workflow_service->create_invoice($payload);
            if ((bool) ($result['ok'] ?? false)) {
                $imported++;
                continue;
            }

            $errors[] = [
                'index' => $index,
                'invoice_number' => (string) ($payload['invoice_number'] ?? ''),
                'errors' => (array) ($result['errors'] ?? []),
            ];
        }

        return [
            'total' => count($documents),
            'imported' => $imported,
            'failed' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * @param int $invoice_id
     * @param array<string,mixed> $moderation_payload
     * @param int $user_id
     * @return array<string,mixed>
     */
    public function moderate_imported_invoice($invoice_id, array $moderation_payload, $user_id)
    {
        $payload = [
            'status' => (string) ($moderation_payload['status'] ?? ''),
            'supplier_id' => (int) ($moderation_payload['supplier_id'] ?? 0),
            'project_id' => (int) ($moderation_payload['project_id'] ?? 0),
            'invoice_number' => (string) ($moderation_payload['invoice_number'] ?? ''),
            'issue_date' => (string) ($moderation_payload['issue_date'] ?? ''),
            'net_amount' => (float) ($moderation_payload['net_amount'] ?? 0),
            'vat_amount' => (float) ($moderation_payload['vat_amount'] ?? 0),
            'gross_amount' => (float) ($moderation_payload['gross_amount'] ?? 0),
            'source' => 'ksef',
            'updated_by_user_id' => (int) $user_id,
        ];

        return $this->workflow_service->update_invoice((int) $invoice_id, $payload, (int) $user_id);
    }

    /**
     * @param array<string,mixed> $document
     * @param int $user_id
     * @return array<string,mixed>
     */
    private function map_ksef_document_to_invoice(array $document, $user_id)
    {
        return [
            'supplier_id' => (int) ($document['supplier_id'] ?? 0),
            'project_id' => (int) ($document['project_id'] ?? 0),
            'invoice_number' => (string) ($document['invoice_number'] ?? ''),
            'issue_date' => (string) ($document['issue_date'] ?? ''),
            'status' => 'zaimportowana',
            'net_amount' => (float) ($document['net_amount'] ?? 0),
            'vat_amount' => (float) ($document['vat_amount'] ?? 0),
            'gross_amount' => (float) ($document['gross_amount'] ?? 0),
            'source' => 'ksef',
            'ksef_reference_number' => (string) ($document['ksef_reference_number'] ?? ''),
            'created_by_user_id' => (int) $user_id,
            'updated_by_user_id' => (int) $user_id,
        ];
    }
}
