<?php

declare(strict_types=1);

if (! defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

require_once __DIR__ . '/../erp-omd/includes/class-backup-manager.php';

final class BackupRestoreSqlScopeWpdbStub
{
    public string $prefix = 'wp_';
}

global $wpdb;
$wpdb = new BackupRestoreSqlScopeWpdbStub();

$reflection = new ReflectionClass('ERP_OMD_Backup_Manager');
$method = $reflection->getMethod('validate_sql_dump_scope');
$method->setAccessible(true);

$validDump = "-- ERP OMD database backup\nDROP TABLE IF EXISTS `wp_erp_omd_projects`;\nCREATE TABLE `wp_erp_omd_projects` (`id` int(11) NOT NULL);\nINSERT INTO `wp_erp_omd_projects` (`id`) VALUES ('1');\n";
$method->invoke(null, $validDump);

$invalidDumps = [
    "UPDATE `wp_users` SET user_pass = 'x';",
    "DROP TABLE IF EXISTS `wp_options`;",
    "INSERT INTO `wp_erp_omd_projects` (`id`) SELECT ID FROM `wp_users`;",
    "CREATE TABLE `wp_erp_omd_leak` AS SELECT * FROM `wp_users`;",
    "GRANT ALL PRIVILEGES ON *.* TO 'evil'@'%';",
];

foreach ($invalidDumps as $invalidDump) {
    $failed = false;
    try {
        $method->invoke(null, $invalidDump);
    } catch (ReflectionException $exception) {
        throw $exception;
    } catch (Throwable $exception) {
        $failed = true;
    }

    if (! $failed) {
        throw new RuntimeException('Invalid SQL dump passed scope validation: ' . $invalidDump);
    }
}

echo "Assertions: 6\n";
echo "Backup restore SQL scope test passed.\n";
