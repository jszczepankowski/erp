<?php
$supplier_form = is_array($selected_supplier ?? null) ? $selected_supplier : [];
$invoice_form = is_array($selected_invoice ?? null) ? $selected_invoice : [];
$supplier_name_by_id = [];
$project_name_by_id = [];
foreach ((array) $suppliers as $supplier_row) {
    $supplier_name_by_id[(int) ($supplier_row['id'] ?? 0)] = (string) ($supplier_row['name'] ?? '');
}
foreach ((array) $projects as $project_row) {
    $project_name_by_id[(int) ($project_row['id'] ?? 0)] = (string) ($project_row['name'] ?? '');
}
?>
<div class="wrap erp-omd-admin erp-omd-cost-invoices-admin">
    <h1><?php esc_html_e('Dostawcy i faktury kosztowe', 'erp-omd'); ?></h1>

    <?php if (! empty($_GET['message'])) : ?>
        <div class="notice notice-success"><p><?php echo esc_html((string) wp_unslash($_GET['message'])); ?></p></div>
    <?php endif; ?>
    <?php if (! empty($_GET['error'])) : ?>
        <div class="notice notice-error"><p><?php echo esc_html(rawurldecode((string) wp_unslash($_GET['error']))); ?></p></div>
    <?php endif; ?>

    <div class="postbox" style="padding:16px;margin-top:16px;">
        <h2><?php echo ! empty($supplier_form) ? esc_html__('Edytuj dostawcę', 'erp-omd') : esc_html__('Nowy dostawca', 'erp-omd'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('erp_omd_save_supplier'); ?>
            <input type="hidden" name="erp_omd_action" value="save_supplier" />
            <input type="hidden" name="supplier_id" value="<?php echo esc_attr((string) ((int) ($supplier_form['id'] ?? 0))); ?>" />
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="supplier_name"><?php esc_html_e('Nazwa', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="text" id="supplier_name" name="supplier_name" value="<?php echo esc_attr((string) ($supplier_form['name'] ?? '')); ?>" required /></td>
                </tr>
                <tr>
                    <th><label for="supplier_contact_person_name"><?php esc_html_e('Opiekun', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="text" id="supplier_contact_person_name" name="supplier_contact_person_name" value="<?php echo esc_attr((string) ($supplier_form['contact_person_name'] ?? '')); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="supplier_contact_person_email"><?php esc_html_e('Email opiekuna', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="email" id="supplier_contact_person_email" name="supplier_contact_person_email" value="<?php echo esc_attr((string) ($supplier_form['contact_person_email'] ?? '')); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="supplier_contact_person_phone"><?php esc_html_e('Telefon opiekuna', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="text" id="supplier_contact_person_phone" name="supplier_contact_person_phone" value="<?php echo esc_attr((string) ($supplier_form['contact_person_phone'] ?? '')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(! empty($supplier_form) ? __('Zaktualizuj dostawcę', 'erp-omd') : __('Zapisz dostawcę', 'erp-omd')); ?>
        </form>
    </div>

    <h2 style="margin-top:24px;"><?php esc_html_e('Dostawcy', 'erp-omd'); ?></h2>
    <table class="widefat striped">
        <thead><tr><th>ID</th><th><?php esc_html_e('Nazwa', 'erp-omd'); ?></th><th><?php esc_html_e('Opiekun', 'erp-omd'); ?></th><th><?php esc_html_e('Email opiekuna', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje', 'erp-omd'); ?></th></tr></thead>
        <tbody>
            <?php if ($suppliers === []) : ?>
                <tr><td colspan="5"><?php esc_html_e('Brak dostawców.', 'erp-omd'); ?></td></tr>
            <?php endif; ?>
            <?php foreach ($suppliers as $supplier) : ?>
                <tr>
                    <td><?php echo esc_html((string) ((int) ($supplier['id'] ?? 0))); ?></td>
                    <td><?php echo esc_html((string) ($supplier['name'] ?? '')); ?></td>
                    <td><?php echo esc_html((string) ($supplier['contact_person_name'] ?? '')); ?></td>
                    <td><?php echo esc_html((string) ($supplier['contact_person_email'] ?? '')); ?></td>
                    <td><a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-cost-invoices', 'supplier_id' => (int) ($supplier['id'] ?? 0)], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="postbox" style="padding:16px;margin-top:16px;">
        <h2><?php echo ! empty($invoice_form) ? esc_html__('Edytuj fakturę kosztową', 'erp-omd') : esc_html__('Nowa faktura kosztowa', 'erp-omd'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('erp_omd_save_cost_invoice'); ?>
            <input type="hidden" name="erp_omd_action" value="save_cost_invoice" />
            <input type="hidden" name="cost_invoice_id" value="<?php echo esc_attr((string) ((int) ($invoice_form['id'] ?? 0))); ?>" />
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="cost_invoice_supplier_id"><?php esc_html_e('Dostawca', 'erp-omd'); ?></label></th>
                    <td>
                        <select id="cost_invoice_supplier_id" name="cost_invoice_supplier_id" required>
                            <option value=""><?php esc_html_e('Wybierz dostawcę', 'erp-omd'); ?></option>
                            <?php foreach ($suppliers as $supplier) : ?>
                                <?php $supplier_id = (int) ($supplier['id'] ?? 0); ?>
                                <option value="<?php echo esc_attr((string) $supplier_id); ?>" <?php selected((int) ($invoice_form['supplier_id'] ?? 0), $supplier_id); ?>><?php echo esc_html((string) ($supplier['name'] ?? '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="cost_invoice_project_id"><?php esc_html_e('Projekt', 'erp-omd'); ?></label></th>
                    <td>
                        <select id="cost_invoice_project_id" name="cost_invoice_project_id" required>
                            <option value=""><?php esc_html_e('Wybierz projekt', 'erp-omd'); ?></option>
                            <?php foreach ($projects as $project) : ?>
                                <?php $project_id = (int) ($project['id'] ?? 0); ?>
                                <option value="<?php echo esc_attr((string) $project_id); ?>" <?php selected((int) ($invoice_form['project_id'] ?? 0), $project_id); ?>><?php echo esc_html((string) ($project['name'] ?? '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="cost_invoice_number"><?php esc_html_e('Numer faktury', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="text" id="cost_invoice_number" name="cost_invoice_number" value="<?php echo esc_attr((string) ($invoice_form['invoice_number'] ?? '')); ?>" required /></td>
                </tr>
                <tr>
                    <th><label for="cost_invoice_issue_date"><?php esc_html_e('Data wystawienia', 'erp-omd'); ?></label></th>
                    <td><input type="date" id="cost_invoice_issue_date" name="cost_invoice_issue_date" value="<?php echo esc_attr((string) ($invoice_form['issue_date'] ?? '')); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="cost_invoice_status"><?php esc_html_e('Status', 'erp-omd'); ?></label></th>
                    <td>
                        <select id="cost_invoice_status" name="cost_invoice_status" required>
                            <?php foreach (['zaimportowana', 'weryfikacja', 'zatwierdzona', 'przypisana'] as $status) : ?>
                                <option value="<?php echo esc_attr($status); ?>" <?php selected((string) ($invoice_form['status'] ?? 'zaimportowana'), $status); ?>><?php echo esc_html($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="cost_invoice_net_amount"><?php esc_html_e('Netto', 'erp-omd'); ?></label></th>
                    <td><input type="number" step="0.01" min="0" id="cost_invoice_net_amount" name="cost_invoice_net_amount" value="<?php echo esc_attr((string) ((float) ($invoice_form['net_amount'] ?? 0))); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="cost_invoice_vat_amount"><?php esc_html_e('VAT', 'erp-omd'); ?></label></th>
                    <td><input type="number" step="0.01" min="0" id="cost_invoice_vat_amount" name="cost_invoice_vat_amount" value="<?php echo esc_attr((string) ((float) ($invoice_form['vat_amount'] ?? 0))); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="cost_invoice_gross_amount"><?php esc_html_e('Brutto', 'erp-omd'); ?></label></th>
                    <td><input type="number" step="0.01" min="0" id="cost_invoice_gross_amount" name="cost_invoice_gross_amount" value="<?php echo esc_attr((string) ((float) ($invoice_form['gross_amount'] ?? 0))); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(! empty($invoice_form) ? __('Zaktualizuj fakturę kosztową', 'erp-omd') : __('Zapisz fakturę kosztową', 'erp-omd')); ?>
        </form>
    </div>

    <h2 style="margin-top:24px;"><?php esc_html_e('Lista faktur kosztowych', 'erp-omd'); ?></h2>
    <table class="widefat striped">
        <thead>
            <tr>
                <th>ID</th><th><?php esc_html_e('Numer', 'erp-omd'); ?></th><th><?php esc_html_e('Dostawca', 'erp-omd'); ?></th><th><?php esc_html_e('Projekt', 'erp-omd'); ?></th><th><?php esc_html_e('Status', 'erp-omd'); ?></th><th><?php esc_html_e('Brutto', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje', 'erp-omd'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($cost_invoices === []) : ?>
                <tr><td colspan="7"><?php esc_html_e('Brak faktur kosztowych.', 'erp-omd'); ?></td></tr>
            <?php endif; ?>
            <?php foreach ($cost_invoices as $invoice) : ?>
                <?php $invoice_id = (int) ($invoice['id'] ?? 0); ?>
                <tr>
                    <td><?php echo esc_html((string) $invoice_id); ?></td>
                    <td><?php echo esc_html((string) ($invoice['invoice_number'] ?? '')); ?></td>
                    <td><?php echo esc_html((string) ($supplier_name_by_id[(int) ($invoice['supplier_id'] ?? 0)] ?? ('#' . (int) ($invoice['supplier_id'] ?? 0)))); ?></td>
                    <td><?php echo esc_html((string) ($project_name_by_id[(int) ($invoice['project_id'] ?? 0)] ?? ('#' . (int) ($invoice['project_id'] ?? 0)))); ?></td>
                    <td><?php echo esc_html((string) ($invoice['status'] ?? '')); ?></td>
                    <td><?php echo esc_html(number_format((float) ($invoice['gross_amount'] ?? 0), 2, '.', ' ')); ?></td>
                    <td>
                        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-cost-invoices', 'invoice_id' => $invoice_id], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                        |
                        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-cost-invoices', 'invoice_id' => $invoice_id], admin_url('admin.php'))); ?>#invoice-audit"><?php esc_html_e('Audit', 'erp-omd'); ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($selected_invoice_id > 0) : ?>
        <h2 id="invoice-audit" style="margin-top:24px;"><?php echo esc_html(sprintf(__('Audit faktury #%d', 'erp-omd'), $selected_invoice_id)); ?></h2>
        <table class="widefat striped">
            <thead><tr><th><?php esc_html_e('Data', 'erp-omd'); ?></th><th><?php esc_html_e('Pole', 'erp-omd'); ?></th><th><?php esc_html_e('Przed', 'erp-omd'); ?></th><th><?php esc_html_e('Po', 'erp-omd'); ?></th><th><?php esc_html_e('Użytkownik', 'erp-omd'); ?></th></tr></thead>
            <tbody>
                <?php if ($selected_invoice_audit === []) : ?>
                    <tr><td colspan="5"><?php esc_html_e('Brak wpisów audytowych.', 'erp-omd'); ?></td></tr>
                <?php endif; ?>
                <?php foreach ($selected_invoice_audit as $audit_row) : ?>
                    <tr>
                        <td><?php echo esc_html((string) ($audit_row['changed_at'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($audit_row['field_name'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($audit_row['before_value'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($audit_row['after_value'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ((int) ($audit_row['changed_by_user_id'] ?? 0))); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
