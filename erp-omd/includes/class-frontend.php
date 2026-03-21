<?php

class ERP_OMD_Frontend
{
    private $employees;
    private $projects;
    private $roles;
    private $time_entries;
    private $time_entry_service;
    private $project_financial_service;
    private $reporting_service;

    public function __construct(
        ERP_OMD_Employee_Repository $employees,
        ERP_OMD_Project_Repository $projects,
        ERP_OMD_Role_Repository $roles,
        ERP_OMD_Time_Entry_Repository $time_entries,
        ERP_OMD_Time_Entry_Service $time_entry_service,
        ERP_OMD_Project_Financial_Service $project_financial_service,
        ERP_OMD_Reporting_Service $reporting_service
    ) {
        $this->employees = $employees;
        $this->projects = $projects;
        $this->roles = $roles;
        $this->time_entries = $time_entries;
        $this->time_entry_service = $time_entry_service;
        $this->project_financial_service = $project_financial_service;
        $this->reporting_service = $reporting_service;
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

        $this->render_manager_dashboard($current_user);
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

        $this->redirect_worker_with_notice('error', __('Nieobsługiwana akcja formularza FRONT.', 'erp-omd'));
    }

    private function save_worker_time_entry(WP_User $user)
    {
        $employee = $this->employees->find_by_user_id((int) $user->ID);
        if (! $employee) {
            $this->redirect_worker_with_notice('error', __('Nie znaleziono profilu pracownika dla bieżącego użytkownika.', 'erp-omd'));
        }

        $entry_id = (int) ($_POST['id'] ?? 0);
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
                array_merge($entry_id ? ['entry_id' => $entry_id] : [], $selected_day_args)
            );
        }

        $allowed_role_ids = wp_list_pluck($this->get_worker_roles($employee), 'id');
        if (! in_array($role_id, array_map('intval', $allowed_role_ids), true)) {
            $this->redirect_worker_with_notice(
                'error',
                __('Wybrana rola nie jest dostępna dla tego pracownika.', 'erp-omd'),
                array_merge($entry_id ? ['entry_id' => $entry_id] : [], $selected_day_args)
            );
        }

        if ($entry_id) {
            $existing = $this->time_entries->find($entry_id);
            if (! $existing || ! $this->time_entry_service->can_edit_entry($existing, $user)) {
                $this->redirect_worker_with_notice('error', __('Możesz edytować tylko własne wpisy ze statusem submitted.', 'erp-omd'), $selected_day_args);
            }
        }

        $payload = $this->time_entry_service->prepare($payload);
        $errors = $this->time_entry_service->validate($payload, $entry_id ?: null);
        if ($errors) {
            $this->redirect_worker_with_notice(
                'error',
                implode(' ', $errors),
                array_merge($entry_id ? ['entry_id' => $entry_id] : [], $selected_day_args)
            );
        }

        if ($entry_id) {
            $this->time_entries->update($entry_id, $payload);
            $message = __('Wpis czasu został zaktualizowany.', 'erp-omd');
        } else {
            $entry_id = $this->time_entries->create($payload);
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
        $available_roles = $this->get_worker_roles($employee);
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
        $time_entries = $this->filter_worker_entries_by_focus($time_entries, $worker_filters);

        $calendar_data = $this->reporting_service->build_calendar([
            'employee_id' => (int) $employee['id'],
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
            'project_id' => 0,
            'role_id' => (int) ($available_roles[0]['id'] ?? 0),
            'hours' => '',
            'entry_date' => $selected_day ?: gmdate('Y-m-d'),
            'description' => '',
            'status' => 'submitted',
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
            $key = implode(':', [
                (int) ($entry['project_id'] ?? 0),
                (int) ($entry['role_id'] ?? 0),
                trim((string) ($entry['description'] ?? '')),
            ]);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $templates[] = [
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

        return $this->time_entry_service->filter_visible_entries($entries, $user);
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
        $dashboard_title = __('Panel managera', 'erp-omd');
        $dashboard_intro = __('To punkt wejścia FRONT-1 dla managera. W kolejnych krokach dołączymy listę projektów, akceptacje czasu i lekkie akcje operacyjne.', 'erp-omd');
        $front_login_url = $this->front_url('login');
        $front_logout_url = $this->front_url('logout');
        $front_worker_url = $this->front_url('worker');
        $front_manager_url = $this->front_url('manager');
        $front_brand_label = __('ERP OMD FRONT', 'erp-omd');

        $this->send_front_headers();
        include ERP_OMD_PATH . 'templates/front/dashboard.php';
        exit;
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
