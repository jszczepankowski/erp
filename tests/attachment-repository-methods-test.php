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

$requiredLogicSnippets = [
    'label LIKE %s',
    'esc_like($base_label)',
];

foreach ($requiredLogicSnippets as $logicSnippet) {
    if (strpos($repository, $logicSnippet) === false) {
        throw new RuntimeException('Missing repository logic snippet: ' . $logicSnippet);
    }
}

echo "Assertions: " . (count($requiredMethods) + count($requiredLogicSnippets)) . "\n";
echo "Attachment repository methods test passed.\n";
