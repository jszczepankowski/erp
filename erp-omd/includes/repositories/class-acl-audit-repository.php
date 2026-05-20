<?php

class ERP_OMD_Acl_Audit_Repository
{
    private function table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'erp_omd_acl_audit';
    }

    public function insert(array $row)
    {
        global $wpdb;
        $wpdb->insert($this->table_name(), [
            'event_id' => sanitize_text_field((string) ($row['event_id'] ?? '')),
            'actor_user_id' => (int) ($row['actor_user_id'] ?? 0),
            'target_user_id' => (int) ($row['target_user_id'] ?? 0),
            'changed_at' => sanitize_text_field((string) ($row['changed_at'] ?? current_time('mysql'))),
            'change_type' => sanitize_key((string) ($row['change_type'] ?? 'acl_override')),
            'before_capability_overrides' => wp_json_encode((array) ($row['before']['capability_overrides'] ?? [])),
            'after_capability_overrides' => wp_json_encode((array) ($row['after']['capability_overrides'] ?? [])),
            'before_menu_overrides' => wp_json_encode((array) ($row['before']['menu_overrides'] ?? [])),
            'after_menu_overrides' => wp_json_encode((array) ($row['after']['menu_overrides'] ?? [])),
        ]);
    }

    public function all()
    {
        global $wpdb;
        return (array) $wpdb->get_results("SELECT * FROM {$this->table_name()} ORDER BY changed_at DESC, id DESC", ARRAY_A);
    }
}
