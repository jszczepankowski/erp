<?php

$root = dirname(__DIR__);
$template = file_get_contents($root . '/erp-omd/templates/admin/projects.php');
$admin_js = file_get_contents($root . '/erp-omd/assets/js/admin.js');
$admin_runtime = file_get_contents($root . '/erp-omd/includes/class-admin-runtime.php');
$project_service = file_get_contents($root . '/erp-omd/includes/services/class-client-project-service.php');
$ajax_handler_start = strpos($admin_runtime, 'public function handle_inline_project_update_ajax()');
$ajax_handler_end = strpos($admin_runtime, 'private function handle_project_duplicate()', $ajax_handler_start ?: 0);
$ajax_handler = $ajax_handler_start !== false && $ajax_handler_end !== false
    ? substr($admin_runtime, $ajax_handler_start, $ajax_handler_end - $ajax_handler_start)
    : '';

$assertions = [
    [
        $template,
        'name="status" form="<?php echo esc_attr($inline_project_form_id); ?>" data-erp-omd-auto-save="change"',
        'Project status select should explicitly opt in to saving on change.',
    ],
    [
        $admin_js,
        "element.dataset.erpOmdAutoSave === 'change'",
        'Admin JavaScript should submit explicitly marked fields on change.',
    ],
    [
        $admin_js,
        "payload.set('action', 'erp_omd_inline_project_update')",
        'Project inline updates should be sent through the AJAX endpoint.',
    ],
    [
        $admin_js,
        'ajaxSubmitQueued = true;',
        'A status change made during an active request should be queued instead of triggering a form navigation.',
    ],
    [
        $admin_runtime,
        "add_action('wp_ajax_erp_omd_inline_project_update', [\$this, 'handle_inline_project_update_ajax']);",
        'The project inline AJAX endpoint should be registered.',
    ],
    [
        $ajax_handler,
        'validate_project($payload, $existing, false)',
        'AJAX list updates should allow every valid status, including do_faktury.',
    ],
    [
        $project_service,
        'if ($validate_status_transition)',
        'Project validation should support an explicit administrative status override.',
    ],
];

foreach ($assertions as [$haystack, $needle, $message]) {
    if (strpos((string) $haystack, $needle) === false) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

echo "Admin project status auto-save regression checks passed.\n";
