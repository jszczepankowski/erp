<?php

if (! class_exists('ERP_OMD_Admin')) {
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
    private $project_requests;
    private $estimates;
    private $estimate_items;
    private $project_notes;
    private $client_project_service;
    private $project_request_service;
    private $estimate_service;
    private $project_rates;
    private $project_costs;
    private $project_revenues;
    private $project_financials;
    private $time_entries;
    private $attachments;
    private $time_entry_service;
    private $project_financial_service;
    private $reporting_service;
    private $alert_service;
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
        ERP_OMD_Project_Request_Repository $project_requests,
        ERP_OMD_Estimate_Repository $estimates,
        ERP_OMD_Estimate_Item_Repository $estimate_items,
        ERP_OMD_Project_Note_Repository $project_notes,
        ERP_OMD_Client_Project_Service $client_project_service,
        ERP_OMD_Project_Request_Service $project_request_service,
        ERP_OMD_Estimate_Service $estimate_service,
        ERP_OMD_Project_Rate_Repository $project_rates,
        ERP_OMD_Project_Cost_Repository $project_costs,
        ERP_OMD_Project_Revenue_Repository $project_revenues,
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
        $this->project_requests = $project_requests;
        $this->estimates = $estimates;
        $this->estimate_items = $estimate_items;
        $this->project_notes = $project_notes;
        $this->client_project_service = $client_project_service;
        $this->project_request_service = $project_request_service;
        $this->estimate_service = $estimate_service;
        $this->project_rates = $project_rates;
        $this->project_costs = $project_costs;
        $this->project_revenues = $project_revenues;
        $this->project_financials = $project_financials;
        $this->time_entries = $time_entries;
        $this->attachments = $attachments;
        $this->time_entry_service = $time_entry_service;
        $this->project_financial_service = $project_financial_service;
        $this->reporting_service = $reporting_service;
        $this->alert_service = $alert_service;
        $this->adjustment_audit = class_exists('ERP_OMD_Adjustment_Audit_Repository') ? new ERP_OMD_Adjustment_Audit_Repository() : null;
    }

    public function register_hooks()
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_init', [$this, 'handle_forms']);
        add_action('wp_ajax_erp_omd_inline_project_update', [$this, 'handle_inline_project_update_ajax']);
    }

    public function register_menu()
    {
        add_menu_page(__('ERP OMD', 'erp-omd'), __('ERP OMD', 'erp-omd'), 'erp_omd_access', 'erp-omd', [$this, 'render_dashboard'], 'dashicons-chart-pie', 56);
        add_submenu_page('erp-omd', __('Dashboard', 'erp-omd'), __('Dashboard', 'erp-omd'), 'erp_omd_access', 'erp-omd', [$this, 'render_dashboard']);
        $this->add_submenu_separator('erp-omd', 'erp-omd-separator-team');
        add_submenu_page('erp-omd', __('Pracownicy', 'erp-omd'), __('Pracownicy', 'erp-omd'), 'erp_omd_manage_employees', 'erp-omd-employees', [$this, 'render_employees']);
        add_submenu_page('erp-omd', __('Role', 'erp-omd'), __('Role', 'erp-omd'), 'erp_omd_manage_roles', 'erp-omd-roles', [$this, 'render_roles']);
        add_submenu_page('erp-omd', __('Klienci', 'erp-omd'), __('Klienci', 'erp-omd'), 'erp_omd_manage_clients', 'erp-omd-clients', [$this, 'render_clients']);
        $this->add_submenu_separator('erp-omd', 'erp-omd-separator-commercial');
        add_submenu_page('erp-omd', __('Czas pracy', 'erp-omd'), __('Czas pracy', 'erp-omd'), 'erp_omd_manage_time', 'erp-omd-time', [$this, 'render_time_entries']);
        add_submenu_page('erp-omd', __('Kosztorysy', 'erp-omd'), __('Kosztorysy', 'erp-omd'), 'erp_omd_manage_projects', 'erp-omd-estimates', [$this, 'render_estimates']);
        add_submenu_page('erp-omd', __('Wnioski', 'erp-omd'), __('Wnioski', 'erp-omd'), 'erp_omd_manage_projects', 'erp-omd-requests', [$this, 'render_project_requests']);
        add_submenu_page('erp-omd', __('Projekty', 'erp-omd'), __('Projekty', 'erp-omd'), 'erp_omd_manage_projects', 'erp-omd-projects', [$this, 'render_projects']);
        add_submenu_page('erp-omd', __('Dostawcy i koszty', 'erp-omd'), __('Dostawcy i koszty', 'erp-omd'), 'erp_omd_manage_projects', 'erp-omd-cost-invoices', [$this, 'render_cost_invoices']);
        $this->add_submenu_separator('erp-omd', 'erp-omd-separator-time');
        add_submenu_page('erp-omd', __('Kalendarz', 'erp-omd'), __('Kalendarz', 'erp-omd'), 'erp_omd_access', 'erp-omd-calendar', [$this, 'render_calendar']);
        add_submenu_page('erp-omd', __('Raporty', 'erp-omd'), __('Raporty', 'erp-omd'), 'erp_omd_access', 'erp-omd-reports', [$this, 'render_reports']);
        add_submenu_page(
            'erp-omd',
            __('Finanse', 'erp-omd'),
            __('Finanse', 'erp-omd'),
            'erp_omd_access',
            'erp-omd-finances',
            function () {
                $month = sanitize_text_field(wp_unslash($_GET['month'] ?? current_time('Y-m')));
                if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) !== 1) {
                    $month = current_time('Y-m');
                }

                $projects_report = $this->reporting_service->build_report('projects', [
                    'report_type' => 'projects',
                    'month' => $month,
                    'detail' => 'simple',
                    'tab' => 'reports',
                ]);
                $clients_report = $this->reporting_service->build_report('clients', [
                    'report_type' => 'clients',
                    'month' => $month,
                    'detail' => 'simple',
                    'tab' => 'reports',
                ]);

                $build_profit_ranking = static function (array $rows, $name_key, $best = true) {
                    $normalized = [];
                    foreach ($rows as $row) {
                        $normalized[] = [
                            'name' => (string) ($row[$name_key] ?? '—'),
                            'client_name' => (string) ($row['client_name'] ?? ''),
                            'profit' => (float) ($row['profit'] ?? 0.0),
                            'revenue' => (float) ($row['revenue'] ?? 0.0),
                            'cost' => (float) ($row['cost'] ?? 0.0),
                            'margin' => (float) ($row['margin'] ?? 0.0),
                        ];
                    }

                    usort($normalized, static function ($left, $right) use ($best) {
                        $left_profit = (float) ($left['profit'] ?? 0.0);
                        $right_profit = (float) ($right['profit'] ?? 0.0);
                        if ($left_profit === $right_profit) {
                            return 0;
                        }

                        if ($best) {
                            return $left_profit < $right_profit ? 1 : -1;
                        }

                        return $left_profit > $right_profit ? 1 : -1;
                    });

                    return array_slice($normalized, 0, 5);
                };

                $top_projects_best = $build_profit_ranking($projects_report, 'project_name');
                $top_projects_worst = $build_profit_ranking($projects_report, 'project_name', false);
                $top_clients_best = $build_profit_ranking($clients_report, 'client_name');
                $top_clients_worst = $build_profit_ranking($clients_report, 'client_name', false);

                $trend_rows = (array) $this->reporting_service->build_report('omd_rozliczenia', [
                    'report_type' => 'omd_rozliczenia',
                    'month' => $month,
                    'tab' => 'reports',
                ]);
                $selected_month_summary = [
                    'revenue' => 0.0,
                    'cost' => 0.0,
                    'salary_cost' => 0.0,
                    'project_direct_cost' => 0.0,
                    'time_cost' => 0.0,
                    'fixed_cost' => 0.0,
                ];
                foreach ($trend_rows as $trend_row) {
                    if ((string) ($trend_row['month'] ?? '') !== $month) {
                        continue;
                    }

                    $selected_month_summary['revenue'] = (float) ($trend_row['active_project_budgets'] ?? 0.0) + (float) ($trend_row['time_revenue'] ?? 0.0);
                    $selected_month_summary['cost'] = (float) ($trend_row['salary_cost'] ?? 0.0) + (float) ($trend_row['project_direct_cost'] ?? 0.0) + (float) ($trend_row['time_cost'] ?? 0.0) + (float) ($trend_row['fixed_cost'] ?? 0.0);
                    $selected_month_summary['salary_cost'] = (float) ($trend_row['salary_cost'] ?? 0.0);
                    $selected_month_summary['project_direct_cost'] = (float) ($trend_row['project_direct_cost'] ?? 0.0);
                    $selected_month_summary['time_cost'] = (float) ($trend_row['time_cost'] ?? 0.0);
                    $selected_month_summary['fixed_cost'] = (float) ($trend_row['fixed_cost'] ?? 0.0);
                    break;
                }

                include ERP_OMD_PATH . 'templates/admin/finances.php';
            }
        );
        add_submenu_page('erp-omd', __('Alerty', 'erp-omd'), __('Alerty', 'erp-omd'), 'erp_omd_access', 'erp-omd-alerts', [$this, 'render_alerts']);
        $this->add_submenu_separator('erp-omd', 'erp-omd-separator-settings');
        add_submenu_page('erp-omd', __('Ustawienia', 'erp-omd'), __('Ustawienia', 'erp-omd'), 'erp_omd_manage_settings', 'erp-omd-settings', [$this, 'render_settings']);
    }

    public function enqueue_assets($hook)
    {
        if (strpos($hook, 'erp-omd') === false) {
            return;
        }
        $admin_style_path = ERP_OMD_PATH . 'assets/css/admin.css';
        $admin_script_path = ERP_OMD_PATH . 'assets/js/admin.js';
        $admin_style_version = is_readable($admin_style_path) ? (string) filemtime($admin_style_path) : ERP_OMD_VERSION;
        $admin_script_version = is_readable($admin_script_path) ? (string) filemtime($admin_script_path) : ERP_OMD_VERSION;
        wp_enqueue_style('erp-omd-admin', ERP_OMD_URL . 'assets/css/admin.css', [], $admin_style_version);
        wp_add_inline_style('erp-omd-admin', '#toplevel_page_erp-omd .wp-submenu a[href*="page=erp-omd-separator-"]{pointer-events:none;opacity:.5;cursor:default;border-top:1px solid rgba(255,255,255,.18);margin-top:4px;padding-top:8px;padding-bottom:8px;}');
        wp_enqueue_script('erp-omd-admin', ERP_OMD_URL . 'assets/js/admin.js', [], $admin_script_version, true);
        wp_localize_script('erp-omd-admin', 'erpOmdAdminData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'inlineProjectNonce' => wp_create_nonce('erp_omd_inline_project_update'),
            'restUrl' => esc_url_raw(rest_url('erp-omd/v1/')),
            'restNonce' => wp_create_nonce('wp_rest'),
            'adminReportsUrl' => esc_url_raw(admin_url('admin.php?page=erp-omd-reports')),
        ]);
        wp_enqueue_media();
    }

    private function add_submenu_separator($parent_slug, $menu_slug)
    {
        add_submenu_page(
            $parent_slug,
            '',
            '────────',
            'erp_omd_access',
            $menu_slug,
            '__return_null'
        );
    }

    public function handle_forms()
    {
        if (
            is_admin()
            && isset($_GET['page'])
            && (string) $_GET['page'] === 'erp-omd-settings'
            && (
                isset($_GET['erp_omd_google_oauth_callback'])
                || isset($_GET['code'])
                || isset($_GET['error'])
            )
        ) {
            $this->handle_google_calendar_oauth_callback();
            return;
        }

        if (! is_admin() || empty($_POST['erp_omd_action'])) {
            return;
        }

        $action = sanitize_text_field(wp_unslash($_POST['erp_omd_action']));
        switch ($action) {
            case 'save_role': $this->handle_role_save(); break;
            case 'delete_role': $this->handle_role_delete(); break;
            case 'save_employee': $this->handle_employee_save(); break;
            case 'inline_update_employee': $this->handle_inline_employee_update_action(); break;
            case 'change_employee_password': $this->handle_employee_password_change(); break;
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
            case 'send_estimate_client_link': $this->handle_send_estimate_client_decision_link(); break;
            case 'export_estimate': $this->handle_estimate_export(); break;
            case 'export_report': $this->handle_report_export(); break;
            case 'export_adjustments_audit':
                check_admin_referer('erp_omd_export_adjustments_audit');
                $this->require_capability('erp_omd_manage_settings');

                $month = sanitize_text_field((string) ($_POST['adjustment_month'] ?? ''));
                if ($month !== '' && ! $this->is_valid_month_string($month)) {
                    $month = '';
                }
                $entity_type = sanitize_text_field((string) ($_POST['adjustment_entity_type'] ?? ''));
                $adjustment_type = sanitize_text_field((string) ($_POST['adjustment_type'] ?? ''));
                $changed_by = max(0, (int) ($_POST['adjustment_changed_by'] ?? 0));
                $reason = sanitize_text_field((string) ($_POST['adjustment_reason'] ?? ''));
                $limit = max(1, min(500, (int) ($_POST['adjustment_limit'] ?? 200)));

                $filters = [
                    'month' => $month,
                    'entity_type' => $entity_type,
                    'adjustment_type' => $adjustment_type,
                    'changed_by' => $changed_by,
                    'reason' => $reason,
                    'limit' => $limit,
                ];
                if (! $this->adjustment_audit || ! method_exists($this->adjustment_audit, 'all')) {
                    wp_die(esc_html__('Repozytorium audytu korekt nie jest dostępne.', 'erp-omd'));
                }
                $rows = (array) $this->adjustment_audit->all(array_filter($filters));

                nocache_headers();
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . sanitize_file_name(sprintf('erp-omd-adjustment-audit-%s.csv', gmdate('Ymd-His'))) . '"');

                $output = fopen('php://output', 'w');
                if (! $output) {
                    wp_die(esc_html__('Nie udało się przygotować pliku audytu korekt.', 'erp-omd'));
                }

                fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($output, ['id', 'month', 'entity_type', 'entity_id', 'field_name', 'old_value', 'new_value', 'adjustment_type', 'reason', 'changed_by', 'changed_at'], ';');
                foreach ($rows as $row) {
                    fputcsv($output, [
                        (int) ($row['id'] ?? 0),
                        (string) ($row['month'] ?? ''),
                        (string) ($row['entity_type'] ?? ''),
                        (int) ($row['entity_id'] ?? 0),
                        (string) ($row['field_name'] ?? ''),
                        (string) ($row['old_value'] ?? ''),
                        (string) ($row['new_value'] ?? ''),
                        (string) ($row['adjustment_type'] ?? ''),
                        (string) ($row['reason'] ?? ''),
                        (int) ($row['changed_by'] ?? 0),
                        (string) ($row['changed_at'] ?? ''),
                    ], ';');
                }

                fclose($output);
                exit;
                break;
            case 'save_project': $this->handle_project_save(); break;
            case 'save_supplier': $this->handle_supplier_save(); break;
            case 'delete_supplier': $this->handle_supplier_delete(); break;
            case 'save_cost_invoice': $this->handle_cost_invoice_save(); break;
            case 'delete_cost_invoice': $this->handle_cost_invoice_delete(); break;
            case 'attach_cost_invoice_to_project': $this->handle_attach_cost_invoice_to_project(); break;
            case 'moderate_ksef_queue': $this->handle_ksef_queue_moderation_action(); break;
            case 'bulk_ksef_queue': $this->handle_ksef_queue_bulk_action(); break;
            case 'import_ksef_sales_xml': $this->handle_import_ksef_sales_xml_action(); break;
            case 'import_ksef_cost_xml': $this->handle_import_ksef_cost_xml_action(); break;
            case 'attach_ksef_sales_invoice': $this->handle_attach_ksef_sales_invoice_action(); break;
            case 'inline_update_project': $this->handle_inline_project_update_action(); break;
            case 'duplicate_project': $this->handle_project_duplicate(); break;
            case 'toggle_project_active': $this->handle_project_active_toggle(); break;
            case 'bulk_clients': $this->handle_clients_bulk_action(); break;
            case 'bulk_projects': $this->handle_projects_bulk_action(); break;
            case 'bulk_estimates': $this->handle_estimates_bulk_action(); break;
            case 'run_manual_backup':
                check_admin_referer('erp_omd_run_manual_backup');
                $this->require_capability('erp_omd_manage_settings');

                ERP_OMD_Backup_Manager::run_backup_bundle();
                $last_backup_status = (string) get_option('erp_omd_last_backup_status', '');

                if ($last_backup_status === 'success') {
                    $this->redirect_with_notice('erp-omd-settings', 'success', __('Backup bazy + ustawień wtyczki został wykonany ręcznie.', 'erp-omd'));
                }

                $this->redirect_with_notice(
                    'erp-omd-settings',
                    'error',
                    sprintf(__('Ręczny backup nie powiódł się (status: %s).', 'erp-omd'), $last_backup_status !== '' ? $last_backup_status : 'unknown')
                );
                break;
            case 'restore_backup_bundle':
                check_admin_referer('erp_omd_restore_backup_bundle');
                $this->require_capability('erp_omd_manage_settings');
                if (! isset($_FILES['restore_backup_zip']) || ! is_array($_FILES['restore_backup_zip'])) {
                    $this->redirect_with_notice('erp-omd-settings', 'error', __('Nie wybrano pliku ZIP backupu do odtworzenia.', 'erp-omd'));
                }
                $backup_file = $_FILES['restore_backup_zip'];
                $tmp_name = isset($backup_file['tmp_name']) ? (string) $backup_file['tmp_name'] : '';
                $file_name = isset($backup_file['name']) ? (string) $backup_file['name'] : '';
                $upload_error = isset($backup_file['error']) ? (int) $backup_file['error'] : UPLOAD_ERR_NO_FILE;
                if ($upload_error !== UPLOAD_ERR_OK || $tmp_name === '' || ! is_uploaded_file($tmp_name)) {
                    $this->redirect_with_notice('erp-omd-settings', 'error', __('Przesłany plik backupu jest nieprawidłowy.', 'erp-omd'));
                }
                if (strtolower(pathinfo($file_name, PATHINFO_EXTENSION)) !== 'zip') {
                    $this->redirect_with_notice('erp-omd-settings', 'error', __('Plik backupu musi mieć rozszerzenie .zip.', 'erp-omd'));
                }
                try {
                    ERP_OMD_Backup_Manager::restore_backup_bundle_from_zip($tmp_name);
                } catch (RuntimeException $exception) {
                    $this->redirect_with_notice('erp-omd-settings', 'error', sprintf(__('Odtworzenie backupu nie powiodło się: %s', 'erp-omd'), $exception->getMessage()));
                }
                $this->redirect_with_notice('erp-omd-settings', 'success', __('Odtworzenie backupu (SQL + ustawienia) zakończone sukcesem.', 'erp-omd'));
                break;
            case 'add_project_note': $this->handle_project_note_add(); break;
            case 'save_project_rate': $this->handle_project_rate_save(); break;
            case 'delete_project_rate': $this->handle_project_rate_delete(); break;
            case 'save_project_cost': $this->handle_project_cost_save(); break;
            case 'delete_project_cost': $this->handle_project_cost_delete(); break;
            case 'save_project_revenue':
                check_admin_referer('erp_omd_save_project_revenue');
                $this->require_capability('erp_omd_manage_projects');
                $id = empty($_POST['project_revenue_id']) ? 0 : (int) $_POST['project_revenue_id'];
                $project_id = (int) ($_POST['project_id'] ?? 0);
                $project = $this->projects->find($project_id);
                if (! $project) {
                    $this->redirect_with_notice('erp-omd-projects', 'error', __('Projekt nie istnieje.', 'erp-omd'));
                }
                if ($this->is_project_cost_locked_by_status((string) ($project['status'] ?? ''))) {
                    $this->redirect_with_notice(
                        'erp-omd-projects',
                        'error',
                        __('Przychody projektu po statusie Zakończony/Archiwum modyfikuj wyłącznie przez admina po zamknięciu miesiąca.', 'erp-omd'),
                        ['id' => $project_id]
                    );
                }
                $payload = [
                    'project_id' => $project_id,
                    'amount' => (float) ($_POST['amount'] ?? 0),
                    'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
                    'revenue_date' => sanitize_text_field(wp_unslash($_POST['revenue_date'] ?? '')),
                    'created_by_user_id' => get_current_user_id(),
                ];
                $errors = $this->project_financial_service->validate_project_revenue($payload);
                if ($errors) {
                    $this->redirect_with_notice('erp-omd-projects', 'error', implode(' ', $errors), ['id' => $project_id]);
                }
                if ($id) {
                    $this->project_revenues->update($id, $payload);
                    $message = __('Pozycja przychodowa projektu została zaktualizowana.', 'erp-omd');
                } else {
                    $this->project_revenues->create($payload);
                    $message = __('Pozycja przychodowa projektu została dodana.', 'erp-omd');
                }
                $this->project_financial_service->rebuild_for_project($project_id);
                $this->redirect_with_notice('erp-omd-projects', 'success', $message, ['id' => $project_id]);
                break;
            case 'delete_project_revenue':
                check_admin_referer('erp_omd_delete_project_revenue');
                $this->require_capability('erp_omd_manage_projects');
                $id = (int) ($_POST['project_revenue_id'] ?? 0);
                $project_id = (int) ($_POST['project_id'] ?? 0);
                $existing = $id > 0 ? $this->project_revenues->find($id) : null;
                if ($project_id <= 0 && is_array($existing)) {
                    $project_id = (int) ($existing['project_id'] ?? 0);
                }
                $project = $project_id > 0 ? $this->projects->find($project_id) : null;
                if (is_array($project) && $this->is_project_cost_locked_by_status((string) ($project['status'] ?? ''))) {
                    $this->redirect_with_notice(
                        'erp-omd-projects',
                        'error',
                        __('Przychody projektu po statusie Zakończony/Archiwum modyfikuj wyłącznie przez admina po zamknięciu miesiąca.', 'erp-omd'),
                        ['id' => $project_id]
                    );
                }
                if ($id) {
                    $this->project_revenues->delete($id);
                    $this->project_financial_service->rebuild_for_project($project_id);
                }
                $this->redirect_with_notice('erp-omd-projects', 'success', __('Pozycja przychodowa projektu została usunięta.', 'erp-omd'), ['id' => $project_id]);
                break;
            case 'save_time_entry': $this->handle_time_entry_save(); break;
            case 'inline_update_time_entry': $this->handle_inline_time_entry_update_action(); break;
            case 'change_time_status': $this->handle_time_status_change(); break;
            case 'delete_time_entry': $this->handle_time_entry_delete(); break;
            case 'bulk_time_entries': $this->handle_time_entries_bulk_action(); break;
            case 'update_project_request_status': $this->handle_project_request_status_update_action(); break;
            case 'convert_project_request': $this->handle_project_request_conversion_action(); break;
            case 'delete_project_request': $this->handle_project_request_delete_action(); break;
            case 'add_attachment': $this->handle_attachment_add(); break;
            case 'delete_attachment': $this->handle_attachment_delete(); break;
            case 'save_settings': $this->handle_settings_save(); break;
            case 'google_calendar_connect': $this->handle_google_calendar_connect(); break;
            case 'google_calendar_disconnect': $this->handle_google_calendar_disconnect(); break;
            case 'google_calendar_sync_now': $this->handle_google_calendar_sync_now(); break;
            case 'google_calendar_fetch_calendars': $this->handle_google_calendar_fetch_calendars(); break;
            case 'ksef_api_sync_now': $this->handle_ksef_api_sync_now(); break;
            case 'ksef_fetch_public_key': $this->handle_ksef_fetch_public_key(); break;
            case 'delete_client': $this->handle_client_delete(); break;
            case 'delete_project': $this->handle_project_delete(); break;
        }
    }

    public function render_dashboard()
    {
        $employees = $this->employees->all();
        $clients = $this->clients->all();
        $projects = $this->projects->all();
        $alerts = $this->alert_service->all_alerts();
        $reporting_month = sanitize_text_field(wp_unslash($_GET['reporting_month'] ?? current_time('Y-m')));
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $reporting_month)) {
            $reporting_month = current_time('Y-m');
        }
        $reporting_month_label = $reporting_month;
        $reporting_month_date = DateTimeImmutable::createFromFormat('Y-m-d', $reporting_month . '-01');
        if ($reporting_month_date instanceof DateTimeImmutable) {
            $reporting_month_label = wp_date('m.Y', $reporting_month_date->getTimestamp());
        }
        $monthly_metrics = $this->build_monthly_performance_metrics($reporting_month);
        $monthly_totals = $monthly_metrics['totals'] ?? [
            'reported_hours' => 0.0,
            'hourly_cost_total' => 0.0,
            'employee_profit' => 0.0,
            'active_employees' => 0,
        ];
        $omd_report_rows = $this->reporting_service->build_report('omd_rozliczenia', [
            'month' => $reporting_month,
            'report_type' => 'omd_rozliczenia',
        ]);
        $omd_month_row = null;
        foreach ((array) $omd_report_rows as $report_row) {
            if ((string) ($report_row['month'] ?? '') === $reporting_month) {
                $omd_month_row = $report_row;
                break;
            }
        }
        if (! is_array($omd_month_row)) {
            $omd_month_row = [];
        }
        $dashboard_project_monthly_cost = round((float) ($omd_month_row['project_direct_cost'] ?? 0.0), 2);
        $dashboard_employee_time_cost = round((float) ($omd_month_row['time_cost'] ?? 0.0), 2);
        $dashboard_employee_hourly_profit = round((float) ($omd_month_row['hourly_profit'] ?? 0.0), 2);
        $dashboard_controlling_result = $this->resolve_dashboard_controlling_result($omd_month_row);
        $dashboard_active_projects_count = $this->count_dashboard_active_projects_for_month($projects, $reporting_month);
        $dashboard_monthly_finance_metrics = [
            [
                'key' => 'project_cost',
                'label' => __('Koszty miesięczne projektów', 'erp-omd'),
                'value' => $dashboard_project_monthly_cost,
                'tone' => 'cost',
            ],
            [
                'key' => 'employee_time_cost',
                'label' => __('Koszty pracy pracowników', 'erp-omd'),
                'value' => $dashboard_employee_time_cost,
                'tone' => 'cost',
            ],
            [
                'key' => 'employee_hourly_profit',
                'label' => __('Zysk z pracy pracowników', 'erp-omd'),
                'value' => $dashboard_employee_hourly_profit,
                'tone' => 'profit',
            ],
            [
                'key' => 'controlling_result',
                'label' => __('Wynik controllingowy', 'erp-omd'),
                'value' => $dashboard_controlling_result,
                'tone' => $dashboard_controlling_result >= 0 ? 'profit' : 'loss',
            ],
        ];
        $dashboard_monthly_finance_max = 1.0;
        foreach ($dashboard_monthly_finance_metrics as $metric_row) {
            $dashboard_monthly_finance_max = max($dashboard_monthly_finance_max, abs((float) ($metric_row['value'] ?? 0.0)));
        }
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

    private function resolve_dashboard_controlling_result(array $omd_month_row)
    {
        $controlling_result = (float) ($omd_month_row['controlling_result'] ?? 0.0);
        if (isset($omd_month_row['controlling_result']) && is_numeric($omd_month_row['controlling_result'])) {
            return round($controlling_result, 2);
        }

        $operational_result = (float) ($omd_month_row['operational_result'] ?? 0.0);
        $controlling_overhead = (float) ($omd_month_row['controlling_overhead'] ?? 0.0);
        return round($operational_result - $controlling_overhead, 2);
    }

    private function count_dashboard_active_projects_for_month(array $projects, $reporting_month)
    {
        $active_statuses = ['archiwum', 'zakonczony'];
        $count = 0;

        foreach ($projects as $project) {
            $status = (string) ($project['status'] ?? '');
            if (in_array($status, $active_statuses, true)) {
                continue;
            }

            $end_date = (string) ($project['end_date'] ?? '');
            if ($end_date === '') {
                $count++;
                continue;
            }

            if (substr($end_date, 0, 7) === (string) $reporting_month) {
                $count++;
            }
        }

        return $count;
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
        $selected_client_projects = [];
        $selected_client_project_financials = [];
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
        $client_filters = [
            'search' => sanitize_text_field(wp_unslash($_GET['search'] ?? '')),
            'status' => sanitize_text_field(wp_unslash($_GET['status'] ?? '')),
            'page_num' => max(1, (int) ($_GET['page_num'] ?? 1)),
            'per_page' => (int) ($_GET['per_page'] ?? 100),
        ];
        if (! in_array($client_filters['per_page'], [25, 50, 100, 200], true)) {
            $client_filters['per_page'] = 100;
        }
        $client_query_filters = array_filter([
            'search' => $client_filters['search'],
            'status' => $client_filters['status'],
        ], [$this, 'is_query_filter']);
        $client_pagination = [
            'total_items' => $this->clients->count_filtered($client_query_filters),
            'per_page' => $client_filters['per_page'],
            'page_num' => $client_filters['page_num'],
        ];
        $client_pagination['total_pages'] = max(1, (int) ceil($client_pagination['total_items'] / max(1, $client_pagination['per_page'])));
        if ($client_pagination['page_num'] > $client_pagination['total_pages']) {
            $client_pagination['page_num'] = $client_pagination['total_pages'];
        }
        $clients = $this->clients->find_paged(
            $client_query_filters,
            $client_pagination['per_page'],
            ($client_pagination['page_num'] - 1) * $client_pagination['per_page']
        );
        foreach ($clients as &$client_row) {
            $client_row['total_profit'] = (float) ($client_profit_totals[(int) $client_row['id']] ?? 0);
            $client_row['alerts'] = $client_alerts[(int) $client_row['id']] ?? [];
        }
        unset($client_row);
        $roles = $this->roles->all();
        $employees_for_select = $this->employees->all();
        if ($selected_client) {
            $employee_logins = [];
            foreach ($employees_for_select as $employee_row) {
                $employee_logins[(int) ($employee_row['id'] ?? 0)] = (string) ($employee_row['user_login'] ?? '');
            }
            $selected_client['account_manager_login'] = $employee_logins[(int) ($selected_client['account_manager_id'] ?? 0)] ?? '';
            $selected_client['total_profit'] = (float) ($client_profit_totals[(int) ($selected_client['id'] ?? 0)] ?? 0.0);
            $selected_client_projects = $this->projects->all(['client_id' => (int) ($selected_client['id'] ?? 0)]);
            $selected_client_project_financials = $this->project_financial_service->get_project_financials(
                wp_list_pluck($selected_client_projects, 'id')
            );
        }
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
        $estimate_filters = [
            'search' => sanitize_text_field(wp_unslash($_GET['search'] ?? '')),
            'status' => sanitize_text_field(wp_unslash($_GET['status'] ?? '')),
            'client_id' => (int) ($_GET['client_id'] ?? 0),
            'month' => sanitize_text_field(wp_unslash($_GET['month'] ?? '')),
            'page_num' => max(1, (int) ($_GET['page_num'] ?? 1)),
            'per_page' => (int) ($_GET['per_page'] ?? 100),
        ];
        if (! in_array($estimate_filters['per_page'], [25, 50, 100, 200], true)) {
            $estimate_filters['per_page'] = 100;
        }
        $estimate_query_filters = array_filter([
            'search' => $estimate_filters['search'],
            'status' => $estimate_filters['status'],
            'client_id' => $estimate_filters['client_id'],
            'month' => $estimate_filters['month'],
        ], [$this, 'is_query_filter']);
        $estimate_pagination = [
            'total_items' => $this->estimates->count_filtered($estimate_query_filters),
            'per_page' => $estimate_filters['per_page'],
            'page_num' => $estimate_filters['page_num'],
        ];
        $estimate_pagination['total_pages'] = max(1, (int) ceil($estimate_pagination['total_items'] / max(1, $estimate_pagination['per_page'])));
        if ($estimate_pagination['page_num'] > $estimate_pagination['total_pages']) {
            $estimate_pagination['page_num'] = $estimate_pagination['total_pages'];
        }
        $estimates = $this->estimates->find_paged(
            $estimate_query_filters,
            $estimate_pagination['per_page'],
            ($estimate_pagination['page_num'] - 1) * $estimate_pagination['per_page']
        );
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
        $clients = $this->clients->all();
        include ERP_OMD_PATH . 'templates/admin/estimates.php';
    }

    public function render_projects()
    {
        $project = null;
        $project_notes = [];
        $project_rates = [];
        $project_cost_rows = [];
        $project_cost_invoice_rows = [];
        $project_revenue_rows = [];
        $project_cost_edit_row = null;
        $project_revenue_edit_row = null;
        $project_financial = null;
        $project_financials_by_project = [];
        if (! empty($_GET['id'])) {
            $project = $this->projects->find((int) $_GET['id']);
            if ($project) {
                $project_notes = $this->project_notes->for_project((int) $project['id']);
                $project_rates = $this->project_rates->for_project((int) $project['id']);
                $project_cost_rows = $this->project_costs->for_project((int) $project['id']);
                $project_cost_invoice_rows = (new ERP_OMD_Cost_Invoice_Repository())->list(['project_id' => (int) $project['id']]);
                $project_revenue_rows = $this->project_revenues->for_project((int) $project['id']);
                $edit_project_cost_id = (int) ($_GET['edit_project_cost_id'] ?? 0);
                if ($edit_project_cost_id > 0) {
                    $candidate_project_cost = $this->project_costs->find($edit_project_cost_id);
                    if (is_array($candidate_project_cost) && (int) ($candidate_project_cost['project_id'] ?? 0) === (int) $project['id']) {
                        $project_cost_edit_row = $candidate_project_cost;
                    }
                }
                $edit_project_revenue_id = (int) ($_GET['edit_project_revenue_id'] ?? 0);
                if ($edit_project_revenue_id > 0) {
                    $candidate_project_revenue = $this->project_revenues->find($edit_project_revenue_id);
                    if (is_array($candidate_project_revenue) && (int) ($candidate_project_revenue['project_id'] ?? 0) === (int) $project['id']) {
                        $project_revenue_edit_row = $candidate_project_revenue;
                    }
                }
                $project_financial = $this->project_financial_service->rebuild_for_project((int) $project['id']);
                $project_financials_by_project[(int) $project['id']] = $project_financial;
                $project['deadline_status'] = $this->resolve_project_deadline_status($project);
                $project['deadline_status_label'] = $this->project_deadline_status_label($project['deadline_status']);
            }
        }
        $projects = $this->projects->all();
        if ($project) {
            foreach ($projects as $project_row) {
                if ((int) $project_row['id'] === (int) $project['id']) {
                    $project['client_name'] = $project_row['client_name'] ?? '';
                    $project['manager_login'] = $project_row['manager_login'] ?? '';
                    $project['manager_ids'] = $project_row['manager_ids'] ?? [];
                    $project['manager_logins_display'] = $project_row['manager_logins_display'] ?? '';
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
            $project_row['deadline_status'] = $this->resolve_project_deadline_status($project_row);
            $project_row['deadline_status_label'] = $this->project_deadline_status_label($project_row['deadline_status']);
        }
        unset($project_row);
        $project_filters = [
            'search' => sanitize_text_field(wp_unslash($_GET['search'] ?? '')),
            'client_id' => (int) ($_GET['client_id'] ?? 0),
            'manager_id' => (int) ($_GET['manager_id'] ?? 0),
            'status' => sanitize_text_field(wp_unslash($_GET['status'] ?? '')),
            'month' => sanitize_text_field(wp_unslash($_GET['month'] ?? '')),
        ];
        $projects_list_view = sanitize_key(wp_unslash($_GET['list_view'] ?? 'active'));
        if (! in_array($projects_list_view, ['active', 'archive'], true)) {
            $projects_list_view = 'active';
        }
        $projects = array_values(array_filter($projects, function ($project_row) use ($project_filters) {
            if ($project_filters['search'] !== '') {
                $haystack = strtolower(implode(' ', [
                    (string) ($project_row['name'] ?? ''),
                    (string) ($project_row['client_name'] ?? ''),
                    (string) ($project_row['manager_logins_display'] ?? ($project_row['manager_login'] ?? '')),
                ]));
                if (strpos($haystack, strtolower($project_filters['search'])) === false) {
                    return false;
                }
            }
            if ($project_filters['client_id'] > 0 && (int) ($project_row['client_id'] ?? 0) !== $project_filters['client_id']) {
                return false;
            }
            if ($project_filters['manager_id'] > 0 && ! in_array($project_filters['manager_id'], array_map('intval', (array) ($project_row['manager_ids'] ?? [])), true)) {
                return false;
            }
            if ($project_filters['status'] !== '' && (string) ($project_row['status'] ?? '') !== $project_filters['status']) {
                return false;
            }
            if ($project_filters['month'] !== '') {
                $project_month_source = (string) (($project_row['start_date'] ?? '') !== '' ? ($project_row['start_date'] ?? '') : ($project_row['created_at'] ?? ''));
                if (substr($project_month_source, 0, 7) !== $project_filters['month']) {
                    return false;
                }
            }

            return true;
        }));
        $projects = array_values(array_filter($projects, function ($project_row) use ($projects_list_view, $project_filters) {
            $status = (string) ($project_row['status'] ?? '');
            if ($projects_list_view === 'archive') {
                return $status === 'archiwum';
            }

            if ($status === 'archiwum' && $project_filters['status'] === '') {
                return false;
            }

            return true;
        }));
        $project_attachments = $project ? $this->attachments->for_entity('project', (int) $project['id']) : [];
        include ERP_OMD_PATH . 'templates/admin/projects.php';
    }

    public function render_time_entries()
    {
        include ERP_OMD_PATH . 'includes/render-time-entries-runtime.php';
    }

    private function is_query_filter($value)
    {
        return $value !== '' && $value !== null;
    }

    public function render_settings()
    {
        $delete_data = (bool) get_option('erp_omd_delete_data_on_uninstall', false);
        $front_admin_redirect_enabled = (bool) get_option('erp_omd_front_admin_redirect_enabled', true);
        $margin_threshold = (float) get_option('erp_omd_alert_margin_threshold', 10);
        $company_nip = preg_replace('/[^0-9]/', '', (string) get_option('erp_omd_company_nip', ''));
        $reports_v1_metrics_freshness_minutes = max(5, (int) get_option('erp_omd_reports_v1_metrics_freshness_minutes', 1440));
        $reports_v1_slo_generation_p95_max = max(100, min(30000, (int) get_option('erp_omd_reports_v1_slo_generation_p95_max', 2500)));
        $reports_v1_slo_recommended_p95_max = $reports_v1_slo_generation_p95_max;
        $reports_v1_slo_calibration_sample_count = 0;
        $reports_v1_slo_calibration_sample_target = 20;
        $reports_v1_slo_samples_missing_to_calibration = $reports_v1_slo_calibration_sample_target;
        $reports_v1_slo_calibration_decision_ready = false;
        $reports_v1_slo_calibration_next_action = __('Zbieraj próbki metryk raportów, aby domknąć kalibrację SLO.', 'erp-omd');
        $reports_v1_slo_last_decision = (array) get_option('erp_omd_reports_v1_slo_calibration_decision', []);
        $reports_v1_slo_closure = (array) get_option('erp_omd_reports_v1_slo_calibration_closure', []);
        $reports_v1_slo_closure_confirmed = ! empty($reports_v1_slo_closure['closed_at']) && ! empty($reports_v1_slo_closure['closed_by_user_id']);
        $reports_v1_metrics_log = (array) get_option('erp_omd_reports_v1_metrics_log', []);
        $reports_v1_samples = array_values(array_map(static function ($row) {
            return (int) ($row['generation_ms'] ?? 0);
        }, $reports_v1_metrics_log));
        sort($reports_v1_samples);
        $reports_v1_slo_calibration_sample_count = count($reports_v1_samples);
        $reports_v1_slo_samples_missing_to_calibration = max(0, (int) $reports_v1_slo_calibration_sample_target - (int) $reports_v1_slo_calibration_sample_count);
        $reports_v1_slo_calibration_decision_ready = $reports_v1_slo_samples_missing_to_calibration === 0;
        if ($reports_v1_slo_calibration_sample_count > 0) {
            $reports_v1_p95_index = (int) ceil(0.95 * $reports_v1_slo_calibration_sample_count) - 1;
            $reports_v1_p95_index = max(0, min($reports_v1_slo_calibration_sample_count - 1, $reports_v1_p95_index));
            $reports_v1_generation_p95 = (int) ($reports_v1_samples[$reports_v1_p95_index] ?? 0);
            $reports_v1_slo_recommended_p95_max = (int) ceil(max(500, $reports_v1_generation_p95 * 1.2) / 50) * 50;
            $reports_v1_slo_recommended_p95_max = max(100, min(30000, $reports_v1_slo_recommended_p95_max));
        }
        if ($reports_v1_slo_closure_confirmed) {
            $reports_v1_slo_calibration_next_action = __('Kalibracja formalnie zamknięta: monitoruj SLO w trybie steady-state i aktualizuj próg tylko przy trwałej zmianie trendu.', 'erp-omd');
        } elseif ($reports_v1_slo_calibration_decision_ready) {
            $reports_v1_slo_calibration_next_action = __('Kalibracja gotowa: zweryfikuj rekomendowany próg p95 i zapisz finalną wartość.', 'erp-omd');
        } else {
            $reports_v1_slo_calibration_next_action = sprintf(
                __('Kalibracja jeszcze niegotowa: zbierz %d dodatkowych próbek.', 'erp-omd'),
                (int) $reports_v1_slo_samples_missing_to_calibration
            );
        }
        $front_login_logo_id = (int) get_option('erp_omd_front_login_logo_id', 0);
        $front_login_cover_id = (int) get_option('erp_omd_front_login_cover_id', 0);
        $front_login_logo_url = $front_login_logo_id > 0 ? (string) wp_get_attachment_image_url($front_login_logo_id, 'medium') : '';
        $front_login_cover_url = $front_login_cover_id > 0 ? (string) wp_get_attachment_image_url($front_login_cover_id, 'large') : '';
        $front_login_logo_name = $front_login_logo_id > 0 ? (get_the_title($front_login_logo_id) ?: ('#' . $front_login_logo_id)) : __('Brak wybranego pliku.', 'erp-omd');
        $front_login_cover_name = $front_login_cover_id > 0 ? (get_the_title($front_login_cover_id) ?: ('#' . $front_login_cover_id)) : __('Brak wybranego pliku.', 'erp-omd');

        $notification_settings = $this->missing_hours_notification_defaults();
        $notification_settings = wp_parse_args((array) get_option('erp_omd_missing_hours_notification_settings', []), $notification_settings);
        $notification_settings['mode'] = in_array($notification_settings['mode'], ['after_x_days', 'day_of_month'], true) ? $notification_settings['mode'] : 'after_x_days';
        $notification_settings['after_days'] = max(1, (int) $notification_settings['after_days']);
        $notification_settings['day_of_month'] = min(31, max(1, (int) $notification_settings['day_of_month']));
        $fixed_monthly_cost = max(0.0, (float) get_option('erp_omd_fixed_monthly_cost', 0));
        $fixed_monthly_cost_items = $this->normalize_fixed_monthly_cost_items((array) get_option('erp_omd_fixed_monthly_cost_items', []));
        if (empty($fixed_monthly_cost_items) && $fixed_monthly_cost > 0) {
            $fixed_monthly_cost_items[] = [
                'name' => __('Koszt stały (legacy)', 'erp-omd'),
                'amount' => $fixed_monthly_cost,
                'valid_from' => '',
                'valid_to' => '',
                'active' => 1,
            ];
        }
        $notification_sender_email = sanitize_email((string) get_option('erp_omd_notification_sender_email', ''));

        $notification_recipients = (array) get_option('erp_omd_missing_hours_notification_recipients', []);
        $employees = $this->employees->all();
        foreach ($employees as &$employee_row) {
            $employee_id = (int) ($employee_row['id'] ?? 0);
            $state = (array) ($notification_recipients[$employee_id] ?? []);
            $employee_row['notification_active'] = array_key_exists('active', $state) ? ! empty($state['active']) : true;
            $employee_row['last_notification_at'] = (string) ($state['last_sent_at'] ?? '');
        }
        unset($employee_row);

        $last_backup_at = (string) get_option('erp_omd_last_backup_at', '');
        $last_backup_status = (string) get_option('erp_omd_last_backup_status', '');
        $last_backup_file = (string) get_option('erp_omd_last_backup_file', '');
        $google_calendar_client_id = (string) get_option('erp_omd_google_calendar_client_id', '');
        $google_calendar_client_secret_masked = $this->masked_secret($this->decrypt_option_value((string) get_option('erp_omd_google_calendar_client_secret_enc', '')));
        $google_calendar_redirect_uri = $this->google_calendar_redirect_uri();
        $google_calendar_scope = (string) get_option('erp_omd_google_calendar_scope', 'https://www.googleapis.com/auth/calendar');
        $google_calendar_calendar_id = (string) get_option('erp_omd_google_calendar_calendar_id', 'primary');
        $google_calendar_available_calendars = (array) get_option('erp_omd_google_calendar_available_calendars', []);
        $google_calendar_technical_account_email = (string) get_option('erp_omd_google_calendar_technical_account_email', '');
        $google_calendar_connected = $this->decrypt_option_value((string) get_option('erp_omd_google_calendar_refresh_token_enc', '')) !== '';
        $google_calendar_last_sync_at = (string) get_option('erp_omd_google_calendar_last_sync_at', '');
        $google_calendar_last_error = (string) get_option('erp_omd_google_calendar_last_error', '');
        $ksef_api_enabled = (bool) get_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_ENABLED, false);
        $ksef_api_mode = (string) get_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_MODE, 'from_now');
        $ksef_api_registration_date = (string) get_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_REGISTRATION_DATE, '');
        $ksef_api_backfill_days = max(1, min(90, (int) get_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_BACKFILL_DAYS, 90)));
        $ksef_api_alert_after_hours = max(1, (int) get_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_ALERT_AFTER_HOURS, 24));
        $ksef_api_base_url = (string) get_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_API_BASE_URL, 'https://api.ksef.mf.gov.pl');
        $ksef_auto_create_supplier = (bool) get_option(ERP_OMD_KSeF_Import_Service::OPTION_AUTO_CREATE_SUPPLIER, false);
        $ksef_api_token_masked = $this->masked_secret($this->decrypt_option_value((string) get_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_TOKEN_ENC, '')));
        $ksef_api_refresh_token_masked = $this->masked_secret($this->decrypt_option_value((string) get_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_REFRESH_TOKEN_ENC, '')));
        $ksef_ap_token_masked = $this->masked_secret($this->decrypt_option_value((string) get_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_AP_TOKEN_ENC, '')));
        $ksef_public_key_pem = (string) get_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_PUBLIC_KEY_PEM, '');
        $ksef_api_last_sync_at = (string) get_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_LAST_SYNC_AT, '');
        $ksef_api_last_error = (string) get_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_LAST_ERROR, '');
        $ksef_api_last_result = (array) get_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_LAST_RESULT, []);

        include ERP_OMD_PATH . 'templates/admin/settings.php';
    }

    public function render_alerts()
    {
        $alerts = $this->alert_service->all_alerts();
        include ERP_OMD_PATH . 'templates/admin/alerts.php';
    }

    public function render_calendar()
    {
        $calendar_month = sanitize_text_field(wp_unslash($_GET['calendar_month'] ?? current_time('Y-m')));
        if (! $this->is_valid_month_string($calendar_month)) {
            $calendar_month = current_time('Y-m');
        }

        $month_start = DateTimeImmutable::createFromFormat('Y-m-d', $calendar_month . '-01') ?: new DateTimeImmutable($calendar_month . '-01');
        $month_end = $month_start->modify('last day of this month');
        $previous_month = $month_start->modify('-1 month')->format('Y-m');
        $next_month = $month_start->modify('+1 month')->format('Y-m');
        $sync_repository = new ERP_OMD_Project_Calendar_Sync_Repository();

        $events = [];
        foreach ($this->projects->all() as $project) {
            $project_status = (string) ($project['status'] ?? '');
            $billing_type = (string) ($project['billing_type'] ?? '');
            if ($project_status === 'archiwum' || $billing_type === 'retainer') {
                continue;
            }

            $project_id = (int) ($project['id'] ?? 0);
            $sync_state = $sync_repository->find_by_project_id($project_id) ?: [];
            $sync_status = (string) ($sync_state['sync_status'] ?? 'pending');
            $sync_error = (string) ($sync_state['last_error'] ?? '');
            $project_name = (string) ($project['name'] ?? ('#' . $project_id));

            $start_date = (string) ($project['start_date'] ?? '');
            $end_date = (string) ($project['end_date'] ?? '');
            if ($start_date !== '' && $end_date !== '') {
                $range_start = DateTimeImmutable::createFromFormat('Y-m-d', $start_date);
                $range_end = DateTimeImmutable::createFromFormat('Y-m-d', $end_date);
                if ($range_start && $range_end && $range_end >= $month_start && $range_start <= $month_end) {
                    $events[] = [
                        'project_id' => $project_id,
                        'project_name' => $project_name,
                        'event_type' => 'range',
                        'date_start' => $start_date,
                        'date_end' => $end_date,
                        'status' => $project_status,
                        'sync_status' => $sync_status,
                        'sync_error' => $sync_error,
                    ];
                }
            }

            $deadline_date = (string) ($project['deadline_date'] ?? '');
            if ($deadline_date !== '' && strpos($deadline_date, $calendar_month . '-') === 0) {
                $events[] = [
                    'project_id' => $project_id,
                    'project_name' => $project_name,
                    'event_type' => 'deadline',
                    'date_start' => $deadline_date,
                    'date_end' => $deadline_date,
                    'status' => $project_status,
                    'sync_status' => $sync_status,
                    'sync_error' => $sync_error,
                ];
            }
        }

        include ERP_OMD_PATH . 'templates/admin/calendar.php';
    }

    public function render_reports()
    {
        $reports_v1_rollout = 'all';
        $reports_v1_enabled = true;
        $reports_v1_freshness_minutes = max(5, (int) get_option('erp_omd_reports_v1_metrics_freshness_minutes', 1440));
        $reports_v1_freshness_seconds = $reports_v1_freshness_minutes * 60;
        $previous_report_monitoring = (array) get_option('erp_omd_reports_v1_last_metrics', []);
        $previous_report_age_seconds = null;
        $previous_report_captured_at = (string) ($previous_report_monitoring['captured_at'] ?? '');
        if ($previous_report_captured_at !== '') {
            $previous_timestamp = strtotime($previous_report_captured_at);
            if ($previous_timestamp !== false) {
                $previous_report_age_seconds = max(0, (int) (time() - $previous_timestamp));
            }
        }

        $report_filters = $this->reporting_service->sanitize_filters($_GET);
        $requested_report_type = sanitize_key((string) ($_GET['report_type'] ?? ''));
        $allowed_report_types = ['projects', 'clients', 'invoice', 'monthly', 'omd_rozliczenia', 'time_entries'];
        if (! in_array($requested_report_type, $allowed_report_types, true)) {
            $requested_report_type = '';
        }
        if ($report_filters['tab'] === 'reports') {
            $report_filters['report_type'] = $requested_report_type;
        }
        $report_started_at = microtime(true);
        $report_rows = [];
        $report_error = false;
        $report_error_message = '';
        $report_error_notice = '';
        if ($report_filters['tab'] === 'reports' && $report_filters['report_type'] !== '') {
            try {
                $report_rows = $this->reporting_service->build_report($report_filters['report_type'], $report_filters);
            } catch (Throwable $error) {
                $report_error = true;
                $report_error_message = (string) $error->getMessage();
                $report_error_notice = __('Nie udało się zbudować raportu. Sprawdź logi systemowe i spróbuj ponownie.', 'erp-omd');
            }
        }
        $report_generation_ms = (int) round((microtime(true) - $report_started_at) * 1000);
        $report_pagination = (array) ($this->reporting_service->last_report_pagination ?? []);
        $calendar_data = $this->reporting_service->build_calendar($report_filters);
        $dashboard_preview_queue_limit = max(1, min(100, (int) ($_GET['dashboard_queue_limit'] ?? 25)));
        $dashboard_preview_filters = [
            'queue_limit' => $dashboard_preview_queue_limit,
        ];
        $metrics_log = (array) get_option('erp_omd_reports_v1_metrics_log', []);
        if ($report_filters['tab'] === 'reports' && $report_filters['report_type'] !== '') {
            $report_monitoring = [
                'generation_ms' => $report_generation_ms,
                'rows_count' => is_array($report_rows) ? count($report_rows) : 0,
                'report_type' => (string) ($report_filters['report_type'] ?? ''),
                'rollout' => $reports_v1_rollout,
                'enabled' => $reports_v1_enabled,
                'has_error' => $report_error,
                'error_message' => $report_error_message,
                'captured_at' => gmdate('c'),
                'freshness_threshold_minutes' => $reports_v1_freshness_minutes,
                'previous_metrics_age_seconds' => $previous_report_age_seconds,
                'previous_metrics_stale' => $previous_report_age_seconds === null ? null : ($previous_report_age_seconds > $reports_v1_freshness_seconds),
                'slo_sample_target_min' => 20,
            ];
            update_option('erp_omd_reports_v1_last_metrics', $report_monitoring);
            array_unshift($metrics_log, $report_monitoring);
            $metrics_log = array_slice($metrics_log, 0, 20);
            $report_monitoring['slo_sample_count'] = count($metrics_log);
            $report_monitoring['slo_samples_missing_to_calibration'] = max(0, (int) $report_monitoring['slo_sample_target_min'] - (int) $report_monitoring['slo_sample_count']);
            $report_monitoring['slo_calibration_decision_ready'] = (int) $report_monitoring['slo_samples_missing_to_calibration'] === 0;
            $report_monitoring['slo_calibration_next_action'] = ! empty($report_monitoring['slo_calibration_decision_ready'])
                ? __('Zweryfikuj rekomendowany próg p95 i zapisz finalną wartość w Ustawieniach.', 'erp-omd')
                : sprintf(
                    __('Zbierz jeszcze %d próbek, aby domknąć kalibrację SLO.', 'erp-omd'),
                    (int) $report_monitoring['slo_samples_missing_to_calibration']
                );
            update_option('erp_omd_reports_v1_metrics_log', $metrics_log);
        }
        $reports_v1_slo_generation_p95_max = max(100, min(30000, (int) get_option('erp_omd_reports_v1_slo_generation_p95_max', 2500)));
        $reports_v1_slo_closure = (array) get_option('erp_omd_reports_v1_slo_calibration_closure', []);
        $reports_v1_slo_calibration_closed = ! empty($reports_v1_slo_closure['closed_at']) && ! empty($reports_v1_slo_closure['closed_by_user_id']);
        $reports_v1_sustained_drift_window_size = 3;
        $reports_v1_recent_metrics_samples = array_slice($metrics_log, 0, $reports_v1_sustained_drift_window_size);
        $reports_v1_history_limit = 5;
        $reports_v1_history_samples = array_slice($metrics_log, 0, $reports_v1_history_limit);
        $reports_v1_history_drift_only = ! empty($_GET['steady_state_drift_only']) && (string) $_GET['steady_state_drift_only'] === '1';
        $reports_v1_recent_generation_samples = array_values(array_map(static function ($row) {
            return (int) ($row['generation_ms'] ?? 0);
        }, $reports_v1_recent_metrics_samples));
        $reports_v1_recent_error_samples = array_values(array_map(static function ($row) {
            return ! empty($row['has_error']);
        }, $reports_v1_recent_metrics_samples));
        $reports_v1_sustained_generation_drift = $reports_v1_slo_calibration_closed
            && count($reports_v1_recent_generation_samples) === $reports_v1_sustained_drift_window_size
            && count(array_filter($reports_v1_recent_generation_samples, static function ($sample) use ($reports_v1_slo_generation_p95_max) {
                return (int) $sample > (int) $reports_v1_slo_generation_p95_max;
            })) === $reports_v1_sustained_drift_window_size;
        $reports_v1_sustained_error_drift = $reports_v1_slo_calibration_closed
            && count($reports_v1_recent_error_samples) === $reports_v1_sustained_drift_window_size
            && count(array_filter($reports_v1_recent_error_samples)) === $reports_v1_sustained_drift_window_size;
        $reports_v1_sustained_drift_detected = $reports_v1_sustained_generation_drift || $reports_v1_sustained_error_drift;
        $reports_v1_history_drift_count = count(
            array_filter(
                $reports_v1_history_samples,
                static function ($row) use ($reports_v1_slo_generation_p95_max) {
                    $generation_ms = (int) ($row['generation_ms'] ?? 0);
                    return ! empty($row['has_error']) || $generation_ms > (int) $reports_v1_slo_generation_p95_max;
                }
            )
        );
        $reports_v1_history_total_count = count($reports_v1_history_samples);
        $reports_v1_history_drift_ratio_percent = $reports_v1_history_total_count > 0
            ? round(($reports_v1_history_drift_count / $reports_v1_history_total_count) * 100, 2)
            : 0.0;
        $reports_v1_steady_state_banner = [
            'level' => 'notice-info',
            'title' => __('Reports v1 — steady-state monitoring', 'erp-omd'),
            'message' => __('Kalibracja SLO nie jest jeszcze formalnie zamknięta — trwały dryf będzie oceniany po closure.', 'erp-omd'),
            'actions' => [
                __('Dokończ decyzję/closure kalibracji w Ustawieniach.', 'erp-omd'),
            ],
            'history' => array_values(array_filter(array_map(static function ($row) use ($reports_v1_slo_generation_p95_max) {
                $generation_ms = (int) ($row['generation_ms'] ?? 0);
                return [
                    'captured_at' => (string) ($row['captured_at'] ?? ''),
                    'report_type' => (string) ($row['report_type'] ?? ''),
                    'generation_ms' => $generation_ms,
                    'has_error' => ! empty($row['has_error']),
                    'generation_above_threshold' => $generation_ms > (int) $reports_v1_slo_generation_p95_max,
                ];
            }, $reports_v1_history_samples), static function ($row) use ($reports_v1_history_drift_only) {
                if (! $reports_v1_history_drift_only) {
                    return true;
                }
                return ! empty($row['has_error']) || ! empty($row['generation_above_threshold']);
            })),
            'history_drift_only' => $reports_v1_history_drift_only,
            'history_total_count' => $reports_v1_history_total_count,
            'history_drift_count' => $reports_v1_history_drift_count,
            'history_drift_ratio_percent' => $reports_v1_history_drift_ratio_percent,
            'history_last_sample_at' => (string) ($reports_v1_history_samples[0]['captured_at'] ?? ''),
        ];
        if ($reports_v1_slo_calibration_closed && ! $reports_v1_sustained_drift_detected) {
            $reports_v1_steady_state_banner['level'] = 'notice-success';
            $reports_v1_steady_state_banner['message'] = __('Kalibracja SLO jest zamknięta, a monitoring steady-state nie wykrywa trwałego dryfu metryk.', 'erp-omd');
            $reports_v1_steady_state_banner['actions'] = [
                __('Kontynuuj obserwację trendu p95/error-rate i reaguj tylko przy trwałym dryfie.', 'erp-omd'),
            ];
        }
        if ($reports_v1_sustained_drift_detected) {
            $reports_v1_steady_state_banner['level'] = 'notice-warning';
            $reports_v1_steady_state_banner['message'] = __('Wykryto trwały dryf metryk Reports v1 — uruchom playbook rollback/tuning.', 'erp-omd');
            $reports_v1_steady_state_banner['actions'] = [
                __('Sprawdź runbook on-call i wykonaj rollback/tuning dla ciężkich raportów.', 'erp-omd'),
                __('Zweryfikuj system/status (reasons, recommended_actions) przed zmianą progów.', 'erp-omd'),
            ];
        }
        $reports_v1_runbook_url = admin_url('admin.php?page=erp-omd-settings#reports-v1-slo-monitoring');
        $reports_v1_monitoring_summary = [
            'slo_generation_p95_max' => (int) $reports_v1_slo_generation_p95_max,
            'freshness_minutes' => (int) $reports_v1_freshness_minutes,
            'drift_ratio_percent' => (float) $reports_v1_history_drift_ratio_percent,
            'calibration_closed' => (bool) $reports_v1_slo_calibration_closed,
            'sustained_drift_detected' => (bool) $reports_v1_sustained_drift_detected,
            'last_sample_at' => (string) ($reports_v1_history_samples[0]['captured_at'] ?? ''),
        ];
        $reports_page_base_args = [
            'page' => 'erp-omd-reports',
            'tab' => (string) ($report_filters['tab'] ?? 'reports'),
            'report_type' => (string) ($report_filters['report_type'] ?? ''),
            'month' => (string) ($report_filters['month'] ?? ''),
            'client_id' => (int) ($report_filters['client_id'] ?? 0),
            'project_id' => (int) ($report_filters['project_id'] ?? 0),
            'employee_id' => (int) ($report_filters['employee_id'] ?? 0),
            'status' => (string) ($report_filters['status'] ?? ''),
            'detail' => (string) ($report_filters['detail'] ?? 'simple'),
            'mode' => (string) ($report_filters['mode'] ?? 'LIVE'),
            'per_page' => (int) ($report_filters['per_page'] ?? 25),
            'dashboard_queue_limit' => (int) ($dashboard_preview_filters['queue_limit'] ?? 25),
        ];
        $reports_v1_history_toggle_url = add_query_arg(
            array_merge(
                $reports_page_base_args,
                ['steady_state_drift_only' => $reports_v1_history_drift_only ? '0' : '1']
            ),
            admin_url('admin.php')
        );
        $reports_v1_steady_state_banner['history_toggle_label'] = $reports_v1_history_drift_only
            ? __('Pokaż wszystkie próbki', 'erp-omd')
            : __('Pokaż tylko próbki z dryfem', 'erp-omd');
        $reports_v1_steady_state_banner['history_toggle_url'] = $reports_v1_history_toggle_url;
        $clients = $this->clients->all();
        $projects = $this->projects->all();
        $employees = $this->employees->all();
        $project_name_by_id = [];
        foreach ($projects as $project_row) {
            $project_name_by_id[(int) ($project_row['id'] ?? 0)] = (string) ($project_row['name'] ?? '');
        }
        $status_options = ['do_rozpoczecia', 'w_realizacji', 'w_akceptacji', 'do_faktury', 'zakonczony', 'archiwum'];
        if ($report_filters['report_type'] === 'time_entries') {
            $status_options = ['submitted', 'approved', 'rejected'];
        }
        $status_labels = [
            'do_rozpoczecia' => $this->project_status_label('do_rozpoczecia'),
            'w_realizacji' => $this->project_status_label('w_realizacji'),
            'w_akceptacji' => $this->project_status_label('w_akceptacji'),
            'do_faktury' => $this->project_status_label('do_faktury'),
            'zakonczony' => $this->project_status_label('zakonczony'),
            'archiwum' => $this->project_status_label('archiwum'),
            'submitted' => $this->time_status_label('submitted'),
            'approved' => $this->time_status_label('approved'),
            'rejected' => $this->time_status_label('rejected'),
        ];
        $report_titles = [
            'projects' => __('Raport projektów', 'erp-omd'),
            'clients' => __('Raport klientów', 'erp-omd'),
            'invoice' => __('Raport projektów do faktury', 'erp-omd'),
            'time_entries' => __('Raport czasu pracy', 'erp-omd'),
            'monthly' => __('Raport miesięczny', 'erp-omd'),
            'omd_rozliczenia' => __('Raport operacyjny OMD', 'erp-omd'),
        ];
        $report_title = $report_titles[$report_filters['report_type']] ?? __('Raporty', 'erp-omd');
        $adjustment_types = ['STANDARD', 'EMERGENCY_ADJUSTMENT'];
        $adjustment_entity_types = ['time_entry', 'project_cost', 'project', 'other'];
        $adjustment_filters = [
            'month' => sanitize_text_field((string) ($_GET['adjustment_month'] ?? $report_filters['month'])),
            'entity_type' => sanitize_text_field((string) ($_GET['adjustment_entity_type'] ?? '')),
            'adjustment_type' => sanitize_text_field((string) ($_GET['adjustment_type'] ?? '')),
            'changed_by' => max(0, (int) ($_GET['adjustment_changed_by'] ?? 0)),
            'reason' => sanitize_text_field((string) ($_GET['adjustment_reason'] ?? '')),
            'limit' => max(10, min(500, (int) ($_GET['adjustment_limit'] ?? 100))),
        ];
        if (! $this->is_valid_month_string($adjustment_filters['month'])) {
            $adjustment_filters['month'] = $report_filters['month'];
        }
        if (! in_array($adjustment_filters['adjustment_type'], $adjustment_types, true)) {
            $adjustment_filters['adjustment_type'] = '';
        }
        if (! in_array($adjustment_filters['entity_type'], $adjustment_entity_types, true)) {
            $adjustment_filters['entity_type'] = '';
        }
        $can_manage_adjustments_audit = current_user_can('erp_omd_manage_settings');
        $admin_correction_month = (string) ($adjustment_filters['month'] ?? $report_filters['month'] ?? gmdate('Y-m'));
        if (! $this->is_valid_month_string($admin_correction_month)) {
            $admin_correction_month = gmdate('Y-m');
        }
        $admin_correction_cost_rows = [];
        if ($can_manage_adjustments_audit && method_exists($this->project_costs, 'for_month')) {
            $admin_correction_cost_rows = (array) $this->project_costs->for_month($admin_correction_month, 20);
        }
        $adjustment_rows = [];
        if ($can_manage_adjustments_audit && $this->adjustment_audit && method_exists($this->adjustment_audit, 'all')) {
            $adjustment_rows = (array) $this->adjustment_audit->all(array_filter($adjustment_filters));
        }
        $adjustment_author_labels = [];
        foreach ($adjustment_rows as $adjustment_row) {
            $changed_by_id = (int) ($adjustment_row['changed_by'] ?? 0);
            if ($changed_by_id <= 0 || isset($adjustment_author_labels[$changed_by_id])) {
                continue;
            }
            $user = get_userdata($changed_by_id);
            $adjustment_author_labels[$changed_by_id] = $user instanceof WP_User ? (string) $user->user_login : ('#' . $changed_by_id);
        }
        include ERP_OMD_PATH . 'templates/admin/reports.php';
    }

    public function render_project_requests()
    {
        $project_requests = $this->project_requests->all();
        $request_filters = [
            'status' => sanitize_text_field(wp_unslash($_GET['status'] ?? '')),
            'search' => sanitize_text_field(wp_unslash($_GET['search'] ?? '')),
        ];

        if ($request_filters['status'] !== '') {
            $project_requests = array_values(
                array_filter(
                    $project_requests,
                    function ($request_row) use ($request_filters) {
                        return (string) ($request_row['status'] ?? '') === $request_filters['status'];
                    }
                )
            );
        }

        if ($request_filters['search'] !== '') {
            $needle = strtolower($request_filters['search']);
            $project_requests = array_values(
                array_filter(
                    $project_requests,
                    function ($request_row) use ($needle) {
                        $haystack = strtolower(
                            implode(
                                ' ',
                                [
                                    (string) ($request_row['project_name'] ?? ''),
                                    (string) ($request_row['client_name'] ?? ''),
                                    (string) ($request_row['requester_login'] ?? ''),
                                    (string) ($request_row['preferred_manager_login'] ?? ''),
                                    (string) ($request_row['status'] ?? ''),
                                ]
                            )
                        );

                        return strpos($haystack, $needle) !== false;
                    }
                )
            );
        }

        include ERP_OMD_PATH . 'templates/admin/project-requests.php';
    }

    public function render_cost_invoices()
    {
        $this->require_capability('erp_omd_manage_projects');

        $suppliers_repository = new ERP_OMD_Supplier_Repository();
        $cost_invoice_repository = new ERP_OMD_Cost_Invoice_Repository();
        $cost_invoice_audit_repository = new ERP_OMD_Cost_Invoice_Audit_Repository();

        $suppliers = (array) $suppliers_repository->all_active();
        $projects = (array) $this->projects->all();
        $clients = (array) $this->clients->all();
        $client_name_by_id = [];
        foreach ($clients as $client) {
            $client_name_by_id[(int) ($client['id'] ?? 0)] = (string) ($client['name'] ?? '');
        }
        foreach ($projects as &$project_row) {
            $project_row['client_name'] = (string) ($client_name_by_id[(int) ($project_row['client_id'] ?? 0)] ?? '');
        }
        unset($project_row);
        $cost_invoices = (array) $cost_invoice_repository->list();
        $project_supplier_pairs = (array) $cost_invoice_repository->project_supplier_pairs();
        $ksef_service = new ERP_OMD_KSeF_Import_Service(
            new ERP_OMD_Cost_Invoice_Workflow_Service($cost_invoice_repository, $cost_invoice_audit_repository, $suppliers_repository, $this->projects),
            $cost_invoice_repository,
            $cost_invoice_audit_repository,
            null,
            null,
            $suppliers_repository
        );
        $ksef_moderation_filter_status = sanitize_key((string) ($_GET['ksef_status'] ?? ''));
        $ksef_moderation_queue = $ksef_service->list_moderation_queue(['status' => $ksef_moderation_filter_status]);
        $ksef_sales_inbox = $ksef_service->list_sales_inbox();
        $ksef_cost_invoices = array_values(array_filter((array) $cost_invoices, static function ($invoice_row) {
            $source = (string) ($invoice_row['source'] ?? '');
            return strpos($source, 'ksef') !== false;
        }));
        $supplier_categories = $this->normalize_supplier_categories((array) get_option('erp_omd_supplier_categories', []));
        $selected_supplier_id = max(0, (int) ($_GET['supplier_id'] ?? 0));
        $selected_invoice_id = max(0, (int) ($_GET['invoice_id'] ?? 0));
        $selected_supplier = $selected_supplier_id > 0 ? (array) $suppliers_repository->find($selected_supplier_id) : [];
        $selected_invoice = $selected_invoice_id > 0 ? (array) $cost_invoice_repository->find($selected_invoice_id) : [];
        $selected_invoice_audit = $selected_invoice_id > 0 ? (array) $cost_invoice_audit_repository->for_invoice($selected_invoice_id) : [];
        $audit_user_labels = [];
        foreach ($selected_invoice_audit as $audit_row) {
            $changed_by_user_id = (int) ($audit_row['changed_by_user_id'] ?? 0);
            if ($changed_by_user_id <= 0 || isset($audit_user_labels[$changed_by_user_id])) {
                continue;
            }

            $user = get_userdata($changed_by_user_id);
            $audit_user_labels[$changed_by_user_id] = $user instanceof WP_User
                ? (string) ($user->display_name ?: $user->user_login)
                : ('#' . $changed_by_user_id);
        }

        include ERP_OMD_PATH . 'templates/admin/cost-invoices.php';
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

    private function handle_project_request_status_update_action()
    {
        check_admin_referer('erp_omd_update_project_request_status');
        $this->require_capability('erp_omd_manage_projects');

        $request_id = (int) ($_POST['request_id'] ?? 0);
        $target_status = sanitize_text_field(wp_unslash($_POST['status'] ?? ''));
        $request = $request_id > 0 ? $this->project_requests->find($request_id) : null;
        if (! $request) {
            $this->redirect_with_notice('erp-omd-requests', 'error', __('Nie znaleziono wniosku projektowego.', 'erp-omd'));
        }

        $payload = $this->project_request_service->prepare(
            array_merge(
                $request,
                [
                    'status' => $target_status,
                    'reviewed_by_user_id' => get_current_user_id(),
                    'reviewed_at' => current_time('mysql'),
                ]
            ),
            $request
        );

        $errors = $this->project_request_service->validate($payload, $request);
        if ($errors) {
            $this->redirect_with_notice('erp-omd-requests', 'error', implode(' ', $errors));
        }

        $this->project_requests->update($request_id, $payload);
        $this->redirect_with_notice('erp-omd-requests', 'success', __('Status wniosku został zaktualizowany.', 'erp-omd'));
    }

    private function handle_project_request_conversion_action()
    {
        check_admin_referer('erp_omd_convert_project_request');
        $this->require_capability('erp_omd_manage_projects');

        $request_id = (int) ($_POST['request_id'] ?? 0);
        $request = $request_id > 0 ? $this->project_requests->find($request_id) : null;
        if (! $request) {
            $this->redirect_with_notice('erp-omd-requests', 'error', __('Nie znaleziono wniosku projektowego.', 'erp-omd'));
        }

        $errors = $this->project_request_service->validate_conversion($request);
        if ($errors) {
            $this->redirect_with_notice('erp-omd-requests', 'error', implode(' ', $errors));
        }

        $project_payload = $this->project_request_service->build_project_payload($request);
        $project_id = $this->projects->create($project_payload);
        $this->project_financial_service->rebuild_for_project($project_id);
        $this->project_requests->mark_converted($request_id, $project_id, get_current_user_id());

        $this->redirect_with_notice('erp-omd-requests', 'success', __('Wniosek został skonwertowany do projektu.', 'erp-omd'), ['id' => $request_id]);
    }

    private function handle_project_request_delete_action()
    {
        check_admin_referer('erp_omd_delete_project_request');
        $this->require_capability('erp_omd_manage_projects');

        $request_id = (int) ($_POST['request_id'] ?? 0);
        $request = $request_id > 0 ? $this->project_requests->find($request_id) : null;
        if (! $request) {
            $this->redirect_with_notice('erp-omd-requests', 'error', __('Nie znaleziono wniosku projektowego.', 'erp-omd'));
        }

        $this->project_requests->delete($request_id);
        $this->redirect_with_notice('erp-omd-requests', 'success', __('Wniosek projektowy został usunięty.', 'erp-omd'));
    }

    private function handle_inline_employee_update_action()
    {
        check_admin_referer('erp_omd_inline_employee_update');
        $this->require_capability('erp_omd_manage_employees');

        $id = (int) ($_POST['id'] ?? 0);
        $employee = $id ? $this->employees->find($id) : null;
        if (! $employee) {
            $this->redirect_with_notice('erp-omd-employees', 'error', __('Nie znaleziono pracownika do aktualizacji inline.', 'erp-omd'));
        }

        $payload = [
            'user_id' => (int) ($employee['user_id'] ?? 0),
            'default_role_id' => (int) ($employee['default_role_id'] ?? 0),
            'account_type' => sanitize_text_field(wp_unslash($_POST['account_type'] ?? ($employee['account_type'] ?? 'worker'))),
            'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? ($employee['status'] ?? 'active'))),
            'role_ids' => array_map('intval', (array) ($employee['role_ids'] ?? [])),
        ];

        $errors = $this->employee_service->validate_employee($payload, $id);
        if ($errors) {
            $this->redirect_with_notice('erp-omd-employees', 'error', implode(' ', $errors));
        }

        $this->employees->update($id, $payload);
        $this->sync_wp_role($payload['user_id'], $payload['account_type']);
        $this->redirect_with_notice('erp-omd-employees', 'success', __('Dane pracownika zostały zaktualizowane inline.', 'erp-omd'));
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

    private function handle_employee_password_change()
    {
        check_admin_referer('erp_omd_change_employee_password');
        $this->require_capability('erp_omd_manage_employees');

        $employee_id = (int) ($_POST['employee_id'] ?? 0);
        $employee = $employee_id ? $this->employees->find($employee_id) : null;
        if (! $employee) {
            $this->redirect_with_notice('erp-omd-employees', 'error', __('Nie znaleziono pracownika do zmiany hasła.', 'erp-omd'));
        }

        $password = (string) wp_unslash($_POST['new_password'] ?? '');
        $password_confirm = (string) wp_unslash($_POST['new_password_confirm'] ?? '');
        if ($password === '' || strlen($password) < 8) {
            $this->redirect_with_notice('erp-omd-employees', 'error', __('Nowe hasło musi mieć co najmniej 8 znaków.', 'erp-omd'), ['id' => $employee_id]);
        }
        if ($password !== $password_confirm) {
            $this->redirect_with_notice('erp-omd-employees', 'error', __('Hasło i potwierdzenie hasła muszą być identyczne.', 'erp-omd'), ['id' => $employee_id]);
        }

        $result = wp_update_user([
            'ID' => (int) ($employee['user_id'] ?? 0),
            'user_pass' => $password,
        ]);
        if (is_wp_error($result)) {
            $this->redirect_with_notice('erp-omd-employees', 'error', $result->get_error_message(), ['id' => $employee_id]);
        }

        $this->redirect_with_notice('erp-omd-employees', 'success', __('Hasło pracownika zostało zmienione.', 'erp-omd'), ['id' => $employee_id]);
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

    private function handle_supplier_save()
    {
        check_admin_referer('erp_omd_save_supplier');
        $this->require_capability('erp_omd_manage_projects');

        $supplier_id = max(0, (int) ($_POST['supplier_id'] ?? 0));
        $supplier_categories_raw = sanitize_text_field((string) ($_POST['supplier_categories_dictionary'] ?? ''));
        $supplier_categories = $this->normalize_supplier_categories(
            array_map(
                'trim',
                explode(',', $supplier_categories_raw)
            )
        );

        update_option('erp_omd_supplier_categories', $supplier_categories, false);

        $supplier_category = sanitize_text_field((string) ($_POST['supplier_category'] ?? ''));
        if ($supplier_category !== '' && ! in_array($supplier_category, $supplier_categories, true)) {
            $this->redirect_cost_invoice_page(['error' => 'supplier_category_invalid']);
        }

        $payload = [
            'name' => sanitize_text_field((string) ($_POST['supplier_name'] ?? '')),
            'company' => sanitize_text_field((string) ($_POST['supplier_company'] ?? '')),
            'nip' => sanitize_text_field((string) ($_POST['supplier_nip'] ?? '')),
            'email' => sanitize_email((string) ($_POST['supplier_email'] ?? '')),
            'phone' => sanitize_text_field((string) ($_POST['supplier_phone'] ?? '')),
            'contact_person_name' => sanitize_text_field((string) ($_POST['supplier_contact_person_name'] ?? '')),
            'contact_person_email' => sanitize_email((string) ($_POST['supplier_contact_person_email'] ?? '')),
            'contact_person_phone' => sanitize_text_field((string) ($_POST['supplier_contact_person_phone'] ?? '')),
            'category' => $supplier_category,
            'supplier_description' => sanitize_textarea_field((string) ($_POST['supplier_description'] ?? '')),
            'city' => sanitize_text_field((string) ($_POST['supplier_city'] ?? '')),
            'street' => sanitize_text_field((string) ($_POST['supplier_street'] ?? '')),
            'apartment_number' => sanitize_text_field((string) ($_POST['supplier_apartment_number'] ?? '')),
            'postal_code' => sanitize_text_field((string) ($_POST['supplier_postal_code'] ?? '')),
            'country' => sanitize_text_field((string) ($_POST['supplier_country'] ?? 'PL')),
            'status' => 'active',
        ];

        if ($payload['name'] === '') {
            $this->redirect_cost_invoice_page(['error' => 'supplier_name_required']);
        }

        $contact_errors = $this->validate_supplier_contact_fields($payload);
        if ($contact_errors !== []) {
            $this->redirect_cost_invoice_page(['error' => rawurlencode(implode(' ', $contact_errors))]);
        }

        $repository = new ERP_OMD_Supplier_Repository();
        if ($supplier_id > 0) {
            $repository->update($supplier_id, $payload);
        } else {
            $supplier_id = (int) $repository->create($payload);
        }

        $this->redirect_cost_invoice_page(['message' => 'supplier_saved', 'supplier_id' => $supplier_id]);
    }

    private function handle_supplier_delete()
    {
        check_admin_referer('erp_omd_delete_supplier');
        $this->require_capability('erp_omd_manage_projects');

        $supplier_id = max(0, (int) ($_POST['supplier_id'] ?? 0));
        if ($supplier_id <= 0) {
            $this->redirect_cost_invoice_page(['error' => 'supplier_not_found']);
        }

        $supplier_repository = new ERP_OMD_Supplier_Repository();
        $supplier = $supplier_repository->find($supplier_id);
        if (! is_array($supplier) || $supplier === []) {
            $this->redirect_cost_invoice_page(['error' => 'supplier_not_found']);
        }

        $invoice_repository = new ERP_OMD_Cost_Invoice_Repository();
        $supplier_invoices = (array) $invoice_repository->list(['supplier_id' => $supplier_id]);
        foreach ($supplier_invoices as $supplier_invoice) {
            $this->delete_cost_invoice_with_side_effects((array) $supplier_invoice);
        }

        $supplier_repository->delete($supplier_id);
        $this->redirect_cost_invoice_page(['message' => 'supplier_deleted']);
    }

    private function handle_cost_invoice_save()
    {
        check_admin_referer('erp_omd_save_cost_invoice');
        $this->require_capability('erp_omd_manage_projects');

        $invoice_id = max(0, (int) ($_POST['cost_invoice_id'] ?? 0));
        $net_amount = (float) ($_POST['cost_invoice_net_amount'] ?? 0);
        $vat_rate_raw = sanitize_text_field((string) ($_POST['cost_invoice_vat_rate'] ?? '23'));
        $vat_rate_map = [
            '23' => 23.0,
            '8' => 8.0,
            '5' => 5.0,
            '0' => 0.0,
            'zw' => 0.0,
        ];
        $vat_rate = (float) ($vat_rate_map[$vat_rate_raw] ?? 23.0);
        $vat_amount = round($net_amount * ($vat_rate / 100), 2);
        $gross_amount = round($net_amount + $vat_amount, 2);
        $payload = [
            'supplier_id' => max(0, (int) ($_POST['cost_invoice_supplier_id'] ?? 0)),
            'project_id' => max(0, (int) ($_POST['cost_invoice_project_id'] ?? 0)),
            'invoice_number' => sanitize_text_field((string) ($_POST['cost_invoice_number'] ?? '')),
            'issue_date' => sanitize_text_field((string) ($_POST['cost_invoice_issue_date'] ?? '')),
            'status' => sanitize_text_field((string) ($_POST['cost_invoice_status'] ?? 'zaimportowana')),
            'description' => sanitize_textarea_field((string) ($_POST['cost_invoice_description'] ?? '')),
            'net_amount' => $net_amount,
            'vat_amount' => $vat_amount,
            'gross_amount' => $gross_amount,
            'source' => sanitize_text_field((string) ($_POST['cost_invoice_source'] ?? 'manual')),
            'ksef_reference_number' => sanitize_text_field((string) ($_POST['cost_invoice_ksef_reference_number'] ?? '')),
            'updated_by_user_id' => get_current_user_id(),
            'created_by_user_id' => get_current_user_id(),
        ];

        $workflow = new ERP_OMD_Cost_Invoice_Workflow_Service(
            new ERP_OMD_Cost_Invoice_Repository(),
            new ERP_OMD_Cost_Invoice_Audit_Repository(),
            new ERP_OMD_Supplier_Repository(),
            $this->projects
        );

        $result = $invoice_id > 0
            ? $workflow->update_invoice($invoice_id, $payload, get_current_user_id())
            : $workflow->create_invoice($payload);

        if (! (bool) ($result['ok'] ?? false)) {
            $this->redirect_cost_invoice_page(['error' => rawurlencode(implode(' ', (array) ($result['errors'] ?? [])))]);
        }

        if ((string) ($payload['status'] ?? '') === 'przypisana') {
            $attach_errors = $this->sync_attached_cost_invoice_to_project_cost((int) ($result['invoice_id'] ?? $invoice_id));
            if ($attach_errors !== []) {
                $this->redirect_cost_invoice_page(['error' => rawurlencode(implode(' ', $attach_errors))]);
            }
        }

        $this->redirect_cost_invoice_page(['message' => 'cost_invoice_saved', 'invoice_id' => (int) ($result['invoice_id'] ?? $invoice_id)]);
    }

    private function handle_cost_invoice_delete()
    {
        check_admin_referer('erp_omd_delete_cost_invoice');
        $this->require_capability('erp_omd_manage_projects');

        $invoice_id = max(0, (int) ($_POST['cost_invoice_id'] ?? 0));
        if ($invoice_id <= 0) {
            $this->redirect_cost_invoice_page(['error' => 'cost_invoice_not_found']);
        }

        $invoice_repository = new ERP_OMD_Cost_Invoice_Repository();
        $invoice = $invoice_repository->find($invoice_id);
        if (! is_array($invoice) || $invoice === []) {
            $this->redirect_cost_invoice_page(['error' => 'cost_invoice_not_found']);
        }

        $this->delete_cost_invoice_with_side_effects($invoice);

        $this->redirect_cost_invoice_page(['message' => 'cost_invoice_deleted']);
    }

    /**
     * @param array<string,mixed> $invoice
     * @return void
     */

    private function handle_ksef_queue_moderation_action()
    {
        check_admin_referer('erp_omd_moderate_ksef_queue');
        $this->require_capability('erp_omd_manage_projects');

        $retry_key = sanitize_text_field((string) ($_POST['retry_key'] ?? ''));
        $action = sanitize_key((string) ($_POST['ksef_action'] ?? ''));
        if ($retry_key === '' || $action === '') {
            $this->redirect_cost_invoice_page(['tab' => 'ksef-moderation', 'error' => rawurlencode(__('Brakuje danych moderacji KSeF.', 'erp-omd'))]);
        }

        $service = new ERP_OMD_KSeF_Import_Service(
            new ERP_OMD_Cost_Invoice_Workflow_Service(new ERP_OMD_Cost_Invoice_Repository(), new ERP_OMD_Cost_Invoice_Audit_Repository(), new ERP_OMD_Supplier_Repository(), $this->projects),
            new ERP_OMD_Cost_Invoice_Repository(),
            new ERP_OMD_Cost_Invoice_Audit_Repository(),
            null,
            null,
            new ERP_OMD_Supplier_Repository()
        );

        $result = $service->moderate_queue_entry($retry_key, $action, [
            'supplier_id' => (int) ($_POST['supplier_id'] ?? 0),
            'project_id' => (int) ($_POST['project_id'] ?? 0),
        ], (int) get_current_user_id());

        if (! (bool) ($result['ok'] ?? false)) {
            $this->redirect_cost_invoice_page(['tab' => 'ksef-moderation', 'error' => rawurlencode(implode(' ', (array) ($result['errors'] ?? [])))]);
        }

        $this->redirect_cost_invoice_page(['tab' => 'ksef-moderation', 'message' => 'ksef_moderation_saved']);
    }

    private function handle_ksef_queue_bulk_action()
    {
        check_admin_referer('erp_omd_bulk_ksef_queue');
        $this->require_capability('erp_omd_manage_projects');

        $retry_keys = array_values(array_filter(array_map('sanitize_text_field', (array) ($_POST['retry_keys'] ?? []))));
        $action = sanitize_key((string) ($_POST['ksef_bulk_action'] ?? ''));
        if ($retry_keys === [] || $action === '') {
            $this->redirect_cost_invoice_page(['tab' => 'ksef-moderation', 'error' => rawurlencode(__('Wybierz rekordy i akcję bulk KSeF.', 'erp-omd'))]);
        }

        $service = new ERP_OMD_KSeF_Import_Service(
            new ERP_OMD_Cost_Invoice_Workflow_Service(new ERP_OMD_Cost_Invoice_Repository(), new ERP_OMD_Cost_Invoice_Audit_Repository(), new ERP_OMD_Supplier_Repository(), $this->projects),
            new ERP_OMD_Cost_Invoice_Repository(),
            new ERP_OMD_Cost_Invoice_Audit_Repository(),
            null,
            null,
            new ERP_OMD_Supplier_Repository()
        );

        $result = $service->bulk_moderate_queue_entries($retry_keys, $action, [
            'supplier_id' => (int) ($_POST['supplier_id'] ?? 0),
            'project_id' => (int) ($_POST['project_id'] ?? 0),
        ], (int) get_current_user_id());

        if (! (bool) ($result['ok'] ?? false) && ! empty($result['errors'])) {
            $this->redirect_cost_invoice_page(['tab' => 'ksef-moderation', 'error' => rawurlencode(__('Część rekordów KSeF nie została zmoderowana.', 'erp-omd'))]);
        }

        $this->redirect_cost_invoice_page(['tab' => 'ksef-moderation', 'message' => 'ksef_bulk_moderation_saved']);
    }


    private function handle_import_ksef_sales_xml_action()
    {
        check_admin_referer('erp_omd_import_ksef_sales_xml');
        $this->require_capability('erp_omd_manage_projects');

        $xml_content = $this->read_ksef_xml_from_request('ksef_sales_xml_content', 'ksef_sales_xml_file');
        if (trim($xml_content) === '') {
            $this->redirect_cost_invoice_page(['tab' => 'ksef-sales', 'error' => rawurlencode(__('Wklej treść XML z KSeF lub wybierz plik XML.', 'erp-omd'))]);
        }

        $service = new ERP_OMD_KSeF_Import_Service(
            new ERP_OMD_Cost_Invoice_Workflow_Service(new ERP_OMD_Cost_Invoice_Repository(), new ERP_OMD_Cost_Invoice_Audit_Repository(), new ERP_OMD_Supplier_Repository(), $this->projects),
            new ERP_OMD_Cost_Invoice_Repository(),
            new ERP_OMD_Cost_Invoice_Audit_Repository(),
            null,
            null,
            new ERP_OMD_Supplier_Repository(),
            $this->clients
        );

        $result = $service->import_sales_xml($xml_content, (int) get_current_user_id());
        if ((int) ($result['imported'] ?? 0) < 1) {
            $errors = (array) (($result['errors'][0]['errors'] ?? []) ?: []);
            $this->redirect_cost_invoice_page(['tab' => 'ksef-sales', 'error' => rawurlencode(implode(' ', $errors))]);
        }

        $this->redirect_cost_invoice_page(['tab' => 'ksef-sales', 'message' => 'ksef_sales_xml_imported']);
    }

    /**
     * @return string
     */
    private function read_ksef_xml_from_request($content_field_name, $file_field_name)
    {
        $content_field_name = sanitize_key((string) $content_field_name);
        $file_field_name = sanitize_key((string) $file_field_name);
        if ($content_field_name === '' || $file_field_name === '') {
            return '';
        }

        $inline_xml = (string) wp_unslash($_POST[$content_field_name] ?? '');
        if (trim($inline_xml) !== '') {
            return $inline_xml;
        }

        if (! isset($_FILES[$file_field_name]) || ! is_array($_FILES[$file_field_name])) {
            return '';
        }

        $upload = (array) $_FILES[$file_field_name];
        $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            return '';
        }

        $tmp_name = (string) ($upload['tmp_name'] ?? '');
        if ($tmp_name === '' || ! is_uploaded_file($tmp_name)) {
            return '';
        }

        $content = file_get_contents($tmp_name);
        return is_string($content) ? $content : '';
    }

    private function handle_import_ksef_cost_xml_action()
    {
        check_admin_referer('erp_omd_import_ksef_cost_xml');
        $this->require_capability('erp_omd_manage_projects');

        $xml_documents = $this->read_ksef_xml_batch_from_request('ksef_cost_xml_content', 'ksef_cost_xml_files');
        if ($xml_documents === []) {
            $this->redirect_cost_invoice_page(['tab' => 'ksef-cost', 'error' => rawurlencode(__('Wklej treść XML z KSeF lub wybierz co najmniej jeden plik XML.', 'erp-omd'))]);
        }

        $service = new ERP_OMD_KSeF_Import_Service(
            new ERP_OMD_Cost_Invoice_Workflow_Service(new ERP_OMD_Cost_Invoice_Repository(), new ERP_OMD_Cost_Invoice_Audit_Repository(), new ERP_OMD_Supplier_Repository(), $this->projects),
            new ERP_OMD_Cost_Invoice_Repository(),
            new ERP_OMD_Cost_Invoice_Audit_Repository(),
            null,
            null,
            new ERP_OMD_Supplier_Repository(),
            $this->clients
        );

        $total_imported = 0;
        $all_errors = [];
        foreach ($xml_documents as $xml_content) {
            $result = $service->import_cost_xml($xml_content, (int) get_current_user_id());
            $total_imported += (int) ($result['imported'] ?? 0);
            if ((int) ($result['imported'] ?? 0) < 1) {
                $errors = (array) (($result['errors'][0]['errors'] ?? []) ?: []);
                if ($errors !== []) {
                    $all_errors[] = implode(' ', $errors);
                }
            }
        }

        if ($total_imported < 1) {
            $this->redirect_cost_invoice_page(['tab' => 'ksef-cost', 'error' => rawurlencode(implode(' | ', $all_errors))]);
        }

        if ($all_errors !== []) {
            $this->redirect_cost_invoice_page([
                'tab' => 'ksef-cost',
                'error' => rawurlencode(sprintf(__('Zaimportowano %1$d dokument(y), część odrzucona: %2$s', 'erp-omd'), $total_imported, implode(' | ', $all_errors))),
            ]);
        }

        $this->redirect_cost_invoice_page(['tab' => 'ksef-cost', 'message' => 'ksef_cost_xml_imported']);
    }

    /**
     * @return array<int,string>
     */
    private function read_ksef_xml_batch_from_request($content_field_name, $file_field_name)
    {
        $documents = [];
        $inline_xml = $this->read_ksef_xml_from_request($content_field_name, $file_field_name);
        if (trim($inline_xml) !== '') {
            $documents[] = $inline_xml;
            return $documents;
        }

        $file_field_name = sanitize_key((string) $file_field_name);
        if ($file_field_name === '' || ! isset($_FILES[$file_field_name]) || ! is_array($_FILES[$file_field_name])) {
            return [];
        }
        $upload = (array) $_FILES[$file_field_name];
        $tmp_names = $upload['tmp_name'] ?? [];
        $errors = $upload['error'] ?? [];
        if (! is_array($tmp_names) || ! is_array($errors)) {
            return [];
        }

        foreach ($tmp_names as $index => $tmp_name) {
            $error = (int) ($errors[$index] ?? UPLOAD_ERR_NO_FILE);
            if ($error !== UPLOAD_ERR_OK) {
                continue;
            }
            $tmp_name = (string) $tmp_name;
            if ($tmp_name === '' || ! is_uploaded_file($tmp_name)) {
                continue;
            }
            $content = file_get_contents($tmp_name);
            if (is_string($content) && trim($content) !== '') {
                $documents[] = $content;
            }
        }

        return $documents;
    }

    private function handle_attach_ksef_sales_invoice_action()
    {
        check_admin_referer('erp_omd_attach_ksef_sales_invoice');
        $this->require_capability('erp_omd_manage_projects');

        $sales_id = (int) ($_POST['sales_id'] ?? 0);
        $project_id = (int) ($_POST['project_id'] ?? 0);
        $is_final = ! empty($_POST['is_final']);
        if ($sales_id <= 0 || $project_id <= 0) {
            $this->redirect_cost_invoice_page(['tab' => 'ksef-sales', 'error' => rawurlencode(__('Wskaż dokument sprzedażowy i projekt.', 'erp-omd'))]);
        }

        $service = new ERP_OMD_KSeF_Import_Service(
            new ERP_OMD_Cost_Invoice_Workflow_Service(new ERP_OMD_Cost_Invoice_Repository(), new ERP_OMD_Cost_Invoice_Audit_Repository(), new ERP_OMD_Supplier_Repository(), $this->projects),
            new ERP_OMD_Cost_Invoice_Repository(),
            new ERP_OMD_Cost_Invoice_Audit_Repository(),
            null,
            null,
            new ERP_OMD_Supplier_Repository(),
            $this->clients
        );

        $result = $service->attach_sales_document_to_project($sales_id, $project_id, $is_final, (int) get_current_user_id());
        if (! (bool) ($result['ok'] ?? false)) {
            $this->redirect_cost_invoice_page(['tab' => 'ksef-sales', 'error' => rawurlencode(implode(' ', (array) ($result['errors'] ?? [])))]);
        }

        $this->redirect_cost_invoice_page(['tab' => 'ksef-sales', 'message' => 'ksef_sales_attached']);
    }

    private function delete_cost_invoice_with_side_effects(array $invoice)
    {
        $invoice_id = (int) ($invoice['id'] ?? 0);
        if ($invoice_id <= 0) {
            return;
        }

        (new ERP_OMD_Cost_Invoice_Audit_Repository())->insert_many([
            [
                'invoice_id' => $invoice_id,
                'field_name' => '__deleted__',
                'before_value' => wp_json_encode($invoice),
                'after_value' => '',
                'changed_by_user_id' => get_current_user_id(),
                'changed_at' => current_time('mysql'),
            ],
        ]);

        $description = sprintf(
            '%s #%d (%s)',
            __('Faktura kosztowa', 'erp-omd'),
            $invoice_id,
            (string) ($invoice['invoice_number'] ?? '')
        );
        $project_id = (int) ($invoice['project_id'] ?? 0);
        if ($project_id > 0) {
            $project_cost_rows = (array) $this->project_costs->for_project($project_id);
            foreach ($project_cost_rows as $project_cost_row) {
                if ((string) ($project_cost_row['description'] ?? '') === $description) {
                    $this->project_costs->delete((int) ($project_cost_row['id'] ?? 0));
                }
            }
        }

        (new ERP_OMD_Cost_Invoice_Repository())->delete($invoice_id);
        if ($project_id > 0) {
            $this->project_financial_service->rebuild_for_project($project_id);
        }
    }

    /**
     * @param array<string,mixed> $args
     * @return void
     */
    private function redirect_cost_invoice_page(array $args = [])
    {
        wp_safe_redirect(add_query_arg(array_merge(['page' => 'erp-omd-cost-invoices'], $args), admin_url('admin.php')));
        exit;
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
        $initial_items_payload = $this->collect_initial_estimate_items();
        if (! $existing) {
            if ($initial_items_payload === []) {
                $errors[] = __('Kosztorys musi zawierać minimum jedną pozycję.', 'erp-omd');
            }
            foreach ($initial_items_payload as $initial_item_payload) {
                $errors = array_merge($errors, $this->estimate_service->validate_item($initial_item_payload, ['id' => 0, 'status' => $payload['status']]));
            }
        }
        if ($errors) {
            $this->redirect_with_notice('erp-omd-estimates', 'error', implode(' ', $errors), $id ? ['id' => $id, 'edit' => 1] : []);
        }
        if ($id) {
            $should_accept_via_status = ($existing['status'] ?? '') !== 'zaakceptowany' && $payload['status'] === 'zaakceptowany';
            if ($should_accept_via_status) {
                $update_payload = $payload;
                $update_payload['status'] = (string) ($existing['status'] ?? 'wstepny');
                $this->estimates->update($id, $update_payload);
                $result = $this->estimate_service->accept($id);
                if ($result instanceof WP_Error) {
                    $this->redirect_with_notice('erp-omd-estimates', 'error', $result->get_error_message(), ['id' => $id, 'edit' => 1]);
                }
            } else {
                $this->estimates->update($id, $payload);
            }
            $message = __('Kosztorys został zaktualizowany.', 'erp-omd');
        } else {
            $id = $this->estimates->create($payload);
            foreach ($initial_items_payload as $initial_item_payload) {
                $initial_item_payload['estimate_id'] = $id;
                $this->estimate_items->create($initial_item_payload);
            }
            if ($payload['status'] === 'zaakceptowany') {
                $result = $this->estimate_service->accept($id);
                if ($result instanceof WP_Error) {
                    $this->redirect_with_notice('erp-omd-estimates', 'error', $result->get_error_message(), ['id' => $id, 'edit' => 1]);
                }
            }
            $message = __('Kosztorys został utworzony.', 'erp-omd');
        }
        $this->redirect_with_notice('erp-omd-estimates', 'success', $message, ['id' => $id]);
    }

    private function collect_initial_estimate_items()
    {
        $names = wp_unslash($_POST['initial_item_name'] ?? []);
        $qtys = wp_unslash($_POST['initial_item_qty'] ?? []);
        $prices = wp_unslash($_POST['initial_item_price'] ?? []);
        $costs = wp_unslash($_POST['initial_item_cost_internal'] ?? []);
        $comments = wp_unslash($_POST['initial_item_comment'] ?? []);

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

        $count = max(count($names), count($qtys), count($prices), count($costs), count($comments));
        $items = [];
        for ($index = 0; $index < $count; $index++) {
            $name = sanitize_text_field((string) ($names[$index] ?? ''));
            $qty = (float) ($qtys[$index] ?? 0);
            $price = (float) ($prices[$index] ?? 0);
            $cost = (float) ($costs[$index] ?? 0);
            $comment = sanitize_textarea_field((string) ($comments[$index] ?? ''));

            if ($name === '' && $qty <= 0 && $price <= 0 && $cost <= 0 && $comment === '') {
                continue;
            }

            $items[] = [
                'estimate_id' => 0,
                'name' => $name,
                'qty' => $qty,
                'price' => $price,
                'cost_internal' => $cost,
                'comment' => $comment,
            ];
        }

        return $items;
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

    private function handle_send_estimate_client_decision_link()
    {
        check_admin_referer('erp_omd_send_estimate_client_link');
        $this->require_capability('erp_omd_manage_projects');
        $estimate_id = (int) ($_POST['estimate_id'] ?? 0);
        $estimate = $estimate_id > 0 ? $this->estimates->find($estimate_id) : null;
        if (! $estimate) {
            $this->redirect_with_notice('erp-omd-estimates', 'error', __('Nie znaleziono kosztorysu do wysyłki.', 'erp-omd'));
        }
        if ((string) ($estimate['status'] ?? '') !== 'do_akceptacji') {
            $this->redirect_with_notice('erp-omd-estimates', 'error', __('Link można wysłać tylko dla kosztorysu o statusie do_akceptacji.', 'erp-omd'), ['id' => $estimate_id]);
        }

        $client = $this->clients->find((int) ($estimate['client_id'] ?? 0));
        $client_email = sanitize_email((string) ($client['email'] ?? ''));
        if (! is_email($client_email)) {
            $this->redirect_with_notice('erp-omd-estimates', 'error', __('Klient nie ma poprawnego adresu e-mail.', 'erp-omd'), ['id' => $estimate_id]);
        }

        $token = wp_generate_password(48, false, false);
        $state = $this->estimate_client_link_state();
        $state[$estimate_id] = [
            'token' => $token,
            'expires_at' => time() + (5 * DAY_IN_SECONDS),
            'created_at' => current_time('mysql'),
            'created_by' => (int) get_current_user_id(),
        ];
        update_option('erp_omd_estimate_client_link_tokens', $state, false);

        $decision_url = add_query_arg(['token' => rawurlencode($token)], home_url('/erp-front/estimate-decision/'));
        $subject = sprintf(__('[ERP OMD] Decyzja klienta dla kosztorysu %s', 'erp-omd'), (string) ($estimate['name'] ?? ('#' . $estimate_id)));
        $body = sprintf(
            __('Dzień dobry,<br><br>prosimy o decyzję dla kosztorysu <strong>%1$s</strong>.<br>Link jest ważny 5 dni:<br><a href="%2$s">%2$s</a>', 'erp-omd'),
            esc_html((string) ($estimate['name'] ?? ('#' . $estimate_id))),
            esc_url($decision_url)
        );
        $sent = wp_mail($client_email, $subject, wpautop($body), ['Content-Type: text/html; charset=UTF-8']);
        if (! $sent) {
            $this->redirect_with_notice('erp-omd-estimates', 'error', __('Nie udało się wysłać e-maila do klienta.', 'erp-omd'), ['id' => $estimate_id]);
        }

        $this->redirect_with_notice('erp-omd-estimates', 'success', __('Link akceptacji/odrzucenia został wysłany do klienta.', 'erp-omd'), ['id' => $estimate_id]);
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
            $header[] = __('Koszt wewnętrzny pozycji', 'erp-omd');
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
        $project_status = sanitize_text_field(wp_unslash($_POST['status'] ?? 'do_rozpoczecia'));
        if ($project_status === 'inactive') {
            $project_status = 'archiwum';
        }
        $deadline_mark_completed = ! empty($_POST['deadline_mark_completed']);
        $payload = $this->client_project_service->prepare_project(['client_id' => (int) ($_POST['client_id'] ?? 0), 'name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')), 'billing_type' => sanitize_text_field(wp_unslash($_POST['billing_type'] ?? 'time_material')), 'budget' => (float) ($_POST['budget'] ?? 0), 'retainer_monthly_fee' => (float) ($_POST['retainer_monthly_fee'] ?? 0), 'status' => $project_status, 'start_date' => sanitize_text_field(wp_unslash($_POST['start_date'] ?? '')), 'end_date' => sanitize_text_field(wp_unslash($_POST['end_date'] ?? '')), 'deadline_date' => sanitize_text_field(wp_unslash($_POST['deadline_date'] ?? '')), 'deadline_completed_at' => $deadline_mark_completed ? current_time('mysql') : (string) ($existing['deadline_completed_at'] ?? ''), 'deadline_completed_by' => $deadline_mark_completed ? (int) get_current_user_id() : (int) ($existing['deadline_completed_by'] ?? 0), 'manager_id' => (int) ($_POST['manager_id'] ?? 0), 'manager_ids' => array_map('intval', wp_unslash($_POST['manager_ids'] ?? [])), 'estimate_id' => (int) ($_POST['estimate_id'] ?? 0), 'brief' => sanitize_textarea_field(wp_unslash($_POST['brief'] ?? '')), 'alert_margin_threshold' => sanitize_text_field(wp_unslash($_POST['alert_margin_threshold'] ?? ''))], $existing);
        $errors = $this->client_project_service->validate_project($payload, $existing);
        if ($errors) { $this->redirect_with_notice('erp-omd-projects', 'error', implode(' ', $errors), $id ? ['id' => $id] : []); }
        $was_update = $id > 0;
        if ($id) { $this->projects->update($id, $payload); $message = __('Projekt został zaktualizowany.', 'erp-omd'); } else { $id = $this->projects->create($payload); $message = __('Projekt został utworzony.', 'erp-omd'); }
        $this->project_financial_service->rebuild_for_project($id);
        $this->redirect_with_notice('erp-omd-projects', 'success', $message, $was_update ? ['id' => $id] : []);
    }

    private function handle_inline_project_update_action()
    {
        check_admin_referer('erp_omd_inline_project_update');
        $this->require_capability('erp_omd_manage_projects');

        $id = (int) ($_POST['id'] ?? 0);
        $existing = $id ? $this->projects->find($id) : null;
        if (! $existing) {
            $this->redirect_with_notice('erp-omd-projects', 'error', __('Nie znaleziono projektu do aktualizacji inline.', 'erp-omd'));
        }

        $manager_ids = array_map('intval', wp_unslash($_POST['manager_ids'] ?? []));
        if ($manager_ids === []) {
            $manager_ids = array_map('intval', (array) ($existing['manager_ids'] ?? []));
        }
        $manager_id = $manager_ids !== [] ? (int) $manager_ids[0] : (int) ($existing['manager_id'] ?? 0);

        $inline_status = sanitize_text_field(wp_unslash($_POST['status'] ?? ($existing['status'] ?? 'do_rozpoczecia')));
        if ($inline_status === 'inactive') {
            $inline_status = 'archiwum';
        }
        $payload = $this->client_project_service->prepare_project(
            [
                'name' => sanitize_text_field(wp_unslash($_POST['name'] ?? ($existing['name'] ?? ''))),
                'status' => $inline_status,
                'manager_id' => $manager_id,
                'manager_ids' => $manager_ids,
            ],
            $existing
        );
        $errors = $this->client_project_service->validate_project($payload, $existing);
        if ($errors) {
            $this->redirect_with_notice('erp-omd-projects', 'error', implode(' ', $errors));
        }

        $this->projects->update($id, $payload);
        $this->project_financial_service->rebuild_for_project($id);
        $this->redirect_with_notice('erp-omd-projects', 'success', __('Projekt został zaktualizowany inline.', 'erp-omd'));
    }

    public function handle_inline_project_update_ajax()
    {
        check_ajax_referer('erp_omd_inline_project_update');
        if (! current_user_can('erp_omd_manage_projects')) {
            wp_send_json_error(['message' => __('Brak uprawnień do zapisu projektu.', 'erp-omd')], 403);
        }

        $id = (int) ($_POST['id'] ?? 0);
        $existing = $id ? $this->projects->find($id) : null;
        if (! $existing) {
            wp_send_json_error(['message' => __('Nie znaleziono projektu do aktualizacji inline.', 'erp-omd')], 404);
        }

        $inline_name = sanitize_text_field(wp_unslash($_POST['name'] ?? ($existing['name'] ?? '')));
        $inline_status = sanitize_text_field(wp_unslash($_POST['status'] ?? ($existing['status'] ?? 'do_rozpoczecia')));
        if ($inline_status === 'inactive') {
            $inline_status = 'archiwum';
        }
        $manager_ids = array_map('intval', (array) ($existing['manager_ids'] ?? []));
        if (empty($manager_ids) && (int) ($existing['manager_id'] ?? 0) > 0) {
            $manager_ids = [(int) $existing['manager_id']];
        }

        $payload = $this->client_project_service->prepare_project(
            [
                'name' => $inline_name,
                'status' => $inline_status,
                'manager_id' => (int) ($existing['manager_id'] ?? 0),
                'manager_ids' => $manager_ids,
            ],
            $existing
        );
        $errors = $this->client_project_service->validate_project($payload, $existing);
        if ($errors) {
            wp_send_json_error(['message' => implode(' ', $errors)], 422);
        }

        $this->projects->update($id, $payload);
        $this->project_financial_service->rebuild_for_project($id);
        wp_send_json_success([
            'message' => __('Projekt został zapisany inline.', 'erp-omd'),
            'project_id' => $id,
            'status' => (string) ($payload['status'] ?? ''),
            'status_label' => $this->project_status_label((string) ($payload['status'] ?? '')),
        ]);
    }

    private function handle_project_duplicate()
    {
        check_admin_referer('erp_omd_duplicate_project');
        $this->require_capability('erp_omd_manage_projects');
        $project_id = (int) ($_POST['id'] ?? 0);
        $new_project_id = $this->duplicate_project_and_rebuild($project_id);
        if ($new_project_id <= 0) {
            $this->redirect_with_notice('erp-omd-projects', 'error', __('Nie znaleziono projektu do duplikacji.', 'erp-omd'));
        }

        $this->redirect_with_notice('erp-omd-projects', 'success', __('Projekt został zduplikowany.', 'erp-omd'), ['id' => $new_project_id]);
    }

    private function duplicate_project_and_rebuild($project_id)
    {
        $project = $project_id ? $this->projects->find((int) $project_id) : null;
        if (! $project) {
            return 0;
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
            'manager_ids' => array_map('intval', (array) ($project['manager_ids'] ?? array_filter([(int) ($project['manager_id'] ?? 0)]))),
            'estimate_id' => 0,
            'brief' => (string) ($project['brief'] ?? ''),
            'alert_margin_threshold' => $project['alert_margin_threshold'] ?? '',
        ]);

        $errors = $this->client_project_service->validate_project($duplicate_payload);
        if ($errors) {
            return 0;
        }

        $new_project_id = (int) $this->projects->create($duplicate_payload);
        if ($new_project_id <= 0) {
            return 0;
        }

        $this->project_financial_service->rebuild_for_project($new_project_id);

        return $new_project_id;
    }

    private function handle_project_active_toggle()
    {
        check_admin_referer('erp_omd_toggle_project_active');
        $this->require_capability('erp_omd_manage_projects');
        $id = (int) ($_POST['id'] ?? 0);
        $project = $id ? $this->projects->find($id) : null;

        if ($project) {
            $target_status = in_array((string) ($project['status'] ?? ''), ['inactive', 'archiwum'], true) ? 'do_rozpoczecia' : 'archiwum';
            $this->projects->set_status($id, $target_status);
            $message = $target_status === 'archiwum'
                ? __('Projekt został przeniesiony do archiwum.', 'erp-omd')
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
        $project = $this->projects->find($project_id);
        if (! $project) {
            $this->redirect_with_notice('erp-omd-projects', 'error', __('Projekt nie istnieje.', 'erp-omd'));
        }
        if ($this->is_project_cost_locked_by_status((string) ($project['status'] ?? ''))) {
            $this->redirect_with_notice(
                'erp-omd-projects',
                'error',
                __('Koszty projektu po statusie Zakończony/Archiwum modyfikuj wyłącznie przez „Szybka korekta admina (po zamknięciu miesiąca)”.', 'erp-omd'),
                ['id' => $project_id]
            );
        }
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

    private function handle_attach_cost_invoice_to_project()
    {
        check_admin_referer('erp_omd_attach_cost_invoice_to_project');
        $this->require_capability('erp_omd_manage_projects');

        $project_id = max(0, (int) ($_POST['project_id'] ?? 0));
        $invoice_id = max(0, (int) ($_POST['cost_invoice_id'] ?? 0));
        if ($project_id <= 0 || $invoice_id <= 0) {
            $this->redirect_with_notice('erp-omd-projects', 'error', __('Wybierz projekt i fakturę kosztową.', 'erp-omd'), ['id' => $project_id]);
        }

        $project = $this->projects->find($project_id);
        if (! is_array($project) || $project === []) {
            $this->redirect_with_notice('erp-omd-projects', 'error', __('Projekt nie istnieje.', 'erp-omd'), ['id' => $project_id]);
        }

        if ($this->is_project_cost_locked_by_status((string) ($project['status'] ?? ''))) {
            $this->redirect_with_notice(
                'erp-omd-projects',
                'error',
                __('Koszty projektu po statusie Zakończony/Archiwum modyfikuj wyłącznie przez „Szybka korekta admina (po zamknięciu miesiąca)”.', 'erp-omd'),
                ['id' => $project_id]
            );
        }

        $invoice_repository = new ERP_OMD_Cost_Invoice_Repository();
        $invoice = $invoice_repository->find($invoice_id);
        if (! is_array($invoice) || $invoice === []) {
            $this->redirect_with_notice('erp-omd-projects', 'error', __('Nie znaleziono faktury kosztowej.', 'erp-omd'), ['id' => $project_id]);
        }

        if ((int) ($invoice['project_id'] ?? 0) !== $project_id) {
            $this->redirect_with_notice('erp-omd-projects', 'error', __('Ta faktura kosztowa nie jest przypięta do wybranego projektu.', 'erp-omd'), ['id' => $project_id]);
        }

        $description = sprintf(
            '%s #%d (%s)',
            __('Faktura kosztowa', 'erp-omd'),
            $invoice_id,
            (string) ($invoice['invoice_number'] ?? '')
        );

        $existing_project_costs = (array) $this->project_costs->for_project($project_id);
        foreach ($existing_project_costs as $existing_project_cost) {
            if ((string) ($existing_project_cost['description'] ?? '') === $description) {
                $this->redirect_with_notice('erp-omd-projects', 'success', __('Faktura kosztowa była już podpięta jako koszt projektu.', 'erp-omd'), ['id' => $project_id]);
            }
        }

        $payload = [
            'project_id' => $project_id,
            'amount' => (float) ($invoice['net_amount'] ?? 0),
            'description' => $description,
            'cost_date' => (string) ($invoice['issue_date'] ?? gmdate('Y-m-d')),
            'created_by_user_id' => get_current_user_id(),
        ];
        $errors = $this->project_financial_service->validate_project_cost($payload);
        if ($errors !== []) {
            $this->redirect_with_notice('erp-omd-projects', 'error', implode(' ', $errors), ['id' => $project_id]);
        }

        $this->project_costs->create($payload);
        $this->project_financial_service->rebuild_for_project($project_id);
        $this->redirect_with_notice('erp-omd-projects', 'success', __('Faktura kosztowa została dodana do kosztów projektu (netto).', 'erp-omd'), ['id' => $project_id]);
    }

    /**
     * @param array<int,string> $categories
     * @return array<int,string>
     */
    private function normalize_supplier_categories(array $categories)
    {
        $categories = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static function ($category) {
                            return sanitize_text_field((string) $category);
                        },
                        $categories
                    ),
                    static function ($category) {
                        return $category !== '';
                    }
                )
            )
        );

        if ($categories === []) {
            return ['drukarnia', 'dostawca_gadzetow', 'podwykonawca', 'produkcja', 'inne'];
        }

        return $categories;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<int,string>
     */
    private function validate_supplier_contact_fields(array $payload)
    {
        $errors = [];
        $contact_name = trim((string) ($payload['contact_person_name'] ?? ''));
        $contact_email = trim((string) ($payload['contact_person_email'] ?? ''));
        $contact_phone = trim((string) ($payload['contact_person_phone'] ?? ''));

        $has_any_contact_field = ($contact_name !== '' || $contact_email !== '' || $contact_phone !== '');
        if (! $has_any_contact_field) {
            return $errors;
        }

        if ($contact_name === '') {
            $errors[] = __('Imię opiekuna jest wymagane, jeśli podajesz dane kontaktowe.', 'erp-omd');
        }

        if ($contact_email === '' && $contact_phone === '') {
            $errors[] = __('Podaj email lub telefon opiekuna.', 'erp-omd');
        }

        if ($contact_phone !== '' && preg_match('/^[0-9+\-\s()]{7,30}$/', $contact_phone) !== 1) {
            $errors[] = __('Telefon opiekuna ma nieprawidłowy format.', 'erp-omd');
        }

        return $errors;
    }

    /**
     * @param int $invoice_id
     * @return array<int,string>
     */
    private function sync_attached_cost_invoice_to_project_cost($invoice_id)
    {
        $invoice = (new ERP_OMD_Cost_Invoice_Repository())->find((int) $invoice_id);
        if (! is_array($invoice) || $invoice === []) {
            return [__('Nie znaleziono faktury kosztowej do synchronizacji.', 'erp-omd')];
        }

        $project_id = (int) ($invoice['project_id'] ?? 0);
        if ($project_id <= 0) {
            return [__('Faktura kosztowa musi mieć przypięty projekt.', 'erp-omd')];
        }

        $project = $this->projects->find($project_id);
        if (! is_array($project) || $project === []) {
            return [__('Projekt dla faktury kosztowej nie istnieje.', 'erp-omd')];
        }
        if ($this->is_project_cost_locked_by_status((string) ($project['status'] ?? ''))) {
            return [__('Nie można zsynchronizować kosztu dla projektu zamkniętego/archiwalnego.', 'erp-omd')];
        }

        $description = sprintf(
            '%s #%d (%s)',
            __('Faktura kosztowa', 'erp-omd'),
            (int) $invoice_id,
            (string) ($invoice['invoice_number'] ?? '')
        );
        $existing_project_costs = (array) $this->project_costs->for_project($project_id);
        foreach ($existing_project_costs as $existing_project_cost) {
            if ((string) ($existing_project_cost['description'] ?? '') === $description) {
                return [];
            }
        }

        $payload = [
            'project_id' => $project_id,
            'amount' => (float) ($invoice['net_amount'] ?? 0),
            'description' => $description,
            'cost_date' => (string) ($invoice['issue_date'] ?? gmdate('Y-m-d')),
            'created_by_user_id' => get_current_user_id(),
        ];
        $errors = $this->project_financial_service->validate_project_cost($payload);
        if ($errors !== []) {
            return array_values(array_filter(array_map('strval', $errors)));
        }

        $this->project_costs->create($payload);
        $this->project_financial_service->rebuild_for_project($project_id);

        return [];
    }

    private function handle_project_cost_delete()
    {
        check_admin_referer('erp_omd_delete_project_cost');
        $this->require_capability('erp_omd_manage_projects');
        $id = (int) ($_POST['project_cost_id'] ?? 0);
        $project_id = (int) ($_POST['project_id'] ?? 0);
        $existing = $id > 0 ? $this->project_costs->find($id) : null;
        if ($project_id <= 0 && is_array($existing)) {
            $project_id = (int) ($existing['project_id'] ?? 0);
        }
        $project = $project_id > 0 ? $this->projects->find($project_id) : null;
        if (is_array($project) && $this->is_project_cost_locked_by_status((string) ($project['status'] ?? ''))) {
            $this->redirect_with_notice(
                'erp-omd-projects',
                'error',
                __('Koszty projektu po statusie Zakończony/Archiwum modyfikuj wyłącznie przez „Szybka korekta admina (po zamknięciu miesiąca)”.', 'erp-omd'),
                ['id' => $project_id]
            );
        }
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
        if ($id) {
            $this->time_entries->update($id, $payload);
            $message = __('Wpis czasu został zaktualizowany.', 'erp-omd');
        } else {
            $id = $this->time_entries->create($payload);
            if ($id <= 0) {
                $this->redirect_with_notice('erp-omd-time', 'error', __('Nie udało się zapisać wpisu czasu. Sprawdź, czy podobny wpis nie istnieje już w systemie.', 'erp-omd'));
            }
            $message = __('Wpis czasu został dodany.', 'erp-omd');
        }
        $this->project_financial_service->rebuild_for_project((int) $payload['project_id']);
        $this->redirect_with_notice('erp-omd-time', 'success', $message);
    }

    private function handle_inline_time_entry_update_action()
    {
        check_admin_referer('erp_omd_inline_time_entry_update');
        $this->require_capability('erp_omd_manage_time');

        $id = (int) ($_POST['id'] ?? 0);
        $entry = $id ? $this->time_entries->find($id) : null;
        if (! $entry) {
            $this->redirect_with_notice('erp-omd-time', 'error', __('Nie znaleziono wpisu czasu do aktualizacji inline.', 'erp-omd'));
        }

        $current_user = wp_get_current_user();
        if (! $this->time_entry_service->can_edit_entry($entry, $current_user)) {
            $this->redirect_with_notice('erp-omd-time', 'error', __('Brak uprawnień do edycji wybranego wpisu czasu.', 'erp-omd'));
        }

        $status = sanitize_text_field(wp_unslash($_POST['status'] ?? ($entry['status'] ?? 'submitted')));
        $payload = $this->time_entry_service->prepare(
            [
                'employee_id' => (int) ($entry['employee_id'] ?? 0),
                'project_id' => (int) ($entry['project_id'] ?? 0),
                'role_id' => (int) ($entry['role_id'] ?? 0),
                'hours' => (float) ($_POST['hours'] ?? ($entry['hours'] ?? 0)),
                'entry_date' => (string) ($entry['entry_date'] ?? ''),
                'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? ($entry['description'] ?? ''))),
                'status' => $status,
                'created_by_user_id' => (int) ($entry['created_by_user_id'] ?? 0),
                'approved_by_user_id' => in_array($status, ['approved', 'rejected'], true) ? (int) $current_user->ID : (int) ($entry['approved_by_user_id'] ?? 0),
                'approved_at' => in_array($status, ['approved', 'rejected'], true) ? current_time('mysql') : ($entry['approved_at'] ?? null),
            ]
        );

        $errors = $this->time_entry_service->validate($payload, $id);
        if ($errors) {
            $this->redirect_with_notice('erp-omd-time', 'error', implode(' ', $errors));
        }

        $this->time_entries->update($id, $payload);
        $this->project_financial_service->rebuild_for_project((int) $payload['project_id']);
        $this->redirect_with_notice('erp-omd-time', 'success', __('Wpis czasu został zaktualizowany inline.', 'erp-omd'));
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
        } elseif ($bulk_action === 'change_project') {
            if (! current_user_can('administrator')) {
                wp_die(esc_html__('Zmiana projektu wpisów czasu jest dostępna tylko dla administratora.', 'erp-omd'));
            }

            $target_project_id = (int) ($_POST['target_project_id'] ?? 0);
            $target_project = $this->projects->find($target_project_id);
            if (! $target_project) {
                $this->redirect_with_notice('erp-omd-time', 'error', __('Wybierz poprawny projekt docelowy dla akcji masowej.', 'erp-omd'));
            }

            foreach ($time_entry_ids as $time_entry_id) {
                $entry = $this->time_entries->find($time_entry_id);
                if (! $entry) {
                    continue;
                }

                $payload = $entry;
                $payload['project_id'] = $target_project_id;
                $payload['rate_snapshot'] = $this->time_entry_service->resolve_rate_snapshot(
                    $target_project_id,
                    (int) ($entry['role_id'] ?? 0),
                    (string) ($entry['entry_date'] ?? '')
                );
                $payload['cost_snapshot'] = $this->time_entry_service->resolve_cost_snapshot(
                    (int) ($entry['employee_id'] ?? 0),
                    (string) ($entry['entry_date'] ?? '')
                );
                $this->time_entries->update($time_entry_id, $payload);
                $affected_project_ids[] = (int) ($entry['project_id'] ?? 0);
                $affected_project_ids[] = $target_project_id;
                $processed_count++;
            }

            $message = __('Projekt dla wybranych wpisów czasu został zmieniony.', 'erp-omd');
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

        $target_status = '';
        if (strpos($bulk_action, 'set_status_') === 0) {
            $target_status = substr($bulk_action, strlen('set_status_'));
        }

        if ($target_status === 'inactive') {
            $target_status = 'archiwum';
        }
        $allowed_statuses = ['do_rozpoczecia', 'w_realizacji', 'w_akceptacji', 'do_faktury', 'zakonczony', 'archiwum'];
        if ($target_status !== '' && ! in_array($target_status, $allowed_statuses, true)) {
            $this->redirect_with_notice('erp-omd-projects', 'error', __('Niepoprawna akcja masowa dla projektów.', 'erp-omd'));
        }

        foreach ($project_ids as $project_id) {
            if ($bulk_action === 'activate') {
                $this->projects->set_status($project_id, 'do_rozpoczecia');
            } elseif ($bulk_action === 'deactivate') {
                $this->projects->set_status($project_id, 'archiwum');
            } elseif ($bulk_action === 'duplicate') {
                $this->duplicate_project_and_rebuild($project_id);
            } elseif ($target_status !== '') {
                $project = $this->projects->find($project_id);
                if (! $project) {
                    continue;
                }
                $validation_payload = $this->client_project_service->prepare_project(['status' => $target_status], $project);
                $errors = $this->client_project_service->validate_project($validation_payload, $project);
                if ($errors) {
                    $this->redirect_with_notice('erp-omd-projects', 'error', implode(' ', array_unique($errors)), ['id' => $project_id]);
                }
                $this->projects->set_status($project_id, $target_status);
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
        $front_login_logo_id = max(0, (int) ($_POST['front_login_logo_id'] ?? 0));
        $front_login_cover_id = max(0, (int) ($_POST['front_login_cover_id'] ?? 0));

        if ($front_login_logo_id > 0 && ! wp_attachment_is_image($front_login_logo_id)) {
            $front_login_logo_id = 0;
        }

        if ($front_login_cover_id > 0 && ! wp_attachment_is_image($front_login_cover_id)) {
            $front_login_cover_id = 0;
        }

        $defaults = $this->missing_hours_notification_defaults();
        $raw_mode = sanitize_key((string) ($_POST['missing_hours_mode'] ?? $defaults['mode']));
        $mode = in_array($raw_mode, ['after_x_days', 'day_of_month'], true) ? $raw_mode : $defaults['mode'];
        $notification_settings = [
            'mode' => $mode,
            'after_days' => max(1, (int) ($_POST['missing_hours_after_days'] ?? $defaults['after_days'])),
            'day_of_month' => min(31, max(1, (int) ($_POST['missing_hours_day_of_month'] ?? $defaults['day_of_month']))),
            'subject' => sanitize_text_field(wp_unslash($_POST['missing_hours_mail_subject'] ?? $defaults['subject'])),
            'body' => wp_kses_post(wp_unslash($_POST['missing_hours_mail_body'] ?? $defaults['body'])),
        ];
        $notification_sender_email = sanitize_email(wp_unslash($_POST['notification_sender_email'] ?? ''));
        if ($notification_sender_email !== '' && ! is_email($notification_sender_email)) {
            $this->redirect_with_notice('erp-omd-settings', 'error', __('Adres nadawcy e-mail jest niepoprawny.', 'erp-omd'));
        }

        $active_recipients = array_values(array_filter(array_map('intval', wp_unslash($_POST['missing_hours_recipients_active'] ?? []))));
        $existing_recipients = (array) get_option('erp_omd_missing_hours_notification_recipients', []);
        $recipient_state = [];
        foreach ($this->employees->all() as $employee_row) {
            $employee_id = (int) ($employee_row['id'] ?? 0);
            if ($employee_id <= 0) {
                continue;
            }

            $previous = (array) ($existing_recipients[$employee_id] ?? []);
            $recipient_state[$employee_id] = [
                'active' => in_array($employee_id, $active_recipients, true) ? 1 : 0,
                'last_sent_at' => (string) ($previous['last_sent_at'] ?? ''),
            ];
        }

        update_option('erp_omd_delete_data_on_uninstall', ! empty($_POST['delete_data_on_uninstall']));
        update_option('erp_omd_front_admin_redirect_enabled', ! empty($_POST['front_admin_redirect_enabled']));
        update_option('erp_omd_reports_v1_rollout', 'all');
        $company_nip = preg_replace('/[^0-9]/', '', (string) wp_unslash($_POST['company_nip'] ?? ''));
        if (! is_string($company_nip)) {
            $company_nip = '';
        }
        if ($company_nip !== '' && strlen($company_nip) !== 10) {
            $this->redirect_with_notice('erp-omd-settings', 'error', __('NIP firmy musi mieć 10 cyfr.', 'erp-omd'));
        }

        update_option('erp_omd_alert_margin_threshold', max(0, (float) ($_POST['alert_margin_threshold'] ?? 10)));
        update_option('erp_omd_company_nip', $company_nip);
        update_option('erp_omd_reports_v1_metrics_freshness_minutes', max(5, (int) ($_POST['reports_v1_metrics_freshness_minutes'] ?? 1440)));
        $reports_v1_slo_generation_p95_max = max(100, min(30000, (int) ($_POST['reports_v1_slo_generation_p95_max'] ?? 2500)));
        if (! empty($_POST['apply_reports_v1_recommended_p95_max'])) {
            $reports_v1_metrics_log = (array) get_option('erp_omd_reports_v1_metrics_log', []);
            $reports_v1_samples = array_values(array_map(static function ($row) {
                return (int) ($row['generation_ms'] ?? 0);
            }, $reports_v1_metrics_log));
            sort($reports_v1_samples);
            $reports_v1_sample_count = count($reports_v1_samples);
            if ($reports_v1_sample_count > 0) {
                $reports_v1_p95_index = (int) ceil(0.95 * $reports_v1_sample_count) - 1;
                $reports_v1_p95_index = max(0, min($reports_v1_sample_count - 1, $reports_v1_p95_index));
                $reports_v1_generation_p95 = (int) ($reports_v1_samples[$reports_v1_p95_index] ?? 0);
                $reports_v1_slo_generation_p95_max = (int) ceil(max(500, $reports_v1_generation_p95 * 1.2) / 50) * 50;
                $reports_v1_slo_generation_p95_max = max(100, min(30000, $reports_v1_slo_generation_p95_max));
            }
        }
        update_option('erp_omd_reports_v1_slo_generation_p95_max', $reports_v1_slo_generation_p95_max);
        if (! empty($_POST['confirm_reports_v1_slo_calibration_decision'])) {
            $reports_v1_metrics_log = (array) get_option('erp_omd_reports_v1_metrics_log', []);
            $reports_v1_sample_count = count($reports_v1_metrics_log);
            $recommended_threshold = $reports_v1_slo_generation_p95_max;
            if ($reports_v1_sample_count > 0) {
                $reports_v1_samples = array_values(array_map(static function ($row) {
                    return (int) ($row['generation_ms'] ?? 0);
                }, $reports_v1_metrics_log));
                sort($reports_v1_samples);
                $reports_v1_p95_index = (int) ceil(0.95 * $reports_v1_sample_count) - 1;
                $reports_v1_p95_index = max(0, min($reports_v1_sample_count - 1, $reports_v1_p95_index));
                $reports_v1_generation_p95 = (int) ($reports_v1_samples[$reports_v1_p95_index] ?? 0);
                $recommended_threshold = (int) ceil(max(500, $reports_v1_generation_p95 * 1.2) / 50) * 50;
                $recommended_threshold = max(100, min(30000, $recommended_threshold));
            }
            update_option('erp_omd_reports_v1_slo_calibration_decision', [
                'decided_at' => gmdate('c'),
                'decided_by_user_id' => (int) get_current_user_id(),
                'threshold_ms' => (int) $reports_v1_slo_generation_p95_max,
                'recommended_threshold_ms' => (int) $recommended_threshold,
                'sample_count' => (int) $reports_v1_sample_count,
            ]);
        }
        if (! empty($_POST['confirm_reports_v1_slo_calibration_closure'])) {
            $reports_v1_slo_decision = (array) get_option('erp_omd_reports_v1_slo_calibration_decision', []);
            $decided_at = (string) ($reports_v1_slo_decision['decided_at'] ?? '');
            $threshold_ms = (int) ($reports_v1_slo_decision['threshold_ms'] ?? 0);
            if ($decided_at !== '' && $threshold_ms > 0) {
                update_option('erp_omd_reports_v1_slo_calibration_closure', [
                    'closed_at' => gmdate('c'),
                    'closed_by_user_id' => (int) get_current_user_id(),
                    'decision_decided_at' => $decided_at,
                    'decision_threshold_ms' => $threshold_ms,
                ]);
            }
        }
        update_option('erp_omd_front_login_logo_id', $front_login_logo_id);
        update_option('erp_omd_front_login_cover_id', $front_login_cover_id);
        update_option('erp_omd_missing_hours_notification_settings', $notification_settings);
        update_option('erp_omd_missing_hours_notification_recipients', $recipient_state);
        update_option('erp_omd_notification_sender_email', $notification_sender_email);
        $fixed_items = $this->normalize_fixed_monthly_cost_items(wp_unslash($_POST['fixed_cost_items'] ?? []));
        update_option('erp_omd_fixed_monthly_cost_items', $fixed_items);
        update_option('erp_omd_fixed_monthly_cost', array_sum(wp_list_pluck($fixed_items, 'amount')));
        $google_calendar_client_id = sanitize_text_field(wp_unslash($_POST['google_calendar_client_id'] ?? ''));
        $google_calendar_client_secret = trim((string) wp_unslash($_POST['google_calendar_client_secret'] ?? ''));
        $google_calendar_scope = sanitize_text_field(wp_unslash($_POST['google_calendar_scope'] ?? 'https://www.googleapis.com/auth/calendar'));
        if (! in_array($google_calendar_scope, ['https://www.googleapis.com/auth/calendar.events', 'https://www.googleapis.com/auth/calendar'], true)) {
            $google_calendar_scope = 'https://www.googleapis.com/auth/calendar';
        }
        $google_calendar_redirect_uri = $this->normalize_google_calendar_redirect_uri_v2((string) wp_unslash($_POST['google_calendar_redirect_uri'] ?? ''));
        if ($google_calendar_redirect_uri === '') {
            $google_calendar_redirect_uri = admin_url('admin.php?page=erp-omd-settings');
        }
        if (! wp_http_validate_url($google_calendar_redirect_uri)) {
            $this->redirect_with_notice('erp-omd-settings', 'error', __('Redirect URI Google Calendar jest niepoprawny.', 'erp-omd'));
        }
        $google_calendar_calendar_id = sanitize_text_field(wp_unslash($_POST['google_calendar_calendar_id'] ?? 'primary'));
        if ($google_calendar_calendar_id === '') {
            $google_calendar_calendar_id = 'primary';
        }
        $google_calendar_technical_account_email = sanitize_email(wp_unslash($_POST['google_calendar_technical_account_email'] ?? ''));
        if ($google_calendar_technical_account_email !== '' && ! is_email($google_calendar_technical_account_email)) {
            $this->redirect_with_notice('erp-omd-settings', 'error', __('Adres e-mail konta technicznego Google Calendar jest niepoprawny.', 'erp-omd'));
        }
        $ksef_api_enabled = ! empty($_POST['ksef_api_enabled']);
        $ksef_api_mode = sanitize_key((string) wp_unslash($_POST['ksef_api_mode'] ?? 'from_now'));
        if (! in_array($ksef_api_mode, ['from_now', 'backfill', 'all'], true)) {
            $ksef_api_mode = 'from_now';
        }
        $ksef_api_registration_date = sanitize_text_field((string) wp_unslash($_POST['ksef_api_registration_date'] ?? ''));
        if ($ksef_api_registration_date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $ksef_api_registration_date) !== 1) {
            $this->redirect_with_notice('erp-omd-settings', 'error', __('Data rejestracji KSeF musi mieć format YYYY-MM-DD.', 'erp-omd'));
        }
        $ksef_api_backfill_days = max(1, min(90, (int) wp_unslash($_POST['ksef_api_backfill_days'] ?? 90)));
        $ksef_api_alert_after_hours = max(1, min(168, (int) wp_unslash($_POST['ksef_api_alert_after_hours'] ?? 24)));
        $ksef_api_base_url = trim((string) wp_unslash($_POST['ksef_api_base_url'] ?? 'https://api.ksef.mf.gov.pl'));
        if ($ksef_api_base_url === '' || ! wp_http_validate_url($ksef_api_base_url)) {
            $this->redirect_with_notice('erp-omd-settings', 'error', __('Bazowy URL KSeF API jest niepoprawny.', 'erp-omd'));
        }
        $ksef_api_token = trim((string) wp_unslash($_POST['ksef_api_token'] ?? ''));
        $ksef_api_refresh_token = trim((string) wp_unslash($_POST['ksef_api_refresh_token'] ?? ''));
        $ksef_ap_token = trim((string) wp_unslash($_POST['ksef_ap_token'] ?? ''));
        $ksef_public_key_pem = trim((string) wp_unslash($_POST['ksef_public_key_pem'] ?? ''));
        $ksef_api_token_clear = ! empty($_POST['ksef_api_token_clear']);
        $ksef_api_refresh_token_clear = ! empty($_POST['ksef_api_refresh_token_clear']);
        $ksef_auto_create_supplier = ! empty($_POST['ksef_auto_create_supplier']);

        update_option('erp_omd_google_calendar_client_id', $google_calendar_client_id);
        update_option('erp_omd_google_calendar_scope', $google_calendar_scope);
        update_option('erp_omd_google_calendar_redirect_uri', $google_calendar_redirect_uri);
        update_option('erp_omd_google_calendar_calendar_id', $google_calendar_calendar_id);
        update_option('erp_omd_google_calendar_technical_account_email', $google_calendar_technical_account_email);
        if ($google_calendar_client_secret !== '') {
            update_option('erp_omd_google_calendar_client_secret_enc', $this->encrypt_option_value($google_calendar_client_secret));
        }
        update_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_ENABLED, $ksef_api_enabled);
        update_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_MODE, $ksef_api_mode);
        update_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_REGISTRATION_DATE, $ksef_api_registration_date);
        update_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_BACKFILL_DAYS, $ksef_api_backfill_days);
        update_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_ALERT_AFTER_HOURS, $ksef_api_alert_after_hours);
        update_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_API_BASE_URL, $ksef_api_base_url);
        update_option(ERP_OMD_KSeF_Import_Service::OPTION_AUTO_CREATE_SUPPLIER, $ksef_auto_create_supplier);
        if ($ksef_api_token_clear) {
            update_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_TOKEN_ENC, '');
        } elseif ($ksef_api_token !== '') {
            update_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_TOKEN_ENC, $this->encrypt_option_value($ksef_api_token));
        }
        if ($ksef_api_refresh_token_clear) {
            update_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_REFRESH_TOKEN_ENC, '');
        } elseif ($ksef_api_refresh_token !== '') {
            update_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_REFRESH_TOKEN_ENC, $this->encrypt_option_value($ksef_api_refresh_token));
        }
        if ($ksef_ap_token !== '') {
            update_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_AP_TOKEN_ENC, $this->encrypt_option_value($ksef_ap_token));
        }
        if ($ksef_public_key_pem !== '') {
            update_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_PUBLIC_KEY_PEM, $ksef_public_key_pem);
        }
        $this->redirect_with_notice('erp-omd-settings', 'success', __('Ustawienia zostały zapisane.', 'erp-omd'));
    }

    private function handle_google_calendar_connect()
    {
        check_admin_referer('erp_omd_google_calendar_connect');
        $this->require_capability('erp_omd_manage_settings');

        $client_id = trim((string) get_option('erp_omd_google_calendar_client_id', ''));
        $client_secret = trim((string) $this->decrypt_option_value((string) get_option('erp_omd_google_calendar_client_secret_enc', '')));
        $scope = trim((string) get_option('erp_omd_google_calendar_scope', 'https://www.googleapis.com/auth/calendar'));
        if ($client_id === '' || $client_secret === '') {
            $this->redirect_with_notice('erp-omd-settings', 'error', __('Aby połączyć Google Calendar, najpierw uzupełnij client_id i client_secret w ustawieniach.', 'erp-omd'));
        }

        $state_payload = [
            'nonce' => wp_generate_password(24, false, false),
            'user_id' => (int) get_current_user_id(),
            'created_at' => time(),
        ];
        update_option('erp_omd_google_calendar_oauth_state', wp_json_encode($state_payload));

        $redirect_uri = $this->google_calendar_redirect_uri();
        $auth_url = add_query_arg(
            [
                'client_id' => $client_id,
                'redirect_uri' => $redirect_uri,
                'response_type' => 'code',
                'access_type' => 'offline',
                'prompt' => 'consent',
                'scope' => $scope,
                'state' => $state_payload['nonce'],
            ],
            'https://accounts.google.com/o/oauth2/v2/auth'
        );

        wp_redirect($auth_url);
        exit;
    }

    private function handle_google_calendar_disconnect()
    {
        check_admin_referer('erp_omd_google_calendar_disconnect');
        $this->require_capability('erp_omd_manage_settings');

        update_option('erp_omd_google_calendar_access_token_enc', '');
        update_option('erp_omd_google_calendar_refresh_token_enc', '');
        update_option('erp_omd_google_calendar_access_token_expires_at', 0);
        update_option('erp_omd_google_calendar_last_error', '');
        update_option('erp_omd_google_calendar_last_sync_at', '');
        $this->redirect_with_notice('erp-omd-settings', 'success', __('Połączenie Google Calendar zostało rozłączone.', 'erp-omd'));
    }

    private function handle_google_calendar_sync_now()
    {
        check_admin_referer('erp_omd_google_calendar_sync_now');
        $this->require_capability('erp_omd_manage_settings');

        try {
            $sync_service = new ERP_OMD_Google_Calendar_Sync_Service(
                $this->projects,
                new ERP_OMD_Project_Calendar_Sync_Repository()
            );
            $sync_service->sync_all_projects();
        } catch (Throwable $exception) {
            $this->redirect_with_notice('erp-omd-settings', 'error', sprintf(__('Ręczna synchronizacja Google Calendar nie powiodła się: %s', 'erp-omd'), $exception->getMessage()));
        }

        $this->redirect_with_notice('erp-omd-settings', 'success', __('Ręczna synchronizacja Google Calendar została uruchomiona.', 'erp-omd'));
    }

    private function handle_google_calendar_fetch_calendars()
    {
        check_admin_referer('erp_omd_google_calendar_fetch_calendars');
        $this->require_capability('erp_omd_manage_settings');

        try {
            $sync_service = new ERP_OMD_Google_Calendar_Sync_Service(
                $this->projects,
                new ERP_OMD_Project_Calendar_Sync_Repository()
            );
            $calendars = $sync_service->list_calendars();
            update_option('erp_omd_google_calendar_available_calendars', $calendars);
        } catch (Throwable $exception) {
            if (stripos($exception->getMessage(), 'insufficient authentication scopes') !== false) {
                $this->redirect_with_notice('erp-omd-settings', 'error', __('Brak uprawnień scope do pobrania listy kalendarzy. Ustaw scope na „calendar”, zapisz ustawienia i połącz Google ponownie.', 'erp-omd'));
            }
            $this->redirect_with_notice('erp-omd-settings', 'error', sprintf(__('Nie udało się pobrać listy kalendarzy Google: %s', 'erp-omd'), $exception->getMessage()));
        }

        $this->redirect_with_notice('erp-omd-settings', 'success', __('Lista kalendarzy Google została pobrana.', 'erp-omd'));
    }

    private function handle_ksef_api_sync_now()
    {
        check_admin_referer('erp_omd_ksef_api_sync_now');
        $this->require_capability('erp_omd_manage_settings');

        $scope = sanitize_key((string) ($_POST['ksef_sync_scope'] ?? 'both'));
        if (! in_array($scope, ['cost', 'sales', 'both'], true)) {
            $scope = 'both';
        }
        $mode = sanitize_key((string) ($_POST['ksef_sync_mode'] ?? get_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_MODE, 'from_now')));
        if (! in_array($mode, ['from_now', 'backfill', 'all'], true)) {
            $mode = 'from_now';
        }
        $backfill_days = max(1, min(90, (int) ($_POST['ksef_sync_backfill_days'] ?? get_option(ERP_OMD_KSeF_API_Sync_Service::OPTION_BACKFILL_DAYS, 90))));

        $sync_service = $this->build_ksef_api_sync_service();
        $result = $sync_service->sync([
            'scope' => $scope,
            'mode' => $mode,
            'backfill_days' => $backfill_days,
            'force_now' => true,
        ]);
        if (! (bool) ($result['ok'] ?? false)) {
            $this->redirect_with_notice('erp-omd-settings', 'error', sprintf(__('Synchronizacja KSeF nie powiodła się: %s', 'erp-omd'), (string) ($result['last_error'] ?? '')));
        }

        $this->redirect_with_notice(
            'erp-omd-settings',
            'success',
            sprintf(
                __('Synchronizacja KSeF zakończona: pobrano %1$d, zaimportowano %2$d, błędy %3$d.', 'erp-omd'),
                (int) ($result['fetched'] ?? 0),
                (int) ($result['imported'] ?? 0),
                (int) ($result['failed'] ?? 0)
            )
        );
    }

    private function handle_ksef_fetch_public_key()
    {
        check_admin_referer('erp_omd_ksef_fetch_public_key');
        $this->require_capability('erp_omd_manage_settings');

        $sync_service = $this->build_ksef_api_sync_service();
        $result = $sync_service->fetch_and_store_token_encryption_public_key();
        if (! (bool) ($result['ok'] ?? false)) {
            $this->redirect_with_notice(
                'erp-omd-settings',
                'error',
                sprintf(__('Pobranie klucza publicznego KSeF nie powiodło się: %s', 'erp-omd'), (string) ($result['message'] ?? ''))
            );
        }
        $this->redirect_with_notice('erp-omd-settings', 'success', (string) ($result['message'] ?? __('Pobrano klucz publiczny KSeF (MF).', 'erp-omd')));
    }

    private function build_ksef_api_sync_service()
    {
        $invoice_repository = new ERP_OMD_Cost_Invoice_Repository();
        $audit_repository = new ERP_OMD_Cost_Invoice_Audit_Repository();
        $supplier_repository = new ERP_OMD_Supplier_Repository();
        $workflow = new ERP_OMD_Cost_Invoice_Workflow_Service(
            $invoice_repository,
            $audit_repository,
            $supplier_repository,
            new ERP_OMD_Project_Repository()
        );
        $import_service = new ERP_OMD_KSeF_Import_Service(
            $workflow,
            $invoice_repository,
            $audit_repository,
            null,
            null,
            $supplier_repository,
            new ERP_OMD_Client_Repository()
        );

        return new ERP_OMD_KSeF_API_Sync_Service($import_service, (string) get_option('erp_omd_company_nip', ''));
    }

    private function handle_google_calendar_oauth_callback()
    {
        $this->require_capability('erp_omd_manage_settings');
        $error = sanitize_text_field((string) ($_GET['error'] ?? ''));
        if ($error !== '') {
            $this->redirect_with_notice('erp-omd-settings', 'error', sprintf(__('Google OAuth zwrócił błąd: %s', 'erp-omd'), $error));
        }

        $code = sanitize_text_field((string) ($_GET['code'] ?? ''));
        $state = sanitize_text_field((string) ($_GET['state'] ?? ''));
        $state_payload = json_decode((string) get_option('erp_omd_google_calendar_oauth_state', ''), true);
        $expected_state = is_array($state_payload) ? (string) ($state_payload['nonce'] ?? '') : '';
        if ($code === '' || $state === '' || $expected_state === '' || ! hash_equals($expected_state, $state)) {
            $this->redirect_with_notice('erp-omd-settings', 'error', __('Niepoprawna odpowiedź OAuth Google Calendar (state/code).', 'erp-omd'));
        }

        $client_id = trim((string) get_option('erp_omd_google_calendar_client_id', ''));
        $client_secret = trim((string) $this->decrypt_option_value((string) get_option('erp_omd_google_calendar_client_secret_enc', '')));
        if ($client_id === '' || $client_secret === '') {
            $this->redirect_with_notice('erp-omd-settings', 'error', __('Brak client_id/client_secret dla Google OAuth.', 'erp-omd'));
        }

        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'timeout' => 20,
            'body' => [
                'code' => $code,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri' => $this->google_calendar_redirect_uri(),
                'grant_type' => 'authorization_code',
            ],
        ]);

        if (is_wp_error($response)) {
            $this->redirect_with_notice('erp-omd-settings', 'error', sprintf(__('Nie udało się połączyć z Google OAuth: %s', 'erp-omd'), $response->get_error_message()));
        }

        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        $access_token = (string) ($payload['access_token'] ?? '');
        $refresh_token = (string) ($payload['refresh_token'] ?? '');
        $expires_in = max(60, (int) ($payload['expires_in'] ?? 3600));
        if ($access_token === '' || $refresh_token === '') {
            $this->redirect_with_notice('erp-omd-settings', 'error', __('Google OAuth nie zwrócił access_token lub refresh_token.', 'erp-omd'));
        }

        update_option('erp_omd_google_calendar_access_token_enc', $this->encrypt_option_value($access_token));
        update_option('erp_omd_google_calendar_refresh_token_enc', $this->encrypt_option_value($refresh_token));
        update_option('erp_omd_google_calendar_access_token_expires_at', time() + $expires_in);
        update_option('erp_omd_google_calendar_last_error', '');
        update_option('erp_omd_google_calendar_oauth_state', '');
        $this->redirect_with_notice('erp-omd-settings', 'success', __('Google Calendar został pomyślnie połączony.', 'erp-omd'));
    }

    private function google_calendar_redirect_uri()
    {
        $stored_redirect_uri = $this->normalize_google_calendar_redirect_uri_v2((string) get_option('erp_omd_google_calendar_redirect_uri', ''));
        if ($stored_redirect_uri !== '' && wp_http_validate_url($stored_redirect_uri)) {
            return $stored_redirect_uri;
        }

        return admin_url('admin.php?page=erp-omd-settings');
    }

    private function normalize_google_calendar_redirect_uri_v2($redirect_uri)
    {
        $normalized_redirect_uri = html_entity_decode(trim((string) $redirect_uri), ENT_QUOTES, 'UTF-8');
        $normalized_redirect_uri = str_replace('&amp;', '&', $normalized_redirect_uri);

        return esc_url_raw($normalized_redirect_uri);
    }

    private function encrypt_option_value($raw_value)
    {
        $raw_value = (string) $raw_value;
        if ($raw_value === '' || ! function_exists('openssl_encrypt')) {
            return $raw_value;
        }

        $key = hash('sha256', (string) wp_salt('auth'), true);
        $iv = substr(hash('sha256', (string) wp_salt('secure_auth')), 0, 16);
        $encrypted = openssl_encrypt($raw_value, 'AES-256-CBC', $key, 0, $iv);

        return is_string($encrypted) ? $encrypted : $raw_value;
    }

    private function decrypt_option_value($encrypted)
    {
        $encrypted = (string) $encrypted;
        if ($encrypted === '' || ! function_exists('openssl_decrypt')) {
            return $encrypted;
        }

        $key = hash('sha256', (string) wp_salt('auth'), true);
        $iv = substr(hash('sha256', (string) wp_salt('secure_auth')), 0, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);

        return is_string($decrypted) ? $decrypted : $encrypted;
    }

    private function masked_secret($secret)
    {
        $secret = (string) $secret;
        if ($secret === '') {
            return '';
        }
        if (strlen($secret) <= 6) {
            return str_repeat('*', strlen($secret));
        }

        return substr($secret, 0, 3) . str_repeat('*', max(0, strlen($secret) - 6)) . substr($secret, -3);
    }

    private function normalize_fixed_monthly_cost_items(array $raw_items)
    {
        $items = [];

        foreach ($raw_items as $raw_item) {
            if (! is_array($raw_item)) {
                continue;
            }

            $name = sanitize_text_field((string) ($raw_item['name'] ?? ''));
            $amount = max(0.0, (float) ($raw_item['amount'] ?? 0));
            $valid_from = sanitize_text_field((string) ($raw_item['valid_from'] ?? ''));
            $valid_to = sanitize_text_field((string) ($raw_item['valid_to'] ?? ''));
            $active = ! empty($raw_item['active']) ? 1 : 0;

            if ($name === '' && $amount <= 0) {
                continue;
            }

            if ($valid_from !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $valid_from)) {
                $valid_from = '';
            }
            if ($valid_to !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $valid_to)) {
                $valid_to = '';
            }
            if ($valid_to !== '' && $valid_from !== '' && $valid_to < $valid_from) {
                $valid_to = $valid_from;
            }

            $items[] = [
                'name' => $name !== '' ? $name : __('Koszt stały', 'erp-omd'),
                'amount' => round($amount, 2),
                'valid_from' => $valid_from,
                'valid_to' => $valid_to,
                'active' => $active,
            ];
        }

        return array_slice($items, 0, 50);
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
        if ($entity_type === 'project') {
            $attachment_path = function_exists('get_attached_file') ? (string) get_attached_file($attachment_id) : '';
            $file_info = function_exists('wp_check_filetype_and_ext') ? (array) wp_check_filetype_and_ext($attachment_path, basename((string) $attachment_path)) : [];
            $is_pdf_candidate = (string) ($file_info['ext'] ?? '') === 'pdf' || (string) ($file_info['type'] ?? '') === 'application/pdf';
            if ($is_pdf_candidate) {
                $pdf_errors = (new ERP_OMD_Project_Attachment_Service($this->attachments))->validate_pdf_attachment($attachment_id);
                if ($pdf_errors) {
                    $this->redirect_with_notice($entity_type === 'project' ? 'erp-omd-projects' : 'erp-omd-estimates', 'error', implode(' ', $pdf_errors), ['id' => $entity_id]);
                }
            }
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
            case 'archiwum':
            case 'inactive':
                return __('Archiwum', 'erp-omd');
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
            case 'mixed':
                return __('Hybryda (ryczałt + godziny)', 'erp-omd');
            case 'time_material':
            default:
                return __('Godzinowy', 'erp-omd');
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

    private function resolve_project_deadline_status(array $project)
    {
        $deadline_date = (string) ($project['deadline_date'] ?? '');
        if ($deadline_date === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline_date) !== 1) {
            return 'ok';
        }

        if ((string) ($project['deadline_completed_at'] ?? '') !== '') {
            return 'ok';
        }

        $today = current_time('Y-m-d');
        if ($deadline_date < $today) {
            return 'po_terminie';
        }

        $today_dt = DateTimeImmutable::createFromFormat('Y-m-d', $today) ?: new DateTimeImmutable($today);
        $deadline_dt = DateTimeImmutable::createFromFormat('Y-m-d', $deadline_date) ?: new DateTimeImmutable($deadline_date);
        $days_to_deadline = (int) $today_dt->diff($deadline_dt)->days;

        if ($days_to_deadline <= 3) {
            return 'ryzyko';
        }

        return 'ok';
    }

    private function project_deadline_status_label($status)
    {
        switch ((string) $status) {
            case 'po_terminie':
                return __('Po terminie', 'erp-omd');
            case 'ryzyko':
                return __('Ryzyko', 'erp-omd');
            case 'ok':
            default:
                return __('OK', 'erp-omd');
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
                if (in_array((string) $status, ['inactive', 'archiwum'], true)) {
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
                if ((string) $status === 'odrzucony') {
                    return 'erp-omd-badge-error';
                }
                if ((string) $status === 'do_akceptacji') {
                    return 'erp-omd-badge-warning';
                }

                return 'erp-omd-badge-info';
            default:
                return 'erp-omd-badge-info';
        }
    }

    private function is_project_cost_locked_by_status($status)
    {
        return in_array((string) $status, ['zakonczony', 'archiwum'], true);
    }

    private function render_alert_icons(array $alerts)
    {
        if (empty($alerts)) {
            return;
        }

        echo '<span class="erp-omd-alert-icons" aria-label="' . esc_attr__('Aktywne alerty', 'erp-omd') . '">';

        foreach ($alerts as $alert) {
            $severity = sanitize_html_class((string) ($alert['severity'] ?? 'info'));
            $message = trim((string) ($alert['message'] ?? ''));
            $code = trim((string) ($alert['code'] ?? ''));
            $tooltip = $message !== '' ? $message : $code;

            if ($tooltip === '') {
                $tooltip = __('Alert', 'erp-omd');
            }

            $icon = 'i';
            if ($severity === 'error') {
                $icon = '!';
            } elseif ($severity === 'warning') {
                $icon = '!';
            } elseif ($severity === 'info') {
                $icon = 'i';
            }

            echo '<span class="erp-omd-alert-icon erp-omd-alert-icon-' . esc_attr($severity) . '" title="' . esc_attr($tooltip) . '" aria-label="' . esc_attr($tooltip) . '" tabindex="0">' . esc_html($icon) . '</span>';
        }

        echo '</span>';
    }


    private function missing_hours_notification_defaults()
    {
        return [
            'mode' => 'after_x_days',
            'after_days' => 3,
            'day_of_month' => 1,
            'subject' => __('Przypomnienie o raporcie godzin pracy', 'erp-omd'),
            'body' => __('Cześć {login},<br><br>ostatni raport godzin wysłałeś: <strong>{last_reported_date}</strong>.<br>Prosimy o uzupełnienie brakujących godzin.', 'erp-omd'),
        ];
    }

    private function estimate_client_link_state()
    {
        $state = (array) get_option('erp_omd_estimate_client_link_tokens', []);
        if ($state === []) {
            return [];
        }

        $now = time();
        foreach ($state as $estimate_id => $token_row) {
            $expires_at = (int) ($token_row['expires_at'] ?? 0);
            if ($expires_at > 0 && $expires_at < $now) {
                unset($state[$estimate_id]);
            }
        }

        return $state;
    }

    private function is_valid_month_string($month)
    {
        if (! is_string($month) || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) !== 1) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m', $month);
        if (! $date instanceof DateTimeImmutable) {
            return false;
        }

        return $date->format('Y-m') === $month;
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
}

add_action('admin_notices', ['ERP_OMD_Admin', 'render_notice']);
