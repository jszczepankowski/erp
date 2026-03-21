<?php

class ERP_OMD_Project_Request_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_project_requests';
    }

    public function all()
    {
        global $wpdb;

        $clients_table = $wpdb->prefix . 'erp_omd_clients';
        $employees_table = $wpdb->prefix . 'erp_omd_employees';
        $projects_table = $wpdb->prefix . 'erp_omd_projects';
        $users_table = $wpdb->users;

        return $wpdb->get_results(
            "SELECT pr.*,
                c.name AS client_name,
                requester_user.user_login AS requester_login,
                preferred_manager_user.user_login AS preferred_manager_login,
                converted_project.name AS converted_project_name
            FROM {$this->table_name()} pr
            INNER JOIN {$clients_table} c ON c.id = pr.client_id
            LEFT JOIN {$users_table} requester_user ON requester_user.ID = pr.requester_user_id
            LEFT JOIN {$employees_table} preferred_manager_employee ON preferred_manager_employee.id = pr.preferred_manager_id
            LEFT JOIN {$users_table} preferred_manager_user ON preferred_manager_user.ID = preferred_manager_employee.user_id
            LEFT JOIN {$projects_table} converted_project ON converted_project.id = pr.converted_project_id
            ORDER BY pr.created_at DESC, pr.id DESC",
            ARRAY_A
        );
    }

    public function find($id)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE id = %d", $id),
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
                'requester_user_id' => (int) $data['requester_user_id'],
                'requester_employee_id' => (int) $data['requester_employee_id'],
                'client_id' => (int) $data['client_id'],
                'project_name' => $data['project_name'],
                'billing_type' => $data['billing_type'],
                'preferred_manager_id' => (int) $data['preferred_manager_id'] ?: null,
                'estimate_id' => (int) $data['estimate_id'] ?: null,
                'brief' => $data['brief'],
                'status' => $data['status'],
                'reviewed_by_user_id' => (int) ($data['reviewed_by_user_id'] ?? 0) ?: null,
                'reviewed_at' => $data['reviewed_at'] ?? null,
                'converted_project_id' => (int) ($data['converted_project_id'] ?? 0) ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public function update($id, array $data)
    {
        global $wpdb;

        return $wpdb->update(
            $this->table_name(),
            [
                'client_id' => (int) $data['client_id'],
                'project_name' => $data['project_name'],
                'billing_type' => $data['billing_type'],
                'preferred_manager_id' => (int) $data['preferred_manager_id'] ?: null,
                'estimate_id' => (int) $data['estimate_id'] ?: null,
                'brief' => $data['brief'],
                'status' => $data['status'],
                'reviewed_by_user_id' => (int) ($data['reviewed_by_user_id'] ?? 0) ?: null,
                'reviewed_at' => $data['reviewed_at'] ?? null,
                'converted_project_id' => (int) ($data['converted_project_id'] ?? 0) ?: null,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => (int) $id],
            ['%d', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%d', '%s'],
            ['%d']
        );
    }

    public function update_status($id, $status, $reviewed_by_user_id = 0)
    {
        $existing = $this->find($id);
        if (! $existing) {
            return false;
        }

        return $this->update(
            $id,
            array_merge(
                $existing,
                [
                    'status' => $status,
                    'reviewed_by_user_id' => $reviewed_by_user_id ?: 0,
                    'reviewed_at' => $reviewed_by_user_id ? current_time('mysql') : null,
                ]
            )
        );
    }

    public function mark_converted($id, $project_id, $reviewed_by_user_id = 0)
    {
        $existing = $this->find($id);
        if (! $existing) {
            return false;
        }

        return $this->update(
            $id,
            array_merge(
                $existing,
                [
                    'status' => 'converted',
                    'reviewed_by_user_id' => $reviewed_by_user_id ?: 0,
                    'reviewed_at' => current_time('mysql'),
                    'converted_project_id' => (int) $project_id,
                ]
            )
        );
    }
}
