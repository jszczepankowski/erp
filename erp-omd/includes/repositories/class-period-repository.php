<?php

class ERP_OMD_Period_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_periods';
    }

    public function find_by_month($month)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE month = %s", $month),
            ARRAY_A
        );
    }

    public function upsert(array $data)
    {
        global $wpdb;

        $existing = $this->find_by_month((string) $data['month']);
        $payload = [
            'status' => (string) $data['status'],
            'closed_at' => $data['closed_at'] ?: null,
            'correction_window_until' => $data['correction_window_until'] ?: null,
            'updated_by_user_id' => (int) ($data['updated_by_user_id'] ?? 0) ?: null,
            'updated_at' => current_time('mysql'),
        ];

        if (! $existing) {
            $payload['month'] = (string) $data['month'];
            $payload['created_at'] = current_time('mysql');
            $wpdb->insert(
                $this->table_name(),
                $payload,
                ['%s', '%s', '%s', '%d', '%s', '%s', '%s']
            );

            return $this->find_by_month((string) $data['month']);
        }

        $wpdb->update(
            $this->table_name(),
            $payload,
            ['month' => (string) $data['month']],
            ['%s', '%s', '%s', '%d', '%s'],
            ['%s']
        );

        return $this->find_by_month((string) $data['month']);
    }
}
