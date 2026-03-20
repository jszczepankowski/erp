<?php

class ERP_OMD_Estimate_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_estimates';
    }

    public function all()
    {
        global $wpdb;

        $clients_table = $wpdb->prefix . 'erp_omd_clients';
        $projects_table = $wpdb->prefix . 'erp_omd_projects';

        return $wpdb->get_results(
            "SELECT e.*, c.name AS client_name, p.id AS project_id, p.name AS project_name
            FROM {$this->table_name()} e
            INNER JOIN {$clients_table} c ON c.id = e.client_id
            LEFT JOIN {$projects_table} p ON p.estimate_id = e.id
            ORDER BY e.created_at DESC, e.id DESC",
            ARRAY_A
        );
    }

    public function find($id)
    {
        global $wpdb;

        $clients_table = $wpdb->prefix . 'erp_omd_clients';
        $projects_table = $wpdb->prefix . 'erp_omd_projects';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT e.*, c.name AS client_name, p.id AS project_id, p.name AS project_name
                FROM {$this->table_name()} e
                INNER JOIN {$clients_table} c ON c.id = e.client_id
                LEFT JOIN {$projects_table} p ON p.estimate_id = e.id
                WHERE e.id = %d",
                $id
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
                'client_id' => $data['client_id'],
                'status' => $data['status'],
                'accepted_by_user_id' => $data['accepted_by_user_id'] ?: null,
                'accepted_at' => $data['accepted_at'] ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%s', '%d', '%s', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public function update($id, array $data)
    {
        global $wpdb;

        return $wpdb->update(
            $this->table_name(),
            [
                'client_id' => $data['client_id'],
                'status' => $data['status'],
                'accepted_by_user_id' => $data['accepted_by_user_id'] ?: null,
                'accepted_at' => $data['accepted_at'] ?: null,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%d', '%s', '%d', '%s', '%s'],
            ['%d']
        );
    }

    public function mark_accepted($id, $user_id)
    {
        global $wpdb;

        return $wpdb->update(
            $this->table_name(),
            [
                'status' => 'zaakceptowany',
                'accepted_by_user_id' => $user_id,
                'accepted_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%s', '%d', '%s', '%s'],
            ['%d']
        );
    }

    public function delete($id)
    {
        global $wpdb;

        return $wpdb->delete($this->table_name(), ['id' => $id], ['%d']);
    }
}
