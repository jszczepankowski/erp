<?php

$dashboard = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/front/dashboard.php');
if ($dashboard === '') {
    throw new RuntimeException('Unable to load front dashboard template.');
}

$runtime = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-frontend-runtime.php');
if ($runtime === '') {
    throw new RuntimeException('Unable to load frontend runtime.');
}

$templateSnippets = [
    "esc_html_e('Podgląd szczegółów wniosku', 'erp-omd')",
    '<summary class="erp-omd-front-button erp-omd-front-button-ghost erp-omd-front-button-small">',
    '<div class="erp-omd-front-detail-grid">',
    "esc_html_e('Typ rozliczenia', 'erp-omd')",
    "esc_html_e('Budżet', 'erp-omd')",
    "name=\"request_preview_ack\" value=\"1\" required",
    "esc_html_e('Potwierdzam, że sprawdzono szczegóły wniosku.', 'erp-omd')",
];

$runtimeSnippets = [
    "\$action === 'approve_project_request' && empty(\$_POST['request_preview_ack'])",
    "Przed akceptacją zapoznaj się ze szczegółami i potwierdź podgląd wniosku.",
];

foreach ($templateSnippets as $snippet) {
    if (strpos($dashboard, $snippet) === false) {
        throw new RuntimeException('Missing expected dashboard snippet: ' . $snippet);
    }
}

foreach ($runtimeSnippets as $snippet) {
    if (strpos($runtime, $snippet) === false) {
        throw new RuntimeException('Missing expected runtime snippet: ' . $snippet);
    }
}

echo "Assertions: " . (count($templateSnippets) + count($runtimeSnippets)) . "\n";
echo "Front dashboard request preview test passed.\n";
