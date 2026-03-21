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
    private $attachments;
    private $time_entry_service;
    private $project_financial_service;
    private $reporting_service;
    private $alert_service;

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
        ERP_OMD_Alert_Service $alert_service
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
        add_submenu_page('erp-omd', __('Raporty', 'erp-omd'), __('Raporty', 'erp-omd'), 'erp_omd_access', 'erp-omd-reports', [$this, 'render_reports']);
        add_submenu_page('erp-omd', __('Alerty', 'erp-omd'), __('Alerty', 'erp-omd'), 'erp_omd_access', 'erp-omd-alerts', [$this, 'render_alerts']);
        add_submenu_page('erp-omd', __('Ustawienia', 'erp-omd'), __('Ustawienia', 'erp-omd'), 'erp_omd_manage_settings', 'erp-omd-settings', [$this, 'render_settings']);
    }

    public function enqueue_assets($hook)
    {
        if (strpos($hook, 'erp-omd') === false) {
            return;
        }
        wp_enqueue_style('erp-omd-admin', ERP_OMD_URL . 'assets/css/admin.css', [], ERP_OMD_VERSION);
        wp_enqueue_script('erp-omd-admin', ERP_OMD_URL . 'assets/js/admin.js', [], ERP_OMD_VERSION, true);
        wp_enqueue_media();
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
            case 'toggle_employee_active': $this->handle_employee_active_toggle(); break;
            case 'save_salary': $this->handle_salary_save(); break;
            case 'delete_salary': $this->handle_salary_delete(); break;
            case 'save_client': $this->handle_client_save(); break;
            case 'toggle_client_active': $this->handle_client_active_toggle(); break;
            case 'save_client_rate': $this->handle_client_rate_save(); break;
            case 'delete_client_rate': $this->handle_client_rate_delete(); break;
            case 'save_estimate': $this->handle_estimate_save(); break;
            case 'delete_estimate': $this->handle_estimate_delete(); break;
            case 'save_estimate_item': $this->handle_estimate_item_save(); break;
            case 'delete_estimate_item': $this->handle_estimate_item_delete(); break;
            case 'accept_estimate': $this->handle_estimate_accept(); break;
            case 'export_estimate': $this->handle_estimate_export(); break;
            case 'export_report': $this->handle_report_export(); break;
            case 'save_project': $this->handle_project_save(); break;
            case 'duplicate_project': $this->handle_project_duplicate(); break;
            case 'toggle_project_active': $this->handle_project_active_toggle(); break;
            case 'bulk_clients': $this->handle_clients_bulk_action(); break;
            case 'bulk_projects': $this->handle_projects_bulk_action(); break;
            case 'bulk_estimates': $this->handle_estimates_bulk_action(); break;
            case 'add_project_note': $this->handle_project_note_add(); break;
            case 'save_project_rate': $this->handle_project_rate_save(); break;
            case 'delete_project_rate': $this->handle_project_rate_delete(); break;
            case 'save_project_cost': $this->handle_project_cost_save(); break;
            case 'delete_project_cost': $this->handle_project_cost_delete(); break;
            case 'save_time_entry': $this->handle_time_entry_save(); break;
            case 'change_time_status': $this->handle_time_status_change(); break;
            case 'delete_time_entry': $this->handle_time_entry_delete(); break;
            case 'bulk_time_entries': $this->handle_time_entries_bulk_action(); break;
            case 'add_attachment': $this->handle_attachment_add(); break;
            case 'delete_attachment': $this->handle_attachment_delete(); break;
            case 'save_settings': $this->handle_settings_save(); break;
            case 'delete_client': $this->handle_client_delete(); break;
            case 'delete_project': $this->handle_project_delete(); break;
        }
    }

    public function render_dashboard()
    {
        $employees = $this->employees->all();
        $roles = $this->roles->all();
        $clients = $this->clients->all();
        $projects = $this->projects->all();
        $alerts = $this->alert_service->all_alerts();
        $reporting_month = current_time('Y-m');
        $reporting_month_label = current_time('m.Y');
        $monthly_metrics = $this->build_monthly_performance_metrics($reporting_month);
        $monthly_totals = $monthly_metrics['totals'] ?? [
            'reported_hours' => 0.0,
            'hourly_cost_total' => 0.0,
            'employee_profit' => 0.0,
            'active_employees' => 0,
        ];
        $alert_summary = [
            'error' => 0,
            'warning' => 0,
            'info' => 0,
        ];

        foreach ($alerts as $alert) {
            $severity = (string) ($alert['severity'] ?? '');
            if (isset($alert_summary[$severity])) {
                $alert_summary[$severity]++;
            }
        }

        $dashboard_recent_alerts = array_slice($alerts, 0, 5);
        $dashboard_shortcuts = [
            ['label' => __('Dodaj klienta', 'erp-omd'), 'url' => add_query_arg(['page' => 'erp-omd-clients', 'edit' => 1], admin_url('admin.php'))],
            ['label' => __('Dodaj projekt', 'erp-omd'), 'url' => add_query_arg(['page' => 'erp-omd-projects'], admin_url('admin.php'))],
            ['label' => __('Dodaj wpis czasu', 'erp-omd'), 'url' => add_query_arg(['page' => 'erp-omd-time'], admin_url('admin.php'))],
            ['label' => __('Raport miesięczny', 'erp-omd'), 'url' => add_query_arg(['page' => 'erp-omd-reports', 'tab' => 'reports', 'report_type' => 'monthly'], admin_url('admin.php'))],
        ];
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
        $employee_alerts = $this->index_alerts_by_entity('employee');
        $employees = $this->employees->all();
        foreach ($employees as &$employee_row) {
            $current_salary_row = $this->resolve_current_salary_row((int) $employee_row['id']);
            $employee_row['current_monthly_salary'] = (float) ($current_salary_row['monthly_salary'] ?? 0);
            $employee_row['current_hourly_cost'] = (float) ($current_salary_row['hourly_cost'] ?? 0);
            $employee_monthly_metrics = $monthly_metrics['employees'][(int) $employee_row['id']] ?? [];
            $employee_row['reported_hours'] = (float) ($employee_monthly_metrics['reported_hours'] ?? 0);
            $employee_row['hourly_cost_total'] = (float) ($employee_monthly_metrics['hourly_cost_total'] ?? 0);
            $employee_row['employee_profit'] = (float) ($employee_monthly_metrics['employee_profit'] ?? 0);
            $employee_row['target_monthly_hours'] = isset($current_salary_row['monthly_hours'])
                ? round((float) $current_salary_row['monthly_hours'] - $employee_row['reported_hours'], 2)
                : null;
            $employee_row['alerts'] = $employee_alerts[(int) $employee_row['id']] ?? [];
        }
        unset($employee_row);
        $roles = $this->roles->all();
        $users = get_users(['number' => 200, 'orderby' => 'login', 'order' => 'ASC']);
        $suggested_hours = $this->monthly_hours_service->suggested_hours(gmdate('Y-m'));
        include ERP_OMD_PATH . 'templates/admin/employees.php';
    }

    public function render_clients()
    {
        $selected_client = null;
        $client = null;
        $client_rates = [];
        $editing_client_rate = null;
        $is_editing_client = ! empty($_GET['edit']) || ! empty($_GET['rate_id']);
        if (! empty($_GET['id'])) {
            $selected_client = $this->clients->find((int) $_GET['id']);
            if ($selected_client) {
                $client_rates = $this->client_rates->for_client((int) $selected_client['id']);
                if (! empty($_GET['rate_id'])) {
                    $editing_client_rate = $this->client_rates->find((int) $_GET['rate_id']);
                    if (! $editing_client_rate || (int) ($editing_client_rate['client_id'] ?? 0) !== (int) $selected_client['id']) {
                        $editing_client_rate = null;
                    }
                }
            }
        }
        if ($is_editing_client && $selected_client) {
            $client = $selected_client;
        }
        $client_profit_totals = $this->build_client_profit_totals();
        $client_alerts = $this->index_alerts_by_entity('client');
        $clients = $this->clients->all();
        foreach ($clients as &$client_row) {
            $client_row['total_profit'] = (float) ($client_profit_totals[(int) $client_row['id']] ?? 0);
            $client_row['alerts'] = $client_alerts[(int) $client_row['id']] ?? [];
        }
        unset($client_row);
        if ($selected_client) {
            foreach ($clients as $client_row) {
                if ((int) $client_row['id'] === (int) $selected_client['id']) {
                    $selected_client['account_manager_login'] = $client_row['account_manager_login'] ?? '';
                    break;
                }
            }
        }
        $client_filters = [
            'search' => sanitize_text_field(wp_unslash($_GET['search'] ?? '')),
            'status' => sanitize_text_field(wp_unslash($_GET['status'] ?? '')),
        ];
        $clients = array_values(array_filter($clients, function ($client_row) use ($client_filters) {
            if ($client_filters['search'] !== '') {
                $haystack = strtolower(implode(' ', [
                    (string) ($client_row['name'] ?? ''),
                    (string) ($client_row['company'] ?? ''),
                    (string) ($client_row['nip'] ?? ''),
                    (string) ($client_row['email'] ?? ''),
                    (string) ($client_row['city'] ?? ''),
                ]));
                if (strpos($haystack, strtolower($client_filters['search'])) === false) {
                    return false;
                }
            }
            if ($client_filters['status'] !== '' && (string) ($client_row['status'] ?? '') !== $client_filters['status']) {
                return false;
            }

            return true;
        }));
        $roles = $this->roles->all();
        $employees_for_select = $this->employees->all();
        include ERP_OMD_PATH . 'templates/admin/clients.php';
    }

    public function render_estimates()
    {
        $selected_estimate = null;
        $estimate = null;
        $estimate_items = [];
        $estimate_totals = ['net' => 0.0, 'tax' => 0.0, 'gross' => 0.0, 'internal_cost' => 0.0];
        $estimate_attachments = [];
        $editing_estimate_item = null;
        $linked_project = null;
        $is_editing_estimate = ! empty($_GET['edit']) || ! empty($_GET['item_id']);
        if (! empty($_GET['id'])) {
            $selected_estimate = $this->estimates->find((int) $_GET['id']);
            if ($selected_estimate) {
                $estimate_items = $this->estimate_items->for_estimate((int) $selected_estimate['id']);
                $estimate_totals = $this->estimate_service->calculate_totals($estimate_items);
                $linked_project = $this->projects->find_by_estimate_id((int) $selected_estimate['id']);
                $estimate_attachments = $this->attachments->for_entity('estimate', (int) $selected_estimate['id']);
                if (! empty($_GET['item_id'])) {
                    $editing_estimate_item = $this->estimate_items->find((int) $_GET['item_id']);
                    if (! $editing_estimate_item || (int) ($editing_estimate_item['estimate_id'] ?? 0) !== (int) $selected_estimate['id']) {
                        $editing_estimate_item = null;
                    }
                }
            }
        }
        if ($is_editing_estimate && $selected_estimate) {
            $estimate = $selected_estimate;
        }
        $estimate_project_alerts = $this->index_alerts_by_entity('project');
        if ($selected_estimate) {
            $selected_estimate['alerts'] = $linked_project
                ? ($estimate_project_alerts[(int) ($linked_project['id'] ?? 0)] ?? [])
                : [];
        }
        $estimates = $this->estimates->all();
        foreach ($estimates as &$estimate_row) {
            $estimate_row_items = $this->estimate_items->for_estimate((int) $estimate_row['id']);
            $estimate_row_totals = $this->estimate_service->calculate_totals($estimate_row_items);
            $estimate_row['total_net'] = $estimate_row_totals['net'];
            $estimate_row['total_gross'] = $estimate_row_totals['gross'];
            $estimate_row['total_internal_cost'] = $estimate_row_totals['internal_cost'];
            $estimate_row['alerts'] = ! empty($estimate_row['project_id'])
                ? ($estimate_project_alerts[(int) $estimate_row['project_id']] ?? [])
                : [];
        }
        unset($estimate_row);
        $estimate_filters = [
            'search' => sanitize_text_field(wp_unslash($_GET['search'] ?? '')),
            'status' => sanitize_text_field(wp_unslash($_GET['status'] ?? '')),
            'client_id' => (int) ($_GET['client_id'] ?? 0),
        ];
        $estimates = array_values(array_filter($estimates, function ($estimate_row) use ($estimate_filters) {
            if ($estimate_filters['search'] !== '') {
                $haystack = strtolower(implode(' ', [
                    (string) ($estimate_row['name'] ?? ''),
                    (string) ($estimate_row['client_name'] ?? ''),
                    (string) ($estimate_row['project_name'] ?? ''),
                ]));
                if (strpos($haystack, strtolower($estimate_filters['search'])) === false) {
                    return false;
                }
            }
            if ($estimate_filters['status'] !== '' && (string) ($estimate_row['status'] ?? '') !== $estimate_filters['status']) {
                return false;
            }
            if ($estimate_filters['client_id'] > 0 && (int) ($estimate_row['client_id'] ?? 0) !== $estimate_filters['client_id']) {
                return false;
            }

            return true;
        }));
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
        if ($project) {
            foreach ($projects as $project_row) {
                if ((int) $project_row['id'] === (int) $project['id']) {
                    $project['client_name'] = $project_row['client_name'] ?? '';
                    $project['manager_login'] = $project_row['manager_login'] ?? '';
                    break;
                }
            }
        }
        $clients = $this->clients->all();
        $employees_for_select = $this->employees->all();
        $roles = $this->roles->all();
        $project_alerts = $this->index_alerts_by_entity('project');
        $project_financials_by_project = array_replace(
            $this->project_financial_service->get_project_financials(wp_list_pluck($projects, 'id')),
            $project_financials_by_project
        );
        foreach ($projects as &$project_row) {
            $project_row['alerts'] = $project_alerts[(int) $project_row['id']] ?? [];
        }
        unset($project_row);
        $project_filters = [
            'search' => sanitize_text_field(wp_unslash($_GET['search'] ?? '')),
            'client_id' => (int) ($_GET['client_id'] ?? 0),
            'manager_id' => (int) ($_GET['manager_id'] ?? 0),
            'status' => sanitize_text_field(wp_unslash($_GET['status'] ?? '')),
        ];
        $projects = array_values(array_filter($projects, function ($project_row) use ($project_filters) {
            if ($project_filters['search'] !== '') {
                $haystack = strtolower(implode(' ', [
                    (string) ($project_row['name'] ?? ''),
                    (string) ($project_row['client_name'] ?? ''),
                    (string) ($project_row['manager_login'] ?? ''),
                ]));
                if (strpos($haystack, strtolower($project_filters['search'])) === false) {
                    return false;
                }
            }
            if ($project_filters['client_id'] > 0 && (int) ($project_row['client_id'] ?? 0) !== $project_filters['client_id']) {
                return false;
            }
            if ($project_filters['manager_id'] > 0 && (int) ($project_row['manager_id'] ?? 0) !== $project_filters['manager_id']) {
                return false;
            }
            if ($project_filters['status'] !== '' && (string) ($project_row['status'] ?? '') !== $project_filters['status']) {
                return false;
            }

            return true;
        }));
        $project_attachments = $project ? $this->attachments->for_entity('project', (int) $project['id']) : [];
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
            'client_id' => $_GET['client_id'] ?? '',
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
        $clients_for_time = $this->clients->all();
        $roles = $this->roles->all();
        $time_entries = $this->time_entry_service->filter_visible_entries($this->time_entries->all(array_diff_key($filters, ['client_id' => ''])), $current_user);
        if (! empty($filters['client_id'])) {
            $time_entries = array_values(array_filter($time_entries, function ($time_row) use ($filters, $projects_for_time) {
                foreach ($projects_for_time as $project_row) {
                    if ((int) ($project_row['id'] ?? 0) === (int) ($time_row['project_id'] ?? 0)) {
                        return (int) ($project_row['client_id'] ?? 0) === (int) $filters['client_id'];
                    }
                }

                return false;
            }));
        }
        $selected_employee_id = $entry['employee_id'] ?? ($current_employee['id'] ?? 0);
        $selected_time_client_id = 0;
        if ($entry) {
            foreach ($projects_for_time as $project_row) {
                if ((int) ($project_row['id'] ?? 0) === (int) ($entry['project_id'] ?? 0)) {
                    $selected_time_client_id = (int) ($project_row['client_id'] ?? 0);
                    break;
                }
            }
        } elseif (! empty($filters['client_id'])) {
            $selected_time_client_id = (int) $filters['client_id'];
        }
        $can_set_status = current_user_can('administrator') || current_user_can('erp_omd_approve_time');
        $saved_views = $this->get_saved_views('time');
        include ERP_OMD_PATH . 'templates/admin/time-entries.php';
    }

    public function render_settings()
    {
        $delete_data = (bool) get_option('erp_omd_delete_data_on_uninstall', false);
        $margin_threshold = (float) get_option('erp_omd_alert_margin_threshold', 10);
        include ERP_OMD_PATH . 'templates/admin/settings.php';
    }

    public function render_alerts()
    {
        $alerts = $this->alert_service->all_alerts();
        include ERP_OMD_PATH . 'templates/admin/alerts.php';
    }

    public function render_reports()
    {
        $report_filters = $this->reporting_service->sanitize_filters($_GET);
        $report_rows = $this->reporting_service->build_report($report_filters['report_type'], $report_filters);
        $calendar_data = $this->reporting_service->build_calendar($report_filters);
        $clients = $this->clients->all();
        $projects = $this->projects->all();
        $employees = $this->employees->all();
        $status_options = ['do_rozpoczecia', 'w_realizacji', 'w_akceptacji', 'do_faktury', 'zakonczony', 'inactive', 'submitted', 'approved', 'rejected'];
        $status_labels = [
            'do_rozpoczecia' => $this->project_status_label('do_rozpoczecia'),
            'w_realizacji' => $this->project_status_label('w_realizacji'),
            'w_akceptacji' => $this->project_status_label('w_akceptacji'),
            'do_faktury' => $this->project_status_label('do_faktury'),
            'zakonczony' => $this->project_status_label('zakonczony'),
            'inactive' => $this->active_status_label('inactive'),
            'submitted' => $this->time_status_label('submitted'),
            'approved' => $this->time_status_label('approved'),
            'rejected' => $this->time_status_label('rejected'),
        ];
        $report_titles = [
            'projects' => __('Raport projektów', 'erp-omd'),
            'clients' => __('Raport klientów', 'erp-omd'),
            'invoice' => __('Raport do faktury', 'erp-omd'),
            'monthly' => __('Raport miesięczny', 'erp-omd'),
        ];
        $report_title = $report_titles[$report_filters['report_type']] ?? __('Raporty', 'erp-omd');
        $saved_views = $this->get_saved_views('reports');
        include ERP_OMD_PATH . 'templates/admin/reports.php';
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

    private function handle_employee_active_toggle()
    {
        check_admin_referer('erp_omd_toggle_employee_active');
        $this->require_capability('erp_omd_manage_employees');
        $id = (int) ($_POST['id'] ?? 0);
        $employee = $id ? $this->employees->find($id) : null;
        if ($employee) {
            $target_status = ($employee['status'] ?? '') === 'inactive' ? 'active' : 'inactive';
            $this->employees->set_status($id, $target_status);
            $message = $target_status === 'inactive'
                ? __('Pracownik został dezaktywowany.', 'erp-omd')
                : __('Pracownik został aktywowany.', 'erp-omd');
            $this->redirect_with_notice('erp-omd-employees', 'success', $message);
        }
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
        $payload = $this->client_project_service->prepare_client(['name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')), 'company' => sanitize_text_field(wp_unslash($_POST['company'] ?? '')), 'nip' => sanitize_text_field(wp_unslash($_POST['nip'] ?? '')), 'email' => sanitize_email(wp_unslash($_POST['email'] ?? '')), 'phone' => sanitize_text_field(wp_unslash($_POST['phone'] ?? '')), 'contact_person_name' => sanitize_text_field(wp_unslash($_POST['contact_person_name'] ?? '')), 'contact_person_email' => sanitize_email(wp_unslash($_POST['contact_person_email'] ?? '')), 'contact_person_phone' => sanitize_text_field(wp_unslash($_POST['contact_person_phone'] ?? '')), 'city' => sanitize_text_field(wp_unslash($_POST['city'] ?? '')), 'street' => sanitize_text_field(wp_unslash($_POST['street'] ?? '')), 'apartment_number' => sanitize_text_field(wp_unslash($_POST['apartment_number'] ?? '')), 'postal_code' => sanitize_text_field(wp_unslash($_POST['postal_code'] ?? '')), 'country' => sanitize_text_field(wp_unslash($_POST['country'] ?? 'PL')), 'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? 'active')), 'account_manager_id' => (int) ($_POST['account_manager_id'] ?? 0), 'alert_margin_threshold' => sanitize_text_field(wp_unslash($_POST['alert_margin_threshold'] ?? ''))]);
        $errors = $this->client_project_service->validate_client($payload, $id ?: null);
        if ($errors) { $this->redirect_with_notice('erp-omd-clients', 'error', implode(' ', $errors), $id ? ['id' => $id, 'edit' => 1] : []); }
        if ($id) { $this->clients->update($id, $payload); $message = __('Klient został zaktualizowany.', 'erp-omd'); } else { $id = $this->clients->create($payload); $message = __('Klient został utworzony.', 'erp-omd'); }
        $this->redirect_with_notice('erp-omd-clients', 'success', $message, ['id' => $id]);
    }

    private function handle_client_active_toggle()
    {
        check_admin_referer('erp_omd_toggle_client_active');
        $this->require_capability('erp_omd_manage_clients');
        $id = (int) ($_POST['id'] ?? 0);
        $client = $id ? $this->clients->find($id) : null;
        if ($client) {
            $target_status = ($client['status'] ?? '') === 'inactive' ? 'active' : 'inactive';
            $this->clients->set_status($id, $target_status);
            $message = $target_status === 'inactive'
                ? __('Klient został dezaktywowany.', 'erp-omd')
                : __('Klient został aktywowany.', 'erp-omd');
            $this->redirect_with_notice('erp-omd-clients', 'success', $message);
        }
        $this->redirect_with_notice('erp-omd-clients', 'error', __('Nie znaleziono klienta.', 'erp-omd'));
    }

    private function handle_client_delete()
    {
        check_admin_referer('erp_omd_delete_client');
        $this->require_capability('erp_omd_manage_clients');
        $id = (int) ($_POST['id'] ?? 0);
        $client = $id ? $this->clients->find($id) : null;

        if (! $client) {
            $this->redirect_with_notice('erp-omd-clients', 'error', __('Nie znaleziono klienta.', 'erp-omd'));
        }

        $this->clients->delete($id);
        $this->redirect_with_notice('erp-omd-clients', 'success', __('Klient został usunięty.', 'erp-omd'));
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
        if ($errors) { $this->redirect_with_notice('erp-omd-clients', 'error', implode(' ', $errors), ['id' => $client_id, 'edit' => 1]); }

        if ($rate_id > 0) {
            $existing_rate = $this->client_rates->find($rate_id);
            if (! $existing_rate || (int) ($existing_rate['client_id'] ?? 0) !== $client_id) {
                $this->redirect_with_notice('erp-omd-clients', 'error', __('Nie znaleziono stawki klienta do edycji.', 'erp-omd'), ['id' => $client_id, 'edit' => 1]);
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
            'name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
            'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? 'wstepny')),
            'accepted_by_user_id' => (int) ($existing['accepted_by_user_id'] ?? 0),
            'accepted_at' => $existing['accepted_at'] ?? null,
        ];
        $errors = $this->estimate_service->validate_estimate($payload, $existing);
        if ($errors) {
            $this->redirect_with_notice('erp-omd-estimates', 'error', implode(' ', $errors), $id ? ['id' => $id, 'edit' => 1] : []);
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
            $this->redirect_with_notice('erp-omd-estimates', 'error', implode(' ', $errors), ['id' => $estimate_id, 'edit' => 1]);
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
            $this->redirect_with_notice('erp-omd-estimates', 'error', __('Nie znaleziono pozycji kosztorysu.', 'erp-omd'), ['id' => $estimate_id, 'edit' => 1]);
        }
        if (($estimate['status'] ?? '') === 'zaakceptowany') {
            $this->redirect_with_notice('erp-omd-estimates', 'error', __('Nie można usuwać pozycji z zaakceptowanego kosztorysu.', 'erp-omd'), ['id' => $estimate_id, 'edit' => 1]);
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

    private function handle_estimate_export()
    {
        check_admin_referer('erp_omd_export_estimate');
        $this->require_capability('erp_omd_manage_projects');

        $estimate_id = (int) ($_POST['estimate_id'] ?? 0);
        $audience = sanitize_text_field(wp_unslash($_POST['export_variant'] ?? 'client'));
        if (! in_array($audience, ['client', 'agency'], true)) {
            $this->redirect_with_notice('erp-omd-estimates', 'error', __('Niepoprawny wariant eksportu kosztorysu.', 'erp-omd'), ['id' => $estimate_id]);
        }

        $estimate = $this->estimates->find($estimate_id);
        if (! $estimate) {
            $this->redirect_with_notice('erp-omd-estimates', 'error', __('Nie znaleziono kosztorysu do eksportu.', 'erp-omd'));
        }

        $client = $this->clients->find((int) ($estimate['client_id'] ?? 0));
        $items = $this->estimate_items->for_estimate($estimate_id);
        $totals = $this->estimate_service->calculate_totals($items);
        $estimate_name = trim((string) ($estimate['name'] ?? '')) !== ''
            ? (string) $estimate['name']
            : sprintf(__('Kosztorys #%d', 'erp-omd'), $estimate_id);
        $filename = sanitize_file_name(sprintf('%s-%s.csv', $estimate_name, strtolower($audience)));

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        if (! $output) {
            wp_die(esc_html__('Nie udało się przygotować pliku eksportu.', 'erp-omd'));
        }

        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, [__('Nazwa kosztorysu', 'erp-omd'), $estimate_name], ';');
        fputcsv($output, [__('Klient', 'erp-omd'), (string) ($client['name'] ?? ($estimate['client_name'] ?? '—'))], ';');
        fputcsv($output, [__('Firma', 'erp-omd'), (string) ($client['company'] ?? '—')], ';');
        fputcsv($output, [__('NIP', 'erp-omd'), (string) ($client['nip'] ?? '—')], ';');
        fputcsv($output, [__('Email', 'erp-omd'), (string) ($client['email'] ?? '—')], ';');
        fputcsv($output, [__('Telefon', 'erp-omd'), (string) ($client['phone'] ?? '—')], ';');
        fputcsv($output, [__('Osoba kontaktowa', 'erp-omd'), (string) ($client['contact_person_name'] ?? '—')], ';');
        fputcsv($output, [__('Email kontaktowy', 'erp-omd'), (string) ($client['contact_person_email'] ?? '—')], ';');
        fputcsv($output, [__('Telefon kontaktowy', 'erp-omd'), (string) ($client['contact_person_phone'] ?? '—')], ';');
        fputcsv($output, [__('Miasto', 'erp-omd'), (string) ($client['city'] ?? '—')], ';');
        fputcsv($output, [__('Kod pocztowy', 'erp-omd'), (string) ($client['postal_code'] ?? '—')], ';');
        fputcsv($output, [__('Kraj', 'erp-omd'), (string) ($client['country'] ?? '—')], ';');
        fputcsv($output, [__('Ulica', 'erp-omd'), (string) ($client['street'] ?? '—')], ';');
        fputcsv($output, [__('Numer lokalu', 'erp-omd'), (string) ($client['apartment_number'] ?? '—')], ';');
        fputcsv($output, [__('Status', 'erp-omd'), (string) ($estimate['status'] ?? '—')], ';');
        fputcsv($output, [], ';');

        $header = [
            __('Pozycja', 'erp-omd'),
            __('Ilość', 'erp-omd'),
            __('Cena jednostkowa netto', 'erp-omd'),
            __('Wartość netto', 'erp-omd'),
            __('Komentarz', 'erp-omd'),
        ];
        if ($audience === 'agency') {
            $header[] = __('Koszt wewnętrzny jednostkowy', 'erp-omd');
            $header[] = __('Koszt wewnętrzny łącznie', 'erp-omd');
        }
        fputcsv($output, $header, ';');

        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $cost_internal = (float) ($item['cost_internal'] ?? 0);

            $row = [
                (string) ($item['name'] ?? ''),
                number_format($qty, 2, '.', ''),
                number_format($price, 2, '.', ''),
                number_format($qty * $price, 2, '.', ''),
                (string) ($item['comment'] ?? ''),
            ];
            if ($audience === 'agency') {
                $row[] = number_format($cost_internal, 2, '.', '');
                $row[] = number_format($qty * $cost_internal, 2, '.', '');
            }
            fputcsv($output, $row, ';');
        }

        fputcsv($output, [], ';');
        fputcsv($output, [__('Suma netto', 'erp-omd'), number_format((float) $totals['net'], 2, '.', '')], ';');
        fputcsv($output, [__('VAT 23%', 'erp-omd'), number_format((float) $totals['tax'], 2, '.', '')], ';');
        fputcsv($output, [__('Suma brutto', 'erp-omd'), number_format((float) $totals['gross'], 2, '.', '')], ';');
        if ($audience === 'agency') {
            fputcsv($output, [__('Koszt wewnętrzny', 'erp-omd'), number_format((float) $totals['internal_cost'], 2, '.', '')], ';');
        }

        fclose($output);
        exit;
    }

    private function handle_report_export()
    {
        check_admin_referer('erp_omd_export_report');
        $this->require_capability('erp_omd_access');

        $filters = $this->reporting_service->sanitize_filters($_POST);
        $report_type = sanitize_key((string) ($_POST['report_type'] ?? 'projects'));
        $export = $this->reporting_service->export_definition($report_type, $filters);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($export['filename']) . '"');

        $output = fopen('php://output', 'w');
        if (! $output) {
            wp_die(esc_html__('Nie udało się przygotować pliku raportu.', 'erp-omd'));
        }

        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, $export['headers'], ';');
        foreach ($export['rows'] as $row) {
            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }

    private function handle_project_save()
    {
        check_admin_referer('erp_omd_save_project');
        $this->require_capability('erp_omd_manage_projects');
        $id = empty($_POST['id']) ? 0 : (int) $_POST['id'];
        $existing = $id ? $this->projects->find($id) : null;
        $payload = $this->client_project_service->prepare_project(['client_id' => (int) ($_POST['client_id'] ?? 0), 'name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')), 'billing_type' => sanitize_text_field(wp_unslash($_POST['billing_type'] ?? 'time_material')), 'budget' => (float) ($_POST['budget'] ?? 0), 'retainer_monthly_fee' => (float) ($_POST['retainer_monthly_fee'] ?? 0), 'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? 'do_rozpoczecia')), 'start_date' => sanitize_text_field(wp_unslash($_POST['start_date'] ?? '')), 'end_date' => sanitize_text_field(wp_unslash($_POST['end_date'] ?? '')), 'manager_id' => (int) ($_POST['manager_id'] ?? 0), 'estimate_id' => (int) ($_POST['estimate_id'] ?? 0), 'brief' => sanitize_textarea_field(wp_unslash($_POST['brief'] ?? '')), 'alert_margin_threshold' => sanitize_text_field(wp_unslash($_POST['alert_margin_threshold'] ?? ''))], $existing);
        $errors = $this->client_project_service->validate_project($payload, $existing);
        if ($errors) { $this->redirect_with_notice('erp-omd-projects', 'error', implode(' ', $errors), $id ? ['id' => $id] : []); }
        if ($id) { $this->projects->update($id, $payload); $message = __('Projekt został zaktualizowany.', 'erp-omd'); } else { $id = $this->projects->create($payload); $message = __('Projekt został utworzony.', 'erp-omd'); }
        $this->project_financial_service->rebuild_for_project($id);
        $this->redirect_with_notice('erp-omd-projects', 'success', $message, ['id' => $id]);
    }

    private function handle_project_duplicate()
    {
        check_admin_referer('erp_omd_duplicate_project');
        $this->require_capability('erp_omd_manage_projects');
        $project_id = (int) ($_POST['id'] ?? 0);
        $project = $project_id ? $this->projects->find($project_id) : null;
        if (! $project) {
            $this->redirect_with_notice('erp-omd-projects', 'error', __('Nie znaleziono projektu do duplikacji.', 'erp-omd'));
        }

        $duplicate_payload = $this->client_project_service->prepare_project([
            'client_id' => (int) ($project['client_id'] ?? 0),
            'name' => sprintf(__('Kopia — %s', 'erp-omd'), (string) ($project['name'] ?? '')),
            'billing_type' => (string) ($project['billing_type'] ?? 'time_material'),
            'budget' => (float) ($project['budget'] ?? 0),
            'retainer_monthly_fee' => (float) ($project['retainer_monthly_fee'] ?? 0),
            'status' => 'do_rozpoczecia',
            'start_date' => '',
            'end_date' => '',
            'manager_id' => (int) ($project['manager_id'] ?? 0),
            'estimate_id' => 0,
            'brief' => (string) ($project['brief'] ?? ''),
            'alert_margin_threshold' => $project['alert_margin_threshold'] ?? '',
        ]);

        $errors = $this->client_project_service->validate_project($duplicate_payload);
        if ($errors) {
            $this->redirect_with_notice('erp-omd-projects', 'error', implode(' ', $errors), ['id' => $project_id]);
        }

        $new_project_id = $this->projects->create($duplicate_payload);
        $this->project_financial_service->rebuild_for_project($new_project_id);

        $this->redirect_with_notice('erp-omd-projects', 'success', __('Projekt został zduplikowany.', 'erp-omd'), ['id' => $new_project_id]);
    }

    private function handle_project_active_toggle()
    {
        check_admin_referer('erp_omd_toggle_project_active');
        $this->require_capability('erp_omd_manage_projects');
        $id = (int) ($_POST['id'] ?? 0);
        $project = $id ? $this->projects->find($id) : null;

        if ($project) {
            $target_status = ($project['status'] ?? '') === 'inactive' ? 'do_rozpoczecia' : 'inactive';
            $this->projects->set_status($id, $target_status);
            $message = $target_status === 'inactive'
                ? __('Projekt został dezaktywowany.', 'erp-omd')
                : __('Projekt został aktywowany.', 'erp-omd');
            $this->redirect_with_notice('erp-omd-projects', 'success', $message);
        }

        $this->redirect_with_notice('erp-omd-projects', 'error', __('Nie znaleziono projektu.', 'erp-omd'));
    }

    private function handle_project_delete()
    {
        check_admin_referer('erp_omd_delete_project');
        $this->require_capability('erp_omd_manage_projects');
        $id = (int) ($_POST['id'] ?? 0);
        $project = $id ? $this->projects->find($id) : null;

        if (! $project) {
            $this->redirect_with_notice('erp-omd-projects', 'error', __('Nie znaleziono projektu.', 'erp-omd'));
        }

        $this->projects->delete($id);
        $this->redirect_with_notice('erp-omd-projects', 'success', __('Projekt został usunięty.', 'erp-omd'));
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
        $selected_client_id = (int) ($_POST['client_id'] ?? 0);
        $payload = ['employee_id' => $employee_id, 'project_id' => (int) ($_POST['project_id'] ?? 0), 'role_id' => (int) ($_POST['role_id'] ?? 0), 'hours' => (float) ($_POST['hours'] ?? 0), 'entry_date' => sanitize_text_field(wp_unslash($_POST['entry_date'] ?? '')), 'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')), 'status' => $status, 'created_by_user_id' => (int) $current_user->ID, 'approved_by_user_id' => in_array($status, ['approved', 'rejected'], true) ? (int) $current_user->ID : 0, 'approved_at' => in_array($status, ['approved', 'rejected'], true) ? current_time('mysql') : null];
        $selected_project = $this->projects->find((int) $payload['project_id']);
        if ($selected_client_id > 0 && $selected_project && (int) ($selected_project['client_id'] ?? 0) !== $selected_client_id) {
            $this->redirect_with_notice('erp-omd-time', 'error', __('Wybrany projekt nie należy do wskazanego klienta.', 'erp-omd'));
        }
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
        $this->redirect_with_notice('erp-omd-time', 'success', $message);
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

    private function handle_clients_bulk_action()
    {
        check_admin_referer('erp_omd_bulk_clients');
        $this->require_capability('erp_omd_manage_clients');
        $bulk_action = sanitize_text_field(wp_unslash($_POST['bulk_action'] ?? ''));
        $client_ids = array_values(array_filter(array_map('intval', wp_unslash($_POST['client_ids'] ?? []))));
        if ($bulk_action === '' || empty($client_ids)) {
            $this->redirect_with_notice('erp-omd-clients', 'error', __('Wybierz akcję masową i co najmniej jednego klienta.', 'erp-omd'));
        }
        foreach ($client_ids as $client_id) {
            if ($bulk_action === 'activate') {
                $this->clients->set_status($client_id, 'active');
            } elseif ($bulk_action === 'deactivate') {
                $this->clients->set_status($client_id, 'inactive');
            }
        }
        $this->redirect_with_notice('erp-omd-clients', 'success', __('Akcja masowa dla klientów została wykonana.', 'erp-omd'));
    }

    private function handle_projects_bulk_action()
    {
        check_admin_referer('erp_omd_bulk_projects');
        $this->require_capability('erp_omd_manage_projects');
        $bulk_action = sanitize_text_field(wp_unslash($_POST['bulk_action'] ?? ''));
        $project_ids = array_values(array_filter(array_map('intval', wp_unslash($_POST['project_ids'] ?? []))));
        if ($bulk_action === '' || empty($project_ids)) {
            $this->redirect_with_notice('erp-omd-projects', 'error', __('Wybierz akcję masową i co najmniej jeden projekt.', 'erp-omd'));
        }
        foreach ($project_ids as $project_id) {
            if ($bulk_action === 'activate') {
                $this->projects->set_status($project_id, 'do_rozpoczecia');
            } elseif ($bulk_action === 'deactivate') {
                $this->projects->set_status($project_id, 'inactive');
            }
        }
        $this->redirect_with_notice('erp-omd-projects', 'success', __('Akcja masowa dla projektów została wykonana.', 'erp-omd'));
    }

    private function handle_estimates_bulk_action()
    {
        check_admin_referer('erp_omd_bulk_estimates');
        $this->require_capability('erp_omd_manage_projects');
        $bulk_action = sanitize_text_field(wp_unslash($_POST['bulk_action'] ?? ''));
        $estimate_ids = array_values(array_filter(array_map('intval', wp_unslash($_POST['estimate_ids'] ?? []))));
        if ($bulk_action === '' || empty($estimate_ids)) {
            $this->redirect_with_notice('erp-omd-estimates', 'error', __('Wybierz akcję masową i co najmniej jeden kosztorys.', 'erp-omd'));
        }

        foreach ($estimate_ids as $estimate_id) {
            $estimate = $this->estimates->find($estimate_id);
            if (! $estimate) {
                continue;
            }
            if ($bulk_action === 'delete' && ($estimate['status'] ?? '') !== 'zaakceptowany') {
                $this->estimates->delete($estimate_id);
            }
            if ($bulk_action === 'accept' && ($estimate['status'] ?? '') !== 'zaakceptowany') {
                $this->estimate_service->accept($estimate_id);
            }
        }

        $this->redirect_with_notice('erp-omd-estimates', 'success', __('Akcja masowa dla kosztorysów została wykonana.', 'erp-omd'));
    }

    private function handle_settings_save()
    {
        check_admin_referer('erp_omd_save_settings');
        $this->require_capability('erp_omd_manage_settings');
        update_option('erp_omd_delete_data_on_uninstall', ! empty($_POST['delete_data_on_uninstall']));
        update_option('erp_omd_alert_margin_threshold', max(0, (float) ($_POST['alert_margin_threshold'] ?? 10)));
        $this->redirect_with_notice('erp-omd-settings', 'success', __('Ustawienia zostały zapisane.', 'erp-omd'));
    }

    private function handle_attachment_add()
    {
        $entity_type = sanitize_key((string) ($_POST['entity_type'] ?? ''));
        $entity_id = (int) ($_POST['entity_id'] ?? 0);
        $attachment_id = (int) ($_POST['attachment_id'] ?? 0);
        $label = sanitize_text_field(wp_unslash($_POST['label'] ?? ''));

        if (! in_array($entity_type, ['project', 'estimate'], true) || $entity_id <= 0 || $attachment_id <= 0) {
            wp_die(esc_html__('Niepoprawne dane załącznika.', 'erp-omd'));
        }

        check_admin_referer('erp_omd_add_attachment_' . $entity_type . '_' . $entity_id);
        $this->require_capability('erp_omd_manage_projects');

        $entity = $entity_type === 'project'
            ? $this->projects->find($entity_id)
            : $this->estimates->find($entity_id);
        if (! $entity || ! wp_attachment_is_image($attachment_id) && ! get_post($attachment_id)) {
            $this->redirect_with_notice($entity_type === 'project' ? 'erp-omd-projects' : 'erp-omd-estimates', 'error', __('Nie udało się dodać załącznika.', 'erp-omd'), ['id' => $entity_id]);
        }

        $this->attachments->create([
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'attachment_id' => $attachment_id,
            'label' => $label,
            'created_by_user_id' => get_current_user_id(),
        ]);

        $this->redirect_with_notice($entity_type === 'project' ? 'erp-omd-projects' : 'erp-omd-estimates', 'success', __('Załącznik został dodany.', 'erp-omd'), ['id' => $entity_id]);
    }

    private function handle_attachment_delete()
    {
        $attachment_relation_id = (int) ($_POST['attachment_relation_id'] ?? 0);
        $attachment = $attachment_relation_id ? $this->attachments->find($attachment_relation_id) : null;
        if (! $attachment) {
            wp_die(esc_html__('Nie znaleziono relacji załącznika.', 'erp-omd'));
        }

        check_admin_referer('erp_omd_delete_attachment_' . $attachment_relation_id);
        $this->require_capability('erp_omd_manage_projects');
        $this->attachments->delete($attachment_relation_id);

        $page = ($attachment['entity_type'] ?? '') === 'project' ? 'erp-omd-projects' : 'erp-omd-estimates';
        $this->redirect_with_notice($page, 'success', __('Załącznik został usunięty.', 'erp-omd'), ['id' => (int) ($attachment['entity_id'] ?? 0)]);
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
        $employee_metrics = [];
        $totals = [
            'reported_hours' => 0.0,
            'hourly_cost_total' => 0.0,
            'employee_profit' => 0.0,
            'active_employees' => 0,
        ];

        $time_entries = $this->time_entries->all();
        foreach ($time_entries as $time_entry) {
            $entry_date = (string) ($time_entry['entry_date'] ?? '');
            $status = (string) ($time_entry['status'] ?? '');
            if (strpos($entry_date, $reporting_month) !== 0 || $status !== 'approved') {
                continue;
            }

            $employee_id = (int) ($time_entry['employee_id'] ?? 0);
            $hours = (float) ($time_entry['hours'] ?? 0);
            $revenue = $hours * (float) ($time_entry['rate_snapshot'] ?? 0);
            $cost = $hours * (float) ($time_entry['cost_snapshot'] ?? 0);

            if (! isset($employee_metrics[$employee_id])) {
                $employee_metrics[$employee_id] = [
                    'reported_hours' => 0.0,
                    'hourly_cost_total' => 0.0,
                    'employee_profit' => 0.0,
                ];
            }

            $employee_metrics[$employee_id]['reported_hours'] += $hours;
            $employee_metrics[$employee_id]['hourly_cost_total'] += $cost;
            $employee_metrics[$employee_id]['employee_profit'] += $revenue - $cost;
            $totals['reported_hours'] += $hours;
            $totals['hourly_cost_total'] += $cost;
            $totals['employee_profit'] += $revenue - $cost;
        }

        foreach ($employee_metrics as &$employee_metric_row) {
            $employee_metric_row['reported_hours'] = round($employee_metric_row['reported_hours'], 2);
            $employee_metric_row['hourly_cost_total'] = round($employee_metric_row['hourly_cost_total'], 2);
            $employee_metric_row['employee_profit'] = round($employee_metric_row['employee_profit'], 2);
        }
        unset($employee_metric_row);

        $totals['reported_hours'] = round($totals['reported_hours'], 2);
        $totals['hourly_cost_total'] = round($totals['hourly_cost_total'], 2);
        $totals['employee_profit'] = round($totals['employee_profit'], 2);
        $totals['active_employees'] = count($employee_metrics);

        return [
            'employees' => $employee_metrics,
            'totals' => $totals,
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

    private function index_alerts_by_entity($entity_type)
    {
        $alerts = [];
        foreach ($this->alert_service->all_alerts() as $alert) {
            if (($alert['entity_type'] ?? '') !== $entity_type) {
                continue;
            }

            $entity_id = (int) ($alert['entity_id'] ?? 0);
            if (! isset($alerts[$entity_id])) {
                $alerts[$entity_id] = [];
            }
            $alerts[$entity_id][] = $alert;
        }

        return $alerts;
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

    private function status_badge_class($status, $type = 'default')
    {
        switch ($type) {
            case 'active':
                return (string) $status === 'inactive' ? 'erp-omd-badge-muted' : 'erp-omd-badge-success';
            case 'time':
                if ((string) $status === 'approved') {
                    return 'erp-omd-badge-success';
                }
                if ((string) $status === 'rejected') {
                    return 'erp-omd-badge-error';
                }

                return 'erp-omd-badge-warning';
            case 'project':
                if ((string) $status === 'zakonczony') {
                    return 'erp-omd-badge-success';
                }
                if ((string) $status === 'inactive') {
                    return 'erp-omd-badge-muted';
                }
                if (in_array((string) $status, ['do_faktury', 'w_akceptacji'], true)) {
                    return 'erp-omd-badge-warning';
                }

                return 'erp-omd-badge-info';
            case 'estimate':
                if ((string) $status === 'zaakceptowany') {
                    return 'erp-omd-badge-success';
                }
                if ((string) $status === 'do_akceptacji') {
                    return 'erp-omd-badge-warning';
                }

                return 'erp-omd-badge-info';
            default:
                return 'erp-omd-badge-info';
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
