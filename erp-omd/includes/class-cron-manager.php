<?php

class ERP_OMD_Cron_Manager
{
    const WEEKLY_BACKUP_HOOK = 'erp_omd_weekly_db_backup';
    const MISSING_HOURS_HOOK = 'erp_omd_daily_missing_hours_notifications';
    const PROJECT_DEADLINE_HOOK = 'erp_omd_daily_project_deadline_notifications';
    const GOOGLE_CALENDAR_SYNC_HOOK = 'erp_omd_google_calendar_sync';
    const KSEF_RETRY_PIPELINE_HOOK = 'erp_omd_ksef_retry_pipeline';

    public static function register_hooks()
    {
        add_filter('cron_schedules', [__CLASS__, 'register_weekly_schedule']);
        add_action(self::WEEKLY_BACKUP_HOOK, [__CLASS__, 'run_weekly_backup']);
        add_action(self::MISSING_HOURS_HOOK, [__CLASS__, 'run_missing_hours_notifications']);
        add_action(self::PROJECT_DEADLINE_HOOK, [__CLASS__, 'run_project_deadline_notifications']);
        add_action(self::GOOGLE_CALENDAR_SYNC_HOOK, [__CLASS__, 'run_google_calendar_sync']);
        add_action(self::KSEF_RETRY_PIPELINE_HOOK, [__CLASS__, 'run_ksef_retry_pipeline']);
        self::schedule_events();
    }

    public static function activate()
    {
        self::schedule_events();
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook(self::WEEKLY_BACKUP_HOOK);
        wp_clear_scheduled_hook(self::MISSING_HOURS_HOOK);
        wp_clear_scheduled_hook(self::PROJECT_DEADLINE_HOOK);
        wp_clear_scheduled_hook(self::GOOGLE_CALENDAR_SYNC_HOOK);
        wp_clear_scheduled_hook(self::KSEF_RETRY_PIPELINE_HOOK);
    }

    public static function register_weekly_schedule($schedules)
    {
        if (! isset($schedules['erp_omd_weekly'])) {
            $schedules['erp_omd_weekly'] = [
                'interval' => 7 * DAY_IN_SECONDS,
                // Intentionally not translated here to avoid loading textdomain before init.
                'display' => 'ERP OMD Weekly',
            ];
        }
        if (! isset($schedules['erp_omd_two_hours'])) {
            $schedules['erp_omd_two_hours'] = [
                'interval' => 2 * HOUR_IN_SECONDS,
                'display' => 'ERP OMD Every 2 Hours',
            ];
        }
        if (! isset($schedules['erp_omd_five_minutes'])) {
            $schedules['erp_omd_five_minutes'] = [
                'interval' => 5 * MINUTE_IN_SECONDS,
                'display' => 'ERP OMD Every 5 Minutes',
            ];
        }

        return $schedules;
    }

    public static function schedule_events()
    {
        if (! wp_next_scheduled(self::WEEKLY_BACKUP_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'erp_omd_weekly', self::WEEKLY_BACKUP_HOOK);
        }

        if (! wp_next_scheduled(self::MISSING_HOURS_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::MISSING_HOURS_HOOK);
        }
        if (! wp_next_scheduled(self::PROJECT_DEADLINE_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::PROJECT_DEADLINE_HOOK);
        }
        if (! wp_next_scheduled(self::GOOGLE_CALENDAR_SYNC_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'erp_omd_two_hours', self::GOOGLE_CALENDAR_SYNC_HOOK);
        }
        if (! wp_next_scheduled(self::KSEF_RETRY_PIPELINE_HOOK)) {
            wp_schedule_event(time() + 5 * MINUTE_IN_SECONDS, 'erp_omd_five_minutes', self::KSEF_RETRY_PIPELINE_HOOK);
        }
    }

    public static function run_weekly_backup()
    {
        ERP_OMD_Backup_Manager::run_backup_bundle();
    }

    public static function run_missing_hours_notifications()
    {
        $settings = self::notification_settings();
        $employees = (new ERP_OMD_Employee_Repository())->all();
        $last_entry_dates = (new ERP_OMD_Time_Entry_Repository())->latest_entry_dates_by_employee();
        $recipient_state = (array) get_option('erp_omd_missing_hours_notification_recipients', []);
        $today = current_time('Y-m-d');

        foreach ($employees as $employee) {
            $employee_id = (int) ($employee['id'] ?? 0);
            if ($employee_id <= 0 || (string) ($employee['status'] ?? '') !== 'active') {
                continue;
            }

            if (! self::is_employee_notifications_active($employee_id, $recipient_state)) {
                continue;
            }

            $last_entry_date = (string) ($last_entry_dates[$employee_id] ?? '');
            if (! self::is_notification_due($settings, $last_entry_date)) {
                continue;
            }

            $last_sent_at = isset($recipient_state[$employee_id]['last_sent_at']) ? (string) $recipient_state[$employee_id]['last_sent_at'] : '';
            if ($last_sent_at !== '' && wp_date('Y-m-d', strtotime($last_sent_at)) === $today) {
                continue;
            }

            $to = sanitize_email((string) ($employee['user_email'] ?? ''));
            if (! is_email($to)) {
                continue;
            }

            $subject = self::render_template((string) $settings['subject'], $employee, $last_entry_date);
            $body = self::render_template((string) $settings['body'], $employee, $last_entry_date);
            $headers = ['Content-Type: text/html; charset=UTF-8'];
            $sent = wp_mail($to, $subject, wpautop($body), $headers);

            if ($sent) {
                $recipient_state[$employee_id]['active'] = 1;
                $recipient_state[$employee_id]['last_sent_at'] = current_time('mysql');
            }
        }

        update_option('erp_omd_missing_hours_notification_recipients', $recipient_state);
    }

    public static function run_project_deadline_notifications()
    {
        $projects = (new ERP_OMD_Project_Repository())->all();
        $employees = (new ERP_OMD_Employee_Repository())->all();
        $employee_email_by_id = [];
        foreach ($employees as $employee) {
            $employee_email_by_id[(int) ($employee['id'] ?? 0)] = sanitize_email((string) ($employee['user_email'] ?? ''));
        }

        $admin_users = get_users(['role' => 'administrator', 'fields' => ['user_email']]);
        $admin_emails = [];
        foreach ($admin_users as $admin_user) {
            $admin_email = sanitize_email((string) ($admin_user->user_email ?? ''));
            if (is_email($admin_email)) {
                $admin_emails[] = $admin_email;
            }
        }

        $today = current_time('Y-m-d');
        $today_dt = DateTimeImmutable::createFromFormat('Y-m-d', $today) ?: new DateTimeImmutable($today);
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        foreach ($projects as $project) {
            $status = (string) ($project['status'] ?? '');
            if (in_array($status, ['archiwum'], true)) {
                continue;
            }
            if ((string) ($project['deadline_completed_at'] ?? '') !== '') {
                continue;
            }

            $deadline_date = (string) ($project['deadline_date'] ?? '');
            if ($deadline_date === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline_date) !== 1) {
                continue;
            }

            $deadline_dt = DateTimeImmutable::createFromFormat('Y-m-d', $deadline_date) ?: new DateTimeImmutable($deadline_date);
            $is_overdue = $deadline_dt < $today_dt;
            $days_to_deadline = (int) $today_dt->diff($deadline_dt)->days;
            if (! $is_overdue && ! in_array($days_to_deadline, [3, 1], true)) {
                continue;
            }

            $project_name = (string) ($project['name'] ?? ('#' . (int) ($project['id'] ?? 0)));
            $phase_label = $is_overdue
                ? __('po terminie', 'erp-omd')
                : sprintf(__('za %d dni', 'erp-omd'), $days_to_deadline);
            $subject = sprintf(__('[ERP OMD] Deadline projektu: %1$s (%2$s)', 'erp-omd'), $project_name, $phase_label);
            $body = sprintf(
                __('Projekt <strong>%1$s</strong> ma deadline <strong>%2$s</strong> (%3$s).', 'erp-omd'),
                esc_html($project_name),
                esc_html($deadline_date),
                esc_html($phase_label)
            );

            $recipient_emails = $admin_emails;
            foreach ((array) ($project['manager_ids'] ?? []) as $manager_id) {
                $email = (string) ($employee_email_by_id[(int) $manager_id] ?? '');
                if (is_email($email)) {
                    $recipient_emails[] = $email;
                }
            }
            $recipient_emails = array_values(array_unique(array_filter($recipient_emails, 'is_email')));

            foreach ($recipient_emails as $recipient_email) {
                wp_mail($recipient_email, $subject, wpautop($body), $headers);
            }
        }
    }

    public static function run_google_calendar_sync()
    {
        $service = new ERP_OMD_Google_Calendar_Sync_Service(
            new ERP_OMD_Project_Repository(),
            new ERP_OMD_Project_Calendar_Sync_Repository()
        );
        $service->sync_all_projects();
    }


    public static function run_ksef_retry_pipeline()
    {
        $invoice_repository = new ERP_OMD_Cost_Invoice_Repository();
        $audit_repository = new ERP_OMD_Cost_Invoice_Audit_Repository();
        $workflow = new ERP_OMD_Cost_Invoice_Workflow_Service(
            $invoice_repository,
            $audit_repository,
            new ERP_OMD_Supplier_Repository(),
            new ERP_OMD_Project_Repository()
        );
        $service = new ERP_OMD_KSeF_Import_Service($workflow, $invoice_repository, $audit_repository);
        $service->process_retry_queue(20);
    }

    private static function notification_settings()
    {
        $defaults = [
            'mode' => 'after_x_days',
            'after_days' => 3,
            'day_of_month' => 1,
            'subject' => __('Przypomnienie o raporcie godzin pracy', 'erp-omd'),
            'body' => __('Cześć {login},<br><br>ostatni raport godzin wysłałeś: <strong>{last_reported_date}</strong>.<br>Prosimy o uzupełnienie brakujących godzin.', 'erp-omd'),
        ];

        $settings = (array) get_option('erp_omd_missing_hours_notification_settings', []);
        $settings = wp_parse_args($settings, $defaults);
        $settings['mode'] = in_array($settings['mode'], ['after_x_days', 'day_of_month'], true) ? $settings['mode'] : 'after_x_days';
        $settings['after_days'] = max(1, (int) $settings['after_days']);
        $settings['day_of_month'] = min(31, max(1, (int) $settings['day_of_month']));

        return $settings;
    }

    private static function is_employee_notifications_active($employee_id, array $recipient_state)
    {
        if (! isset($recipient_state[$employee_id])) {
            return true;
        }

        return ! empty($recipient_state[$employee_id]['active']);
    }

    private static function is_notification_due(array $settings, $last_entry_date)
    {
        $today = new DateTimeImmutable(current_time('Y-m-d'));
        $last_date = $last_entry_date !== '' ? DateTimeImmutable::createFromFormat('Y-m-d', $last_entry_date) : null;

        if ($settings['mode'] === 'day_of_month') {
            $days_in_month = (int) $today->format('t');
            $trigger_day = min((int) $settings['day_of_month'], $days_in_month);
            if ((int) $today->format('j') !== $trigger_day) {
                return false;
            }

            if (! $last_date) {
                return true;
            }

            return $last_date->format('Y-m-d') < $today->format('Y-m-d');
        }

        if (! $last_date) {
            return true;
        }

        $threshold = $today->modify('-' . (int) $settings['after_days'] . ' days');
        return $last_date->format('Y-m-d') <= $threshold->format('Y-m-d');
    }


    public static function restore_backup_bundle_from_zip($zip_path)
    {
        if (class_exists('ERP_OMD_Backup_Manager') && method_exists('ERP_OMD_Backup_Manager', 'restore_backup_bundle_from_zip')) {
            return ERP_OMD_Backup_Manager::restore_backup_bundle_from_zip($zip_path);
        }

        return ['success' => false, 'error' => 'backup_restore_not_available'];
    }

    public static function import_sql_dump($sql_path)
    {
        if (class_exists('ERP_OMD_Backup_Manager') && method_exists('ERP_OMD_Backup_Manager', 'import_sql_dump')) {
            return ERP_OMD_Backup_Manager::import_sql_dump($sql_path);
        }

        return false;
    }

    private static function render_template($template, array $employee, $last_entry_date)
    {
        $days_since = '';
        if ($last_entry_date !== '') {
            $days_since = (string) max(0, (new DateTimeImmutable(current_time('Y-m-d')))->diff(new DateTimeImmutable($last_entry_date))->days);
        }

        $replacements = [
            '{login}' => (string) ($employee['user_login'] ?? ''),
            '{employee_id}' => (string) ((int) ($employee['id'] ?? 0)),
            '{last_reported_date}' => $last_entry_date !== '' ? $last_entry_date : __('brak wpisów', 'erp-omd'),
            '{days_since_last_report}' => $days_since,
        ];

        return strtr($template, $replacements);
    }
}
