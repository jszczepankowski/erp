<?php

declare(strict_types=1);

if (! function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

if (! function_exists('current_time')) {
    function current_time($type)
    {
        return '2026-03-20 12:00:00';
    }
}

if (! class_exists('ERP_OMD_Project_Repository')) {
    class ERP_OMD_Project_Repository
    {
        private $projects;

        public function __construct(array $projects)
        {
            $this->projects = $projects;
        }

        public function find($id)
        {
            return $this->projects[(int) $id] ?? null;
        }
    }
}

if (! class_exists('ERP_OMD_Project_Cost_Repository')) {
    class ERP_OMD_Project_Cost_Repository
    {
        private $costs;

        public function __construct(array $costs)
        {
            $this->costs = $costs;
        }

        public function for_project($project_id)
        {
            return $this->costs[(int) $project_id] ?? [];
        }
    }
}

if (! class_exists('ERP_OMD_Project_Financial_Repository')) {
    class ERP_OMD_Project_Financial_Repository
    {
        public $records = [];

        public function __construct(array $records = [])
        {
            foreach ($records as $project_id => $record) {
                $this->records[(int) $project_id] = array_merge(['project_id' => (int) $project_id], $record);
            }
        }

        public function upsert($project_id, array $data)
        {
            $this->records[(int) $project_id] = array_merge(['project_id' => (int) $project_id], $data);

            return (int) $project_id;
        }

        public function find_by_project($project_id)
        {
            return $this->records[(int) $project_id] ?? null;
        }

        public function find_by_projects(array $project_ids)
        {
            $rows = [];

            foreach ($project_ids as $project_id) {
                if (isset($this->records[(int) $project_id])) {
                    $rows[(int) $project_id] = $this->records[(int) $project_id];
                }
            }

            return $rows;
        }
    }
}

if (! class_exists('ERP_OMD_Project_Revenue_Repository')) {
    class ERP_OMD_Project_Revenue_Repository
    {
        private $revenues;

        public function __construct(array $revenues)
        {
            $this->revenues = $revenues;
        }

        public function for_project($project_id)
        {
            return $this->revenues[(int) $project_id] ?? [];
        }
    }
}

if (! class_exists('ERP_OMD_Time_Entry_Repository')) {
    class ERP_OMD_Time_Entry_Repository
    {
        private $entries;
        public $allCalls = 0;

        public function __construct(array $entries)
        {
            $this->entries = $entries;
        }

        public function all(array $filters = [])
        {
            $this->allCalls++;
            $project_id = (int) ($filters['project_id'] ?? 0);

            return $this->entries[$project_id] ?? [];
        }
    }
}

require_once __DIR__ . '/../erp-omd/includes/services/class-project-financial-service.php';

final class ProjectFinancialServiceTestRunner
{
    private $assertions = 0;

    public function run(): void
    {
        $financialsRepository = new ERP_OMD_Project_Financial_Repository();
        $timeEntriesRepository = new ERP_OMD_Time_Entry_Repository([
            10 => [
                ['hours' => 2, 'rate_snapshot' => 100, 'cost_snapshot' => 40, 'status' => 'approved'],
                ['hours' => 1, 'rate_snapshot' => 80, 'cost_snapshot' => 20, 'status' => 'submitted'],
                ['hours' => 3, 'rate_snapshot' => 90, 'cost_snapshot' => 30, 'status' => 'rejected'],
            ],
            11 => [
                ['hours' => 10, 'rate_snapshot' => 999, 'cost_snapshot' => 50, 'status' => 'approved'],
            ],
            12 => [],
            13 => [
                ['hours' => 4, 'rate_snapshot' => 120, 'cost_snapshot' => 40, 'status' => 'approved'],
            ],
        ]);
        $service = new ERP_OMD_Project_Financial_Service(
            new ERP_OMD_Project_Repository([
                10 => ['id' => 10, 'billing_type' => 'time_material', 'budget' => 1000, 'retainer_monthly_fee' => 0, 'status' => 'w_realizacji', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31'],
                11 => ['id' => 11, 'billing_type' => 'fixed_price', 'budget' => 5000, 'retainer_monthly_fee' => 0, 'status' => 'do_faktury', 'start_date' => '2026-01-01', 'end_date' => '2026-01-31'],
                12 => ['id' => 12, 'billing_type' => 'retainer', 'budget' => 0, 'retainer_monthly_fee' => 1500, 'status' => 'w_realizacji', 'start_date' => '2026-01-15', 'end_date' => '2026-03-20'],
                13 => ['id' => 13, 'billing_type' => 'fixed_price', 'budget' => 3200, 'retainer_monthly_fee' => 0, 'status' => 'w_realizacji', 'start_date' => '2026-02-01', 'end_date' => '2026-04-30'],
            ]),
            new ERP_OMD_Project_Cost_Repository([
                10 => [
                    ['amount' => 100.0],
                    ['amount' => 50.0],
                ],
                11 => [
                    ['amount' => 400.0],
                ],
            ]),
            new ERP_OMD_Project_Revenue_Repository([]),
            $financialsRepository,
            $timeEntriesRepository
        );

        $tmFinancials = $service->rebuild_for_project(10);
        $this->assertSame(200.0, $tmFinancials['revenue'], 'Time & material revenue should use approved time entry snapshots only.');
        $this->assertSame(230.0, $tmFinancials['cost'], 'Project cost should combine approved time cost and direct costs.');
        $this->assertSame(-30.0, $tmFinancials['profit'], 'Profit should be revenue minus total cost.');
        $this->assertSame(23.0, $tmFinancials['budget_usage'], 'Budget usage should be cost divided by budget.');

        $fixedFinancials = $service->rebuild_for_project(11);
        $this->assertSame(5000.0, $fixedFinancials['revenue'], 'Fixed price revenue should always recognize the project budget.');
        $this->assertSame(900.0, $fixedFinancials['cost'], 'Fixed price cost should include time and direct costs.');

        $fixedInProgressFinancials = $service->rebuild_for_project(13);
        $this->assertSame(3200.0, $fixedInProgressFinancials['revenue'], 'Fixed price revenue should also use the project budget after changing billing type on an in-progress project.');
        $this->assertSame(160.0, $fixedInProgressFinancials['time_cost'], 'Fixed price project should expose time-based labor cost from reported hours.');
        $this->assertSame(160.0, $fixedInProgressFinancials['cost'], 'Fixed price project total cost should include labor time cost even without direct costs.');

        $retainerFinancials = $service->rebuild_for_project(12);
        $this->assertSame(4500.0, $retainerFinancials['revenue'], 'Retainer revenue should count inclusive active months.');

        $cachedRepository = new ERP_OMD_Project_Financial_Repository([
            10 => [
                'revenue' => 321.0,
                'cost' => 123.0,
                'profit' => 198.0,
                'margin' => 61.68,
                'budget_usage' => 12.3,
                'time_revenue' => 321.0,
                'time_cost' => 100.0,
                'direct_cost' => 23.0,
                'last_recalculated_at' => '2026-03-20 12:00:00',
            ],
        ]);
        $cachedTimeEntriesRepository = new ERP_OMD_Time_Entry_Repository([
            10 => [
                ['hours' => 99, 'rate_snapshot' => 99, 'cost_snapshot' => 99, 'status' => 'approved'],
            ],
        ]);
        $cachedService = new ERP_OMD_Project_Financial_Service(
            new ERP_OMD_Project_Repository([
                10 => ['id' => 10, 'billing_type' => 'time_material', 'budget' => 1000, 'retainer_monthly_fee' => 0, 'status' => 'w_realizacji', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31'],
            ]),
            new ERP_OMD_Project_Cost_Repository([
                10 => [
                    ['amount' => 500.0],
                ],
            ]),
            new ERP_OMD_Project_Revenue_Repository([]),
            $cachedRepository,
            $cachedTimeEntriesRepository
        );

        $cachedFinancials = $cachedService->get_project_financial(10);
        $this->assertSame(321.0, $cachedFinancials['revenue'], 'Cached financial snapshot should be returned when available.');
        $this->assertSame(0, $cachedTimeEntriesRepository->allCalls, 'Using cached financial snapshot should not force a full rebuild.');

        $financialIndex = $cachedService->get_project_financials([10]);
        $this->assertSame(321.0, $financialIndex[10]['revenue'], 'Bulk financial lookup should reuse cached project financials.');
        $this->assertSame(0, $cachedTimeEntriesRepository->allCalls, 'Bulk cached lookup should not rebuild cached projects.');

        $errors = $service->validate_project_cost([
            'project_id' => 10,
            'amount' => -1,
            'description' => 'Invalid',
            'cost_date' => '2026-99-99',
            'created_by_user_id' => 1,
        ]);
        $this->assertSame(2, count($errors), 'Project cost validation should catch amount and date errors.');

        echo "Assertions: {$this->assertions}\n";
        echo "Project financial service tests passed.\n";
    }

    private function assertSame($expected, $actual, string $message): void
    {
        $this->assertions++;

        if ($expected !== $actual) {
            throw new RuntimeException($message . " Expected: " . var_export($expected, true) . ' Actual: ' . var_export($actual, true));
        }
    }
}

(new ProjectFinancialServiceTestRunner())->run();
