<?php

$adminRuntime = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
if ($adminRuntime === '') {
    throw new RuntimeException('Unable to load class-admin-runtime.php');
}

$projectTemplate = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/projects.php');
if ($projectTemplate === '') {
    throw new RuntimeException('Unable to load admin projects template');
}

$projectNotesRepository = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/repositories/class-project-note-repository.php');
if ($projectNotesRepository === '') {
    throw new RuntimeException('Unable to load class-project-note-repository.php');
}

$requiredRuntimeSnippets = [
    "case 'delete_project_note':",
    'function handle_project_note_delete(',
    'erp_omd_delete_project_note',
    "['id' => \$project_id]",
    'is_object($client_front_user)',
];

foreach ($requiredRuntimeSnippets as $snippet) {
    if (strpos($adminRuntime, $snippet) === false) {
        throw new RuntimeException('Missing expected admin runtime snippet: ' . $snippet);
    }
}

$requiredTemplateSnippets = [
    "esc_html_e('Akcje', 'erp-omd')",
    'value="delete_project_note"',
    'name="note_id"',
    "esc_html_e('Usuń', 'erp-omd')",
];

foreach ($requiredTemplateSnippets as $snippet) {
    if (strpos($projectTemplate, $snippet) === false) {
        throw new RuntimeException('Missing expected admin projects template snippet: ' . $snippet);
    }
}

$requiredRepositorySnippets = [
    'function delete($id)',
    '$wpdb->delete(',
];

foreach ($requiredRepositorySnippets as $snippet) {
    if (strpos($projectNotesRepository, $snippet) === false) {
        throw new RuntimeException('Missing expected project notes repository snippet: ' . $snippet);
    }
}

echo "Assertions: " . (count($requiredRuntimeSnippets) + count($requiredTemplateSnippets) + count($requiredRepositorySnippets)) . "\n";
echo "Admin client follow-up test passed.\n";
