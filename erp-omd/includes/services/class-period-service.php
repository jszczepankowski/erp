<?php

class ERP_OMD_Period_Service
{
    private $periods;

    public function __construct(ERP_OMD_Period_Repository $periods)
    {
        $this->periods = $periods;
    }

    public function normalize_month($month)
    {
        $month = trim((string) $month);
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return gmdate('Y-m');
        }

        return $month;
    }

    public function get_or_create($month)
    {
        $month = $this->normalize_month($month);
        $period = $this->periods->find_by_month($month);
        if ($period) {
            return $period;
        }

        return $this->periods->upsert([
            'month' => $month,
            'status' => 'LIVE',
            'closed_at' => null,
            'correction_window_until' => null,
            'updated_by_user_id' => get_current_user_id(),
        ]);
    }

    public function transition($month, $target_status)
    {
        $period = $this->get_or_create($month);
        $current = (string) ($period['status'] ?? 'LIVE');
        $target_status = $this->normalize_status($target_status);
        $current = $this->normalize_status($current);

        $allowed = [
            'LIVE' => ['DO ROZLICZENIA'],
            'DO ROZLICZENIA' => ['ZAMKNIĘTY'],
            'ZAMKNIĘTY' => [],
        ];

        if ($current !== $target_status && ! in_array($target_status, $allowed[$current] ?? [], true)) {
            return new WP_Error('erp_omd_period_transition_invalid', __('Niedozwolona zmiana statusu miesiąca.', 'erp-omd'), ['status' => 422]);
        }

        $closed_at = (string) ($period['closed_at'] ?? '');
        $window_until = (string) ($period['correction_window_until'] ?? '');
        if ($target_status === 'ZAMKNIĘTY') {
            $closed_at = current_time('mysql');
            $window_until = (new DateTimeImmutable($closed_at))->modify('+72 hours')->format('Y-m-d H:i:s');
        }

        return $this->periods->upsert([
            'month' => $this->normalize_month($month),
            'status' => $target_status,
            'closed_at' => $closed_at,
            'correction_window_until' => $window_until,
            'updated_by_user_id' => get_current_user_id(),
        ]);
    }

    private function normalize_status($status)
    {
        $status = strtoupper(trim((string) $status));
        if ($status === 'DO_ROZLICZENIA') {
            return 'DO ROZLICZENIA';
        }
        if ($status === 'ZAMKNIETY') {
            return 'ZAMKNIĘTY';
        }

        return $status;
    }

    public function can_modify_date($date, $is_admin, $emergency = false)
    {
        $month = substr((string) $date, 0, 7);
        $period = $this->get_or_create($month);
        $status = (string) ($period['status'] ?? 'LIVE');
        if ($status === 'LIVE') {
            return true;
        }

        if (! $is_admin) {
            return false;
        }

        if ($status === 'DO ROZLICZENIA') {
            return true;
        }

        if (! $emergency) {
            $window_until = (string) ($period['correction_window_until'] ?? '');
            if ($window_until === '') {
                return false;
            }

            return current_time('mysql') <= $window_until;
        }

        return true;
    }

    public function checklist($month)
    {
        global $wpdb;

        $month = $this->normalize_month($month);
        $start = $month . '-01';
        $end = gmdate('Y-m-t', strtotime($start));
        $time_table = $wpdb->prefix . 'erp_omd_time_entries';
        $cost_table = $wpdb->prefix . 'erp_omd_project_costs';

        $pending_entries = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$time_table} WHERE entry_date BETWEEN %s AND %s AND status IN ('submitted','rejected')",
            $start,
            $end
        ));

        $cost_rows = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$cost_table} WHERE cost_date BETWEEN %s AND %s",
            $start,
            $end
        ));

        return [
            'month' => $month,
            'items' => [
                ['code' => 'time_entries_approved', 'ok' => $pending_entries === 0, 'message' => $pending_entries === 0 ? __('Brak niezatwierdzonych wpisów czasu.', 'erp-omd') : __('Istnieją wpisy czasu submitted/rejected.', 'erp-omd')],
                ['code' => 'project_costs_present', 'ok' => true, 'message' => sprintf(__('Koszty projektowe w miesiącu: %d.', 'erp-omd'), $cost_rows)],
            ],
        ];
    }
}
