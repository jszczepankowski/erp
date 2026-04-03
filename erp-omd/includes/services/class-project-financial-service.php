<?php

class ERP_OMD_Project_Financial_Service
{
    private $projects;
    private $project_costs;
    private $project_revenues;
    private $project_financials;
    private $time_entries;

    public function __construct(
        ERP_OMD_Project_Repository $projects,
        ERP_OMD_Project_Cost_Repository $project_costs,
        ERP_OMD_Project_Revenue_Repository $project_revenues,
        ERP_OMD_Project_Financial_Repository $project_financials,
        ERP_OMD_Time_Entry_Repository $time_entries
    ) {
        $this->projects = $projects;
        $this->project_costs = $project_costs;
        $this->project_revenues = $project_revenues;
        $this->project_financials = $project_financials;
        $this->time_entries = $time_entries;
    }

    public function validate_project_cost(array $data)
    {
        $errors = [];

        if (! $this->projects->find((int) $data['project_id'])) {
            $errors[] = __('Projekt kosztu nie istnieje.', 'erp-omd');
        }

        if ((float) $data['amount'] < 0) {
            $errors[] = __('Kwota kosztu projektu nie może być ujemna.', 'erp-omd');
        }

        $cost_date = DateTimeImmutable::createFromFormat('Y-m-d', (string) $data['cost_date']);
        if (! $cost_date || $cost_date->format('Y-m-d') !== $data['cost_date']) {
            $errors[] = __('Data kosztu projektu jest niepoprawna.', 'erp-omd');
        }

        return $errors;
    }

    public function validate_project_revenue(array $data)
    {
        $errors = [];

        if (! $this->projects->find((int) $data['project_id'])) {
            $errors[] = __('Projekt pozycji przychodowej nie istnieje.', 'erp-omd');
        }

        if ((float) $data['amount'] < 0) {
            $errors[] = __('Kwota pozycji przychodowej nie może być ujemna.', 'erp-omd');
        }

        $revenue_date = DateTimeImmutable::createFromFormat('Y-m-d', (string) $data['revenue_date']);
        if (! $revenue_date || $revenue_date->format('Y-m-d') !== $data['revenue_date']) {
            $errors[] = __('Data pozycji przychodowej jest niepoprawna.', 'erp-omd');
        }

        return $errors;
    }

    public function get_project_financial($project_id, $force_rebuild = false)
    {
        $project_id = (int) $project_id;
        if ($project_id <= 0) {
            return null;
        }

        if (! $force_rebuild) {
            $cached_financial = $this->project_financials->find_by_project($project_id);
            if ($cached_financial) {
                return $cached_financial;
            }
        }

        return $this->rebuild_for_project($project_id);
    }

    public function get_project_financials(array $project_ids, $force_rebuild_missing = true)
    {
        $project_ids = array_values(array_unique(array_filter(array_map('intval', $project_ids))));
        if ($project_ids === []) {
            return [];
        }

        $financials = $this->project_financials->find_by_projects($project_ids);

        if (! $force_rebuild_missing) {
            return $financials;
        }

        foreach ($project_ids as $project_id) {
            if (isset($financials[$project_id])) {
                continue;
            }

            $rebuilt_financial = $this->rebuild_for_project($project_id);
            if ($rebuilt_financial) {
                $financials[$project_id] = $rebuilt_financial;
            }
        }

        return $financials;
    }

    public function rebuild_for_project($project_id)
    {
        $project = $this->projects->find((int) $project_id);
        if (! $project) {
            return null;
        }

        $entries = $this->time_entries->all(['project_id' => (int) $project_id]);
        $entries = array_values(
            array_filter(
                $entries,
                static function ($entry) {
                    return ($entry['status'] ?? '') === 'approved';
                }
            )
        );
        $project_costs = $this->project_costs->for_project((int) $project_id);
        $project_revenues = $this->project_revenues->for_project((int) $project_id);

        $time_revenue = 0.0;
        $time_cost = 0.0;

        foreach ($entries as $entry) {
            $hours = (float) ($entry['hours'] ?? 0);
            $time_revenue += $hours * (float) ($entry['rate_snapshot'] ?? 0);
            $time_cost += $hours * (float) ($entry['cost_snapshot'] ?? 0);
        }

        $direct_cost = 0.0;
        foreach ($project_costs as $project_cost) {
            $direct_cost += (float) ($project_cost['amount'] ?? 0);
        }

        $extra_revenue = 0.0;
        foreach ($project_revenues as $project_revenue_row) {
            $extra_revenue += (float) ($project_revenue_row['amount'] ?? 0);
        }

        $revenue = $this->resolve_revenue($project, $time_revenue, $extra_revenue);
        $cost = round($time_cost + $direct_cost, 2);
        $profit = round($revenue - $cost, 2);
        $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0.0;
        $budget = (float) ($project['budget'] ?? 0);
        $budget_usage = $budget > 0 ? round(($cost / $budget) * 100, 2) : 0.0;

        $data = [
            'revenue' => round($revenue, 2),
            'cost' => $cost,
            'profit' => $profit,
            'margin' => $margin,
            'budget_usage' => $budget_usage,
            'time_revenue' => round($time_revenue, 2),
            'time_cost' => round($time_cost, 2),
            'direct_cost' => round($direct_cost, 2),
            'last_recalculated_at' => current_time('mysql'),
        ];

        $this->project_financials->upsert((int) $project_id, $data);

        return $this->project_financials->find_by_project((int) $project_id);
    }

    private function resolve_revenue(array $project, $time_revenue, $extra_revenue = 0.0)
    {
        switch ($project['billing_type']) {
            case 'fixed_price':
                return (float) ($project['budget'] ?? 0);

            case 'retainer':
                return $this->retainer_revenue($project);

            case 'mixed':
                return round((float) ($project['budget'] ?? 0) + (float) $time_revenue + (float) $extra_revenue, 2);

            case 'time_material':
            default:
                return round((float) $time_revenue + (float) $extra_revenue, 2);
        }
    }

    private function retainer_revenue(array $project)
    {
        $monthly_fee = (float) ($project['retainer_monthly_fee'] ?? 0);
        if ($monthly_fee <= 0) {
            return 0.0;
        }

        $start_date = ! empty($project['start_date']) ? new DateTimeImmutable($project['start_date']) : new DateTimeImmutable('today');
        $end_anchor = ! empty($project['end_date']) ? new DateTimeImmutable($project['end_date']) : new DateTimeImmutable('today');

        if ($end_anchor < $start_date) {
            return 0.0;
        }

        $month_count = ((int) $end_anchor->format('Y') - (int) $start_date->format('Y')) * 12;
        $month_count += (int) $end_anchor->format('n') - (int) $start_date->format('n') + 1;

        return round($month_count * $monthly_fee, 2);
    }
}
