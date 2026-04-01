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
            $wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE month = %s", (string) $month),
            ARRAY_A
        );
    }

    public function upsert(array $data)
    {
        global $wpdb;

        $month = (string) ($data['month'] ?? '');
        $status = (string) ($data['status'] ?? 'LIVE');
        $closed_at = isset($data['closed_at']) ? (string) $data['closed_at'] : null;
        $correction_window_until = isset($data['correction_window_until']) ? (string) $data['correction_window_until'] : null;
        $updated_by = (int) ($data['updated_by'] ?? 0);

        $existing = $this->find_by_month($month);

        if ($existing) {
            $wpdb->update(
                $this->table_name(),
                [
                    'status' => $status,
                    'closed_at' => $closed_at,
                    'correction_window_until' => $correction_window_until,
                    'updated_by' => $updated_by,
                ],
                ['month' => $month],
                ['%s', '%s', '%s', '%d'],
                ['%s']
            );

            return $month;
        }

        $wpdb->insert(
            $this->table_name(),
            [
                'month' => $month,
                'status' => $status,
                'closed_at' => $closed_at,
                'correction_window_until' => $correction_window_until,
                'updated_by' => $updated_by,
            ],
            ['%s', '%s', '%s', '%s', '%d']
        );

        return $month;
    }
}
