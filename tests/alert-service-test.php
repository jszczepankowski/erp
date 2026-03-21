<?php

declare(strict_types=1);

if (! function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

if (! function_exists('get_option')) {
    function get_option($key, $default = false)
    {
        return $key === 'erp_omd_alert_margin_threshold' ? 10 : $default;
    }
}

if (! function_exists('current_time')) {
    function current_time($format)
    {
        return $format === 'Y-m-d' ? '2026-03-20' : '2026-03-20 12:00:00';
    }
}

if (! function_exists('number_format_i18n')) {
    function number_format_i18n($number, $decimals = 0)
    {
        return number_format((float) $number, $decimals, '.', '');
    }
}

if (! function_exists('wp_list_pluck')) {
    function wp_list_pluck(array $list, $field)
    {
        $values = [];
        foreach ($list as $item) {
            if (is_array($item) && array_key_exists($field, $item)) {
                $values[] = $item[$field];
            }
        }
        return $values;
    }
}

if (! class_exists('ERP_OMD_Employee_Repository')) {
    class ERP_OMD_Employee_Repository
    {
        private $employees;
        public function __construct(array $employees) { $this->employees = $employees; }
        public function all() { return $this->employees; }
    }
}

if (! class_exists('ERP_OMD_Client_Repository')) {
    class ERP_OMD_Client_Repository
    {
        private $clients;
        public function __construct(array $clients = []) { $this->clients = $clients; }
        public function find($id) { return $this->clients[(int) $id] ?? null; }
    }
}

if (! class_exists('ERP_OMD_Client_Rate_Repository')) {
    class ERP_OMD_Client_Rate_Repository
    {
        private $rates;
        public function __construct(array $rates) { $this->rates = $rates; }
        public function for_client($client_id) { return $this->rates[(int) $client_id] ?? []; }
    }
}

if (! class_exists('ERP_OMD_Project_Repository')) {
    class ERP_OMD_Project_Repository
    {
        private $projects;
        public function __construct(array $projects) { $this->projects = $projects; }
        public function all() { return $this->projects; }
    }
}

if (! class_exists('ERP_OMD_Project_Rate_Repository')) {
    class ERP_OMD_Project_Rate_Repository
    {
        private $rates;
        public function __construct(array $rates) { $this->rates = $rates; }
        public function for_project($project_id) { return $this->rates[(int) $project_id] ?? []; }
    }
}

if (! class_exists('ERP_OMD_Project_Financial_Service')) {
    class ERP_OMD_Project_Financial_Service
    {
        private $financials;
        public function __construct(array $financials) { $this->financials = $financials; }
        public function get_project_financials(array $project_ids)
        {
            $rows = [];
            foreach ($project_ids as $project_id) {
                $rows[(int) $project_id] = $this->financials[(int) $project_id] ?? [];
            }
            return $rows;
        }
    }
}

if (! class_exists('ERP_OMD_Time_Entry_Repository')) {
    class ERP_OMD_Time_Entry_Repository
    {
        private $entries;
        public function __construct(array $entries) { $this->entries = $entries; }
        public function all(array $filters = []) { return $this->entries; }
    }
}

require_once __DIR__ . '/../erp-omd/includes/services/class-alert-service.php';

final class AlertServiceTestRunner
{
    private $assertions = 0;

    public function run(): void
    {
        $service = new ERP_OMD_Alert_Service(
            new ERP_OMD_Employee_Repository([
                ['id' => 1, 'user_login' => 'anna', 'status' => 'active'],
                ['id' => 2, 'user_login' => 'jan', 'status' => 'active'],
            ]),
            new ERP_OMD_Client_Repository([1 => ['id' => 1, 'alert_margin_threshold' => 9]]),
            new ERP_OMD_Client_Rate_Repository([
                1 => [],
            ]),
            new ERP_OMD_Project_Repository([
                ['id' => 10, 'client_id' => 1, 'name' => 'SEO', 'status' => 'w_realizacji'],
                ['id' => 11, 'client_id' => 1, 'name' => 'Branding', 'status' => 'w_realizacji'],
            ]),
            new ERP_OMD_Project_Rate_Repository([
                11 => [['role_id' => 5, 'rate' => 120]],
            ]),
            new ERP_OMD_Project_Financial_Service([
                10 => ['revenue' => 1000, 'budget_usage' => 120, 'margin' => 8],
                11 => ['revenue' => 500, 'budget_usage' => 50, 'margin' => 20],
            ]),
            new ERP_OMD_Time_Entry_Repository([
                ['employee_id' => 1, 'entry_date' => '2026-03-19'],
                ['employee_id' => 2, 'entry_date' => '2026-03-10'],
            ])
        );

        $projectAlerts = $service->project_alerts();
        $this->assertSame(3, count($projectAlerts), 'Project alerts should include budget, low margin and missing rates.');

        $timeAlerts = $service->missing_time_entry_alerts();
        $this->assertSame(1, count($timeAlerts), 'Only employees without entries for 3 days should get a reminder alert.');
        $this->assertSame('employee_missing_time_entry', $timeAlerts[0]['code'], 'Reminder alert should use employee_missing_time_entry code.');

        echo "Assertions: {$this->assertions}\n";
        echo "Alert service tests passed.\n";
    }

    private function assertSame($expected, $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new RuntimeException($message . ' Expected: ' . var_export($expected, true) . ' Actual: ' . var_export($actual, true));
        }
    }
}

(new AlertServiceTestRunner())->run();
