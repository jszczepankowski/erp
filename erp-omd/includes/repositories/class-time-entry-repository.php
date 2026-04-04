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
        return $this->find_paged($filters, 1000000, 0);
    }

    public function find_paged(array $filters = [], $limit = 100, $offset = 0)
    {
        global $wpdb;

        $employees_table = $wpdb->prefix . 'erp_omd_employees';
        $projects_table = $wpdb->prefix . 'erp_omd_projects';
        $clients_table = $wpdb->prefix . 'erp_omd_clients';
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
        if (! empty($filters['client_id'])) {
            $where[] = 'p.client_id = %d';
            $params[] = (int) $filters['client_id'];
        }
        if (! empty($filters['status'])) {
            $where[] = 't.status = %s';
            $params[] = $filters['status'];
        }
        if (! empty($filters['entry_date'])) {
            $where[] = 't.entry_date = %s';
            $params[] = $filters['entry_date'];
        }
        if (! empty($filters['month']) && preg_match('/^\d{4}-\d{2}$/', (string) $filters['month']) === 1) {
            $where[] = 't.entry_date LIKE %s';
            $params[] = (string) $filters['month'] . '%';
        }
        if (! empty($filters['entry_date_from'])) {
            $where[] = 't.entry_date >= %s';
            $params[] = (string) $filters['entry_date_from'];
        }
        if (! empty($filters['entry_date_to'])) {
            $where[] = 't.entry_date <= %s';
            $params[] = (string) $filters['entry_date_to'];
        }

        $limit = max(1, (int) $limit);
        $offset = max(0, (int) $offset);

        $sql = "SELECT t.*, eu.user_login AS employee_login, p.client_id AS client_id, c.name AS client_name, p.name AS project_name, r.name AS role_name,
                au.user_login AS approved_by_login
                FROM {$this->table_name()} t
                INNER JOIN {$employees_table} e ON e.id = t.employee_id
                INNER JOIN {$users_table} eu ON eu.ID = e.user_id
                INNER JOIN {$projects_table} p ON p.id = t.project_id
                LEFT JOIN {$clients_table} c ON c.id = p.client_id
                INNER JOIN {$roles_table} r ON r.id = t.role_id
                LEFT JOIN {$users_table} au ON au.ID = t.approved_by_user_id
                WHERE " . implode(' AND ', $where) . ' ORDER BY t.entry_date DESC, t.id DESC LIMIT %d OFFSET %d';

        $params[] = $limit;
        $params[] = $offset;

        return $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
    }

    public function count_filtered(array $filters = [])
    {
        global $wpdb;

        $projects_table = $wpdb->prefix . 'erp_omd_projects';
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
        if (! empty($filters['client_id'])) {
            $where[] = 'p.client_id = %d';
            $params[] = (int) $filters['client_id'];
        }
        if (! empty($filters['status'])) {
            $where[] = 't.status = %s';
            $params[] = (string) $filters['status'];
        }
        if (! empty($filters['entry_date'])) {
            $where[] = 't.entry_date = %s';
            $params[] = (string) $filters['entry_date'];
        }
        if (! empty($filters['month']) && preg_match('/^\d{4}-\d{2}$/', (string) $filters['month']) === 1) {
            $where[] = 't.entry_date LIKE %s';
            $params[] = (string) $filters['month'] . '%';
        }
        if (! empty($filters['entry_date_from'])) {
            $where[] = 't.entry_date >= %s';
            $params[] = (string) $filters['entry_date_from'];
        }
        if (! empty($filters['entry_date_to'])) {
            $where[] = 't.entry_date <= %s';
            $params[] = (string) $filters['entry_date_to'];
        }

        $sql = "SELECT COUNT(*) FROM {$this->table_name()} t INNER JOIN {$projects_table} p ON p.id = t.project_id WHERE " . implode(' AND ', $where);

        if ($params) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
        }

        return (int) $wpdb->get_var($sql);
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

        if (function_exists('erp_omd_reports_cache_bump_version')) {
            erp_omd_reports_cache_bump_version();
        }

        return (int) $wpdb->insert_id;
    }

    public function update($id, array $data)
    {
        global $wpdb;

        $updated = $wpdb->update(
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

        if ($updated !== false && function_exists('erp_omd_reports_cache_bump_version')) {
            erp_omd_reports_cache_bump_version();
        }

        return $updated;
    }

    public function delete($id)
    {
        global $wpdb;
        $deleted = $wpdb->delete($this->table_name(), ['id' => $id], ['%d']);
        if ($deleted !== false && function_exists('erp_omd_reports_cache_bump_version')) {
            erp_omd_reports_cache_bump_version();
        }

        return $deleted;
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


    public function latest_entry_dates_by_employee()
    {
        global $wpdb;

        $rows = $wpdb->get_results("SELECT employee_id, MAX(entry_date) AS last_entry_date FROM {$this->table_name()} GROUP BY employee_id", ARRAY_A);
        $map = [];

        foreach ($rows as $row) {
            $employee_id = (int) ($row['employee_id'] ?? 0);
            $last_entry_date = (string) ($row['last_entry_date'] ?? '');
            if ($employee_id > 0 && $last_entry_date !== '') {
                $map[$employee_id] = $last_entry_date;
            }
        }

        return $map;
    }

    public function duplicate_exists($employee_id, $project_id, $hours, $entry_date, $exclude_id = null)
    {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$this->table_name()} WHERE employee_id = %d AND project_id = %d AND hours = %f AND entry_date = %s";
        $params = [(int) $employee_id, (int) $project_id, (float) $hours, (string) $entry_date];
        if ($exclude_id) {
            $sql .= ' AND id != %d';
            $params[] = (int) $exclude_id;
        }

        return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params)) > 0;
    }
}
