<?php

class ERP_OMD_Client_Portal_Service
{
    /** @var mixed */
    private $projects;

    /** @var mixed */
    private $project_revenues;

    /** @var mixed */
    private $project_costs;

    public function __construct($projects, $project_revenues, $project_costs)
    {
        $this->projects = $projects;
        $this->project_revenues = $project_revenues;
        $this->project_costs = $project_costs;
    }

    /**
     * @param int $project_id
     * @return array<string,mixed>|null
     */
    public function build_project_finance_view($project_id)
    {
        $project = $this->projects->find((int) $project_id);
        if (! is_array($project) || $project === []) {
            return null;
        }

        $planned_budget = (float) ($project['budget'] ?? 0);
        $revenue_rows = (array) $this->project_revenues->for_project((int) $project_id);
        $cost_rows = (array) $this->project_costs->for_project((int) $project_id);

        $budget_increases = array_map(static function ($row) {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'label' => (string) ($row['description'] ?? ''),
                'amount' => (float) ($row['amount'] ?? 0),
                'date' => (string) ($row['revenue_date'] ?? ''),
            ];
        }, $revenue_rows);

        $visible_cost_items = array_map(static function ($row) {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'label' => (string) ($row['description'] ?? ''),
                'amount' => (float) ($row['amount'] ?? 0),
                'date' => (string) ($row['cost_date'] ?? ''),
                'type' => 'external',
            ];
        }, $cost_rows);

        $increase_total = array_reduce($budget_increases, static function ($carry, $row) {
            return $carry + (float) ($row['amount'] ?? 0);
        }, 0.0);

        $cost_total = array_reduce($visible_cost_items, static function ($carry, $row) {
            return $carry + (float) ($row['amount'] ?? 0);
        }, 0.0);

        $history = [
            [
                'type' => 'base_budget',
                'amount' => $planned_budget,
                'date' => (string) ($project['created_at'] ?? ''),
                'label' => __('Budżet bazowy projektu', 'erp-omd'),
            ],
        ];

        foreach ($budget_increases as $increase) {
            $history[] = [
                'type' => 'increase',
                'amount' => (float) ($increase['amount'] ?? 0),
                'date' => (string) ($increase['date'] ?? ''),
                'label' => (string) ($increase['label'] ?? ''),
            ];
        }

        usort($history, static function ($a, $b) {
            return strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''));
        });

        return [
            'project_id' => (int) ($project['id'] ?? 0),
            'project_name' => (string) ($project['name'] ?? ''),
            'planned_budget' => $planned_budget,
            'budget_increases_total' => $increase_total,
            'budget_current' => $planned_budget + $increase_total,
            'budget_increases' => $budget_increases,
            'cost_items' => $visible_cost_items,
            'cost_total' => $cost_total,
            'budget_history' => $history,
        ];
    }
}
