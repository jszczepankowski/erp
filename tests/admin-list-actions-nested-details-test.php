<?php

$adminJs = (string) file_get_contents(__DIR__ . '/../erp-omd/assets/js/admin.js');
if ($adminJs === '') {
    throw new RuntimeException('Unable to load admin.js');
}

$requiredSnippets = [
    "document.querySelectorAll('.erp-omd-list-actions').forEach((detailsNode) => {",
    "!otherNode.contains(detailsNode)",
    "!detailsNode.contains(otherNode)",
];

foreach ($requiredSnippets as $snippet) {
    if (strpos($adminJs, $snippet) === false) {
        throw new RuntimeException('Missing expected nested-details snippet: ' . $snippet);
    }
}

echo "Assertions: " . count($requiredSnippets) . "\n";
echo "Admin list actions nested details test passed.\n";
