<?php

class ERP_OMD_Estimate_Audit_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_estimate_audit';
    }

    public function for_estimate($estimate_id)
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name()} WHERE estimate_id = %d ORDER BY created_at DESC, id DESC",
                $estimate_id
            ),
            ARRAY_A
        );
    }

    public function log($estimate_id, $action, array $details = [], $changed_by_user_id = null)
    {
        global $wpdb;

        $wpdb->insert(
            $this->table_name(),
            [
                'estimate_id' => (int) $estimate_id,
                'action' => (string) $action,
                'changed_by_user_id' => $changed_by_user_id ?: null,
                'details' => function_exists('wp_json_encode') ? wp_json_encode($details) : json_encode($details),
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%d', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }
}
