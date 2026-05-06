<?php
$supplier_form = is_array($selected_supplier ?? null) ? $selected_supplier : [];
$invoice_form = is_array($selected_invoice ?? null) ? $selected_invoice : [];
$invoice_form_items = is_array($selected_invoice_items ?? null) ? $selected_invoice_items : [];
$supplier_name_by_id = [];
$supplier_nip_by_id = [];
$project_name_by_id = [];
foreach ((array) $suppliers as $supplier_row) {
    $supplier_name_by_id[(int) ($supplier_row['id'] ?? 0)] = (string) ($supplier_row['name'] ?? '');
    $supplier_nip_by_id[(int) ($supplier_row['id'] ?? 0)] = (string) ($supplier_row['nip'] ?? '');
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
$active_tab = sanitize_key((string) ($_GET['tab'] ?? 'suppliers'));
if (! in_array($active_tab, ['suppliers', 'invoices', 'relations', 'ksef-moderation', 'ksef-sales', 'ksef-cost'], true)) {
    $active_tab = 'suppliers';
}
?>
<div class="wrap erp-omd-admin erp-omd-cost-invoices-admin">
    <h1><?php esc_html_e('Dostawcy i koszty', 'erp-omd'); ?></h1>

    <?php if (! empty($_GET['message'])) : ?>
        <div class="notice notice-success"><p><?php echo esc_html((string) wp_unslash($_GET['message'])); ?></p></div>
    <?php endif; ?>
    <?php if (! empty($_GET['error'])) : ?>
        <div class="notice notice-error"><p><?php echo esc_html(rawurldecode((string) wp_unslash($_GET['error']))); ?></p></div>
    <?php endif; ?>

    <nav class="nav-tab-wrapper erp-omd-nav-tabs">
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-cost-invoices', 'tab' => 'suppliers'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $active_tab === 'suppliers' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Dostawcy', 'erp-omd'); ?></a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-cost-invoices', 'tab' => 'invoices'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $active_tab === 'invoices' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Faktury kosztowe', 'erp-omd'); ?></a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-cost-invoices', 'tab' => 'ksef-cost'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $active_tab === 'ksef-cost' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Kosztowe KSeF', 'erp-omd'); ?></a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-cost-invoices', 'tab' => 'ksef-sales'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $active_tab === 'ksef-sales' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Sprzedażowe KSeF', 'erp-omd'); ?></a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-cost-invoices', 'tab' => 'ksef-moderation'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $active_tab === 'ksef-moderation' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Kolejka moderacji KSeF', 'erp-omd'); ?></a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-cost-invoices', 'tab' => 'relations'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $active_tab === 'relations' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Relacje projekt ↔ dostawca', 'erp-omd'); ?></a>
    </nav>

    <?php if ($active_tab === 'suppliers') : ?>
    <section class="erp-omd-card">
        <h2><?php echo ! empty($supplier_form) ? esc_html__('Edytuj dostawcę', 'erp-omd') : esc_html__('Nowy dostawca', 'erp-omd'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('erp_omd_save_supplier'); ?>
            <input type="hidden" name="erp_omd_action" value="save_supplier" />
            <input type="hidden" name="supplier_id" value="<?php echo esc_attr((string) ((int) ($supplier_form['id'] ?? 0))); ?>" />
            <div class="erp-omd-form-sections">
                <section class="erp-omd-form-section">
                    <div class="erp-omd-form-section-header">
                        <h3><?php esc_html_e('Podstawy', 'erp-omd'); ?></h3>
                        <p><?php esc_html_e('Dane identyfikujące i klasyfikujące dostawcę.', 'erp-omd'); ?></p>
                    </div>
                    <div class="erp-omd-form-grid erp-omd-form-grid-cost-supplier-basics-row">
                        <div class="erp-omd-form-field">
                            <label for="supplier_name"><?php esc_html_e('Nazwa', 'erp-omd'); ?></label>
                            <input class="regular-text" type="text" id="supplier_name" name="supplier_name" value="<?php echo esc_attr((string) ($supplier_form['name'] ?? '')); ?>" required />
                        </div>
                        <div class="erp-omd-form-field">
                            <label for="supplier_company"><?php esc_html_e('Firma', 'erp-omd'); ?></label>
                            <input class="regular-text" type="text" id="supplier_company" name="supplier_company" value="<?php echo esc_attr((string) ($supplier_form['company'] ?? '')); ?>" />
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                            <label for="supplier_nip"><?php esc_html_e('NIP', 'erp-omd'); ?></label>
                            <input class="regular-text" type="text" id="supplier_nip" name="supplier_nip" value="<?php echo esc_attr((string) ($supplier_form['nip'] ?? '')); ?>" />
                        </div>
                        <div class="erp-omd-form-field">
                            <label for="supplier_category"><?php esc_html_e('Kategoria', 'erp-omd'); ?></label>
                            <select id="supplier_category" name="supplier_category">
                                <option value=""><?php esc_html_e('Wybierz kategorię', 'erp-omd'); ?></option>
                                <?php foreach ((array) ($supplier_categories ?? []) as $supplier_category_option) : ?>
                                    <option value="<?php echo esc_attr((string) $supplier_category_option); ?>" <?php selected((string) ($supplier_form['category'] ?? ''), (string) $supplier_category_option); ?>>
                                        <?php echo esc_html((string) $supplier_category_option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-full">
                            <label for="supplier_description"><?php esc_html_e('Opis dostawcy', 'erp-omd'); ?></label>
                            <textarea id="supplier_description" name="supplier_description" class="large-text" rows="3"><?php echo esc_textarea((string) ($supplier_form['supplier_description'] ?? '')); ?></textarea>
                        </div>
                    </div>
                </section>

                <section class="erp-omd-form-section">
                    <div class="erp-omd-form-section-header">
                        <h3><?php esc_html_e('Adres i kontakt', 'erp-omd'); ?></h3>
                        <p><?php esc_html_e('Najpierw dane adresowe, następnie kontakt główny i opiekun.', 'erp-omd'); ?></p>
                    </div>
                    <div class="erp-omd-form-grid">
                        <div class="erp-omd-form-field erp-omd-form-field-span-full erp-omd-form-grid erp-omd-form-grid-client-address-row">
                            <div class="erp-omd-form-field">
                                <label for="supplier_street"><?php esc_html_e('Ulica', 'erp-omd'); ?></label>
                                <input class="regular-text" type="text" id="supplier_street" name="supplier_street" value="<?php echo esc_attr((string) ($supplier_form['street'] ?? '')); ?>" />
                            </div>
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="supplier_apartment_number"><?php esc_html_e('Numer lokalu', 'erp-omd'); ?></label>
                                <input class="regular-text" type="text" id="supplier_apartment_number" name="supplier_apartment_number" value="<?php echo esc_attr((string) ($supplier_form['apartment_number'] ?? '')); ?>" />
                            </div>
                            <div class="erp-omd-form-field">
                                <label for="supplier_city"><?php esc_html_e('Miasto', 'erp-omd'); ?></label>
                                <input class="regular-text" type="text" id="supplier_city" name="supplier_city" value="<?php echo esc_attr((string) ($supplier_form['city'] ?? '')); ?>" />
                            </div>
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="supplier_postal_code"><?php esc_html_e('Kod pocztowy', 'erp-omd'); ?></label>
                                <input class="regular-text" type="text" id="supplier_postal_code" name="supplier_postal_code" value="<?php echo esc_attr((string) ($supplier_form['postal_code'] ?? '')); ?>" />
                            </div>
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="supplier_country"><?php esc_html_e('Kraj', 'erp-omd'); ?></label>
                                <input class="regular-text" type="text" id="supplier_country" name="supplier_country" value="<?php echo esc_attr((string) ($supplier_form['country'] ?? 'PL')); ?>" />
                            </div>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-full erp-omd-form-grid erp-omd-form-grid-cost-supplier-contact-row">
                            <div class="erp-omd-form-field">
                                <label for="supplier_email"><?php esc_html_e('Email główny', 'erp-omd'); ?></label>
                                <input class="regular-text" type="email" id="supplier_email" name="supplier_email" value="<?php echo esc_attr((string) ($supplier_form['email'] ?? '')); ?>" />
                            </div>
                            <div class="erp-omd-form-field">
                                <label for="supplier_phone"><?php esc_html_e('Telefon główny', 'erp-omd'); ?></label>
                                <input class="regular-text" type="text" id="supplier_phone" name="supplier_phone" value="<?php echo esc_attr((string) ($supplier_form['phone'] ?? '')); ?>" />
                            </div>
                            <div class="erp-omd-form-field">
                                <label for="supplier_contact_person_name"><?php esc_html_e('Opiekun', 'erp-omd'); ?></label>
                                <input class="regular-text" type="text" id="supplier_contact_person_name" name="supplier_contact_person_name" value="<?php echo esc_attr((string) ($supplier_form['contact_person_name'] ?? '')); ?>" />
                            </div>
                            <div class="erp-omd-form-field">
                                <label for="supplier_contact_person_email"><?php esc_html_e('Email opiekuna', 'erp-omd'); ?></label>
                                <input class="regular-text" type="email" id="supplier_contact_person_email" name="supplier_contact_person_email" value="<?php echo esc_attr((string) ($supplier_form['contact_person_email'] ?? '')); ?>" />
                            </div>
                            <div class="erp-omd-form-field">
                                <label for="supplier_contact_person_phone"><?php esc_html_e('Telefon opiekuna', 'erp-omd'); ?></label>
                                <input class="regular-text" type="text" id="supplier_contact_person_phone" name="supplier_contact_person_phone" value="<?php echo esc_attr((string) ($supplier_form['contact_person_phone'] ?? '')); ?>" />
                            </div>
                        </div>
                    </div>
                </section>

                <section class="erp-omd-form-section">
                    <div class="erp-omd-form-section-header">
                        <h3><?php esc_html_e('Lifecycle', 'erp-omd'); ?></h3>
                        <p><?php esc_html_e('Ustawienia słownika kategorii dla formularza dostawców.', 'erp-omd'); ?></p>
                    </div>
                    <div class="erp-omd-form-grid">
                        <div class="erp-omd-form-field erp-omd-form-field-span-full">
                            <label for="supplier_categories_dictionary"><?php esc_html_e('Słownik kategorii', 'erp-omd'); ?></label>
                            <input class="large-text" type="text" id="supplier_categories_dictionary" name="supplier_categories_dictionary" value="<?php echo esc_attr(implode(', ', (array) ($supplier_categories ?? []))); ?>" />
                            <p class="description"><?php esc_html_e('Wartości oddziel przecinkiem. Lista zasila pole „Kategoria” powyżej.', 'erp-omd'); ?></p>
                        </div>
                    </div>
                </section>
            </div>
            <div class="erp-omd-form-actions">
                <?php submit_button(! empty($supplier_form) ? __('Zaktualizuj dostawcę', 'erp-omd') : __('Zapisz dostawcę', 'erp-omd')); ?>
            </div>
        </form>
    </section>

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
                    <td>
                        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-cost-invoices', 'tab' => 'suppliers', 'supplier_id' => (int) ($supplier['id'] ?? 0)], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                        |
                        <form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js(__('Czy na pewno chcesz usunąć dostawcę wraz z jego fakturami?', 'erp-omd')); ?>');">
                            <?php wp_nonce_field('erp_omd_delete_supplier'); ?>
                            <input type="hidden" name="erp_omd_action" value="delete_supplier" />
                            <input type="hidden" name="supplier_id" value="<?php echo esc_attr((string) ((int) ($supplier['id'] ?? 0))); ?>" />
                            <button type="submit" class="button-link-delete"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if ($active_tab === 'invoices') : ?>
    <section class="erp-omd-card">
        <h2><?php esc_html_e('Faktury kosztowe', 'erp-omd'); ?></h2>
        <section class="erp-omd-form-section">
            <div class="erp-omd-section-header">
                <div>
                    <h3><?php echo ! empty($invoice_form) ? esc_html__('Edytuj fakturę kosztową', 'erp-omd') : esc_html__('Nowa faktura kosztowa', 'erp-omd'); ?></h3>
                </div>
            </div>
            <form method="post">
                <?php wp_nonce_field('erp_omd_save_cost_invoice'); ?>
                <input type="hidden" name="erp_omd_action" value="save_cost_invoice" />
                <input type="hidden" name="cost_invoice_id" value="<?php echo esc_attr((string) ((int) ($invoice_form['id'] ?? 0))); ?>" />
                <div class="erp-omd-form-sections">
                    <section class="erp-omd-form-section">
                        <div class="erp-omd-form-section-header">
                            <h3><?php esc_html_e('Relacje i dokument', 'erp-omd'); ?></h3>
                            <p><?php esc_html_e('Połączenia faktury z dostawcą i projektem oraz dane dokumentu.', 'erp-omd'); ?></p>
                        </div>
                        <div class="erp-omd-form-grid">
                            <div class="erp-omd-form-field">
                                <label for="cost_invoice_supplier_id"><?php esc_html_e('Dostawca', 'erp-omd'); ?></label>
                                <select id="cost_invoice_supplier_id" name="cost_invoice_supplier_id" required>
                                    <option value=""><?php esc_html_e('Wybierz dostawcę', 'erp-omd'); ?></option>
                                    <?php foreach ($suppliers as $supplier) : ?>
                                        <?php $supplier_id = (int) ($supplier['id'] ?? 0); ?>
                                        <option value="<?php echo esc_attr((string) $supplier_id); ?>" <?php selected((int) ($invoice_form['supplier_id'] ?? 0), $supplier_id); ?>><?php echo esc_html((string) ($supplier['name'] ?? '')); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="erp-omd-form-field">
                                <label for="cost_invoice_project_id"><?php esc_html_e('Projekt', 'erp-omd'); ?></label>
                                <select id="cost_invoice_project_id" name="cost_invoice_project_id">
                                    <option value=""><?php esc_html_e('Wybierz projekt', 'erp-omd'); ?></option>
                                    <?php foreach ($projects as $project) : ?>
                                        <?php $project_id = (int) ($project['id'] ?? 0); ?>
                                        <?php $project_client_name = (string) ($project['client_name'] ?? ''); ?>
                                        <option value="<?php echo esc_attr((string) $project_id); ?>" <?php selected((int) ($invoice_form['project_id'] ?? 0), $project_id); ?>><?php echo esc_html(($project_client_name !== '' ? '[' . $project_client_name . '] ' : '') . (string) ($project['name'] ?? '')); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="erp-omd-form-field erp-omd-form-field-span-full erp-omd-form-grid erp-omd-form-grid-cost-invoice-document-row">
                                <div class="erp-omd-form-field">
                                    <label for="cost_invoice_number"><?php esc_html_e('Numer faktury', 'erp-omd'); ?></label>
                                    <input class="regular-text" type="text" id="cost_invoice_number" name="cost_invoice_number" value="<?php echo esc_attr((string) ($invoice_form['invoice_number'] ?? '')); ?>" required />
                                </div>
                                <div class="erp-omd-form-field erp-omd-form-field-compact">
                                    <label for="cost_invoice_issue_date"><?php esc_html_e('Data wystawienia', 'erp-omd'); ?></label>
                                    <input type="date" id="cost_invoice_issue_date" name="cost_invoice_issue_date" value="<?php echo esc_attr((string) ($invoice_form['issue_date'] ?? '')); ?>" />
                                </div>
                                <div class="erp-omd-form-field erp-omd-form-field-compact">
                                    <label for="cost_invoice_status"><?php esc_html_e('Status', 'erp-omd'); ?></label>
                                    <select id="cost_invoice_status" name="cost_invoice_status" required>
                                        <?php foreach (['zaimportowana', 'weryfikacja', 'zatwierdzona', 'przypisana', 'nieistotne'] as $status) : ?>
                                            <option value="<?php echo esc_attr($status); ?>" <?php selected((string) ($invoice_form['status'] ?? 'zaimportowana'), $status); ?>><?php echo esc_html($status); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="erp-omd-form-field">
                                    <label for="cost_invoice_ksef_reference_number"><?php esc_html_e('Numer ref. KSeF (opcjonalny)', 'erp-omd'); ?></label>
                                    <input class="regular-text" type="text" id="cost_invoice_ksef_reference_number" name="cost_invoice_ksef_reference_number" value="<?php echo esc_attr((string) ($invoice_form['ksef_reference_number'] ?? '')); ?>" />
                                </div>
                            </div>
                            <div class="erp-omd-form-field erp-omd-form-field-span-full">
                                <label for="cost_invoice_description"><?php esc_html_e('Opis faktury kosztowej', 'erp-omd'); ?></label>
                                <textarea id="cost_invoice_description" name="cost_invoice_description" rows="3" class="large-text"><?php echo esc_textarea((string) ($invoice_form['description'] ?? '')); ?></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="erp-omd-form-section">
                        <div class="erp-omd-form-section-header">
                            <h3><?php esc_html_e('Pozycje faktury i podsumowanie', 'erp-omd'); ?></h3>
                            <p><?php esc_html_e('Edytuj pozycje wraz ze stawkami VAT. Suma netto/VAT/brutto wylicza się automatycznie.', 'erp-omd'); ?></p>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-full">
                            <table class="widefat striped" id="cost-invoice-items-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Lp', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Nazwa pozycji', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Ilość', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('JM', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Netto', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('VAT %', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('VAT', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Brutto', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Akcja', 'erp-omd'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($invoice_form_items === []) : ?>
                                        <?php $invoice_form_items = [['line_no' => 1, 'item_name' => '', 'qty' => 1, 'unit' => 'szt', 'net_amount' => 0, 'vat_rate' => 23, 'vat_amount' => 0, 'gross_amount' => 0]]; ?>
                                    <?php endif; ?>
                                    <?php foreach ((array) $invoice_form_items as $item_index => $item_row) : ?>
                                        <?php
                                        $item_line_no = (int) ($item_row['line_no'] ?? ($item_index + 1));
                                        $item_name = (string) ($item_row['item_name'] ?? $item_row['name'] ?? '');
                                        $item_qty = (float) ($item_row['qty'] ?? 1);
                                        $item_unit = (string) ($item_row['unit'] ?? 'szt');
                                        $item_net = (float) ($item_row['net_amount'] ?? 0);
                                        $item_vat_rate = (float) ($item_row['vat_rate'] ?? 23);
                                        $item_vat = (float) ($item_row['vat_amount'] ?? 0);
                                        $item_gross = (float) ($item_row['gross_amount'] ?? 0);
                                        ?>
                                        <tr class="cost-invoice-item-row">
                                            <td><input type="number" min="1" name="cost_invoice_items[<?php echo esc_attr((string) $item_index); ?>][line_no]" value="<?php echo esc_attr((string) $item_line_no); ?>" class="small-text cost-item-line-no" /></td>
                                            <td><input type="text" name="cost_invoice_items[<?php echo esc_attr((string) $item_index); ?>][name]" value="<?php echo esc_attr($item_name); ?>" class="regular-text cost-item-name" /></td>
                                            <td><input type="number" step="0.001" min="0" name="cost_invoice_items[<?php echo esc_attr((string) $item_index); ?>][qty]" value="<?php echo esc_attr((string) $item_qty); ?>" class="small-text cost-item-qty" /></td>
                                            <td><input type="text" name="cost_invoice_items[<?php echo esc_attr((string) $item_index); ?>][unit]" value="<?php echo esc_attr($item_unit); ?>" class="small-text cost-item-unit" /></td>
                                            <td><input type="number" step="0.01" min="0" name="cost_invoice_items[<?php echo esc_attr((string) $item_index); ?>][net_amount]" value="<?php echo esc_attr((string) $item_net); ?>" class="small-text cost-item-net" /></td>
                                            <td><input type="number" step="0.01" min="0" name="cost_invoice_items[<?php echo esc_attr((string) $item_index); ?>][vat_rate]" value="<?php echo esc_attr((string) $item_vat_rate); ?>" class="small-text cost-item-vat-rate" /></td>
                                            <td><input type="number" step="0.01" min="0" value="<?php echo esc_attr((string) $item_vat); ?>" class="small-text cost-item-vat" readonly /></td>
                                            <td><input type="number" step="0.01" min="0" value="<?php echo esc_attr((string) $item_gross); ?>" class="small-text cost-item-gross" readonly /></td>
                                            <td><button type="button" class="button-link-delete cost-item-remove"><?php esc_html_e('Usuń', 'erp-omd'); ?></button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p><button type="button" class="button button-secondary" id="cost-invoice-add-item"><?php esc_html_e('Dodaj pozycję', 'erp-omd'); ?></button></p>
                        </div>
                        <div class="erp-omd-form-grid erp-omd-form-grid-cost-invoice-amounts-row">
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="cost_invoice_net_amount"><?php esc_html_e('Suma netto', 'erp-omd'); ?></label>
                                <input type="number" step="0.01" min="0" id="cost_invoice_net_amount" name="cost_invoice_net_amount" value="<?php echo esc_attr((string) $invoice_form_net_amount); ?>" readonly />
                            </div>
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="cost_invoice_vat_amount"><?php esc_html_e('Suma VAT', 'erp-omd'); ?></label>
                                <input type="number" step="0.01" min="0" id="cost_invoice_vat_amount" value="<?php echo esc_attr((string) $invoice_form_vat_amount); ?>" readonly />
                            </div>
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="cost_invoice_gross_amount"><?php esc_html_e('Suma brutto', 'erp-omd'); ?></label>
                                <input type="number" step="0.01" min="0" id="cost_invoice_gross_amount" value="<?php echo esc_attr((string) ((float) ($invoice_form['gross_amount'] ?? 0))); ?>" readonly />
                            </div>
                        </div>
                    </section>
                </div>
                <div class="erp-omd-form-actions">
                    <?php submit_button(! empty($invoice_form) ? __('Zaktualizuj fakturę kosztową', 'erp-omd') : __('Zapisz fakturę kosztową', 'erp-omd')); ?>
                </div>
            </form>
        </section>
        <section class="erp-omd-form-section">
            <div class="erp-omd-section-header">
                <div>
                    <h3><?php esc_html_e('Lista faktur kosztowych', 'erp-omd'); ?></h3>
                </div>
            </div>
            <form method="get" class="erp-omd-filter-form" style="margin-bottom:12px;">
                <input type="hidden" name="page" value="erp-omd-cost-invoices" />
                <input type="hidden" name="tab" value="invoices" />
                <input type="hidden" name="invoice_id" value="<?php echo esc_attr((string) $selected_invoice_id); ?>" />
                <select name="invoice_supplier_id">
                    <option value="0"><?php esc_html_e('Wszyscy dostawcy', 'erp-omd'); ?></option>
                    <?php foreach ((array) $suppliers as $supplier_filter_option) : ?>
                        <?php $supplier_filter_id = (int) ($supplier_filter_option['id'] ?? 0); ?>
                        <option value="<?php echo esc_attr((string) $supplier_filter_id); ?>" <?php selected((int) ($invoice_list_filters['supplier_id'] ?? 0), $supplier_filter_id); ?>>
                            <?php echo esc_html((string) ($supplier_filter_option['name'] ?? '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="invoice_project_id">
                    <option value="0"><?php esc_html_e('Wszystkie projekty', 'erp-omd'); ?></option>
                    <?php foreach ((array) $projects as $project_filter_option) : ?>
                        <?php $project_filter_id = (int) ($project_filter_option['id'] ?? 0); ?>
                        <?php $project_filter_client_name = (string) ($project_filter_option['client_name'] ?? ''); ?>
                        <option value="<?php echo esc_attr((string) $project_filter_id); ?>" <?php selected((int) ($invoice_list_filters['project_id'] ?? 0), $project_filter_id); ?>>
                            <?php echo esc_html(($project_filter_client_name !== '' ? '[' . $project_filter_client_name . '] ' : '') . (string) ($project_filter_option['name'] ?? '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="invoice_status">
                    <option value=""><?php esc_html_e('Wszystkie statusy', 'erp-omd'); ?></option>
                    <?php foreach (['zaimportowana', 'weryfikacja', 'zatwierdzona', 'przypisana', 'nieistotne'] as $invoice_status_option) : ?>
                        <option value="<?php echo esc_attr($invoice_status_option); ?>" <?php selected((string) ($invoice_list_filters['status'] ?? ''), $invoice_status_option); ?>>
                            <?php echo esc_html($invoice_status_option); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="button" type="submit"><?php esc_html_e('Filtruj', 'erp-omd'); ?></button>
            </form>
            <?php
            $invoice_status_labels = [
                '' => __('Wszystkie', 'erp-omd'),
                'zaimportowana' => __('Zaimportowana', 'erp-omd'),
                'weryfikacja' => __('Weryfikacja', 'erp-omd'),
                'zatwierdzona' => __('Zatwierdzona', 'erp-omd'),
                'przypisana' => __('Przypisana', 'erp-omd'),
                'nieistotne' => __('Nieistotne', 'erp-omd'),
            ];
            ?>
            <div class="erp-omd-filter-form" style="margin-bottom:12px;">
                <?php foreach ($invoice_status_labels as $status_key => $status_label) : ?>
                    <?php
                    $status_filter_url = add_query_arg(
                        [
                            'page' => 'erp-omd-cost-invoices',
                            'tab' => 'invoices',
                            'invoice_id' => $selected_invoice_id,
                            'invoice_supplier_id' => (int) ($invoice_list_filters['supplier_id'] ?? 0),
                            'invoice_project_id' => (int) ($invoice_list_filters['project_id'] ?? 0),
                            'invoice_status' => $status_key,
                        ],
                        admin_url('admin.php')
                    );
                    ?>
                    <a class="button <?php echo (string) ($invoice_list_filters['status'] ?? '') === (string) $status_key ? 'button-primary' : ''; ?>" href="<?php echo esc_url($status_filter_url); ?>">
                        <?php echo esc_html($status_label); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <form method="post">
                <?php wp_nonce_field('erp_omd_bulk_cost_invoices'); ?>
                <input type="hidden" name="erp_omd_action" value="bulk_cost_invoices" />
                <p>
                    <select name="bulk_action">
                        <option value=""><?php esc_html_e('Akcje zbiorowe', 'erp-omd'); ?></option>
                        <option value="delete"><?php esc_html_e('Usuń', 'erp-omd'); ?></option>
                        <option value="status_zaimportowana"><?php esc_html_e('Status: zaimportowana', 'erp-omd'); ?></option>
                        <option value="status_weryfikacja"><?php esc_html_e('Status: weryfikacja', 'erp-omd'); ?></option>
                        <option value="status_zatwierdzona"><?php esc_html_e('Status: zatwierdzona', 'erp-omd'); ?></option>
                        <option value="status_przypisana"><?php esc_html_e('Status: przypisana', 'erp-omd'); ?></option>
                        <option value="status_nieistotne"><?php esc_html_e('Status: nieistotne', 'erp-omd'); ?></option>
                    </select>
                    <button type="submit" class="button button-secondary"><?php esc_html_e('Zastosuj', 'erp-omd'); ?></button>
                </p>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="cost-invoice-select-all" /></th><th>ID</th><th><?php esc_html_e('Data wystawienia', 'erp-omd'); ?></th><th><?php esc_html_e('Numer', 'erp-omd'); ?></th><th><?php esc_html_e('Dostawca', 'erp-omd'); ?></th><th><?php esc_html_e('Projekt', 'erp-omd'); ?></th><th><?php esc_html_e('Opis', 'erp-omd'); ?></th><th><?php esc_html_e('Status', 'erp-omd'); ?></th><th><?php esc_html_e('Brutto', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje', 'erp-omd'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($cost_invoices === []) : ?>
                        <tr><td colspan="10"><?php esc_html_e('Brak faktur kosztowych.', 'erp-omd'); ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($cost_invoices as $invoice) : ?>
                        <?php $invoice_id = (int) ($invoice['id'] ?? 0); ?>
                        <tr>
                            <td><input type="checkbox" class="cost-invoice-checkbox" name="cost_invoice_ids[]" value="<?php echo esc_attr((string) $invoice_id); ?>" /></td>
                            <td><?php echo esc_html((string) $invoice_id); ?></td>
                            <td><?php echo esc_html((string) ($invoice['issue_date'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($invoice['invoice_number'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($supplier_name_by_id[(int) ($invoice['supplier_id'] ?? 0)] ?? ('#' . (int) ($invoice['supplier_id'] ?? 0)))); ?></td>
                            <td><?php echo esc_html((string) ($project_name_by_id[(int) ($invoice['project_id'] ?? 0)] ?? ('#' . (int) ($invoice['project_id'] ?? 0)))); ?></td>
                            <td><?php echo esc_html((string) ($invoice['description'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($invoice['status'] ?? '')); ?></td>
                            <td><?php echo esc_html(number_format((float) ($invoice['gross_amount'] ?? 0), 2, '.', ' ')); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-cost-invoices', 'tab' => 'invoices', 'invoice_id' => $invoice_id], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                |
                                <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-cost-invoices', 'tab' => 'invoices', 'invoice_id' => $invoice_id], admin_url('admin.php'))); ?>#invoice-audit"><?php esc_html_e('Audit', 'erp-omd'); ?></a>
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
            </form>
        </section>
    </section>
    <?php endif; ?>


    <?php if ($active_tab === 'ksef-moderation') : ?>
    <section class="erp-omd-card">
        <h2><?php esc_html_e('KSeF — kolejka moderacji', 'erp-omd'); ?></h2>
        <form method="get" style="margin-bottom:12px;">
            <input type="hidden" name="page" value="erp-omd-cost-invoices" />
            <input type="hidden" name="tab" value="ksef-moderation" />
            <label for="ksef-status-filter"><?php esc_html_e('Status', 'erp-omd'); ?></label>
            <select id="ksef-status-filter" name="ksef_status">
                <option value=""><?php esc_html_e('Wszystkie', 'erp-omd'); ?></option>
                <?php foreach (['new', 'conflict', 'manual_required', 'ready', 'rejected'] as $ksef_status_option) : ?>
                    <option value="<?php echo esc_attr($ksef_status_option); ?>" <?php selected((string) ($ksef_moderation_filter_status ?? ''), $ksef_status_option); ?>><?php echo esc_html($ksef_status_option); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button button-secondary"><?php esc_html_e('Filtruj', 'erp-omd'); ?></button>
        </form>

        <form method="post">
            <?php wp_nonce_field('erp_omd_bulk_ksef_queue'); ?>
            <input type="hidden" name="erp_omd_action" value="bulk_ksef_queue" />
            <p>
                <select name="ksef_bulk_action">
                    <option value=""><?php esc_html_e('Akcja bulk', 'erp-omd'); ?></option>
                    <option value="approve"><?php esc_html_e('Zatwierdź', 'erp-omd'); ?></option>
                    <option value="reject"><?php esc_html_e('Odrzuć', 'erp-omd'); ?></option>
                    <option value="delete"><?php esc_html_e('Usuń z kolejki', 'erp-omd'); ?></option>
                    <option value="assign_supplier"><?php esc_html_e('Przypisz dostawcę', 'erp-omd'); ?></option>
                    <option value="assign_project"><?php esc_html_e('Przypisz projekt', 'erp-omd'); ?></option>
                </select>
                <input type="number" min="0" name="supplier_id" placeholder="<?php esc_attr_e('supplier_id', 'erp-omd'); ?>" style="width:110px;" />
                <input type="number" min="0" name="project_id" placeholder="<?php esc_attr_e('project_id', 'erp-omd'); ?>" style="width:110px;" />
                <button type="submit" class="button button-secondary"><?php esc_html_e('Wykonaj bulk', 'erp-omd'); ?></button>
            </p>
            <table class="widefat striped">
                <thead><tr><th></th><th><?php esc_html_e('Retry key', 'erp-omd'); ?></th><th><?php esc_html_e('Status', 'erp-omd'); ?></th><th><?php esc_html_e('Dokument', 'erp-omd'); ?></th><th><?php esc_html_e('Próby', 'erp-omd'); ?></th><th><?php esc_html_e('Ostatni błąd', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje manualne', 'erp-omd'); ?></th></tr></thead>
                <tbody>
                <?php if (empty($ksef_moderation_queue)) : ?>
                    <tr><td colspan="7"><?php esc_html_e('Brak rekordów do moderacji.', 'erp-omd'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ((array) $ksef_moderation_queue as $ksef_row) : ?>
                        <tr>
                            <td><input type="checkbox" name="retry_keys[]" value="<?php echo esc_attr((string) ($ksef_row['retry_key'] ?? '')); ?>" /></td>
                            <td><code><?php echo esc_html((string) ($ksef_row['retry_key'] ?? '')); ?></code></td>
                            <td><?php echo esc_html((string) ($ksef_row['status'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) (($ksef_row['document']['invoice_number'] ?? '') ?: '—')); ?></td>
                            <td><?php echo esc_html((string) ((int) ($ksef_row['retry_attempts'] ?? 0))); ?></td>
                            <td><?php echo esc_html((string) ($ksef_row['last_error'] ?? '')); ?></td>
                            <td><?php esc_html_e('Użyj akcji bulk (zatwierdź/odrzuć).', 'erp-omd'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </form>
    </section>
    <?php endif; ?>


    <?php if ($active_tab === 'ksef-sales') : ?>
    <?php
    $final_invoice_project_ids = [];
    foreach ((array) $ksef_sales_inbox as $sales_invoice_row) {
        if ((int) ($sales_invoice_row['is_final'] ?? 0) === 1) {
            $final_project_id = (int) ($sales_invoice_row['project_id'] ?? 0);
            if ($final_project_id > 0) {
                $final_invoice_project_ids[$final_project_id] = true;
            }
        }
    }
    ?>
    <section class="erp-omd-card">
        <h2><?php esc_html_e('KSeF — faktury sprzedażowe', 'erp-omd'); ?></h2>
        
        <form method="post" enctype="multipart/form-data" style="margin-bottom:14px;">
            <?php wp_nonce_field('erp_omd_import_ksef_sales_xml'); ?>
            <input type="hidden" name="erp_omd_action" value="import_ksef_sales_xml" />
            <p>
                <label for="ksef-sales-xml-files"><?php esc_html_e('Wybierz plik/pliki XML (import zbiorowy)', 'erp-omd'); ?></label><br />
                <input id="ksef-sales-xml-files" type="file" name="ksef_sales_xml_files[]" accept=".xml,text/xml,application/xml" multiple />
            </p>
            <p>
                <label for="ksef-sales-xml-content"><?php esc_html_e('Lub wklej XML sprzedażowy (opcjonalnie)', 'erp-omd'); ?></label><br />
                <textarea id="ksef-sales-xml-content" name="ksef_sales_xml_content" rows="4" class="large-text"></textarea>
            </p>
            <p>
                <label for="ksef-sales-description"><?php esc_html_e('Opis faktury sprzedażowej (opcjonalny, dla importu manualnego)', 'erp-omd'); ?></label><br />
                <textarea id="ksef-sales-description" name="ksef_sales_description" rows="2" class="large-text"></textarea>
            </p>
            <p><button type="submit" class="button button-primary"><?php esc_html_e('Importuj XML sprzedażowy', 'erp-omd'); ?></button></p>
        </form>
        <div class="erp-omd-table-tools" style="margin: 8px 0 14px;">
            <?php
            $sales_filter_all_url = add_query_arg(['page' => 'erp-omd-cost-invoices', 'tab' => 'ksef-sales', 'ksef_sales_assignment' => 'all'], admin_url('admin.php'));
            $sales_filter_assigned_url = add_query_arg(['page' => 'erp-omd-cost-invoices', 'tab' => 'ksef-sales', 'ksef_sales_assignment' => 'assigned'], admin_url('admin.php'));
            $sales_filter_unassigned_url = add_query_arg(['page' => 'erp-omd-cost-invoices', 'tab' => 'ksef-sales', 'ksef_sales_assignment' => 'unassigned'], admin_url('admin.php'));
            ?>
            <strong><?php esc_html_e('Filtr przypisania:', 'erp-omd'); ?></strong>
            <a class="button button-small <?php echo ($ksef_sales_assignment_filter ?? 'all') === 'assigned' ? '' : 'button-link'; ?>" href="<?php echo esc_url($sales_filter_assigned_url); ?>"><?php esc_html_e('Przypisane', 'erp-omd'); ?></a>
            <a class="button button-small <?php echo ($ksef_sales_assignment_filter ?? 'all') === 'unassigned' ? '' : 'button-link'; ?>" href="<?php echo esc_url($sales_filter_unassigned_url); ?>"><?php esc_html_e('Nie przypisane', 'erp-omd'); ?></a>
            <a class="button button-small <?php echo ($ksef_sales_assignment_filter ?? 'all') === 'all' ? '' : 'button-link'; ?>" href="<?php echo esc_url($sales_filter_all_url); ?>"><?php esc_html_e('Wszystkie', 'erp-omd'); ?></a>
        </div>

        <table class="widefat striped">
            <thead><tr><th>ID</th><th><?php esc_html_e('Numer', 'erp-omd'); ?></th><th><?php esc_html_e('Nabywca', 'erp-omd'); ?></th><th><?php esc_html_e('NIP nabywcy', 'erp-omd'); ?></th><th><?php esc_html_e('Client ID', 'erp-omd'); ?></th><th><?php esc_html_e('Projekt', 'erp-omd'); ?></th><th><?php esc_html_e('Końcowa', 'erp-omd'); ?></th><th><?php esc_html_e('Status', 'erp-omd'); ?></th><th><?php esc_html_e('Akcja', 'erp-omd'); ?></th></tr></thead>
            <tbody>
            <?php if (empty($ksef_sales_inbox)) : ?>
                <tr><td colspan="9"><?php esc_html_e('Brak sprzedażowych dokumentów KSeF.', 'erp-omd'); ?></td></tr>
            <?php else : ?>
                <?php foreach ((array) $ksef_sales_inbox as $sales_row) : ?>
                    <tr>
                        <?php $sales_client_id = (int) ($sales_row['client_id'] ?? 0); ?>
                        <td><?php echo esc_html((string) ((int) ($sales_row['id'] ?? 0))); ?></td>
                        <td><?php echo esc_html((string) ($sales_row['invoice_number'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($client_name_by_id[$sales_client_id] ?? '—')); ?></td>
                        <td><?php echo esc_html((string) ($sales_row['buyer_nip'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) $sales_client_id); ?></td>
                        <td><?php echo esc_html((string) ($project_name_by_id[(int) ($sales_row['project_id'] ?? 0)] ?? ('#' . (int) ($sales_row['project_id'] ?? 0)))); ?></td>
                        <td><?php echo ((int) ($sales_row['is_final'] ?? 0) === 1) ? esc_html__('Tak', 'erp-omd') : esc_html__('Nie', 'erp-omd'); ?></td>
                        <td><?php echo esc_html((string) ($sales_row['status'] ?? '')); ?></td>
                        <td>
                            <form method="post" style="display:flex;gap:6px;align-items:center;">
                                <?php wp_nonce_field('erp_omd_attach_ksef_sales_invoice'); ?>
                                <input type="hidden" name="erp_omd_action" value="attach_ksef_sales_invoice" />
                                <input type="hidden" name="sales_id" value="<?php echo esc_attr((string) ((int) ($sales_row['id'] ?? 0))); ?>" />
                                <select name="project_id" required style="min-width:180px;">
                                    <option value=""><?php esc_html_e('Wybierz projekt', 'erp-omd'); ?></option>
                                    <?php foreach ($projects as $project) : ?>
                                        <?php $project_id = (int) ($project['id'] ?? 0); ?>
                                        <?php $project_status = (string) ($project['status'] ?? ''); ?>
                                        <?php if (in_array($project_status, ['zakonczony', 'archiwum'], true) && (int) ($sales_row['project_id'] ?? 0) !== $project_id) { continue; } ?>
                                        <?php if (! empty($final_invoice_project_ids[$project_id]) && (int) ($sales_row['project_id'] ?? 0) !== $project_id) { continue; } ?>
                                        <?php $project_client_name = (string) ($project['client_name'] ?? ''); ?>
                                        <option value="<?php echo esc_attr((string) $project_id); ?>" <?php selected((int) ($sales_row['project_id'] ?? 0), $project_id); ?>>
                                            <?php echo esc_html(($project_client_name !== '' ? '[' . $project_client_name . '] ' : '') . (string) ($project['name'] ?? '')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label style="display:flex;gap:3px;align-items:center;">
                                    <input type="checkbox" name="is_final" value="1" <?php checked((int) ($sales_row['is_final'] ?? 0), 1); ?> />
                                    <span><?php esc_html_e('końcowa', 'erp-omd'); ?></span>
                                </label>
                                <button type="submit" class="button button-small"><?php esc_html_e('Zapisz', 'erp-omd'); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <?php if ($active_tab === 'ksef-cost') : ?>
    <section class="erp-omd-card">
        <h2><?php esc_html_e('KSeF — faktury kosztowe', 'erp-omd'); ?></h2>
        <form method="post" enctype="multipart/form-data" style="margin-bottom:18px;">
            <?php wp_nonce_field('erp_omd_import_ksef_cost_xml'); ?>
            <input type="hidden" name="erp_omd_action" value="import_ksef_cost_xml" />
            <p>
                <label for="ksef-cost-xml-files"><?php esc_html_e('Wybierz plik/pliki XML (import zbiorowy)', 'erp-omd'); ?></label><br />
                <input id="ksef-cost-xml-files" type="file" name="ksef_cost_xml_files[]" accept=".xml,text/xml,application/xml" multiple />
            </p>
            <p>
                <label for="ksef-cost-xml-content"><?php esc_html_e('Lub wklej XML kosztowy (opcjonalnie)', 'erp-omd'); ?></label><br />
                <textarea id="ksef-cost-xml-content" name="ksef_cost_xml_content" rows="4" class="large-text"></textarea>
            </p>
            <p><button type="submit" class="button button-primary"><?php esc_html_e('Importuj XML kosztowy', 'erp-omd'); ?></button></p>
        </form>
        <form method="post">
            <?php wp_nonce_field('erp_omd_bulk_ksef_cost_invoices'); ?>
            <input type="hidden" name="erp_omd_action" value="bulk_ksef_cost_invoices" />
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="bulk_action">
                        <option value=""><?php esc_html_e('Akcje masowe', 'erp-omd'); ?></option>
                        <option value="status_nieistotne"><?php esc_html_e('Status: nieistotne', 'erp-omd'); ?></option>
                        <option value="delete"><?php esc_html_e('Usuń', 'erp-omd'); ?></option>
                    </select>
                    <button class="button action" type="submit"><?php esc_html_e('Zastosuj', 'erp-omd'); ?></button>
                </div>
            </div>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><input type="checkbox" id="ksef-cost-invoice-select-all" /></th>
                    <th>ID</th>
                    <th><?php esc_html_e('Numer', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('NIP sprzedawcy', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Dostawca', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Project ID', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Brutto', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Status', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Ref KSeF', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Pozycje / stawki VAT', 'erp-omd'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($ksef_cost_invoices)) : ?>
                <tr><td colspan="10"><?php esc_html_e('Brak zaimportowanych kosztowych dokumentów KSeF.', 'erp-omd'); ?></td></tr>
            <?php else : ?>
                <?php foreach ((array) $ksef_cost_invoices as $ksef_cost_row) : ?>
                    <?php $ksef_invoice_id = (int) ($ksef_cost_row['id'] ?? 0); ?>
                    <?php $ksef_items = (array) ($ksef_cost_items_by_invoice_id[$ksef_invoice_id] ?? []); ?>
                    <tr>
                        <td><input type="checkbox" class="ksef-cost-invoice-checkbox" name="cost_invoice_ids[]" value="<?php echo esc_attr((string) $ksef_invoice_id); ?>" /></td>
                        <td><?php echo esc_html((string) $ksef_invoice_id); ?></td>
                        <td><?php echo esc_html((string) ($ksef_cost_row['invoice_number'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($supplier_nip_by_id[(int) ($ksef_cost_row['supplier_id'] ?? 0)] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($supplier_name_by_id[(int) ($ksef_cost_row['supplier_id'] ?? 0)] ?? ('#' . (int) ($ksef_cost_row['supplier_id'] ?? 0)))); ?></td>
                        <td><?php echo esc_html((string) ((int) ($ksef_cost_row['project_id'] ?? 0))); ?></td>
                        <td><?php echo esc_html(number_format((float) ($ksef_cost_row['gross_amount'] ?? 0), 2, '.', ' ')); ?></td>
                        <td><?php echo esc_html((string) ($ksef_cost_row['status'] ?? '')); ?></td>
                        <td><code><?php echo esc_html((string) ($ksef_cost_row['ksef_reference_number'] ?? '')); ?></code></td>
                        <td>
                            <?php if ($ksef_items === []) : ?>
                                <span>—</span>
                            <?php else : ?>
                                <ul style="margin:0;padding-left:18px;">
                                    <?php foreach ($ksef_items as $ksef_item_row) : ?>
                                        <li>
                                            <?php
                                            $item_line_no = (int) ($ksef_item_row['line_no'] ?? 0);
                                            $item_name = (string) ($ksef_item_row['item_name'] ?? '');
                                            $item_net = (float) ($ksef_item_row['net_amount'] ?? 0);
                                            $item_vat_rate = (float) ($ksef_item_row['vat_rate'] ?? 0);
                                            $item_vat = (float) ($ksef_item_row['vat_amount'] ?? 0);
                                            $item_gross = (float) ($ksef_item_row['gross_amount'] ?? 0);
                                            ?>
                                            <strong><?php echo esc_html('#' . $item_line_no); ?></strong>
                                            <?php if ($item_name !== '') : ?>
                                                — <?php echo esc_html($item_name); ?>
                                            <?php endif; ?>
                                            <br />
                                            <small>
                                                <?php
                                                echo esc_html(
                                                    sprintf(
                                                        'netto: %s | VAT %s%%: %s | brutto: %s',
                                                        number_format($item_net, 2, '.', ' '),
                                                        number_format($item_vat_rate, 2, '.', ' '),
                                                        number_format($item_vat, 2, '.', ' '),
                                                        number_format($item_gross, 2, '.', ' ')
                                                    )
                                                );
                                                ?>
                                            </small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        </form>
    </section>
    <?php endif; ?>

    <?php if ($active_tab === 'relations') : ?>
    <section class="erp-omd-card">
        <h2><?php esc_html_e('Relacje projekt ↔ dostawca', 'erp-omd'); ?></h2>
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
    </section>
    <?php endif; ?>
    <script>
    (function () {
        var itemsTable = document.getElementById('cost-invoice-items-table');
        var netField = document.getElementById('cost_invoice_net_amount');
        var vatAmountField = document.getElementById('cost_invoice_vat_amount');
        var grossAmountField = document.getElementById('cost_invoice_gross_amount');
        var addItemButton = document.getElementById('cost-invoice-add-item');
        var selectAll = document.getElementById('cost-invoice-select-all');
        var ksefCostSelectAll = document.getElementById('ksef-cost-invoice-select-all');
        if (!netField || !vatAmountField || !grossAmountField) { return; }

        var recalculate = function () {
            if (!itemsTable) { return; }
            var totalNet = 0;
            var totalVat = 0;
            var totalGross = 0;
            itemsTable.querySelectorAll('tr.cost-invoice-item-row').forEach(function (row) {
                var netInput = row.querySelector('.cost-item-net');
                var rateInput = row.querySelector('.cost-item-vat-rate');
                var vatField = row.querySelector('.cost-item-vat');
                var grossField = row.querySelector('.cost-item-gross');
                var net = parseFloat((netInput && netInput.value) || '0');
                var rate = parseFloat((rateInput && rateInput.value) || '0');
                if (isNaN(net) || net < 0) { net = 0; }
                if (isNaN(rate) || rate < 0) { rate = 0; }
                var vat = Math.round((net * (rate / 100)) * 100) / 100;
                var gross = Math.round((net + vat) * 100) / 100;
                if (vatField) { vatField.value = vat.toFixed(2); }
                if (grossField) { grossField.value = gross.toFixed(2); }
                totalNet += net;
                totalVat += vat;
                totalGross += gross;
            });
            netField.value = totalNet.toFixed(2);
            vatAmountField.value = totalVat.toFixed(2);
            grossAmountField.value = totalGross.toFixed(2);
        };

        var bindRowEvents = function (row) {
            if (!row) { return; }
            row.querySelectorAll('input').forEach(function (input) {
                input.addEventListener('input', recalculate);
                input.addEventListener('change', recalculate);
            });
            var removeButton = row.querySelector('.cost-item-remove');
            if (removeButton) {
                removeButton.addEventListener('click', function () {
                    row.remove();
                    recalculate();
                });
            }
        };

        if (itemsTable) {
            itemsTable.querySelectorAll('tr.cost-invoice-item-row').forEach(bindRowEvents);
        }

        if (addItemButton && itemsTable) {
            addItemButton.addEventListener('click', function () {
                var body = itemsTable.querySelector('tbody');
                var index = body.querySelectorAll('tr.cost-invoice-item-row').length;
                var row = document.createElement('tr');
                row.className = 'cost-invoice-item-row';
                row.innerHTML = '<td><input type="number" min="1" name="cost_invoice_items[' + index + '][line_no]" value="' + (index + 1) + '" class="small-text cost-item-line-no" /></td>' +
                    '<td><input type="text" name="cost_invoice_items[' + index + '][name]" value="" class="regular-text cost-item-name" /></td>' +
                    '<td><input type="number" step="0.001" min="0" name="cost_invoice_items[' + index + '][qty]" value="1" class="small-text cost-item-qty" /></td>' +
                    '<td><input type="text" name="cost_invoice_items[' + index + '][unit]" value="szt" class="small-text cost-item-unit" /></td>' +
                    '<td><input type="number" step="0.01" min="0" name="cost_invoice_items[' + index + '][net_amount]" value="0" class="small-text cost-item-net" /></td>' +
                    '<td><input type="number" step="0.01" min="0" name="cost_invoice_items[' + index + '][vat_rate]" value="23" class="small-text cost-item-vat-rate" /></td>' +
                    '<td><input type="number" step="0.01" min="0" value="0" class="small-text cost-item-vat" readonly /></td>' +
                    '<td><input type="number" step="0.01" min="0" value="0" class="small-text cost-item-gross" readonly /></td>' +
                    '<td><button type="button" class="button-link-delete cost-item-remove">Usuń</button></td>';
                body.appendChild(row);
                bindRowEvents(row);
                recalculate();
            });
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.cost-invoice-checkbox').forEach(function (checkbox) {
                    checkbox.checked = !!selectAll.checked;
                });
            });
        }
        if (ksefCostSelectAll) {
            ksefCostSelectAll.addEventListener('change', function () {
                document.querySelectorAll('.ksef-cost-invoice-checkbox').forEach(function (checkbox) {
                    checkbox.checked = !!ksefCostSelectAll.checked;
                });
            });
        }

        recalculate();
    }());
    </script>


    <?php if ($active_tab === 'invoices' && $selected_invoice_id > 0) : ?>
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
