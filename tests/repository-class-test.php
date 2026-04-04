<?php

declare(strict_types=1);

$repositoryFiles = [
    __DIR__ . '/../erp-omd/includes/repositories/class-estimate-repository.php',
    __DIR__ . '/../erp-omd/includes/repositories/class-client-repository.php',
    __DIR__ . '/../erp-omd/includes/repositories/class-project-repository.php',
    __DIR__ . '/../erp-omd/includes/repositories/class-time-entry-repository.php',
    __DIR__ . '/../erp-omd/includes/repositories/class-project-request-repository.php',
    __DIR__ . '/../erp-omd/includes/repositories/class-project-cost-repository.php',
    __DIR__ . '/../erp-omd/includes/repositories/class-salary-history-repository.php',
];

$assertions = 0;
foreach ($repositoryFiles as $file) {
    $source = (string) file_get_contents($file);
    if ($source === '') {
        throw new RuntimeException('Unable to read repository source: ' . $file);
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
echo "Repository class duplicate-method test passed.\n";
