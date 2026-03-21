<?php

declare(strict_types=1);

if (! function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

if (! function_exists('user_can')) {
    function user_can($user, $capability)
    {
        return ! empty($user->caps[$capability]);
    }
}

if (! class_exists('WP_User')) {
    class WP_User
    {
        public $ID;
        public $caps;

        public function __construct(int $id, array $caps = [])
        {
            $this->ID = $id;
            $this->caps = $caps;
        }
    }
}

if (! class_exists('ERP_OMD_Time_Entry_Repository')) {
    class ERP_OMD_Time_Entry_Repository
    {
        private $duplicates;

        public function __construct(array $duplicates = [])
        {
            $this->duplicates = $duplicates;
        }

        public function duplicate_exists($employee_id, $project_id, $role_id, $hours, $exclude_id = null)
        {
            foreach ($this->duplicates as $duplicate) {
                if (
                    (int) $duplicate['employee_id'] === (int) $employee_id &&
                    (int) $duplicate['project_id'] === (int) $project_id &&
                    (int) $duplicate['role_id'] === (int) $role_id &&
                    (float) $duplicate['hours'] === (float) $hours &&
                    (int) ($duplicate['id'] ?? 0) !== (int) $exclude_id
                ) {
                    return true;
                }
            }

            return false;
        }
    }
}

if (! class_exists('ERP_OMD_Employee_Repository')) {
    class ERP_OMD_Employee_Repository
    {
        private $employees;
        private $employees_by_user;

        public function __construct(array $employees = [])
        {
            $this->employees = $employees;
            $this->employees_by_user = [];

            foreach ($employees as $employee) {
                if (isset($employee['user_id'])) {
                    $this->employees_by_user[(int) $employee['user_id']] = $employee;
                }
            }
        }

        public function find($id)
        {
            return $this->employees[(int) $id] ?? null;
        }

        public function find_by_user_id($user_id)
        {
            return $this->employees_by_user[(int) $user_id] ?? null;
        }
    }
}

if (! class_exists('ERP_OMD_Project_Repository')) {
    class ERP_OMD_Project_Repository
    {
        private $projects;

        public function __construct(array $projects = [])
        {
            $this->projects = $projects;
        }

        public function find($id)
        {
            return $this->projects[(int) $id] ?? null;
        }

        public function ids_managed_by_employee($employee_id)
        {
            $ids = [];

            foreach ($this->projects as $project) {
                $manager_ids = array_map('intval', (array) ($project['manager_ids'] ?? []));
                if ($manager_ids === [] && ! empty($project['manager_id'])) {
                    $manager_ids[] = (int) $project['manager_id'];
                }
                if (in_array((int) $employee_id, $manager_ids, true)) {
                    $ids[] = (int) $project['id'];
                }
            }

            sort($ids);

            return $ids;
        }
    }
}

if (! class_exists('ERP_OMD_Role_Repository')) {
    class ERP_OMD_Role_Repository
    {
        private $roles;

        public function __construct(array $roles = [])
        {
            $this->roles = $roles;
        }

        public function find($id)
        {
            return $this->roles[(int) $id] ?? null;
        }
    }
}

if (! class_exists('ERP_OMD_Client_Rate_Repository')) {
    class ERP_OMD_Client_Rate_Repository
    {
        private $rates_by_client;

        public function __construct(array $rates_by_client = [])
        {
            $this->rates_by_client = $rates_by_client;
        }

        public function for_client($client_id)
        {
            return $this->rates_by_client[(int) $client_id] ?? [];
        }
    }
}

if (! class_exists('ERP_OMD_Project_Rate_Repository')) {
    class ERP_OMD_Project_Rate_Repository
    {
        private $rates_by_key;

        public function __construct(array $rates_by_key = [])
        {
            $this->rates_by_key = $rates_by_key;
        }

        public function find_by_project_role($project_id, $role_id)
        {
            $key = (int) $project_id . ':' . (int) $role_id;

            return $this->rates_by_key[$key] ?? null;
        }
    }
}

if (! class_exists('ERP_OMD_Salary_History_Repository')) {
    class ERP_OMD_Salary_History_Repository
    {
        private $history_by_employee;

        public function __construct(array $history_by_employee = [])
        {
            $this->history_by_employee = $history_by_employee;
        }

        public function for_employee($employee_id)
        {
            return $this->history_by_employee[(int) $employee_id] ?? [];
        }
    }
}

require_once __DIR__ . '/../erp-omd/includes/services/class-time-entry-service.php';

final class TimeEntryServiceTestRunner
{
    private $assertions = 0;

    public function run(): void
    {
        $service = $this->makeService();

        $this->assertSame(200.0, $service->resolve_rate_snapshot(10, 7), 'Project rate should override client rate.');
        $this->assertSame(80.0, $service->resolve_rate_snapshot(11, 8), 'Client rate should be used when no project override exists.');
        $this->assertSame(0.0, $service->resolve_rate_snapshot(11, 99), 'Missing rate should resolve to zero.');

        $this->assertSame(55.5, $service->resolve_cost_snapshot(1, '2026-03-15'), 'Cost snapshot should use matching salary history period.');
        $this->assertSame(0.0, $service->resolve_cost_snapshot(1, '2025-12-01'), 'Missing salary history should resolve to zero.');

        $errors = $service->validate([
            'employee_id' => 1,
            'project_id' => 12,
            'role_id' => 7,
            'hours' => 2.5,
            'entry_date' => '2026-03-20',
            'status' => 'submitted',
        ]);
        $this->assertTrue(
            in_array('Czas można raportować tylko do projektów w statusie w_realizacji.', $errors, true),
            'Validation should block time entries for projects outside w_realizacji.'
        );

        $errors = $service->validate([
            'employee_id' => 1,
            'project_id' => 10,
            'role_id' => 7,
            'hours' => 4.0,
            'entry_date' => '2026-03-20',
            'status' => 'submitted',
        ]);
        $this->assertTrue(
            in_array('Duplikat wpisu czasu dla employee_id + project_id + role_id + hours.', $errors, true),
            'Validation should block duplicate time entries.'
        );

        $worker = new WP_User(101, ['erp_omd_manage_time' => true]);
        $ownerEntry = ['employee_id' => 1, 'project_id' => 10, 'status' => 'submitted'];
        $approvedOwnerEntry = ['employee_id' => 1, 'project_id' => 10, 'status' => 'approved'];
        $foreignEntry = ['employee_id' => 2, 'project_id' => 10, 'status' => 'submitted'];
        $this->assertTrue($service->can_view_entry($ownerEntry, $worker), 'Worker should be able to view own time entries.');
        $this->assertFalse($service->can_view_entry($foreignEntry, $worker), 'Worker should not be able to view foreign time entries.');
        $this->assertTrue($service->can_edit_entry($ownerEntry, $worker), 'Worker should be able to edit own submitted entry.');
        $this->assertFalse($service->can_edit_entry($approvedOwnerEntry, $worker), 'Worker should not be able to edit approved entry.');
        $this->assertTrue($service->can_delete_entry($worker, $ownerEntry), 'Worker should be able to delete own submitted entry.');
        $this->assertFalse($service->can_delete_entry($worker, $approvedOwnerEntry), 'Worker should not be able to delete approved entry.');

        $projectManager = new WP_User(202, ['erp_omd_manage_time' => true, 'erp_omd_approve_time' => true]);
        $otherManager = new WP_User(303, ['erp_omd_manage_time' => true, 'erp_omd_approve_time' => true]);
        $secondaryManager = new WP_User(505, ['erp_omd_manage_time' => true, 'erp_omd_approve_time' => true]);
        $managedEntry = ['employee_id' => 2, 'project_id' => 10, 'status' => 'submitted'];
        $this->assertTrue($service->can_view_entry($managedEntry, $projectManager), 'Assigned project manager should be able to view managed project entries.');
        $this->assertTrue($service->can_approve_entry($managedEntry, $projectManager), 'Assigned project manager should be able to approve entries.');
        $this->assertTrue($service->can_approve_entry($managedEntry, $secondaryManager), 'Additional project manager should also be able to approve entries.');
        $this->assertFalse($service->can_approve_entry($managedEntry, $otherManager), 'Unassigned manager should not be able to approve entries.');

        $admin = new WP_User(999, ['administrator' => true]);
        $this->assertTrue($service->can_view_entry($managedEntry, $admin), 'Administrator should be able to view all entries.');
        $this->assertTrue($service->can_approve_entry($managedEntry, $admin), 'Administrator should be able to approve all entries.');

        $workerFilters = $service->get_visible_filters_for_user($worker, ['status' => 'submitted']);
        $this->assertSame(1, $workerFilters['employee_id'], 'Worker filters should be pinned to current employee.');

        $entries = [
            ['employee_id' => 1, 'project_id' => 10],
            ['employee_id' => 2, 'project_id' => 10],
            ['employee_id' => 2, 'project_id' => 11],
        ];
        $visibleToManager = $service->filter_visible_entries($entries, $projectManager);
        $this->assertSame(2, count($visibleToManager), 'Project manager should only see own entries and entries for managed projects.');

        echo "Assertions: {$this->assertions}\n";
        echo "Time entry service tests passed.\n";
    }

    private function makeService(): ERP_OMD_Time_Entry_Service
    {
        return new ERP_OMD_Time_Entry_Service(
            new ERP_OMD_Time_Entry_Repository([
                ['id' => 500, 'employee_id' => 1, 'project_id' => 10, 'role_id' => 7, 'hours' => 4.0],
            ]),
            new ERP_OMD_Employee_Repository([
                1 => ['id' => 1, 'user_id' => 101],
                2 => ['id' => 2, 'user_id' => 404],
                3 => ['id' => 3, 'user_id' => 202],
                4 => ['id' => 4, 'user_id' => 303],
                5 => ['id' => 5, 'user_id' => 505],
            ]),
            new ERP_OMD_Project_Repository([
                10 => ['id' => 10, 'client_id' => 20, 'status' => 'w_realizacji', 'manager_id' => 3, 'manager_ids' => [3, 5]],
                11 => ['id' => 11, 'client_id' => 21, 'status' => 'w_realizacji', 'manager_id' => 4, 'manager_ids' => [4]],
                12 => ['id' => 12, 'client_id' => 20, 'status' => 'do_rozpoczecia', 'manager_id' => 3, 'manager_ids' => [3]],
            ]),
            new ERP_OMD_Role_Repository([
                7 => ['id' => 7, 'name' => 'Developer'],
                8 => ['id' => 8, 'name' => 'Designer'],
            ]),
            new ERP_OMD_Client_Rate_Repository([
                20 => [
                    ['client_id' => 20, 'role_id' => 7, 'rate' => 120.0],
                ],
                21 => [
                    ['client_id' => 21, 'role_id' => 8, 'rate' => 80.0],
                ],
            ]),
            new ERP_OMD_Project_Rate_Repository([
                '10:7' => ['project_id' => 10, 'role_id' => 7, 'rate' => 200.0],
            ]),
            new ERP_OMD_Salary_History_Repository([
                1 => [
                    [
                        'employee_id' => 1,
                        'hourly_cost' => 55.5,
                        'valid_from' => '2026-03-01',
                        'valid_to' => '2026-03-31',
                    ],
                ],
            ])
        );
    }

    private function assertSame($expected, $actual, string $message): void
    {
        $this->assertions++;

        if ($expected !== $actual) {
            throw new RuntimeException($message . " Expected: " . var_export($expected, true) . ' Actual: ' . var_export($actual, true));
        }
    }

    private function assertTrue($condition, string $message): void
    {
        $this->assertions++;

        if (! $condition) {
            throw new RuntimeException($message);
        }
    }

    private function assertFalse($condition, string $message): void
    {
        $this->assertions++;

        if ($condition) {
            throw new RuntimeException($message);
        }
    }
}

(new TimeEntryServiceTestRunner())->run();
