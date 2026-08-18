<?php

declare(strict_types=1);

function trailingslashit($path)
{
    return rtrim((string) $path, '/\\') . '/';
}

require_once __DIR__ . '/../erp-omd/includes/class-backup-manager.php';

$backup_dir = sys_get_temp_dir() . '/erp-omd-backup-retention-' . uniqid('', true);
if (! mkdir($backup_dir, 0700, true)) {
    throw new RuntimeException('Unable to create temporary backup directory.');
}

$files = [
    'erp-omd-db-20260814-010000.zip',
    'erp-omd-db-20260815-010000.zip',
    'erp-omd-db-20260816-010000.zip',
    'erp-omd-db-20260817-010000.zip',
];

foreach ($files as $file) {
    file_put_contents($backup_dir . '/' . $file, 'backup');
}
file_put_contents($backup_dir . '/unrelated.zip', 'keep');

$method = (new ReflectionClass(ERP_OMD_Backup_Manager::class))->getMethod('prune_old_backups');
$method->setAccessible(true);
$method->invoke(null, $backup_dir, ERP_OMD_Backup_Manager::BACKUP_RETENTION_COUNT);

$remaining = glob($backup_dir . '/erp-omd-db-*.zip') ?: [];
$remaining = array_map('basename', $remaining);
sort($remaining, SORT_STRING);

$expected = array_slice($files, -3);
if ($remaining !== $expected) {
    throw new RuntimeException('Expected the three newest backups, got: ' . implode(', ', $remaining));
}
if (! is_file($backup_dir . '/unrelated.zip')) {
    throw new RuntimeException('Retention must not remove unrelated ZIP files.');
}

foreach (glob($backup_dir . '/*') ?: [] as $file) {
    unlink($file);
}
rmdir($backup_dir);

echo "Assertions: 2\n";
echo "Backup retention test passed.\n";
