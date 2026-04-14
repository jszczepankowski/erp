<div class="wrap erp-omd-admin erp-omd-cost-invoices-admin">
    <h1><?php esc_html_e('Dostawcy i faktury kosztowe', 'erp-omd'); ?></h1>

    <?php if (! empty($_GET['message'])) : ?>
        <div class="notice notice-success"><p><?php echo esc_html((string) wp_unslash($_GET['message'])); ?></p></div>
    <?php endif; ?>
    <?php if (! empty($_GET['error'])) : ?>
        <div class="notice notice-error"><p><?php echo esc_html(rawurldecode((string) wp_unslash($_GET['error']))); ?></p></div>
    <?php endif; ?>

    <div class="postbox" style="padding:16px;margin-top:16px;">
        <h2><?php esc_html_e('Nowy dostawca', 'erp-omd'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('erp_omd_save_supplier'); ?>
            <input type="hidden" name="erp_omd_action" value="save_supplier" />
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="supplier_name"><?php esc_html_e('Nazwa', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="text" id="supplier_name" name="supplier_name" required /></td>
                </tr>
                <tr>
                    <th><label for="supplier_contact_person_name"><?php esc_html_e('Opiekun', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="text" id="supplier_contact_person_name" name="supplier_contact_person_name" /></td>
                </tr>
                <tr>
                    <th><label for="supplier_contact_person_email"><?php esc_html_e('Email opiekuna', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="email" id="supplier_contact_person_email" name="supplier_contact_person_email" /></td>
                </tr>
                <tr>
                    <th><label for="supplier_contact_person_phone"><?php esc_html_e('Telefon opiekuna', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="text" id="supplier_contact_person_phone" name="supplier_contact_person_phone" /></td>
                </tr>
            </table>
            <?php submit_button(__('Zapisz dostawcę', 'erp-omd')); ?>
        </form>
    </div>

    <div class="postbox" style="padding:16px;margin-top:16px;">
        <h2><?php esc_html_e('Nowa / aktualizacja faktury kosztowej', 'erp-omd'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('erp_omd_save_cost_invoice'); ?>
            <input type="hidden" name="erp_omd_action" value="save_cost_invoice" />
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="cost_invoice_id"><?php esc_html_e('ID faktury (opcjonalnie - edycja)', 'erp-omd'); ?></label></th>
                    <td><input class="small-text" type="number" id="cost_invoice_id" name="cost_invoice_id" min="0" /></td>
                </tr>
                <tr>
                    <th><label for="cost_invoice_supplier_id"><?php esc_html_e('Dostawca', 'erp-omd'); ?></label></th>
                    <td>
                        <select id="cost_invoice_supplier_id" name="cost_invoice_supplier_id" required>
                            <option value=""><?php esc_html_e('Wybierz dostawcę', 'erp-omd'); ?></option>
                            <?php foreach ($suppliers as $supplier) : ?>
                                <option value="<?php echo esc_attr((string) ((int) ($supplier['id'] ?? 0))); ?>"><?php echo esc_html((string) ($supplier['name'] ?? '')); ?></option>
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
                                <option value="<?php echo esc_attr((string) ((int) ($project['id'] ?? 0))); ?>"><?php echo esc_html((string) ($project['name'] ?? '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="cost_invoice_number"><?php esc_html_e('Numer faktury', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="text" id="cost_invoice_number" name="cost_invoice_number" required /></td>
                </tr>
                <tr>
                    <th><label for="cost_invoice_issue_date"><?php esc_html_e('Data wystawienia', 'erp-omd'); ?></label></th>
                    <td><input type="date" id="cost_invoice_issue_date" name="cost_invoice_issue_date" /></td>
                </tr>
                <tr>
                    <th><label for="cost_invoice_status"><?php esc_html_e('Status', 'erp-omd'); ?></label></th>
                    <td>
                        <select id="cost_invoice_status" name="cost_invoice_status" required>
                            <?php foreach (['zaimportowana', 'weryfikacja', 'zatwierdzona', 'przypisana'] as $status) : ?>
                                <option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="cost_invoice_net_amount"><?php esc_html_e('Netto', 'erp-omd'); ?></label></th>
                    <td><input type="number" step="0.01" min="0" id="cost_invoice_net_amount" name="cost_invoice_net_amount" value="0" /></td>
                </tr>
                <tr>
                    <th><label for="cost_invoice_vat_amount"><?php esc_html_e('VAT', 'erp-omd'); ?></label></th>
                    <td><input type="number" step="0.01" min="0" id="cost_invoice_vat_amount" name="cost_invoice_vat_amount" value="0" /></td>
                </tr>
                <tr>
                    <th><label for="cost_invoice_gross_amount"><?php esc_html_e('Brutto', 'erp-omd'); ?></label></th>
                    <td><input type="number" step="0.01" min="0" id="cost_invoice_gross_amount" name="cost_invoice_gross_amount" value="0" /></td>
                </tr>
            </table>
            <?php submit_button(__('Zapisz fakturę kosztową', 'erp-omd')); ?>
        </form>
    </div>

    <h2 style="margin-top:24px;"><?php esc_html_e('Lista faktur kosztowych', 'erp-omd'); ?></h2>
    <table class="widefat striped">
        <thead>
            <tr>
                <th>ID</th><th><?php esc_html_e('Numer', 'erp-omd'); ?></th><th><?php esc_html_e('Dostawca', 'erp-omd'); ?></th><th><?php esc_html_e('Projekt', 'erp-omd'); ?></th><th><?php esc_html_e('Status', 'erp-omd'); ?></th><th><?php esc_html_e('Brutto', 'erp-omd'); ?></th><th><?php esc_html_e('Audit', 'erp-omd'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($cost_invoices === []) : ?>
                <tr><td colspan="7"><?php esc_html_e('Brak faktur kosztowych.', 'erp-omd'); ?></td></tr>
            <?php endif; ?>
            <?php foreach ($cost_invoices as $invoice) : ?>
                <tr>
                    <td><?php echo esc_html((string) ((int) ($invoice['id'] ?? 0))); ?></td>
                    <td><?php echo esc_html((string) ($invoice['invoice_number'] ?? '')); ?></td>
                    <td><?php echo esc_html((string) ((int) ($invoice['supplier_id'] ?? 0))); ?></td>
                    <td><?php echo esc_html((string) ((int) ($invoice['project_id'] ?? 0))); ?></td>
                    <td><?php echo esc_html((string) ($invoice['status'] ?? '')); ?></td>
                    <td><?php echo esc_html(number_format((float) ($invoice['gross_amount'] ?? 0), 2, '.', ' ')); ?></td>
                    <td><a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-cost-invoices', 'invoice_id' => (int) ($invoice['id'] ?? 0)], admin_url('admin.php'))); ?>"><?php esc_html_e('Pokaż', 'erp-omd'); ?></a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($selected_invoice_id > 0) : ?>
        <h2 style="margin-top:24px;"><?php echo esc_html(sprintf(__('Audit faktury #%d', 'erp-omd'), $selected_invoice_id)); ?></h2>
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
