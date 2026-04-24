<?php

$template = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/project-requests.php');
if ($template === '') {
    throw new RuntimeException('Unable to load admin project requests template.');
}

$runtime = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
if ($runtime === '') {
    throw new RuntimeException('Unable to load admin runtime.');
}

$templateSnippets = [
    "name=\"erp_omd_action\" value=\"bulk_project_requests\"",
    "name=\"bulk_action\"",
    "esc_html_e('Masowe akcje', 'erp-omd')",
    "option value=\"approve\"",
    "option value=\"reject\"",
    "option value=\"delete\"",
    "name=\"request_ids[]\"",
    "id=\"erp-omd-request-check-all\"",
];

$runtimeSnippets = [
    "case 'bulk_project_requests': \$this->handle_project_requests_bulk_action(); break;",
    'private function handle_project_requests_bulk_action()',
    "check_admin_referer('erp_omd_bulk_project_requests')",
];

foreach ($templateSnippets as $snippet) {
    if (strpos($template, $snippet) === false) {
        throw new RuntimeException('Missing expected bulk-action template snippet: ' . $snippet);
    }
}

foreach ($runtimeSnippets as $snippet) {
    if (strpos($runtime, $snippet) === false) {
        throw new RuntimeException('Missing expected bulk-action runtime snippet: ' . $snippet);
    }
}

echo "Assertions: " . (count($templateSnippets) + count($runtimeSnippets)) . "\n";
echo "Admin project requests bulk actions test passed.\n";
