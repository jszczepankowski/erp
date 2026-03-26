<?php

declare(strict_types=1);

if (! function_exists('sanitize_text_field')) {
    function sanitize_text_field($value)
    {
        return trim((string) $value);
    }
}

if (! function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

if (! function_exists('get_option')) {
    function get_option($name, $default = false)
    {
        global $erp_omd_test_options;
        if (is_array($erp_omd_test_options) && array_key_exists($name, $erp_omd_test_options)) {
            return $erp_omd_test_options[$name];
        }
        return $default;
    }
}

if (! function_exists('sanitize_key')) {
    function sanitize_key($value)
    {
        return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value));
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

if (! class_exists('ERP_OMD_Project_Repository')) {
    class ERP_OMD_Project_Repository
    {
        private $projects;
        public function __construct(array $projects) { $this->projects = $projects; }
        public function all() { return $this->projects; }
    }
}

if (! class_exists('ERP_OMD_Client_Repository')) {
    class ERP_OMD_Client_Repository
    {
        private $clients;
        public function __construct(array $clients) { $this->clients = $clients; }
        public function all() { return $this->clients; }
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

if (! class_exists('ERP_OMD_Salary_History_Repository')) {
    class ERP_OMD_Salary_History_Repository
    {
        private $rows;
        public function __construct(array $rows) { $this->rows = $rows; }
        public function for_employee($employee_id) { return $this->rows[(int) $employee_id] ?? []; }
    }
}

if (! class_exists('ERP_OMD_Project_Cost_Repository')) {
    class ERP_OMD_Project_Cost_Repository
    {
        private $costs;
        public function __construct(array $costs) { $this->costs = $costs; }
        public function for_project($project_id) { return $this->costs[(int) $project_id] ?? []; }
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

if (! class_exists('ERP_OMD_Project_Financial_Service')) {
    class ERP_OMD_Project_Financial_Service
    {
        private $financials;
        public function __construct(array $financials) { $this->financials = $financials; }
        public function get_project_financials(array $project_ids)
        {
            $rows = [];
            foreach ($project_ids as $project_id) {
                if (isset($this->financials[(int) $project_id])) {
                    $rows[(int) $project_id] = $this->financials[(int) $project_id];
                }
            }
            return $rows;
        }
    }
}

require_once __DIR__ . '/../erp-omd/includes/services/class-reporting-service.php';

final class ReportingServiceTestRunner
{
    private $assertions = 0;

    public function run(): void
    {
        global $erp_omd_test_options;
        $erp_omd_test_options = [
            'erp_omd_fixed_monthly_cost_items' => [
                ['name' => 'Biuro', 'amount' => 1000.0, 'valid_from' => '2026-01-01', 'valid_to' => '', 'active' => 1],
                ['name' => 'Nieaktywne', 'amount' => 999.0, 'valid_from' => '2026-01-01', 'valid_to' => '', 'active' => 0],
            ],
            'erp_omd_fixed_monthly_cost' => 500.0,
        ];

        $service = new ERP_OMD_Reporting_Service(
            new ERP_OMD_Project_Repository([
                ['id' => 10, 'client_id' => 1, 'name' => 'SEO', 'client_name' => 'ACME', 'status' => 'w_realizacji', 'billing_type' => 'time_material', 'manager_login' => 'manager', 'budget' => 1000],
                ['id' => 11, 'client_id' => 2, 'name' => 'Branding', 'client_name' => 'Globex', 'status' => 'do_faktury', 'billing_type' => 'fixed_price', 'manager_login' => 'manager2', 'budget' => 5000],
            ]),
            new ERP_OMD_Client_Repository([
                ['id' => 1, 'name' => 'ACME'],
                ['id' => 2, 'name' => 'Globex'],
            ]),
            new ERP_OMD_Employee_Repository([
                ['id' => 1, 'user_login' => 'anna'],
                ['id' => 2, 'user_login' => 'jan'],
            ]),
            new ERP_OMD_Salary_History_Repository([
                1 => [['monthly_salary' => 10000.0, 'valid_from' => '2026-01-01', 'valid_to' => null]],
                2 => [['monthly_salary' => 9000.0, 'valid_from' => '2026-01-01', 'valid_to' => null]],
            ]),
            new ERP_OMD_Project_Cost_Repository([
                10 => [
                    ['amount' => 100.0, 'cost_date' => '2026-03-10'],
                ],
                11 => [
                    ['amount' => 250.0, 'cost_date' => '2026-03-05'],
                ],
            ]),
            new ERP_OMD_Time_Entry_Repository([
                ['project_id' => 10, 'client_id' => 1, 'employee_id' => 1, 'hours' => 2, 'entry_date' => '2026-03-10', 'status' => 'approved', 'rate_snapshot' => 100, 'cost_snapshot' => 40],
                ['project_id' => 10, 'client_id' => 1, 'employee_id' => 2, 'hours' => 1, 'entry_date' => '2026-03-12', 'status' => 'submitted', 'rate_snapshot' => 120, 'cost_snapshot' => 50],
                ['project_id' => 11, 'client_id' => 2, 'employee_id' => 1, 'hours' => 3, 'entry_date' => '2026-03-15', 'status' => 'approved', 'rate_snapshot' => 200, 'cost_snapshot' => 80],
            ]),
            new ERP_OMD_Project_Financial_Service([
                10 => ['revenue' => 320.0, 'cost' => 190.0, 'profit' => 130.0, 'margin' => 40.63, 'budget_usage' => 19.0],
                11 => ['revenue' => 5000.0, 'cost' => 490.0, 'profit' => 4510.0, 'margin' => 90.2, 'budget_usage' => 9.8],
            ])
        );

        $filters = $service->sanitize_filters(['report_type' => 'projects', 'month' => '2026-03']);
        $this->assertSame('projects', $filters['report_type'], 'Project report should remain selected.');
        $this->assertSame('2026-03', $filters['month'], 'Valid month filter should be preserved.');

        $projectReport = $service->build_project_report($filters);
        $this->assertSame(2, count($projectReport), 'Project report should include all matching projects.');
        $this->assertSame(3.0, $projectReport[0]['reported_hours'], 'Project report should aggregate filtered hours per project.');

        $approvedFilters = $service->sanitize_filters(['report_type' => 'projects', 'month' => '2026-03', 'status' => 'approved']);
        $approvedProjectReport = $service->build_project_report($approvedFilters);
        $this->assertSame(2, count($approvedProjectReport), 'Approved time-entry filter should not hide projects by project lifecycle status.');
        $this->assertSame(2.0, $approvedProjectReport[0]['reported_hours'], 'Approved filter should only count approved project hours.');

        $invoiceStatusFilters = $service->sanitize_filters(['report_type' => 'projects', 'month' => '2026-03', 'status' => 'do_faktury']);
        $invoiceStatusProjectReport = $service->build_project_report($invoiceStatusFilters);
        $this->assertSame(1, count($invoiceStatusProjectReport), 'Project status filter should narrow projects by lifecycle status.');
        $this->assertSame('Branding', $invoiceStatusProjectReport[0]['project_name'], 'Project status filter should keep matching project rows.');

        $clientReport = $service->build_client_report($filters);
        $this->assertSame(2, count($clientReport), 'Client report should aggregate per client.');
        $this->assertSame('ACME', $clientReport[0]['client_name'], 'Client report should keep client name.');

        $invoiceReport = $service->build_invoice_report($filters);
        $this->assertSame(1, count($invoiceReport), 'Invoice report should only include do_faktury projects.');
        $this->assertSame('Branding', $invoiceReport[0]['project_name'], 'Invoice report should return the invoice-ready project.');

        $monthlyReport = $service->build_monthly_report($filters);
        $this->assertSame(1, count($monthlyReport), 'Monthly report should group entries by month.');
        $this->assertSame(6.0, $monthlyReport[0]['hours'], 'Monthly report should sum hours for the month.');

        $omdSettlement = $service->build_report('omd_rozliczenia', $filters);
        $this->assertSame(12, count($omdSettlement), 'OMD settlement report should return a 12-month trend.');
        $this->assertSame('2026-03', $omdSettlement[11]['month'], 'OMD settlement report should end with selected month.');
        $this->assertSame(19000.0, $omdSettlement[11]['salary_cost'], 'OMD settlement report should include full monthly salaries for active month.');
        $this->assertSame(1000.0, $omdSettlement[11]['fixed_cost'], 'OMD settlement report should sum active fixed-cost items for selected month.');

        $calendar = $service->build_calendar(['month' => '2026-03', 'client_id' => 0, 'project_id' => 0, 'employee_id' => 0, 'status' => '', 'report_type' => 'projects', 'tab' => 'calendar']);
        $this->assertSame('2026-03', $calendar['month'], 'Calendar should be built for requested month.');
        $this->assertSame(6.0, $calendar['totals']['hours'], 'Calendar totals should aggregate daily hours.');

        $approvedCalendar = $service->build_calendar(['month' => '2026-03', 'client_id' => 0, 'project_id' => 0, 'employee_id' => 0, 'status' => 'approved', 'report_type' => 'projects', 'tab' => 'calendar']);
        $this->assertSame(5.0, $approvedCalendar['totals']['hours'], 'Calendar approved filter should keep approved entry hours.');

        $export = $service->export_definition('projects', $filters);
        $this->assertSame('Klient', $export['headers'][0], 'Project export should expose column headers.');
        $this->assertSame(2, count($export['rows']), 'Project export should include report rows.');

        echo "Assertions: {$this->assertions}\n";
        echo "Reporting service tests passed.\n";
    }

    private function assertSame($expected, $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new RuntimeException($message . ' Expected: ' . var_export($expected, true) . ' Actual: ' . var_export($actual, true));
        }
    }
}

(new ReportingServiceTestRunner())->run();
