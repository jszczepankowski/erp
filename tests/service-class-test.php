<?php

declare(strict_types=1);

$serviceFiles = [
    __DIR__ . '/../erp-omd/includes/services/class-reporting-service.php',
    __DIR__ . '/../erp-omd/includes/services/class-reporting-service-v2.php',
];

$assertions = 0;
foreach ($serviceFiles as $file) {
    $source = (string) file_get_contents($file);
    if ($source === '') {
        throw new RuntimeException('Unable to read service source: ' . $file);
    }

    preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(/', $source, $matches);
    $methods = array_map('strtolower', $matches[1] ?? []);
    $counts = array_count_values($methods);
    $duplicates = array_keys(array_filter($counts, static function ($count) {
        return (int) $count > 1;
    }));

    $assertions++;
    if ($duplicates !== []) {
        throw new RuntimeException(
            'Duplicate methods in ' . basename($file) . ': ' . implode(', ', $duplicates)
        );
    }
}

echo "Assertions: {$assertions}\n";
echo "Service class duplicate-method test passed.\n";
