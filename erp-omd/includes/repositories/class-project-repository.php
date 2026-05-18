<?php

class ERP_OMD_Project_Repository
{
    private $projects_columns_cache = null;

    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_projects';
    }

    public function managers_table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_project_managers';
    }

    public function all(array $filters = [])
    {
        return $this->find_paged($filters, 1000000, 0);
    }

    public function find_paged(array $filters = [], $limit = 100, $offset = 0)
    {
        global $wpdb;

        $clients_table = $wpdb->prefix . 'erp_omd_clients';
        $employees_table = $wpdb->prefix . 'erp_omd_employees';
        $users_table = $wpdb->users;
        $where = ['1=1'];
        $params = [];

        if (! empty($filters['client_id'])) {
            $where[] = 'p.client_id = %d';
            $params[] = (int) $filters['client_id'];
        }
        if (! empty($filters['status'])) {
            $where[] = 'p.status = %s';
            $params[] = (string) $filters['status'];
        }
        if (! empty($filters['manager_id'])) {
            $where[] = 'p.manager_id = %d';
            $params[] = (int) $filters['manager_id'];
        }
        if (! empty($filters['search'])) {
            $like = '%' . $wpdb->esc_like((string) $filters['search']) . '%';
            $where[] = '(p.name LIKE %s OR p.brief LIKE %s OR c.name LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $limit = max(1, (int) $limit);
        $offset = max(0, (int) $offset);
        $params[] = $limit;
        $params[] = $offset;

        $projects = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.*, c.name AS client_name, u.user_login AS manager_login
            FROM {$this->table_name()} p
            INNER JOIN {$clients_table} c ON c.id = p.client_id
            LEFT JOIN {$employees_table} e ON e.id = p.manager_id
            LEFT JOIN {$users_table} u ON u.ID = e.user_id
            WHERE " . implode(' AND ', $where) . " ORDER BY p.created_at DESC, p.id DESC LIMIT %d OFFSET %d",
                ...$params
            ),
            ARRAY_A
        );

        return $this->enrich_projects($projects);
    }

    public function count_filtered(array $filters = [])
    {
        global $wpdb;

        $clients_table = $wpdb->prefix . 'erp_omd_clients';
        $where = ['1=1'];
        $params = [];

        if (! empty($filters['client_id'])) {
            $where[] = 'p.client_id = %d';
            $params[] = (int) $filters['client_id'];
        }
        if (! empty($filters['status'])) {
            $where[] = 'p.status = %s';
            $params[] = (string) $filters['status'];
        }
        if (! empty($filters['manager_id'])) {
            $where[] = 'p.manager_id = %d';
            $params[] = (int) $filters['manager_id'];
        }
        if (! empty($filters['search'])) {
            $like = '%' . $wpdb->esc_like((string) $filters['search']) . '%';
            $where[] = '(p.name LIKE %s OR p.brief LIKE %s OR c.name LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT COUNT(*) FROM {$this->table_name()} p INNER JOIN {$clients_table} c ON c.id = p.client_id WHERE " . implode(' AND ', $where);

        if ($params !== []) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
        }

        return (int) $wpdb->get_var($sql);
    }

    public function find($id)
    {
        global $wpdb;

        $clients_table = $wpdb->prefix . 'erp_omd_clients';
        $employees_table = $wpdb->prefix . 'erp_omd_employees';
        $users_table = $wpdb->users;

        $project = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT p.*, c.name AS client_name, u.user_login AS manager_login
                FROM {$this->table_name()} p
                LEFT JOIN {$clients_table} c ON c.id = p.client_id
                LEFT JOIN {$employees_table} e ON e.id = p.manager_id
                LEFT JOIN {$users_table} u ON u.ID = e.user_id
                WHERE p.id = %d",
                $id
            ),
            ARRAY_A
        );

        if (! $project) {
            return null;
        }

        return $this->enrich_project($project);
    }

    public function ids_managed_by_employee($employee_id)
    {
        global $wpdb;

        $project_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT project_id FROM (
                    SELECT id AS project_id FROM {$this->table_name()} WHERE manager_id = %d
                    UNION ALL
                    SELECT project_id FROM {$this->managers_table_name()} WHERE employee_id = %d
                ) managed_projects
                ORDER BY project_id ASC",
                $employee_id,
                $employee_id
            )
        );

        return array_map('intval', $project_ids);
    }

    public function manager_ids($project_id)
    {
        global $wpdb;

        $project = $wpdb->get_row(
            $wpdb->prepare("SELECT manager_id FROM {$this->table_name()} WHERE id = %d", $project_id),
            ARRAY_A
        );
        if (! $project) {
            return [];
        }

        $manager_ids = array_map(
            'intval',
            $wpdb->get_col($wpdb->prepare("SELECT employee_id FROM {$this->managers_table_name()} WHERE project_id = %d ORDER BY employee_id ASC", $project_id))
        );

        if (! empty($project['manager_id'])) {
            array_unshift($manager_ids, (int) $project['manager_id']);
        }

        return array_values(array_unique(array_filter($manager_ids)));
    }

    public function sync_manager_ids($project_id, array $manager_ids)
    {
        global $wpdb;
        $project_id = (int) $project_id;
        if ($project_id <= 0) {
            return;
        }

        $manager_ids = array_values(array_unique(array_filter(array_map('intval', $manager_ids))));
        $wpdb->delete($this->managers_table_name(), ['project_id' => $project_id], ['%d']);

        foreach ($manager_ids as $manager_id) {
            $wpdb->insert(
                $this->managers_table_name(),
                [
                    'project_id' => (int) $project_id,
                    'employee_id' => (int) $manager_id,
                    'assigned_at' => current_time('mysql'),
                ],
                ['%d', '%d', '%s']
            );
        }
    }

    public function find_by_estimate_id($estimate_id)
    {
        global $wpdb;

        $project = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name()} WHERE estimate_id = %d LIMIT 1",
                $estimate_id
            ),
            ARRAY_A
        );

        return $project ? $this->enrich_project($project) : null;
    }

    public function create(array $data)
    {
        global $wpdb;

        $now = current_time('mysql');
        $insert_data = [
            'client_id' => $data['client_id'],
            'name' => $data['name'],
            'billing_type' => $data['billing_type'],
            'budget' => $data['budget'],
            'retainer_monthly_fee' => $data['retainer_monthly_fee'],
            'status' => $data['status'],
            'start_date' => $data['start_date'] ?: null,
            'end_date' => $data['end_date'] ?: null,
            'deadline_date' => ($data['deadline_date'] ?? '') !== '' ? $data['deadline_date'] : null,
            'deadline_completed_at' => ($data['deadline_completed_at'] ?? '') !== '' ? $data['deadline_completed_at'] : null,
            'deadline_completed_by' => ! empty($data['deadline_completed_by']) ? (int) $data['deadline_completed_by'] : null,
            'manager_id' => $data['manager_id'] ?: null,
            'estimate_id' => $data['estimate_id'] ?: null,
            'brief' => $data['brief'],
            'alert_margin_threshold' => $data['alert_margin_threshold'] === null ? null : (string) $data['alert_margin_threshold'],
            'created_at' => $now,
            'updated_at' => $now,
        ];
        if ($this->projects_table_has_column('project_links')) {
            $insert_data['project_links'] = $data['project_links'] ?? '';
        }
        $insert_formats = [
            '%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s',
            '%d', '%d', '%d', '%s', '%s', '%s', '%s',
        ];
        if (isset($insert_data['project_links'])) {
            array_splice($insert_formats, 14, 0, ['%s']);
        }
        $wpdb->insert($this->table_name(), $insert_data, $insert_formats);

        $project_id = (int) $wpdb->insert_id;
        $this->sync_manager_ids($project_id, $data['manager_ids'] ?? array_filter([(int) ($data['manager_id'] ?? 0)]));
        $created_project = $this->find($project_id);
        if (function_exists('do_action')) {
            do_action('erp_omd_project_saved', $created_project ?: ['id' => $project_id], 'create');
        }

        return $project_id;
    }

    public function update($id, array $data)
    {
        global $wpdb;

        $update_data = [
            'client_id' => $data['client_id'],
            'name' => $data['name'],
            'billing_type' => $data['billing_type'],
            'budget' => $data['budget'],
            'retainer_monthly_fee' => $data['retainer_monthly_fee'],
            'status' => $data['status'],
            'start_date' => $data['start_date'] ?: null,
            'end_date' => $data['end_date'] ?: null,
            'deadline_date' => ($data['deadline_date'] ?? '') !== '' ? $data['deadline_date'] : null,
            'deadline_completed_at' => ($data['deadline_completed_at'] ?? '') !== '' ? $data['deadline_completed_at'] : null,
            'deadline_completed_by' => ! empty($data['deadline_completed_by']) ? (int) $data['deadline_completed_by'] : null,
            'manager_id' => $data['manager_id'] ?: null,
            'estimate_id' => $data['estimate_id'] ?: null,
            'brief' => $data['brief'],
            'alert_margin_threshold' => $data['alert_margin_threshold'] === null ? null : (string) $data['alert_margin_threshold'],
            'updated_at' => current_time('mysql'),
        ];
        if ($this->projects_table_has_column('project_links')) {
            $update_data['project_links'] = $data['project_links'] ?? '';
        }
        $update_formats = [
            '%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s',
            '%d', '%d', '%d', '%s', '%s', '%s',
        ];
        if (isset($update_data['project_links'])) {
            array_splice($update_formats, 14, 0, ['%s']);
        }
        $updated = $wpdb->update($this->table_name(), $update_data, ['id' => $id], $update_formats, ['%d']);

        $this->sync_manager_ids($id, $data['manager_ids'] ?? array_filter([(int) ($data['manager_id'] ?? 0)]));
        $updated_project = $this->find($id);
        if (function_exists('do_action')) {
            do_action('erp_omd_project_saved', $updated_project ?: ['id' => (int) $id], 'update');
        }

        return $updated;
    }

    private function projects_table_has_column($column_name)
    {
        global $wpdb;
        if (! is_string($column_name) || $column_name === '') {
            return false;
        }
        if (! is_array($this->projects_columns_cache)) {
            $table = esc_sql($this->table_name());
            $columns = $wpdb->get_col("SHOW COLUMNS FROM `{$table}`", 0);
            $this->projects_columns_cache = is_array($columns) ? array_map('strval', $columns) : [];
        }
        return in_array($column_name, $this->projects_columns_cache, true);
    }

    public function delete($id)
    {
        global $wpdb;
        $project = $this->find($id);

        $wpdb->delete($this->managers_table_name(), ['project_id' => $id], ['%d']);
        if (function_exists('do_action') && $project) {
            do_action('erp_omd_project_deleted', $project);
        }

        return $wpdb->delete($this->table_name(), ['id' => $id], ['%d']);
    }

    public function deactivate($id)
    {
        return $this->set_status($id, 'archiwum');
    }

    public function set_status($id, $status)
    {
        global $wpdb;

        $result = $wpdb->update(
            $this->table_name(),
            ['status' => $status, 'updated_at' => current_time('mysql')],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );
        if ($result !== false) {
            $project = $this->find($id);
            if (function_exists('do_action') && $project) {
                do_action('erp_omd_project_saved', $project, 'status_change');
            }
        }

        return $result;
    }

    private function enrich_projects(array $projects)
    {
        foreach ($projects as &$project) {
            $project = $this->enrich_project($project);
        }
        unset($project);

        return $projects;
    }

    private function enrich_project(array $project)
    {
        global $wpdb;

        $project_id = (int) ($project['id'] ?? 0);
        if ($project_id <= 0) {
            $project['manager_ids'] = [];
            $project['manager_logins'] = [];
            $project['manager_logins_display'] = '';

            return $project;
        }

        $manager_ids = $this->manager_ids($project_id);
        $project['manager_ids'] = $manager_ids;

        if ($manager_ids === []) {
            $project['manager_logins'] = [];
            $project['manager_logins_display'] = (string) ($project['manager_login'] ?? '');

            return $project;
        }

        $employees_table = $wpdb->prefix . 'erp_omd_employees';
        $users_table = $wpdb->users;
        $placeholders = implode(', ', array_fill(0, count($manager_ids), '%d'));
        $query = $wpdb->prepare(
            "SELECT e.id, u.user_login
            FROM {$employees_table} e
            INNER JOIN {$users_table} u ON u.ID = e.user_id
            WHERE e.id IN ({$placeholders})",
            ...$manager_ids
        );
        $rows = $wpdb->get_results($query, ARRAY_A);

        $login_map = [];
        foreach ($rows as $row) {
            $login_map[(int) ($row['id'] ?? 0)] = (string) ($row['user_login'] ?? '');
        }

        $manager_logins = [];
        foreach ($manager_ids as $manager_id) {
            if (! empty($login_map[$manager_id])) {
                $manager_logins[] = $login_map[$manager_id];
            }
        }

        $project['manager_logins'] = $manager_logins;
        $project['manager_logins_display'] = implode(', ', $manager_logins);
        if ($project['manager_logins_display'] === '' && ! empty($project['manager_login'])) {
            $project['manager_logins_display'] = (string) $project['manager_login'];
        }

        return $project;
    }
}
