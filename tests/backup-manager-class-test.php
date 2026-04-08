<?php

declare(strict_types=1);

$file = __DIR__ . '/../erp-omd/includes/class-backup-manager.php';
$source = (string) file_get_contents($file);

if ($source === '') {
    throw new RuntimeException('Unable to load class-backup-manager.php source.');
}

preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(/', $source, $matches);
$method_names = array_map('strtolower', $matches[1] ?? []);
$counts = array_count_values($method_names);
$duplicates = array_filter($counts, static function ($count) {
    return $count > 1;
});

if (! empty($duplicates)) {
    throw new RuntimeException('Duplicate backup-manager methods found: ' . implode(', ', array_keys($duplicates)));
}

if (($counts['run_backup_bundle'] ?? 0) !== 1) {
    throw new RuntimeException('run_backup_bundle() must be declared exactly once.');
}

if (($counts['restore_backup_bundle_from_zip'] ?? 0) !== 1) {
    throw new RuntimeException('restore_backup_bundle_from_zip() must be declared exactly once.');
}

echo "Assertions: 3\n";
echo "Backup manager duplicate-method test passed.\n";

