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
    $wpdb->prefix . 'erp_omd_project_financials',
    $wpdb->prefix . 'erp_omd_project_costs',
    $wpdb->prefix . 'erp_omd_time_entries',
    $wpdb->prefix . 'erp_omd_project_rates',
    $wpdb->prefix . 'erp_omd_project_notes',
    $wpdb->prefix . 'erp_omd_projects',
    $wpdb->prefix . 'erp_omd_estimate_items',
    $wpdb->prefix . 'erp_omd_estimates',
    $wpdb->prefix . 'erp_omd_client_rates',
    $wpdb->prefix . 'erp_omd_clients',
    $wpdb->prefix . 'erp_omd_salary_history',
    $wpdb->prefix . 'erp_omd_employee_roles',
    $wpdb->prefix . 'erp_omd_employees',
    $wpdb->prefix . 'erp_omd_roles',
];

$wpdb->query('SET FOREIGN_KEY_CHECKS = 0');

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
}

$wpdb->query('SET FOREIGN_KEY_CHECKS = 1');

delete_option('erp_omd_db_version');
delete_option('erp_omd_delete_data_on_uninstall');
