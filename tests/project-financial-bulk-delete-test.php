<?php

declare(strict_types=1);

$runtimeSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$projectsTemplateSource = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/projects.php');

if ($runtimeSource === '' || $projectsTemplateSource === '') {
    throw new RuntimeException('Unable to read runtime/projects template source.');
}

$runtimeFragments = [
    "case 'bulk_delete_project_revenues':",
    "case 'bulk_delete_project_costs':",
    'function handle_project_revenues_bulk_delete(',
    'function handle_project_costs_bulk_delete(',
    "check_admin_referer('erp_omd_bulk_delete_project_revenues')",
    "check_admin_referer('erp_omd_bulk_delete_project_costs')",
];


$templateFragments = [
    'value="bulk_delete_project_revenues"',
    'value="bulk_delete_project_costs"',
    'name="project_revenue_ids[]"',
    'name="project_cost_ids[]"',
    'erp-omd-bulk-project-revenues-form',
    'erp-omd-bulk-project-costs-form',
    "esc_html_e('Usuń zaznaczone przychody', 'erp-omd')",
    "esc_html_e('Usuń zaznaczone koszty', 'erp-omd')",
];

$assertions = 0;
foreach ($runtimeFragments as $fragment) {
    $assertions++;
    if (strpos($runtimeSource, $fragment) === false) {
        throw new RuntimeException('Missing runtime fragment: ' . $fragment);
    }
}

foreach ($templateFragments as $fragment) {
    $assertions++;
    if (strpos($projectsTemplateSource, $fragment) === false) {
        throw new RuntimeException('Missing projects template fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "Project financial bulk delete test passed.\n";
