<?php

class ERP_OMD_Frontend
{
    private $employees;

    public function __construct(ERP_OMD_Employee_Repository $employees)
    {
        $this->employees = $employees;
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

        if ($screen === 'home') {
            wp_safe_redirect($this->resolve_dashboard_url_for_user(wp_get_current_user()));
            exit;
        }

        $this->guard_dashboard_access($screen, wp_get_current_user());
        $this->render_dashboard($screen, wp_get_current_user());
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

    private function render_dashboard($screen, WP_User $user)
    {
        $employee = $this->employees->find_by_user_id((int) $user->ID);
        $dashboard_title = $screen === 'manager'
            ? __('Panel managera', 'erp-omd')
            : __('Panel pracownika', 'erp-omd');
        $dashboard_intro = $screen === 'manager'
            ? __('To punkt wejścia FRONT-1 dla managera. W kolejnych krokach dołączymy listę projektów, akceptacje czasu i lekkie akcje operacyjne.', 'erp-omd')
            : __('To punkt wejścia FRONT-1 dla pracownika. W kolejnych krokach dołączymy dashboard czasu pracy, formularz wpisu i listę własnych zgłoszeń.', 'erp-omd');
        $front_login_url = $this->front_url('login');
        $front_logout_url = $this->front_url('logout');
        $front_worker_url = $this->front_url('worker');
        $front_manager_url = $this->front_url('manager');
        $front_brand_label = __('ERP OMD FRONT', 'erp-omd');

        $this->send_front_headers();
        include ERP_OMD_PATH . 'templates/front/dashboard.php';
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
