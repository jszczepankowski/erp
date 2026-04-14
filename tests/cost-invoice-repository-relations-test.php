<?php

declare(strict_types=1);

$source = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/repositories/class-cost-invoice-repository.php');
if ($source === '') {
    throw new RuntimeException('Unable to read cost invoice repository source.');
}

$required = [
    'function project_supplier_pairs(',
    'GROUP BY project_id, supplier_id',
    'COUNT(*) AS invoices_count',
    'SUM(gross_amount) AS gross_total',
];

$assertions = 0;
foreach ($required as $fragment) {
    $assertions++;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException('Missing repository relation fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "Cost invoice repository relations test passed.\n";
