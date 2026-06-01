<?php

declare(strict_types=1);

$installerSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-installer.php');
if ($installerSource === '') {
    throw new RuntimeException('Unable to load class-installer.php source.');
}

preg_match_all('/CREATE TABLE \{\$([a-zA-Z0-9_]+)\}/', $installerSource, $matches);
$tableVariables = $matches[1] ?? [];
$counts = array_count_values($tableVariables);
$duplicates = array_keys(array_filter($counts, static function ($count) {
    return (int) $count > 1;
}));

if ($duplicates !== []) {
    throw new RuntimeException('Duplicate installer CREATE TABLE targets found: ' . implode(', ', $duplicates));
}

if (($counts['adjustment_audit_table'] ?? 0) !== 1) {
    throw new RuntimeException('Expected adjustment audit table to be declared exactly once.');
}

echo 'Assertions: ' . (count($tableVariables) + 1) . "\n";
echo "Installer schema duplicate test passed.\n";
