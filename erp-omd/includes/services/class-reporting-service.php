<?php

if (! class_exists('ERP_OMD_Reporting_Service', false)) {
class ERP_OMD_Reporting_Service
{
    private $projects;
    private $clients;
    private $employees;
    private $salary_history;
    private $project_costs;
    private $time_entries;
    private $project_financial_service;

    public function __construct(
        ERP_OMD_Project_Repository $projects,
        ERP_OMD_Client_Repository $clients,
        ERP_OMD_Employee_Repository $employees,
        ERP_OMD_Salary_History_Repository $salary_history,
        ERP_OMD_Project_Cost_Repository $project_costs,
        ERP_OMD_Time_Entry_Repository $time_entries,
        ERP_OMD_Project_Financial_Service $project_financial_service
    ) {
        $this->projects = $projects;
        $this->clients = $clients;
        $this->employees = $employees;
        $this->salary_history = $salary_history;
        $this->project_costs = $project_costs;
        $this->time_entries = $time_entries;
        $this->project_financial_service = $project_financial_service;
    }

    public function sanitize_filters(array $raw_filters = [])
    {
        $month = isset($raw_filters['month']) ? sanitize_text_field((string) $raw_filters['month']) : gmdate('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = gmdate('Y-m');
        }

        $report_type = isset($raw_filters['report_type']) ? sanitize_key((string) $raw_filters['report_type']) : 'projects';
        if (! in_array($report_type, ['projects', 'clients', 'invoice', 'monthly', 'omd_rozliczenia'], true)) {
            $report_type = 'projects';
        }

        $tab = isset($raw_filters['tab']) ? sanitize_key((string) $raw_filters['tab']) : 'reports';
        if (! in_array($tab, ['reports', 'calendar'], true)) {
            $tab = 'reports';
        }

        $status = sanitize_text_field((string) ($raw_filters['status'] ?? ''));
        if (! in_array($status, $this->allowedStatuses(), true)) {
            $status = '';
        }

        return [
            'client_id' => (int) ($raw_filters['client_id'] ?? 0),
            'project_id' => (int) ($raw_filters['project_id'] ?? 0),
            'employee_id' => (int) ($raw_filters['employee_id'] ?? 0),
            'status' => $status,
            'month' => $month,
            'report_type' => $report_type,
            'tab' => $tab,
        ];
    }

    public function build_report($report_type, array $filters)
    {
        $filters = $this->sanitize_filters($filters);

        switch ($report_type) {
            case 'clients':
                return $this->build_client_report($filters);
            case 'invoice':
                return $this->build_invoice_report($filters);
            case 'monthly':
                return $this->build_monthly_report($filters);
            case 'omd_rozliczenia':
                $rows = [];
                foreach ($this->build_month_sequence((string) $filters['month'], 12) as $month) {
                    $month_filters = $filters;
                    $month_filters['month'] = $month;
                    $projects = $this->get_filtered_projects($month_filters);
                    $project_ids = array_map('intval', wp_list_pluck($projects, 'id'));
                    $entries = $this->get_filtered_entries($project_ids, $month_filters);
                    $salary_cost = $this->get_salary_cost_for_month($month);
                    $direct_cost = 0.0;
                    $active_budgets = 0.0;
                    $time_revenue = 0.0;
                    $time_cost = 0.0;

                    foreach ($projects as $project) {
                        if ((string) ($project['status'] ?? '') !== 'inactive') {
                            $active_budgets += (float) ($project['budget'] ?? 0);
                        }
                    }

                    foreach ($entries as $entry) {
                        $hours = (float) ($entry['hours'] ?? 0);
                        $time_revenue += $hours * (float) ($entry['rate_snapshot'] ?? 0);
                        $time_cost += $hours * (float) ($entry['cost_snapshot'] ?? 0);
                    }

                    foreach ($this->get_direct_cost_metrics_by_project($project_ids, $month) as $project_cost) {
                        $direct_cost += (float) $project_cost;
                    }

                    $fixed_cost = $this->get_fixed_monthly_cost($month);
                    $operating_result = $time_revenue - ($salary_cost + $fixed_cost + $direct_cost);
                    $hourly_profit = $time_revenue - $time_cost;
                    $rows[] = [
                        'month' => $month,
                        'salary_cost' => round($salary_cost, 2),
                        'project_direct_cost' => round($direct_cost, 2),
                        'active_project_budgets' => round($active_budgets, 2),
                        'hourly_profit' => round($hourly_profit, 2),
                        'fixed_cost' => round($fixed_cost, 2),
                        'operating_result' => round($operating_result, 2),
                        'time_revenue' => round($time_revenue, 2),
                        'time_cost' => round($time_cost, 2),
                    ];
                }
                return $rows;
            case 'projects':
            default:
                return $this->build_project_report($filters);
        }
    }

    public function build_project_report(array $filters)
    {
        $filters = $this->sanitize_filters($filters);
        $projects = $this->get_filtered_projects($filters);
        $project_ids = array_map('intval', wp_list_pluck($projects, 'id'));
        $financials = $this->project_financial_service->get_project_financials($project_ids);
        $entry_index = $this->get_entry_metrics_by_project($project_ids, $filters);
        $cost_index = $this->get_direct_cost_metrics_by_project($project_ids, $filters['month']);
        $rows = [];

        foreach ($projects as $project) {
            $project_id = (int) $project['id'];
            $financial = $financials[$project_id] ?? [];
            $entry_metrics = $entry_index[$project_id] ?? $this->emptyEntryMetrics();
            $direct_cost = (float) ($cost_index[$project_id] ?? 0.0);

            if ($filters['employee_id'] > 0 && (int) $entry_metrics['entries_count'] === 0) {
                continue;
            }

            $rows[] = [
                'project_id' => $project_id,
                'project_name' => (string) ($project['name'] ?? ''),
                'client_name' => (string) ($project['client_name'] ?? ''),
                'status' => (string) ($project['status'] ?? ''),
                'billing_type' => (string) ($project['billing_type'] ?? ''),
                'manager_login' => (string) ($project['manager_login'] ?? '—'),
                'budget' => (float) ($project['budget'] ?? 0),
                'reported_hours' => (float) $entry_metrics['hours'],
                'entries_count' => (int) $entry_metrics['entries_count'],
                'filtered_time_revenue' => (float) $entry_metrics['time_revenue'],
                'filtered_time_cost' => (float) $entry_metrics['time_cost'],
                'filtered_direct_cost' => $direct_cost,
                'revenue' => (float) ($financial['revenue'] ?? 0),
                'cost' => (float) ($financial['cost'] ?? 0),
                'profit' => (float) ($financial['profit'] ?? 0),
                'margin' => (float) ($financial['margin'] ?? 0),
                'budget_usage' => (float) ($financial['budget_usage'] ?? 0),
            ];
        }

        return $rows;
    }

    public function build_client_report(array $filters)
    {
        $filters = $this->sanitize_filters($filters);
        $project_rows = $this->build_project_report($filters);
        $client_rows = [];

        foreach ($project_rows as $project_row) {
            $client_name = (string) ($project_row['client_name'] ?? '—');
            if (! isset($client_rows[$client_name])) {
                $client_rows[$client_name] = [
                    'client_name' => $client_name,
                    'projects_count' => 0,
                    'reported_hours' => 0.0,
                    'filtered_time_revenue' => 0.0,
                    'filtered_time_cost' => 0.0,
                    'filtered_direct_cost' => 0.0,
                    'revenue' => 0.0,
                    'cost' => 0.0,
                    'profit' => 0.0,
                ];
            }

            $client_rows[$client_name]['projects_count']++;
            $client_rows[$client_name]['reported_hours'] += (float) $project_row['reported_hours'];
            $client_rows[$client_name]['filtered_time_revenue'] += (float) $project_row['filtered_time_revenue'];
            $client_rows[$client_name]['filtered_time_cost'] += (float) $project_row['filtered_time_cost'];
            $client_rows[$client_name]['filtered_direct_cost'] += (float) $project_row['filtered_direct_cost'];
            $client_rows[$client_name]['revenue'] += (float) $project_row['revenue'];
            $client_rows[$client_name]['cost'] += (float) $project_row['cost'];
            $client_rows[$client_name]['profit'] += (float) $project_row['profit'];
        }

        foreach ($client_rows as &$row) {
            $row['margin'] = $row['revenue'] > 0 ? round(($row['profit'] / $row['revenue']) * 100, 2) : 0.0;
        }
        unset($row);

        ksort($client_rows);

        return array_values($client_rows);
    }

    public function build_invoice_report(array $filters)
    {
        $filters = $this->sanitize_filters($filters);
        $filters['status'] = 'do_faktury';
        return $this->build_project_report($filters);
    }

    public function build_monthly_report(array $filters)
    {
        $filters = $this->sanitize_filters($filters);
        $projects = $this->get_filtered_projects($filters);
        $project_ids = array_map('intval', wp_list_pluck($projects, 'id'));
        $entries = $this->get_filtered_entries($project_ids, $filters);
        $direct_costs_by_month = $this->get_direct_cost_metrics_by_month($project_ids);
        $rows = [];

        foreach ($entries as $entry) {
            $month = substr((string) ($entry['entry_date'] ?? ''), 0, 7);
            if ($month === '') {
                continue;
            }
            if (! isset($rows[$month])) {
                $rows[$month] = [
                    'month' => $month,
                    'entries_count' => 0,
                    'projects' => [],
                    'clients' => [],
                    'hours' => 0.0,
                    'time_revenue' => 0.0,
                    'time_cost' => 0.0,
                ];
            }

            $rows[$month]['entries_count']++;
            $rows[$month]['projects'][(int) ($entry['project_id'] ?? 0)] = true;
            $rows[$month]['clients'][(int) ($entry['client_id'] ?? 0)] = true;
            $rows[$month]['hours'] += (float) ($entry['hours'] ?? 0);
            $rows[$month]['time_revenue'] += (float) ($entry['hours'] ?? 0) * (float) ($entry['rate_snapshot'] ?? 0);
            $rows[$month]['time_cost'] += (float) ($entry['hours'] ?? 0) * (float) ($entry['cost_snapshot'] ?? 0);
        }

        foreach ($direct_costs_by_month as $month => $amount) {
            if (! isset($rows[$month])) {
                $rows[$month] = [
                    'month' => $month,
                    'entries_count' => 0,
                    'projects' => [],
                    'clients' => [],
                    'hours' => 0.0,
                    'time_revenue' => 0.0,
                    'time_cost' => 0.0,
                ];
            }
        }

        ksort($rows);
        $report_rows = [];

        foreach ($rows as $month => $row) {
            $direct_cost = (float) ($direct_costs_by_month[$month] ?? 0.0);
            $profit = (float) $row['time_revenue'] - (float) $row['time_cost'] - $direct_cost;
            $report_rows[] = [
                'month' => $month,
                'entries_count' => (int) $row['entries_count'],
                'projects_count' => count($row['projects']),
                'clients_count' => count($row['clients']),
                'hours' => round((float) $row['hours'], 2),
                'time_revenue' => round((float) $row['time_revenue'], 2),
                'time_cost' => round((float) $row['time_cost'], 2),
                'direct_cost' => round($direct_cost, 2),
                'profit' => round($profit, 2),
            ];
        }

        return array_reverse($report_rows);
    }

    public function build_calendar(array $filters)
    {
        $filters = $this->sanitize_filters($filters);
        $projects = $this->get_filtered_projects($filters);
        $project_ids = array_map('intval', wp_list_pluck($projects, 'id'));
        $entries = $this->get_filtered_entries($project_ids, $filters);
        $month = $filters['month'];
        $first_day = new DateTimeImmutable($month . '-01');
        $last_day = $first_day->modify('last day of this month');
        $days = [];

        foreach ($entries as $entry) {
            $date = (string) ($entry['entry_date'] ?? '');
            if (! isset($days[$date])) {
                $days[$date] = [
                    'date' => $date,
                    'hours' => 0.0,
                    'entries_count' => 0,
                    'approved_hours' => 0.0,
                    'submitted_hours' => 0.0,
                    'rejected_hours' => 0.0,
                ];
            }

            $hours = (float) ($entry['hours'] ?? 0);
            $status = (string) ($entry['status'] ?? 'submitted');
            $days[$date]['hours'] += $hours;
            $days[$date]['entries_count']++;
            if ($status === 'approved') {
                $days[$date]['approved_hours'] += $hours;
            } elseif ($status === 'rejected') {
                $days[$date]['rejected_hours'] += $hours;
            } else {
                $days[$date]['submitted_hours'] += $hours;
            }
        }

        $weeks = [];
        $week = array_fill(0, 7, null);
        $day_pointer = (int) $first_day->format('N') - 1;
        $current_day = $first_day;

        while ($current_day <= $last_day) {
            $date_key = $current_day->format('Y-m-d');
            $week[$day_pointer] = array_merge(
                [
                    'date' => $date_key,
                    'day' => (int) $current_day->format('j'),
                    'hours' => 0.0,
                    'entries_count' => 0,
                    'approved_hours' => 0.0,
                    'submitted_hours' => 0.0,
                    'rejected_hours' => 0.0,
                ],
                $days[$date_key] ?? []
            );

            $day_pointer++;
            if ($day_pointer === 7) {
                $weeks[] = $week;
                $week = array_fill(0, 7, null);
                $day_pointer = 0;
            }
            $current_day = $current_day->modify('+1 day');
        }

        if (array_filter($week)) {
            $weeks[] = $week;
        }

        return [
            'month' => $month,
            'weeks' => $weeks,
            'totals' => [
                'hours' => round(array_sum(wp_list_pluck($days, 'hours')), 2),
                'entries_count' => array_sum(wp_list_pluck($days, 'entries_count')),
                'approved_hours' => round(array_sum(wp_list_pluck($days, 'approved_hours')), 2),
                'submitted_hours' => round(array_sum(wp_list_pluck($days, 'submitted_hours')), 2),
                'rejected_hours' => round(array_sum(wp_list_pluck($days, 'rejected_hours')), 2),
            ],
        ];
    }

    public function export_definition($report_type, array $filters)
    {
        $filters = $this->sanitize_filters($filters);
        $rows = $this->build_report($report_type, $filters);
        $month = $filters['month'];
        switch ($report_type) {
            case 'clients':
                return [
                    'filename' => sprintf('erp-omd-raport-klienci-%s.csv', $month),
                    'headers' => ['Klient', 'Liczba projektów', 'Godziny', 'Przychód czasu', 'Koszt czasu', 'Koszt bezpośredni', 'Przychód łącznie', 'Koszt łącznie', 'Zysk', 'Marża %'],
                    'rows' => array_map(static function ($row) {
                        return [
                            $row['client_name'],
                            $row['projects_count'],
                            number_format((float) $row['reported_hours'], 2, '.', ''),
                            number_format((float) $row['filtered_time_revenue'], 2, '.', ''),
                            number_format((float) $row['filtered_time_cost'], 2, '.', ''),
                            number_format((float) $row['filtered_direct_cost'], 2, '.', ''),
                            number_format((float) $row['revenue'], 2, '.', ''),
                            number_format((float) $row['cost'], 2, '.', ''),
                            number_format((float) $row['profit'], 2, '.', ''),
                            number_format((float) $row['margin'], 2, '.', ''),
                        ];
                    }, $rows),
                ];
            case 'invoice':
            case 'projects':
                return [
                    'filename' => sprintf('erp-omd-raport-%s-%s.csv', $report_type, $month),
                    'headers' => ['Klient', 'Projekt', 'Typ rozliczenia', 'Manager', 'Budżet', 'Godziny', 'Wpisy', 'Przychód czasu (filtrowany)', 'Koszt czasu (filtrowany)', 'Koszt bezpośredni (filtrowany)', 'Przychód łącznie', 'Koszt łącznie', 'Zysk', 'Marża %', 'Wykorzystanie budżetu %', 'Status'],
                    'rows' => array_map(function ($row) {
                        return [
                            $row['client_name'],
                            $row['project_name'],
                            $this->billing_type_label((string) ($row['billing_type'] ?? '')),
                            $row['manager_login'],
                            number_format((float) $row['budget'], 2, '.', ''),
                            number_format((float) $row['reported_hours'], 2, '.', ''),
                            $row['entries_count'],
                            number_format((float) $row['filtered_time_revenue'], 2, '.', ''),
                            number_format((float) $row['filtered_time_cost'], 2, '.', ''),
                            number_format((float) $row['filtered_direct_cost'], 2, '.', ''),
                            number_format((float) $row['revenue'], 2, '.', ''),
                            number_format((float) $row['cost'], 2, '.', ''),
                            number_format((float) $row['profit'], 2, '.', ''),
                            number_format((float) $row['margin'], 2, '.', ''),
                            number_format((float) $row['budget_usage'], 2, '.', ''),
                            $row['status'],
                        ];
                    }, $rows),
                ];
            case 'monthly':
                return [
                    'filename' => sprintf('erp-omd-raport-miesieczny-%s.csv', $month),
                    'headers' => ['Miesiąc', 'Wpisy', 'Projekty', 'Klienci', 'Godziny', 'Przychód czasu', 'Koszt czasu', 'Koszt bezpośredni', 'Wynik'],
                    'rows' => array_map(static function ($row) {
                        return [
                            $row['month'],
                            $row['entries_count'],
                            $row['projects_count'],
                            $row['clients_count'],
                            number_format((float) $row['hours'], 2, '.', ''),
                            number_format((float) $row['time_revenue'], 2, '.', ''),
                            number_format((float) $row['time_cost'], 2, '.', ''),
                            number_format((float) $row['direct_cost'], 2, '.', ''),
                            number_format((float) $row['profit'], 2, '.', ''),
                        ];
                    }, $rows),
                ];
            case 'omd_rozliczenia':
                return [
                    'filename' => sprintf('erp-omd-rozliczenie-omd-%s.csv', $month),
                    'headers' => ['Miesiąc', 'Koszt pensji', 'Koszt projektów', 'Budżety aktywnych projektów', 'Zysk godzinowy', 'Koszty stałe', 'Wynik operacyjny', 'Przychód czasu', 'Koszt czasu'],
                    'rows' => array_map(static function ($row) {
                        return [
                            $row['month'],
                            number_format((float) $row['salary_cost'], 2, '.', ''),
                            number_format((float) $row['project_direct_cost'], 2, '.', ''),
                            number_format((float) $row['active_project_budgets'], 2, '.', ''),
                            number_format((float) $row['hourly_profit'], 2, '.', ''),
                            number_format((float) $row['fixed_cost'], 2, '.', ''),
                            number_format((float) $row['operating_result'], 2, '.', ''),
                            number_format((float) $row['time_revenue'], 2, '.', ''),
                            number_format((float) $row['time_cost'], 2, '.', ''),
                        ];
                    }, $rows),
                ];
            default:
                return ['filename' => 'erp-omd-report.csv', 'headers' => [], 'rows' => []];
        }
    }

    private function get_salary_cost_for_month($month)
    {
        $month_date = DateTimeImmutable::createFromFormat('Y-m-d', $month . '-01');
        if (! $month_date) {
            return 0.0;
        }

        $salary_cost = 0.0;
        $month_start = $month_date->format('Y-m-01');
        $month_end = $month_date->format('Y-m-t');
        foreach ($this->employees->all() as $employee) {
            $employee_id = (int) ($employee['id'] ?? 0);
            if ($employee_id <= 0) {
                continue;
            }

            foreach ($this->salary_history->for_employee($employee_id) as $salary_row) {
                $valid_from = (string) ($salary_row['valid_from'] ?? '');
                $valid_to = (string) ($salary_row['valid_to'] ?? '');
                $effective_to = $valid_to !== '' ? $valid_to : '9999-12-31';
                if ($valid_from === '') {
                    continue;
                }

                if ($valid_from <= $month_end && $effective_to >= $month_start) {
                    $salary_cost += (float) ($salary_row['monthly_salary'] ?? 0.0);
                    break;
                }
            }
        }

        return $salary_cost;
    }

    private function get_fixed_monthly_cost($month)
    {
        $month_date = DateTimeImmutable::createFromFormat('Y-m-d', $month . '-01');
        if (! $month_date) {
            return max(0.0, (float) get_option('erp_omd_fixed_monthly_cost', 0));
        }

        $month_start = $month_date->format('Y-m-01');
        $month_end = $month_date->format('Y-m-t');
        $items = (array) get_option('erp_omd_fixed_monthly_cost_items', []);
        $sum = 0.0;

        foreach ($items as $item) {
            if (! is_array($item) || empty($item['active'])) {
                continue;
            }

            $amount = max(0.0, (float) ($item['amount'] ?? 0));
            if ($amount <= 0) {
                continue;
            }

            $valid_from = (string) ($item['valid_from'] ?? '');
            $valid_to = (string) ($item['valid_to'] ?? '');
            $effective_from = $valid_from !== '' ? $valid_from : '0001-01-01';
            $effective_to = $valid_to !== '' ? $valid_to : '9999-12-31';
            if ($effective_from <= $month_end && $effective_to >= $month_start) {
                $sum += $amount;
            }
        }

        if ($sum > 0) {
            return round($sum, 2);
        }

        return max(0.0, (float) get_option('erp_omd_fixed_monthly_cost', 0));
    }

    private function build_month_sequence($anchor_month, $count)
    {
        $count = max(1, (int) $count);
        $anchor = DateTimeImmutable::createFromFormat('Y-m-d', $anchor_month . '-01');
        if (! $anchor) {
            $anchor = new DateTimeImmutable(gmdate('Y-m-01'));
        }

        $months = [];
        for ($offset = $count - 1; $offset >= 0; $offset--) {
            $months[] = $anchor->modify('-' . $offset . ' month')->format('Y-m');
        }

        return $months;
    }

    private function get_filtered_projects(array $filters)
    {
        $projects = $this->projects->all();

        return array_values(array_filter($projects, function ($project) use ($filters) {
            if ($filters['project_id'] > 0 && (int) ($project['id'] ?? 0) !== $filters['project_id']) {
                return false;
            }
            if ($filters['client_id'] > 0 && (int) ($project['client_id'] ?? 0) !== $filters['client_id']) {
                return false;
            }
            if (
                $filters['status'] !== ''
                && $this->isProjectStatusFilter($filters['status'])
                && (string) ($project['status'] ?? '') !== $filters['status']
            ) {
                return false;
            }
            return true;
        }));
    }

    private function get_filtered_entries(array $project_ids, array $filters)
    {
        $entries = $this->time_entries->all([]);

        return array_values(array_filter($entries, function ($entry) use ($project_ids, $filters) {
            if ($project_ids !== [] && ! in_array((int) ($entry['project_id'] ?? 0), $project_ids, true)) {
                return false;
            }
            if ($filters['employee_id'] > 0 && (int) ($entry['employee_id'] ?? 0) !== $filters['employee_id']) {
                return false;
            }
            if ($filters['project_id'] > 0 && (int) ($entry['project_id'] ?? 0) !== $filters['project_id']) {
                return false;
            }
            if (
                $filters['status'] !== ''
                && $this->isTimeEntryStatusFilter($filters['status'])
                && (string) ($entry['status'] ?? '') !== $filters['status']
            ) {
                return false;
            }
            if ($filters['month'] !== '' && strpos((string) ($entry['entry_date'] ?? ''), $filters['month']) !== 0) {
                return false;
            }
            return true;
        }));
    }

    private function get_entry_metrics_by_project(array $project_ids, array $filters)
    {
        $entries = $this->get_filtered_entries($project_ids, $filters);
        $metrics = [];

        foreach ($entries as $entry) {
            $project_id = (int) ($entry['project_id'] ?? 0);
            if (! isset($metrics[$project_id])) {
                $metrics[$project_id] = $this->emptyEntryMetrics();
            }
            $hours = (float) ($entry['hours'] ?? 0);
            $metrics[$project_id]['hours'] += $hours;
            $metrics[$project_id]['entries_count']++;
            $metrics[$project_id]['time_revenue'] += $hours * (float) ($entry['rate_snapshot'] ?? 0);
            $metrics[$project_id]['time_cost'] += $hours * (float) ($entry['cost_snapshot'] ?? 0);
        }

        return $metrics;
    }

    private function get_direct_cost_metrics_by_project(array $project_ids, $month)
    {
        $metrics = [];

        foreach ($project_ids as $project_id) {
            foreach ($this->project_costs->for_project((int) $project_id) as $cost_row) {
                $cost_month = substr((string) ($cost_row['cost_date'] ?? ''), 0, 7);
                if ($month !== '' && $cost_month !== $month) {
                    continue;
                }
                if (! isset($metrics[(int) $project_id])) {
                    $metrics[(int) $project_id] = 0.0;
                }
                $metrics[(int) $project_id] += (float) ($cost_row['amount'] ?? 0);
            }
        }

        return $metrics;
    }

    private function get_direct_cost_metrics_by_month(array $project_ids)
    {
        $metrics = [];

        foreach ($project_ids as $project_id) {
            foreach ($this->project_costs->for_project((int) $project_id) as $cost_row) {
                $month = substr((string) ($cost_row['cost_date'] ?? ''), 0, 7);
                if ($month === '') {
                    continue;
                }
                if (! isset($metrics[$month])) {
                    $metrics[$month] = 0.0;
                }
                $metrics[$month] += (float) ($cost_row['amount'] ?? 0);
            }
        }

        return $metrics;
    }

    private function emptyEntryMetrics()
    {
        return [
            'hours' => 0.0,
            'entries_count' => 0,
            'time_revenue' => 0.0,
            'time_cost' => 0.0,
        ];
    }

    private function allowedStatuses()
    {
        return [
            '',
            'do_rozpoczecia',
            'w_realizacji',
            'w_akceptacji',
            'do_faktury',
            'zakonczony',
            'inactive',
            'submitted',
            'approved',
            'rejected',
        ];
    }

    private function isProjectStatusFilter($status)
    {
        return in_array($status, ['do_rozpoczecia', 'w_realizacji', 'w_akceptacji', 'do_faktury', 'zakonczony', 'inactive'], true);
    }

    private function isTimeEntryStatusFilter($status)
    {
        return in_array($status, ['submitted', 'approved', 'rejected'], true);
    }

    private function billing_type_label($billing_type)
    {
        switch ((string) $billing_type) {
            case 'fixed_price':
                return __('Ryczałt', 'erp-omd');
            case 'retainer':
                return __('Abonament', 'erp-omd');
            case 'time_material':
            default:
                return __('Godzinowy', 'erp-omd');
        }
    }
}
}
