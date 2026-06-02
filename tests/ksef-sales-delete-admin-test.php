<?php

declare(strict_types=1);

$admin = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$service = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/services/class-ksef-import-service.php');
$template = (string) file_get_contents(__DIR__ . '/../erp-omd/templates/admin/cost-invoices.php');

if ($admin === '' || $service === '' || $template === '') {
    throw new RuntimeException('Unable to load one of files for KSeF sales delete test.');
}

$assertions = 0;
$fragments = [
    [$service, 'function delete_sales_document(', 'KSeF service should expose sales invoice delete method.'],
    [$service, '$this->save_sales_inbox($rows)', 'KSeF service should persist sales inbox after deleting row.'],
    [$service, '$this->append_sales_audit((int) $sales_id', 'KSeF service should audit sales invoice deletion.'],
    [$service, "'deleted' => 1", 'KSeF service should mark deletion in audit payload.'],
    [$admin, "case 'delete_ksef_sales_invoice'", 'Admin runtime should dispatch KSeF sales delete action.'],
    [$admin, 'function handle_delete_ksef_sales_invoice_action(', 'Admin runtime should expose KSeF sales delete handler.'],
    [$admin, "check_admin_referer('erp_omd_delete_ksef_sales_invoice')", 'KSeF sales delete handler should verify nonce.'],
    [$admin, "require_capability('erp_omd_manage_projects')", 'KSeF sales delete handler should require project management capability.'],
    [$admin, 'delete_sales_document($sales_id', 'KSeF sales delete handler should call service delete method.'],
    [$admin, 'rebuild_for_project($project_id)', 'KSeF sales delete handler should rebuild financials when deleted invoice was attached.'],
    [$admin, "'message' => 'ksef_sales_deleted'", 'KSeF sales delete handler should redirect with success message.'],
    [$template, "'ksef_sales_deleted'", 'KSeF sales template should define success message.'],
    [$template, "wp_nonce_field('erp_omd_delete_ksef_sales_invoice')", 'KSeF sales template should render delete nonce.'],
    [$template, 'name="erp_omd_action" value="delete_ksef_sales_invoice"', 'KSeF sales template should post delete action.'],
    [$template, 'name="sales_id" value="<?php echo esc_attr((string) ((int) ($sales_row[\'id\'] ?? 0))); ?>"', 'KSeF sales delete form should include sales id.'],
    [$template, "esc_html_e('Usuń', 'erp-omd')", 'KSeF sales template should render delete button.'],
    [$template, 'button-link-delete', 'KSeF sales template should use destructive button styling.'],
];

foreach ($fragments as [$source, $fragment, $message]) {
    $assertions++;
    if (strpos($source, $fragment) === false) {
        throw new RuntimeException($message . ' Missing fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "KSeF sales delete admin test passed.\n";
