<?php

declare(strict_types=1);

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
