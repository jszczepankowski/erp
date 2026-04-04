<?php

declare(strict_types=1);

require_once __DIR__ . '/../erp-omd/includes/class-cron-manager.php';

$reflection = new ReflectionClass('ERP_OMD_Cron_Manager');
$method = $reflection->getMethod('filter_erp_tables');
$method->setAccessible(true);

$tables = [
    'wp_posts',
    'wp_options',
    'wp_erp_omd_projects',
    'wp_erp_omd_time_entries',
    'wp_erp_omd_salary_history',
    'wp_users',
    'custom_table',
];

$filtered = $method->invoke(null, $tables, 'wp_');

$expected = [
    'wp_erp_omd_projects',
    'wp_erp_omd_time_entries',
    'wp_erp_omd_salary_history',
];

if ($filtered !== $expected) {
    fwrite(STDERR, "Unexpected filtered table list.\n");
    fwrite(STDERR, 'Expected: ' . json_encode($expected) . "\n");
    fwrite(STDERR, 'Actual:   ' . json_encode($filtered) . "\n");
    exit(1);
}

echo "Backup table filter test passed.\n";

