<?php

class ERP_OMD_Alert_Service
{
    private $employees;
    private $clients;
    private $client_rates;
    private $projects;
    private $project_rates;
    private $project_financial_service;
    private $time_entries;

    public function __construct(
        ERP_OMD_Employee_Repository $employees,
        ERP_OMD_Client_Repository $clients,
        ERP_OMD_Client_Rate_Repository $client_rates,
        ERP_OMD_Project_Repository $projects,
        ERP_OMD_Project_Rate_Repository $project_rates,
        ERP_OMD_Project_Financial_Service $project_financial_service,
        ERP_OMD_Time_Entry_Repository $time_entries
    ) {
        $this->employees = $employees;
        $this->clients = $clients;
        $this->client_rates = $client_rates;
        $this->projects = $projects;
        $this->project_rates = $project_rates;
        $this->project_financial_service = $project_financial_service;
        $this->time_entries = $time_entries;
    }

    public function margin_threshold()
    {
        return (float) get_option('erp_omd_alert_margin_threshold', 10);
    }

    public function all_alerts()
    {
        $alerts = array_merge(
            $this->project_alerts(),
            $this->missing_time_entry_alerts()
        );

        usort($alerts, static function ($left, $right) {
            $weights = ['error' => 3, 'warning' => 2, 'info' => 1];
            $left_weight = $weights[(string) ($left['severity'] ?? '')] ?? 0;
            $right_weight = $weights[(string) ($right['severity'] ?? '')] ?? 0;

            if ($left_weight === $right_weight) {
                return strcmp((string) ($left['code'] ?? ''), (string) ($right['code'] ?? ''));
            }

            return $right_weight <=> $left_weight;
        });

        return $alerts;
    }

    public function project_alerts()
    {
        $projects = $this->projects->all();
        $financials = $this->project_financial_service->get_project_financials(wp_list_pluck($projects, 'id'));
        $alerts = [];

        foreach ($projects as $project) {
            $project_id = (int) ($project['id'] ?? 0);
            $status = (string) ($project['status'] ?? '');
            if ($project_id <= 0 || $status === 'inactive') {
                continue;
            }

            $financial = $financials[$project_id] ?? [];
            $budget_usage = (float) ($financial['budget_usage'] ?? 0);
            $margin = (float) ($financial['margin'] ?? 0);
            $margin_threshold = $this->resolve_margin_threshold($project);

            if ($budget_usage > 100) {
                $alerts[] = $this->make_alert('error', 'project_budget_exceeded', 'project', $project_id, sprintf(__('Projekt %s przekroczył budżet (%s%%).', 'erp-omd'), (string) ($project['name'] ?? '#' . $project_id), number_format_i18n($budget_usage, 2)));
            }

            if ((float) ($financial['revenue'] ?? 0) > 0 && $margin < $margin_threshold) {
                $alerts[] = $this->make_alert('warning', 'project_low_margin', 'project', $project_id, sprintf(__('Projekt %s ma niską marżę (%s%%, próg %s%%).', 'erp-omd'), (string) ($project['name'] ?? '#' . $project_id), number_format_i18n($margin, 2), number_format_i18n($margin_threshold, 2)));
            }

            $project_rates = $this->project_rates->for_project($project_id);
            $client_rates = $this->client_rates->for_client((int) ($project['client_id'] ?? 0));
            if (empty($project_rates) && empty($client_rates)) {
                $alerts[] = $this->make_alert('warning', 'project_missing_rates', 'project', $project_id, sprintf(__('Projekt %s nie ma skonfigurowanych stawek projektowych ani stawek klienta.', 'erp-omd'), (string) ($project['name'] ?? '#' . $project_id)));
            }
        }

        return $alerts;
    }

    public function missing_time_entry_alerts()
    {
        $employees = $this->employees->all();
        $time_entries = $this->time_entries->all();
        $last_entries = [];
        $alerts = [];
        $threshold_date = new DateTimeImmutable(current_time('Y-m-d'));
        $threshold_date = $threshold_date->modify('-3 days')->format('Y-m-d');

        foreach ($time_entries as $time_entry) {
            $employee_id = (int) ($time_entry['employee_id'] ?? 0);
            $entry_date = (string) ($time_entry['entry_date'] ?? '');
            if ($employee_id <= 0 || $entry_date === '') {
                continue;
            }

            if (! isset($last_entries[$employee_id]) || $entry_date > $last_entries[$employee_id]) {
                $last_entries[$employee_id] = $entry_date;
            }
        }

        foreach ($employees as $employee) {
            $employee_id = (int) ($employee['id'] ?? 0);
            if ($employee_id <= 0 || (string) ($employee['status'] ?? '') === 'inactive') {
                continue;
            }

            $last_entry = $last_entries[$employee_id] ?? '';
            if ($last_entry === '' || $last_entry <= $threshold_date) {
                $alerts[] = $this->make_alert(
                    'info',
                    'employee_missing_time_entry',
                    'employee',
                    $employee_id,
                    sprintf(
                        __('Pracownik %1$s nie dodał wpisu czasu od co najmniej 3 dni. Ostatni wpis: %2$s.', 'erp-omd'),
                        (string) ($employee['user_login'] ?? '#' . $employee_id),
                        $last_entry !== '' ? $last_entry : __('brak wpisów', 'erp-omd')
                    )
                );
            }
        }

        return $alerts;
    }

    public function alerts_for_entity($entity_type, $entity_id)
    {
        return array_values(array_filter($this->all_alerts(), static function ($alert) use ($entity_type, $entity_id) {
            return (string) ($alert['entity_type'] ?? '') === (string) $entity_type
                && (int) ($alert['entity_id'] ?? 0) === (int) $entity_id;
        }));
    }

    private function resolve_margin_threshold(array $project)
    {
        $project_override = isset($project['alert_margin_threshold']) && $project['alert_margin_threshold'] !== null
            ? (float) $project['alert_margin_threshold']
            : null;
        if ($project_override !== null && $project_override >= 0) {
            return $project_override;
        }

        $client = $this->clients->find((int) ($project['client_id'] ?? 0));
        $client_override = isset($client['alert_margin_threshold']) && $client['alert_margin_threshold'] !== null
            ? (float) $client['alert_margin_threshold']
            : null;
        if ($client_override !== null && $client_override >= 0) {
            return $client_override;
        }

        return $this->margin_threshold();
    }

    private function make_alert($severity, $code, $entity_type, $entity_id, $message)
    {
        return [
            'severity' => $severity,
            'code' => $code,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'message' => $message,
        ];
    }
}
