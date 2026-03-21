<?php

class ERP_OMD_Time_Entry_Service
{
    private $time_entries;
    private $employees;
    private $projects;
    private $roles;
    private $client_rates;
    private $project_rates;
    private $salary_history;

    public function __construct(
        ERP_OMD_Time_Entry_Repository $time_entries,
        ERP_OMD_Employee_Repository $employees,
        ERP_OMD_Project_Repository $projects,
        ERP_OMD_Role_Repository $roles,
        ERP_OMD_Client_Rate_Repository $client_rates,
        ERP_OMD_Project_Rate_Repository $project_rates,
        ERP_OMD_Salary_History_Repository $salary_history
    ) {
        $this->time_entries = $time_entries;
        $this->employees = $employees;
        $this->projects = $projects;
        $this->roles = $roles;
        $this->client_rates = $client_rates;
        $this->project_rates = $project_rates;
        $this->salary_history = $salary_history;
    }

    public function validate(array $data, $entry_id = null)
    {
        $errors = [];
        $project = $this->projects->find((int) $data['project_id']);

        if (! $this->employees->find((int) $data['employee_id'])) {
            $errors[] = __('Pracownik wpisu czasu nie istnieje.', 'erp-omd');
        }

        if (! $project) {
            $errors[] = __('Projekt wpisu czasu nie istnieje.', 'erp-omd');
        } elseif (($project['status'] ?? '') !== 'w_realizacji') {
            $errors[] = __('Czas można raportować tylko do projektów w statusie w_realizacji.', 'erp-omd');
        }

        if (! $this->roles->find((int) $data['role_id'])) {
            $errors[] = __('Rola wpisu czasu nie istnieje.', 'erp-omd');
        }

        if ((float) $data['hours'] <= 0) {
            $errors[] = __('Liczba godzin musi być większa od zera.', 'erp-omd');
        }

        $entry_date = DateTimeImmutable::createFromFormat('Y-m-d', (string) $data['entry_date']);
        if (! $entry_date || $entry_date->format('Y-m-d') !== $data['entry_date']) {
            $errors[] = __('Data wpisu czasu jest niepoprawna.', 'erp-omd');
        }

        if (! in_array($data['status'], ['submitted', 'approved', 'rejected'], true)) {
            $errors[] = __('Status wpisu czasu jest niepoprawny.', 'erp-omd');
        }

        if ($this->time_entries->duplicate_exists($data['employee_id'], $data['project_id'], $data['role_id'], $data['hours'], $entry_id ? (int) $entry_id : null)) {
            $errors[] = __('Duplikat wpisu czasu dla employee_id + project_id + role_id + hours.', 'erp-omd');
        }

        return $errors;
    }

    public function prepare(array $data)
    {
        $data['hours'] = round((float) $data['hours'], 2);
        $data['rate_snapshot'] = $this->resolve_rate_snapshot((int) $data['project_id'], (int) $data['role_id'], (string) ($data['entry_date'] ?? ''));
        $data['cost_snapshot'] = $this->resolve_cost_snapshot((int) $data['employee_id'], $data['entry_date']);

        return $data;
    }

    public function resolve_rate_snapshot($project_id, $role_id, $entry_date = '')
    {
        if ($entry_date !== '' && method_exists($this->project_rates, 'find_effective_rate')) {
            $project_rate = $this->project_rates->find_effective_rate($project_id, $role_id, $entry_date);
            if ($project_rate) {
                return round((float) $project_rate['rate'], 2);
            }
        }

        $project_rate = $this->project_rates->find_by_project_role($project_id, $role_id);
        if ($project_rate) {
            return round((float) $project_rate['rate'], 2);
        }

        $project = $this->projects->find($project_id);
        if (! $project) {
            return 0.0;
        }

        if ($entry_date !== '' && method_exists($this->client_rates, 'find_effective_rate')) {
            $client_rate = $this->client_rates->find_effective_rate((int) $project['client_id'], $role_id, $entry_date);
            if ($client_rate) {
                return round((float) $client_rate['rate'], 2);
            }
        }

        $client_rates = $this->client_rates->for_client((int) $project['client_id']);
        foreach ($client_rates as $rate) {
            if ((int) $rate['role_id'] === (int) $role_id) {
                return round((float) $rate['rate'], 2);
            }
        }

        return 0.0;
    }

    public function resolve_cost_snapshot($employee_id, $entry_date)
    {
        $history = $this->salary_history->for_employee($employee_id);
        foreach ($history as $row) {
            $valid_from = $row['valid_from'];
            $valid_to = $row['valid_to'] ?: '9999-12-31';
            if ($entry_date >= $valid_from && $entry_date <= $valid_to) {
                return round((float) $row['hourly_cost'], 2);
            }
        }

        return 0.0;
    }

    public function can_edit_entry($entry, WP_User $user)
    {
        if (user_can($user, 'administrator')) {
            return true;
        }

        $current_employee = $this->employees->find_by_user_id($user->ID);
        if (! $current_employee) {
            return false;
        }

        return (int) ($entry['employee_id'] ?? 0) === (int) $current_employee['id']
            && (string) ($entry['status'] ?? '') === 'submitted';
    }

    public function can_view_entry($entry, WP_User $user)
    {
        if (user_can($user, 'administrator')) {
            return true;
        }

        $current_employee = $this->employees->find_by_user_id($user->ID);
        if (! $current_employee) {
            return false;
        }

        if ((int) $entry['employee_id'] === (int) $current_employee['id']) {
            return true;
        }

        if (! user_can($user, 'erp_omd_approve_time')) {
            return false;
        }

        return $this->is_project_manager_for_entry($entry, $current_employee);
    }

    public function can_delete_entry(WP_User $user, $entry = null)
    {
        if (user_can($user, 'administrator')) {
            return true;
        }

        if (! is_array($entry)) {
            return false;
        }

        $current_employee = $this->employees->find_by_user_id($user->ID);
        if (! $current_employee) {
            return false;
        }

        return (int) ($entry['employee_id'] ?? 0) === (int) $current_employee['id']
            && (string) ($entry['status'] ?? '') === 'submitted';
    }

    public function can_approve_entry($entry, WP_User $user)
    {
        if (user_can($user, 'administrator')) {
            return true;
        }

        if (! user_can($user, 'erp_omd_approve_time')) {
            return false;
        }

        $current_employee = $this->employees->find_by_user_id($user->ID);
        if (! $current_employee) {
            return false;
        }

        return $this->is_project_manager_for_entry($entry, $current_employee);
    }

    public function get_visible_filters_for_user(WP_User $user, array $filters = [])
    {
        if (user_can($user, 'administrator')) {
            return $filters;
        }

        $current_employee = $this->employees->find_by_user_id($user->ID);
        if (! $current_employee) {
            return ['employee_id' => -1];
        }

        if (! user_can($user, 'erp_omd_approve_time')) {
            $filters['employee_id'] = (int) $current_employee['id'];

            return $filters;
        }

        $managed_project_ids = $this->projects->ids_managed_by_employee((int) $current_employee['id']);

        return array_merge(
            $filters,
            [
                '__viewer_employee_id' => (int) $current_employee['id'],
                '__managed_project_ids' => $managed_project_ids,
            ]
        );
    }

    public function filter_visible_entries(array $entries, WP_User $user)
    {
        return array_values(
            array_filter(
                $entries,
                function ($entry) use ($user) {
                    return $this->can_view_entry($entry, $user);
                }
            )
        );
    }

    private function is_project_manager_for_entry($entry, array $current_employee)
    {
        $project = $this->projects->find((int) $entry['project_id']);
        if (! $project) {
            return false;
        }

        $manager_ids = array_map('intval', (array) ($project['manager_ids'] ?? []));
        if ($manager_ids === [] && ! empty($project['manager_id'])) {
            $manager_ids[] = (int) $project['manager_id'];
        }

        return in_array((int) ($current_employee['id'] ?? 0), $manager_ids, true);
    }
}
