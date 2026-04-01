<?php

class ERP_OMD_Adjustment_Audit_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_adjustment_audit';
    }

    public function create(array $data)
    {
        global $wpdb;

        $wpdb->insert(
            $this->table_name(),
            [
                'month' => (string) ($data['month'] ?? ''),
                'entity_type' => (string) ($data['entity_type'] ?? ''),
                'entity_id' => (int) ($data['entity_id'] ?? 0),
                'field_name' => (string) ($data['field_name'] ?? 'payload'),
                'old_value' => isset($data['old_value']) ? (string) $data['old_value'] : null,
                'new_value' => isset($data['new_value']) ? (string) $data['new_value'] : null,
                'reason' => (string) ($data['reason'] ?? ''),
                'adjustment_type' => (string) ($data['adjustment_type'] ?? 'STANDARD'),
                'changed_by' => (int) ($data['changed_by'] ?? 0),
                'changed_at' => (string) ($data['changed_at'] ?? current_time('mysql')),
            ],
            ['%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public function find($id)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE id = %d", (int) $id),
            ARRAY_A
        );
    }

    public function all(array $filters = [])
    {
        global $wpdb;

        $where = ['1=1'];
        $params = [];

        if (! empty($filters['month'])) {
            $where[] = 'month = %s';
            $params[] = (string) $filters['month'];
        }
        if (! empty($filters['entity_type'])) {
            $where[] = 'entity_type = %s';
            $params[] = (string) $filters['entity_type'];
        }

        $sql = "SELECT * FROM {$this->table_name()} WHERE " . implode(' AND ', $where) . ' ORDER BY changed_at DESC, id DESC';
        if ($params === []) {
            return $wpdb->get_results($sql, ARRAY_A);
        }

        return $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
    }
}
