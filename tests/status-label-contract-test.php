<?php

declare(strict_types=1);

$restApiSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-rest-api.php');
$adminJsSource = (string) file_get_contents(__DIR__ . '/../erp-omd/assets/js/admin.js');
$reportsTemplateSource = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/reports.php');

if ($restApiSource === '' || $adminJsSource === '' || $reportsTemplateSource === '') {
    throw new RuntimeException('Unable to load one or more source files for status-label contract test.');
}

if (strpos($restApiSource, "'period_status_label' =>") === false) {
    throw new RuntimeException('Dashboard-v1 payload must expose period_status_label.');
}

if (strpos($restApiSource, "'to_status_label' =>") === false) {
    throw new RuntimeException('Dashboard-v1 status actions must expose to_status_label.');
}

if (strpos($restApiSource, 'private function format_period_status_label') === false) {
    throw new RuntimeException('REST API should provide format_period_status_label helper for status-label contract.');
}

if (strpos($adminJsSource, 'safePayload.period_status_label') === false) {
    throw new RuntimeException('Admin JS should prefer period_status_label from dashboard payload.');
}

if (strpos($adminJsSource, 'safeAction.to_status_label') === false) {
    throw new RuntimeException('Admin JS should prefer to_status_label from status_actions payload.');
}

if (strpos($reportsTemplateSource, "esc_html_e('DO ROZLICZENIA', 'erp-omd')") === false) {
    throw new RuntimeException('Reports template should render DO ROZLICZENIA label (without underscore).');
}

echo "Assertions: 6\n";
echo "Status-label contract test passed.\n";

