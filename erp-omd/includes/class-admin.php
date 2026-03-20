<?php

class ERP_OMD_Admin
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
    private $time_entry_service;
    private $project_financial_service;

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
        ERP_OMD_Time_Entry_Service $time_entry_service,
        ERP_OMD_Project_Financial_Service $project_financial_service
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
        $this->time_entry_service = $time_entry_service;
        $this->project_financial_service = $project_financial_service;
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
        add_submenu_page('erp-omd', __('Klienci', 'erp-omd'), __('Klienci', 'erp-omd'), 'erp_omd_manage_clients', 'erp-omd-clients', [$this, 'render_clients']);
        add_submenu_page('erp-omd', __('Kosztorysy', 'erp-omd'), __('Kosztorysy', 'erp-omd'), 'erp_omd_manage_projects', 'erp-omd-estimates', [$this, 'render_estimates']);
        add_submenu_page('erp-omd', __('Projekty', 'erp-omd'), __('Projekty', 'erp-omd'), 'erp_omd_manage_projects', 'erp-omd-projects', [$this, 'render_projects']);
        add_submenu_page('erp-omd', __('Czas pracy', 'erp-omd'), __('Czas pracy', 'erp-omd'), 'erp_omd_manage_time', 'erp-omd-time', [$this, 'render_time_entries']);
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
            case 'save_role': $this->handle_role_save(); break;
            case 'delete_role': $this->handle_role_delete(); break;
            case 'save_employee': $this->handle_employee_save(); break;
            case 'deactivate_employee': $this->handle_employee_deactivate(); break;
            case 'save_salary': $this->handle_salary_save(); break;
            case 'delete_salary': $this->handle_salary_delete(); break;
            case 'save_client': $this->handle_client_save(); break;
            case 'deactivate_client': $this->handle_client_deactivate(); break;
            case 'save_client_rate': $this->handle_client_rate_save(); break;
            case 'delete_client_rate': $this->handle_client_rate_delete(); break;
            case 'save_estimate': $this->handle_estimate_save(); break;
            case 'delete_estimate': $this->handle_estimate_delete(); break;
            case 'save_estimate_item': $this->handle_estimate_item_save(); break;
            case 'delete_estimate_item': $this->handle_estimate_item_delete(); break;
            case 'accept_estimate': $this->handle_estimate_accept(); break;
            case 'save_project': $this->handle_project_save(); break;
            case 'deactivate_project': $this->handle_project_deactivate(); break;
            case 'add_project_note': $this->handle_project_note_add(); break;
            case 'save_project_rate': $this->handle_project_rate_save(); break;
            case 'delete_project_rate': $this->handle_project_rate_delete(); break;
            case 'save_project_cost': $this->handle_project_cost_save(); break;
            case 'delete_project_cost': $this->handle_project_cost_delete(); break;
            case 'save_time_entry': $this->handle_time_entry_save(); break;
            case 'change_time_status': $this->handle_time_status_change(); break;
            case 'delete_time_entry': $this->handle_time_entry_delete(); break;
            case 'bulk_time_entries': $this->handle_time_entries_bulk_action(); break;
            case 'save_settings': $this->handle_settings_save(); break;
        }
    }

    public function render_dashboard()
    {
        $employees = $this->employees->all();
        $roles = $this->roles->all();
        $clients = $this->clients->all();
        $projects = $this->projects->all();
        include ERP_OMD_PATH . 'templates/admin/dashboard.php';
    }

    public function render_roles()
    {
        $role = ! empty($_GET['id']) ? $this->roles->find((int) $_GET['id']) : null;
        $roles = $this->roles->all();
        include ERP_OMD_PATH . 'templates/admin/roles.php';
    }

    public function render_employees()
    {
        $employee = null;
        $salary_rows = [];
        $reporting_month = current_time('Y-m');
        $reporting_month_label = current_time('m.Y');
        if (! empty($_GET['id'])) {
            $employee = $this->employees->find((int) $_GET['id']);
            if ($employee) {
                $salary_rows = $this->salary_history->for_employee((int) $employee['id']);
            }
        }
        $monthly_metrics = $this->build_monthly_performance_metrics($reporting_month);
        $employees = $this->employees->all();
        foreach ($employees as &$employee_row) {
            $current_salary_row = $this->resolve_current_salary_row((int) $employee_row['id']);
            $employee_row['current_monthly_salary'] = (float) ($current_salary_row['monthly_salary'] ?? 0);
            $employee_row['current_hourly_cost'] = (float) ($current_salary_row['hourly_cost'] ?? 0);
            $employee_monthly_metrics = $monthly_metrics['employees'][(int) $employee_row['id']] ?? [];
            $employee_row['reported_hours'] = (float) ($employee_monthly_metrics['reported_hours'] ?? 0);
            $employee_row['produced_profit'] = (float) ($employee_monthly_metrics['produced_profit'] ?? 0);
            $employee_row['employee_profit'] = (float) ($employee_monthly_metrics['employee_profit'] ?? 0);
            $employee_row['target_monthly_hours'] = isset($current_salary_row['monthly_hours'])
                ? round((float) $current_salary_row['monthly_hours'] - $employee_row['reported_hours'], 2)
                : null;
        }
        unset($employee_row);
        $roles = $this->roles->all();
        $users = get_users(['number' => 200, 'orderby' => 'login', 'order' => 'ASC']);
        $suggested_hours = $this->monthly_hours_service->suggested_hours(gmdate('Y-m'));
        include ERP_OMD_PATH . 'templates/admin/employees.php';
    }

    public function render_clients()
    {
        $client = null;
        $client_rates = [];
        $editing_client_rate = null;
        if (! empty($_GET['id'])) {
            $client = $this->clients->find((int) $_GET['id']);
            if ($client) {
                $client_rates = $this->client_rates->for_client((int) $client['id']);
                if (! empty($_GET['rate_id'])) {
                    $editing_client_rate = $this->client_rates->find((int) $_GET['rate_id']);
                    if (! $editing_client_rate || (int) ($editing_client_rate['client_id'] ?? 0) !== (int) $client['id']) {
                        $editing_client_rate = null;
                    }
                }
            }
        }
        $client_profit_totals = $this->build_client_profit_totals();
        $clients = $this->clients->all();
        foreach ($clients as &$client_row) {
            $client_row['total_profit'] = (float) ($client_profit_totals[(int) $client_row['id']] ?? 0);
        }
        unset($client_row);
        $roles = $this->roles->all();
        $employees_for_select = $this->employees->all();
        include ERP_OMD_PATH . 'templates/admin/clients.php';
    }

    public function render_estimates()
    {
        $estimate = null;
        $estimate_items = [];
        $estimate_totals = ['net' => 0.0, 'tax' => 0.0, 'gross' => 0.0, 'internal_cost' => 0.0];
        $editing_estimate_item = null;
        $linked_project = null;
        if (! empty($_GET['id'])) {
            $estimate = $this->estimates->find((int) $_GET['id']);
            if ($estimate) {
                $estimate_items = $this->estimate_items->for_estimate((int) $estimate['id']);
                $estimate_totals = $this->estimate_service->calculate_totals($estimate_items);
                $linked_project = $this->projects->find_by_estimate_id((int) $estimate['id']);
                if (! empty($_GET['item_id'])) {
                    $editing_estimate_item = $this->estimate_items->find((int) $_GET['item_id']);
                    if (! $editing_estimate_item || (int) ($editing_estimate_item['estimate_id'] ?? 0) !== (int) $estimate['id']) {
                        $editing_estimate_item = null;
                    }
                }
            }
        }
        $estimates = $this->estimates->all();
        foreach ($estimates as &$estimate_row) {
            $estimate_row_items = $this->estimate_items->for_estimate((int) $estimate_row['id']);
            $estimate_row_totals = $this->estimate_service->calculate_totals($estimate_row_items);
            $estimate_row['total_net'] = $estimate_row_totals['net'];
            $estimate_row['total_gross'] = $estimate_row_totals['gross'];
            $estimate_row['total_internal_cost'] = $estimate_row_totals['internal_cost'];
        }
        unset($estimate_row);
        $clients = $this->clients->all();
        include ERP_OMD_PATH . 'templates/admin/estimates.php';
    }

    public function render_projects()
    {
        $project = null;
        $project_notes = [];
        $project_rates = [];
        $project_cost_rows = [];
        $project_financial = null;
        $project_financials_by_project = [];
        if (! empty($_GET['id'])) {
            $project = $this->projects->find((int) $_GET['id']);
            if ($project) {
                $project_notes = $this->project_notes->for_project((int) $project['id']);
                $project_rates = $this->project_rates->for_project((int) $project['id']);
                $project_cost_rows = $this->project_costs->for_project((int) $project['id']);
                $project_financial = $this->project_financial_service->rebuild_for_project((int) $project['id']);
                $project_financials_by_project[(int) $project['id']] = $project_financial;
            }
        }
        $projects = $this->projects->all();
        $clients = $this->clients->all();
        $employees_for_select = $this->employees->all();
        $roles = $this->roles->all();
        $project_financials_by_project = array_replace(
            $this->project_financial_service->get_project_financials(wp_list_pluck($projects, 'id')),
            $project_financials_by_project
        );
        include ERP_OMD_PATH . 'templates/admin/projects.php';
    }

    public function render_time_entries()
    {
        $current_user = wp_get_current_user();
        $current_employee = $this->employees->find_by_user_id($current_user->ID);
        $can_select_any_employee = current_user_can('administrator') || current_user_can('erp_omd_approve_time');
        $can_edit_any_entry = current_user_can('administrator');
        $can_delete_entries = $this->time_entry_service->can_delete_entry($current_user);
        $filters = [
            'employee_id' => $_GET['employee_id'] ?? '',
            'project_id' => $_GET['project_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'entry_date' => $_GET['entry_date'] ?? '',
        ];
        if (! $can_select_any_employee && $current_employee) {
            $filters['employee_id'] = (string) $current_employee['id'];
        }

        $entry = ! empty($_GET['id']) ? $this->time_entries->find((int) $_GET['id']) : null;
        $can_edit_selected_entry = $entry ? $this->time_entry_service->can_edit_entry($entry, $current_user) : true;
        if ($entry && ! $can_edit_selected_entry) {
            $entry = null;
        }

        $employees_for_select = $this->employees->all();
        $projects_for_time = $this->projects->all();
        $roles = $this->roles->all();
        $time_entries = $this->time_entry_service->filter_visible_entries($this->time_entries->all($filters), $current_user);
        $selected_employee_id = $entry['employee_id'] ?? ($current_employee['id'] ?? 0);
        $can_set_status = current_user_can('administrator') || current_user_can('erp_omd_approve_time');
        include ERP_OMD_PATH . 'templates/admin/time-entries.php';
    }

    public function render_settings()
    {
        $delete_data = (bool) get_option('erp_omd_delete_data_on_uninstall', false);
        include ERP_OMD_PATH . 'templates/admin/settings.php';
    }

    private function handle_role_save() { /* retained */
        check_admin_referer('erp_omd_save_role');
        $this->require_capability('erp_omd_manage_roles');
        $id = empty($_POST['id']) ? 0 : (int) $_POST['id'];
        $payload = ['name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')), 'slug' => sanitize_title(wp_unslash($_POST['slug'] ?? '')), 'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')), 'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? 'active'))];
        if ($payload['name'] === '' || $payload['slug'] === '') { $this->redirect_with_notice('erp-omd-roles', 'error', __('Nazwa i slug roli są wymagane.', 'erp-omd')); }
        if ($this->roles->slug_exists($payload['slug'], $id ?: null)) { $this->redirect_with_notice('erp-omd-roles', 'error', __('Slug roli musi być unikalny.', 'erp-omd')); }
        if ($id) { $this->roles->update($id, $payload); $message = __('Rola została zaktualizowana.', 'erp-omd'); } else { $id = $this->roles->create($payload); $message = __('Rola została utworzona.', 'erp-omd'); }
        $this->redirect_with_notice('erp-omd-roles', 'success', $message, ['id' => $id]);
    }

    private function handle_role_delete()
    {
        check_admin_referer('erp_omd_delete_role');
        $this->require_capability('erp_omd_manage_roles');
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) { $this->roles->delete($id); }
        $this->redirect_with_notice('erp-omd-roles', 'success', __('Rola została usunięta.', 'erp-omd'));
    }

    private function handle_employee_save()
    {
        check_admin_referer('erp_omd_save_employee');
        $this->require_capability('erp_omd_manage_employees');
        $id = empty($_POST['id']) ? 0 : (int) $_POST['id'];
        $payload = ['user_id' => (int) ($_POST['user_id'] ?? 0), 'default_role_id' => (int) ($_POST['default_role_id'] ?? 0), 'account_type' => sanitize_text_field(wp_unslash($_POST['account_type'] ?? 'worker')), 'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? 'active')), 'role_ids' => array_map('intval', wp_unslash($_POST['role_ids'] ?? []))];
        $errors = $this->employee_service->validate_employee($payload, $id ?: null);
        if ($errors) { $this->redirect_with_notice('erp-omd-employees', 'error', implode(' ', $errors), $id ? ['id' => $id] : []); }
        if ($id) { $this->employees->update($id, $payload); $message = __('Pracownik został zaktualizowany.', 'erp-omd'); } else { $id = $this->employees->create($payload); $message = __('Pracownik został utworzony.', 'erp-omd'); }
        $this->sync_wp_role($payload['user_id'], $payload['account_type']);
        $this->redirect_with_notice('erp-omd-employees', 'success', $message, ['id' => $id]);
    }

    private function handle_employee_deactivate()
    {
        check_admin_referer('erp_omd_deactivate_employee');
        $this->require_capability('erp_omd_manage_employees');
        $id = (int) ($_POST['id'] ?? 0);
        if ($id && $this->employees->find($id)) { $this->employees->deactivate($id); $this->redirect_with_notice('erp-omd-employees', 'success', __('Pracownik został dezaktywowany.', 'erp-omd')); }
        $this->redirect_with_notice('erp-omd-employees', 'error', __('Nie znaleziono pracownika.', 'erp-omd'));
    }

    private function handle_salary_save()
    {
        check_admin_referer('erp_omd_save_salary');
        $this->require_capability('erp_omd_manage_salary');
        $id = empty($_POST['salary_id']) ? 0 : (int) $_POST['salary_id'];
        $employee_id = (int) ($_POST['employee_id'] ?? 0);
        $payload = ['employee_id' => $employee_id, 'monthly_salary' => (float) ($_POST['monthly_salary'] ?? 0), 'monthly_hours' => (float) ($_POST['monthly_hours'] ?? 0), 'valid_from' => sanitize_text_field(wp_unslash($_POST['valid_from'] ?? '')), 'valid_to' => sanitize_text_field(wp_unslash($_POST['valid_to'] ?? ''))];
        $payload = $this->employee_service->prepare_salary_payload($payload);
        $errors = $this->employee_service->validate_salary($payload, $id ?: null);
        if ($errors) { $this->redirect_with_notice('erp-omd-employees', 'error', implode(' ', $errors), ['id' => $employee_id]); }
        if ($id) { $this->salary_history->update($id, $payload); $message = __('Wpis salary history został zaktualizowany.', 'erp-omd'); } else { $this->salary_history->create($payload); $message = __('Wpis salary history został dodany.', 'erp-omd'); }
        $this->redirect_with_notice('erp-omd-employees', 'success', $message, ['id' => $employee_id]);
    }

    private function handle_salary_delete()
    {
        check_admin_referer('erp_omd_delete_salary');
        $this->require_capability('erp_omd_manage_salary');
        $salary_id = (int) ($_POST['salary_id'] ?? 0);
        $employee_id = (int) ($_POST['employee_id'] ?? 0);
        if ($salary_id) { $this->salary_history->delete($salary_id); }
        $this->redirect_with_notice('erp-omd-employees', 'success', __('Wpis salary history został usunięty.', 'erp-omd'), ['id' => $employee_id]);
    }

    private function handle_client_save()
    {
        check_admin_referer('erp_omd_save_client');
        $this->require_capability('erp_omd_manage_clients');
        $id = empty($_POST['id']) ? 0 : (int) $_POST['id'];
        $payload = ['name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')), 'company' => sanitize_text_field(wp_unslash($_POST['company'] ?? '')), 'nip' => sanitize_text_field(wp_unslash($_POST['nip'] ?? '')), 'email' => sanitize_email(wp_unslash($_POST['email'] ?? '')), 'phone' => sanitize_text_field(wp_unslash($_POST['phone'] ?? '')), 'contact_person_name' => sanitize_text_field(wp_unslash($_POST['contact_person_name'] ?? '')), 'contact_person_email' => sanitize_email(wp_unslash($_POST['contact_person_email'] ?? '')), 'contact_person_phone' => sanitize_text_field(wp_unslash($_POST['contact_person_phone'] ?? '')), 'city' => sanitize_text_field(wp_unslash($_POST['city'] ?? '')), 'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? 'active')), 'account_manager_id' => (int) ($_POST['account_manager_id'] ?? 0)];
        $errors = $this->client_project_service->validate_client($payload, $id ?: null);
        if ($errors) { $this->redirect_with_notice('erp-omd-clients', 'error', implode(' ', $errors), $id ? ['id' => $id] : []); }
        if ($id) { $this->clients->update($id, $payload); $message = __('Klient został zaktualizowany.', 'erp-omd'); } else { $id = $this->clients->create($payload); $message = __('Klient został utworzony.', 'erp-omd'); }
        $this->redirect_with_notice('erp-omd-clients', 'success', $message, ['id' => $id]);
    }

    private function handle_client_deactivate()
    {
        check_admin_referer('erp_omd_deactivate_client');
        $this->require_capability('erp_omd_manage_clients');
        $id = (int) ($_POST['id'] ?? 0);
        if ($id && $this->clients->find($id)) { $this->clients->deactivate($id); $this->redirect_with_notice('erp-omd-clients', 'success', __('Klient został dezaktywowany.', 'erp-omd')); }
        $this->redirect_with_notice('erp-omd-clients', 'error', __('Nie znaleziono klienta.', 'erp-omd'));
    }

    private function handle_client_rate_save()
    {
        check_admin_referer('erp_omd_save_client_rate');
        $this->require_capability('erp_omd_manage_clients');
        $rate_id = (int) ($_POST['rate_id'] ?? 0);
        $client_id = (int) ($_POST['client_id'] ?? 0);
        $role_id = (int) ($_POST['role_id'] ?? 0);
        $rate = (float) ($_POST['rate'] ?? 0);
        $errors = $this->client_project_service->validate_client_rate($client_id, $role_id, $rate);
        if ($errors) { $this->redirect_with_notice('erp-omd-clients', 'error', implode(' ', $errors), ['id' => $client_id]); }

        if ($rate_id > 0) {
            $existing_rate = $this->client_rates->find($rate_id);
            if (! $existing_rate || (int) ($existing_rate['client_id'] ?? 0) !== $client_id) {
                $this->redirect_with_notice('erp-omd-clients', 'error', __('Nie znaleziono stawki klienta do edycji.', 'erp-omd'), ['id' => $client_id]);
            }

            $this->client_rates->update($rate_id, $role_id, $rate);
        } else {
            $this->client_rates->upsert($client_id, $role_id, $rate);
        }

        $this->redirect_with_notice('erp-omd-clients', 'success', __('Stawka klienta została zapisana.', 'erp-omd'), ['id' => $client_id]);
    }

    private function handle_client_rate_delete()
    {
        check_admin_referer('erp_omd_delete_client_rate');
        $this->require_capability('erp_omd_manage_clients');
        $id = (int) ($_POST['id'] ?? 0);
        $client_id = (int) ($_POST['client_id'] ?? 0);
        if ($id) { $this->client_rates->delete($id); }
        $this->redirect_with_notice('erp-omd-clients', 'success', __('Stawka klienta została usunięta.', 'erp-omd'), ['id' => $client_id]);
    }

    private function handle_estimate_save()
    {
        check_admin_referer('erp_omd_save_estimate');
        $this->require_capability('erp_omd_manage_projects');
        $id = empty($_POST['id']) ? 0 : (int) $_POST['id'];
        $existing = $id ? $this->estimates->find($id) : null;
        $payload = [
            'client_id' => (int) ($_POST['client_id'] ?? 0),
            'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? 'wstepny')),
            'accepted_by_user_id' => (int) ($existing['accepted_by_user_id'] ?? 0),
            'accepted_at' => $existing['accepted_at'] ?? null,
        ];
        $errors = $this->estimate_service->validate_estimate($payload, $existing);
        if ($errors) {
            $this->redirect_with_notice('erp-omd-estimates', 'error', implode(' ', $errors), $id ? ['id' => $id] : []);
        }
        if ($id) {
            $this->estimates->update($id, $payload);
            $message = __('Kosztorys został zaktualizowany.', 'erp-omd');
        } else {
            $id = $this->estimates->create($payload);
            $message = __('Kosztorys został utworzony.', 'erp-omd');
        }
        $this->redirect_with_notice('erp-omd-estimates', 'success', $message, ['id' => $id]);
    }

    private function handle_estimate_delete()
    {
        check_admin_referer('erp_omd_delete_estimate');
        $this->require_capability('erp_omd_manage_projects');
        $id = (int) ($_POST['id'] ?? 0);
        $estimate = $id ? $this->estimates->find($id) : null;
        if (! $estimate) {
            $this->redirect_with_notice('erp-omd-estimates', 'error', __('Nie znaleziono kosztorysu.', 'erp-omd'));
        }
        if (($estimate['status'] ?? '') === 'zaakceptowany') {
            $this->redirect_with_notice('erp-omd-estimates', 'error', __('Zaakceptowany kosztorys nie może zostać usunięty.', 'erp-omd'), ['id' => $id]);
        }
        $this->estimates->delete($id);
        $this->redirect_with_notice('erp-omd-estimates', 'success', __('Kosztorys został usunięty.', 'erp-omd'));
    }

    private function handle_estimate_item_save()
    {
        check_admin_referer('erp_omd_save_estimate_item');
        $this->require_capability('erp_omd_manage_projects');
        $estimate_id = (int) ($_POST['estimate_id'] ?? 0);
        $item_id = empty($_POST['item_id']) ? 0 : (int) $_POST['item_id'];
        $estimate = $this->estimates->find($estimate_id);
        $existing_item = $item_id ? $this->estimate_items->find($item_id) : null;
        $payload = [
            'estimate_id' => $estimate_id,
            'name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
            'qty' => (float) ($_POST['qty'] ?? 0),
            'price' => (float) ($_POST['price'] ?? 0),
            'cost_internal' => (float) ($_POST['cost_internal'] ?? 0),
            'comment' => sanitize_textarea_field(wp_unslash($_POST['comment'] ?? '')),
        ];
        $errors = $this->estimate_service->validate_item($payload, $estimate, $existing_item);
        if ($errors) {
            $this->redirect_with_notice('erp-omd-estimates', 'error', implode(' ', $errors), ['id' => $estimate_id]);
        }
        if ($item_id) {
            $this->estimate_items->update($item_id, $payload);
            $message = __('Pozycja kosztorysu została zaktualizowana.', 'erp-omd');
        } else {
            $this->estimate_items->create($payload);
            $message = __('Pozycja kosztorysu została dodana.', 'erp-omd');
        }
        $this->redirect_with_notice('erp-omd-estimates', 'success', $message, ['id' => $estimate_id]);
    }

    private function handle_estimate_item_delete()
    {
        check_admin_referer('erp_omd_delete_estimate_item');
        $this->require_capability('erp_omd_manage_projects');
        $item_id = (int) ($_POST['item_id'] ?? 0);
        $estimate_id = (int) ($_POST['estimate_id'] ?? 0);
        $item = $item_id ? $this->estimate_items->find($item_id) : null;
        $estimate = $estimate_id ? $this->estimates->find($estimate_id) : null;
        if (! $item || ! $estimate || (int) ($item['estimate_id'] ?? 0) !== $estimate_id) {
            $this->redirect_with_notice('erp-omd-estimates', 'error', __('Nie znaleziono pozycji kosztorysu.', 'erp-omd'), ['id' => $estimate_id]);
        }
        if (($estimate['status'] ?? '') === 'zaakceptowany') {
            $this->redirect_with_notice('erp-omd-estimates', 'error', __('Nie można usuwać pozycji z zaakceptowanego kosztorysu.', 'erp-omd'), ['id' => $estimate_id]);
        }
        $this->estimate_items->delete($item_id);
        $this->redirect_with_notice('erp-omd-estimates', 'success', __('Pozycja kosztorysu została usunięta.', 'erp-omd'), ['id' => $estimate_id]);
    }

    private function handle_estimate_accept()
    {
        check_admin_referer('erp_omd_accept_estimate');
        $this->require_capability('erp_omd_manage_projects');
        $estimate_id = (int) ($_POST['estimate_id'] ?? 0);
        $result = $this->estimate_service->accept($estimate_id);
        if ($result instanceof WP_Error) {
            $this->redirect_with_notice('erp-omd-estimates', 'error', $result->get_error_message(), ['id' => $estimate_id]);
        }
        $this->redirect_with_notice('erp-omd-estimates', 'success', __('Kosztorys został zaakceptowany i powiązany z projektem.', 'erp-omd'), ['id' => $estimate_id]);
    }

    private function handle_project_save()
    {
        check_admin_referer('erp_omd_save_project');
        $this->require_capability('erp_omd_manage_projects');
        $id = empty($_POST['id']) ? 0 : (int) $_POST['id'];
        $payload = ['client_id' => (int) ($_POST['client_id'] ?? 0), 'name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')), 'billing_type' => sanitize_text_field(wp_unslash($_POST['billing_type'] ?? 'time_material')), 'budget' => (float) ($_POST['budget'] ?? 0), 'retainer_monthly_fee' => (float) ($_POST['retainer_monthly_fee'] ?? 0), 'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? 'do_rozpoczecia')), 'start_date' => sanitize_text_field(wp_unslash($_POST['start_date'] ?? '')), 'end_date' => sanitize_text_field(wp_unslash($_POST['end_date'] ?? '')), 'manager_id' => (int) ($_POST['manager_id'] ?? 0), 'estimate_id' => (int) ($_POST['estimate_id'] ?? 0), 'brief' => sanitize_textarea_field(wp_unslash($_POST['brief'] ?? ''))];
        $errors = $this->client_project_service->validate_project($payload);
        if ($errors) { $this->redirect_with_notice('erp-omd-projects', 'error', implode(' ', $errors), $id ? ['id' => $id] : []); }
        if ($id) { $this->projects->update($id, $payload); $message = __('Projekt został zaktualizowany.', 'erp-omd'); } else { $id = $this->projects->create($payload); $message = __('Projekt został utworzony.', 'erp-omd'); }
        $this->project_financial_service->rebuild_for_project($id);
        $this->redirect_with_notice('erp-omd-projects', 'success', $message, ['id' => $id]);
    }

    private function handle_project_deactivate()
    {
        check_admin_referer('erp_omd_deactivate_project');
        $this->require_capability('erp_omd_manage_projects');
        $id = (int) ($_POST['id'] ?? 0);
        if ($id && $this->projects->find($id)) { $this->projects->deactivate($id); $this->redirect_with_notice('erp-omd-projects', 'success', __('Projekt został dezaktywowany.', 'erp-omd')); }
        $this->redirect_with_notice('erp-omd-projects', 'error', __('Nie znaleziono projektu.', 'erp-omd'));
    }

    private function handle_project_note_add()
    {
        check_admin_referer('erp_omd_add_project_note');
        $this->require_capability('erp_omd_manage_projects');
        $project_id = (int) ($_POST['project_id'] ?? 0);
        $note = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));
        if (! $project_id || $note === '') { $this->redirect_with_notice('erp-omd-projects', 'error', __('Projekt i treść uwagi są wymagane.', 'erp-omd')); }
        $this->project_notes->create($project_id, $note, get_current_user_id());
        $this->redirect_with_notice('erp-omd-projects', 'success', __('Uwaga klienta została dodana.', 'erp-omd'), ['id' => $project_id]);
    }

    private function handle_project_rate_save()
    {
        check_admin_referer('erp_omd_save_project_rate');
        $this->require_capability('erp_omd_manage_projects');
        $project_id = (int) ($_POST['project_id'] ?? 0);
        $role_id = (int) ($_POST['role_id'] ?? 0);
        $rate = (float) ($_POST['rate'] ?? 0);
        if (! $this->projects->find($project_id) || ! $this->roles->find($role_id) || $rate < 0) {
            $this->redirect_with_notice('erp-omd-projects', 'error', __('Niepoprawna stawka projektowa.', 'erp-omd'), ['id' => $project_id]);
        }
        $this->project_rates->upsert($project_id, $role_id, $rate);
        $this->redirect_with_notice('erp-omd-projects', 'success', __('Stawka projektowa została zapisana.', 'erp-omd'), ['id' => $project_id]);
    }

    private function handle_project_rate_delete()
    {
        check_admin_referer('erp_omd_delete_project_rate');
        $this->require_capability('erp_omd_manage_projects');
        $id = (int) ($_POST['id'] ?? 0);
        $project_id = (int) ($_POST['project_id'] ?? 0);
        if ($id) { $this->project_rates->delete($id); }
        $this->redirect_with_notice('erp-omd-projects', 'success', __('Stawka projektowa została usunięta.', 'erp-omd'), ['id' => $project_id]);
    }

    private function handle_project_cost_save()
    {
        check_admin_referer('erp_omd_save_project_cost');
        $this->require_capability('erp_omd_manage_projects');
        $id = empty($_POST['project_cost_id']) ? 0 : (int) $_POST['project_cost_id'];
        $project_id = (int) ($_POST['project_id'] ?? 0);
        $payload = [
            'project_id' => $project_id,
            'amount' => (float) ($_POST['amount'] ?? 0),
            'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
            'cost_date' => sanitize_text_field(wp_unslash($_POST['cost_date'] ?? '')),
            'created_by_user_id' => get_current_user_id(),
        ];
        $errors = $this->project_financial_service->validate_project_cost($payload);
        if ($errors) {
            $this->redirect_with_notice('erp-omd-projects', 'error', implode(' ', $errors), ['id' => $project_id]);
        }
        if ($id) {
            $this->project_costs->update($id, $payload);
            $message = __('Koszt projektu został zaktualizowany.', 'erp-omd');
        } else {
            $this->project_costs->create($payload);
            $message = __('Koszt projektu został dodany.', 'erp-omd');
        }
        $this->project_financial_service->rebuild_for_project($project_id);
        $this->redirect_with_notice('erp-omd-projects', 'success', $message, ['id' => $project_id]);
    }

    private function handle_project_cost_delete()
    {
        check_admin_referer('erp_omd_delete_project_cost');
        $this->require_capability('erp_omd_manage_projects');
        $id = (int) ($_POST['project_cost_id'] ?? 0);
        $project_id = (int) ($_POST['project_id'] ?? 0);
        if ($id) {
            $this->project_costs->delete($id);
            $this->project_financial_service->rebuild_for_project($project_id);
        }
        $this->redirect_with_notice('erp-omd-projects', 'success', __('Koszt projektu został usunięty.', 'erp-omd'), ['id' => $project_id]);
    }

    private function handle_time_entry_save()
    {
        check_admin_referer('erp_omd_save_time_entry');
        $this->require_capability('erp_omd_manage_time');
        $id = empty($_POST['id']) ? 0 : (int) $_POST['id'];
        $current_user = wp_get_current_user();
        $current_employee = $this->employees->find_by_user_id($current_user->ID);
        $employee_id = (int) ($_POST['employee_id'] ?? 0);
        if (! current_user_can('administrator') && ! current_user_can('erp_omd_approve_time') && $current_employee) {
            $employee_id = (int) $current_employee['id'];
        }
        $status = sanitize_text_field(wp_unslash($_POST['status'] ?? 'submitted'));
        if (! current_user_can('administrator')) {
            $status = $id && current_user_can('erp_omd_approve_time') ? $status : 'submitted';
        }
        $payload = ['employee_id' => $employee_id, 'project_id' => (int) ($_POST['project_id'] ?? 0), 'role_id' => (int) ($_POST['role_id'] ?? 0), 'hours' => (float) ($_POST['hours'] ?? 0), 'entry_date' => sanitize_text_field(wp_unslash($_POST['entry_date'] ?? '')), 'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')), 'status' => $status, 'created_by_user_id' => (int) $current_user->ID, 'approved_by_user_id' => in_array($status, ['approved', 'rejected'], true) ? (int) $current_user->ID : 0, 'approved_at' => in_array($status, ['approved', 'rejected'], true) ? current_time('mysql') : null];
        if ($id) {
            $existing = $this->time_entries->find($id);
            if (! $existing || ! $this->time_entry_service->can_edit_entry($existing, $current_user)) {
                $this->redirect_with_notice('erp-omd-time', 'error', __('Tylko administrator może edytować istniejący wpis czasu.', 'erp-omd'));
            }
        }
        $payload = $this->time_entry_service->prepare($payload);
        $errors = $this->time_entry_service->validate($payload, $id ?: null);
        if ($errors) { $this->redirect_with_notice('erp-omd-time', 'error', implode(' ', $errors), $id ? ['id' => $id] : []); }
        if ($id) { $this->time_entries->update($id, $payload); $message = __('Wpis czasu został zaktualizowany.', 'erp-omd'); } else { $id = $this->time_entries->create($payload); $message = __('Wpis czasu został dodany.', 'erp-omd'); }
        $this->project_financial_service->rebuild_for_project((int) $payload['project_id']);
        $this->redirect_with_notice('erp-omd-time', 'success', $message, $id && ! empty($_POST['id']) ? ['id' => $id] : []);
    }

    private function handle_time_status_change()
    {
        check_admin_referer('erp_omd_change_time_status');
        $current_user = wp_get_current_user();
        $id = (int) ($_POST['id'] ?? 0);
        $status = sanitize_text_field(wp_unslash($_POST['status'] ?? 'submitted'));
        $entry = $this->time_entries->find($id);
        if (! $entry) {
            $this->redirect_with_notice('erp-omd-time', 'error', __('Nie znaleziono wpisu czasu.', 'erp-omd'));
        }
        if (! in_array($status, ['submitted', 'approved', 'rejected'], true)) {
            $this->redirect_with_notice('erp-omd-time', 'error', __('Niepoprawny status wpisu czasu.', 'erp-omd'));
        }
        if (! $this->time_entry_service->can_approve_entry($entry, $current_user)) {
            wp_die(esc_html__('Akceptacja wpisu czasu jest dostępna tylko dla administratora lub managera przypisanego do projektu.', 'erp-omd'));
        }
        $payload = array_merge($entry, ['status' => $status, 'approved_by_user_id' => (int) $current_user->ID, 'approved_at' => current_time('mysql')]);
        $this->time_entries->update($id, $payload);
        $this->project_financial_service->rebuild_for_project((int) $entry['project_id']);
        $this->redirect_with_notice('erp-omd-time', 'success', __('Status wpisu czasu został zmieniony.', 'erp-omd'), ['id' => $id]);
    }

    private function handle_time_entry_delete()
    {
        check_admin_referer('erp_omd_delete_time_entry');
        $current_user = wp_get_current_user();
        if (! $this->time_entry_service->can_delete_entry($current_user)) {
            wp_die(esc_html__('Usuwanie wpisów czasu jest dostępne tylko dla administratora.', 'erp-omd'));
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $entry = $this->time_entries->find($id);
            $this->time_entries->delete($id);
            if ($entry) {
                $this->project_financial_service->rebuild_for_project((int) $entry['project_id']);
            }
        }
        $this->redirect_with_notice('erp-omd-time', 'success', __('Wpis czasu został usunięty.', 'erp-omd'));
    }

    private function handle_time_entries_bulk_action()
    {
        check_admin_referer('erp_omd_bulk_time_entries');
        $current_user = wp_get_current_user();
        $bulk_action = sanitize_text_field(wp_unslash($_POST['bulk_action'] ?? ''));
        $time_entry_ids = array_values(array_filter(array_map('intval', wp_unslash($_POST['time_entry_ids'] ?? []))));

        if ($bulk_action === '' || empty($time_entry_ids)) {
            $this->redirect_with_notice('erp-omd-time', 'error', __('Wybierz akcję masową i co najmniej jeden wpis czasu.', 'erp-omd'));
        }

        $affected_project_ids = [];
        $processed_count = 0;

        if ($bulk_action === 'delete') {
            if (! $this->time_entry_service->can_delete_entry($current_user)) {
                wp_die(esc_html__('Usuwanie wpisów czasu jest dostępne tylko dla administratora.', 'erp-omd'));
            }

            foreach ($time_entry_ids as $time_entry_id) {
                $entry = $this->time_entries->find($time_entry_id);
                if (! $entry) {
                    continue;
                }

                $this->time_entries->delete($time_entry_id);
                $affected_project_ids[] = (int) $entry['project_id'];
                $processed_count++;
            }

            $message = __('Wybrane wpisy czasu zostały usunięte.', 'erp-omd');
        } else {
            if (! in_array($bulk_action, ['submitted', 'approved', 'rejected'], true)) {
                $this->redirect_with_notice('erp-omd-time', 'error', __('Niepoprawna akcja masowa dla wpisów czasu.', 'erp-omd'));
            }

            foreach ($time_entry_ids as $time_entry_id) {
                $entry = $this->time_entries->find($time_entry_id);
                if (! $entry) {
                    continue;
                }

                if (! $this->time_entry_service->can_approve_entry($entry, $current_user)) {
                    continue;
                }

                $payload = array_merge(
                    $entry,
                    [
                        'status' => $bulk_action,
                        'approved_by_user_id' => in_array($bulk_action, ['approved', 'rejected'], true) ? (int) $current_user->ID : 0,
                        'approved_at' => in_array($bulk_action, ['approved', 'rejected'], true) ? current_time('mysql') : null,
                    ]
                );
                $this->time_entries->update($time_entry_id, $payload);
                $affected_project_ids[] = (int) $entry['project_id'];
                $processed_count++;
            }

            $message = __('Status wybranych wpisów czasu został zmieniony.', 'erp-omd');
        }

        foreach (array_values(array_unique($affected_project_ids)) as $project_id) {
            $this->project_financial_service->rebuild_for_project($project_id);
        }

        if ($processed_count === 0) {
            $this->redirect_with_notice('erp-omd-time', 'error', __('Nie udało się przetworzyć wybranych wpisów czasu.', 'erp-omd'));
        }

        $this->redirect_with_notice('erp-omd-time', 'success', $message);
    }

    private function handle_settings_save()
    {
        check_admin_referer('erp_omd_save_settings');
        $this->require_capability('erp_omd_manage_settings');
        update_option('erp_omd_delete_data_on_uninstall', ! empty($_POST['delete_data_on_uninstall']));
        $this->redirect_with_notice('erp-omd-settings', 'success', __('Ustawienia zostały zapisane.', 'erp-omd'));
    }

    private function require_capability($capability)
    {
        if (! current_user_can($capability)) { wp_die(esc_html__('Brak uprawnień.', 'erp-omd')); }
    }

    private function resolve_current_salary_row($employee_id)
    {
        $today = current_time('Y-m-d');
        $salary_rows = $this->salary_history->for_employee($employee_id);

        foreach ($salary_rows as $salary_row) {
            $valid_from = $salary_row['valid_from'] ?? '';
            $valid_to = $salary_row['valid_to'] ?: '9999-12-31';

            if ($valid_from !== '' && $today >= $valid_from && $today <= $valid_to) {
                return $salary_row;
            }
        }

        return null;
    }

    private function build_monthly_performance_metrics($reporting_month)
    {
        $projects = $this->projects->all();
        $project_index = [];
        $project_metrics = [];
        $employee_metrics = [];
        $employee_project_hours = [];

        foreach ($projects as $project_row) {
            $project_id = (int) $project_row['id'];
            $project_index[$project_id] = $project_row;
            $project_metrics[$project_id] = [
                'hours' => 0.0,
                'revenue' => 0.0,
                'cost' => 0.0,
                'direct_cost' => 0.0,
                'profit' => 0.0,
            ];
        }

        $time_entries = $this->time_entries->all();
        foreach ($time_entries as $time_entry) {
            $entry_date = (string) ($time_entry['entry_date'] ?? '');
            $status = (string) ($time_entry['status'] ?? '');
            if (strpos($entry_date, $reporting_month) !== 0 || $status !== 'approved') {
                continue;
            }

            $project_id = (int) ($time_entry['project_id'] ?? 0);
            $employee_id = (int) ($time_entry['employee_id'] ?? 0);
            $hours = (float) ($time_entry['hours'] ?? 0);
            $revenue = $hours * (float) ($time_entry['rate_snapshot'] ?? 0);
            $cost = $hours * (float) ($time_entry['cost_snapshot'] ?? 0);

            if (! isset($project_metrics[$project_id])) {
                $project_metrics[$project_id] = [
                    'hours' => 0.0,
                    'revenue' => 0.0,
                    'cost' => 0.0,
                    'direct_cost' => 0.0,
                    'profit' => 0.0,
                ];
            }

            if (! isset($employee_metrics[$employee_id])) {
                $employee_metrics[$employee_id] = [
                    'reported_hours' => 0.0,
                    'produced_profit' => 0.0,
                    'employee_profit' => 0.0,
                ];
            }

            $project_metrics[$project_id]['hours'] += $hours;
            $project_metrics[$project_id]['revenue'] += $revenue;
            $project_metrics[$project_id]['cost'] += $cost;

            $employee_metrics[$employee_id]['reported_hours'] += $hours;
            $employee_metrics[$employee_id]['employee_profit'] += $revenue - $cost;

            if (! isset($employee_project_hours[$employee_id])) {
                $employee_project_hours[$employee_id] = [];
            }
            if (! isset($employee_project_hours[$employee_id][$project_id])) {
                $employee_project_hours[$employee_id][$project_id] = 0.0;
            }
            $employee_project_hours[$employee_id][$project_id] += $hours;
        }

        foreach ($project_metrics as $project_id => &$project_metric_row) {
            $project_cost_rows = $this->project_costs->for_project($project_id);
            foreach ($project_cost_rows as $project_cost_row) {
                $cost_date = (string) ($project_cost_row['cost_date'] ?? '');
                if (strpos($cost_date, $reporting_month) !== 0) {
                    continue;
                }

                $project_metric_row['direct_cost'] += (float) ($project_cost_row['amount'] ?? 0);
            }

            $project_metric_row['profit'] = $project_metric_row['revenue'] - $project_metric_row['cost'] - $project_metric_row['direct_cost'];
        }
        unset($project_metric_row);

        foreach ($employee_project_hours as $employee_id => $project_hours) {
            foreach ($project_hours as $project_id => $employee_hours) {
                $project_total_hours = (float) ($project_metrics[$project_id]['hours'] ?? 0);
                if ($project_total_hours <= 0) {
                    continue;
                }

                $employee_metrics[$employee_id]['produced_profit'] += ((float) $employee_hours / $project_total_hours) * (float) ($project_metrics[$project_id]['profit'] ?? 0);
            }
        }

        foreach ($employee_metrics as &$employee_metric_row) {
            $employee_metric_row['reported_hours'] = round($employee_metric_row['reported_hours'], 2);
            $employee_metric_row['produced_profit'] = round($employee_metric_row['produced_profit'], 2);
            $employee_metric_row['employee_profit'] = round($employee_metric_row['employee_profit'], 2);
        }
        unset($employee_metric_row);

        return [
            'employees' => $employee_metrics,
        ];
    }

    private function build_client_profit_totals()
    {
        $profit_totals = [];
        $projects = $this->projects->all();
        $project_financials = $this->project_financial_service->get_project_financials(wp_list_pluck($projects, 'id'));

        foreach ($projects as $project_row) {
            $project_financial = $project_financials[(int) $project_row['id']] ?? null;
            $client_id = (int) ($project_row['client_id'] ?? 0);
            if ($client_id <= 0) {
                continue;
            }

            if (! isset($profit_totals[$client_id])) {
                $profit_totals[$client_id] = 0.0;
            }

            $profit_totals[$client_id] += (float) ($project_financial['profit'] ?? 0);
        }

        foreach ($profit_totals as &$profit_total) {
            $profit_total = round($profit_total, 2);
        }
        unset($profit_total);

        return $profit_totals;
    }

    private function account_type_label($account_type)
    {
        switch ((string) $account_type) {
            case 'admin':
                return __('Administrator', 'erp-omd');
            case 'manager':
                return __('Manager', 'erp-omd');
            case 'worker':
            default:
                return __('Pracownik', 'erp-omd');
        }
    }

    private function active_status_label($status)
    {
        switch ((string) $status) {
            case 'inactive':
                return __('Nieaktywny', 'erp-omd');
            case 'active':
            default:
                return __('Aktywny', 'erp-omd');
        }
    }

    private function project_status_label($status)
    {
        switch ((string) $status) {
            case 'do_rozpoczecia':
                return __('Do rozpoczęcia', 'erp-omd');
            case 'w_realizacji':
                return __('W realizacji', 'erp-omd');
            case 'w_akceptacji':
                return __('W akceptacji', 'erp-omd');
            case 'do_faktury':
                return __('Do faktury', 'erp-omd');
            case 'zakonczony':
                return __('Zakończony', 'erp-omd');
            case 'inactive':
                return __('Nieaktywny', 'erp-omd');
            default:
                return (string) $status;
        }
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
                return __('Time & Material', 'erp-omd');
        }
    }

    private function time_status_label($status)
    {
        switch ((string) $status) {
            case 'approved':
                return __('Zaakceptowany', 'erp-omd');
            case 'rejected':
                return __('Odrzucony', 'erp-omd');
            case 'submitted':
            default:
                return __('Zgłoszony', 'erp-omd');
        }
    }

    private function redirect_with_notice($page, $type, $message, array $extra = [])
    {
        $args = array_merge(['page' => $page, 'erp_omd_notice_type' => $type, 'erp_omd_notice' => rawurlencode($message)], $extra);
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    public static function render_notice()
    {
        if (empty($_GET['erp_omd_notice'])) { return; }
        $type = sanitize_html_class(wp_unslash($_GET['erp_omd_notice_type'] ?? 'success'));
        $message = sanitize_text_field(wp_unslash($_GET['erp_omd_notice']));
        printf('<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr($type), esc_html(rawurldecode($message)));
    }

    private function sync_wp_role($user_id, $account_type)
    {
        $user = get_user_by('id', $user_id);
        if (! $user instanceof WP_User) { return; }
        if ($account_type === 'manager') { $user->set_role('erp_omd_manager'); } elseif ($account_type === 'worker') { $user->set_role('erp_omd_worker'); }
    }
}

add_action('admin_notices', ['ERP_OMD_Admin', 'render_notice']);
