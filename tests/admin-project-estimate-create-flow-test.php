<?php

declare(strict_types=1);

$adminSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$projectTemplateSource = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/projects.php');
$estimateTemplateSource = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/estimates.php');
$aclSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/services/class-acl-service.php');

if ($adminSource === '' || $projectTemplateSource === '' || $estimateTemplateSource === '' || $aclSource === '') {
    throw new RuntimeException('Unable to load admin project/estimate create flow sources.');
}

$expectedFragments = [
    [$adminSource, "'erp-omd-estimates-new'", 'Admin runtime should register and authorize the estimate create screen.'],
    [$adminSource, "'erp-omd-projects-new'", 'Admin runtime should register and authorize the project create screen.'],
    [$adminSource, 'function render_estimate_create()', 'Admin runtime should expose a dedicated estimate create callback.'],
    [$adminSource, 'function render_project_create()', 'Admin runtime should expose a dedicated project create callback.'],
    [$adminSource, "\$show_estimate_list = ! \$show_estimate_editor && ! \$show_estimate_details;", 'Estimate list should be hidden on create/detail screens.'],
    [$adminSource, "\$show_project_list = ! \$show_project_editor;", 'Project list should be hidden on create/edit screens.'],
    [$projectTemplateSource, "admin.php?page=erp-omd-projects-new", 'Project list should link to the dedicated project create screen.'],
    [$projectTemplateSource, 'Wróć do listy projektów', 'Project create/edit screen should link back to the list.'],
    [$estimateTemplateSource, "admin.php?page=erp-omd-estimates-new", 'Estimate list should link to the dedicated estimate create screen.'],
    [$estimateTemplateSource, 'Wróć do listy kosztorysów', 'Estimate create/edit screen should link back to the list.'],
    [$aclSource, "'erp-omd-estimates-new'", 'ACL service should allow menu visibility overrides for estimate create screen.'],
    [$aclSource, "'erp-omd-projects-new'", 'ACL service should allow menu visibility overrides for project create screen.'],
];

foreach ($expectedFragments as [$source, $fragment, $message]) {
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException($message . ' Missing fragment: ' . $fragment);
    }
}

echo "Admin project/estimate create flow test passed.\n";
