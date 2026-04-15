<?php

declare(strict_types=1);

$source = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/services/class-ksef-import-service.php');
if ($source === '') {
    throw new RuntimeException('Unable to read KSeF import service source file.');
}

$requiredFragments = [
    'function list_moderation_queue(',
    'function moderate_queue_entry(',
    'function bulk_moderate_queue_entries(',
    'assign_supplier',
    'assign_project',
    'approve',
    'reject',
    'function match_supplier_for_cost_document(',
    'Wiele dopasowań dostawcy po NIP',
    'Brak dopasowania dostawcy po NIP',
];

$assertions = 0;
foreach ($requiredFragments as $fragment) {
    $assertions++;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException('Missing KSeF moderation service fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "KSeF moderation service test passed.\n";
