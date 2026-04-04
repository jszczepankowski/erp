<?php

class ERP_OMD_Estimate_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_estimates';
    }

    public function all(array $filters = [])
    {
        return $this->find_paged($filters, 1000000, 0);
    }

    public function find_paged(array $filters = [], $limit = 100, $offset = 0)
    {
        global $wpdb;

        $clients_table = $wpdb->prefix . 'erp_omd_clients';
        $projects_table = $wpdb->prefix . 'erp_omd_projects';
        $where = ['1=1'];
        $params = [];

        if (! empty($filters['status'])) {
            $where[] = 'e.status = %s';
            $params[] = (string) $filters['status'];
        }
        if (! empty($filters['client_id'])) {
            $where[] = 'e.client_id = %d';
            $params[] = (int) $filters['client_id'];
        }
        if (! empty($filters['search'])) {
            $like = '%' . $wpdb->esc_like((string) $filters['search']) . '%';
            $where[] = '(e.name LIKE %s OR c.name LIKE %s OR p.name LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (! empty($filters['month']) && preg_match('/^\d{4}-\d{2}$/', (string) $filters['month']) === 1) {
            $where[] = 'e.created_at LIKE %s';
            $params[] = (string) $filters['month'] . '%';
        }

        $limit = max(1, (int) $limit);
        $offset = max(0, (int) $offset);
        $params[] = $limit;
        $params[] = $offset;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.*, c.name AS client_name, p.id AS project_id, p.name AS project_name
            FROM {$this->table_name()} e
            INNER JOIN {$clients_table} c ON c.id = e.client_id
            LEFT JOIN {$projects_table} p ON p.estimate_id = e.id
            WHERE " . implode(' AND ', $where) . " ORDER BY e.created_at DESC, e.id DESC LIMIT %d OFFSET %d",
                ...$params
            ),
            ARRAY_A
        );
    }

    public function count_filtered(array $filters = [])
    {
        global $wpdb;

        $clients_table = $wpdb->prefix . 'erp_omd_clients';
        $projects_table = $wpdb->prefix . 'erp_omd_projects';
        $where = ['1=1'];
        $params = [];

        if (! empty($filters['status'])) {
            $where[] = 'e.status = %s';
            $params[] = (string) $filters['status'];
        }
        if (! empty($filters['client_id'])) {
            $where[] = 'e.client_id = %d';
            $params[] = (int) $filters['client_id'];
        }
        if (! empty($filters['search'])) {
            $like = '%' . $wpdb->esc_like((string) $filters['search']) . '%';
            $where[] = '(e.name LIKE %s OR c.name LIKE %s OR p.name LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (! empty($filters['month']) && preg_match('/^\d{4}-\d{2}$/', (string) $filters['month']) === 1) {
            $where[] = 'e.created_at LIKE %s';
            $params[] = (string) $filters['month'] . '%';
        }

        $sql = "SELECT COUNT(*) FROM {$this->table_name()} e INNER JOIN {$clients_table} c ON c.id = e.client_id LEFT JOIN {$projects_table} p ON p.estimate_id = e.id WHERE " . implode(' AND ', $where);

        if ($params !== []) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
        }

        return (int) $wpdb->get_var($sql);
    }

    public function count_filtered(array $filters = [])
    {
        global $wpdb;

        $clients_table = $wpdb->prefix . 'erp_omd_clients';
        $projects_table = $wpdb->prefix . 'erp_omd_projects';
        $where = ['1=1'];
        $params = [];

        if (! empty($filters['status'])) {
            $where[] = 'e.status = %s';
            $params[] = (string) $filters['status'];
        }
        if (! empty($filters['client_id'])) {
            $where[] = 'e.client_id = %d';
            $params[] = (int) $filters['client_id'];
        }
        if (! empty($filters['search'])) {
            $like = '%' . $wpdb->esc_like((string) $filters['search']) . '%';
            $where[] = '(e.name LIKE %s OR c.name LIKE %s OR p.name LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (! empty($filters['month']) && preg_match('/^\d{4}-\d{2}$/', (string) $filters['month']) === 1) {
            $where[] = 'e.created_at LIKE %s';
            $params[] = (string) $filters['month'] . '%';
        }

        $sql = "SELECT COUNT(*) FROM {$this->table_name()} e INNER JOIN {$clients_table} c ON c.id = e.client_id LEFT JOIN {$projects_table} p ON p.estimate_id = e.id WHERE " . implode(' AND ', $where);

        if ($params !== []) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
        }

        return (int) $wpdb->get_var($sql);
    }

    public function count_filtered(array $filters = [])
    {
        global $wpdb;

        $clients_table = $wpdb->prefix . 'erp_omd_clients';
        $projects_table = $wpdb->prefix . 'erp_omd_projects';
        $where = ['1=1'];
        $params = [];

        if (! empty($filters['status'])) {
            $where[] = 'e.status = %s';
            $params[] = (string) $filters['status'];
        }
        if (! empty($filters['client_id'])) {
            $where[] = 'e.client_id = %d';
            $params[] = (int) $filters['client_id'];
        }
        if (! empty($filters['search'])) {
            $like = '%' . $wpdb->esc_like((string) $filters['search']) . '%';
            $where[] = '(e.name LIKE %s OR c.name LIKE %s OR p.name LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (! empty($filters['month']) && preg_match('/^\d{4}-\d{2}$/', (string) $filters['month']) === 1) {
            $where[] = 'e.created_at LIKE %s';
            $params[] = (string) $filters['month'] . '%';
        }

        $sql = "SELECT COUNT(*) FROM {$this->table_name()} e INNER JOIN {$clients_table} c ON c.id = e.client_id LEFT JOIN {$projects_table} p ON p.estimate_id = e.id WHERE " . implode(' AND ', $where);

        if ($params !== []) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
        }

        return (int) $wpdb->get_var($sql);
    }

    public function count_filtered(array $filters = [])
    {
        global $wpdb;

        $clients_table = $wpdb->prefix . 'erp_omd_clients';
        $projects_table = $wpdb->prefix . 'erp_omd_projects';
        $where = ['1=1'];
        $params = [];

        if (! empty($filters['status'])) {
            $where[] = 'e.status = %s';
            $params[] = (string) $filters['status'];
        }
        if (! empty($filters['client_id'])) {
            $where[] = 'e.client_id = %d';
            $params[] = (int) $filters['client_id'];
        }
        if (! empty($filters['search'])) {
            $like = '%' . $wpdb->esc_like((string) $filters['search']) . '%';
            $where[] = '(e.name LIKE %s OR c.name LIKE %s OR p.name LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (! empty($filters['month']) && preg_match('/^\d{4}-\d{2}$/', (string) $filters['month']) === 1) {
            $where[] = 'e.created_at LIKE %s';
            $params[] = (string) $filters['month'] . '%';
        }

        $sql = "SELECT COUNT(*) FROM {$this->table_name()} e INNER JOIN {$clients_table} c ON c.id = e.client_id LEFT JOIN {$projects_table} p ON p.estimate_id = e.id WHERE " . implode(' AND ', $where);

        if ($params !== []) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
        }

        return (int) $wpdb->get_var($sql);
    }

    public function count_filtered(array $filters = [])
    {
        global $wpdb;

        $clients_table = $wpdb->prefix . 'erp_omd_clients';
        $projects_table = $wpdb->prefix . 'erp_omd_projects';
        $where = ['1=1'];
        $params = [];

        if (! empty($filters['status'])) {
            $where[] = 'e.status = %s';
            $params[] = (string) $filters['status'];
        }
        if (! empty($filters['client_id'])) {
            $where[] = 'e.client_id = %d';
            $params[] = (int) $filters['client_id'];
        }
        if (! empty($filters['search'])) {
            $like = '%' . $wpdb->esc_like((string) $filters['search']) . '%';
            $where[] = '(e.name LIKE %s OR c.name LIKE %s OR p.name LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (! empty($filters['month']) && preg_match('/^\d{4}-\d{2}$/', (string) $filters['month']) === 1) {
            $where[] = 'e.created_at LIKE %s';
            $params[] = (string) $filters['month'] . '%';
        }

        $sql = "SELECT COUNT(*) FROM {$this->table_name()} e INNER JOIN {$clients_table} c ON c.id = e.client_id LEFT JOIN {$projects_table} p ON p.estimate_id = e.id WHERE " . implode(' AND ', $where);

        if ($params !== []) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
        }

        return (int) $wpdb->get_var($sql);
    }

    public function count_filtered(array $filters = [])
    {
        global $wpdb;

        $clients_table = $wpdb->prefix . 'erp_omd_clients';
        $projects_table = $wpdb->prefix . 'erp_omd_projects';
        $where = ['1=1'];
        $params = [];

        if (! empty($filters['status'])) {
            $where[] = 'e.status = %s';
            $params[] = (string) $filters['status'];
        }
        if (! empty($filters['client_id'])) {
            $where[] = 'e.client_id = %d';
            $params[] = (int) $filters['client_id'];
        }
        if (! empty($filters['search'])) {
            $like = '%' . $wpdb->esc_like((string) $filters['search']) . '%';
            $where[] = '(e.name LIKE %s OR c.name LIKE %s OR p.name LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (! empty($filters['month']) && preg_match('/^\d{4}-\d{2}$/', (string) $filters['month']) === 1) {
            $where[] = 'e.created_at LIKE %s';
            $params[] = (string) $filters['month'] . '%';
        }

        $sql = "SELECT COUNT(*) FROM {$this->table_name()} e INNER JOIN {$clients_table} c ON c.id = e.client_id LEFT JOIN {$projects_table} p ON p.estimate_id = e.id WHERE " . implode(' AND ', $where);

        if ($params !== []) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
        }

        return (int) $wpdb->get_var($sql);
    }

    public function count_filtered(array $filters = [])
    {
        global $wpdb;

        $clients_table = $wpdb->prefix . 'erp_omd_clients';
        $projects_table = $wpdb->prefix . 'erp_omd_projects';
        $where = ['1=1'];
        $params = [];

        if (! empty($filters['status'])) {
            $where[] = 'e.status = %s';
            $params[] = (string) $filters['status'];
        }
        if (! empty($filters['client_id'])) {
            $where[] = 'e.client_id = %d';
            $params[] = (int) $filters['client_id'];
        }
        if (! empty($filters['search'])) {
            $like = '%' . $wpdb->esc_like((string) $filters['search']) . '%';
            $where[] = '(e.name LIKE %s OR c.name LIKE %s OR p.name LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (! empty($filters['month']) && preg_match('/^\d{4}-\d{2}$/', (string) $filters['month']) === 1) {
            $where[] = 'e.created_at LIKE %s';
            $params[] = (string) $filters['month'] . '%';
        }

        $sql = "SELECT COUNT(*) FROM {$this->table_name()} e INNER JOIN {$clients_table} c ON c.id = e.client_id LEFT JOIN {$projects_table} p ON p.estimate_id = e.id WHERE " . implode(' AND ', $where);

        if ($params !== []) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
        }

        return (int) $wpdb->get_var($sql);
    }

    public function count_filtered(array $filters = [])
    {
        global $wpdb;

        $clients_table = $wpdb->prefix . 'erp_omd_clients';
        $projects_table = $wpdb->prefix . 'erp_omd_projects';
        $where = ['1=1'];
        $params = [];

        if (! empty($filters['status'])) {
            $where[] = 'e.status = %s';
            $params[] = (string) $filters['status'];
        }
        if (! empty($filters['client_id'])) {
            $where[] = 'e.client_id = %d';
            $params[] = (int) $filters['client_id'];
        }
        if (! empty($filters['search'])) {
            $like = '%' . $wpdb->esc_like((string) $filters['search']) . '%';
            $where[] = '(e.name LIKE %s OR c.name LIKE %s OR p.name LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (! empty($filters['month']) && preg_match('/^\d{4}-\d{2}$/', (string) $filters['month']) === 1) {
            $where[] = 'e.created_at LIKE %s';
            $params[] = (string) $filters['month'] . '%';
        }

        $sql = "SELECT COUNT(*) FROM {$this->table_name()} e INNER JOIN {$clients_table} c ON c.id = e.client_id LEFT JOIN {$projects_table} p ON p.estimate_id = e.id WHERE " . implode(' AND ', $where);

        if ($params !== []) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
        }

        return (int) $wpdb->get_var($sql);
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
                'name' => $data['name'],
                'status' => $data['status'],
                'accepted_by_user_id' => $data['accepted_by_user_id'] ?: null,
                'accepted_at' => $data['accepted_at'] ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%s', '%s', '%d', '%s', '%s', '%s']
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
                'status' => $data['status'],
                'accepted_by_user_id' => $data['accepted_by_user_id'] ?: null,
                'accepted_at' => $data['accepted_at'] ?: null,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%d', '%s', '%s', '%d', '%s', '%s'],
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
