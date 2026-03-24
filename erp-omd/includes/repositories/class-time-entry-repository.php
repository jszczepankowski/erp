<?php

class ERP_OMD_Time_Entry_Repository
{
    public function table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'erp_omd_time_entries';
    }

    public function all(array $filters = [])
    {
        global $wpdb;

        $employees_table = $wpdb->prefix . 'erp_omd_employees';
        $projects_table = $wpdb->prefix . 'erp_omd_projects';
        $roles_table = $wpdb->prefix . 'erp_omd_roles';
        $users_table = $wpdb->users;

        $where = ['1=1'];
        $params = [];

        if (! empty($filters['employee_id'])) {
            $where[] = 't.employee_id = %d';
            $params[] = (int) $filters['employee_id'];
        }
        if (! empty($filters['project_id'])) {
            $where[] = 't.project_id = %d';
            $params[] = (int) $filters['project_id'];
        }
        if (! empty($filters['status'])) {
            $where[] = 't.status = %s';
            $params[] = $filters['status'];
        }
        if (! empty($filters['entry_date'])) {
            $where[] = 't.entry_date = %s';
            $params[] = $filters['entry_date'];
        }

        $sql = "SELECT t.*, eu.user_login AS employee_login, p.client_id AS client_id, p.name AS project_name, r.name AS role_name,
                au.user_login AS approved_by_login
                FROM {$this->table_name()} t
                INNER JOIN {$employees_table} e ON e.id = t.employee_id
                INNER JOIN {$users_table} eu ON eu.ID = e.user_id
                INNER JOIN {$projects_table} p ON p.id = t.project_id
                INNER JOIN {$roles_table} r ON r.id = t.role_id
                LEFT JOIN {$users_table} au ON au.ID = t.approved_by_user_id
                WHERE " . implode(' AND ', $where) . ' ORDER BY t.entry_date DESC, t.id DESC';

        if ($params) {
            return $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
        }

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public function find($id)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE id = %d", $id), ARRAY_A);
    }

    public function create(array $data)
    {
        global $wpdb;

        $now = current_time('mysql');
        $wpdb->insert(
            $this->table_name(),
            [
                'employee_id' => $data['employee_id'],
                'project_id' => $data['project_id'],
                'role_id' => $data['role_id'],
                'hours' => $data['hours'],
                'entry_date' => $data['entry_date'],
                'description' => $data['description'],
                'status' => $data['status'],
                'rate_snapshot' => $data['rate_snapshot'],
                'cost_snapshot' => $data['cost_snapshot'],
                'created_by_user_id' => $data['created_by_user_id'],
                'approved_by_user_id' => $data['approved_by_user_id'] ?: null,
                'approved_at' => $data['approved_at'] ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%d', '%d', '%f', '%s', '%s', '%s', '%f', '%f', '%d', '%d', '%s', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public function update($id, array $data)
    {
        global $wpdb;

        return $wpdb->update(
            $this->table_name(),
            [
                'employee_id' => $data['employee_id'],
                'project_id' => $data['project_id'],
                'role_id' => $data['role_id'],
                'hours' => $data['hours'],
                'entry_date' => $data['entry_date'],
                'description' => $data['description'],
                'status' => $data['status'],
                'rate_snapshot' => $data['rate_snapshot'],
                'cost_snapshot' => $data['cost_snapshot'],
                'approved_by_user_id' => $data['approved_by_user_id'] ?: null,
                'approved_at' => $data['approved_at'] ?: null,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%d', '%d', '%d', '%f', '%s', '%s', '%s', '%f', '%f', '%d', '%s', '%s'],
            ['%d']
        );
    }

    public function delete($id)
    {
        global $wpdb;
        return $wpdb->delete($this->table_name(), ['id' => $id], ['%d']);
    }


    public function count_for_project_by_statuses($project_id, array $statuses)
    {
        global $wpdb;

        if ($project_id <= 0 || $statuses === []) {
            return 0;
        }

        $placeholders = implode(', ', array_fill(0, count($statuses), '%s'));
        $params = array_merge([(int) $project_id], array_values($statuses));

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name()} WHERE project_id = %d AND status IN ({$placeholders})",
                ...$params
            )
        );
    }

    public function duplicate_exists($employee_id, $project_id, $role_id, $hours, $exclude_id = null)
    {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$this->table_name()} WHERE employee_id = %d AND project_id = %d AND role_id = %d AND hours = %f";
        $params = [(int) $employee_id, (int) $project_id, (int) $role_id, (float) $hours];
        if ($exclude_id) {
            $sql .= ' AND id != %d';
            $params[] = (int) $exclude_id;
        }

        return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params)) > 0;
    }
}
