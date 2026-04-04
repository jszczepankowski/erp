<?php

declare(strict_types=1);

if (! function_exists('sanitize_text_field')) {
    function sanitize_text_field($value) { return trim((string) $value); }
}
if (! function_exists('sanitize_key')) {
    function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)); }
}
if (! function_exists('__')) {
    function __($text, $domain = null) { return $text; }
}
if (! function_exists('get_option')) {
    function get_option($name, $default = false) { return $default; }
}
if (! function_exists('wp_list_pluck')) {
    function wp_list_pluck(array $list, $field)
    {
        $out = [];
        foreach ($list as $row) {
            if (is_array($row) && array_key_exists($field, $row)) {
                $out[] = $row[$field];
            }
        }
        return $out;
    }
}

if (! class_exists('ERP_OMD_Project_Repository')) {
    class ERP_OMD_Project_Repository { public function __construct(private array $rows) {} public function all() { return $this->rows; } }
}
if (! class_exists('ERP_OMD_Client_Repository')) {
    class ERP_OMD_Client_Repository { public function __construct(private array $rows) {} public function all() { return $this->rows; } }
}
if (! class_exists('ERP_OMD_Employee_Repository')) {
    class ERP_OMD_Employee_Repository { public function __construct(private array $rows) {} public function all() { return $this->rows; } }
}
if (! class_exists('ERP_OMD_Salary_History_Repository')) {
    class ERP_OMD_Salary_History_Repository {
        public int $forEmployeeCalls = 0;
        public int $forEmployeesCalls = 0;
        public function __construct(private array $rows) {}
        public function for_employee($employeeId) { $this->forEmployeeCalls++; return $this->rows[(int) $employeeId] ?? []; }
        public function for_employees(array $employeeIds)
        {
            $this->forEmployeesCalls++;
            $result = [];
            foreach ($employeeIds as $employeeId) {
                foreach (($this->rows[(int) $employeeId] ?? []) as $row) {
                    $row['employee_id'] = (int) $employeeId;
                    $result[] = $row;
                }
            }
            return $result;
        }
    }
}
if (! class_exists('ERP_OMD_Project_Cost_Repository')) {
    class ERP_OMD_Project_Cost_Repository {
        public int $forProjectCalls = 0;
        public int $sumByProjectAndMonthInDateRangeCalls = 0;
        public function __construct(private array $rows) {}
        public function for_project($projectId) { $this->forProjectCalls++; return $this->rows[(int) $projectId] ?? []; }
        public function sum_by_project_and_month_in_date_range(array $projectIds, $dateFrom, $dateTo)
        {
            $this->sumByProjectAndMonthInDateRangeCalls++;
            $bucket = [];
            foreach ($projectIds as $projectId) {
                foreach (($this->rows[(int) $projectId] ?? []) as $row) {
                    $costDate = (string) ($row['cost_date'] ?? '');
                    if ($costDate < (string) $dateFrom || $costDate > (string) $dateTo) {
                        continue;
                    }
                    $month = substr($costDate, 0, 7);
                    if ($month === '') {
                        continue;
                    }
                    $key = $month . ':' . (int) $projectId;
                    if (! isset($bucket[$key])) {
                        $bucket[$key] = ['project_id' => (int) $projectId, 'cost_month' => $month, 'amount_sum' => 0.0];
                    }
                    $bucket[$key]['amount_sum'] += (float) ($row['amount'] ?? 0.0);
                }
            }
            return array_values($bucket);
        }
    }
}
if (! class_exists('ERP_OMD_Time_Entry_Repository')) {
    class ERP_OMD_Time_Entry_Repository {
        public int $allCalls = 0;
        public function __construct(private array $rows) {}
        public function all(array $filters = []) { $this->allCalls++; return $this->rows; }
    }
}
if (! class_exists('ERP_OMD_Project_Financial_Service')) {
    class ERP_OMD_Project_Financial_Service {
        public function __construct(private array $rows) {}
        public function get_project_financials(array $projectIds)
        {
            $out = [];
            foreach ($projectIds as $id) {
                if (isset($this->rows[(int) $id])) {
                    $out[(int) $id] = $this->rows[(int) $id];
                }
            }
            return $out;
        }
    }
}

require_once __DIR__ . '/../erp-omd/includes/services/class-reporting-service-v2.php';

$projects = [];
$clients = [];
$employees = [];
$salary = [];
$projectCosts = [];
$timeEntries = [];
$financials = [];

for ($c = 1; $c <= 10; $c++) { $clients[] = ['id' => $c, 'name' => 'Client ' . $c]; }
for ($e = 1; $e <= 25; $e++) {
    $employees[] = ['id' => $e, 'user_login' => 'u' . $e];
    $salary[$e] = [['monthly_salary' => 9000 + ($e % 5) * 500, 'valid_from' => '2025-01-01', 'valid_to' => null]];
}
for ($p = 1; $p <= 80; $p++) {
    $month = sprintf('2026-%02d', (($p - 1) % 12) + 1);
    $projects[] = [
        'id' => $p,
        'client_id' => (($p - 1) % 10) + 1,
        'name' => 'P' . $p,
        'client_name' => 'Client ' . ((($p - 1) % 10) + 1),
        'status' => 'do_faktury',
        'billing_type' => 'time_material',
        'manager_login' => 'm' . $p,
        'budget' => 15000 + $p,
        'operational_close_month' => $month,
        'start_date' => $month . '-01',
        'end_date' => $month . '-28',
    ];
    $financials[$p] = ['revenue' => 10000.0, 'cost' => 7000.0, 'profit' => 3000.0, 'margin' => 30.0, 'budget_usage' => 40.0];
    $projectCosts[$p] = [];
    for ($i = 1; $i <= 4; $i++) {
        $projectCosts[$p][] = ['amount' => 100 + $i, 'cost_date' => $month . '-0' . (($i % 9) + 1)];
    }
}
for ($i = 1; $i <= 12000; $i++) {
    $m = sprintf('2026-%02d', (($i - 1) % 12) + 1);
    $timeEntries[] = [
        'project_id' => (($i - 1) % 80) + 1,
        'client_id' => (($i - 1) % 10) + 1,
        'employee_id' => (($i - 1) % 25) + 1,
        'hours' => 1.5,
        'entry_date' => $m . '-15',
        'status' => 'approved',
        'rate_snapshot' => 150,
        'cost_snapshot' => 60,
    ];
}

$salaryRepo = new ERP_OMD_Salary_History_Repository($salary);
$projectCostRepo = new ERP_OMD_Project_Cost_Repository($projectCosts);
$timeRepo = new ERP_OMD_Time_Entry_Repository($timeEntries);

$service = new ERP_OMD_Reporting_Service(
    new ERP_OMD_Project_Repository($projects),
    new ERP_OMD_Client_Repository($clients),
    new ERP_OMD_Employee_Repository($employees),
    $salaryRepo,
    $projectCostRepo,
    $timeRepo,
    new ERP_OMD_Project_Financial_Service($financials)
);

$filters = $service->sanitize_filters(['report_type' => 'omd_rozliczenia', 'month' => '2026-12']);
$start = microtime(true);
$rows = $service->build_omd_settlement_report($filters);
$elapsedMs = (microtime(true) - $start) * 1000;

$result = [
    'rows' => count($rows),
    'elapsed_ms' => round($elapsedMs, 2),
    'salary_for_employee_calls' => $salaryRepo->forEmployeeCalls,
    'salary_for_employees_calls' => $salaryRepo->forEmployeesCalls,
    'project_cost_for_project_calls' => $projectCostRepo->forProjectCalls,
    'project_cost_sum_by_project_and_month_calls' => $projectCostRepo->sumByProjectAndMonthInDateRangeCalls,
    'time_entries_all_calls' => $timeRepo->allCalls,
];

echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
