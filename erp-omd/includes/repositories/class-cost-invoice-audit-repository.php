<?php

class ERP_OMD_Cost_Invoice_Audit_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_cost_invoice_audit';
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return int
     */
    public function insert_many(array $rows)
    {
        global $wpdb;

        $inserted = 0;
        foreach ($rows as $row) {
            $ok = $wpdb->insert(
                $this->table_name(),
                [
                    'invoice_id' => (int) ($row['invoice_id'] ?? 0),
                    'field_name' => (string) ($row['field_name'] ?? ''),
                    'before_value' => (string) ($row['before_value'] ?? ''),
                    'after_value' => (string) ($row['after_value'] ?? ''),
                    'changed_by_user_id' => (int) ($row['changed_by_user_id'] ?? 0),
                    'changed_at' => (string) ($row['changed_at'] ?? current_time('mysql')),
                ],
                ['%d', '%s', '%s', '%s', '%d', '%s']
            );

            if ($ok !== false) {
                $inserted++;
            }
        }

        return $inserted;
    }

    public function for_invoice($invoice_id)
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name()} WHERE invoice_id = %d ORDER BY changed_at DESC, id DESC",
                $invoice_id
            ),
            ARRAY_A
        );
    }
}
