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
    "user_can(\$requester_user_id, 'erp_omd_front_client')",
    "return ! (\$requester_user_id > 0 && user_can(\$requester_user_id, 'erp_omd_front_client'));",
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
