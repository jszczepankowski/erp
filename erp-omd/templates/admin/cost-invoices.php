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
$invoice_form_net_amount = (float) ($invoice_form['net_amount'] ?? 0);
$invoice_form_vat_amount = (float) ($invoice_form['vat_amount'] ?? 0);
$invoice_form_vat_rate = '23';
if ($invoice_form_net_amount > 0) {
    $calculated_rate = round(($invoice_form_vat_amount / $invoice_form_net_amount) * 100, 2);
    foreach (['23', '8', '5', '0'] as $rate_option) {
        if (abs($calculated_rate - (float) $rate_option) < 0.01) {
            $invoice_form_vat_rate = $rate_option;
            break;
        }
    }
}
if ((float) ($invoice_form['vat_amount'] ?? 0) === 0.0 && ! empty($invoice_form)) {
    $invoice_form_vat_rate = 'zw';
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
                    <th><label for="supplier_company"><?php esc_html_e('Firma', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="text" id="supplier_company" name="supplier_company" value="<?php echo esc_attr((string) ($supplier_form['company'] ?? '')); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="supplier_nip"><?php esc_html_e('NIP', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="text" id="supplier_nip" name="supplier_nip" value="<?php echo esc_attr((string) ($supplier_form['nip'] ?? '')); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="supplier_email"><?php esc_html_e('Email główny', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="email" id="supplier_email" name="supplier_email" value="<?php echo esc_attr((string) ($supplier_form['email'] ?? '')); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="supplier_phone"><?php esc_html_e('Telefon główny', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="text" id="supplier_phone" name="supplier_phone" value="<?php echo esc_attr((string) ($supplier_form['phone'] ?? '')); ?>" /></td>
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
                <tr>
                    <th><label for="supplier_category"><?php esc_html_e('Kategoria', 'erp-omd'); ?></label></th>
                    <td>
                        <select id="supplier_category" name="supplier_category">
                            <option value=""><?php esc_html_e('Wybierz kategorię', 'erp-omd'); ?></option>
                            <?php foreach ((array) ($supplier_categories ?? []) as $supplier_category_option) : ?>
                                <option value="<?php echo esc_attr((string) $supplier_category_option); ?>" <?php selected((string) ($supplier_form['category'] ?? ''), (string) $supplier_category_option); ?>>
                                    <?php echo esc_html((string) $supplier_category_option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Słownik kategorii możesz zmienić poniżej (wartości oddzielone przecinkiem).', 'erp-omd'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="supplier_description"><?php esc_html_e('Opis dostawcy', 'erp-omd'); ?></label></th>
                    <td><textarea id="supplier_description" name="supplier_description" class="large-text" rows="3"><?php echo esc_textarea((string) ($supplier_form['supplier_description'] ?? '')); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="supplier_city"><?php esc_html_e('Miasto', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="text" id="supplier_city" name="supplier_city" value="<?php echo esc_attr((string) ($supplier_form['city'] ?? '')); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="supplier_street"><?php esc_html_e('Ulica', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="text" id="supplier_street" name="supplier_street" value="<?php echo esc_attr((string) ($supplier_form['street'] ?? '')); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="supplier_apartment_number"><?php esc_html_e('Numer lokalu', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="text" id="supplier_apartment_number" name="supplier_apartment_number" value="<?php echo esc_attr((string) ($supplier_form['apartment_number'] ?? '')); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="supplier_postal_code"><?php esc_html_e('Kod pocztowy', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="text" id="supplier_postal_code" name="supplier_postal_code" value="<?php echo esc_attr((string) ($supplier_form['postal_code'] ?? '')); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="supplier_country"><?php esc_html_e('Kraj', 'erp-omd'); ?></label></th>
                    <td><input class="regular-text" type="text" id="supplier_country" name="supplier_country" value="<?php echo esc_attr((string) ($supplier_form['country'] ?? 'PL')); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="supplier_categories_dictionary"><?php esc_html_e('Słownik kategorii', 'erp-omd'); ?></label></th>
                    <td><input class="large-text" type="text" id="supplier_categories_dictionary" name="supplier_categories_dictionary" value="<?php echo esc_attr(implode(', ', (array) ($supplier_categories ?? []))); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(! empty($supplier_form) ? __('Zaktualizuj dostawcę', 'erp-omd') : __('Zapisz dostawcę', 'erp-omd')); ?>
        </form>
    </div>

    <h2 style="margin-top:24px;"><?php esc_html_e('Dostawcy', 'erp-omd'); ?></h2>
    <table class="widefat striped">
        <thead><tr><th>ID</th><th><?php esc_html_e('Nazwa', 'erp-omd'); ?></th><th><?php esc_html_e('Kategoria', 'erp-omd'); ?></th><th><?php esc_html_e('Opiekun', 'erp-omd'); ?></th><th><?php esc_html_e('Email opiekuna', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje', 'erp-omd'); ?></th></tr></thead>
        <tbody>
            <?php if ($suppliers === []) : ?>
                <tr><td colspan="6"><?php esc_html_e('Brak dostawców.', 'erp-omd'); ?></td></tr>
            <?php endif; ?>
            <?php foreach ($suppliers as $supplier) : ?>
                <tr>
                    <td><?php echo esc_html((string) ((int) ($supplier['id'] ?? 0))); ?></td>
                    <td><?php echo esc_html((string) ($supplier['name'] ?? '')); ?></td>
                    <td><?php echo esc_html((string) ($supplier['category'] ?? '—')); ?></td>
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
                                <?php $project_client_name = (string) ($project['client_name'] ?? ''); ?>
                                <option value="<?php echo esc_attr((string) $project_id); ?>" <?php selected((int) ($invoice_form['project_id'] ?? 0), $project_id); ?>><?php echo esc_html(($project_client_name !== '' ? '[' . $project_client_name . '] ' : '') . (string) ($project['name'] ?? '')); ?></option>
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
                    <td><input type="number" step="0.01" min="0" id="cost_invoice_net_amount" name="cost_invoice_net_amount" value="<?php echo esc_attr((string) $invoice_form_net_amount); ?>" required /></td>
                </tr>
                <tr>
                    <th><label for="cost_invoice_vat_rate"><?php esc_html_e('Stawka VAT', 'erp-omd'); ?></label></th>
                    <td>
                        <select id="cost_invoice_vat_rate" name="cost_invoice_vat_rate">
                            <?php foreach (['23', '8', '5', '0', 'zw'] as $vat_rate_option) : ?>
                                <option value="<?php echo esc_attr($vat_rate_option); ?>" <?php selected($invoice_form_vat_rate, $vat_rate_option); ?>><?php echo esc_html($vat_rate_option . (is_numeric($vat_rate_option) ? '%' : '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="cost_invoice_vat_amount"><?php esc_html_e('Kwota VAT (auto)', 'erp-omd'); ?></label></th>
                    <td><input type="number" step="0.01" min="0" id="cost_invoice_vat_amount" value="<?php echo esc_attr((string) $invoice_form_vat_amount); ?>" readonly /></td>
                </tr>
                <tr>
                    <th><label for="cost_invoice_gross_amount"><?php esc_html_e('Brutto (auto)', 'erp-omd'); ?></label></th>
                    <td><input type="number" step="0.01" min="0" id="cost_invoice_gross_amount" value="<?php echo esc_attr((string) ((float) ($invoice_form['gross_amount'] ?? 0))); ?>" readonly /></td>
                </tr>
            </table>
            <?php submit_button(! empty($invoice_form) ? __('Zaktualizuj fakturę kosztową', 'erp-omd') : __('Zapisz fakturę kosztową', 'erp-omd')); ?>
        </form>
    </div>
    <script>
    (function () {
        var netField = document.getElementById('cost_invoice_net_amount');
        var vatRateField = document.getElementById('cost_invoice_vat_rate');
        var vatAmountField = document.getElementById('cost_invoice_vat_amount');
        var grossAmountField = document.getElementById('cost_invoice_gross_amount');
        if (!netField || !vatRateField || !vatAmountField || !grossAmountField) { return; }

        var recalculate = function () {
            var net = parseFloat(netField.value || '0');
            if (isNaN(net) || net < 0) { net = 0; }
            var vatRateRaw = String(vatRateField.value || '0');
            var vatRate = vatRateRaw === 'zw' ? 0 : parseFloat(vatRateRaw || '0');
            if (isNaN(vatRate) || vatRate < 0) { vatRate = 0; }
            var vatAmount = Math.round((net * (vatRate / 100)) * 100) / 100;
            var gross = Math.round((net + vatAmount) * 100) / 100;
            vatAmountField.value = vatAmount.toFixed(2);
            grossAmountField.value = gross.toFixed(2);
        };

        netField.addEventListener('input', recalculate);
        vatRateField.addEventListener('change', recalculate);
        recalculate();
    }());
    </script>

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
                        |
                        <form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js(__('Czy na pewno chcesz usunąć fakturę kosztową?', 'erp-omd')); ?>');">
                            <?php wp_nonce_field('erp_omd_delete_cost_invoice'); ?>
                            <input type="hidden" name="erp_omd_action" value="delete_cost_invoice" />
                            <input type="hidden" name="cost_invoice_id" value="<?php echo esc_attr((string) $invoice_id); ?>" />
                            <button type="submit" class="button-link-delete"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 style="margin-top:24px;"><?php esc_html_e('Relacje projekt ↔ dostawca (E3)', 'erp-omd'); ?></h2>
    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Projekt', 'erp-omd'); ?></th>
                <th><?php esc_html_e('Dostawca', 'erp-omd'); ?></th>
                <th><?php esc_html_e('Liczba faktur', 'erp-omd'); ?></th>
                <th><?php esc_html_e('Suma brutto', 'erp-omd'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ((array) ($project_supplier_pairs ?? []) === []) : ?>
                <tr><td colspan="4"><?php esc_html_e('Brak relacji projekt-dostawca.', 'erp-omd'); ?></td></tr>
            <?php endif; ?>
            <?php foreach ((array) ($project_supplier_pairs ?? []) as $pair) : ?>
                <?php $pair_project_id = (int) ($pair['project_id'] ?? 0); ?>
                <?php $pair_supplier_id = (int) ($pair['supplier_id'] ?? 0); ?>
                <tr>
                    <td><?php echo esc_html((string) ($project_name_by_id[$pair_project_id] ?? ('#' . $pair_project_id))); ?></td>
                    <td><?php echo esc_html((string) ($supplier_name_by_id[$pair_supplier_id] ?? ('#' . $pair_supplier_id))); ?></td>
                    <td><?php echo esc_html((string) ((int) ($pair['invoices_count'] ?? 0))); ?></td>
                    <td><?php echo esc_html(number_format((float) ($pair['gross_total'] ?? 0), 2, '.', ' ')); ?></td>
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
                    <?php $changed_by_user_id = (int) ($audit_row['changed_by_user_id'] ?? 0); ?>
                    <tr>
                        <td><?php echo esc_html((string) ($audit_row['changed_at'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($audit_row['field_name'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($audit_row['before_value'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($audit_row['after_value'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($audit_user_labels[$changed_by_user_id] ?? ('#' . $changed_by_user_id))); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
