<?php

class ERP_OMD_Client_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_clients';
    }

    public function all(array $filters = [])
    {
        return $this->find_paged($filters, 1000000, 0);
    }

    public function find_paged(array $filters = [], $limit = 100, $offset = 0)
    {
        global $wpdb;

        $employees_table = $wpdb->prefix . 'erp_omd_employees';
        $users_table = $wpdb->users;

        $where = ['1=1'];
        $params = [];

        if (! empty($filters['status'])) {
            $where[] = 'c.status = %s';
            $params[] = (string) $filters['status'];
        }

        if (! empty($filters['search'])) {
            $like = '%' . $wpdb->esc_like((string) $filters['search']) . '%';
            $where[] = '(c.name LIKE %s OR c.company LIKE %s OR c.nip LIKE %s OR c.email LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $limit = max(1, (int) $limit);
        $offset = max(0, (int) $offset);

        $sql = "SELECT c.*, u.user_login AS account_manager_login
            FROM {$this->table_name()} c
            LEFT JOIN {$employees_table} e ON e.id = c.account_manager_id
            LEFT JOIN {$users_table} u ON u.ID = e.user_id
            WHERE " . implode(' AND ', $where) . ' ORDER BY c.name ASC LIMIT %d OFFSET %d';

        $params[] = $limit;
        $params[] = $offset;

        return $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
    }

    public function count_filtered(array $filters = [])
    {
        global $wpdb;

        $where = ['1=1'];
        $params = [];

        if (! empty($filters['status'])) {
            $where[] = 'status = %s';
            $params[] = (string) $filters['status'];
        }

        if (! empty($filters['search'])) {
            $like = '%' . $wpdb->esc_like((string) $filters['search']) . '%';
            $where[] = '(name LIKE %s OR company LIKE %s OR nip LIKE %s OR email LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT COUNT(*) FROM {$this->table_name()} WHERE " . implode(' AND ', $where);

        if ($params !== []) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
        }

        return (int) $wpdb->get_var($sql);
    }

    public function find($id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE id = %d", $id), ARRAY_A);
    }

    public function find_by_nip($nip)
    {
        global $wpdb;

        $nip = preg_replace('/[^0-9]/', '', (string) $nip);
        if (! is_string($nip) || $nip === '') {
            return [];
        }

        return (array) $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name()} WHERE REPLACE(REPLACE(REPLACE(nip, '-', ''), ' ', ''), '.', '') = %s ORDER BY id ASC",
                $nip
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
                'name' => $data['name'],
                'company' => $data['company'],
                'nip' => $data['nip'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'contact_person_name' => $data['contact_person_name'],
                'contact_person_email' => $data['contact_person_email'],
                'contact_person_phone' => $data['contact_person_phone'],
                'city' => $data['city'],
                'street' => $data['street'],
                'apartment_number' => $data['apartment_number'],
                'postal_code' => $data['postal_code'],
                'country' => $data['country'],
                'status' => $data['status'],
                'account_manager_id' => $data['account_manager_id'] ?: null,
                'alert_margin_threshold' => $data['alert_margin_threshold'] === null ? null : (string) $data['alert_margin_threshold'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public function update($id, array $data)
    {
        global $wpdb;

        return $wpdb->update(
            $this->table_name(),
            [
                'name' => $data['name'],
                'company' => $data['company'],
                'nip' => $data['nip'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'contact_person_name' => $data['contact_person_name'],
                'contact_person_email' => $data['contact_person_email'],
                'contact_person_phone' => $data['contact_person_phone'],
                'city' => $data['city'],
                'street' => $data['street'],
                'apartment_number' => $data['apartment_number'],
                'postal_code' => $data['postal_code'],
                'country' => $data['country'],
                'status' => $data['status'],
                'account_manager_id' => $data['account_manager_id'] ?: null,
                'alert_margin_threshold' => $data['alert_margin_threshold'] === null ? null : (string) $data['alert_margin_threshold'],
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s'],
            ['%d']
        );
    }

    public function delete($id)
    {
        global $wpdb;

        return $wpdb->delete($this->table_name(), ['id' => $id], ['%d']);
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

    public function nip_exists($nip, $exclude_id = null)
    {
        global $wpdb;

        if ($nip === '') {
            return false;
        }

        if ($exclude_id) {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name()} WHERE nip = %s AND id != %d", $nip, $exclude_id));
        } else {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name()} WHERE nip = %s", $nip));
        }

        return (int) $count > 0;
    }
}
