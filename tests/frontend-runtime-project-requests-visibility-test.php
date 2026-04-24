<?php

$runtime = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-frontend-runtime.php');
if ($runtime === '') {
    throw new RuntimeException('Unable to load class-frontend-runtime.php');
}

$snippets = [
    "private function load_visible_project_requests(\$current_employee_id, WP_User \$user)",
    "\$visible_client_ids = array_map('intval', wp_list_pluck(\$this->get_manager_available_clients((int) \$current_employee_id, false), 'id'));",
    "|| in_array((int) (\$request['client_id'] ?? 0), \$visible_client_ids, true);",
];

foreach ($snippets as $snippet) {
    if (strpos($runtime, $snippet) === false) {
        throw new RuntimeException('Missing expected runtime snippet: ' . $snippet);
    }
}

echo "Assertions: " . count($snippets) . "\n";
echo "Frontend runtime project request visibility test passed.\n";
