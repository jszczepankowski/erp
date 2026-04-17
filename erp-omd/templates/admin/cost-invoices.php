<?php
$supplier_form = is_array($selected_supplier ?? null) ? $selected_supplier : [];
$invoice_form = is_array($selected_invoice ?? null) ? $selected_invoice : [];
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
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-cost-invoices', 'tab' => 'ksef-moderation'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $active_tab === 'ksef-moderation' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Kolejka moderacji KSeF', 'erp-omd'); ?></a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-cost-invoices', 'tab' => 'ksef-sales'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $active_tab === 'ksef-sales' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Sprzedażowe KSeF', 'erp-omd'); ?></a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-cost-invoices', 'tab' => 'ksef-cost'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $active_tab === 'ksef-cost' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Kosztowe KSeF', 'erp-omd'); ?></a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-cost-invoices', 'tab' => 'relations'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $active_tab === 'relations' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Relacje projekt ↔ dostawca (E3)', 'erp-omd'); ?></a>
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
                                <select id="cost_invoice_project_id" name="cost_invoice_project_id" required>
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
                                        <?php foreach (['zaimportowana', 'weryfikacja', 'zatwierdzona', 'przypisana'] as $status) : ?>
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
                            <h3><?php esc_html_e('Kwoty', 'erp-omd'); ?></h3>
                            <p><?php esc_html_e('Netto + stawka VAT, a kwoty VAT/Brutto wyliczane automatycznie.', 'erp-omd'); ?></p>
                        </div>
                        <div class="erp-omd-form-grid erp-omd-form-grid-cost-invoice-amounts-row">
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="cost_invoice_net_amount"><?php esc_html_e('Netto', 'erp-omd'); ?></label>
                                <input type="number" step="0.01" min="0" id="cost_invoice_net_amount" name="cost_invoice_net_amount" value="<?php echo esc_attr((string) $invoice_form_net_amount); ?>" required />
                            </div>
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="cost_invoice_vat_rate"><?php esc_html_e('Stawka VAT', 'erp-omd'); ?></label>
                                <select id="cost_invoice_vat_rate" name="cost_invoice_vat_rate">
                                    <?php foreach (['23', '8', '5', '0', 'zw'] as $vat_rate_option) : ?>
                                        <option value="<?php echo esc_attr($vat_rate_option); ?>" <?php selected($invoice_form_vat_rate, $vat_rate_option); ?>><?php echo esc_html($vat_rate_option . (is_numeric($vat_rate_option) ? '%' : '')); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="cost_invoice_vat_amount"><?php esc_html_e('Kwota VAT (auto)', 'erp-omd'); ?></label>
                                <input type="number" step="0.01" min="0" id="cost_invoice_vat_amount" value="<?php echo esc_attr((string) $invoice_form_vat_amount); ?>" readonly />
                            </div>
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="cost_invoice_gross_amount"><?php esc_html_e('Brutto (auto)', 'erp-omd'); ?></label>
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
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th><th><?php esc_html_e('Numer', 'erp-omd'); ?></th><th><?php esc_html_e('Dostawca', 'erp-omd'); ?></th><th><?php esc_html_e('Projekt', 'erp-omd'); ?></th><th><?php esc_html_e('Ref KSeF', 'erp-omd'); ?></th><th><?php esc_html_e('Opis', 'erp-omd'); ?></th><th><?php esc_html_e('Status', 'erp-omd'); ?></th><th><?php esc_html_e('Brutto', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje', 'erp-omd'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($cost_invoices === []) : ?>
                        <tr><td colspan="9"><?php esc_html_e('Brak faktur kosztowych.', 'erp-omd'); ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($cost_invoices as $invoice) : ?>
                        <?php $invoice_id = (int) ($invoice['id'] ?? 0); ?>
                        <tr>
                            <td><?php echo esc_html((string) $invoice_id); ?></td>
                            <td><?php echo esc_html((string) ($invoice['invoice_number'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($supplier_name_by_id[(int) ($invoice['supplier_id'] ?? 0)] ?? ('#' . (int) ($invoice['supplier_id'] ?? 0)))); ?></td>
                            <td><?php echo esc_html((string) ($project_name_by_id[(int) ($invoice['project_id'] ?? 0)] ?? ('#' . (int) ($invoice['project_id'] ?? 0)))); ?></td>
                            <td><code><?php echo esc_html((string) ($invoice['ksef_reference_number'] ?? '')); ?></code></td>
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
    <section class="erp-omd-card">
        <h2><?php esc_html_e('KSeF — faktury sprzedażowe', 'erp-omd'); ?></h2>
        <form method="post" enctype="multipart/form-data" style="margin-bottom:14px;">
            <?php wp_nonce_field('erp_omd_import_ksef_sales_xml'); ?>
            <input type="hidden" name="erp_omd_action" value="import_ksef_sales_xml" />
            <p><label for="ksef-sales-xml"><?php esc_html_e('Manual import XML z KSeF', 'erp-omd'); ?></label></p>
            <textarea id="ksef-sales-xml" name="ksef_sales_xml_content" rows="8" class="large-text" placeholder="<?php esc_attr_e('<Fa>...</Fa>', 'erp-omd'); ?>"></textarea>
            <p>
                <label for="ksef-sales-xml-files"><?php esc_html_e('lub wybierz plik/pliki XML (import zbiorowy)', 'erp-omd'); ?></label><br />
                <input id="ksef-sales-xml-files" type="file" name="ksef_sales_xml_files[]" accept=".xml,text/xml,application/xml" multiple />
            </p>
            <p>
                <label for="ksef-sales-description"><?php esc_html_e('Opis faktury sprzedażowej (opcjonalny, dla importu manualnego)', 'erp-omd'); ?></label><br />
                <textarea id="ksef-sales-description" name="ksef_sales_description" rows="2" class="large-text"></textarea>
            </p>
            <p><button type="submit" class="button button-primary"><?php esc_html_e('Importuj XML sprzedażowy', 'erp-omd'); ?></button></p>
        </form>

        <table class="widefat striped">
            <thead><tr><th>ID</th><th><?php esc_html_e('Numer', 'erp-omd'); ?></th><th><?php esc_html_e('NIP nabywcy', 'erp-omd'); ?></th><th><?php esc_html_e('Opis', 'erp-omd'); ?></th><th><?php esc_html_e('Client ID', 'erp-omd'); ?></th><th><?php esc_html_e('Project ID', 'erp-omd'); ?></th><th><?php esc_html_e('Końcowa', 'erp-omd'); ?></th><th><?php esc_html_e('Status', 'erp-omd'); ?></th><th><?php esc_html_e('Akcja', 'erp-omd'); ?></th></tr></thead>
            <tbody>
            <?php if (empty($ksef_sales_inbox)) : ?>
                <tr><td colspan="9"><?php esc_html_e('Brak sprzedażowych dokumentów KSeF.', 'erp-omd'); ?></td></tr>
            <?php else : ?>
                <?php foreach ((array) $ksef_sales_inbox as $sales_row) : ?>
                    <tr>
                        <td><?php echo esc_html((string) ((int) ($sales_row['id'] ?? 0))); ?></td>
                        <td><?php echo esc_html((string) ($sales_row['invoice_number'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($sales_row['buyer_nip'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($sales_row['description'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ((int) ($sales_row['client_id'] ?? 0))); ?></td>
                        <td><?php echo esc_html((string) ((int) ($sales_row['project_id'] ?? 0))); ?></td>
                        <td><?php echo ((int) ($sales_row['is_final'] ?? 0) === 1) ? esc_html__('Tak', 'erp-omd') : esc_html__('Nie', 'erp-omd'); ?></td>
                        <td><?php echo esc_html((string) ($sales_row['status'] ?? '')); ?></td>
                        <td>
                            <form method="post" style="display:flex;gap:6px;align-items:center;">
                                <?php wp_nonce_field('erp_omd_attach_ksef_sales_invoice'); ?>
                                <input type="hidden" name="erp_omd_action" value="attach_ksef_sales_invoice" />
                                <input type="hidden" name="sales_id" value="<?php echo esc_attr((string) ((int) ($sales_row['id'] ?? 0))); ?>" />
                                <input type="number" min="1" name="project_id" value="<?php echo esc_attr((string) ((int) ($sales_row['project_id'] ?? 0))); ?>" placeholder="<?php esc_attr_e('project_id', 'erp-omd'); ?>" style="width:90px;" required />
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
            <p><label for="ksef-cost-xml"><?php esc_html_e('Manual import XML kosztowy z KSeF', 'erp-omd'); ?></label></p>
            <textarea id="ksef-cost-xml" name="ksef_cost_xml_content" rows="8" class="large-text" placeholder="<?php esc_attr_e('<Fa>...</Fa>', 'erp-omd'); ?>"></textarea>
            <p>
                <label for="ksef-cost-xml-files"><?php esc_html_e('lub wybierz plik/pliki XML (import zbiorowy)', 'erp-omd'); ?></label><br />
                <input id="ksef-cost-xml-files" type="file" name="ksef_cost_xml_files[]" accept=".xml,text/xml,application/xml" multiple />
            </p>
            <p><button type="submit" class="button button-primary"><?php esc_html_e('Importuj XML kosztowy', 'erp-omd'); ?></button></p>
        </form>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php esc_html_e('Numer', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('NIP sprzedawcy', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Dostawca', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Project ID', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Brutto', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Status', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Ref KSeF', 'erp-omd'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($ksef_cost_invoices)) : ?>
                <tr><td colspan="8"><?php esc_html_e('Brak zaimportowanych kosztowych dokumentów KSeF.', 'erp-omd'); ?></td></tr>
            <?php else : ?>
                <?php foreach ((array) $ksef_cost_invoices as $ksef_cost_row) : ?>
                    <tr>
                        <td><?php echo esc_html((string) ((int) ($ksef_cost_row['id'] ?? 0))); ?></td>
                        <td><?php echo esc_html((string) ($ksef_cost_row['invoice_number'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($supplier_nip_by_id[(int) ($ksef_cost_row['supplier_id'] ?? 0)] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($supplier_name_by_id[(int) ($ksef_cost_row['supplier_id'] ?? 0)] ?? ('#' . (int) ($ksef_cost_row['supplier_id'] ?? 0)))); ?></td>
                        <td><?php echo esc_html((string) ((int) ($ksef_cost_row['project_id'] ?? 0))); ?></td>
                        <td><?php echo esc_html(number_format((float) ($ksef_cost_row['gross_amount'] ?? 0), 2, '.', ' ')); ?></td>
                        <td><?php echo esc_html((string) ($ksef_cost_row['status'] ?? '')); ?></td>
                        <td><code><?php echo esc_html((string) ($ksef_cost_row['ksef_reference_number'] ?? '')); ?></code></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <?php if ($active_tab === 'relations') : ?>
    <section class="erp-omd-card">
        <h2><?php esc_html_e('Relacje projekt ↔ dostawca (E3)', 'erp-omd'); ?></h2>
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
