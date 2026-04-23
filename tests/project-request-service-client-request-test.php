<?php

$service = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/services/class-project-request-service.php');
if ($service === '') {
    throw new RuntimeException('Unable to load class-project-request-service.php');
}

$requiredSnippets = [
    "user_can(\$requester_user, 'erp_omd_front_client')",
    '! $is_front_client_requester && ! $this->employees->find((int) $data[\'requester_employee_id\'])',
];

foreach ($requiredSnippets as $snippet) {
    if (strpos($service, $snippet) === false) {
        throw new RuntimeException('Missing expected project request service snippet: ' . $snippet);
    }
}

echo "Assertions: " . count($requiredSnippets) . "\n";
echo "Project request service client requester test passed.\n";
