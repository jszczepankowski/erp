<?php

class ERP_OMD_REST_API
{
    private $roles;
    private $employees;
    private $salary_history;
    private $employee_service;
    private $monthly_hours_service;
    private $clients;
    private $client_rates;
    private $projects;
    private $project_notes;
    private $client_project_service;

    public function __construct(
        ERP_OMD_Role_Repository $roles,
        ERP_OMD_Employee_Repository $employees,
        ERP_OMD_Salary_History_Repository $salary_history,
        ERP_OMD_Employee_Service $employee_service,
        ERP_OMD_Monthly_Hours_Service $monthly_hours_service,
        ERP_OMD_Client_Repository $clients,
        ERP_OMD_Client_Rate_Repository $client_rates,
        ERP_OMD_Project_Repository $projects,
        ERP_OMD_Project_Note_Repository $project_notes,
        ERP_OMD_Client_Project_Service $client_project_service
    ) {
        $this->roles = $roles;
        $this->employees = $employees;
        $this->salary_history = $salary_history;
        $this->employee_service = $employee_service;
        $this->monthly_hours_service = $monthly_hours_service;
        $this->clients = $clients;
        $this->client_rates = $client_rates;
        $this->projects = $projects;
        $this->project_notes = $project_notes;
        $this->client_project_service = $client_project_service;
    }

    public function register_hooks()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        // Roles.
        register_rest_route('erp-omd/v1', '/roles', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_roles'], 'permission_callback' => [$this, 'can_manage_roles']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_role'], 'permission_callback' => [$this, 'can_manage_roles']],
        ]);
        register_rest_route('erp-omd/v1', '/roles/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_role'], 'permission_callback' => [$this, 'can_manage_roles']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_role'], 'permission_callback' => [$this, 'can_manage_roles']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_role'], 'permission_callback' => [$this, 'can_manage_roles']],
        ]);

        // Employees + salary.
        register_rest_route('erp-omd/v1', '/employees', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_employees'], 'permission_callback' => [$this, 'can_manage_employees']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_employee'], 'permission_callback' => [$this, 'can_manage_employees']],
        ]);
        register_rest_route('erp-omd/v1', '/employees/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_employee'], 'permission_callback' => [$this, 'can_manage_employees']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_employee'], 'permission_callback' => [$this, 'can_manage_employees']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_employee'], 'permission_callback' => [$this, 'can_manage_employees']],
        ]);
        register_rest_route('erp-omd/v1', '/employees/(?P<id>\d+)/salary', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_salary_history'], 'permission_callback' => [$this, 'can_manage_salary']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_salary_history'], 'permission_callback' => [$this, 'can_manage_salary']],
        ]);
        register_rest_route('erp-omd/v1', '/salary/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_salary_history'], 'permission_callback' => [$this, 'can_manage_salary']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_salary_history'], 'permission_callback' => [$this, 'can_manage_salary']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_salary_history'], 'permission_callback' => [$this, 'can_manage_salary']],
        ]);
        register_rest_route('erp-omd/v1', '/monthly-hours/(?P<year_month>\d{4}-\d{2})', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_monthly_hours_suggestion'], 'permission_callback' => [$this, 'can_manage_salary']],
        ]);

        // Clients + rates.
        register_rest_route('erp-omd/v1', '/clients', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_clients'], 'permission_callback' => [$this, 'can_manage_clients']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_client'], 'permission_callback' => [$this, 'can_manage_clients']],
        ]);
        register_rest_route('erp-omd/v1', '/clients/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_client'], 'permission_callback' => [$this, 'can_manage_clients']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_client'], 'permission_callback' => [$this, 'can_manage_clients']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_client'], 'permission_callback' => [$this, 'can_manage_clients']],
        ]);
        register_rest_route('erp-omd/v1', '/clients/(?P<id>\d+)/rates', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_client_rates'], 'permission_callback' => [$this, 'can_manage_clients']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_client_rate'], 'permission_callback' => [$this, 'can_manage_clients']],
        ]);
        register_rest_route('erp-omd/v1', '/client-rates/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_client_rate'], 'permission_callback' => [$this, 'can_manage_clients']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_client_rate'], 'permission_callback' => [$this, 'can_manage_clients']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_client_rate'], 'permission_callback' => [$this, 'can_manage_clients']],
        ]);

        // Projects + notes.
        register_rest_route('erp-omd/v1', '/projects', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_projects'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_project'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/projects/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_project'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_project'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_project'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/projects/(?P<id>\d+)/notes', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_project_notes'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_project_note'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
    }

    public function can_manage_roles() { return current_user_can('erp_omd_manage_roles'); }
    public function can_manage_employees() { return current_user_can('erp_omd_manage_employees'); }
    public function can_manage_salary() { return current_user_can('erp_omd_manage_salary'); }
    public function can_manage_clients() { return current_user_can('erp_omd_manage_clients'); }
    public function can_manage_projects() { return current_user_can('erp_omd_manage_projects'); }

    public function list_roles() { return rest_ensure_response($this->roles->all()); }
    public function get_role(WP_REST_Request $request) { return $this->find_or_error($this->roles->find((int) $request['id']), 'erp_omd_role_not_found', __('Role not found.', 'erp-omd')); }
    public function create_role(WP_REST_Request $request)
    {
        $payload = $this->sanitize_role_payload($request);
        if ($this->roles->slug_exists($payload['slug'])) {
            return new WP_Error('erp_omd_role_slug_exists', __('Role slug must be unique.', 'erp-omd'), ['status' => 422]);
        }
        $id = $this->roles->create($payload);
        return new WP_REST_Response($this->roles->find($id), 201);
    }
    public function update_role(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        if (! $this->roles->find($id)) {
            return new WP_Error('erp_omd_role_not_found', __('Role not found.', 'erp-omd'), ['status' => 404]);
        }
        $payload = $this->sanitize_role_payload($request);
        if ($this->roles->slug_exists($payload['slug'], $id)) {
            return new WP_Error('erp_omd_role_slug_exists', __('Role slug must be unique.', 'erp-omd'), ['status' => 422]);
        }
        $this->roles->update($id, $payload);
        return rest_ensure_response($this->roles->find($id));
    }
    public function delete_role(WP_REST_Request $request) { $this->roles->delete((int) $request['id']); return new WP_REST_Response(null, 204); }

    public function list_employees() { return rest_ensure_response($this->employees->all()); }
    public function get_employee(WP_REST_Request $request) { return $this->find_or_error($this->employees->find((int) $request['id']), 'erp_omd_employee_not_found', __('Employee not found.', 'erp-omd')); }
    public function create_employee(WP_REST_Request $request)
    {
        $payload = $this->sanitize_employee_payload($request);
        $errors = $this->employee_service->validate_employee($payload);
        if ($errors) { return new WP_Error('erp_omd_employee_invalid', implode(' ', $errors), ['status' => 422]); }
        $id = $this->employees->create($payload);
        $this->sync_wp_role($payload['user_id'], $payload['account_type']);
        return new WP_REST_Response($this->employees->find($id), 201);
    }
    public function update_employee(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        if (! $this->employees->find($id)) { return new WP_Error('erp_omd_employee_not_found', __('Employee not found.', 'erp-omd'), ['status' => 404]); }
        $payload = $this->sanitize_employee_payload($request);
        $errors = $this->employee_service->validate_employee($payload, $id);
        if ($errors) { return new WP_Error('erp_omd_employee_invalid', implode(' ', $errors), ['status' => 422]); }
        $this->employees->update($id, $payload);
        $this->sync_wp_role($payload['user_id'], $payload['account_type']);
        return rest_ensure_response($this->employees->find($id));
    }
    public function delete_employee(WP_REST_Request $request) { $this->employees->deactivate((int) $request['id']); return new WP_REST_Response(null, 204); }
    public function list_salary_history(WP_REST_Request $request) { return rest_ensure_response($this->salary_history->for_employee((int) $request['id'])); }
    public function create_salary_history(WP_REST_Request $request)
    {
        $payload = $this->employee_service->prepare_salary_payload($this->sanitize_salary_payload($request, (int) $request['id']));
        $errors = $this->employee_service->validate_salary($payload);
        if ($errors) { return new WP_Error('erp_omd_salary_invalid', implode(' ', $errors), ['status' => 422]); }
        $id = $this->salary_history->create($payload);
        return new WP_REST_Response($this->salary_history->find($id), 201);
    }
    public function get_salary_history(WP_REST_Request $request) { return $this->find_or_error($this->salary_history->find((int) $request['id']), 'erp_omd_salary_not_found', __('Salary history not found.', 'erp-omd')); }
    public function update_salary_history(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $existing = $this->salary_history->find($id);
        if (! $existing) { return new WP_Error('erp_omd_salary_not_found', __('Salary history not found.', 'erp-omd'), ['status' => 404]); }
        $payload = $this->employee_service->prepare_salary_payload($this->sanitize_salary_payload($request, (int) $existing['employee_id']));
        $errors = $this->employee_service->validate_salary($payload, $id);
        if ($errors) { return new WP_Error('erp_omd_salary_invalid', implode(' ', $errors), ['status' => 422]); }
        $this->salary_history->update($id, $payload);
        return rest_ensure_response($this->salary_history->find($id));
    }
    public function delete_salary_history(WP_REST_Request $request) { $this->salary_history->delete((int) $request['id']); return new WP_REST_Response(null, 204); }
    public function get_monthly_hours_suggestion(WP_REST_Request $request)
    {
        return rest_ensure_response(['year_month' => (string) $request['year_month'], 'suggested_hours' => $this->monthly_hours_service->suggested_hours((string) $request['year_month'])]);
    }

    public function list_clients() { return rest_ensure_response($this->clients->all()); }
    public function get_client(WP_REST_Request $request) { return $this->find_or_error($this->clients->find((int) $request['id']), 'erp_omd_client_not_found', __('Client not found.', 'erp-omd')); }
    public function create_client(WP_REST_Request $request)
    {
        $payload = $this->sanitize_client_payload($request);
        $errors = $this->client_project_service->validate_client($payload);
        if ($errors) { return new WP_Error('erp_omd_client_invalid', implode(' ', $errors), ['status' => 422]); }
        $id = $this->clients->create($payload);
        return new WP_REST_Response($this->clients->find($id), 201);
    }
    public function update_client(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        if (! $this->clients->find($id)) { return new WP_Error('erp_omd_client_not_found', __('Client not found.', 'erp-omd'), ['status' => 404]); }
        $payload = $this->sanitize_client_payload($request);
        $errors = $this->client_project_service->validate_client($payload, $id);
        if ($errors) { return new WP_Error('erp_omd_client_invalid', implode(' ', $errors), ['status' => 422]); }
        $this->clients->update($id, $payload);
        return rest_ensure_response($this->clients->find($id));
    }
    public function delete_client(WP_REST_Request $request) { $this->clients->deactivate((int) $request['id']); return new WP_REST_Response(null, 204); }
    public function list_client_rates(WP_REST_Request $request) { return rest_ensure_response($this->client_rates->for_client((int) $request['id'])); }
    public function create_client_rate(WP_REST_Request $request)
    {
        $client_id = (int) $request['id'];
        $role_id = (int) $request->get_param('role_id');
        $rate = (float) $request->get_param('rate');
        $errors = $this->client_project_service->validate_client_rate($client_id, $role_id, $rate);
        if ($errors) { return new WP_Error('erp_omd_client_rate_invalid', implode(' ', $errors), ['status' => 422]); }
        $id = $this->client_rates->upsert($client_id, $role_id, $rate);
        return new WP_REST_Response($this->client_rates->find($id), 201);
    }
    public function get_client_rate(WP_REST_Request $request) { return $this->find_or_error($this->client_rates->find((int) $request['id']), 'erp_omd_client_rate_not_found', __('Client rate not found.', 'erp-omd')); }
    public function update_client_rate(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $existing = $this->client_rates->find($id);
        if (! $existing) { return new WP_Error('erp_omd_client_rate_not_found', __('Client rate not found.', 'erp-omd'), ['status' => 404]); }
        $role_id = (int) ($request->get_param('role_id') ?: $existing['role_id']);
        $rate = (float) ($request->get_param('rate') ?: $existing['rate']);
        $errors = $this->client_project_service->validate_client_rate((int) $existing['client_id'], $role_id, $rate);
        if ($errors) { return new WP_Error('erp_omd_client_rate_invalid', implode(' ', $errors), ['status' => 422]); }
        $upserted_id = $this->client_rates->upsert((int) $existing['client_id'], $role_id, $rate);
        if ($upserted_id !== $id) { $this->client_rates->delete($id); }
        return rest_ensure_response($this->client_rates->find($upserted_id));
    }
    public function delete_client_rate(WP_REST_Request $request) { $this->client_rates->delete((int) $request['id']); return new WP_REST_Response(null, 204); }

    public function list_projects() { return rest_ensure_response($this->projects->all()); }
    public function get_project(WP_REST_Request $request) { return $this->find_or_error($this->projects->find((int) $request['id']), 'erp_omd_project_not_found', __('Project not found.', 'erp-omd')); }
    public function create_project(WP_REST_Request $request)
    {
        $payload = $this->sanitize_project_payload($request);
        $errors = $this->client_project_service->validate_project($payload);
        if ($errors) { return new WP_Error('erp_omd_project_invalid', implode(' ', $errors), ['status' => 422]); }
        $id = $this->projects->create($payload);
        return new WP_REST_Response($this->projects->find($id), 201);
    }
    public function update_project(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        if (! $this->projects->find($id)) { return new WP_Error('erp_omd_project_not_found', __('Project not found.', 'erp-omd'), ['status' => 404]); }
        $payload = $this->sanitize_project_payload($request);
        $errors = $this->client_project_service->validate_project($payload);
        if ($errors) { return new WP_Error('erp_omd_project_invalid', implode(' ', $errors), ['status' => 422]); }
        $this->projects->update($id, $payload);
        return rest_ensure_response($this->projects->find($id));
    }
    public function delete_project(WP_REST_Request $request) { $this->projects->deactivate((int) $request['id']); return new WP_REST_Response(null, 204); }
    public function list_project_notes(WP_REST_Request $request) { return rest_ensure_response($this->project_notes->for_project((int) $request['id'])); }
    public function create_project_note(WP_REST_Request $request)
    {
        $project_id = (int) $request['id'];
        if (! $this->projects->find($project_id)) { return new WP_Error('erp_omd_project_not_found', __('Project not found.', 'erp-omd'), ['status' => 404]); }
        $note = sanitize_textarea_field((string) $request->get_param('note'));
        if ($note === '') { return new WP_Error('erp_omd_project_note_invalid', __('Project note is required.', 'erp-omd'), ['status' => 422]); }
        $id = $this->project_notes->create($project_id, $note, get_current_user_id());
        return new WP_REST_Response($this->project_notes->for_project($project_id)[0] ?? ['id' => $id], 201);
    }

    private function find_or_error($record, $code, $message)
    {
        return $record ? rest_ensure_response($record) : new WP_Error($code, $message, ['status' => 404]);
    }

    private function sanitize_role_payload(WP_REST_Request $request)
    {
        return [
            'name' => sanitize_text_field((string) $request->get_param('name')),
            'slug' => sanitize_title((string) $request->get_param('slug')),
            'description' => sanitize_textarea_field((string) $request->get_param('description')),
            'status' => sanitize_text_field((string) $request->get_param('status')) ?: 'active',
        ];
    }

    private function sanitize_employee_payload(WP_REST_Request $request)
    {
        $role_ids = $request->get_param('role_ids');
        return [
            'user_id' => (int) $request->get_param('user_id'),
            'default_role_id' => (int) $request->get_param('default_role_id'),
            'account_type' => sanitize_text_field((string) $request->get_param('account_type')) ?: 'worker',
            'status' => sanitize_text_field((string) $request->get_param('status')) ?: 'active',
            'role_ids' => is_array($role_ids) ? array_map('intval', $role_ids) : [],
        ];
    }

    private function sanitize_salary_payload(WP_REST_Request $request, $employee_id)
    {
        return [
            'employee_id' => $employee_id,
            'monthly_salary' => (float) $request->get_param('monthly_salary'),
            'monthly_hours' => (float) $request->get_param('monthly_hours'),
            'valid_from' => sanitize_text_field((string) $request->get_param('valid_from')),
            'valid_to' => sanitize_text_field((string) $request->get_param('valid_to')),
        ];
    }

    private function sanitize_client_payload(WP_REST_Request $request)
    {
        return [
            'name' => sanitize_text_field((string) $request->get_param('name')),
            'company' => sanitize_text_field((string) $request->get_param('company')),
            'nip' => sanitize_text_field((string) $request->get_param('nip')),
            'email' => sanitize_email((string) $request->get_param('email')),
            'phone' => sanitize_text_field((string) $request->get_param('phone')),
            'contact_person_name' => sanitize_text_field((string) $request->get_param('contact_person_name')),
            'contact_person_email' => sanitize_email((string) $request->get_param('contact_person_email')),
            'contact_person_phone' => sanitize_text_field((string) $request->get_param('contact_person_phone')),
            'city' => sanitize_text_field((string) $request->get_param('city')),
            'status' => sanitize_text_field((string) $request->get_param('status')) ?: 'active',
            'account_manager_id' => (int) $request->get_param('account_manager_id'),
        ];
    }

    private function sanitize_project_payload(WP_REST_Request $request)
    {
        return [
            'client_id' => (int) $request->get_param('client_id'),
            'name' => sanitize_text_field((string) $request->get_param('name')),
            'billing_type' => sanitize_text_field((string) $request->get_param('billing_type')) ?: 'time_material',
            'budget' => (float) $request->get_param('budget'),
            'retainer_monthly_fee' => (float) $request->get_param('retainer_monthly_fee'),
            'status' => sanitize_text_field((string) $request->get_param('status')) ?: 'do_rozpoczecia',
            'start_date' => sanitize_text_field((string) $request->get_param('start_date')),
            'end_date' => sanitize_text_field((string) $request->get_param('end_date')),
            'manager_id' => (int) $request->get_param('manager_id'),
            'estimate_id' => (int) $request->get_param('estimate_id'),
            'brief' => sanitize_textarea_field((string) $request->get_param('brief')),
        ];
    }

    private function sync_wp_role($user_id, $account_type)
    {
        $user = get_user_by('id', $user_id);
        if (! $user instanceof WP_User) {
            return;
        }

        if ($account_type === 'manager') {
            $user->set_role('erp_omd_manager');
        } elseif ($account_type === 'worker') {
            $user->set_role('erp_omd_worker');
        }
    }
}
