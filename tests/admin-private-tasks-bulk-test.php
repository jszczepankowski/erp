<?php

declare(strict_types=1);

$runtime = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$template = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/private-tasks.php');

if ($runtime === '' || $template === '') {
    throw new RuntimeException('Unable to read private tasks runtime/template source.');
}

$runtimeFragments = [
    "case 'bulk_admin_private_tasks'",
    'function handle_admin_private_tasks_bulk_action(',
    'function normalize_admin_private_tasks_filter(',
    "\$tasks_filter = \$this->normalize_admin_private_tasks_filter(\$_POST['tasks_filter'] ?? 'all');",
    "if (\$action === 'delete')",
    'return ! in_array($row_id, $ids, true);',
];

$templateFragments = [
    'id="erp-omd-bulk-private-tasks-form"',
    'value="bulk_admin_private_tasks"',
    'option value="delete"',
    'form="erp-omd-bulk-private-tasks-form"',
    'input[name="task_ids[]"][form="erp-omd-bulk-private-tasks-form"]',
];

$assertions = 0;
foreach ($runtimeFragments as $fragment) {
    $assertions++;
    if (strpos($runtime, $fragment) === false) {
        throw new RuntimeException('Missing private task runtime fragment: ' . $fragment);
    }
}

foreach ($templateFragments as $fragment) {
    $assertions++;
    if (strpos($template, $fragment) === false) {
        throw new RuntimeException('Missing private task template fragment: ' . $fragment);
    }
}

$bulkFormClose = strpos($template, "</form>\n\n                <table class=\"widefat striped\">");
if ($bulkFormClose === false) {
    throw new RuntimeException('Bulk private tasks form should close before row-level action forms to avoid nested forms.');
}
$assertions++;

echo "Assertions: {$assertions}\n";
echo "Admin private tasks bulk test passed.\n";
