<?php

declare(strict_types=1);

$runtimeSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$projectsTemplateSource = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/projects.php');
$projectCostRepositorySource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/repositories/class-project-cost-repository.php');
$installerSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-installer.php');

if ($runtimeSource === '' || $projectsTemplateSource === '' || $projectCostRepositorySource === '' || $installerSource === '') {
    throw new RuntimeException('Unable to read runtime/projects template source.');
}

$runtimeFragments = [
    "case 'attach_cost_invoice_to_project'",
    'function handle_attach_cost_invoice_to_project(',
    'erp_omd_attach_cost_invoice_to_project',
    'Projekt nie istnieje.',
    'is_project_cost_locked_by_status',
    'Faktura kosztowa została dodana do kosztów projektu (netto).',
    'project_cost_invoice_rows',
    "case 'map_project_cost_to_invoice'",
    'function handle_map_project_cost_to_invoice(',
    'erp_omd_map_project_cost_to_invoice',
    'Koszt projektu został połączony z fakturą kosztową.',
    'cost_invoice_id',
];

$templateFragments = [
    'attach_cost_invoice_to_project',
    'attach-cost-invoice-id',
    'Dodaj koszt z faktury kosztowej (netto)',
    'Podłącz fakturę kosztową jako koszt netto',
    'data-erp-omd-open-cost-map-modal',
    'value="map_project_cost_to_invoice"',
    "esc_html_e('Połącz koszt projektu z fakturą kosztową', 'erp-omd')",
    "esc_html_e('Połącz', 'erp-omd')",
];

$repositoryFragments = [
    'cost_invoice_id',
    "'cost_invoice_id' => ! empty(\$data['cost_invoice_id']) ? (int) \$data['cost_invoice_id'] : null",
];

$installerFragments = [
    'cost_invoice_id BIGINT UNSIGNED NULL',
    'fk_erp_omd_project_costs_cost_invoice',
    "define('ERP_OMD_DB_VERSION', '6.6.3')",
];

$pluginSource = (string) file_get_contents(__DIR__ . '/../erp-omd/erp-omd.php');

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

foreach ($repositoryFragments as $fragment) {
    $assertions++;
    if (strpos($projectCostRepositorySource, $fragment) === false) {
        throw new RuntimeException('Missing project cost repository fragment: ' . $fragment);
    }
}

foreach ($installerFragments as $fragment) {
    $assertions++;
    $source = strpos($fragment, 'ERP_OMD_DB_VERSION') !== false ? $pluginSource : $installerSource;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException('Missing installer/plugin fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "Project cost invoice attach test passed.\n";
