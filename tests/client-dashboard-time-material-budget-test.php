<?php

declare(strict_types=1);

$runtime = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-frontend-runtime.php');
if ($runtime === '') {
    throw new RuntimeException('Unable to load class-frontend-runtime.php');
}

$requiredSnippets = [
    "\$project_financials_for_display = \$this->project_financial_service->get_project_financials(wp_list_pluck(\$projects, 'id'));",
    "if ((string) (\$project_row['billing_type'] ?? '') === 'time_material')",
    "\$project_row['budget_current'] = (float) (\$project_financial['revenue'] ?? \$project_row['budget_current']);",
];

foreach ($requiredSnippets as $snippet) {
    if (strpos($runtime, $snippet) === false) {
        throw new RuntimeException('Missing expected runtime snippet: ' . $snippet);
    }
}

$template = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/front/client-dashboard.php');
if ($template === '') {
    throw new RuntimeException('Unable to load client-dashboard.php template');
}

$budgetHeaderPos = strpos($template, "__('Budżet aktualny', 'erp-omd')");
$budgetValuePos = strpos($template, "\$project_item['budget_current'] ?? \$project_item['budget'] ?? 0");
if ($budgetHeaderPos === false || $budgetValuePos === false || $budgetHeaderPos > $budgetValuePos) {
    throw new RuntimeException('Expected client project list to render budget_current under the Budżet aktualny column.');
}

echo 'Assertions: ' . (count($requiredSnippets) + 1) . "\n";
echo "Client dashboard time-material budget test passed.\n";
