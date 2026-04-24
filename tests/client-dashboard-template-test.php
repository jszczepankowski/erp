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
    "value=\"create_project_request\"",
    "esc_html_e('Wyślij wniosek projektowy', 'erp-omd')",
    "['time_material', 'fixed_price', 'mixed']",
    'name="budget"',
    "esc_html_e('Budżet projektu (wymagany dla Ryczałtu)', 'erp-omd')",
    'name="end_date"',
    'name="deadline"',
    "esc_html_e('Brief / opis projektu', 'erp-omd')",
    "name=\"erp_omd_front_action\" value=\"create_project_note\"",
    'enctype="multipart/form-data"',
    'name="attachment_label"',
    'name="attachment_file"',
    "esc_html_e('Załączniki projektu', 'erp-omd')",
    "esc_html_e('Źródło', 'erp-omd')",
    "esc_html_e('Wersja', 'erp-omd')",
    "esc_html_e('Typ', 'erp-omd')",
    "esc_html_e('Rozmiar', 'erp-omd')",
    "esc_html_e('Akcje', 'erp-omd')",
    "value=\"delete_project_attachment\"",
    "esc_html_e('Wyczyść filtr miesiąca', 'erp-omd')",
    "esc_html_e('Statusy', 'erp-omd')",
    'usort(',
    '$attachment_version_totals',
    "['source_key'] = 'project'",
];

foreach ($requiredSnippets as $snippet) {
    if (strpos($template, $snippet) === false) {
        throw new RuntimeException('Missing expected template snippet: ' . $snippet);
    }
}

echo "Assertions: " . count($requiredSnippets) . "\n";
echo "Client dashboard template test passed.\n";
