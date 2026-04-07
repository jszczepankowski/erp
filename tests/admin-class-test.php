<?php

declare(strict_types=1);

$file = __DIR__ . '/../erp-omd/includes/class-admin.php';
$source = (string) file_get_contents($file);

if ($source === '') {
    throw new RuntimeException('Unable to load class-admin.php source.');
}

preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(/', $source, $matches);
$method_names = array_map('strtolower', $matches[1] ?? []);
$counts = array_count_values($method_names);
$duplicates = array_filter($counts, static function ($count) {
    return $count > 1;
});

if (! empty($duplicates)) {
    throw new RuntimeException('Duplicate admin methods found: ' . implode(', ', array_keys($duplicates)));
}

if (($counts['render_finances_page'] ?? 0) !== 0) {
    throw new RuntimeException('render_finances_page() should not be declared in ERP_OMD_Admin (finances page uses closure callback).');
}

if (($counts['build_profit_ranking'] ?? 0) !== 0) {
    throw new RuntimeException('build_profit_ranking() should not be declared in ERP_OMD_Admin (ranking uses local closure).');
}

if (($counts['handle_project_revenue_save'] ?? 0) !== 0 || ($counts['handle_project_revenue_delete'] ?? 0) !== 0) {
    throw new RuntimeException('Project revenue handlers should be inlined in handle_forms() and not declared as class methods.');
}

if (($counts['handle_manual_backup_action'] ?? 0) !== 0) {
    throw new RuntimeException('Manual backup handler should be inlined in handle_forms() and not declared as class method.');
}

if (($counts['handle_adjustments_audit_export'] ?? 0) !== 0) {
    throw new RuntimeException('Legacy handle_adjustments_audit_export() should not be declared (export action must be inlined in handle_forms()).');
}

if (($counts['handle_adjustments_audit_export_csv'] ?? 0) !== 0) {
    throw new RuntimeException('handle_adjustments_audit_export_csv() should not be declared (export action must be inlined in handle_forms()).');
}

echo "Assertions: 7\n";
echo "Admin class duplicate-method test passed.\n";
