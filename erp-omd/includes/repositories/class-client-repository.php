<?php

class ERP_OMD_Client_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_clients';
    }

    public function all()
    {
        global $wpdb;

        $employees_table = $wpdb->prefix . 'erp_omd_employees';
        $users_table = $wpdb->users;

        return $wpdb->get_results(
            "SELECT c.*, u.user_login AS account_manager_login
            FROM {$this->table_name()} c
            LEFT JOIN {$employees_table} e ON e.id = c.account_manager_id
            LEFT JOIN {$users_table} u ON u.ID = e.user_id
            ORDER BY c.name ASC",
            ARRAY_A
        );
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
