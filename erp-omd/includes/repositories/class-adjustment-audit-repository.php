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
                'field_name' => (string) ($data['field_name'] ?? ''),
                'old_value' => isset($data['old_value']) ? wp_json_encode($data['old_value']) : null,
                'new_value' => isset($data['new_value']) ? wp_json_encode($data['new_value']) : null,
                'reason' => (string) ($data['reason'] ?? ''),
                'adjustment_type' => (string) ($data['adjustment_type'] ?? 'STANDARD'),
                'changed_by_user_id' => (int) ($data['changed_by_user_id'] ?? 0) ?: null,
                'changed_at' => current_time('mysql'),
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public function all(array $filters = [])
    {
        global $wpdb;

        $where = [];
        $params = [];
        if (! empty($filters['month'])) {
            $where[] = 'month = %s';
            $params[] = (string) $filters['month'];
        }
        if (! empty($filters['entity_type'])) {
            $where[] = 'entity_type = %s';
            $params[] = (string) $filters['entity_type'];
        }
        if (! empty($filters['entity_id'])) {
            $where[] = 'entity_id = %d';
            $params[] = (int) $filters['entity_id'];
        }

        $sql = "SELECT * FROM {$this->table_name()}";
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY changed_at DESC, id DESC';

        if ($params !== []) {
            $sql = $wpdb->prepare($sql, ...$params);
        }

        return $wpdb->get_results($sql, ARRAY_A);
    }
}
