<?php

$adminRuntime = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
if ($adminRuntime === '') {
    throw new RuntimeException('Unable to load class-admin-runtime.php');
}

$template = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/project-requests.php');
if ($template === '') {
    throw new RuntimeException('Unable to load project-requests admin template');
}

$runtimeSnippets = [
    "sanitize_key(wp_unslash(\$_GET['tab'] ?? 'employee'))",
    "in_array(\$request_tab, ['employee', 'client'], true)",
    "return \$this->is_client_project_request(\$request_row);",
    "return ! \$this->is_client_project_request(\$request_row);",
    "private function is_client_project_request(\$request_row)",
    "in_array('erp_omd_client', \$requester_roles, true)",
    "get_user_meta(\$requester_user_id, 'erp_omd_client_id', true)",
];

foreach ($runtimeSnippets as $snippet) {
    if (strpos($adminRuntime, $snippet) === false) {
        throw new RuntimeException('Missing expected runtime snippet: ' . $snippet);
    }
}

$templateSnippets = [
    "esc_html_e('Wnioski pracowników', 'erp-omd')",
    "esc_html_e('Wnioski klientów', 'erp-omd')",
    'name="tab"',
    "esc_html__('Lista wniosków klientów', 'erp-omd')",
];

foreach ($templateSnippets as $snippet) {
    if (strpos($template, $snippet) === false) {
        throw new RuntimeException('Missing expected template snippet: ' . $snippet);
    }
}

echo "Assertions: " . (count($runtimeSnippets) + count($templateSnippets)) . "\n";
echo "Admin project requests tabs test passed.\n";
