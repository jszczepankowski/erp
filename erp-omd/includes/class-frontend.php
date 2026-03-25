<?php

class ERP_OMD_Frontend
{
    private $employees;
    private $clients;
    private $projects;
    private $roles;
    private $time_entries;
    private $project_requests;
    private $estimates;
    private $estimate_items;
    private $project_costs;
    private $time_entry_service;
    private $client_project_service;
    private $project_request_service;
    private $estimate_service;
    private $project_financial_service;
    private $reporting_service;
    private $alert_service;

    public function __construct(
        ERP_OMD_Employee_Repository $employees,
        ERP_OMD_Client_Repository $clients,
        ERP_OMD_Project_Repository $projects,
        ERP_OMD_Role_Repository $roles,
        ERP_OMD_Time_Entry_Repository $time_entries,
        ERP_OMD_Project_Request_Repository $project_requests,
        ERP_OMD_Estimate_Repository $estimates,
        ERP_OMD_Estimate_Item_Repository $estimate_items,
        ERP_OMD_Project_Cost_Repository $project_costs,
        ERP_OMD_Time_Entry_Service $time_entry_service,
        ERP_OMD_Client_Project_Service $client_project_service,
        ERP_OMD_Project_Request_Service $project_request_service,
        ERP_OMD_Estimate_Service $estimate_service,
        ERP_OMD_Project_Financial_Service $project_financial_service,
        ERP_OMD_Reporting_Service $reporting_service,
        ERP_OMD_Alert_Service $alert_service
    ) {
        $this->employees = $employees;
        $this->clients = $clients;
        $this->projects = $projects;
        $this->roles = $roles;
        $this->time_entries = $time_entries;
        $this->project_requests = $project_requests;
        $this->estimates = $estimates;
        $this->estimate_items = $estimate_items;
        $this->project_costs = $project_costs;
        $this->time_entry_service = $time_entry_service;
        $this->client_project_service = $client_project_service;
        $this->project_request_service = $project_request_service;
        $this->estimate_service = $estimate_service;
        $this->project_financial_service = $project_financial_service;
        $this->reporting_service = $reporting_service;
        $this->alert_service = $alert_service;
    }

    public static function register_rewrite_rules()
    {
        add_rewrite_tag('%erp_omd_front%', '([^&]+)');
        add_rewrite_rule('^erp-front/?$', 'index.php?erp_omd_front=home', 'top');
        add_rewrite_rule('^erp-front/login/?$', 'index.php?erp_omd_front=login', 'top');
        add_rewrite_rule('^erp-front/logout/?$', 'index.php?erp_omd_front=logout', 'top');
        add_rewrite_rule('^erp-front/worker/?$', 'index.php?erp_omd_front=worker', 'top');
        add_rewrite_rule('^erp-front/manager/?$', 'index.php?erp_omd_front=manager', 'top');
    }

    public function register_hooks()
    {
        add_action('init', [__CLASS__, 'register_rewrite_rules']);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_action('template_redirect', [$this, 'handle_front_request']);
        add_action('admin_init', [$this, 'redirect_front_users_from_admin']);
        add_filter('login_redirect', [$this, 'filter_login_redirect'], 10, 3);
    }

    public function redirect_front_users_from_admin()
    {
        if (! is_user_logged_in()) {
            return;
        }

        if ((function_exists('wp_doing_ajax') && wp_doing_ajax()) || (defined('DOING_AJAX') && DOING_AJAX)) {
            return;
        }

        if ((function_exists('wp_doing_cron') && wp_doing_cron()) || (defined('DOING_CRON') && DOING_CRON)) {
            return;
        }

        $user = wp_get_current_user();
        if (! $this->should_hide_admin_for_user($user)) {
            return;
        }

        wp_safe_redirect($this->resolve_dashboard_url_for_user($user));
        exit;
    }

    public function filter_login_redirect($redirect_to, $requested_redirect_to, $user)
    {
        if ($user instanceof WP_User && $this->should_hide_admin_for_user($user)) {
            return $this->resolve_dashboard_url_for_user($user);
        }

        return $redirect_to;
    }

    public function register_query_vars($query_vars)
    {
        $query_vars[] = 'erp_omd_front';

        return $query_vars;
    }

    public function handle_front_request()
    {
        $screen = get_query_var('erp_omd_front');
        if (! is_string($screen) || $screen === '') {
            return;
        }

        if ($screen === 'logout') {
            $this->handle_logout();
            return;
        }

        if ($screen === 'login') {
            $this->handle_login_screen();
            return;
        }

        if (! is_user_logged_in()) {
            $this->redirect_to_login($this->front_url($screen));
        }

        $current_user = wp_get_current_user();

        if ($screen === 'home') {
            wp_safe_redirect($this->resolve_dashboard_url_for_user($current_user));
            exit;
        }

        $this->guard_dashboard_access($screen, $current_user);

        if ($screen === 'worker') {
            $this->handle_worker_screen($current_user);
            return;
        }

        $this->handle_manager_screen($current_user);
    }

    public function front_url($screen = 'home', array $args = [])
    {
        $path = 'erp-front';
        if ($screen !== 'home') {
            $path .= '/' . rawurlencode($screen);
        }

        return add_query_arg($args, home_url('/' . $path . '/'));
    }

    private function handle_login_screen()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['erp_omd_front_action']) && wp_unslash($_POST['erp_omd_front_action']) === 'login') {
            $this->process_login();
            return;
        }

        if (is_user_logged_in()) {
            wp_safe_redirect($this->resolve_dashboard_url_for_user(wp_get_current_user()));
            exit;
        }

        $this->render_login_screen();
    }

    private function process_login()
    {
        check_admin_referer('erp_omd_front_login');

        $creds = [
            'user_login' => sanitize_user((string) wp_unslash($_POST['log'] ?? '')),
            'user_password' => (string) wp_unslash($_POST['pwd'] ?? ''),
            'remember' => ! empty($_POST['rememberme']),
        ];
        $redirect_to = esc_url_raw((string) wp_unslash($_POST['redirect_to'] ?? ''));

        $user = wp_signon($creds, is_ssl());
        if (is_wp_error($user)) {
            $this->render_login_screen($user);
            return;
        }

        $destination = $this->is_front_url($redirect_to) ? $redirect_to : $this->resolve_dashboard_url_for_user($user);
        wp_safe_redirect($destination);
        exit;
    }

    private function handle_logout()
    {
        wp_logout();
        wp_safe_redirect($this->front_url('login', ['logged_out' => 1]));
        exit;
    }

    private function redirect_to_login($redirect_to = '')
    {
        $args = [];
        if ($redirect_to !== '') {
            $args['redirect_to'] = $redirect_to;
        }

        wp_safe_redirect($this->front_url('login', $args));
        exit;
    }

    private function guard_dashboard_access($screen, WP_User $user)
    {
        if ($screen === 'manager' && ! user_can($user, 'erp_omd_front_manager')) {
            wp_safe_redirect($this->resolve_dashboard_url_for_user($user));
            exit;
        }

        if ($screen === 'worker' && ! user_can($user, 'erp_omd_front_worker') && ! user_can($user, 'erp_omd_front_manager')) {
            wp_safe_redirect($this->front_url('login', ['denied' => 1]));
            exit;
        }
    }

    private function should_hide_admin_for_user($user)
    {
        if (! $user instanceof WP_User || ! $user->exists()) {
            return false;
        }

        if (! (bool) get_option('erp_omd_front_admin_redirect_enabled', true)) {
            return false;
        }

        if (user_can($user, 'administrator')) {
            return false;
        }

        return user_can($user, 'erp_omd_front_manager') || user_can($user, 'erp_omd_front_worker');
    }

    private function resolve_dashboard_url_for_user($user)
    {
        if (! $user instanceof WP_User || ! $user->exists()) {
            return $this->front_url('login');
        }

        if (user_can($user, 'erp_omd_front_manager')) {
            return $this->front_url('manager');
        }

        if (user_can($user, 'erp_omd_front_worker')) {
            return $this->front_url('worker');
        }

        return admin_url();
    }

    private function render_login_screen($error = null)
    {
        $redirect_to = isset($_GET['redirect_to']) ? esc_url_raw((string) wp_unslash($_GET['redirect_to'])) : '';
        $logged_out = ! empty($_GET['logged_out']);
        $denied = ! empty($_GET['denied']);
        $front_login_url = $this->front_url('login');
        $front_brand_label = __('ERP OMD FRONT', 'erp-omd');
        $front_login_logo_url = '';
        $front_login_cover_url = '';
        $front_login_logo_id = (int) get_option('erp_omd_front_login_logo_id', 0);
        $front_login_cover_id = (int) get_option('erp_omd_front_login_cover_id', 0);

        if ($front_login_logo_id > 0) {
            $front_login_logo_url = (string) wp_get_attachment_image_url($front_login_logo_id, 'medium');
        }

        if ($front_login_cover_id > 0) {
            $front_login_cover_url = (string) wp_get_attachment_image_url($front_login_cover_id, 'large');
        }

        $this->send_front_headers();
        include ERP_OMD_PATH . 'templates/front/login.php';
        exit;
    }

    private function handle_worker_screen(WP_User $user)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->process_worker_request($user);
            return;
        }

        $this->render_worker_dashboard($user);
    }

    private function handle_manager_screen(WP_User $user)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->process_manager_request($user);
            return;
        }

        $this->render_manager_dashboard($user);
    }

    private function process_worker_request(WP_User $user)
    {
        check_admin_referer('erp_omd_front_worker');

        $action = sanitize_text_field(wp_unslash($_POST['erp_omd_front_action'] ?? ''));
        if ($action === 'save_time_entry') {
            $this->save_worker_time_entry($user);
            return;
        }

        if ($action === 'delete_time_entry') {
            $this->delete_worker_time_entry($user);
            return;
        }
        if ($action === 'create_project_request') {
            $this->create_worker_project_request($user);
            return;
        }

        $this->redirect_worker_with_notice('error', __('Nieobsługiwana akcja formularza FRONT.', 'erp-omd'));
    }

    private function process_manager_request(WP_User $user)
    {
        check_admin_referer('erp_omd_front_manager');

        $action = sanitize_text_field(wp_unslash($_POST['erp_omd_front_action'] ?? ''));
        if ($action === 'create_estimate') {
            $this->create_manager_estimate($user);
            return;
        }
        if ($action === 'update_estimate_status') {
            $this->update_manager_estimate_status($user);
            return;
        }
        if ($action === 'update_estimate_item_inline') {
            $this->update_manager_estimate_item_inline($user);
            return;
        }
        if ($action === 'update_project_status') {
            $this->update_manager_project_status($user);
            return;
        }
        if ($action === 'add_project_cost') {
            $this->add_manager_project_cost($user);
            return;
        }
        if ($action === 'accept_estimate') {
            $this->accept_manager_estimate($user);
            return;
        }
        if ($action === 'export_estimate_csv') {
            $this->export_manager_estimate_csv($user);
            return;
        }
        if ($action === 'create_project_request') {
            $this->create_manager_project_request($user);
            return;
        }
        if (in_array($action, ['review_project_request', 'approve_project_request', 'reject_project_request', 'convert_project_request'], true)) {
            $this->process_project_request_action($user, $action);
            return;
        }
        if ($action === 'approve_time_entry' || $action === 'reject_time_entry') {
            $this->change_manager_time_entry_status($user, $action === 'approve_time_entry' ? 'approved' : 'rejected');
            return;
        }

        $this->redirect_manager_with_notice('error', __('Nieobsługiwana akcja formularza managera.', 'erp-omd'));
    }

    private function save_worker_time_entry(WP_User $user)
    {
        $employee = $this->employees->find_by_user_id((int) $user->ID);
        if (! $employee) {
            $this->redirect_worker_with_notice('error', __('Nie znaleziono profilu pracownika dla bieżącego użytkownika.', 'erp-omd'));
        }

        $entry_id = (int) ($_POST['id'] ?? 0);
        $client_id = (int) ($_POST['client_id'] ?? 0);
        $project_id = (int) ($_POST['project_id'] ?? 0);
        $role_id = (int) ($_POST['role_id'] ?? 0);
        $selected_date = sanitize_text_field(wp_unslash($_POST['selected_date'] ?? ''));
        $selected_day_args = preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date) ? ['selected_date' => $selected_date] : [];
        $payload = [
            'employee_id' => (int) $employee['id'],
            'project_id' => $project_id,
            'role_id' => $role_id,
            'hours' => (float) ($_POST['hours'] ?? 0),
            'entry_date' => sanitize_text_field(wp_unslash($_POST['entry_date'] ?? '')),
            'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
            'status' => 'submitted',
            'created_by_user_id' => (int) $user->ID,
            'approved_by_user_id' => 0,
            'approved_at' => null,
        ];

        $selected_project = $this->projects->find($project_id);
        if (! $selected_project || (string) ($selected_project['status'] ?? '') !== 'w_realizacji') {
            $this->redirect_worker_with_notice(
                'error',
                __('Możesz raportować czas tylko do aktywnych projektów w realizacji.', 'erp-omd'),
                array_merge($entry_id ? ['entry_id' => $entry_id] : [], $client_id > 0 ? ['client_id' => $client_id] : [], $selected_day_args)
            );
        }
        if ($client_id > 0 && (int) ($selected_project['client_id'] ?? 0) !== $client_id) {
            $this->redirect_worker_with_notice(
                'error',
                __('Wybrany projekt nie należy do wskazanego klienta.', 'erp-omd'),
                array_merge($entry_id ? ['entry_id' => $entry_id] : [], ['client_id' => $client_id], $selected_day_args)
            );
        }

        $allowed_role_ids = wp_list_pluck($this->get_worker_roles($employee), 'id');
        if (! in_array($role_id, array_map('intval', $allowed_role_ids), true)) {
            $this->redirect_worker_with_notice(
                'error',
                __('Wybrana rola nie jest dostępna dla tego pracownika.', 'erp-omd'),
                array_merge($entry_id ? ['entry_id' => $entry_id] : [], $client_id > 0 ? ['client_id' => $client_id] : [], $selected_day_args)
            );
        }

        if ($entry_id) {
            $existing = $this->time_entries->find($entry_id);
            if (! $existing || ! $this->time_entry_service->can_edit_entry($existing, $user)) {
                $this->redirect_worker_with_notice(
                    'error',
                    __('Możesz edytować tylko własne wpisy ze statusem submitted.', 'erp-omd'),
                    array_merge($client_id > 0 ? ['client_id' => $client_id] : [], $selected_day_args)
                );
            }
        }

        $payload = $this->time_entry_service->prepare($payload);
        $errors = $this->time_entry_service->validate($payload, $entry_id ?: null);
        if ($errors) {
            $this->redirect_worker_with_notice(
                'error',
                implode(' ', $errors),
                array_merge($entry_id ? ['entry_id' => $entry_id] : [], $client_id > 0 ? ['client_id' => $client_id] : [], $selected_day_args)
            );
        }
        if ($entry_id) {
            $this->time_entries->update($entry_id, $payload);
            $message = __('Wpis czasu został zaktualizowany.', 'erp-omd');
        } else {
            $entry_id = $this->time_entries->create($payload);
            if ($entry_id <= 0) {
                $this->redirect_worker_with_notice(
                    'error',
                    __('Nie udało się zapisać wpisu czasu. Sprawdź, czy podobny wpis nie istnieje już w systemie.', 'erp-omd'),
                    array_merge($client_id > 0 ? ['client_id' => $client_id] : [], $selected_day_args)
                );
            }
            $message = __('Wpis czasu został dodany.', 'erp-omd');
        }

        $this->project_financial_service->rebuild_for_project($project_id);
        $this->redirect_worker_with_notice('success', $message, $selected_day_args);
    }

    private function delete_worker_time_entry(WP_User $user)
    {
        $entry_id = (int) ($_POST['id'] ?? 0);
        $selected_date = sanitize_text_field(wp_unslash($_POST['selected_date'] ?? ''));
        $selected_day_args = preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date) ? ['selected_date' => $selected_date] : [];
        $entry = $entry_id ? $this->time_entries->find($entry_id) : null;
        if (! $entry) {
            $this->redirect_worker_with_notice('error', __('Nie znaleziono wpisu czasu do usunięcia.', 'erp-omd'), $selected_day_args);
        }

        if (! $this->time_entry_service->can_delete_entry($user, $entry)) {
            $this->redirect_worker_with_notice('error', __('Możesz usuwać tylko własne wpisy ze statusem submitted.', 'erp-omd'), $selected_day_args);
        }

        $this->time_entries->delete($entry_id);
        $this->project_financial_service->rebuild_for_project((int) $entry['project_id']);
        $this->redirect_worker_with_notice('success', __('Wpis czasu został usunięty.', 'erp-omd'), $selected_day_args);
    }

    private function render_worker_dashboard(WP_User $user)
    {
        $employee = $this->employees->find_by_user_id((int) $user->ID);
        if (! $employee) {
            $this->redirect_worker_with_notice('error', __('Nie znaleziono profilu pracownika dla bieżącego użytkownika.', 'erp-omd'));
        }

        $worker_filters = [
            'client_id' => (int) ($_GET['client_id'] ?? 0),
            'project_id' => (int) ($_GET['project_id'] ?? 0),
            'status' => sanitize_text_field(wp_unslash($_GET['status'] ?? '')),
            'entry_date' => sanitize_text_field(wp_unslash($_GET['entry_date'] ?? '')),
            'focus' => sanitize_key(wp_unslash($_GET['focus'] ?? 'month')),
            'calendar_month' => sanitize_text_field(wp_unslash($_GET['calendar_month'] ?? gmdate('Y-m'))),
            'selected_date' => sanitize_text_field(wp_unslash($_GET['selected_date'] ?? '')),
        ];
        if ($worker_filters['project_id'] <= 0) {
            $worker_filters['project_id'] = 0;
        }
        if ($worker_filters['client_id'] <= 0) {
            $worker_filters['client_id'] = 0;
        }
        if (! in_array($worker_filters['status'], ['', 'submitted', 'approved', 'rejected'], true)) {
            $worker_filters['status'] = '';
        }
        if (! in_array($worker_filters['focus'], ['all', 'today', 'week', 'month'], true)) {
            $worker_filters['focus'] = 'month';
        }
        if (! preg_match('/^\d{4}-\d{2}$/', $worker_filters['calendar_month'])) {
            $worker_filters['calendar_month'] = gmdate('Y-m');
        }
        if ($worker_filters['selected_date'] !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $worker_filters['selected_date'])) {
            $worker_filters['selected_date'] = '';
        }

        $entry_id = (int) ($_GET['entry_id'] ?? 0);
        $entry = $entry_id ? $this->time_entries->find($entry_id) : null;
        $editable_entry = $entry && $this->time_entry_service->can_edit_entry($entry, $user) ? $entry : null;

        $available_projects = array_values(
            array_filter(
                $this->projects->all(),
                function ($project) {
                    return (string) ($project['status'] ?? '') === 'w_realizacji';
                }
            )
        );
        $available_clients = [];
        foreach ($available_projects as $project) {
            $client_id = (int) ($project['client_id'] ?? 0);
            if ($client_id <= 0 || isset($available_clients[$client_id])) {
                continue;
            }

            $available_clients[$client_id] = [
                'id' => $client_id,
                'name' => (string) ($project['client_name'] ?? ('#' . $client_id)),
            ];
        }
        $available_clients = array_values($available_clients);
        $available_roles = $this->get_worker_roles($employee);
        $worker_request_available_clients = array_values(
            array_filter(
                $this->clients->all(),
                static function ($client_row) {
                    return (string) ($client_row['status'] ?? '') === 'active';
                }
            )
        );
        $worker_request_available_managers = array_values(
            array_filter(
                $this->employees->all(),
                static function ($employee_row) {
                    return (string) ($employee_row['account_type'] ?? '') === 'manager'
                        && (string) ($employee_row['status'] ?? '') === 'active';
                }
            )
        );
        $recent_entry_templates = $this->build_recent_worker_templates(
            $this->time_entry_service->filter_visible_entries(
                $this->time_entries->all([
                    'employee_id' => (int) $employee['id'],
                ]),
                $user
            )
        );
        $time_entries = $this->time_entries->all(array_filter([
            'employee_id' => (int) $employee['id'],
            'project_id' => $worker_filters['project_id'],
            'status' => $worker_filters['status'],
            'entry_date' => $worker_filters['entry_date'],
        ]));
        $time_entries = $this->time_entry_service->filter_visible_entries($time_entries, $user);
        if ($worker_filters['client_id'] > 0) {
            $time_entries = array_values(
                array_filter(
                    $time_entries,
                    static function ($entry) use ($worker_filters) {
                        return (int) ($entry['client_id'] ?? 0) === (int) $worker_filters['client_id'];
                    }
                )
            );
        }
        $time_entries = $this->filter_worker_entries_by_focus($time_entries, $worker_filters);

        $calendar_data = $this->reporting_service->build_calendar([
            'employee_id' => (int) $employee['id'],
            'client_id' => $worker_filters['client_id'],
            'project_id' => $worker_filters['project_id'],
            'status' => $worker_filters['status'],
            'month' => $worker_filters['calendar_month'],
            'report_type' => 'monthly',
            'tab' => 'calendar',
        ]);
        $calendar_navigation = $this->get_calendar_navigation($worker_filters['calendar_month'], $worker_filters);
        $selected_day = $this->resolve_selected_day($worker_filters, $calendar_data);
        $selected_day_entries = $selected_day !== ''
            ? $this->load_selected_day_entries((int) $employee['id'], $selected_day, $worker_filters, $user)
            : [];
        $selected_day_totals = $this->summarize_selected_day_entries($selected_day_entries);

        $status_totals = [
            'submitted' => 0,
            'approved' => 0,
            'rejected' => 0,
        ];
        $hours_total = 0.0;
        foreach ($time_entries as $time_entry) {
            $hours_total += (float) ($time_entry['hours'] ?? 0);
            $status = (string) ($time_entry['status'] ?? '');
            if (isset($status_totals[$status])) {
                $status_totals[$status]++;
            }
        }

        $worker_notice_type = sanitize_key(wp_unslash($_GET['notice'] ?? ''));
        $worker_notice_message = sanitize_text_field(wp_unslash($_GET['message'] ?? ''));
        if (! in_array($worker_notice_type, ['', 'success', 'error', 'warning'], true)) {
            $worker_notice_type = '';
            $worker_notice_message = '';
        }

        $worker_form_defaults = $editable_entry ?: [
            'id' => 0,
            'client_id' => (int) ($_GET['client_id'] ?? 0),
            'project_id' => 0,
            'role_id' => 0,
            'hours' => '',
            'entry_date' => $selected_day ?: gmdate('Y-m-d'),
            'description' => '',
            'status' => 'submitted',
        ];
        if (! isset($worker_form_defaults['client_id']) || (int) $worker_form_defaults['client_id'] <= 0) {
            $selected_project_for_form = $this->find_project_in_collection($available_projects, (int) ($worker_form_defaults['project_id'] ?? 0));
            $worker_form_defaults['client_id'] = (int) ($selected_project_for_form['client_id'] ?? 0);
        }
        $worker_request_defaults = [
            'client_id' => 0,
            'project_name' => '',
            'billing_type' => 'time_material',
            'preferred_manager_id' => 0,
            'brief' => '',
        ];

        $dashboard_title = __('Panel pracownika', 'erp-omd');
        $dashboard_intro = __('FRONT-2 udostępnia pracownikowi własne raportowanie czasu: szybki formularz, listę wpisów, filtry i operacje tylko na własnych draftach submitted.', 'erp-omd');
        $front_logout_url = $this->front_url('logout');
        $front_worker_url = $this->front_url('worker');
        $front_manager_url = $this->front_url('manager');
        $front_brand_label = __('ERP OMD FRONT', 'erp-omd');
        $worker_form_action = $this->front_url('worker');

        $this->send_front_headers();
        include ERP_OMD_PATH . 'templates/front/worker-dashboard.php';
        exit;
    }

    private function build_recent_worker_templates(array $entries)
    {
        $templates = [];
        $seen = [];

        foreach ($entries as $entry) {
            $key = (int) ($entry['project_id'] ?? 0);
            if ($key <= 0) {
                continue;
            }

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $templates[] = [
                'client_id' => (int) ($entry['client_id'] ?? 0),
                'project_id' => (int) ($entry['project_id'] ?? 0),
                'project_name' => (string) ($entry['project_name'] ?? '—'),
                'role_id' => (int) ($entry['role_id'] ?? 0),
                'role_name' => (string) ($entry['role_name'] ?? '—'),
                'hours' => round((float) ($entry['hours'] ?? 0), 2),
                'description' => trim((string) ($entry['description'] ?? '')),
            ];

            if (count($templates) === 5) {
                break;
            }
        }

        return $templates;
    }

    private function filter_worker_entries_by_focus(array $entries, array $worker_filters)
    {
        if (! empty($worker_filters['entry_date'])) {
            return $entries;
        }

        $focus = (string) ($worker_filters['focus'] ?? 'month');
        if ($focus === 'all') {
            return $entries;
        }

        $today = new DateTimeImmutable(current_time('Y-m-d'));
        if ($focus === 'today') {
            $start = $today;
            $end = $today;
        } elseif ($focus === 'week') {
            $start = $today->modify('monday this week');
            $end = $start->modify('+6 days');
        } else {
            $month = (string) ($worker_filters['calendar_month'] ?? gmdate('Y-m'));
            $start = new DateTimeImmutable($month . '-01');
            $end = $start->modify('last day of this month');
        }

        return array_values(
            array_filter(
                $entries,
                function ($entry) use ($start, $end) {
                    $entry_date = DateTimeImmutable::createFromFormat('Y-m-d', (string) ($entry['entry_date'] ?? ''));
                    if (! $entry_date) {
                        return false;
                    }

                    return $entry_date >= $start && $entry_date <= $end;
                }
            )
        );
    }

    private function get_calendar_navigation($calendar_month, array $worker_filters)
    {
        $month = new DateTimeImmutable($calendar_month . '-01');
        $previous_month = $month->modify('-1 month')->format('Y-m');
        $next_month = $month->modify('+1 month')->format('Y-m');
        $base_args = [
            'client_id' => $worker_filters['client_id'],
            'project_id' => $worker_filters['project_id'],
            'status' => $worker_filters['status'],
            'focus' => $worker_filters['focus'],
            'selected_date' => $worker_filters['selected_date'],
        ];

        return [
            'label' => wp_date('F Y', $month->getTimestamp()),
            'previous_url' => $this->front_url('worker', array_merge($base_args, ['calendar_month' => $previous_month])),
            'next_url' => $this->front_url('worker', array_merge($base_args, ['calendar_month' => $next_month])),
        ];
    }

    private function resolve_selected_day(array $worker_filters, array $calendar_data)
    {
        if ($worker_filters['selected_date'] !== '') {
            return $worker_filters['selected_date'];
        }

        if ($worker_filters['entry_date'] !== '') {
            return $worker_filters['entry_date'];
        }

        $today = current_time('Y-m-d');
        if (strpos($today, $worker_filters['calendar_month']) === 0) {
            return $today;
        }

        foreach ((array) ($calendar_data['weeks'] ?? []) as $week) {
            foreach ((array) $week as $day) {
                if (is_array($day) && ! empty($day['date'])) {
                    return (string) $day['date'];
                }
            }
        }

        return '';
    }

    private function load_selected_day_entries($employee_id, $selected_day, array $worker_filters, WP_User $user)
    {
        $entries = $this->time_entries->all(array_filter([
            'employee_id' => $employee_id,
            'project_id' => $worker_filters['project_id'],
            'status' => $worker_filters['status'],
            'entry_date' => $selected_day,
        ]));

        $entries = $this->time_entry_service->filter_visible_entries($entries, $user);
        if ($worker_filters['client_id'] <= 0) {
            return $entries;
        }

        return array_values(
            array_filter(
                $entries,
                static function ($entry) use ($worker_filters) {
                    return (int) ($entry['client_id'] ?? 0) === (int) $worker_filters['client_id'];
                }
            )
        );
    }

    private function summarize_selected_day_entries(array $entries)
    {
        $summary = [
            'hours' => 0.0,
            'submitted' => 0,
            'approved' => 0,
            'rejected' => 0,
        ];

        foreach ($entries as $entry) {
            $summary['hours'] += (float) ($entry['hours'] ?? 0);
            $status = (string) ($entry['status'] ?? '');
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        $summary['hours'] = round($summary['hours'], 2);

        return $summary;
    }

    private function render_manager_dashboard(WP_User $user)
    {
        $employee = $this->employees->find_by_user_id((int) $user->ID);
        if (! $employee) {
            $this->redirect_to_login($this->front_url('manager'));
        }

        $managed_projects = $this->load_managed_projects((int) $employee['id'], user_can($user, 'administrator'));
        $managed_project_ids = array_map('intval', wp_list_pluck($managed_projects, 'id'));
        $selected_project_id = (int) ($_GET['project_id'] ?? 0);
        $selected_request_id = (int) ($_GET['request_id'] ?? 0);
        if ($selected_project_id > 0 && ! in_array($selected_project_id, $managed_project_ids, true)) {
            $selected_project_id = 0;
        }
        if ($selected_project_id <= 0) {
            $selected_project_id = (int) ($managed_projects[0]['id'] ?? 0);
        }

        $selected_project = $selected_project_id > 0 ? $this->find_project_in_collection($managed_projects, $selected_project_id) : null;
        $linked_estimates = $this->load_estimates_for_projects($managed_projects);
        $manager_estimates = $this->load_visible_manager_estimates((int) $employee['id'], $managed_projects, user_can($user, 'administrator'));
        $selected_estimate_id = (int) ($_GET['estimate_id'] ?? 0);
        $visible_estimate_ids = array_map('intval', wp_list_pluck($manager_estimates, 'id'));
        if ($selected_estimate_id > 0 && ! in_array($selected_estimate_id, $visible_estimate_ids, true)) {
            $selected_estimate_id = 0;
        }
        if ($selected_estimate_id <= 0) {
            $selected_estimate_id = (int) ($linked_estimates[0]['id'] ?? $manager_estimates[0]['id'] ?? 0);
        }
        $selected_estimate = $selected_estimate_id > 0 ? $this->find_estimate_in_collection($manager_estimates, $selected_estimate_id) : null;
        $approval_queue = $this->load_manager_approval_queue($managed_project_ids, $selected_project_id);
        $queue_summary = $this->summarize_queue_entries($approval_queue);
        $project_requests = $this->load_visible_project_requests((int) $employee['id'], $user);
        $selected_request = $selected_request_id > 0 ? $this->find_request_in_collection($project_requests, $selected_request_id) : null;
        $request_form_defaults = $selected_request ?: [
            'client_id' => 0,
            'project_name' => '',
            'billing_type' => 'time_material',
            'preferred_manager_id' => (int) $employee['id'],
            'estimate_id' => 0,
            'brief' => '',
        ];
        $available_clients = $this->get_manager_available_clients((int) $employee['id'], user_can($user, 'administrator'));
        $available_managers = array_values(
            array_filter(
                $this->employees->all(),
                function ($employee_row) {
                    return (string) ($employee_row['account_type'] ?? '') === 'manager'
                        && (string) ($employee_row['status'] ?? '') === 'active';
                }
            )
        );
        $available_estimates = $manager_estimates;
        $manager_notice_type = sanitize_key(wp_unslash($_GET['notice'] ?? ''));
        $manager_notice_message = sanitize_text_field(wp_unslash($_GET['message'] ?? ''));
        if (! in_array($manager_notice_type, ['', 'success', 'error', 'warning'], true)) {
            $manager_notice_type = '';
            $manager_notice_message = '';
        }

        $dashboard_metrics = [
            'projects_count' => count($managed_projects),
            'queue_count' => count($approval_queue),
            'submitted_hours' => $queue_summary['hours'],
            'linked_estimates_count' => count($manager_estimates),
            'project_requests_count' => count($project_requests),
        ];
        $dashboard_title = __('Panel managera', 'erp-omd');
        $dashboard_intro = __('FRONT-3 daje managerowi operacyjny przegląd własnych projektów: finanse, alerty, powiązane kosztorysy i kolejkę wpisów czasu do szybkiej akceptacji.', 'erp-omd');
        $front_logout_url = $this->front_url('logout');
        $front_worker_url = $this->front_url('worker');
        $front_manager_url = $this->front_url('manager');
        $front_brand_label = __('ERP OMD FRONT', 'erp-omd');
        $manager_form_action = $this->front_url('manager');

        $this->send_front_headers();
        include ERP_OMD_PATH . 'templates/front/dashboard.php';
        exit;
    }

    private function change_manager_time_entry_status(WP_User $user, $status)
    {
        $entry_id = (int) ($_POST['id'] ?? 0);
        $project_id = (int) ($_POST['project_id'] ?? 0);
        $entry = $entry_id > 0 ? $this->time_entries->find($entry_id) : null;
        if (! $entry) {
            $this->redirect_manager_with_notice('error', __('Nie znaleziono wpisu czasu do aktualizacji.', 'erp-omd'), $project_id > 0 ? ['project_id' => $project_id] : []);
        }

        if (! $this->time_entry_service->can_approve_entry($entry, $user)) {
            $this->redirect_manager_with_notice('error', __('Możesz zmieniać status tylko wpisów przypisanych do Twoich projektów.', 'erp-omd'), ['project_id' => (int) ($entry['project_id'] ?? $project_id)]);
        }

        $payload = array_merge(
            $entry,
            [
                'status' => $status,
                'approved_by_user_id' => (int) $user->ID,
                'approved_at' => current_time('mysql'),
            ]
        );

        $this->time_entries->update($entry_id, $payload);
        $this->project_financial_service->rebuild_for_project((int) $entry['project_id']);

        $message = $status === 'approved'
            ? __('Wpis czasu został zaakceptowany.', 'erp-omd')
            : __('Wpis czasu został odrzucony.', 'erp-omd');

        $this->redirect_manager_with_notice('success', $message, ['project_id' => (int) $entry['project_id']]);
    }

    private function create_manager_estimate(WP_User $user)
    {
        $employee = $this->employees->find_by_user_id((int) $user->ID);
        if (! $employee) {
            $this->redirect_manager_with_notice('error', __('Nie znaleziono profilu pracownika dla bieżącego użytkownika.', 'erp-omd'));
        }

        $client_id = (int) ($_POST['client_id'] ?? 0);
        $payload = [
            'client_id' => $client_id,
            'name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
            'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? 'wstepny')) ?: 'wstepny',
            'accepted_by_user_id' => 0,
            'accepted_at' => null,
        ];

        $line_items = $this->collect_manager_estimate_line_items();

        $visible_client_ids = array_map('intval', wp_list_pluck($this->get_manager_available_clients((int) $employee['id'], user_can($user, 'administrator')), 'id'));
        if (! in_array($client_id, $visible_client_ids, true)) {
            $this->redirect_manager_with_notice('error', __('Możesz tworzyć kosztorysy tylko dla aktywnych klientów przypisanych do Ciebie lub Twoich projektów.', 'erp-omd'));
        }

        $errors = $this->estimate_service->validate_estimate($payload);
        $errors = array_merge($errors, $this->validate_manager_estimate_line_items($line_items, $payload['status']));
        if ($errors) {
            $this->redirect_manager_with_notice('error', implode(' ', array_unique($errors)));
        }

        $estimate_id = $this->estimates->create($payload);
        if ($estimate_id <= 0) {
            $this->redirect_manager_with_notice('error', __('Nie udało się utworzyć kosztorysu.', 'erp-omd'));
        }

        foreach ($line_items as $line_item_payload) {
            $line_item_payload['estimate_id'] = $estimate_id;
            $this->estimate_items->create($line_item_payload);
        }

        $this->redirect_manager_with_notice('success', __('Kosztorys został utworzony.', 'erp-omd'), ['estimate_id' => $estimate_id]);
    }

    private function update_manager_estimate_status(WP_User $user)
    {
        $estimate_id = (int) ($_POST['estimate_id'] ?? 0);
        $estimate = $estimate_id > 0 ? $this->estimates->find($estimate_id) : null;
        if (! $estimate) {
            $this->redirect_manager_with_notice('error', __('Nie znaleziono kosztorysu.', 'erp-omd'));
        }

        $employee = $this->employees->find_by_user_id((int) $user->ID);
        if (! $employee) {
            $this->redirect_manager_with_notice('error', __('Nie znaleziono profilu pracownika dla bieżącego użytkownika.', 'erp-omd'));
        }

        $managed_projects = $this->load_managed_projects((int) $employee['id'], user_can($user, 'administrator'));
        $visible_estimate_ids = array_map('intval', wp_list_pluck($this->load_visible_manager_estimates((int) $employee['id'], $managed_projects, user_can($user, 'administrator')), 'id'));
        if (! in_array($estimate_id, $visible_estimate_ids, true)) {
            $this->redirect_manager_with_notice('error', __('Nie możesz edytować kosztorysów spoza własnego zakresu odpowiedzialności.', 'erp-omd'));
        }

        $payload = [
            'client_id' => (int) ($estimate['client_id'] ?? 0),
            'name' => (string) ($estimate['name'] ?? ''),
            'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? ($estimate['status'] ?? 'wstepny'))),
            'accepted_by_user_id' => (int) ($estimate['accepted_by_user_id'] ?? 0),
            'accepted_at' => $estimate['accepted_at'] ?? null,
        ];

        $errors = $this->estimate_service->validate_estimate($payload, $estimate);
        if ($errors) {
            $this->redirect_manager_with_notice('error', implode(' ', array_unique($errors)), ['estimate_id' => $estimate_id]);
        }

        $this->estimates->update($estimate_id, $payload);
        $this->redirect_manager_with_notice('success', __('Kosztorys został zaktualizowany.', 'erp-omd'), ['estimate_id' => $estimate_id]);
    }

    private function update_manager_estimate_item_inline(WP_User $user)
    {
        $estimate_id = (int) ($_POST['estimate_id'] ?? 0);
        $item_id = (int) ($_POST['item_id'] ?? 0);
        $estimate = $estimate_id > 0 ? $this->estimates->find($estimate_id) : null;
        $item = $item_id > 0 ? $this->estimate_items->find($item_id) : null;
        if (! $estimate || ! $item || (int) ($item['estimate_id'] ?? 0) !== $estimate_id) {
            $this->redirect_manager_with_notice('error', __('Nie znaleziono pozycji kosztorysu do aktualizacji.', 'erp-omd'), ['estimate_id' => $estimate_id]);
        }

        $employee = $this->employees->find_by_user_id((int) $user->ID);
        if (! $employee) {
            $this->redirect_manager_with_notice('error', __('Nie znaleziono profilu pracownika dla bieżącego użytkownika.', 'erp-omd'));
        }

        $managed_projects = $this->load_managed_projects((int) $employee['id'], user_can($user, 'administrator'));
        $visible_estimate_ids = array_map('intval', wp_list_pluck($this->load_visible_manager_estimates((int) $employee['id'], $managed_projects, user_can($user, 'administrator')), 'id'));
        if (! in_array($estimate_id, $visible_estimate_ids, true)) {
            $this->redirect_manager_with_notice('error', __('Nie możesz edytować pozycji kosztorysów spoza własnego zakresu odpowiedzialności.', 'erp-omd'), ['estimate_id' => $estimate_id]);
        }

        $payload = [
            'name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
            'qty' => (float) ($_POST['qty'] ?? 0),
            'price' => (float) ($_POST['price'] ?? 0),
            'cost_internal' => (float) ($_POST['cost_internal'] ?? 0),
            'comment' => sanitize_textarea_field(wp_unslash($_POST['comment'] ?? '')),
        ];

        $errors = $this->estimate_service->validate_item($payload, $estimate, $item);
        if ($errors) {
            $this->redirect_manager_with_notice('error', implode(' ', array_unique($errors)), ['estimate_id' => $estimate_id]);
        }

        $this->estimate_items->update($item_id, $payload);
        $this->redirect_manager_with_notice('success', __('Pozycja kosztorysu została zaktualizowana.', 'erp-omd'), ['estimate_id' => $estimate_id]);
    }

    private function update_manager_project_status(WP_User $user)
    {
        $project_id = (int) ($_POST['project_id'] ?? 0);
        $status = sanitize_text_field(wp_unslash($_POST['status'] ?? ''));
        $allowed_statuses = ['do_rozpoczecia', 'w_realizacji', 'w_akceptacji', 'do_faktury', 'zakonczony', 'inactive'];
        if ($project_id <= 0 || ! in_array($status, $allowed_statuses, true)) {
            $this->redirect_manager_with_notice('error', __('Niepoprawne dane zmiany statusu projektu.', 'erp-omd'));
        }

        $employee = $this->employees->find_by_user_id((int) $user->ID);
        if (! $employee) {
            $this->redirect_manager_with_notice('error', __('Nie znaleziono profilu pracownika dla bieżącego użytkownika.', 'erp-omd'));
        }

        $managed_project_ids = array_map('intval', wp_list_pluck($this->load_managed_projects((int) $employee['id'], user_can($user, 'administrator')), 'id'));
        if (! in_array($project_id, $managed_project_ids, true)) {
            $this->redirect_manager_with_notice('error', __('Nie możesz zmieniać statusu projektów spoza własnego zakresu odpowiedzialności.', 'erp-omd'));
        }

        $this->projects->set_status($project_id, $status);
        $this->redirect_manager_with_notice('success', __('Status projektu został zaktualizowany.', 'erp-omd'), ['project_id' => $project_id]);
    }

    private function add_manager_project_cost(WP_User $user)
    {
        $project_id = (int) ($_POST['project_id'] ?? 0);
        $employee = $this->employees->find_by_user_id((int) $user->ID);
        if (! $employee) {
            $this->redirect_manager_with_notice('error', __('Nie znaleziono profilu pracownika dla bieżącego użytkownika.', 'erp-omd'));
        }

        $managed_project_ids = array_map('intval', wp_list_pluck($this->load_managed_projects((int) $employee['id'], user_can($user, 'administrator')), 'id'));
        if (! in_array($project_id, $managed_project_ids, true)) {
            $this->redirect_manager_with_notice('error', __('Nie możesz dodawać kosztów do projektów spoza własnego zakresu odpowiedzialności.', 'erp-omd'));
        }

        $payload = [
            'project_id' => $project_id,
            'amount' => (float) ($_POST['amount'] ?? 0),
            'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
            'cost_date' => sanitize_text_field(wp_unslash($_POST['cost_date'] ?? '')),
            'created_by_user_id' => (int) $user->ID,
        ];
        $errors = $this->project_financial_service->validate_project_cost($payload);
        if ($errors) {
            $this->redirect_manager_with_notice('error', implode(' ', array_unique($errors)), ['project_id' => $project_id]);
        }

        $this->project_costs->create($payload);
        $this->project_financial_service->rebuild_for_project($project_id);
        $this->redirect_manager_with_notice('success', __('Koszt projektu został dodany.', 'erp-omd'), ['project_id' => $project_id]);
    }

    private function accept_manager_estimate(WP_User $user)
    {
        $estimate_id = (int) ($_POST['estimate_id'] ?? 0);
        $estimate = $estimate_id > 0 ? $this->estimates->find($estimate_id) : null;
        if (! $estimate) {
            $this->redirect_manager_with_notice('error', __('Nie znaleziono kosztorysu.', 'erp-omd'));
        }

        $employee = $this->employees->find_by_user_id((int) $user->ID);
        if (! $employee) {
            $this->redirect_manager_with_notice('error', __('Nie znaleziono profilu pracownika dla bieżącego użytkownika.', 'erp-omd'));
        }

        $visible_estimate_ids = array_map('intval', wp_list_pluck($this->load_visible_manager_estimates((int) $employee['id'], $this->load_managed_projects((int) $employee['id'], user_can($user, 'administrator')), user_can($user, 'administrator')), 'id'));
        if (! in_array($estimate_id, $visible_estimate_ids, true)) {
            $this->redirect_manager_with_notice('error', __('Nie możesz akceptować kosztorysów spoza własnego zakresu odpowiedzialności.', 'erp-omd'));
        }

        $result = $this->estimate_service->accept($estimate_id);
        if ($result instanceof WP_Error) {
            $this->redirect_manager_with_notice('error', $result->get_error_message(), ['estimate_id' => $estimate_id]);
        }

        $extra_args = ['estimate_id' => $estimate_id];
        if (! empty($result['project']['id'])) {
            $extra_args['project_id'] = (int) $result['project']['id'];
        }

        $this->redirect_manager_with_notice('success', __('Kosztorys został zaakceptowany i powiązany z projektem.', 'erp-omd'), $extra_args);
    }

    private function create_manager_project_request(WP_User $user)
    {
        $employee = $this->employees->find_by_user_id((int) $user->ID);
        if (! $employee) {
            $this->redirect_manager_with_notice('error', __('Nie znaleziono profilu pracownika dla bieżącego użytkownika.', 'erp-omd'));
        }

        $payload = $this->project_request_service->prepare([
            'requester_user_id' => (int) $user->ID,
            'requester_employee_id' => (int) $employee['id'],
            'client_id' => (int) ($_POST['client_id'] ?? 0),
            'project_name' => sanitize_text_field(wp_unslash($_POST['project_name'] ?? '')),
            'billing_type' => sanitize_text_field(wp_unslash($_POST['billing_type'] ?? 'time_material')),
            'preferred_manager_id' => (int) ($_POST['preferred_manager_id'] ?? (int) $employee['id']),
            'estimate_id' => (int) ($_POST['estimate_id'] ?? 0),
            'brief' => sanitize_textarea_field(wp_unslash($_POST['brief'] ?? '')),
            'status' => 'new',
        ]);

        $errors = $this->project_request_service->validate($payload);
        if ($errors) {
            $this->redirect_manager_with_notice('error', implode(' ', $errors));
        }

        $this->project_requests->create($payload);
        $this->redirect_manager_with_notice('success', __('Wniosek projektowy został zapisany.', 'erp-omd'));
    }

    private function create_worker_project_request(WP_User $user)
    {
        $employee = $this->employees->find_by_user_id((int) $user->ID);
        if (! $employee) {
            $this->redirect_worker_with_notice('error', __('Nie znaleziono profilu pracownika dla bieżącego użytkownika.', 'erp-omd'));
        }

        $payload = $this->project_request_service->prepare([
            'requester_user_id' => (int) $user->ID,
            'requester_employee_id' => (int) $employee['id'],
            'client_id' => (int) ($_POST['client_id'] ?? 0),
            'project_name' => sanitize_text_field(wp_unslash($_POST['project_name'] ?? '')),
            'billing_type' => sanitize_text_field(wp_unslash($_POST['billing_type'] ?? 'time_material')),
            'preferred_manager_id' => (int) ($_POST['preferred_manager_id'] ?? 0),
            'estimate_id' => 0,
            'brief' => sanitize_textarea_field(wp_unslash($_POST['brief'] ?? '')),
            'status' => 'new',
            'reviewed_by_user_id' => 0,
            'reviewed_at' => null,
            'converted_project_id' => 0,
        ]);

        $errors = $this->project_request_service->validate($payload);
        if ($errors) {
            $this->redirect_worker_with_notice('error', implode(' ', array_unique($errors)));
        }

        $this->project_requests->create($payload);
        $this->redirect_worker_with_notice('success', __('Wniosek projektowy został wysłany.', 'erp-omd'));
    }

    private function collect_manager_estimate_line_items()
    {
        $names = wp_unslash($_POST['item_name'] ?? []);
        $qtys = wp_unslash($_POST['item_qty'] ?? []);
        $prices = wp_unslash($_POST['item_price'] ?? []);
        $costs = wp_unslash($_POST['item_cost_internal'] ?? []);
        $comments = wp_unslash($_POST['item_comment'] ?? []);

        if (! is_array($names)) {
            $names = [$names];
        }
        if (! is_array($qtys)) {
            $qtys = [$qtys];
        }
        if (! is_array($prices)) {
            $prices = [$prices];
        }
        if (! is_array($costs)) {
            $costs = [$costs];
        }
        if (! is_array($comments)) {
            $comments = [$comments];
        }

        $max_count = max(count($names), count($qtys), count($prices), count($costs), count($comments));
        $line_items = [];

        for ($index = 0; $index < $max_count; $index++) {
            $line_items[] = [
                'estimate_id' => 0,
                'name' => sanitize_text_field((string) ($names[$index] ?? '')),
                'qty' => (float) ($qtys[$index] ?? 0),
                'price' => (float) ($prices[$index] ?? 0),
                'cost_internal' => (float) ($costs[$index] ?? 0),
                'comment' => sanitize_textarea_field((string) ($comments[$index] ?? '')),
            ];
        }

        return array_values(
            array_filter(
                $line_items,
                static function ($item) {
                    return trim((string) ($item['name'] ?? '')) !== ''
                        || (float) ($item['qty'] ?? 0) > 0
                        || (float) ($item['price'] ?? 0) > 0
                        || (float) ($item['cost_internal'] ?? 0) > 0
                        || trim((string) ($item['comment'] ?? '')) !== '';
                }
            )
        );
    }

    private function validate_manager_estimate_line_items(array $line_items, $estimate_status)
    {
        if ($line_items === []) {
            return [__('Dodaj co najmniej jedną pozycję kosztorysu.', 'erp-omd')];
        }

        $errors = [];
        foreach ($line_items as $line_item) {
            $errors = array_merge($errors, $this->estimate_service->validate_item($line_item, ['id' => 0, 'status' => $estimate_status]));
        }

        return array_values(array_unique($errors));
    }

    private function export_manager_estimate_csv(WP_User $user)
    {
        $estimate_id = (int) ($_POST['estimate_id'] ?? 0);
        $estimate = $estimate_id > 0 ? $this->estimates->find($estimate_id) : null;
        if (! $estimate) {
            $this->redirect_manager_with_notice('error', __('Nie znaleziono kosztorysu do eksportu.', 'erp-omd'));
        }

        $employee = $this->employees->find_by_user_id((int) $user->ID);
        if (! $employee) {
            $this->redirect_manager_with_notice('error', __('Nie znaleziono profilu pracownika dla bieżącego użytkownika.', 'erp-omd'));
        }

        $managed_projects = $this->load_managed_projects((int) $employee['id'], user_can($user, 'administrator'));
        $visible_estimate_ids = array_map('intval', wp_list_pluck($this->load_visible_manager_estimates((int) $employee['id'], $managed_projects, user_can($user, 'administrator')), 'id'));
        if (! in_array($estimate_id, $visible_estimate_ids, true)) {
            $this->redirect_manager_with_notice('error', __('Nie możesz eksportować kosztorysów spoza własnego zakresu widoczności.', 'erp-omd'));
        }

        $items = $this->estimate_items->for_estimate($estimate_id);
        $totals = $this->estimate_service->calculate_totals($items);
        $client = $this->clients->find((int) ($estimate['client_id'] ?? 0));
        $estimate_name = trim((string) ($estimate['name'] ?? '')) !== '' ? (string) $estimate['name'] : sprintf('estimate-%d', $estimate_id);
        $filename = sanitize_file_name(sprintf('%s-client.csv', $estimate_name));

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        if (! $output) {
            wp_die(esc_html__('Nie udało się przygotować pliku CSV.', 'erp-omd'));
        }

        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, [__('Nazwa kosztorysu', 'erp-omd'), $estimate_name], ';');
        fputcsv($output, [__('Klient', 'erp-omd'), (string) ($client['name'] ?? ($estimate['client_name'] ?? '—'))], ';');
        fputcsv($output, [__('Status', 'erp-omd'), (string) ($estimate['status'] ?? '—')], ';');
        fputcsv($output, [], ';');
        fputcsv($output, [__('Pozycja', 'erp-omd'), __('Ilość', 'erp-omd'), __('Cena jednostkowa netto', 'erp-omd'), __('Wartość netto', 'erp-omd'), __('Komentarz', 'erp-omd')], ';');

        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            fputcsv(
                $output,
                [
                    (string) ($item['name'] ?? ''),
                    number_format($qty, 2, '.', ''),
                    number_format($price, 2, '.', ''),
                    number_format($qty * $price, 2, '.', ''),
                    (string) ($item['comment'] ?? ''),
                ],
                ';'
            );
        }

        fputcsv($output, [], ';');
        fputcsv($output, [__('Suma netto', 'erp-omd'), number_format((float) $totals['net'], 2, '.', '')], ';');
        fputcsv($output, [__('VAT 23%', 'erp-omd'), number_format((float) $totals['tax'], 2, '.', '')], ';');
        fputcsv($output, [__('Suma brutto', 'erp-omd'), number_format((float) $totals['gross'], 2, '.', '')], ';');
        fclose($output);
        exit;
    }

    private function process_project_request_action(WP_User $user, $action)
    {
        $request_id = (int) ($_POST['request_id'] ?? 0);
        $request = $request_id > 0 ? $this->project_requests->find($request_id) : null;
        if (! $request) {
            $this->redirect_manager_with_notice('error', __('Nie znaleziono wniosku projektowego.', 'erp-omd'));
        }

        $employee = $this->employees->find_by_user_id((int) $user->ID);
        if (! $employee || ! $this->can_review_project_request($request, $employee, $user)) {
            $this->redirect_manager_with_notice('error', __('Nie możesz zarządzać tym wnioskiem projektowym.', 'erp-omd'));
        }

        if ($action === 'convert_project_request') {
            $errors = $this->project_request_service->validate_conversion($request);
            if ($errors) {
                $this->redirect_manager_with_notice('error', implode(' ', $errors), ['request_id' => $request_id]);
            }

            $project_payload = $this->project_request_service->build_project_payload($request);
            $project_id = $this->projects->create($project_payload);
            $this->project_financial_service->rebuild_for_project($project_id);
            $this->project_requests->mark_converted($request_id, $project_id, (int) $user->ID);

            $this->redirect_manager_with_notice('success', __('Wniosek został skonwertowany do projektu.', 'erp-omd'), ['project_id' => $project_id]);
        }

        $status_map = [
            'review_project_request' => 'under_review',
            'approve_project_request' => 'approved',
            'reject_project_request' => 'rejected',
        ];
        $target_status = $status_map[$action] ?? '';
        if ($target_status === '') {
            $this->redirect_manager_with_notice('error', __('Nieobsługiwana akcja wniosku projektowego.', 'erp-omd'));
        }

        $request_payload = $this->project_request_service->prepare(
            array_merge(
                $request,
                [
                    'status' => $target_status,
                    'reviewed_by_user_id' => (int) $user->ID,
                    'reviewed_at' => current_time('mysql'),
                ]
            ),
            $request
        );
        $errors = $this->project_request_service->validate($request_payload, $request);
        if ($errors) {
            $this->redirect_manager_with_notice('error', implode(' ', $errors), ['request_id' => $request_id]);
        }

        $this->project_requests->update($request_id, $request_payload);
        $messages = [
            'under_review' => __('Wniosek został oznaczony jako analizowany.', 'erp-omd'),
            'approved' => __('Wniosek został zatwierdzony.', 'erp-omd'),
            'rejected' => __('Wniosek został odrzucony.', 'erp-omd'),
        ];
        $this->redirect_manager_with_notice('success', $messages[$target_status] ?? __('Wniosek został zaktualizowany.', 'erp-omd'), ['request_id' => $request_id]);
    }

    private function get_worker_roles(array $employee)
    {
        $role_ids = array_map('intval', (array) ($employee['role_ids'] ?? []));
        $default_role_id = (int) ($employee['default_role_id'] ?? 0);
        if ($default_role_id > 0) {
            $role_ids[] = $default_role_id;
        }
        $role_ids = array_values(array_unique(array_filter($role_ids)));

        $roles = $this->roles->all();
        if ($role_ids === []) {
            return $roles;
        }

        return array_values(
            array_filter(
                $roles,
                function ($role) use ($role_ids) {
                    return in_array((int) ($role['id'] ?? 0), $role_ids, true);
                }
            )
        );
    }

    private function redirect_worker_with_notice($type, $message, array $extra_args = [])
    {
        $args = array_merge(
            [
                'notice' => $type,
                'message' => $message,
            ],
            $extra_args
        );

        wp_safe_redirect($this->front_url('worker', $args));
        exit;
    }

    private function redirect_manager_with_notice($type, $message, array $extra_args = [])
    {
        $args = array_merge(
            [
                'notice' => $type,
                'message' => $message,
            ],
            $extra_args
        );

        wp_safe_redirect($this->front_url('manager', $args));
        exit;
    }

    private function load_managed_projects($employee_id, $include_all = false)
    {
        $projects = $this->projects->all();
        if ($employee_id <= 0) {
            return [];
        }

        if ($include_all) {
            $managed_projects = $projects;
        } else {
            $managed_project_ids = $this->projects->ids_managed_by_employee($employee_id);
            $managed_projects = array_values(
                array_filter(
                    $projects,
                    function ($project) use ($managed_project_ids) {
                        return in_array((int) ($project['id'] ?? 0), $managed_project_ids, true);
                    }
                )
            );
        }

        if ($managed_projects === []) {
            return [];
        }

        $financials = $this->project_financial_service->get_project_financials(array_map('intval', wp_list_pluck($managed_projects, 'id')));
        foreach ($managed_projects as &$project) {
            $project_id = (int) ($project['id'] ?? 0);
            $project['financial'] = $financials[$project_id] ?? [];
            $project['alerts'] = $this->alert_service->alerts_for_entity('project', $project_id);
            $project['pending_entries_count'] = $this->time_entries->count_for_project_by_statuses($project_id, ['submitted']);
        }
        unset($project);

        return $managed_projects;
    }

    private function load_estimates_for_projects(array $projects)
    {
        if ($projects === []) {
            return [];
        }

        $project_estimate_ids = array_values(array_unique(array_filter(array_map('intval', wp_list_pluck($projects, 'estimate_id')))));
        $project_ids = array_map('intval', wp_list_pluck($projects, 'id'));
        $estimates = $this->estimates->all();

        return array_values(
            array_filter(
                $estimates,
                function ($estimate) use ($project_estimate_ids, $project_ids) {
                    $estimate_id = (int) ($estimate['id'] ?? 0);
                    $project_id = (int) ($estimate['project_id'] ?? 0);

                    return in_array($estimate_id, $project_estimate_ids, true)
                        || in_array($project_id, $project_ids, true);
                }
            )
        );
    }

    private function get_manager_available_clients($employee_id, $include_all = false)
    {
        $clients = $this->clients->all();
        if ($include_all) {
            return array_values(
                array_filter(
                    $clients,
                    function ($client) {
                        return (string) ($client['status'] ?? '') === 'active';
                    }
                )
            );
        }

        $managed_projects = $this->load_managed_projects($employee_id, false);
        $managed_client_ids = array_map('intval', wp_list_pluck($managed_projects, 'client_id'));

        return array_values(
            array_filter(
                $clients,
                function ($client) use ($employee_id, $managed_client_ids) {
                    if ((string) ($client['status'] ?? '') !== 'active') {
                        return false;
                    }

                    return (int) ($client['account_manager_id'] ?? 0) === (int) $employee_id
                        || in_array((int) ($client['id'] ?? 0), $managed_client_ids, true);
                }
            )
        );
    }

    private function load_visible_manager_estimates($employee_id, array $managed_projects, $include_all = false)
    {
        if ($include_all) {
            $estimates = $this->estimates->all();
            foreach ($estimates as &$estimate) {
                $items = $this->estimate_items->for_estimate((int) ($estimate['id'] ?? 0));
                $estimate['items'] = $items;
                $estimate['totals'] = $this->estimate_service->calculate_totals($items);
                $estimate['items_count'] = count($items);
            }
            unset($estimate);

            usort(
                $estimates,
                static function ($left, $right) {
                    return [(string) ($right['created_at'] ?? ''), (int) ($right['id'] ?? 0)] <=> [(string) ($left['created_at'] ?? ''), (int) ($left['id'] ?? 0)];
                }
            );

            return $estimates;
        }

        $project_estimates = $this->load_estimates_for_projects($managed_projects);
        $visible_client_ids = array_map('intval', wp_list_pluck($this->get_manager_available_clients($employee_id, false), 'id'));
        $estimates = $this->estimates->all();

        foreach ($estimates as &$estimate) {
            $items = $this->estimate_items->for_estimate((int) ($estimate['id'] ?? 0));
            $estimate['items'] = $items;
            $estimate['totals'] = $this->estimate_service->calculate_totals($items);
            $estimate['items_count'] = count($items);
        }
        unset($estimate);

        $visible = array_values(
            array_filter(
                $estimates,
                function ($estimate) use ($visible_client_ids, $project_estimates) {
                    $estimate_id = (int) ($estimate['id'] ?? 0);
                    $linked_ids = array_map('intval', wp_list_pluck($project_estimates, 'id'));

                    return in_array((int) ($estimate['client_id'] ?? 0), $visible_client_ids, true)
                        || in_array($estimate_id, $linked_ids, true);
                }
            )
        );

        usort(
            $visible,
            static function ($left, $right) {
                return [(string) ($right['created_at'] ?? ''), (int) ($right['id'] ?? 0)] <=> [(string) ($left['created_at'] ?? ''), (int) ($left['id'] ?? 0)];
            }
        );

        return $visible;
    }

    private function find_estimate_in_collection(array $estimates, $estimate_id)
    {
        foreach ($estimates as $estimate) {
            if ((int) ($estimate['id'] ?? 0) === (int) $estimate_id) {
                return $estimate;
            }
        }

        return null;
    }

    private function load_manager_approval_queue(array $managed_project_ids, $selected_project_id = 0)
    {
        if ($managed_project_ids === []) {
            return [];
        }

        $entries = $this->time_entries->all(['status' => 'submitted']);

        return array_values(
            array_filter(
                $entries,
                function ($entry) use ($managed_project_ids, $selected_project_id) {
                    $project_id = (int) ($entry['project_id'] ?? 0);
                    if (! in_array($project_id, $managed_project_ids, true)) {
                        return false;
                    }

                    if ($selected_project_id > 0 && $project_id !== $selected_project_id) {
                        return false;
                    }

                    return true;
                }
            )
        );
    }

    private function summarize_queue_entries(array $entries)
    {
        $summary = [
            'hours' => 0.0,
            'employees' => [],
        ];

        foreach ($entries as $entry) {
            $summary['hours'] += (float) ($entry['hours'] ?? 0);
            $employee_login = (string) ($entry['employee_login'] ?? '');
            if ($employee_login !== '') {
                $summary['employees'][$employee_login] = true;
            }
        }

        $summary['hours'] = round($summary['hours'], 2);
        $summary['employees_count'] = count($summary['employees']);

        return $summary;
    }

    private function find_project_in_collection(array $projects, $project_id)
    {
        foreach ($projects as $project) {
            if ((int) ($project['id'] ?? 0) === (int) $project_id) {
                return $project;
            }
        }

        return null;
    }

    private function load_visible_project_requests($current_employee_id, WP_User $user)
    {
        $requests = $this->project_requests->all();
        if (user_can($user, 'administrator')) {
            return $requests;
        }

        return array_values(
            array_filter(
                $requests,
                function ($request) use ($current_employee_id) {
                    return (int) ($request['requester_employee_id'] ?? 0) === (int) $current_employee_id
                        || (int) ($request['preferred_manager_id'] ?? 0) === (int) $current_employee_id;
                }
            )
        );
    }

    private function can_review_project_request(array $request, array $current_employee, WP_User $user)
    {
        if (user_can($user, 'administrator')) {
            return true;
        }

        return (int) ($request['preferred_manager_id'] ?? 0) === (int) ($current_employee['id'] ?? 0);
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
                return $status ?: '—';
        }
    }

    private function find_request_in_collection(array $requests, $request_id)
    {
        foreach ($requests as $request) {
            if ((int) ($request['id'] ?? 0) === (int) $request_id) {
                return $request;
            }
        }

        return null;
    }

    private function send_front_headers()
    {
        status_header(200);
        nocache_headers();
    }

    private function is_front_url($url)
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        $front_base = trailingslashit(home_url('/erp-front/'));

        return strpos(trailingslashit($url), $front_base) === 0;
    }
}
