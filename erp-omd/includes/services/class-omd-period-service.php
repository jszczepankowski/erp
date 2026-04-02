<?php

class ERP_OMD_Period_Service
{
    const STATUS_LIVE = 'LIVE';
    const STATUS_DO_ROZLICZENIA = 'DO_ROZLICZENIA';
    const STATUS_ZAMKNIETY = 'ZAMKNIETY';

    /** @var ERP_OMD_Period_Repository|null */
    private $periods;

    public function __construct($periods = null)
    {
        $this->periods = $periods;
    }

    public function build_readiness_checklist(array $signals)
    {
        $map = [
            'time_entries_finalized' => ! empty($signals['time_entries_finalized']),
            'project_costs_verified' => ! empty($signals['project_costs_verified']),
            'project_client_completeness' => ! empty($signals['project_client_completeness']),
            'critical_settlement_locks' => ! empty($signals['critical_settlement_locks']),
        ];

        $blockers = [];
        foreach ($map as $key => $passed) {
            if (! $passed) {
                $blockers[] = $key;
            }
        }

        return [
            'ready' => $blockers === [],
            'checks' => $map,
            'blockers' => $blockers,
        ];
    }

    public function can_transition($from_status, $to_status)
    {
        if ($from_status === self::STATUS_LIVE && $to_status === self::STATUS_DO_ROZLICZENIA) {
            return true;
        }

        if ($from_status === self::STATUS_DO_ROZLICZENIA && $to_status === self::STATUS_ZAMKNIETY) {
            return true;
        }

        return false;
    }

    public function assert_transition_allowed($from_status, $to_status, $is_ready)
    {
        if (! $this->can_transition($from_status, $to_status)) {
            throw new InvalidArgumentException('Requested month status transition is not allowed.');
        }

        if ($from_status === self::STATUS_LIVE && $to_status === self::STATUS_DO_ROZLICZENIA && ! $is_ready) {
            throw new InvalidArgumentException('LIVE -> DO_ROZLICZENIA requires readiness checklist == ready.');
        }
    }

    public function is_month_locked_for_regular_user($status)
    {
        return in_array((string) $status, [self::STATUS_DO_ROZLICZENIA, self::STATUS_ZAMKNIETY], true);
    }

    public function build_closure_timestamps(DateTimeImmutable $closed_at)
    {
        return [
            'closed_at' => $closed_at->format('Y-m-d H:i:s'),
            'correction_window_until' => $closed_at->modify('+72 hours')->format('Y-m-d H:i:s'),
        ];
    }

    public function is_emergency_adjustment_required(DateTimeImmutable $now, DateTimeImmutable $correction_window_until)
    {
        return $now > $correction_window_until;
    }

    public function ensure_month_exists($month, $updated_by = 0)
    {
        if (! $this->is_valid_month($month)) {
            throw new InvalidArgumentException('Month must use YYYY-MM format.');
        }

        if (! $this->periods) {
            return [
                'month' => $month,
                'status' => self::STATUS_LIVE,
                'closed_at' => null,
                'correction_window_until' => null,
                'updated_by' => (int) $updated_by,
            ];
        }

        $existing = $this->periods->find_by_month($month);
        if ($existing) {
            return $existing;
        }

        $this->periods->upsert([
            'month' => $month,
            'status' => self::STATUS_LIVE,
            'closed_at' => null,
            'correction_window_until' => null,
            'updated_by' => (int) $updated_by,
        ]);

        return $this->periods->find_by_month($month);
    }

    public function resolve_month_status($month)
    {
        if (! $this->is_valid_month($month)) {
            throw new InvalidArgumentException('Month must use YYYY-MM format.');
        }

        $period = $this->ensure_month_exists($month);

        return (string) ($period['status'] ?? self::STATUS_LIVE);
    }

    public function list_periods()
    {
        if (! $this->periods || ! method_exists($this->periods, 'all')) {
            return [];
        }

        return (array) $this->periods->all();
    }

    public function transition_month($month, $to_status, array $readiness)
    {
        if (! $this->periods) {
            throw new RuntimeException('Period repository is required for month transitions.');
        }
        if (! $this->is_valid_month($month)) {
            throw new InvalidArgumentException('Month must use YYYY-MM format.');
        }
        if (! in_array((string) $to_status, [self::STATUS_LIVE, self::STATUS_DO_ROZLICZENIA, self::STATUS_ZAMKNIETY], true)) {
            throw new InvalidArgumentException('Requested target status is not supported.');
        }

        $existing = $this->ensure_month_exists($month, get_current_user_id());
        $from_status = (string) ($existing['status'] ?? self::STATUS_LIVE);
        $checklist = $this->build_readiness_checklist($readiness);
        $this->assert_transition_allowed($from_status, $to_status, (bool) $checklist['ready']);

        $payload = [
            'month' => $month,
            'status' => $to_status,
            'updated_by' => (int) get_current_user_id(),
            'closed_at' => null,
            'correction_window_until' => null,
        ];

        if ($to_status === self::STATUS_ZAMKNIETY) {
            $timestamps = $this->build_closure_timestamps(new DateTimeImmutable(current_time('mysql')));
            $payload['closed_at'] = $timestamps['closed_at'];
            $payload['correction_window_until'] = $timestamps['correction_window_until'];
        }

        $this->periods->upsert($payload);

        return [
            'period' => $this->periods->find_by_month($month),
            'checklist' => $checklist,
        ];
    }

    private function is_valid_month($month)
    {
        return is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) === 1;
    }
}
