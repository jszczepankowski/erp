<?php

declare(strict_types=1);

$file = __DIR__ . '/../erp-omd/includes/class-cron-manager.php';
$source = (string) file_get_contents($file);

if ($source === '') {
    throw new RuntimeException('Unable to load class-cron-manager.php source.');
}

preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(/', $source, $matches);
$method_names = array_map('strtolower', $matches[1] ?? []);
$counts = array_count_values($method_names);
$duplicates = array_filter($counts, static function ($count) {
    return $count > 1;
});

if (! empty($duplicates)) {
    throw new RuntimeException('Duplicate cron-manager methods found: ' . implode(', ', array_keys($duplicates)));
}

if (($counts['restore_backup_bundle_from_zip'] ?? 0) !== 1) {
    throw new RuntimeException('restore_backup_bundle_from_zip() must be declared exactly once in ERP_OMD_Cron_Manager.');
}

if (($counts['restore_from_backup_zip'] ?? 0) !== 0) {
    throw new RuntimeException('Legacy restore_from_backup_zip() should not be declared in ERP_OMD_Cron_Manager.');
}

if (($counts['import_sql_dump'] ?? 0) !== 1) {
    throw new RuntimeException('import_sql_dump() must be declared exactly once in ERP_OMD_Cron_Manager.');
}

echo "Assertions: 3\n";
echo "Cron manager duplicate-method test passed.\n";
