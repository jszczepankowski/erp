<?php

declare(strict_types=1);

if (! function_exists('current_time')) {
    function current_time($type)
    {
        return $type === 'mysql' ? '2026-04-04 12:00:00' : '';
    }
}

if (! function_exists('esc_sql')) {
    function esc_sql($value)
    {
        return addslashes((string) $value);
    }
}

if (! defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

if (! defined('ARRAY_N')) {
    define('ARRAY_N', 'ARRAY_N');
}

require_once __DIR__ . '/../erp-omd/includes/class-cron-manager.php';

final class CronBackupDumpScopeWpdbStub
{
    public string $prefix = 'wp_';

    public function get_col($query)
    {
        if ($query === 'SHOW TABLES') {
            return ['wp_posts', 'wp_users', 'wp_erp_omd_projects', 'wp_erp_omd_time_entries'];
        }
        return [];
    }

    public function get_row($query, $output_type = null)
    {
        if (strpos($query, 'SHOW CREATE TABLE `wp_erp_omd_projects`') !== false) {
            return ['wp_erp_omd_projects', 'CREATE TABLE `wp_erp_omd_projects` (`id` int(11) NOT NULL)'];
        }
        if (strpos($query, 'SHOW CREATE TABLE `wp_erp_omd_time_entries`') !== false) {
            return ['wp_erp_omd_time_entries', 'CREATE TABLE `wp_erp_omd_time_entries` (`id` int(11) NOT NULL)'];
        }

        return null;
    }

    public function get_results($query, $output_type = null)
    {
        if (strpos($query, 'SELECT * FROM `wp_erp_omd_projects`') !== false) {
            return [['id' => 1]];
        }
        if (strpos($query, 'SELECT * FROM `wp_erp_omd_time_entries`') !== false) {
            return [['id' => 10]];
        }

        return [];
    }
}

global $wpdb;
$wpdb = new CronBackupDumpScopeWpdbStub();

$reflection = new ReflectionClass('ERP_OMD_Cron_Manager');
$method = $reflection->getMethod('build_database_dump');
$method->setAccessible(true);
$dump = (string) $method->invoke(null);

if (strpos($dump, '`wp_erp_omd_projects`') === false || strpos($dump, '`wp_erp_omd_time_entries`') === false) {
    fwrite(STDERR, "ERP tables missing in dump output.\n");
    exit(1);
}

if (strpos($dump, '`wp_posts`') !== false || strpos($dump, '`wp_users`') !== false) {
    fwrite(STDERR, "Non-ERP tables leaked into dump output.\n");
    exit(1);
}

echo "Backup dump scope test passed.\n";
