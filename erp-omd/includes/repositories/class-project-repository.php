<?php

class ERP_OMD_Project_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_projects';
    }

    public function all()
    {
        global $wpdb;

        $clients_table = $wpdb->prefix . 'erp_omd_clients';
        $employees_table = $wpdb->prefix . 'erp_omd_employees';
        $users_table = $wpdb->users;

        return $wpdb->get_results(
            "SELECT p.*, c.name AS client_name, u.user_login AS manager_login
            FROM {$this->table_name()} p
            INNER JOIN {$clients_table} c ON c.id = p.client_id
            LEFT JOIN {$employees_table} e ON e.id = p.manager_id
            LEFT JOIN {$users_table} u ON u.ID = e.user_id
            ORDER BY p.created_at DESC, p.id DESC",
            ARRAY_A
        );
    }

    public function find($id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE id = %d", $id), ARRAY_A);
    }

    public function ids_managed_by_employee($employee_id)
    {
        global $wpdb;

        return array_map(
            'intval',
            $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT id
                    FROM {$this->table_name()}
                    WHERE manager_id = %d
                    ORDER BY id ASC",
                    $employee_id
                )
            )
        );
    }

    public function find_by_estimate_id($estimate_id)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name()} WHERE estimate_id = %d LIMIT 1",
                $estimate_id
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
                'name' => $data['name'],
                'billing_type' => $data['billing_type'],
                'budget' => $data['budget'],
                'retainer_monthly_fee' => $data['retainer_monthly_fee'],
                'status' => $data['status'],
                'start_date' => $data['start_date'] ?: null,
                'end_date' => $data['end_date'] ?: null,
                'manager_id' => $data['manager_id'] ?: null,
                'estimate_id' => $data['estimate_id'] ?: null,
                'brief' => $data['brief'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s']
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
                'name' => $data['name'],
                'billing_type' => $data['billing_type'],
                'budget' => $data['budget'],
                'retainer_monthly_fee' => $data['retainer_monthly_fee'],
                'status' => $data['status'],
                'start_date' => $data['start_date'] ?: null,
                'end_date' => $data['end_date'] ?: null,
                'manager_id' => $data['manager_id'] ?: null,
                'estimate_id' => $data['estimate_id'] ?: null,
                'brief' => $data['brief'],
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%d', '%d', '%s', '%s'],
            ['%d']
        );
    }

    public function deactivate($id)
    {
        return $this->set_status($id, 'inactive');
    }

    public function set_status($id, $status)
    {
        global $wpdb;

        return $wpdb->update(
            $this->table_name(),
            ['status' => $status, 'updated_at' => current_time('mysql')],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );
    }
}
