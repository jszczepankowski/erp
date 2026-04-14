<?php

class ERP_OMD_Cost_Invoice_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_cost_invoices';
    }

    public function find($id)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE id = %d", $id),
            ARRAY_A
        );
    }

    public function for_supplier($supplier_id)
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name()} WHERE supplier_id = %d ORDER BY issue_date DESC, id DESC",
                $supplier_id
            ),
            ARRAY_A
        );
    }

    public function create(array $data)
    {
        global $wpdb;

        $now = current_time('mysql');
        $wpdb->insert(
            $this->table_name(),
            [
                'supplier_id' => (int) ($data['supplier_id'] ?? 0),
                'project_id' => (int) ($data['project_id'] ?? 0),
                'invoice_number' => (string) ($data['invoice_number'] ?? ''),
                'issue_date' => (string) ($data['issue_date'] ?? ''),
                'status' => (string) ($data['status'] ?? 'zaimportowana'),
                'net_amount' => (float) ($data['net_amount'] ?? 0),
                'vat_amount' => (float) ($data['vat_amount'] ?? 0),
                'gross_amount' => (float) ($data['gross_amount'] ?? 0),
                'source' => (string) ($data['source'] ?? 'manual'),
                'ksef_reference_number' => (string) ($data['ksef_reference_number'] ?? ''),
                'created_by_user_id' => (int) ($data['created_by_user_id'] ?? 0),
                'updated_by_user_id' => (int) ($data['updated_by_user_id'] ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%d', '%d', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public function update($id, array $data)
    {
        global $wpdb;

        return $wpdb->update(
            $this->table_name(),
            [
                'supplier_id' => (int) ($data['supplier_id'] ?? 0),
                'project_id' => (int) ($data['project_id'] ?? 0),
                'invoice_number' => (string) ($data['invoice_number'] ?? ''),
                'issue_date' => (string) ($data['issue_date'] ?? ''),
                'status' => (string) ($data['status'] ?? 'zaimportowana'),
                'net_amount' => (float) ($data['net_amount'] ?? 0),
                'vat_amount' => (float) ($data['vat_amount'] ?? 0),
                'gross_amount' => (float) ($data['gross_amount'] ?? 0),
                'source' => (string) ($data['source'] ?? 'manual'),
                'ksef_reference_number' => (string) ($data['ksef_reference_number'] ?? ''),
                'updated_by_user_id' => (int) ($data['updated_by_user_id'] ?? 0),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => (int) $id],
            ['%d', '%d', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%d', '%s'],
            ['%d']
        );
    }
}
