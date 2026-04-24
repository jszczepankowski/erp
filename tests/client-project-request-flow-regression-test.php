<?php

$repository = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/repositories/class-project-request-repository.php');
if ($repository === '') {
    throw new RuntimeException('Unable to load class-project-request-repository.php');
}

$frontendRuntime = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-frontend-runtime.php');
if ($frontendRuntime === '') {
    throw new RuntimeException('Unable to load class-frontend-runtime.php');
}

$installer = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-installer.php');
if ($installer === '') {
    throw new RuntimeException('Unable to load class-installer.php');
}

$repositorySnippets = [
    "\$requester_employee_id = (int) (\$data['requester_employee_id'] ?? 0);",
    "'requester_employee_id' => \$requester_employee_id > 0 ? \$requester_employee_id : null,",
];

$frontendSnippets = [
    "\$request_id = (int) \$this->project_requests->create(\$payload);",
    "if (\$request_id <= 0) {",
    "Nie udało się zapisać wniosku projektowego. Zweryfikuj mapowanie konta klienta i spróbuj ponownie.",
];

$installerSnippets = [
    'requester_employee_id BIGINT UNSIGNED NULL,',
    'self::maybe_allow_nullable_project_request_requester_employee_id();',
    'private static function maybe_allow_nullable_project_request_requester_employee_id()',
    "ALTER TABLE {\$project_requests_table} MODIFY requester_employee_id BIGINT UNSIGNED NULL",
];

foreach ($repositorySnippets as $snippet) {
    if (strpos($repository, $snippet) === false) {
        throw new RuntimeException('Missing expected project-request-repository snippet: ' . $snippet);
    }
}

foreach ($frontendSnippets as $snippet) {
    if (strpos($frontendRuntime, $snippet) === false) {
        throw new RuntimeException('Missing expected frontend runtime snippet: ' . $snippet);
    }
}

foreach ($installerSnippets as $snippet) {
    if (strpos($installer, $snippet) === false) {
        throw new RuntimeException('Missing expected installer snippet: ' . $snippet);
    }
}

$assertions = count($repositorySnippets) + count($frontendSnippets) + count($installerSnippets);
echo "Assertions: {$assertions}\n";
echo "Client project request flow regression test passed.\n";
