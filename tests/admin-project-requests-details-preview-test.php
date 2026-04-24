<?php

$template = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/project-requests.php');
if ($template === '') {
    throw new RuntimeException('Unable to load admin project requests template.');
}

$requiredSnippets = [
    "esc_html_e('Podgląd szczegółów', 'erp-omd')",
    "esc_html_e('Nazwa projektu:', 'erp-omd')",
    "esc_html_e('Typ rozliczenia:', 'erp-omd')",
    "esc_html_e('Budżet:', 'erp-omd')",
    "esc_html_e('Data rozpoczęcia:', 'erp-omd')",
    "esc_html_e('Data zakończenia:', 'erp-omd')",
    "esc_html_e('Brief:', 'erp-omd')",
];

foreach ($requiredSnippets as $snippet) {
    if (strpos($template, $snippet) === false) {
        throw new RuntimeException('Missing expected details-preview snippet: ' . $snippet);
    }
}

echo "Assertions: " . count($requiredSnippets) . "\n";
echo "Admin project requests details preview test passed.\n";
