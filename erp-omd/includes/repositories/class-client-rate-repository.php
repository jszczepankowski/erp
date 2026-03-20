<?php

class ERP_OMD_Client_Rate_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_client_rates';
    }

    public function for_client($client_id)
    {
        global $wpdb;

        $roles_table = $wpdb->prefix . 'erp_omd_roles';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT cr.*, r.name AS role_name
                FROM {$this->table_name()} cr
                INNER JOIN {$roles_table} r ON r.id = cr.role_id
                WHERE cr.client_id = %d
                ORDER BY r.name ASC",
                $client_id
            ),
            ARRAY_A
        );
    }

    public function find($id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE id = %d", $id), ARRAY_A);
    }

    public function upsert($client_id, $role_id, $rate)
    {
        global $wpdb;

        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name()} WHERE client_id = %d AND role_id = %d",
                $client_id,
                $role_id
            ),
            ARRAY_A
        );

        if ($existing) {
            $wpdb->update(
                $this->table_name(),
                ['rate' => $rate, 'updated_at' => current_time('mysql')],
                ['id' => $existing['id']],
                ['%f', '%s'],
                ['%d']
            );

            return (int) $existing['id'];
        }

        $wpdb->insert(
            $this->table_name(),
            [
                'client_id' => $client_id,
                'role_id' => $role_id,
                'rate' => $rate,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%f', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public function update($id, $role_id, $rate)
    {
        global $wpdb;

        return $wpdb->update(
            $this->table_name(),
            [
                'role_id' => $role_id,
                'rate' => $rate,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%d', '%f', '%s'],
            ['%d']
        );
    }

    public function delete($id)
    {
        global $wpdb;

        return $wpdb->delete($this->table_name(), ['id' => $id], ['%d']);
    }
}
