<?php

class ERP_OMD_Cost_Invoice_Repository
{
    public function list(array $filters = [])
    {
        global $wpdb;

        $where = [];
        $params = [];

        $supplier_id = (int) ($filters['supplier_id'] ?? 0);
        if ($supplier_id > 0) {
            $where[] = 'supplier_id = %d';
            $params[] = $supplier_id;
        }

        $project_id = (int) ($filters['project_id'] ?? 0);
        if ($project_id > 0) {
            $where[] = 'project_id = %d';
            $params[] = $project_id;
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = 'status = %s';
            $params[] = $status;
        }

        $sql = "SELECT * FROM {$this->table_name()}";
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY issue_date DESC, id DESC';
        if ($params !== []) {
            $sql = $wpdb->prepare($sql, ...$params);
        }

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public function project_supplier_pairs()
    {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT project_id, supplier_id, COUNT(*) AS invoices_count, SUM(gross_amount) AS gross_total
            FROM {$this->table_name()}
            GROUP BY project_id, supplier_id
            ORDER BY project_id ASC, supplier_id ASC",
            ARRAY_A
        );
    }

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

    public function find_by_ksef_reference($ksef_reference_number)
    {
        global $wpdb;

        $ksef_reference_number = trim((string) $ksef_reference_number);
        if ($ksef_reference_number === '') {
            return null;
        }

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name()} WHERE ksef_reference_number = %s ORDER BY id DESC LIMIT 1",
                $ksef_reference_number
            ),
            ARRAY_A
        );
    }


    public function find_by_supplier_and_invoice_number($supplier_id, $invoice_number)
    {
        global $wpdb;

        $supplier_id = (int) $supplier_id;
        $invoice_number = trim((string) $invoice_number);

        if ($supplier_id <= 0 || $invoice_number === '') {
            return null;
        }

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name()} WHERE supplier_id = %d AND LOWER(invoice_number) = LOWER(%s) ORDER BY id DESC LIMIT 1",
                $supplier_id,
                $invoice_number
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
                'description' => (string) ($data['description'] ?? ''),
                'created_by_user_id' => (int) ($data['created_by_user_id'] ?? 0),
                'updated_by_user_id' => (int) ($data['updated_by_user_id'] ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%d', '%d', '%s', '%s']
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
                'description' => (string) ($data['description'] ?? ''),
                'updated_by_user_id' => (int) ($data['updated_by_user_id'] ?? 0),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => (int) $id],
            ['%d', '%d', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%d', '%s'],
            ['%d']
        );
    }

    public function delete($id)
    {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name(),
            ['id' => (int) $id],
            ['%d']
        );
    }
}
