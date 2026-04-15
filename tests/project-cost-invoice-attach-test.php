<?php

declare(strict_types=1);

$runtimeSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$projectsTemplateSource = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/projects.php');

if ($runtimeSource === '' || $projectsTemplateSource === '') {
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
];

$templateFragments = [
    'attach_cost_invoice_to_project',
    'attach-cost-invoice-id',
    'Dodaj koszt z faktury kosztowej (netto)',
    'Podłącz fakturę kosztową jako koszt netto',
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
echo "Project cost invoice attach test passed.\n";
