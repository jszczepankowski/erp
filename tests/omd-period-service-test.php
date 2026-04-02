<?php

declare(strict_types=1);

if (! function_exists('get_current_user_id')) {
    function get_current_user_id()
    {
        return 77;
    }
}

if (! function_exists('current_time')) {
    function current_time($type)
    {
        return '2026-04-05 09:15:00';
    }
}

if (! class_exists('ERP_OMD_Period_Repository')) {
    class ERP_OMD_Period_Repository
    {
        private $rows = [];

        public function __construct(array $rows = [])
        {
            foreach ($rows as $row) {
                $month = (string) ($row['month'] ?? '');
                if ($month !== '') {
                    $this->rows[$month] = $row;
                }
            }
        }

        public function find_by_month($month)
        {
            return $this->rows[(string) $month] ?? null;
        }

        public function upsert(array $data)
        {
            $month = (string) ($data['month'] ?? '');
            if ($month === '') {
                return 0;
            }

            $this->rows[$month] = array_merge([
                'status' => ERP_OMD_Period_Service::STATUS_LIVE,
                'closed_at' => null,
                'correction_window_until' => null,
                'updated_by' => 0,
            ], $this->rows[$month] ?? [], $data);

            return 1;
        }

        public function all()
        {
            return array_values($this->rows);
        }
    }
}

require_once __DIR__ . '/../erp-omd/includes/services/class-omd-period-service.php';

final class OMDPeriodServiceTestRunner
{
    private $assertions = 0;

    public function run(): void
    {
        $service = new ERP_OMD_Period_Service();

        $checklist = $service->build_readiness_checklist([
            'time_entries_finalized' => true,
            'project_costs_verified' => true,
            'project_client_completeness' => false,
            'critical_settlement_locks' => true,
        ]);

        $this->assertFalse($checklist['ready'], 'Checklist should be not ready when at least one validator fails.');
        $this->assertSame(['project_client_completeness'], $checklist['blockers'], 'Checklist should expose failed validator keys.');

        $this->assertTrue(
            $service->can_transition(ERP_OMD_Period_Service::STATUS_LIVE, ERP_OMD_Period_Service::STATUS_DO_ROZLICZENIA),
            'LIVE -> DO_ROZLICZENIA should be allowed.'
        );

        $this->assertTrue(
            $service->can_transition(ERP_OMD_Period_Service::STATUS_DO_ROZLICZENIA, ERP_OMD_Period_Service::STATUS_ZAMKNIETY),
            'DO_ROZLICZENIA -> ZAMKNIETY should be allowed.'
        );

        $this->assertFalse(
            $service->can_transition(ERP_OMD_Period_Service::STATUS_ZAMKNIETY, ERP_OMD_Period_Service::STATUS_LIVE),
            'ZAMKNIETY -> LIVE should be forbidden.'
        );

        $thrown = false;
        try {
            $service->assert_transition_allowed(
                ERP_OMD_Period_Service::STATUS_LIVE,
                ERP_OMD_Period_Service::STATUS_DO_ROZLICZENIA,
                false
            );
        } catch (InvalidArgumentException $exception) {
            $thrown = true;
        }

        $this->assertTrue($thrown, 'LIVE -> DO_ROZLICZENIA should require readiness checklist == ready.');

        $timestamps = $service->build_closure_timestamps(new DateTimeImmutable('2026-04-01 10:30:00'));
        $this->assertSame('2026-04-01 10:30:00', $timestamps['closed_at'], 'Closed at should be passed through unchanged.');
        $this->assertSame('2026-04-04 10:30:00', $timestamps['correction_window_until'], 'Correction window should be closed_at +72h.');

        $this->assertTrue($service->is_month_locked_for_regular_user(ERP_OMD_Period_Service::STATUS_DO_ROZLICZENIA), 'DO_ROZLICZENIA should lock regular users.');
        $this->assertTrue($service->is_month_locked_for_regular_user(ERP_OMD_Period_Service::STATUS_ZAMKNIETY), 'ZAMKNIETY should lock regular users.');
        $this->assertFalse($service->is_month_locked_for_regular_user(ERP_OMD_Period_Service::STATUS_LIVE), 'LIVE should remain editable for regular users.');

        $this->assertFalse(
            $service->is_emergency_adjustment_required(
                new DateTimeImmutable('2026-04-04 10:30:00'),
                new DateTimeImmutable('2026-04-04 10:30:00')
            ),
            'Emergency mode should not be required exactly at the correction window boundary.'
        );

        $this->assertTrue(
            $service->is_emergency_adjustment_required(
                new DateTimeImmutable('2026-04-04 10:30:01'),
                new DateTimeImmutable('2026-04-04 10:30:00')
            ),
            'Emergency mode should be required after correction window boundary.'
        );

        $repository = new ERP_OMD_Period_Repository([
            [
                'month' => '2026-03',
                'status' => ERP_OMD_Period_Service::STATUS_LIVE,
                'closed_at' => null,
                'correction_window_until' => null,
                'updated_by' => 1,
            ],
        ]);
        $repositoryBackedService = new ERP_OMD_Period_Service($repository);

        $createdPeriod = $repositoryBackedService->ensure_month_exists('2026-04', 12);
        $this->assertSame('2026-04', $createdPeriod['month'], 'ensure_month_exists should create missing month rows.');
        $this->assertSame(ERP_OMD_Period_Service::STATUS_LIVE, $createdPeriod['status'], 'New months should default to LIVE status.');
        $this->assertSame(12, $createdPeriod['updated_by'], 'ensure_month_exists should preserve explicit updater ID during bootstrap.');

        $liveToSettlement = $repositoryBackedService->transition_month('2026-04', ERP_OMD_Period_Service::STATUS_DO_ROZLICZENIA, [
            'time_entries_finalized' => true,
            'project_costs_verified' => true,
            'project_client_completeness' => true,
            'critical_settlement_locks' => true,
        ]);
        $this->assertSame(ERP_OMD_Period_Service::STATUS_DO_ROZLICZENIA, $liveToSettlement['period']['status'], 'Transition should update month status to DO_ROZLICZENIA.');
        $this->assertSame(77, $liveToSettlement['period']['updated_by'], 'Transition should record updater from current user context.');
        $this->assertTrue($liveToSettlement['checklist']['ready'], 'Checklist should be ready for fully passed readiness signals.');

        $thrownInvalidMonth = false;
        try {
            $repositoryBackedService->transition_month('2026/04', ERP_OMD_Period_Service::STATUS_DO_ROZLICZENIA, [
                'time_entries_finalized' => true,
                'project_costs_verified' => true,
                'project_client_completeness' => true,
                'critical_settlement_locks' => true,
            ]);
        } catch (InvalidArgumentException $exception) {
            $thrownInvalidMonth = true;
        }
        $this->assertTrue($thrownInvalidMonth, 'Transition should reject invalid month format.');

        $thrownInvalidStatus = false;
        try {
            $repositoryBackedService->transition_month('2026-04', 'ARCHIVE', [
                'time_entries_finalized' => true,
                'project_costs_verified' => true,
                'project_client_completeness' => true,
                'critical_settlement_locks' => true,
            ]);
        } catch (InvalidArgumentException $exception) {
            $thrownInvalidStatus = true;
        }
        $this->assertTrue($thrownInvalidStatus, 'Transition should reject unsupported target statuses.');

        $closeResult = $repositoryBackedService->transition_month('2026-04', ERP_OMD_Period_Service::STATUS_ZAMKNIETY, [
            'time_entries_finalized' => true,
            'project_costs_verified' => true,
            'project_client_completeness' => true,
            'critical_settlement_locks' => true,
        ]);
        $this->assertSame(ERP_OMD_Period_Service::STATUS_ZAMKNIETY, $closeResult['period']['status'], 'Transition should allow DO_ROZLICZENIA -> ZAMKNIETY.');
        $this->assertSame('2026-04-05 09:15:00', $closeResult['period']['closed_at'], 'Closure transition should stamp closed_at using current_time.');
        $this->assertSame('2026-04-08 09:15:00', $closeResult['period']['correction_window_until'], 'Closure transition should stamp +72h correction window.');

        $thrownForbiddenTransition = false;
        try {
            $repositoryBackedService->transition_month('2026-03', ERP_OMD_Period_Service::STATUS_ZAMKNIETY, [
                'time_entries_finalized' => true,
                'project_costs_verified' => true,
                'project_client_completeness' => true,
                'critical_settlement_locks' => true,
            ]);
        } catch (InvalidArgumentException $exception) {
            $thrownForbiddenTransition = true;
        }
        $this->assertTrue($thrownForbiddenTransition, 'Transition should reject unsupported status jumps.');

        $periods = $repositoryBackedService->list_periods();
        $this->assertSame(2, count($periods), 'Repository-backed service should list all known periods.');

        echo "OK ({$this->assertions} assertions)\n";
    }

    private function assertSame($expected, $actual, string $message): void
    {
        $this->assertions++;

        if ($expected !== $actual) {
            throw new RuntimeException($message . ' Expected: ' . var_export($expected, true) . '; got: ' . var_export($actual, true));
        }
    }

    private function assertTrue($value, string $message): void
    {
        $this->assertions++;

        if ($value !== true) {
            throw new RuntimeException($message);
        }
    }

    private function assertFalse($value, string $message): void
    {
        $this->assertions++;

        if ($value !== false) {
            throw new RuntimeException($message);
        }
    }
}

$runner = new OMDPeriodServiceTestRunner();
$runner->run();
