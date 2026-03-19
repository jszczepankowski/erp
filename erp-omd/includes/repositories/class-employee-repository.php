<?php

class ERP_OMD_Employee_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_employees';
    }

    public function pivot_table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_employee_roles';
    }

    public function all()
    {
        global $wpdb;

        $users_table = $wpdb->users;
        $roles_table = $wpdb->prefix . 'erp_omd_roles';

        return $wpdb->get_results(
            "SELECT e.*, u.user_login, u.user_email, r.name AS default_role_name
            FROM {$this->table_name()} e
            INNER JOIN {$users_table} u ON u.ID = e.user_id
            LEFT JOIN {$roles_table} r ON r.id = e.default_role_id
            ORDER BY u.user_login ASC",
            ARRAY_A
        );
    }

    public function find($id)
    {
        global $wpdb;

        $employee = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE id = %d", $id), ARRAY_A);
        if (! $employee) {
            return null;
        }

        $employee['role_ids'] = $this->role_ids($id);
        return $employee;
    }

    public function create(array $data)
    {
        global $wpdb;

        $now = current_time('mysql');
        $wpdb->insert(
            $this->table_name(),
            [
                'user_id' => $data['user_id'],
                'default_role_id' => $data['default_role_id'] ?: null,
                'account_type' => $data['account_type'],
                'status' => $data['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );

        $employee_id = (int) $wpdb->insert_id;
        $this->sync_roles($employee_id, $data['role_ids']);

        return $employee_id;
    }

    public function update($id, array $data)
    {
        global $wpdb;

        $wpdb->update(
            $this->table_name(),
            [
                'user_id' => $data['user_id'],
                'default_role_id' => $data['default_role_id'] ?: null,
                'account_type' => $data['account_type'],
                'status' => $data['status'],
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%d', '%d', '%s', '%s', '%s'],
            ['%d']
        );

        $this->sync_roles($id, $data['role_ids']);
    }

    public function deactivate($id)
    {
        global $wpdb;

        return $wpdb->update(
            $this->table_name(),
            ['status' => 'inactive', 'updated_at' => current_time('mysql')],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );
    }

    public function user_exists($user_id, $exclude_id = null)
    {
        global $wpdb;

        if ($exclude_id) {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name()} WHERE user_id = %d AND id != %d", $user_id, $exclude_id));
        } else {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name()} WHERE user_id = %d", $user_id));
        }

        return (int) $count > 0;
    }

    public function role_ids($employee_id)
    {
        global $wpdb;

        return array_map(
            'intval',
            $wpdb->get_col($wpdb->prepare("SELECT role_id FROM {$this->pivot_table_name()} WHERE employee_id = %d ORDER BY role_id ASC", $employee_id))
        );
    }

    public function sync_roles($employee_id, array $role_ids)
    {
        global $wpdb;

        $wpdb->delete($this->pivot_table_name(), ['employee_id' => $employee_id], ['%d']);
        $role_ids = array_values(array_unique(array_filter(array_map('intval', $role_ids))));

        foreach ($role_ids as $role_id) {
            $wpdb->insert(
                $this->pivot_table_name(),
                [
                    'employee_id' => $employee_id,
                    'role_id' => $role_id,
                    'assigned_at' => current_time('mysql'),
                ],
                ['%d', '%d', '%s']
            );
        }
    }
}
