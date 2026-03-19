<?php

class ERP_OMD_REST_API
{
    private $roles;
    private $employees;
    private $salary_history;
    private $employee_service;
    private $monthly_hours_service;

    public function __construct(
        ERP_OMD_Role_Repository $roles,
        ERP_OMD_Employee_Repository $employees,
        ERP_OMD_Salary_History_Repository $salary_history,
        ERP_OMD_Employee_Service $employee_service,
        ERP_OMD_Monthly_Hours_Service $monthly_hours_service
    ) {
        $this->roles = $roles;
        $this->employees = $employees;
        $this->salary_history = $salary_history;
        $this->employee_service = $employee_service;
        $this->monthly_hours_service = $monthly_hours_service;
    }

    public function register_hooks()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        register_rest_route('erp-omd/v1', '/roles', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'list_roles'],
                'permission_callback' => [$this, 'can_manage_roles'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_role'],
                'permission_callback' => [$this, 'can_manage_roles'],
            ],
        ]);

        register_rest_route('erp-omd/v1', '/roles/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_role'],
                'permission_callback' => [$this, 'can_manage_roles'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_role'],
                'permission_callback' => [$this, 'can_manage_roles'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_role'],
                'permission_callback' => [$this, 'can_manage_roles'],
            ],
        ]);

        register_rest_route('erp-omd/v1', '/employees', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'list_employees'],
                'permission_callback' => [$this, 'can_manage_employees'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_employee'],
                'permission_callback' => [$this, 'can_manage_employees'],
            ],
        ]);

        register_rest_route('erp-omd/v1', '/employees/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_employee'],
                'permission_callback' => [$this, 'can_manage_employees'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_employee'],
                'permission_callback' => [$this, 'can_manage_employees'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_employee'],
                'permission_callback' => [$this, 'can_manage_employees'],
            ],
        ]);

        register_rest_route('erp-omd/v1', '/employees/(?P<id>\d+)/salary', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'list_salary_history'],
                'permission_callback' => [$this, 'can_manage_salary'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_salary_history'],
                'permission_callback' => [$this, 'can_manage_salary'],
            ],
        ]);

        register_rest_route('erp-omd/v1', '/salary/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_salary_history'],
                'permission_callback' => [$this, 'can_manage_salary'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_salary_history'],
                'permission_callback' => [$this, 'can_manage_salary'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_salary_history'],
                'permission_callback' => [$this, 'can_manage_salary'],
            ],
        ]);

        register_rest_route('erp-omd/v1', '/monthly-hours/(?P<year_month>\d{4}-\d{2})', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_monthly_hours_suggestion'],
                'permission_callback' => [$this, 'can_manage_salary'],
            ],
        ]);
    }

    public function can_manage_roles()
    {
        return current_user_can('erp_omd_manage_roles');
    }

    public function can_manage_employees()
    {
        return current_user_can('erp_omd_manage_employees');
    }

    public function can_manage_salary()
    {
        return current_user_can('erp_omd_manage_salary');
    }

    public function list_roles()
    {
        return rest_ensure_response($this->roles->all());
    }

    public function get_role(WP_REST_Request $request)
    {
        $role = $this->roles->find((int) $request['id']);
        return $role ? rest_ensure_response($role) : new WP_Error('erp_omd_role_not_found', __('Role not found.', 'erp-omd'), ['status' => 404]);
    }

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

    public function delete_role(WP_REST_Request $request)
    {
        $this->roles->delete((int) $request['id']);
        return new WP_REST_Response(null, 204);
    }

    public function list_employees()
    {
        return rest_ensure_response($this->employees->all());
    }

    public function get_employee(WP_REST_Request $request)
    {
        $employee = $this->employees->find((int) $request['id']);
        return $employee ? rest_ensure_response($employee) : new WP_Error('erp_omd_employee_not_found', __('Employee not found.', 'erp-omd'), ['status' => 404]);
    }

    public function create_employee(WP_REST_Request $request)
    {
        $payload = $this->sanitize_employee_payload($request);
        $errors = $this->employee_service->validate_employee($payload);
        if ($errors) {
            return new WP_Error('erp_omd_employee_invalid', implode(' ', $errors), ['status' => 422]);
        }
        $id = $this->employees->create($payload);
        $this->sync_wp_role($payload['user_id'], $payload['account_type']);
        return new WP_REST_Response($this->employees->find($id), 201);
    }

    public function update_employee(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        if (! $this->employees->find($id)) {
            return new WP_Error('erp_omd_employee_not_found', __('Employee not found.', 'erp-omd'), ['status' => 404]);
        }
        $payload = $this->sanitize_employee_payload($request);
        $errors = $this->employee_service->validate_employee($payload, $id);
        if ($errors) {
            return new WP_Error('erp_omd_employee_invalid', implode(' ', $errors), ['status' => 422]);
        }
        $this->employees->update($id, $payload);
        $this->sync_wp_role($payload['user_id'], $payload['account_type']);
        return rest_ensure_response($this->employees->find($id));
    }

    public function delete_employee(WP_REST_Request $request)
    {
        $this->employees->deactivate((int) $request['id']);
        return new WP_REST_Response(null, 204);
    }

    public function list_salary_history(WP_REST_Request $request)
    {
        return rest_ensure_response($this->salary_history->for_employee((int) $request['id']));
    }

    public function create_salary_history(WP_REST_Request $request)
    {
        $payload = $this->sanitize_salary_payload($request, (int) $request['id']);
        $payload = $this->employee_service->prepare_salary_payload($payload);
        $errors = $this->employee_service->validate_salary($payload);
        if ($errors) {
            return new WP_Error('erp_omd_salary_invalid', implode(' ', $errors), ['status' => 422]);
        }
        $id = $this->salary_history->create($payload);
        return new WP_REST_Response($this->salary_history->find($id), 201);
    }

    public function get_salary_history(WP_REST_Request $request)
    {
        $salary = $this->salary_history->find((int) $request['id']);
        return $salary ? rest_ensure_response($salary) : new WP_Error('erp_omd_salary_not_found', __('Salary history not found.', 'erp-omd'), ['status' => 404]);
    }

    public function update_salary_history(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $existing = $this->salary_history->find($id);
        if (! $existing) {
            return new WP_Error('erp_omd_salary_not_found', __('Salary history not found.', 'erp-omd'), ['status' => 404]);
        }
        $payload = $this->sanitize_salary_payload($request, (int) $existing['employee_id']);
        $payload = $this->employee_service->prepare_salary_payload($payload);
        $errors = $this->employee_service->validate_salary($payload, $id);
        if ($errors) {
            return new WP_Error('erp_omd_salary_invalid', implode(' ', $errors), ['status' => 422]);
        }
        $this->salary_history->update($id, $payload);
        return rest_ensure_response($this->salary_history->find($id));
    }

    public function delete_salary_history(WP_REST_Request $request)
    {
        $this->salary_history->delete((int) $request['id']);
        return new WP_REST_Response(null, 204);
    }

    public function get_monthly_hours_suggestion(WP_REST_Request $request)
    {
        return rest_ensure_response([
            'year_month' => (string) $request['year_month'],
            'suggested_hours' => $this->monthly_hours_service->suggested_hours((string) $request['year_month']),
        ]);
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
