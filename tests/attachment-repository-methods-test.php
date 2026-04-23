<?php

$repository = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/repositories/class-attachment-repository.php');
if ($repository === '') {
    throw new RuntimeException('Unable to load class-attachment-repository.php');
}

$requiredMethods = [
    'function for_entity(',
    'function find(',
    'function create(',
    'function delete(',
    'function count_links_for_attachment(',
    'function count_for_entity_label(',
];

foreach ($requiredMethods as $methodSnippet) {
    if (strpos($repository, $methodSnippet) === false) {
        throw new RuntimeException('Missing repository method: ' . $methodSnippet);
    }
}

echo "Assertions: " . count($requiredMethods) . "\n";
echo "Attachment repository methods test passed.\n";
