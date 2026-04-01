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
    private $estimates;
    private $estimate_items;
    private $project_notes;
    private $client_project_service;
    private $estimate_service;
    private $project_rates;
    private $project_costs;
    private $project_financials;
    private $time_entries;
    private $attachments;
    private $time_entry_service;
    private $project_financial_service;
    private $reporting_service;
    private $alert_service;
    private $period_service;
    private $adjustment_audit;


    public function __construct(
        ERP_OMD_Role_Repository $roles,
        ERP_OMD_Employee_Repository $employees,
        ERP_OMD_Salary_History_Repository $salary_history,
        ERP_OMD_Employee_Service $employee_service,
        ERP_OMD_Monthly_Hours_Service $monthly_hours_service,
        ERP_OMD_Client_Repository $clients,
        ERP_OMD_Client_Rate_Repository $client_rates,
        ERP_OMD_Project_Repository $projects,
        ERP_OMD_Estimate_Repository $estimates,
        ERP_OMD_Estimate_Item_Repository $estimate_items,
        ERP_OMD_Project_Note_Repository $project_notes,
        ERP_OMD_Client_Project_Service $client_project_service,
        ERP_OMD_Estimate_Service $estimate_service,
        ERP_OMD_Project_Rate_Repository $project_rates,
        ERP_OMD_Project_Cost_Repository $project_costs,
        ERP_OMD_Project_Financial_Repository $project_financials,
        ERP_OMD_Time_Entry_Repository $time_entries,
        ERP_OMD_Attachment_Repository $attachments,
        ERP_OMD_Time_Entry_Service $time_entry_service,
        ERP_OMD_Project_Financial_Service $project_financial_service,
        ERP_OMD_Reporting_Service $reporting_service,
        ERP_OMD_Alert_Service $alert_service,
        ERP_OMD_Period_Service $period_service,
        ERP_OMD_Adjustment_Audit_Repository $adjustment_audit

    ) {
        $this->roles = $roles;
        $this->employees = $employees;
        $this->salary_history = $salary_history;
        $this->employee_service = $employee_service;
        $this->monthly_hours_service = $monthly_hours_service;
        $this->clients = $clients;
        $this->client_rates = $client_rates;
        $this->projects = $projects;
        $this->estimates = $estimates;
        $this->estimate_items = $estimate_items;
        $this->project_notes = $project_notes;
        $this->client_project_service = $client_project_service;
        $this->estimate_service = $estimate_service;
        $this->project_rates = $project_rates;
        $this->project_costs = $project_costs;
        $this->project_financials = $project_financials;
        $this->time_entries = $time_entries;
        $this->attachments = $attachments;
        $this->time_entry_service = $time_entry_service;
        $this->project_financial_service = $project_financial_service;
        $this->reporting_service = $reporting_service;
        $this->alert_service = $alert_service;
        $this->period_service = $period_service;
        $this->adjustment_audit = $adjustment_audit;
    }

    public function register_hooks()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        $this->register_role_routes();
        $this->register_employee_routes();
        $this->register_client_routes();
        $this->register_estimate_routes();
        $this->register_project_routes();
        $this->register_time_routes();
        $this->register_report_routes();
        $this->register_period_management_routes();
        $this->register_hardening_routes();
    }

    private function register_period_management_routes()
    {
        register_rest_route('erp-omd/v1', '/periods/(?P<month>\d{4}-\d{2})', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_period'], 'permission_callback' => [$this, 'can_access_reports']],
        ]);
        register_rest_route('erp-omd/v1', '/periods/(?P<month>\d{4}-\d{2})/checklist', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_period_checklist'], 'permission_callback' => [$this, 'can_access_reports']],
        ]);
        register_rest_route('erp-omd/v1', '/periods/(?P<month>\d{4}-\d{2})/transition', [
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'transition_period'], 'permission_callback' => [$this, 'can_manage_settings']],
        ]);
        register_rest_route('erp-omd/v1', '/adjustments', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_adjustments'], 'permission_callback' => [$this, 'can_manage_settings']],
        ]);
    }

    private function register_role_routes()
    {
        register_rest_route('erp-omd/v1', '/roles', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_roles'], 'permission_callback' => [$this, 'can_manage_roles']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_role'], 'permission_callback' => [$this, 'can_manage_roles']],
        ]);
        register_rest_route('erp-omd/v1', '/roles/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_role'], 'permission_callback' => [$this, 'can_manage_roles']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_role'], 'permission_callback' => [$this, 'can_manage_roles']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_role'], 'permission_callback' => [$this, 'can_manage_roles']],
        ]);
    }

    private function register_employee_routes()
    {
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
    }

    private function register_client_routes()
    {
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
    }

    private function register_estimate_routes()
    {
        register_rest_route('erp-omd/v1', '/estimates', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_estimates'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_estimate'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/estimates/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_estimate'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_estimate'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_estimate'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/estimates/(?P<id>\d+)/items', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_estimate_items'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_estimate_item'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/estimate-items/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_estimate_item'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_estimate_item'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_estimate_item'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/estimates/(?P<id>\d+)/accept', [
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'accept_estimate'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
    }

    private function register_project_routes()
    {
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
        register_rest_route('erp-omd/v1', '/projects/(?P<id>\d+)/rates', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_project_rates'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_project_rate'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/projects/(?P<id>\d+)/costs', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_project_costs'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_project_cost'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/projects/(?P<id>\d+)/finance', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_project_finance'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/project-rates/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_project_rate'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_project_rate'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_project_rate'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/project-costs/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_project_cost'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_project_cost'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_project_cost'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
    }

    private function register_time_routes()
    {
        register_rest_route('erp-omd/v1', '/time', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_time_entries'], 'permission_callback' => [$this, 'can_manage_time']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_time_entry'], 'permission_callback' => [$this, 'can_manage_time']],
        ]);
        register_rest_route('erp-omd/v1', '/time/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_time_entry'], 'permission_callback' => [$this, 'can_manage_time']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_time_entry'], 'permission_callback' => [$this, 'can_manage_time']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_time_entry'], 'permission_callback' => [$this, 'can_manage_time']],
        ]);
        register_rest_route('erp-omd/v1', '/time/(?P<id>\d+)/status', [
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'change_time_entry_status'], 'permission_callback' => [$this, 'can_approve_time']],
        ]);
    }

    private function register_report_routes()
    {
        register_rest_route('erp-omd/v1', '/reports', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_reports'], 'permission_callback' => [$this, 'can_access_reports']],
        ]);
        register_rest_route('erp-omd/v1', '/reports/export', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'export_report_definition'], 'permission_callback' => [$this, 'can_access_reports']],
        ]);
        register_rest_route('erp-omd/v1', '/calendar', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_calendar'], 'permission_callback' => [$this, 'can_access_reports']],
        ]);
    }

    private function register_hardening_routes()
    {
        register_rest_route('erp-omd/v1', '/alerts', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_alerts'], 'permission_callback' => [$this, 'can_access_reports']],
        ]);
        register_rest_route('erp-omd/v1', '/attachments', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_attachments'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_attachment'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/attachments/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_attachment'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_attachment'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/meta', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_meta'], 'permission_callback' => [$this, 'can_access_reports']],
        ]);
        register_rest_route('erp-omd/v1', '/system', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_system_status'], 'permission_callback' => [$this, 'can_manage_settings']],
        ]);
    }


    public function can_manage_roles() { return current_user_can('erp_omd_manage_roles'); }
    public function can_manage_employees() { return current_user_can('erp_omd_manage_employees'); }
    public function can_manage_salary() { return current_user_can('erp_omd_manage_salary'); }
    public function can_manage_clients() { return current_user_can('erp_omd_manage_clients'); }
    public function can_manage_projects() { return current_user_can('erp_omd_manage_projects'); }
    public function can_manage_time() { return current_user_can('erp_omd_manage_time'); }
    public function can_approve_time() { return current_user_can('erp_omd_approve_time') || current_user_can('administrator'); }
    public function can_access_reports() { return current_user_can('erp_omd_access') || current_user_can('administrator'); }
    public function can_manage_settings() { return current_user_can('erp_omd_manage_settings') || current_user_can('administrator'); }


    // existing modules omitted no, implemented below.
    public function list_roles() { return rest_ensure_response($this->roles->all()); }
    public function get_role(WP_REST_Request $request) { return $this->find_or_error($this->roles->find((int) $request['id']), 'erp_omd_role_not_found', __('Role not found.', 'erp-omd')); }
    public function create_role(WP_REST_Request $request) { $payload = $this->sanitize_role_payload($request); if ($this->roles->slug_exists($payload['slug'])) { return new WP_Error('erp_omd_role_slug_exists', __('Role slug must be unique.', 'erp-omd'), ['status' => 422]); } $id = $this->roles->create($payload); return new WP_REST_Response($this->roles->find($id), 201); }
    public function update_role(WP_REST_Request $request) { $id = (int) $request['id']; if (! $this->roles->find($id)) { return new WP_Error('erp_omd_role_not_found', __('Role not found.', 'erp-omd'), ['status' => 404]); } $payload = $this->sanitize_role_payload($request); if ($this->roles->slug_exists($payload['slug'], $id)) { return new WP_Error('erp_omd_role_slug_exists', __('Role slug must be unique.', 'erp-omd'), ['status' => 422]); } $this->roles->update($id, $payload); return rest_ensure_response($this->roles->find($id)); }
    public function delete_role(WP_REST_Request $request) { $this->roles->delete((int) $request['id']); return new WP_REST_Response(null, 204); }

    public function list_employees() { return rest_ensure_response($this->employees->all()); }
    public function get_employee(WP_REST_Request $request) { return $this->find_or_error($this->employees->find((int) $request['id']), 'erp_omd_employee_not_found', __('Employee not found.', 'erp-omd')); }
    public function create_employee(WP_REST_Request $request) { $payload = $this->sanitize_employee_payload($request); $errors = $this->employee_service->validate_employee($payload); if ($errors) { return new WP_Error('erp_omd_employee_invalid', implode(' ', $errors), ['status' => 422]); } $id = $this->employees->create($payload); $this->sync_wp_role($payload['user_id'], $payload['account_type']); return new WP_REST_Response($this->employees->find($id), 201); }
    public function update_employee(WP_REST_Request $request) { $id = (int) $request['id']; if (! $this->employees->find($id)) { return new WP_Error('erp_omd_employee_not_found', __('Employee not found.', 'erp-omd'), ['status' => 404]); } $payload = $this->sanitize_employee_payload($request); $errors = $this->employee_service->validate_employee($payload, $id); if ($errors) { return new WP_Error('erp_omd_employee_invalid', implode(' ', $errors), ['status' => 422]); } $this->employees->update($id, $payload); $this->sync_wp_role($payload['user_id'], $payload['account_type']); return rest_ensure_response($this->employees->find($id)); }
    public function delete_employee(WP_REST_Request $request) { $this->employees->deactivate((int) $request['id']); return new WP_REST_Response(null, 204); }
    public function list_salary_history(WP_REST_Request $request) { return rest_ensure_response($this->salary_history->for_employee((int) $request['id'])); }
    public function create_salary_history(WP_REST_Request $request) { $payload = $this->employee_service->prepare_salary_payload($this->sanitize_salary_payload($request, (int) $request['id'])); $errors = $this->employee_service->validate_salary($payload); if ($errors) { return new WP_Error('erp_omd_salary_invalid', implode(' ', $errors), ['status' => 422]); } $id = $this->salary_history->create($payload); return new WP_REST_Response($this->salary_history->find($id), 201); }
    public function get_salary_history(WP_REST_Request $request) { return $this->find_or_error($this->salary_history->find((int) $request['id']), 'erp_omd_salary_not_found', __('Salary history not found.', 'erp-omd')); }
    public function update_salary_history(WP_REST_Request $request) { $id = (int) $request['id']; $existing = $this->salary_history->find($id); if (! $existing) { return new WP_Error('erp_omd_salary_not_found', __('Salary history not found.', 'erp-omd'), ['status' => 404]); } $payload = $this->employee_service->prepare_salary_payload($this->sanitize_salary_payload($request, (int) $existing['employee_id'])); $errors = $this->employee_service->validate_salary($payload, $id); if ($errors) { return new WP_Error('erp_omd_salary_invalid', implode(' ', $errors), ['status' => 422]); } $this->salary_history->update($id, $payload); return rest_ensure_response($this->salary_history->find($id)); }
    public function delete_salary_history(WP_REST_Request $request) { $this->salary_history->delete((int) $request['id']); return new WP_REST_Response(null, 204); }
    public function get_monthly_hours_suggestion(WP_REST_Request $request) { return rest_ensure_response(['year_month' => (string) $request['year_month'], 'suggested_hours' => $this->monthly_hours_service->suggested_hours((string) $request['year_month'])]); }

    public function list_clients() { return rest_ensure_response($this->clients->all()); }
    public function get_client(WP_REST_Request $request) { return $this->find_or_error($this->clients->find((int) $request['id']), 'erp_omd_client_not_found', __('Client not found.', 'erp-omd')); }
    public function create_client(WP_REST_Request $request) { $payload = $this->client_project_service->prepare_client($this->sanitize_client_payload($request)); $errors = $this->client_project_service->validate_client($payload); if ($errors) { return new WP_Error('erp_omd_client_invalid', implode(' ', $errors), ['status' => 422]); } $id = $this->clients->create($payload); return new WP_REST_Response($this->clients->find($id), 201); }
    public function update_client(WP_REST_Request $request) { $id = (int) $request['id']; $existing = $this->clients->find($id); if (! $existing) { return new WP_Error('erp_omd_client_not_found', __('Client not found.', 'erp-omd'), ['status' => 404]); } $payload = $this->client_project_service->prepare_client(array_merge($existing, $this->sanitize_client_payload($request))); $errors = $this->client_project_service->validate_client($payload, $id); if ($errors) { return new WP_Error('erp_omd_client_invalid', implode(' ', $errors), ['status' => 422]); } $this->clients->update($id, $payload); return rest_ensure_response($this->clients->find($id)); }
    public function delete_client(WP_REST_Request $request) { $this->clients->deactivate((int) $request['id']); return new WP_REST_Response(null, 204); }
    public function list_client_rates(WP_REST_Request $request) { return rest_ensure_response($this->client_rates->for_client((int) $request['id'])); }
    public function create_client_rate(WP_REST_Request $request) { $client_id = (int) $request['id']; $role_id = (int) $request->get_param('role_id'); $rate = (float) $request->get_param('rate'); $errors = $this->client_project_service->validate_client_rate($client_id, $role_id, $rate); if ($errors) { return new WP_Error('erp_omd_client_rate_invalid', implode(' ', $errors), ['status' => 422]); } $id = $this->client_rates->upsert($client_id, $role_id, $rate); return new WP_REST_Response($this->client_rates->find($id), 201); }
    public function get_client_rate(WP_REST_Request $request) { return $this->find_or_error($this->client_rates->find((int) $request['id']), 'erp_omd_client_rate_not_found', __('Client rate not found.', 'erp-omd')); }
    public function update_client_rate(WP_REST_Request $request) { $id = (int) $request['id']; $existing = $this->client_rates->find($id); if (! $existing) { return new WP_Error('erp_omd_client_rate_not_found', __('Client rate not found.', 'erp-omd'), ['status' => 404]); } $role_id = (int) ($request->get_param('role_id') ?: $existing['role_id']); $rate = (float) ($request->get_param('rate') ?: $existing['rate']); $errors = $this->client_project_service->validate_client_rate((int) $existing['client_id'], $role_id, $rate); if ($errors) { return new WP_Error('erp_omd_client_rate_invalid', implode(' ', $errors), ['status' => 422]); } $upserted_id = $this->client_rates->upsert((int) $existing['client_id'], $role_id, $rate); if ($upserted_id !== $id) { $this->client_rates->delete($id); } return rest_ensure_response($this->client_rates->find($upserted_id)); }
    public function delete_client_rate(WP_REST_Request $request) { $this->client_rates->delete((int) $request['id']); return new WP_REST_Response(null, 204); }

    public function list_estimates()
    {
        $estimates = $this->estimates->all();
        foreach ($estimates as &$estimate) {
            $items = $this->estimate_items->for_estimate((int) $estimate['id']);
            $estimate['totals'] = $this->estimate_service->calculate_totals($items);
        }
        unset($estimate);

        return rest_ensure_response($estimates);
    }

    public function get_estimate(WP_REST_Request $request)
    {
        $estimate = $this->estimates->find((int) $request['id']);
        if (! $estimate) {
            return new WP_Error('erp_omd_estimate_not_found', __('Estimate not found.', 'erp-omd'), ['status' => 404]);
        }
        $estimate['items'] = $this->estimate_items->for_estimate((int) $estimate['id']);
        $estimate['totals'] = $this->estimate_service->calculate_totals($estimate['items']);

        return rest_ensure_response($estimate);
    }

    public function create_estimate(WP_REST_Request $request)
    {
        $payload = $this->sanitize_estimate_payload($request);
        $errors = $this->estimate_service->validate_estimate($payload);
        if ($errors) {
            return new WP_Error('erp_omd_estimate_invalid', implode(' ', $errors), ['status' => 422]);
        }
        $id = $this->estimates->create($payload);

        return new WP_REST_Response($this->estimates->find($id), 201);
    }

    public function update_estimate(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $existing = $this->estimates->find($id);
        if (! $existing) {
            return new WP_Error('erp_omd_estimate_not_found', __('Estimate not found.', 'erp-omd'), ['status' => 404]);
        }
        $payload = $this->sanitize_estimate_payload($request, $existing);
        $errors = $this->estimate_service->validate_estimate($payload, $existing);
        if ($errors) {
            return new WP_Error('erp_omd_estimate_invalid', implode(' ', $errors), ['status' => 422]);
        }

        $should_accept_via_status = ($existing['status'] ?? '') !== 'zaakceptowany' && $payload['status'] === 'zaakceptowany';
        if ($should_accept_via_status) {
            $update_payload = $payload;
            $update_payload['status'] = (string) ($existing['status'] ?? 'wstepny');
            $this->estimates->update($id, $update_payload);
            $accept_result = $this->estimate_service->accept($id);
            if ($accept_result instanceof WP_Error) {
                return $accept_result;
            }
        } else {
            $this->estimates->update($id, $payload);
        }

        return rest_ensure_response($this->estimates->find($id));
    }

    public function delete_estimate(WP_REST_Request $request)
    {
        $existing = $this->estimates->find((int) $request['id']);
        if (! $existing) {
            return new WP_REST_Response(null, 204);
        }
        if (($existing['status'] ?? '') === 'zaakceptowany') {
            return new WP_Error('erp_omd_estimate_locked', __('Accepted estimate cannot be deleted.', 'erp-omd'), ['status' => 422]);
        }
        $this->estimates->delete((int) $request['id']);

        return new WP_REST_Response(null, 204);
    }

    public function list_estimate_items(WP_REST_Request $request)
    {
        $estimate = $this->estimates->find((int) $request['id']);
        if (! $estimate) {
            return new WP_Error('erp_omd_estimate_not_found', __('Estimate not found.', 'erp-omd'), ['status' => 404]);
        }

        return rest_ensure_response($this->estimate_items->for_estimate((int) $estimate['id']));
    }

    public function create_estimate_item(WP_REST_Request $request)
    {
        $estimate = $this->estimates->find((int) $request['id']);
        $payload = $this->sanitize_estimate_item_payload($request, (int) $request['id']);
        $errors = $this->estimate_service->validate_item($payload, $estimate);
        if ($errors) {
            return new WP_Error('erp_omd_estimate_item_invalid', implode(' ', $errors), ['status' => 422]);
        }
        $id = $this->estimate_items->create($payload);

        return new WP_REST_Response($this->estimate_items->find($id), 201);
    }

    public function get_estimate_item(WP_REST_Request $request)
    {
        $item = $this->estimate_items->find((int) $request['id']);
        if (! $item) {
            return new WP_Error('erp_omd_estimate_item_not_found', __('Estimate item not found.', 'erp-omd'), ['status' => 404]);
        }

        return rest_ensure_response($item);
    }

    public function update_estimate_item(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $existing = $this->estimate_items->find($id);
        if (! $existing) {
            return new WP_Error('erp_omd_estimate_item_not_found', __('Estimate item not found.', 'erp-omd'), ['status' => 404]);
        }
        $estimate = $this->estimates->find((int) $existing['estimate_id']);
        $payload = $this->sanitize_estimate_item_payload($request, (int) $existing['estimate_id'], $existing);
        $errors = $this->estimate_service->validate_item($payload, $estimate, $existing);
        if ($errors) {
            return new WP_Error('erp_omd_estimate_item_invalid', implode(' ', $errors), ['status' => 422]);
        }
        $this->estimate_items->update($id, $payload);

        return rest_ensure_response($this->estimate_items->find($id));
    }

    public function delete_estimate_item(WP_REST_Request $request)
    {
        $existing = $this->estimate_items->find((int) $request['id']);
        if (! $existing) {
            return new WP_REST_Response(null, 204);
        }
        $estimate = $this->estimates->find((int) $existing['estimate_id']);
        if ($estimate && ($estimate['status'] ?? '') === 'zaakceptowany') {
            return new WP_Error('erp_omd_estimate_item_locked', __('Accepted estimate items cannot be deleted.', 'erp-omd'), ['status' => 422]);
        }
        $this->estimate_items->delete((int) $request['id']);

        return new WP_REST_Response(null, 204);
    }

    public function accept_estimate(WP_REST_Request $request)
    {
        $result = $this->estimate_service->accept((int) $request['id']);
        if ($result instanceof WP_Error) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    public function list_projects() { return rest_ensure_response($this->projects->all()); }
    public function get_project(WP_REST_Request $request) { return $this->find_or_error($this->projects->find((int) $request['id']), 'erp_omd_project_not_found', __('Project not found.', 'erp-omd')); }
    public function create_project(WP_REST_Request $request) { $payload = $this->client_project_service->prepare_project($this->sanitize_project_payload($request)); $errors = $this->client_project_service->validate_project($payload); if ($errors) { return new WP_Error('erp_omd_project_invalid', implode(' ', $errors), ['status' => 422]); } $id = $this->projects->create($payload); $this->project_financial_service->rebuild_for_project($id); return new WP_REST_Response($this->projects->find($id), 201); }
    public function update_project(WP_REST_Request $request) { $id = (int) $request['id']; $existing = $this->projects->find($id); if (! $existing) { return new WP_Error('erp_omd_project_not_found', __('Project not found.', 'erp-omd'), ['status' => 404]); } $payload = $this->client_project_service->prepare_project(array_merge($existing, $this->sanitize_project_payload($request)), $existing); $errors = $this->client_project_service->validate_project($payload, $existing); if ($errors) { return new WP_Error('erp_omd_project_invalid', implode(' ', $errors), ['status' => 422]); } $this->projects->update($id, $payload); $this->project_financial_service->rebuild_for_project($id); return rest_ensure_response($this->projects->find($id)); }
    public function delete_project(WP_REST_Request $request) { $this->projects->deactivate((int) $request['id']); return new WP_REST_Response(null, 204); }
    public function list_project_notes(WP_REST_Request $request) { return rest_ensure_response($this->project_notes->for_project((int) $request['id'])); }
    public function create_project_note(WP_REST_Request $request) { $project_id = (int) $request['id']; if (! $this->projects->find($project_id)) { return new WP_Error('erp_omd_project_not_found', __('Project not found.', 'erp-omd'), ['status' => 404]); } $note = sanitize_textarea_field((string) $request->get_param('note')); if ($note === '') { return new WP_Error('erp_omd_project_note_invalid', __('Project note is required.', 'erp-omd'), ['status' => 422]); } $id = $this->project_notes->create($project_id, $note, get_current_user_id()); return new WP_REST_Response($this->project_notes->for_project($project_id)[0] ?? ['id' => $id], 201); }
    public function list_project_rates(WP_REST_Request $request) { return rest_ensure_response($this->project_rates->for_project((int) $request['id'])); }
    public function create_project_rate(WP_REST_Request $request) { $project_id = (int) $request['id']; $role_id = (int) $request->get_param('role_id'); $rate = (float) $request->get_param('rate'); if (! $this->projects->find($project_id) || ! $this->roles->find($role_id) || $rate < 0) { return new WP_Error('erp_omd_project_rate_invalid', __('Project rate payload is invalid.', 'erp-omd'), ['status' => 422]); } $id = $this->project_rates->upsert($project_id, $role_id, $rate); return new WP_REST_Response($this->project_rates->find($id), 201); }
    public function get_project_rate(WP_REST_Request $request) { return $this->find_or_error($this->project_rates->find((int) $request['id']), 'erp_omd_project_rate_not_found', __('Project rate not found.', 'erp-omd')); }
    public function update_project_rate(WP_REST_Request $request) { $id = (int) $request['id']; $existing = $this->project_rates->find($id); if (! $existing) { return new WP_Error('erp_omd_project_rate_not_found', __('Project rate not found.', 'erp-omd'), ['status' => 404]); } $role_id = (int) ($request->get_param('role_id') ?: $existing['role_id']); $rate = (float) ($request->get_param('rate') ?: $existing['rate']); if (! $this->roles->find($role_id) || $rate < 0) { return new WP_Error('erp_omd_project_rate_invalid', __('Project rate payload is invalid.', 'erp-omd'), ['status' => 422]); } $upserted_id = $this->project_rates->upsert((int) $existing['project_id'], $role_id, $rate); if ($upserted_id !== $id) { $this->project_rates->delete($id); } return rest_ensure_response($this->project_rates->find($upserted_id)); }
    public function delete_project_rate(WP_REST_Request $request) { $this->project_rates->delete((int) $request['id']); return new WP_REST_Response(null, 204); }
    public function list_project_costs(WP_REST_Request $request) { return rest_ensure_response($this->project_costs->for_project((int) $request['id'])); }
    public function create_project_cost(WP_REST_Request $request) { $project_id = (int) $request['id']; $payload = $this->sanitize_project_cost_payload($request, $project_id); $locked_error = $this->assert_period_allows_changes((string) ($payload['cost_date'] ?? ''), 'project_cost', 0, null, $payload); if ($locked_error instanceof WP_Error) { return $locked_error; } $errors = $this->project_financial_service->validate_project_cost($payload); if ($errors) { return new WP_Error('erp_omd_project_cost_invalid', implode(' ', $errors), ['status' => 422]); } $id = $this->project_costs->create($payload); $this->project_financial_service->rebuild_for_project($project_id); return new WP_REST_Response($this->project_costs->find($id), 201); }
    public function get_project_cost(WP_REST_Request $request) { return $this->find_or_error($this->project_costs->find((int) $request['id']), 'erp_omd_project_cost_not_found', __('Project cost not found.', 'erp-omd')); }
    public function update_project_cost(WP_REST_Request $request) { $id = (int) $request['id']; $existing = $this->project_costs->find($id); if (! $existing) { return new WP_Error('erp_omd_project_cost_not_found', __('Project cost not found.', 'erp-omd'), ['status' => 404]); } $payload = $this->sanitize_project_cost_payload($request, (int) $existing['project_id']); $locked_error = $this->assert_period_allows_changes((string) ($payload['cost_date'] ?? ''), 'project_cost', $id, $existing, $payload); if ($locked_error instanceof WP_Error) { return $locked_error; } $errors = $this->project_financial_service->validate_project_cost($payload); if ($errors) { return new WP_Error('erp_omd_project_cost_invalid', implode(' ', $errors), ['status' => 422]); } $this->project_costs->update($id, $payload); $this->project_financial_service->rebuild_for_project((int) $existing['project_id']); return rest_ensure_response($this->project_costs->find($id)); }
    public function delete_project_cost(WP_REST_Request $request) { $existing = $this->project_costs->find((int) $request['id']); if ($existing) { $locked_error = $this->assert_period_allows_changes((string) ($existing['cost_date'] ?? ''), 'project_cost', (int) $request['id'], $existing, null); if ($locked_error instanceof WP_Error) { return $locked_error; } $this->project_costs->delete((int) $request['id']); $this->project_financial_service->rebuild_for_project((int) $existing['project_id']); } return new WP_REST_Response(null, 204); }
    public function get_project_finance(WP_REST_Request $request) { $project_id = (int) $request['id']; if (! $this->projects->find($project_id)) { return new WP_Error('erp_omd_project_not_found', __('Project not found.', 'erp-omd'), ['status' => 404]); } return rest_ensure_response($this->project_financial_service->rebuild_for_project($project_id)); }

    public function list_time_entries(WP_REST_Request $request)
    {
        $current_user = wp_get_current_user();
        $filters = [
            'employee_id' => $request->get_param('employee_id'),
            'project_id' => $request->get_param('project_id'),
            'status' => $request->get_param('status'),
            'entry_date' => $request->get_param('entry_date'),
        ];
        $filters = $this->time_entry_service->get_visible_filters_for_user($current_user, array_filter($filters));
        $entries = $this->time_entries->all(array_filter($filters, [$this, 'is_query_filter']));

        return rest_ensure_response($this->time_entry_service->filter_visible_entries($entries, $current_user));
    }

    public function get_time_entry(WP_REST_Request $request)
    {
        $entry = $this->time_entries->find((int) $request['id']);
        if (! $entry) {
            return new WP_Error('erp_omd_time_not_found', __('Time entry not found.', 'erp-omd'), ['status' => 404]);
        }
        if (! $this->time_entry_service->can_view_entry($entry, wp_get_current_user())) {
            return new WP_Error('erp_omd_time_forbidden', __('You cannot view this time entry.', 'erp-omd'), ['status' => 403]);
        }

        return rest_ensure_response($entry);
    }

    public function create_time_entry(WP_REST_Request $request)
    {
        $payload = $this->sanitize_time_entry_payload($request);
        $locked_error = $this->assert_period_allows_changes((string) ($payload['entry_date'] ?? ''), 'time_entry', 0, null, $payload);
        if ($locked_error instanceof WP_Error) {
            return $locked_error;
        }
        if (! current_user_can('administrator') && ! current_user_can('erp_omd_approve_time')) {
            $payload['employee_id'] = $this->current_employee_id();
            $payload['status'] = 'submitted';
        }
        $payload['created_by_user_id'] = get_current_user_id();
        $payload['approved_by_user_id'] = 0;
        $payload['approved_at'] = null;
        $payload = $this->time_entry_service->prepare($payload);
        $errors = $this->time_entry_service->validate($payload);
        if ($errors) { return new WP_Error('erp_omd_time_invalid', implode(' ', $errors), ['status' => 422]); }
        $id = $this->time_entries->create($payload);
        $this->project_financial_service->rebuild_for_project((int) $payload['project_id']);
        return new WP_REST_Response($this->time_entries->find($id), 201);
    }
    public function update_time_entry(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $existing = $this->time_entries->find($id);
        if (! $existing) { return new WP_Error('erp_omd_time_not_found', __('Time entry not found.', 'erp-omd'), ['status' => 404]); }
        if (! current_user_can('administrator')) { return new WP_Error('erp_omd_time_forbidden', __('Only administrator can edit time entries.', 'erp-omd'), ['status' => 403]); }
        $payload = $this->sanitize_time_entry_payload($request);
        $locked_error = $this->assert_period_allows_changes((string) ($payload['entry_date'] ?? ''), 'time_entry', $id, $existing, $payload);
        if ($locked_error instanceof WP_Error) {
            return $locked_error;
        }
        if (! current_user_can('administrator') && ! current_user_can('erp_omd_approve_time')) {
            $payload['employee_id'] = $this->current_employee_id();
            $payload['status'] = 'submitted';
        }
        $payload['created_by_user_id'] = (int) $existing['created_by_user_id'];
        $payload['approved_by_user_id'] = in_array($payload['status'], ['approved', 'rejected'], true) ? get_current_user_id() : 0;
        $payload['approved_at'] = in_array($payload['status'], ['approved', 'rejected'], true) ? current_time('mysql') : null;
        $payload = $this->time_entry_service->prepare($payload);
        $errors = $this->time_entry_service->validate($payload, $id);
        if ($errors) { return new WP_Error('erp_omd_time_invalid', implode(' ', $errors), ['status' => 422]); }
        $this->time_entries->update($id, $payload);
        $this->project_financial_service->rebuild_for_project((int) $payload['project_id']);
        return rest_ensure_response($this->time_entries->find($id));
    }
    public function delete_time_entry(WP_REST_Request $request)
    {
        if (! current_user_can('administrator')) { return new WP_Error('erp_omd_time_forbidden', __('Only administrator can delete time entries.', 'erp-omd'), ['status' => 403]); }
        $existing = $this->time_entries->find((int) $request['id']);
        if ($existing) {
            $locked_error = $this->assert_period_allows_changes((string) ($existing['entry_date'] ?? ''), 'time_entry', (int) $request['id'], $existing, null);
            if ($locked_error instanceof WP_Error) {
                return $locked_error;
            }
        }
        $this->time_entries->delete((int) $request['id']);
        if ($existing) {
            $this->project_financial_service->rebuild_for_project((int) $existing['project_id']);
        }
        return new WP_REST_Response(null, 204);
    }
    public function change_time_entry_status(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $existing = $this->time_entries->find($id);
        if (! $existing) { return new WP_Error('erp_omd_time_not_found', __('Time entry not found.', 'erp-omd'), ['status' => 404]); }
        $status = sanitize_text_field((string) $request->get_param('status'));
        if (! in_array($status, ['submitted', 'approved', 'rejected'], true)) { return new WP_Error('erp_omd_time_status_invalid', __('Invalid time entry status.', 'erp-omd'), ['status' => 422]); }
        if (! $this->time_entry_service->can_approve_entry($existing, wp_get_current_user())) {
            return new WP_Error('erp_omd_time_forbidden', __('Only the assigned project manager or administrator can approve this time entry.', 'erp-omd'), ['status' => 403]);
        }
        $payload = array_merge($existing, ['status' => $status, 'approved_by_user_id' => get_current_user_id(), 'approved_at' => current_time('mysql')]);
        $this->time_entries->update($id, $payload);
        $this->project_financial_service->rebuild_for_project((int) $existing['project_id']);
        return rest_ensure_response($this->time_entries->find($id));
    }

    public function get_period(WP_REST_Request $request)
    {
        return rest_ensure_response($this->period_service->get_or_create((string) $request['month']));
    }

    public function get_period_checklist(WP_REST_Request $request)
    {
        return rest_ensure_response($this->period_service->checklist((string) $request['month']));
    }

    public function transition_period(WP_REST_Request $request)
    {
        $target_status = sanitize_text_field((string) $request->get_param('status'));
        $result = $this->period_service->transition((string) $request['month'], $target_status);
        if ($result instanceof WP_Error) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    public function list_adjustments(WP_REST_Request $request)
    {
        $filters = [
            'month' => sanitize_text_field((string) $request->get_param('month')),
            'entity_type' => sanitize_key((string) $request->get_param('entity_type')),
            'entity_id' => (int) $request->get_param('entity_id'),
        ];

        if (! preg_match('/^\d{4}-\d{2}$/', (string) $filters['month'])) {
            $filters['month'] = '';
        }
        if (! in_array($filters['entity_type'], ['', 'time_entry', 'project_cost'], true)) {
            $filters['entity_type'] = '';
        }

        return rest_ensure_response($this->adjustment_audit->all($filters));
    }

    public function list_reports(WP_REST_Request $request)
    {
        $filters = $this->reporting_service->sanitize_filters($request->get_params());
        $report_type = sanitize_key((string) ($request->get_param('report_type') ?: $filters['report_type']));

        return rest_ensure_response([
            'filters' => $filters,
            'report_type' => $report_type,
            'rows' => $this->reporting_service->build_report($report_type, $filters),
        ]);
    }

    public function export_report_definition(WP_REST_Request $request)
    {
        $filters = $this->reporting_service->sanitize_filters($request->get_params());
        $report_type = sanitize_key((string) ($request->get_param('report_type') ?: $filters['report_type']));

        return rest_ensure_response($this->reporting_service->export_definition($report_type, $filters));
    }

    public function get_calendar(WP_REST_Request $request)
    {
        $filters = $this->reporting_service->sanitize_filters($request->get_params());
        $filters['tab'] = 'calendar';

        return rest_ensure_response($this->reporting_service->build_calendar($filters));
    }

    public function list_alerts(WP_REST_Request $request)
    {
        $entity_type = sanitize_key((string) $request->get_param('entity_type'));
        $entity_id = (int) $request->get_param('entity_id');
        $alerts = $this->alert_service->all_alerts();

        if ($entity_type !== '') {
            $alerts = array_values(array_filter($alerts, static function ($alert) use ($entity_type, $entity_id) {
                if (($alert['entity_type'] ?? '') !== $entity_type) {
                    return false;
                }

                if ($entity_id > 0 && (int) ($alert['entity_id'] ?? 0) !== $entity_id) {
                    return false;
                }

                return true;
            }));
        }

        return rest_ensure_response($alerts);
    }

    public function list_attachments(WP_REST_Request $request)
    {
        $entity_type = sanitize_key((string) $request->get_param('entity_type'));
        $entity_id = (int) $request->get_param('entity_id');

        if (! in_array($entity_type, ['project', 'estimate'], true) || $entity_id <= 0) {
            return new WP_Error('erp_omd_attachment_invalid_entity', __('Attachment entity_type and entity_id are required.', 'erp-omd'), ['status' => 422]);
        }

        if (! $this->entity_exists($entity_type, $entity_id)) {
            return new WP_Error('erp_omd_attachment_entity_not_found', __('Attachment entity not found.', 'erp-omd'), ['status' => 404]);
        }

        return rest_ensure_response($this->attachments->for_entity($entity_type, $entity_id));
    }

    public function get_attachment(WP_REST_Request $request)
    {
        return $this->find_or_error($this->attachments->find((int) $request['id']), 'erp_omd_attachment_not_found', __('Attachment relation not found.', 'erp-omd'));
    }

    public function create_attachment(WP_REST_Request $request)
    {
        $entity_type = sanitize_key((string) $request->get_param('entity_type'));
        $entity_id = (int) $request->get_param('entity_id');
        $attachment_id = (int) $request->get_param('attachment_id');
        $label = sanitize_text_field((string) $request->get_param('label'));

        if (! in_array($entity_type, ['project', 'estimate'], true) || $entity_id <= 0 || $attachment_id <= 0) {
            return new WP_Error('erp_omd_attachment_invalid_payload', __('Attachment payload is invalid.', 'erp-omd'), ['status' => 422]);
        }
        if (! $this->entity_exists($entity_type, $entity_id)) {
            return new WP_Error('erp_omd_attachment_entity_not_found', __('Attachment entity not found.', 'erp-omd'), ['status' => 404]);
        }
        if (! wp_attachment_is_image($attachment_id) && ! get_post($attachment_id)) {
            return new WP_Error('erp_omd_attachment_media_not_found', __('WordPress media attachment not found.', 'erp-omd'), ['status' => 404]);
        }

        $id = $this->attachments->create([
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'attachment_id' => $attachment_id,
            'label' => $label,
            'created_by_user_id' => get_current_user_id(),
        ]);

        return new WP_REST_Response($this->attachments->find($id), 201);
    }

    public function delete_attachment(WP_REST_Request $request)
    {
        $existing = $this->attachments->find((int) $request['id']);
        if (! $existing) {
            return new WP_REST_Response(null, 204);
        }

        $this->attachments->delete((int) $request['id']);

        return new WP_REST_Response(null, 204);
    }

    public function get_meta()
    {
        return rest_ensure_response([
            'plugin_version' => ERP_OMD_VERSION,
            'db_version' => ERP_OMD_DB_VERSION,
            'billing_types' => [
                ['value' => 'time_material', 'label' => __('Godzinowy', 'erp-omd')],
                ['value' => 'fixed_price', 'label' => __('Ryczałt', 'erp-omd')],
                ['value' => 'retainer', 'label' => __('Abonament', 'erp-omd')],
            ],
            'project_statuses' => ['do_rozpoczecia', 'w_realizacji', 'w_akceptacji', 'do_faktury', 'zakonczony', 'archiwum'],
            'estimate_statuses' => ['wstepny', 'do_akceptacji', 'zaakceptowany'],
            'time_statuses' => ['submitted', 'approved', 'rejected'],
            'report_types' => ['projects', 'clients', 'invoice', 'monthly'],
            'period_modes' => ['LIVE', 'DO ROZLICZENIA', 'ZAMKNIĘTY'],
            'attachment_entity_types' => ['project', 'estimate'],
            'export_variants' => ['client', 'agency'],
        ]);
    }

    public function get_system_status()
    {
        return rest_ensure_response([
            'plugin_version' => ERP_OMD_VERSION,
            'db_version' => ERP_OMD_DB_VERSION,
            'delete_data_on_uninstall' => (bool) get_option('erp_omd_delete_data_on_uninstall', false),
            'alert_margin_threshold' => (float) get_option('erp_omd_alert_margin_threshold', 10),
            'counts' => [
                'roles' => count($this->roles->all()),
                'employees' => count($this->employees->all()),
                'clients' => count($this->clients->all()),
                'projects' => count($this->projects->all()),
                'estimates' => count($this->estimates->all()),
                'alerts' => count($this->alert_service->all_alerts()),
            ],
            'current_user' => [
                'id' => (int) get_current_user_id(),
                'can_manage_settings' => $this->can_manage_settings(),
                'can_manage_projects' => $this->can_manage_projects(),
                'can_manage_time' => $this->can_manage_time(),
                'can_access_reports' => $this->can_access_reports(),
            ],
        ]);
    }


    private function find_or_error($record, $code, $message) { return $record ? rest_ensure_response($record) : new WP_Error($code, $message, ['status' => 404]); }
    private function sanitize_role_payload(WP_REST_Request $request) { return ['name' => sanitize_text_field((string) $request->get_param('name')), 'slug' => sanitize_title((string) $request->get_param('slug')), 'description' => sanitize_textarea_field((string) $request->get_param('description')), 'status' => sanitize_text_field((string) $request->get_param('status')) ?: 'active']; }
    private function sanitize_employee_payload(WP_REST_Request $request) { $role_ids = $request->get_param('role_ids'); return ['user_id' => (int) $request->get_param('user_id'), 'default_role_id' => (int) $request->get_param('default_role_id'), 'account_type' => sanitize_text_field((string) $request->get_param('account_type')) ?: 'worker', 'status' => sanitize_text_field((string) $request->get_param('status')) ?: 'active', 'role_ids' => is_array($role_ids) ? array_map('intval', $role_ids) : []]; }
    private function sanitize_salary_payload(WP_REST_Request $request, $employee_id) { return ['employee_id' => $employee_id, 'monthly_salary' => (float) $request->get_param('monthly_salary'), 'monthly_hours' => (float) $request->get_param('monthly_hours'), 'valid_from' => sanitize_text_field((string) $request->get_param('valid_from')), 'valid_to' => sanitize_text_field((string) $request->get_param('valid_to'))]; }
    private function sanitize_client_payload(WP_REST_Request $request) { return ['name' => sanitize_text_field((string) $request->get_param('name')), 'company' => sanitize_text_field((string) $request->get_param('company')), 'nip' => sanitize_text_field((string) $request->get_param('nip')), 'email' => sanitize_email((string) $request->get_param('email')), 'phone' => sanitize_text_field((string) $request->get_param('phone')), 'contact_person_name' => sanitize_text_field((string) $request->get_param('contact_person_name')), 'contact_person_email' => sanitize_email((string) $request->get_param('contact_person_email')), 'contact_person_phone' => sanitize_text_field((string) $request->get_param('contact_person_phone')), 'city' => sanitize_text_field((string) $request->get_param('city')), 'street' => sanitize_text_field((string) $request->get_param('street')), 'apartment_number' => sanitize_text_field((string) $request->get_param('apartment_number')), 'postal_code' => sanitize_text_field((string) $request->get_param('postal_code')), 'country' => sanitize_text_field((string) $request->get_param('country')), 'status' => sanitize_text_field((string) $request->get_param('status')) ?: 'active', 'account_manager_id' => (int) $request->get_param('account_manager_id'), 'alert_margin_threshold' => sanitize_text_field((string) $request->get_param('alert_margin_threshold'))]; }
    private function sanitize_estimate_payload(WP_REST_Request $request, array $existing = null) { return ['client_id' => (int) ($request->get_param('client_id') ?: ($existing['client_id'] ?? 0)), 'name' => sanitize_text_field((string) ($request->get_param('name') ?: ($existing['name'] ?? ''))), 'status' => sanitize_text_field((string) ($request->get_param('status') ?: ($existing['status'] ?? 'wstepny'))) ?: 'wstepny', 'accepted_by_user_id' => (int) ($existing['accepted_by_user_id'] ?? 0), 'accepted_at' => $existing['accepted_at'] ?? null]; }
    private function sanitize_estimate_item_payload(WP_REST_Request $request, $estimate_id, array $existing = null) { return ['estimate_id' => (int) $estimate_id, 'name' => sanitize_text_field((string) ($request->get_param('name') ?: ($existing['name'] ?? ''))), 'qty' => (float) ($request->get_param('qty') !== null ? $request->get_param('qty') : ($existing['qty'] ?? 0)), 'price' => (float) ($request->get_param('price') !== null ? $request->get_param('price') : ($existing['price'] ?? 0)), 'cost_internal' => (float) ($request->get_param('cost_internal') !== null ? $request->get_param('cost_internal') : ($existing['cost_internal'] ?? 0)), 'comment' => sanitize_textarea_field((string) ($request->get_param('comment') !== null ? $request->get_param('comment') : ($existing['comment'] ?? '')) )]; }
    private function sanitize_project_payload(WP_REST_Request $request) { $manager_ids = $request->get_param('manager_ids'); return ['client_id' => (int) $request->get_param('client_id'), 'name' => sanitize_text_field((string) $request->get_param('name')), 'billing_type' => sanitize_text_field((string) $request->get_param('billing_type')) ?: 'time_material', 'budget' => (float) $request->get_param('budget'), 'retainer_monthly_fee' => (float) $request->get_param('retainer_monthly_fee'), 'status' => sanitize_text_field((string) $request->get_param('status')) ?: 'do_rozpoczecia', 'start_date' => sanitize_text_field((string) $request->get_param('start_date')), 'end_date' => sanitize_text_field((string) $request->get_param('end_date')), 'manager_id' => (int) $request->get_param('manager_id'), 'manager_ids' => is_array($manager_ids) ? array_map('intval', $manager_ids) : [], 'estimate_id' => (int) $request->get_param('estimate_id'), 'operational_close_month' => sanitize_text_field((string) $request->get_param('operational_close_month')), 'brief' => sanitize_textarea_field((string) $request->get_param('brief')), 'alert_margin_threshold' => sanitize_text_field((string) $request->get_param('alert_margin_threshold'))]; }
    private function sanitize_project_cost_payload(WP_REST_Request $request, $project_id) { return ['project_id' => (int) $project_id, 'amount' => (float) $request->get_param('amount'), 'description' => sanitize_textarea_field((string) $request->get_param('description')), 'cost_date' => sanitize_text_field((string) $request->get_param('cost_date')), 'created_by_user_id' => get_current_user_id()]; }
    private function sanitize_time_entry_payload(WP_REST_Request $request) { return ['employee_id' => (int) $request->get_param('employee_id'), 'project_id' => (int) $request->get_param('project_id'), 'role_id' => (int) $request->get_param('role_id'), 'hours' => (float) $request->get_param('hours'), 'entry_date' => sanitize_text_field((string) $request->get_param('entry_date')), 'description' => sanitize_textarea_field((string) $request->get_param('description')), 'status' => sanitize_text_field((string) $request->get_param('status')) ?: 'submitted']; }


    private function current_employee_id()
    {
        $employee = $this->employees->find_by_user_id(get_current_user_id());
        return (int) ($employee['id'] ?? 0);
    }

    private function is_query_filter($value)
    {
        return $value !== '' && $value !== null;
    }

    private function assert_period_allows_changes($date, $entity_type, $entity_id = 0, $old_value = null, $new_value = null)
    {
        $is_admin = current_user_can('administrator');
        $emergency = $is_admin && (bool) rest_sanitize_boolean($new_value['emergency_adjustment'] ?? false);
        $reason = trim((string) ($new_value['adjustment_reason'] ?? ''));
        if ($emergency && $reason === '') {
            return new WP_Error('erp_omd_adjustment_reason_required', __('Tryb awaryjny wymaga podania powodu korekty.', 'erp-omd'), ['status' => 422]);
        }

        if (! $this->period_service->can_modify_date($date, $is_admin, $emergency)) {
            return new WP_Error('erp_omd_period_locked', __('Wybrany miesiąc jest zamknięty i nie można już modyfikować danych.', 'erp-omd'), ['status' => 423]);
        }

        if ($is_admin && ($emergency || $reason !== '')) {
            $this->adjustment_audit->create([
                'month' => substr((string) $date, 0, 7),
                'entity_type' => $entity_type,
                'entity_id' => (int) $entity_id,
                'field_name' => 'payload',
                'old_value' => $old_value,
                'new_value' => $new_value,
                'reason' => $reason !== '' ? $reason : __('Korekta administracyjna w oknie 72h.', 'erp-omd'),
                'adjustment_type' => $emergency ? 'EMERGENCY_ADJUSTMENT' : 'STANDARD',
                'changed_by_user_id' => get_current_user_id(),
            ]);
        }

        return true;
    }

    private function sync_wp_role($user_id, $account_type)
    {
        $user = get_user_by('id', $user_id);
        if (! $user instanceof WP_User) { return; }
        if ($account_type === 'admin') {
            $user->set_role('administrator');
        } elseif ($account_type === 'manager') {
            $user->set_role('erp_omd_manager');
        } elseif ($account_type === 'worker') {
            $user->set_role('erp_omd_worker');
        }
    }

    private function entity_exists($entity_type, $entity_id)
    {
        if ($entity_type === 'project') {
            return (bool) $this->projects->find($entity_id);
        }

        if ($entity_type === 'estimate') {
            return (bool) $this->estimates->find($entity_id);
        }

        return false;
    }
}
