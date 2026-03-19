<?php

class ERP_OMD_Admin
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
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_init', [$this, 'handle_forms']);
    }

    public function register_menu()
    {
        add_menu_page(__('ERP OMD', 'erp-omd'), __('ERP OMD', 'erp-omd'), 'erp_omd_access', 'erp-omd', [$this, 'render_dashboard'], 'dashicons-chart-pie', 56);
        add_submenu_page('erp-omd', __('Dashboard', 'erp-omd'), __('Dashboard', 'erp-omd'), 'erp_omd_access', 'erp-omd', [$this, 'render_dashboard']);
        add_submenu_page('erp-omd', __('Pracownicy', 'erp-omd'), __('Pracownicy', 'erp-omd'), 'erp_omd_manage_employees', 'erp-omd-employees', [$this, 'render_employees']);
        add_submenu_page('erp-omd', __('Role', 'erp-omd'), __('Role', 'erp-omd'), 'erp_omd_manage_roles', 'erp-omd-roles', [$this, 'render_roles']);
        add_submenu_page('erp-omd', __('Ustawienia', 'erp-omd'), __('Ustawienia', 'erp-omd'), 'erp_omd_manage_settings', 'erp-omd-settings', [$this, 'render_settings']);
    }

    public function enqueue_assets($hook)
    {
        if (strpos($hook, 'erp-omd') === false) {
            return;
        }

        wp_enqueue_style('erp-omd-admin', ERP_OMD_URL . 'assets/css/admin.css', [], ERP_OMD_VERSION);
    }

    public function handle_forms()
    {
        if (! is_admin() || empty($_POST['erp_omd_action'])) {
            return;
        }

        $action = sanitize_text_field(wp_unslash($_POST['erp_omd_action']));

        switch ($action) {
            case 'save_role':
                $this->handle_role_save();
                break;
            case 'delete_role':
                $this->handle_role_delete();
                break;
            case 'save_employee':
                $this->handle_employee_save();
                break;
            case 'deactivate_employee':
                $this->handle_employee_deactivate();
                break;
            case 'save_salary':
                $this->handle_salary_save();
                break;
            case 'delete_salary':
                $this->handle_salary_delete();
                break;
            case 'save_settings':
                $this->handle_settings_save();
                break;
        }
    }

    public function render_dashboard()
    {
        $employees = $this->employees->all();
        $roles = $this->roles->all();
        include ERP_OMD_PATH . 'templates/admin/dashboard.php';
    }

    public function render_roles()
    {
        $role = null;
        if (! empty($_GET['id'])) {
            $role = $this->roles->find((int) $_GET['id']);
        }
        $roles = $this->roles->all();
        include ERP_OMD_PATH . 'templates/admin/roles.php';
    }

    public function render_employees()
    {
        $employee = null;
        $salary_rows = [];
        if (! empty($_GET['id'])) {
            $employee = $this->employees->find((int) $_GET['id']);
            if ($employee) {
                $salary_rows = $this->salary_history->for_employee((int) $employee['id']);
            }
        }
        $employees = $this->employees->all();
        $roles = $this->roles->all();
        $users = get_users(['number' => 200, 'orderby' => 'login', 'order' => 'ASC']);
        $suggested_hours = $this->monthly_hours_service->suggested_hours(gmdate('Y-m'));
        include ERP_OMD_PATH . 'templates/admin/employees.php';
    }

    public function render_settings()
    {
        $delete_data = (bool) get_option('erp_omd_delete_data_on_uninstall', false);
        include ERP_OMD_PATH . 'templates/admin/settings.php';
    }

    private function handle_role_save()
    {
        check_admin_referer('erp_omd_save_role');
        if (! current_user_can('erp_omd_manage_roles')) {
            wp_die(esc_html__('Brak uprawnień.', 'erp-omd'));
        }

        $id = empty($_POST['id']) ? 0 : (int) $_POST['id'];
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $slug = sanitize_title(wp_unslash($_POST['slug'] ?? ''));
        $description = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
        $status = sanitize_text_field(wp_unslash($_POST['status'] ?? 'active'));

        if ($name === '' || $slug === '') {
            $this->redirect_with_notice('erp-omd-roles', 'error', __('Nazwa i slug roli są wymagane.', 'erp-omd'));
        }

        if ($this->roles->slug_exists($slug, $id ?: null)) {
            $this->redirect_with_notice('erp-omd-roles', 'error', __('Slug roli musi być unikalny.', 'erp-omd'));
        }

        $payload = compact('name', 'slug', 'description', 'status');

        if ($id) {
            $this->roles->update($id, $payload);
            $message = __('Rola została zaktualizowana.', 'erp-omd');
        } else {
            $id = $this->roles->create($payload);
            $message = __('Rola została utworzona.', 'erp-omd');
        }

        $this->redirect_with_notice('erp-omd-roles', 'success', $message, ['id' => $id]);
    }

    private function handle_role_delete()
    {
        check_admin_referer('erp_omd_delete_role');
        if (! current_user_can('erp_omd_manage_roles')) {
            wp_die(esc_html__('Brak uprawnień.', 'erp-omd'));
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $this->roles->delete($id);
        }

        $this->redirect_with_notice('erp-omd-roles', 'success', __('Rola została usunięta.', 'erp-omd'));
    }

    private function handle_employee_save()
    {
        check_admin_referer('erp_omd_save_employee');
        if (! current_user_can('erp_omd_manage_employees')) {
            wp_die(esc_html__('Brak uprawnień.', 'erp-omd'));
        }

        $id = empty($_POST['id']) ? 0 : (int) $_POST['id'];
        $payload = [
            'user_id' => (int) ($_POST['user_id'] ?? 0),
            'default_role_id' => (int) ($_POST['default_role_id'] ?? 0),
            'account_type' => sanitize_text_field(wp_unslash($_POST['account_type'] ?? 'worker')),
            'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? 'active')),
            'role_ids' => array_map('intval', wp_unslash($_POST['role_ids'] ?? [])),
        ];

        $errors = $this->employee_service->validate_employee($payload, $id ?: null);
        if ($errors) {
            $this->redirect_with_notice('erp-omd-employees', 'error', implode(' ', $errors), $id ? ['id' => $id] : []);
        }

        if ($id) {
            $this->employees->update($id, $payload);
            $message = __('Pracownik został zaktualizowany.', 'erp-omd');
        } else {
            $id = $this->employees->create($payload);
            $message = __('Pracownik został utworzony.', 'erp-omd');
        }

        $this->sync_wp_role($payload['user_id'], $payload['account_type']);
        $this->redirect_with_notice('erp-omd-employees', 'success', $message, ['id' => $id]);
    }

    private function handle_employee_deactivate()
    {
        check_admin_referer('erp_omd_deactivate_employee');
        if (! current_user_can('erp_omd_manage_employees')) {
            wp_die(esc_html__('Brak uprawnień.', 'erp-omd'));
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $employee = $this->employees->find($id);
            if ($employee) {
                $this->employees->deactivate($id);
                $this->redirect_with_notice('erp-omd-employees', 'success', __('Pracownik został dezaktywowany.', 'erp-omd'));
            }
        }

        $this->redirect_with_notice('erp-omd-employees', 'error', __('Nie znaleziono pracownika.', 'erp-omd'));
    }

    private function handle_salary_save()
    {
        check_admin_referer('erp_omd_save_salary');
        if (! current_user_can('erp_omd_manage_salary')) {
            wp_die(esc_html__('Brak uprawnień.', 'erp-omd'));
        }

        $id = empty($_POST['salary_id']) ? 0 : (int) $_POST['salary_id'];
        $employee_id = (int) ($_POST['employee_id'] ?? 0);
        $payload = [
            'employee_id' => $employee_id,
            'monthly_salary' => (float) ($_POST['monthly_salary'] ?? 0),
            'monthly_hours' => (float) ($_POST['monthly_hours'] ?? 0),
            'valid_from' => sanitize_text_field(wp_unslash($_POST['valid_from'] ?? '')),
            'valid_to' => sanitize_text_field(wp_unslash($_POST['valid_to'] ?? '')),
        ];
        $payload = $this->employee_service->prepare_salary_payload($payload);
        $errors = $this->employee_service->validate_salary($payload, $id ?: null);

        if ($errors) {
            $this->redirect_with_notice('erp-omd-employees', 'error', implode(' ', $errors), ['id' => $employee_id]);
        }

        if ($id) {
            $this->salary_history->update($id, $payload);
            $message = __('Wpis salary history został zaktualizowany.', 'erp-omd');
        } else {
            $this->salary_history->create($payload);
            $message = __('Wpis salary history został dodany.', 'erp-omd');
        }

        $this->redirect_with_notice('erp-omd-employees', 'success', $message, ['id' => $employee_id]);
    }

    private function handle_salary_delete()
    {
        check_admin_referer('erp_omd_delete_salary');
        if (! current_user_can('erp_omd_manage_salary')) {
            wp_die(esc_html__('Brak uprawnień.', 'erp-omd'));
        }

        $salary_id = (int) ($_POST['salary_id'] ?? 0);
        $employee_id = (int) ($_POST['employee_id'] ?? 0);
        if ($salary_id) {
            $this->salary_history->delete($salary_id);
        }

        $this->redirect_with_notice('erp-omd-employees', 'success', __('Wpis salary history został usunięty.', 'erp-omd'), ['id' => $employee_id]);
    }

    private function handle_settings_save()
    {
        check_admin_referer('erp_omd_save_settings');
        if (! current_user_can('erp_omd_manage_settings')) {
            wp_die(esc_html__('Brak uprawnień.', 'erp-omd'));
        }

        update_option('erp_omd_delete_data_on_uninstall', ! empty($_POST['delete_data_on_uninstall']));
        $this->redirect_with_notice('erp-omd-settings', 'success', __('Ustawienia zostały zapisane.', 'erp-omd'));
    }

    private function redirect_with_notice($page, $type, $message, array $extra = [])
    {
        $args = array_merge(
            [
                'page' => $page,
                'erp_omd_notice_type' => $type,
                'erp_omd_notice' => rawurlencode($message),
            ],
            $extra
        );

        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    public static function render_notice()
    {
        if (empty($_GET['erp_omd_notice'])) {
            return;
        }

        $type = sanitize_html_class(wp_unslash($_GET['erp_omd_notice_type'] ?? 'success'));
        $message = sanitize_text_field(wp_unslash($_GET['erp_omd_notice']));
        printf('<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr($type), esc_html(rawurldecode($message)));
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

add_action('admin_notices', ['ERP_OMD_Admin', 'render_notice']);
