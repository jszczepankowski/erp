<?php

declare(strict_types=1);

$file = __DIR__ . '/../erp-omd/includes/class-frontend.php';
$source = (string) file_get_contents($file);

if ($source === '') {
    throw new RuntimeException('Unable to load class-frontend.php source.');
}

preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(/', $source, $matches);
$method_names = array_map('strtolower', $matches[1] ?? []);
$counts = array_count_values($method_names);
$duplicates = array_filter($counts, static function ($count) {
    return $count > 1;
});

if (! empty($duplicates)) {
    throw new RuntimeException('Duplicate frontend methods found: ' . implode(', ', array_keys($duplicates)));
}

echo "Assertions: 1\n";
echo "Frontend class duplicate-method test passed.\n";
