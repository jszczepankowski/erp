<?php

class ERP_OMD_Client_Rate_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_client_rates';
    }

    public function history_table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_client_rate_history';
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

    public function find_effective_rate($client_id, $role_id, $entry_date)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM {$this->history_table_name()}
                WHERE client_id = %d
                    AND role_id = %d
                    AND valid_from <= %s
                    AND (valid_to IS NULL OR valid_to = '0000-00-00' OR valid_to >= %s)
                ORDER BY valid_from DESC, id DESC
                LIMIT 1",
                $client_id,
                $role_id,
                $entry_date,
                $entry_date
            ),
            ARRAY_A
        );
    }

    public function upsert($client_id, $role_id, $rate, $valid_from = '', $valid_to = '')
    {
        global $wpdb;

        $valid_from = $valid_from ?: current_time('Y-m-d');
        $valid_to = $valid_to ?: null;
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
            $id = (int) $existing['id'];
        } else {
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
            $id = (int) $wpdb->insert_id;
        }

        $this->record_history_version($client_id, $role_id, $rate, $valid_from, $valid_to);

        return $id;
    }

    public function update($id, $role_id, $rate, $valid_from = '', $valid_to = '')
    {
        global $wpdb;

        $existing = $this->find($id);
        if (! $existing) {
            return 0;
        }

        $result = $wpdb->update(
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

        $this->record_history_version((int) $existing['client_id'], $role_id, $rate, $valid_from ?: current_time('Y-m-d'), $valid_to ?: null);

        return $result;
    }

    public function delete($id)
    {
        global $wpdb;

        return $wpdb->delete($this->table_name(), ['id' => $id], ['%d']);
    }

    private function record_history_version($client_id, $role_id, $rate, $valid_from, $valid_to = null)
    {
        global $wpdb;

        $open_version = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM {$this->history_table_name()}
                WHERE client_id = %d AND role_id = %d AND (valid_to IS NULL OR valid_to = '0000-00-00')
                ORDER BY valid_from DESC, id DESC
                LIMIT 1",
                $client_id,
                $role_id
            ),
            ARRAY_A
        );

        if ($open_version && (float) $open_version['rate'] === (float) $rate && (string) $open_version['valid_from'] === (string) $valid_from) {
            return;
        }

        if ($open_version) {
            $closed_to = (new DateTimeImmutable($valid_from))->modify('-1 day')->format('Y-m-d');
            if ($closed_to >= (string) $open_version['valid_from']) {
                $wpdb->update(
                    $this->history_table_name(),
                    ['valid_to' => $closed_to, 'updated_at' => current_time('mysql')],
                    ['id' => $open_version['id']],
                    ['%s', '%s'],
                    ['%d']
                );
            }
        }

        $wpdb->insert(
            $this->history_table_name(),
            [
                'client_id' => $client_id,
                'role_id' => $role_id,
                'rate' => $rate,
                'valid_from' => $valid_from,
                'valid_to' => $valid_to,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%f', '%s', '%s', '%s', '%s']
        );
    }
}
