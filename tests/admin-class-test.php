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

if (($counts['render_finances_page'] ?? 0) !== 1) {
    throw new RuntimeException('Expected exactly one render_finances_page() method in ERP_OMD_Admin.');
}

if (($counts['build_profit_ranking'] ?? 0) !== 1) {
    throw new RuntimeException('Expected exactly one build_profit_ranking() method in ERP_OMD_Admin.');
}

echo "Assertions: 3\n";
echo "Admin class duplicate-method test passed.\n";
