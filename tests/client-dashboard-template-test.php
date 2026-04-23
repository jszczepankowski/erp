<?php

$template = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/front/client-dashboard.php');
if ($template === '') {
    throw new RuntimeException('Unable to load client-dashboard.php template');
}

$requiredSnippets = [
    'name="project_scope"',
    'name="sort_by"',
    'name="sort_order"',
    'name="history_month"',
    "name=\"erp_omd_front_action\" value=\"create_project_note\"",
    "esc_html_e('Załączniki projektu', 'erp-omd')",
    "esc_html_e('Źródło', 'erp-omd')",
    "esc_html_e('Wyczyść filtr miesiąca', 'erp-omd')",
    'usort(',
];

foreach ($requiredSnippets as $snippet) {
    if (strpos($template, $snippet) === false) {
        throw new RuntimeException('Missing expected template snippet: ' . $snippet);
    }
}

echo "Assertions: " . count($requiredSnippets) . "\n";
echo "Client dashboard template test passed.\n";
