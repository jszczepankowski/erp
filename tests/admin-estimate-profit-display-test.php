<?php

declare(strict_types=1);

$adminSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$estimateTemplateSource = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/estimates.php');
$frontDashboardSource = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/front/client-dashboard.php');

if ($adminSource === '' || $estimateTemplateSource === '' || $frontDashboardSource === '') {
    throw new RuntimeException('Unable to load estimate profit display sources.');
}

$expectedFragments = [
    [$adminSource, "\$estimate_row['total_profit'] = round((float) \$estimate_row_totals['net'] - (float) \$estimate_row_totals['internal_cost'], 2);", 'Admin runtime should calculate estimate profit for the admin list.'],
    [$estimateTemplateSource, "esc_html_e('Zysk', 'erp-omd'); ?></strong><span><?php echo esc_html(number_format_i18n((float) \$estimate_totals['net'] - (float) \$estimate_totals['internal_cost'], 2));", 'Estimate details should show profit after internal cost in financial values.'],
    [$estimateTemplateSource, "esc_html_e('Zysk', 'erp-omd'); ?></th>", 'Admin estimate list should include a profit column header.'],
    [$estimateTemplateSource, "number_format_i18n((float) (\$estimate_row['total_profit'] ?? 0), 2)", 'Admin estimate list should render calculated profit.'],
];

foreach ($expectedFragments as [$source, $fragment, $message]) {
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException($message . ' Missing fragment: ' . $fragment);
    }
}

$internalCostPosition = strpos($estimateTemplateSource, "esc_html_e('Koszt wewnętrzny', 'erp-omd'); ?></strong><span><?php echo esc_html(number_format_i18n((float) \$estimate_totals['internal_cost'], 2));");
$profitPosition = strpos($estimateTemplateSource, "esc_html_e('Zysk', 'erp-omd'); ?></strong><span><?php echo esc_html(number_format_i18n((float) \$estimate_totals['net'] - (float) \$estimate_totals['internal_cost'], 2));");
if ($internalCostPosition === false || $profitPosition === false || $profitPosition < $internalCostPosition) {
    throw new RuntimeException('Estimate financial profit box should be rendered after internal cost.');
}

if (strpos($frontDashboardSource, "esc_html_e('Zysk', 'erp-omd')") !== false) {
    throw new RuntimeException('Client dashboard should not expose estimate profit.');
}

echo "Admin estimate profit display test passed.\n";
