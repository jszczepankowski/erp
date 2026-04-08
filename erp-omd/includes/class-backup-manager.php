<?php

class ERP_OMD_Backup_Manager
{
    const BACKUP_MANIFEST_FILE = 'erp-omd-backup-manifest.json';

    public static function run_backup_bundle()
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
        $sql_basename = "erp-omd-db-{$timestamp}.sql";
        $settings_basename = "erp-omd-settings-{$timestamp}.json";
        $sql_path = trailingslashit($backup_dir) . $sql_basename;
        $zip_path = trailingslashit($backup_dir) . "erp-omd-db-{$timestamp}.zip";
        $dump = self::build_database_dump();
        $settings_payload = self::build_settings_export_payload();
        $settings_json = wp_json_encode($settings_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($dump === '' || ! is_string($settings_json) || $settings_json === '') {
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

        $zip->addFile($sql_path, $sql_basename);
        $zip->addFromString($settings_basename, $settings_json);
        $zip->addFromString(self::BACKUP_MANIFEST_FILE, wp_json_encode([
            'created_at' => current_time('mysql'),
            'plugin_version' => defined('ERP_OMD_VERSION') ? ERP_OMD_VERSION : '',
            'db_prefix' => self::database_prefix(),
            'sql_file' => $sql_basename,
            'settings_file' => $settings_basename,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $zip->close();
        @unlink($sql_path);

        self::prune_old_backups($backup_dir, 12);

        update_option('erp_omd_last_backup_status', 'success');
        update_option('erp_omd_last_backup_at', current_time('mysql'));
        update_option('erp_omd_last_backup_file', $zip_path);
    }

    public static function restore_backup_bundle_from_zip($zip_path)
    {
        if (! class_exists('ZipArchive')) {
            throw new RuntimeException('ZipArchive extension is required to restore backup.');
        }
        if (! is_string($zip_path) || $zip_path === '' || ! is_readable($zip_path)) {
            throw new RuntimeException('Backup ZIP file is not readable.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zip_path) !== true) {
            throw new RuntimeException('Unable to open backup ZIP file.');
        }

        $manifest = [];
        $manifest_raw = $zip->getFromName(self::BACKUP_MANIFEST_FILE);
        if (is_string($manifest_raw) && $manifest_raw !== '') {
            $manifest_decoded = json_decode($manifest_raw, true);
            if (is_array($manifest_decoded)) {
                $manifest = $manifest_decoded;
            }
        }

        $sql_file = (string) ($manifest['sql_file'] ?? '');
        $settings_file = (string) ($manifest['settings_file'] ?? '');

        if ($sql_file === '' || $zip->locateName($sql_file) === false) {
            $sql_file = self::find_zip_file_by_extension($zip, '.sql');
        }
        if ($settings_file === '' || $zip->locateName($settings_file) === false) {
            $settings_file = self::find_zip_file_by_extension($zip, '.json', [self::BACKUP_MANIFEST_FILE]);
        }

        if ($sql_file === '') {
            $zip->close();
            throw new RuntimeException('SQL dump file not found inside backup ZIP.');
        }
        if ($settings_file === '') {
            $zip->close();
            throw new RuntimeException('Settings export file not found inside backup ZIP.');
        }

        $sql_dump = (string) $zip->getFromName($sql_file);
        $settings_raw = (string) $zip->getFromName($settings_file);
        $zip->close();

        if ($sql_dump === '') {
            throw new RuntimeException('SQL dump file is empty.');
        }
        $settings_payload = json_decode($settings_raw, true);
        if (! is_array($settings_payload) || ! isset($settings_payload['options']) || ! is_array($settings_payload['options'])) {
            throw new RuntimeException('Settings export file is invalid.');
        }

        $source_prefix = (string) ($manifest['db_prefix'] ?? self::database_prefix());
        $target_prefix = self::database_prefix();
        if ($source_prefix !== '' && $source_prefix !== $target_prefix) {
            $sql_dump = str_replace('`' . $source_prefix . 'erp_omd_', '`' . $target_prefix . 'erp_omd_', $sql_dump);
        }

        self::import_sql_dump($sql_dump);
        self::import_settings_payload($settings_payload);
        update_option('erp_omd_last_restore_status', 'success');
        update_option('erp_omd_last_restore_at', current_time('mysql'));
    }

    private static function build_database_dump()
    {
        global $wpdb;
        $tables = $wpdb->get_col('SHOW TABLES');
        $tables = self::filter_erp_tables((array) $tables, (string) $wpdb->prefix);
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
                $dump .= sprintf("INSERT INTO `%s` (%s) VALUES (%s);\n", $table, implode(', ', $columns), implode(', ', $values));
            }
            $dump .= "\n";
        }

        return $dump;
    }

    private static function filter_erp_tables(array $tables, $db_prefix)
    {
        $allowed_prefix = (string) $db_prefix . 'erp_omd_';
        return array_values(array_filter($tables, static function ($table) use ($allowed_prefix) {
            return $allowed_prefix !== '' && strpos((string) $table, $allowed_prefix) === 0;
        }));
    }

    private static function build_settings_export_payload()
    {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
                'erp_omd_%'
            ),
            ARRAY_A
        );
        $options = [];
        foreach ((array) $rows as $row) {
            $option_name = (string) ($row['option_name'] ?? '');
            if ($option_name === '') {
                continue;
            }
            $options[$option_name] = maybe_unserialize($row['option_value'] ?? null);
        }

        return [
            'created_at' => current_time('mysql'),
            'plugin_version' => defined('ERP_OMD_VERSION') ? ERP_OMD_VERSION : '',
            'site_url' => function_exists('home_url') ? home_url('/') : '',
            'db_prefix' => self::database_prefix(),
            'options' => $options,
        ];
    }

    private static function import_settings_payload(array $payload)
    {
        $options = (array) ($payload['options'] ?? []);
        foreach ($options as $option_name => $value) {
            if (! is_string($option_name) || strpos($option_name, 'erp_omd_') !== 0) {
                continue;
            }
            update_option($option_name, $value);
        }
    }

    private static function import_sql_dump($sql_dump)
    {
        global $wpdb;
        if (! isset($wpdb->dbh) || ! $wpdb->dbh) {
            throw new RuntimeException('Database connection is not available.');
        }
        if (! function_exists('mysqli_multi_query')) {
            throw new RuntimeException('mysqli_multi_query is required to import SQL dump.');
        }
        $dbh = $wpdb->dbh;
        @mysqli_query($dbh, 'SET FOREIGN_KEY_CHECKS = 0');
        @mysqli_query($dbh, 'SET UNIQUE_CHECKS = 0');
        try {
            if (! @mysqli_multi_query($dbh, $sql_dump)) {
                throw new RuntimeException('Failed to execute SQL dump import.');
            }
            while (true) {
                if ($result = @mysqli_store_result($dbh)) {
                    mysqli_free_result($result);
                }
                if (@mysqli_errno($dbh) !== 0) {
                    throw new RuntimeException('SQL dump import returned database error: ' . (string) @mysqli_error($dbh));
                }
                if (! @mysqli_more_results($dbh)) {
                    break;
                }
                if (! @mysqli_next_result($dbh)) {
                    throw new RuntimeException('SQL dump import stopped before processing all statements: ' . (string) @mysqli_error($dbh));
                }
            }
        } finally {
            @mysqli_query($dbh, 'SET UNIQUE_CHECKS = 1');
            @mysqli_query($dbh, 'SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    private static function find_zip_file_by_extension(ZipArchive $zip, $extension, array $exclude = [])
    {
        $extension = strtolower((string) $extension);
        $exclude_map = [];
        foreach ($exclude as $excluded_file) {
            $exclude_map[(string) $excluded_file] = true;
        }
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);
            if ($name === '' || isset($exclude_map[$name])) {
                continue;
            }
            if (substr(strtolower($name), -strlen($extension)) === $extension) {
                return $name;
            }
        }

        return '';
    }

    private static function prune_old_backups($backup_dir, $keep_count)
    {
        $files = glob(trailingslashit($backup_dir) . 'erp-omd-db-*.zip') ?: [];
        rsort($files, SORT_STRING);
        if (count($files) <= $keep_count) {
            return;
        }
        foreach (array_slice($files, $keep_count) as $file) {
            @unlink($file);
        }
    }

    private static function database_prefix()
    {
        global $wpdb;
        return isset($wpdb->prefix) ? (string) $wpdb->prefix : '';
    }
}

