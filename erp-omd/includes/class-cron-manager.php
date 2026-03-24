<?php

class ERP_OMD_Cron_Manager
{
    const WEEKLY_BACKUP_HOOK = 'erp_omd_weekly_db_backup';
    const MISSING_HOURS_HOOK = 'erp_omd_daily_missing_hours_notifications';

    public static function register_hooks()
    {
        add_filter('cron_schedules', [__CLASS__, 'register_weekly_schedule']);
        add_action(self::WEEKLY_BACKUP_HOOK, [__CLASS__, 'run_weekly_backup']);
        add_action(self::MISSING_HOURS_HOOK, [__CLASS__, 'run_missing_hours_notifications']);
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
    }

    public static function register_weekly_schedule($schedules)
    {
        if (! isset($schedules['erp_omd_weekly'])) {
            $schedules['erp_omd_weekly'] = [
                'interval' => 7 * DAY_IN_SECONDS,
                'display' => __('Raz w tygodniu', 'erp-omd'),
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
    }

    public static function run_weekly_backup()
    {
        if (! class_exists('ZipArchive')) {
            update_option('erp_omd_last_backup_status', 'ziparchive_missing');
            update_option('erp_omd_last_backup_at', current_time('mysql'));
            return;
        }

        $upload_dir = wp_upload_dir();
        $backup_dir = trailingslashit($upload_dir['basedir']) . 'erp-omd-backups';
        if (! wp_mkdir_p($backup_dir)) {
            update_option('erp_omd_last_backup_status', 'mkdir_failed');
            update_option('erp_omd_last_backup_at', current_time('mysql'));
            return;
        }

        $timestamp = current_time('Ymd-His');
        $sql_path = trailingslashit($backup_dir) . "erp-omd-db-{$timestamp}.sql";
        $zip_path = trailingslashit($backup_dir) . "erp-omd-db-{$timestamp}.zip";
        $dump = self::build_database_dump();

        if ($dump === '') {
            update_option('erp_omd_last_backup_status', 'dump_failed');
            update_option('erp_omd_last_backup_at', current_time('mysql'));
            return;
        }

        file_put_contents($sql_path, $dump);

        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($sql_path);
            update_option('erp_omd_last_backup_status', 'zip_failed');
            update_option('erp_omd_last_backup_at', current_time('mysql'));
            return;
        }

        $zip->addFile($sql_path, basename($sql_path));
        $zip->close();
        @unlink($sql_path);

        self::prune_old_backups($backup_dir, 12);

        update_option('erp_omd_last_backup_status', 'success');
        update_option('erp_omd_last_backup_at', current_time('mysql'));
        update_option('erp_omd_last_backup_file', $zip_path);
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

    private static function build_database_dump()
    {
        global $wpdb;

        $tables = $wpdb->get_col('SHOW TABLES');
        if (empty($tables)) {
            return '';
        }

        $dump = "-- ERP OMD database backup\n";
        $dump .= '-- Generated at: ' . current_time('mysql') . "\n\n";

        foreach ($tables as $table) {
            $table = (string) $table;
            $create_row = $wpdb->get_row("SHOW CREATE TABLE `{$table}`", ARRAY_N);
            if (! is_array($create_row) || ! isset($create_row[1])) {
                continue;
            }

            $dump .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $dump .= $create_row[1] . ";\n\n";

            $rows = $wpdb->get_results("SELECT * FROM `{$table}`", ARRAY_A);
            foreach ($rows as $row) {
                $columns = array_map(static function ($column) {
                    return '`' . $column . '`';
                }, array_keys($row));
                $values = array_map(static function ($value) {
                    if ($value === null) {
                        return 'NULL';
                    }

                    return "'" . esc_sql($value) . "'";
                }, array_values($row));

                $dump .= sprintf(
                    "INSERT INTO `%s` (%s) VALUES (%s);\n",
                    $table,
                    implode(', ', $columns),
                    implode(', ', $values)
                );
            }

            $dump .= "\n";
        }

        return $dump;
    }

    private static function prune_old_backups($backup_dir, $keep_count)
    {
        $files = glob(trailingslashit($backup_dir) . 'erp-omd-db-*.zip') ?: [];
        rsort($files, SORT_STRING);

        if (count($files) <= $keep_count) {
            return;
        }

        $files_to_delete = array_slice($files, $keep_count);
        foreach ($files_to_delete as $file) {
            @unlink($file);
        }
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
        $settings['day_of_month'] = min(28, max(1, (int) $settings['day_of_month']));

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
            if ((int) $today->format('j') !== (int) $settings['day_of_month']) {
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
