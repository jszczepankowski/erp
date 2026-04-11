<?php

class ERP_OMD_Estimate_Service
{
    private $estimates;
    private $estimate_items;
    private $clients;
    private $projects;
    private $project_costs;
    private $estimate_audit;
    private $project_requests;

    public function __construct(
        ERP_OMD_Estimate_Repository $estimates,
        ERP_OMD_Estimate_Item_Repository $estimate_items,
        ERP_OMD_Client_Repository $clients,
        ERP_OMD_Project_Repository $projects,
        $project_costs = null,
        $estimate_audit = null,
        $project_requests = null
    ) {
        $this->estimates = $estimates;
        $this->estimate_items = $estimate_items;
        $this->clients = $clients;
        $this->projects = $projects;
        $this->project_costs = $project_costs;
        $this->estimate_audit = $estimate_audit;
        $this->project_requests = $project_requests;
    }

    public function validate_estimate(array $data, array $existing = null)
    {
        $errors = [];

        if (trim((string) ($data['name'] ?? '')) === '') {
            $errors[] = __('Nazwa kosztorysu jest wymagana.', 'erp-omd');
        }

        if (! $this->clients->find((int) $data['client_id'])) {
            $errors[] = __('Kosztorys musi być przypisany do istniejącego klienta.', 'erp-omd');
        }

        if (! in_array($data['status'], ['wstepny', 'do_akceptacji', 'zaakceptowany', 'odrzucony'], true)) {
            $errors[] = __('Status kosztorysu jest niepoprawny.', 'erp-omd');
        }

        if ($existing && ($existing['status'] ?? '') === 'zaakceptowany') {
            $locked_fields = ['client_id', 'name'];
            foreach ($locked_fields as $field) {
                $before = (string) ($existing[$field] ?? '');
                $after = (string) ($data[$field] ?? '');
                if ($before !== $after) {
                    $errors[] = __('Zaakceptowany kosztorys pozwala zmienić tylko status.', 'erp-omd');
                    $this->log_audit((int) ($existing['id'] ?? 0), 'locked_update_rejected', [
                        'field' => $field,
                        'before' => $before,
                        'after' => $after,
                    ]);
                    break;
                }
            }
        }

        return $errors;
    }

    public function validate_item(array $data, array $estimate = null, array $existing_item = null)
    {
        $errors = [];

        if (! $estimate) {
            $errors[] = __('Kosztorys dla pozycji nie istnieje.', 'erp-omd');
            return $errors;
        }

        if (($estimate['status'] ?? '') === 'zaakceptowany') {
            $errors[] = __('Nie można zmieniać pozycji zaakceptowanego kosztorysu.', 'erp-omd');
            $this->log_audit((int) ($estimate['id'] ?? 0), 'locked_item_change_rejected', [
                'item_id' => (int) ($existing_item['id'] ?? 0),
                'incoming_name' => (string) ($data['name'] ?? ''),
            ]);
        }

        if ($existing_item && (int) ($existing_item['estimate_id'] ?? 0) !== (int) $estimate['id']) {
            $errors[] = __('Pozycja kosztorysu nie należy do wskazanego kosztorysu.', 'erp-omd');
        }

        if ($data['name'] === '') {
            $errors[] = __('Nazwa pozycji kosztorysu jest wymagana.', 'erp-omd');
        }

        if ((float) $data['qty'] <= 0) {
            $errors[] = __('Ilość pozycji kosztorysu musi być większa od zera.', 'erp-omd');
        }

        if ((float) $data['price'] < 0) {
            $errors[] = __('Cena pozycji kosztorysu nie może być ujemna.', 'erp-omd');
        }

        if ((float) $data['cost_internal'] < 0) {
            $errors[] = __('Koszt wewnętrzny pozycji kosztorysu nie może być ujemny.', 'erp-omd');
        }

        return $errors;
    }

    public function calculate_totals(array $items)
    {
        $totals = [
            'net' => 0.0,
            'tax' => 0.0,
            'gross' => 0.0,
            'internal_cost' => 0.0,
        ];

        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $internal_cost = (float) ($item['cost_internal'] ?? 0);

            $totals['net'] += $qty * $price;
            $totals['internal_cost'] += $internal_cost;
        }

        $totals['net'] = round($totals['net'], 2);
        $totals['tax'] = round($totals['net'] * 0.23, 2);
        $totals['gross'] = round($totals['net'] + $totals['tax'], 2);
        $totals['internal_cost'] = round($totals['internal_cost'], 2);

        return $totals;
    }

    public function accept($estimate_id)
    {
        global $wpdb;

        $estimate = $this->estimates->find((int) $estimate_id);
        if (! $estimate) {
            return new WP_Error('erp_omd_estimate_not_found', __('Kosztorys nie istnieje.', 'erp-omd'), ['status' => 404]);
        }

        if (($estimate['status'] ?? '') === 'zaakceptowany') {
            $linked_project = $this->projects->find_by_estimate_id((int) $estimate_id);
            return ['estimate' => $estimate, 'project' => $linked_project];
        }

        $items = $this->estimate_items->for_estimate((int) $estimate_id);
        if ($items === []) {
            return new WP_Error('erp_omd_estimate_empty', __('Nie można zaakceptować pustego kosztorysu.', 'erp-omd'), ['status' => 422]);
        }

        $client = $this->clients->find((int) $estimate['client_id']);
        if (! $client) {
            return new WP_Error('erp_omd_estimate_client_missing', __('Klient kosztorysu nie istnieje.', 'erp-omd'), ['status' => 422]);
        }

        $totals = $this->calculate_totals($items);
        $existing_project = $this->projects->find_by_estimate_id((int) $estimate_id);
        if ($existing_project) {
            $this->estimates->mark_accepted((int) $estimate_id, get_current_user_id());
            $this->log_audit((int) $estimate_id, 'accepted_existing_project', ['project_id' => (int) ($existing_project['id'] ?? 0)]);
            return ['estimate' => $this->estimates->find((int) $estimate_id), 'project' => $existing_project];
        }

        $project_name = trim((string) ($estimate['name'] ?? ''));
        if ($project_name === '') {
            $project_name = sprintf(__('Kosztorys #%d — %s', 'erp-omd'), (int) $estimate_id, $client['name']);
        }
        $brief_lines = array_map(
            static function ($item) {
                return sprintf('%s x %s', (string) ($item['name'] ?? ''), (string) ($item['qty'] ?? 0));
            },
            array_slice($items, 0, 5)
        );

        $wpdb->query('START TRANSACTION');

        $default_manager_id = (int) ($client['account_manager_id'] ?? 0);
        $request_context = ['primary_manager_id' => 0, 'requester_employee_id' => 0];
        if ($estimate_id && $this->project_requests && method_exists($this->project_requests, 'all')) {
            $requests = array_values(
                array_filter(
                    (array) $this->project_requests->all(),
                    static function ($request) use ($estimate_id) {
                        return (int) ($request['estimate_id'] ?? 0) === (int) $estimate_id;
                    }
                )
            );

            if ($requests !== []) {
                usort(
                    $requests,
                    static function ($left, $right) {
                        return [(string) ($right['created_at'] ?? ''), (int) ($right['id'] ?? 0)] <=> [(string) ($left['created_at'] ?? ''), (int) ($left['id'] ?? 0)];
                    }
                );

                $request = $requests[0];
                $requester_employee_id = (int) ($request['requester_employee_id'] ?? 0);
                $preferred_manager_id = (int) ($request['preferred_manager_id'] ?? 0);
                $request_context = [
                    'primary_manager_id' => $preferred_manager_id > 0 ? $preferred_manager_id : $requester_employee_id,
                    'requester_employee_id' => $requester_employee_id,
                ];
            }
        }
        $request_primary_manager_id = (int) ($request_context['primary_manager_id'] ?? 0);
        $requester_employee_id = (int) ($request_context['requester_employee_id'] ?? 0);
        $manager_id = $request_primary_manager_id > 0 ? $request_primary_manager_id : $default_manager_id;
        $manager_ids = array_values(array_unique(array_filter([$manager_id, $requester_employee_id])));

        $project_id = $this->projects->create([
            'client_id' => (int) $estimate['client_id'],
            'name' => $project_name,
            'billing_type' => 'fixed_price',
            'budget' => (float) $totals['net'],
            'retainer_monthly_fee' => 0,
            'status' => 'w_realizacji',
            'start_date' => '',
            'end_date' => '',
            'manager_id' => $manager_id,
            'manager_ids' => $manager_ids,
            'estimate_id' => (int) $estimate_id,
            'brief' => implode("\n", $brief_lines),
            'alert_margin_threshold' => null,
        ]);

        if ($project_id <= 0) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('erp_omd_project_create_failed', __('Nie udało się utworzyć projektu z kosztorysu.', 'erp-omd'), ['status' => 500]);
        }

        $this->copy_internal_costs_to_project((int) $project_id, $items);
        $this->estimates->mark_accepted((int) $estimate_id, get_current_user_id());
        $this->log_audit((int) $estimate_id, 'accepted', [
            'project_id' => $project_id,
            'project_budget' => (float) $totals['net'],
            'items_count' => count($items),
        ]);
        $wpdb->query('COMMIT');

        return [
            'estimate' => $this->estimates->find((int) $estimate_id),
            'project' => $this->projects->find($project_id),
            'totals' => $totals,
        ];
    }

    private function copy_internal_costs_to_project($project_id, array $items)
    {
        if (! $project_id || ! $this->project_costs || ! method_exists($this->project_costs, 'create')) {
            return;
        }

        $user_id = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
        $cost_date = function_exists('current_time') ? (string) current_time('Y-m-d') : gmdate('Y-m-d');

        foreach ($items as $item) {
            $amount = round((float) ($item['cost_internal'] ?? 0), 2);
            if ($amount < 0) {
                continue;
            }

            $item_name = trim((string) ($item['name'] ?? ''));
            $description = $item_name !== ''
                ? sprintf(__('Koszt wewnętrzny z kosztorysu: %s', 'erp-omd'), $item_name)
                : __('Koszt wewnętrzny z kosztorysu', 'erp-omd');

            $this->project_costs->create([
                'project_id' => (int) $project_id,
                'amount' => $amount,
                'description' => $description,
                'cost_date' => $cost_date,
                'created_by_user_id' => $user_id,
            ]);
        }
    }

    private function log_audit($estimate_id, $action, array $details)
    {
        if (! $estimate_id || ! $this->estimate_audit || ! method_exists($this->estimate_audit, 'log')) {
            return;
        }

        $this->estimate_audit->log($estimate_id, $action, $details, function_exists('get_current_user_id') ? get_current_user_id() : null);
    }
}
