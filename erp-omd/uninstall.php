<?php
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$delete_data = (bool) get_option('erp_omd_delete_data_on_uninstall', false);
if (! $delete_data) {
    delete_option('erp_omd_db_version');
    delete_option('erp_omd_delete_data_on_uninstall');
    return;
}

global $wpdb;

$tables = [
    $wpdb->prefix . 'erp_omd_salary_history',
    $wpdb->prefix . 'erp_omd_employee_roles',
    $wpdb->prefix . 'erp_omd_employees',
    $wpdb->prefix . 'erp_omd_roles',
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
}

delete_option('erp_omd_db_version');
delete_option('erp_omd_delete_data_on_uninstall');
