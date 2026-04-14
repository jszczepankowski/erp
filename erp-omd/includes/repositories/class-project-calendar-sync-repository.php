<?php

class ERP_OMD_Project_Calendar_Sync_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_project_calendar_sync';
    }

    public function find_by_project_id($project_id)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE project_id = %d LIMIT 1", (int) $project_id),
            ARRAY_A
        );
    }

    public function upsert(array $data)
    {
        global $wpdb;

        $existing = $this->find_by_project_id((int) ($data['project_id'] ?? 0));
        $payload = [
            'project_id' => (int) ($data['project_id'] ?? 0),
            'range_event_id' => (string) ($data['range_event_id'] ?? ''),
            'deadline_event_id' => (string) ($data['deadline_event_id'] ?? ''),
            'sync_status' => (string) ($data['sync_status'] ?? 'pending'),
            'last_error' => (string) ($data['last_error'] ?? ''),
            'last_synced_at' => ($data['last_synced_at'] ?? '') !== '' ? (string) $data['last_synced_at'] : null,
            'updated_at' => current_time('mysql'),
        ];

        if (! $existing) {
            $payload['created_at'] = current_time('mysql');
            $wpdb->insert(
                $this->table_name(),
                $payload,
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );

            return (int) $wpdb->insert_id;
        }

        $wpdb->update(
            $this->table_name(),
            $payload,
            ['project_id' => (int) $data['project_id']],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );

        return (int) ($existing['id'] ?? 0);
    }

    public function delete_by_project_id($project_id)
    {
        global $wpdb;

        return $wpdb->delete($this->table_name(), ['project_id' => (int) $project_id], ['%d']);
    }

    public function all_pending()
    {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$this->table_name()} WHERE sync_status IN ('pending','error') ORDER BY updated_at ASC",
            ARRAY_A
        );
    }
}
