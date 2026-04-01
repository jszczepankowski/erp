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
        $service = new ERP_OMD_Reporting_Service(
            new ERP_OMD_Project_Repository([
                ['id' => 10, 'client_id' => 1, 'name' => 'SEO', 'client_name' => 'ACME', 'status' => 'w_realizacji', 'billing_type' => 'time_material', 'manager_login' => 'manager', 'budget' => 1000, 'operational_close_month' => '2026-02'],
                ['id' => 11, 'client_id' => 2, 'name' => 'Branding', 'client_name' => 'Globex', 'status' => 'do_faktury', 'billing_type' => 'fixed_price', 'manager_login' => 'manager2', 'budget' => 5000, 'operational_close_month' => '2026-03'],
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
        $this->assertSame(1, $filters['page_num'], 'Filters should default to first page.');
        $this->assertSame(25, $filters['per_page'], 'Filters should default to 25 rows per page.');

        $projectReport = $service->build_project_report($filters);
        $this->assertSame(2, count($projectReport), 'Project report should include all matching projects.');
        $this->assertSame(2.0, $projectReport[0]['reported_hours'], 'Project report should aggregate approved hours per project by default.');

        $approvedFilters = $service->sanitize_filters(['report_type' => 'projects', 'month' => '2026-03', 'status' => 'approved']);
        $approvedProjectReport = $service->build_project_report($approvedFilters);
        $this->assertSame(2, count($approvedProjectReport), 'Approved time-entry filter should not hide projects by project lifecycle status.');
        $this->assertSame(2.0, $approvedProjectReport[0]['reported_hours'], 'Approved filter should only count approved project hours.');

        $invoiceStatusFilters = $service->sanitize_filters(['report_type' => 'projects', 'month' => '2026-03', 'status' => 'do_faktury']);
        $invoiceStatusProjectReport = $service->build_project_report($invoiceStatusFilters);
        $this->assertSame(1, count($invoiceStatusProjectReport), 'Project status filter should narrow projects by lifecycle status.');
        $this->assertSame('Branding', $invoiceStatusProjectReport[0]['project_name'], 'Project status filter should keep matching project rows.');

        $settlementModeReport = $service->build_project_report($service->sanitize_filters(['report_type' => 'projects', 'month' => '2026-03', 'mode' => 'DO_ROZLICZENIA']));
        $this->assertSame(1, count($settlementModeReport), 'DO ROZLICZENIA mode should keep invoice-ready/closed projects only.');

        $projectDetail = $service->build_project_report($service->sanitize_filters(['report_type' => 'projects', 'month' => '2026-03', 'detail' => 'detail', 'project_id' => 10]));
        $this->assertSame(1, count($projectDetail), 'Project detail report should respect project filter.');
        $this->assertSame(true, isset($projectDetail[0]['detail']['time_entries']), 'Project detail report should include time entry drilldown rows.');
        $this->assertSame(1, count($projectDetail[0]['detail']['time_entries']), 'Project detail report should include approved entries for selected month by default.');
        $this->assertSame(1, count($projectDetail[0]['detail']['direct_cost_items']), 'Project detail report should include direct cost rows for selected month.');
        $this->assertSame(200.0, $projectDetail[0]['detail']['billing_mix']['hourly_component'], 'Project detail report should expose billing mix hourly component.');
        $this->assertSame(19.0, $projectDetail[0]['detail']['billing_mix']['budget_usage'], 'Project detail report should expose billing mix budget usage.');

        $clientReport = $service->build_client_report($filters);
        $this->assertSame(2, count($clientReport), 'Client report should aggregate per client.');
        $this->assertSame('ACME', $clientReport[0]['client_name'], 'Client report should keep client name.');
        $this->assertSame('/wp-admin/admin.php?page=erp-omd-reports&report_type=clients&month=2026-03&detail=detail&client_id=1', $clientReport[0]['drilldown_link'], 'Client report should expose drilldown link to detail view.');

        $clientDetail = $service->build_client_report($service->sanitize_filters(['report_type' => 'clients', 'month' => '2026-03', 'detail' => 'detail', 'client_id' => 1]));
        $this->assertSame(1, count($clientDetail), 'Client detail report should respect client filter.');
        $this->assertSame(1, count($clientDetail[0]['projects']), 'Client detail report should include project-level rows.');
        $this->assertSame('/wp-admin/admin.php?page=erp-omd-reports&report_type=time_entries&month=2026-03&project_id=10', $clientDetail[0]['projects'][0]['drilldown_link'], 'Client detail projects should expose drilldown to line-by-line time entries.');

        $invoiceReport = $service->build_invoice_report($filters);
        $this->assertSame(1, count($invoiceReport), 'Invoice report should only include do_faktury projects.');
        $this->assertSame('Branding', $invoiceReport[0]['project_name'], 'Invoice report should return the invoice-ready project.');

        $monthlyReport = $service->build_monthly_report($filters);
        $this->assertSame(1, count($monthlyReport), 'Monthly report should group entries by month.');
        $this->assertSame(5.0, $monthlyReport[0]['hours'], 'Monthly report should sum approved hours for the month.');

        $timeEntriesPage2 = $service->build_report('time_entries', $service->sanitize_filters(['report_type' => 'time_entries', 'month' => '2026-03', 'per_page' => 1, 'page_num' => 2]));
        $timePagination = $service->get_last_report_pagination();
        $this->assertSame(2, $timePagination['total_items'], 'Time entries pagination should expose total approved rows for the month.');
        $this->assertSame(2, $timePagination['total_pages'], 'Time entries pagination should expose number of pages.');
        $this->assertSame(2, $timePagination['page_num'], 'Time entries pagination should keep current page.');
        $this->assertSame(1, count($timeEntriesPage2), 'Time entries report should return only rows for current page.');
        $this->assertSame('2026-03-10', $timeEntriesPage2[0]['entry_date'], 'Time entries pagination should return the second row on page 2.');

        $omdSettlement = $service->build_omd_settlement_report($filters);
        $this->assertSame(12, count($omdSettlement), 'OMD settlement report should return a 12-month trend.');
        $this->assertSame('2026-03', $omdSettlement[11]['month'], 'OMD settlement report should end with selected month.');
        $this->assertSame(0.0, $omdSettlement[10]['active_project_budgets'], 'OMD settlement should not recognize project budget before operational_close_month.');
        $this->assertSame(5000.0, $omdSettlement[11]['active_project_budgets'], 'OMD settlement should recognize project budget in operational_close_month.');
        $this->assertSame(19000.0, $omdSettlement[11]['salary_cost'], 'OMD settlement report should include full monthly salaries for active month.');
        $this->assertSame(5130.0, $omdSettlement[11]['operational_result'], 'OMD settlement should expose operational result before controlling overhead.');
        $this->assertSame(19000.0, $omdSettlement[11]['controlling_overhead'], 'OMD settlement should expose controlling overhead components.');
        $this->assertSame(-13870.0, $omdSettlement[11]['controlling_result'], 'OMD settlement should expose controlling result after overhead.');

        $calendar = $service->build_calendar(['month' => '2026-03', 'client_id' => 0, 'project_id' => 0, 'employee_id' => 0, 'status' => '', 'report_type' => 'projects', 'tab' => 'calendar']);
        $this->assertSame('2026-03', $calendar['month'], 'Calendar should be built for requested month.');
        $this->assertSame(5.0, $calendar['totals']['hours'], 'Calendar totals should aggregate approved daily hours by default.');

        $approvedCalendar = $service->build_calendar(['month' => '2026-03', 'client_id' => 0, 'project_id' => 0, 'employee_id' => 0, 'status' => 'approved', 'report_type' => 'projects', 'tab' => 'calendar']);
        $this->assertSame(5.0, $approvedCalendar['totals']['hours'], 'Calendar approved filter should keep approved entry hours.');

        $export = $service->export_definition('projects', $filters);
        $this->assertSame('Klient', $export['headers'][0], 'Project export should expose column headers.');
        $this->assertSame('Miesiąc zamk. oper.', $export['headers'][16], 'Project export should include operational close month column.');
        $this->assertSame(2, count($export['rows']), 'Project export should include report rows.');
        $this->assertSame('2026-02', $export['rows'][0][16], 'Project export should include operational close month value.');

        $timeExport = $service->export_definition('time_entries', $service->sanitize_filters(['report_type' => 'time_entries', 'month' => '2026-03', 'per_page' => 1, 'page_num' => 2]));
        $this->assertSame(1, count($timeExport['rows']), 'Time entries export should match paginated time report row count.');
        $this->assertSame('2026-03-10', $timeExport['rows'][0][0], 'Time entries export should match visible paginated row order.');

        $omdExport = $service->export_definition('omd_rozliczenia', $filters);
        $this->assertSame('Narzut controllingowy', $omdExport['headers'][7], 'OMD export should expose controlling overhead column.');
        $this->assertSame('Wynik controllingowy', $omdExport['headers'][8], 'OMD export should expose controlling result column.');

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
