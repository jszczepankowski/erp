<?php

declare(strict_types=1);

$adminSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$projectTemplateSource = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/projects.php');
$estimateTemplateSource = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/estimates.php');
$aclSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/services/class-acl-service.php');
$adminJsSource = (string) file_get_contents(__DIR__ . '/../erp-omd/assets/js/admin.js');
$adminCssSource = (string) file_get_contents(__DIR__ . '/../erp-omd/assets/css/admin.css');

if ($adminSource === '' || $projectTemplateSource === '' || $estimateTemplateSource === '' || $aclSource === '' || $adminJsSource === '' || $adminCssSource === '') {
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
    [$adminJsSource, 'const initNestedAdminMenu = () =>', 'Admin JS should initialize nested create submenu behavior.'],
    [$adminJsSource, "{ parent: 'erp-omd-estimates', child: 'erp-omd-estimates-new' }", 'Admin JS should nest estimate create under estimates.'],
    [$adminJsSource, "{ parent: 'erp-omd-projects', child: 'erp-omd-projects-new' }", 'Admin JS should nest project create under projects.'],
    [$adminCssSource, '.erp-omd-nested-submenu', 'Admin CSS should style nested create submenus.'],
];

foreach ($expectedFragments as [$source, $fragment, $message]) {
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException($message . ' Missing fragment: ' . $fragment);
    }
}

$projectMonthPosition = strpos($projectTemplateSource, 'name="month" value="<?php echo esc_attr($project_filters');
$projectAddPosition = strpos($projectTemplateSource, "admin.php?page=erp-omd-projects-new");
if ($projectMonthPosition === false || $projectAddPosition === false || $projectAddPosition < $projectMonthPosition) {
    throw new RuntimeException('Project add button should be rendered after the month filter field.');
}

$estimateMonthPosition = strpos($estimateTemplateSource, 'name="month" value="<?php echo esc_attr($estimate_filters');
$estimateAddPosition = strpos($estimateTemplateSource, "admin.php?page=erp-omd-estimates-new");
if ($estimateMonthPosition === false || $estimateAddPosition === false || $estimateAddPosition < $estimateMonthPosition) {
    throw new RuntimeException('Estimate add button should be rendered after the month filter field.');
}

echo "Admin project/estimate create flow test passed.\n";
