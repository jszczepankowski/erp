<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Klienci', 'erp-omd'); ?></h1>

    <section class="erp-omd-card">
            <h2><?php echo $client ? esc_html__('Edytuj klienta', 'erp-omd') : esc_html__('Nowy klient', 'erp-omd'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('erp_omd_save_client'); ?>
                <input type="hidden" name="erp_omd_action" value="save_client" />
                <input type="hidden" name="id" value="<?php echo esc_attr($client['id'] ?? ''); ?>" />
                <table class="form-table">
                    <tr><th colspan="2"><h3 class="erp-omd-form-section-title"><?php esc_html_e('Podstawy', 'erp-omd'); ?></h3></th></tr>
                    <tr><th><label for="client-name"><?php esc_html_e('Nazwa', 'erp-omd'); ?></label></th><td><input id="client-name" class="regular-text" type="text" name="name" value="<?php echo esc_attr($client['name'] ?? ''); ?>" required /></td></tr>
                    <tr><th><label for="client-company"><?php esc_html_e('Firma', 'erp-omd'); ?></label></th><td><input id="client-company" class="regular-text" type="text" name="company" value="<?php echo esc_attr($client['company'] ?? ''); ?>" /></td></tr>
                    <tr><th><label for="client-nip"><?php esc_html_e('NIP', 'erp-omd'); ?></label></th><td><input id="client-nip" class="regular-text" type="text" name="nip" value="<?php echo esc_attr($client['nip'] ?? ''); ?>" /></td></tr>
                    <tr><th colspan="2"><h3 class="erp-omd-form-section-title"><?php esc_html_e('Adres i kontakt', 'erp-omd'); ?></h3></th></tr>
                    <tr><th><label for="client-street"><?php esc_html_e('Ulica', 'erp-omd'); ?></label></th><td><input id="client-street" class="regular-text" type="text" name="street" value="<?php echo esc_attr($client['street'] ?? ''); ?>" /></td></tr>
                    <tr><th><label for="client-apartment-number"><?php esc_html_e('Numer lokalu', 'erp-omd'); ?></label></th><td><input id="client-apartment-number" class="regular-text" type="text" name="apartment_number" value="<?php echo esc_attr($client['apartment_number'] ?? ''); ?>" /></td></tr>
                    <tr><th><label for="client-city"><?php esc_html_e('Miasto', 'erp-omd'); ?></label></th><td><input id="client-city" class="regular-text" type="text" name="city" value="<?php echo esc_attr($client['city'] ?? ''); ?>" /></td></tr>
                    <tr><th><label for="client-postal-code"><?php esc_html_e('Kod pocztowy', 'erp-omd'); ?></label></th><td><input id="client-postal-code" class="regular-text" type="text" name="postal_code" value="<?php echo esc_attr($client['postal_code'] ?? ''); ?>" placeholder="00-000" /></td></tr>
                    <tr><th><label for="client-country"><?php esc_html_e('Kraj (ISO)', 'erp-omd'); ?></label></th><td><input id="client-country" class="small-text" type="text" name="country" value="<?php echo esc_attr($client['country'] ?? 'PL'); ?>" maxlength="2" /></td></tr>
                    <tr><th><label for="client-email"><?php esc_html_e('Email', 'erp-omd'); ?></label></th><td><input id="client-email" class="regular-text" type="email" name="email" value="<?php echo esc_attr($client['email'] ?? ''); ?>" /></td></tr>
                    <tr><th><label for="client-phone"><?php esc_html_e('Telefon', 'erp-omd'); ?></label></th><td><input id="client-phone" class="regular-text" type="text" name="phone" value="<?php echo esc_attr($client['phone'] ?? ''); ?>" /></td></tr>
                    <tr><th><label for="contact-person-name"><?php esc_html_e('Osoba kontaktowa — imię i nazwisko', 'erp-omd'); ?></label></th><td><input id="contact-person-name" class="regular-text" type="text" name="contact_person_name" value="<?php echo esc_attr($client['contact_person_name'] ?? ''); ?>" /></td></tr>
                    <tr><th><label for="contact-person-email"><?php esc_html_e('Osoba kontaktowa — email', 'erp-omd'); ?></label></th><td><input id="contact-person-email" class="regular-text" type="email" name="contact_person_email" value="<?php echo esc_attr($client['contact_person_email'] ?? ''); ?>" /></td></tr>
                    <tr><th><label for="contact-person-phone"><?php esc_html_e('Osoba kontaktowa — telefon', 'erp-omd'); ?></label></th><td><input id="contact-person-phone" class="regular-text" type="text" name="contact_person_phone" value="<?php echo esc_attr($client['contact_person_phone'] ?? ''); ?>" /></td></tr>
                    <tr><th colspan="2"><h3 class="erp-omd-form-section-title"><?php esc_html_e('Lifecycle i odpowiedzialność', 'erp-omd'); ?></h3></th></tr>
                    <tr>
                        <th><label for="client-account-manager"><?php esc_html_e('Opiekun klienta', 'erp-omd'); ?></label></th>
                        <td>
                            <select id="client-account-manager" name="account_manager_id">
                                <option value="0"><?php esc_html_e('Brak', 'erp-omd'); ?></option>
                                <?php foreach ($employees_for_select as $employee_item) : ?>
                                    <option value="<?php echo esc_attr($employee_item['id']); ?>" <?php selected((int) ($client['account_manager_id'] ?? 0), (int) $employee_item['id']); ?>>
                                        <?php echo esc_html($employee_item['user_login'] . ' (' . $this->account_type_label($employee_item['account_type']) . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="client-status"><?php esc_html_e('Status', 'erp-omd'); ?></label></th>
                        <td>
                            <select id="client-status" name="status">
                                <option value="active" <?php selected($client['status'] ?? 'active', 'active'); ?>><?php esc_html_e('Aktywny', 'erp-omd'); ?></option>
                                <option value="inactive" <?php selected($client['status'] ?? '', 'inactive'); ?>><?php esc_html_e('Nieaktywny', 'erp-omd'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr><th><label for="client-alert-threshold"><?php esc_html_e('Próg marży klienta (%)', 'erp-omd'); ?></label></th><td><input id="client-alert-threshold" type="number" step="0.01" min="0" name="alert_margin_threshold" value="<?php echo esc_attr($client['alert_margin_threshold'] ?? ''); ?>" /><p class="description"><?php esc_html_e('Opcjonalnie nadpisuje globalny próg alertu niskiej marży dla projektów klienta.', 'erp-omd'); ?></p></td></tr>
                </table>
                <?php submit_button($client ? __('Zapisz klienta', 'erp-omd') : __('Dodaj klienta', 'erp-omd')); ?>
                <?php if ($client) : ?>
                    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=erp-omd-clients&id=' . (int) $client['id'])); ?>"><?php esc_html_e('Przejdź do szczegółów', 'erp-omd'); ?></a>
                <?php endif; ?>
            </form>

            <?php if ($selected_client) : ?>
                <hr />
                <div class="erp-omd-detail-grid">
                    <div class="erp-omd-detail-card">
                        <h3><?php esc_html_e('Widok 360° klienta', 'erp-omd'); ?></h3>
                        <p><strong><?php esc_html_e('Nazwa:', 'erp-omd'); ?></strong> <?php echo esc_html($selected_client['name'] ?? '—'); ?></p>
                        <p><strong><?php esc_html_e('Firma:', 'erp-omd'); ?></strong> <?php echo esc_html($selected_client['company'] ?? '—'); ?></p>
                        <p><strong><?php esc_html_e('Adres:', 'erp-omd'); ?></strong> <?php echo esc_html(trim(($selected_client['street'] ?? '') . ' ' . ($selected_client['apartment_number'] ?? '') . ', ' . ($selected_client['postal_code'] ?? '') . ' ' . ($selected_client['city'] ?? '') . ', ' . ($selected_client['country'] ?? '')) ?: '—'); ?></p>
                        <p><strong><?php esc_html_e('Status:', 'erp-omd'); ?></strong> <span class="erp-omd-badge <?php echo esc_attr($this->status_badge_class($selected_client['status'] ?? 'active', 'active')); ?>"><?php echo esc_html($this->active_status_label($selected_client['status'] ?? 'active')); ?></span></p>
                    </div>
                    <div class="erp-omd-detail-card">
                        <h3><?php esc_html_e('Kontekst operacyjny', 'erp-omd'); ?></h3>
                        <p><strong><?php esc_html_e('Opiekun klienta:', 'erp-omd'); ?></strong> <?php echo esc_html($selected_client['account_manager_login'] ?? '—'); ?></p>
                        <p><strong><?php esc_html_e('Email:', 'erp-omd'); ?></strong> <?php echo esc_html($selected_client['email'] ?? '—'); ?></p>
                        <p><strong><?php esc_html_e('Telefon:', 'erp-omd'); ?></strong> <?php echo esc_html($selected_client['phone'] ?? '—'); ?></p>
                        <p><strong><?php esc_html_e('Kontakt główny:', 'erp-omd'); ?></strong> <?php echo esc_html($selected_client['contact_person_name'] ?? '—'); ?></p>
                        <p><strong><?php esc_html_e('Próg marży:', 'erp-omd'); ?></strong> <?php echo esc_html(($selected_client['alert_margin_threshold'] ?? '') !== '' && $selected_client['alert_margin_threshold'] !== null ? number_format_i18n((float) $selected_client['alert_margin_threshold'], 2) . '%' : '—'); ?></p>
                    </div>
                </div>
                <div class="erp-omd-section-header">
                    <h2><?php echo $editing_client_rate ? esc_html__('Edytuj stawkę klienta', 'erp-omd') : esc_html__('Stawki klienta', 'erp-omd'); ?></h2>
                    <?php if (! $client) : ?>
                        <a class="button button-secondary" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-clients', 'id' => (int) $selected_client['id'], 'edit' => 1], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj klienta', 'erp-omd'); ?></a>
                    <?php endif; ?>
                </div>
                <form method="post">
                    <?php wp_nonce_field('erp_omd_save_client_rate'); ?>
                    <input type="hidden" name="erp_omd_action" value="save_client_rate" />
                    <input type="hidden" name="rate_id" value="<?php echo esc_attr($editing_client_rate['id'] ?? 0); ?>" />
                    <input type="hidden" name="client_id" value="<?php echo esc_attr($selected_client['id']); ?>" />
                    <table class="form-table">
                        <tr>
                            <th><label for="client-rate-role"><?php esc_html_e('Rola', 'erp-omd'); ?></label></th>
                            <td>
                                <?php if ($editing_client_rate) : ?>
                                    <input type="hidden" name="role_id" value="<?php echo esc_attr($editing_client_rate['role_id']); ?>" />
                                    <p class="description">
                                        <?php
                                        $editing_role_name = '—';
                                        foreach ($roles as $role_item) {
                                            if ((int) $role_item['id'] === (int) $editing_client_rate['role_id']) {
                                                $editing_role_name = $role_item['name'];
                                                break;
                                            }
                                        }
                                        echo esc_html(sprintf(__('Edytowana rola: %s', 'erp-omd'), $editing_role_name));
                                        ?>
                                    </p>
                                <?php else : ?>
                                    <select id="client-rate-role" name="role_id" required>
                                        <option value=""><?php esc_html_e('Wybierz rolę', 'erp-omd'); ?></option>
                                        <?php foreach ($roles as $role_item) : ?>
                                            <option value="<?php echo esc_attr($role_item['id']); ?>"><?php echo esc_html($role_item['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="client-rate-value"><?php esc_html_e('Stawka', 'erp-omd'); ?></label></th>
                            <td><input id="client-rate-value" type="number" step="0.01" min="0" name="rate" value="<?php echo esc_attr($editing_client_rate['rate'] ?? ''); ?>" required /></td>
                        </tr>
                    </table>
                    <?php submit_button($editing_client_rate ? __('Zapisz zmiany stawki', 'erp-omd') : __('Zapisz stawkę klienta', 'erp-omd'), 'secondary'); ?>
                    <?php if ($editing_client_rate) : ?>
                        <a class="button" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-clients', 'id' => (int) $selected_client['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Anuluj edycję', 'erp-omd'); ?></a>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
    </section>

    <section class="erp-omd-card">
            <h2><?php esc_html_e('Lista klientów', 'erp-omd'); ?></h2>
            <form method="get" class="erp-omd-filter-form">
                <input type="hidden" name="page" value="erp-omd-clients" />
                <input type="search" name="search" class="regular-text" placeholder="<?php echo esc_attr__('Szukaj klienta, firmy, NIP, email…', 'erp-omd'); ?>" value="<?php echo esc_attr($client_filters['search'] ?? ''); ?>" />
                <select name="status">
                    <option value=""><?php esc_html_e('Wszystkie statusy', 'erp-omd'); ?></option>
                    <option value="active" <?php selected($client_filters['status'] ?? '', 'active'); ?>><?php esc_html_e('Aktywny', 'erp-omd'); ?></option>
                    <option value="inactive" <?php selected($client_filters['status'] ?? '', 'inactive'); ?>><?php esc_html_e('Nieaktywny', 'erp-omd'); ?></option>
                </select>
                <button class="button" type="submit"><?php esc_html_e('Filtruj', 'erp-omd'); ?></button>
            </form>
            <form method="post">
            <?php wp_nonce_field('erp_omd_bulk_clients'); ?>
            <input type="hidden" name="erp_omd_action" value="bulk_clients" />
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="bulk_action">
                        <option value=""><?php esc_html_e('Akcje masowe', 'erp-omd'); ?></option>
                        <option value="activate"><?php esc_html_e('Aktywuj', 'erp-omd'); ?></option>
                        <option value="deactivate"><?php esc_html_e('Dezaktywuj', 'erp-omd'); ?></option>
                    </select>
                    <button class="button action" type="submit"><?php esc_html_e('Zastosuj', 'erp-omd'); ?></button>
                </div>
            </div>
            <table class="widefat striped">
                <thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.erp-omd-client-checkbox').forEach(function(checkbox){ checkbox.checked = this.checked; }.bind(this));" /></th><th>ID</th><th><?php esc_html_e('Nazwa', 'erp-omd'); ?></th><th><?php esc_html_e('Firma', 'erp-omd'); ?></th><th><?php esc_html_e('Status', 'erp-omd'); ?></th><th><?php esc_html_e('Opiekun klienta', 'erp-omd'); ?></th><th><?php esc_html_e('Zysk', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje', 'erp-omd'); ?></th></tr></thead>
                <tbody>
                <?php if (empty($clients)) : ?>
                    <tr><td colspan="8"><?php esc_html_e('Brak klientów dla wybranych filtrów. Spróbuj zmienić kryteria albo dodaj nowego klienta.', 'erp-omd'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($clients as $client_row) : ?>
                        <tr>
                            <td><input class="erp-omd-client-checkbox" type="checkbox" name="client_ids[]" value="<?php echo esc_attr($client_row['id']); ?>" /></td>
                            <td><?php echo esc_html($client_row['id']); ?></td>
                            <td><?php echo esc_html($client_row['name']); ?><?php if (! empty($client_row['alerts'])) : ?><div class="erp-omd-badge-list"><?php foreach ($client_row['alerts'] as $client_alert) : ?><span class="erp-omd-badge erp-omd-badge-<?php echo esc_attr($client_alert['severity']); ?>"><?php echo esc_html($client_alert['message']); ?></span><?php endforeach; ?></div><?php endif; ?></td>
                            <td><?php echo esc_html($client_row['company']); ?></td>
                            <td><span class="erp-omd-badge <?php echo esc_attr($this->status_badge_class($client_row['status'], 'active')); ?>"><?php echo esc_html($this->active_status_label($client_row['status'])); ?></span></td>
                            <td><?php echo esc_html($client_row['account_manager_login'] ?: '—'); ?></td>
                            <td><?php echo esc_html(number_format_i18n((float) ($client_row['total_profit'] ?? 0), 2)); ?></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-clients', 'id' => (int) $client_row['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Szczegóły', 'erp-omd'); ?></a>
                                <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-clients', 'id' => (int) $client_row['id'], 'edit' => 1], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                <?php $client_is_inactive = ($client_row['status'] ?? '') === 'inactive'; ?>
                                <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js($client_is_inactive ? __('Aktywować klienta?', 'erp-omd') : __('Dezaktywować klienta?', 'erp-omd')); ?>');">
                                    <?php wp_nonce_field('erp_omd_toggle_client_active'); ?>
                                    <input type="hidden" name="erp_omd_action" value="toggle_client_active" />
                                    <input type="hidden" name="id" value="<?php echo esc_attr($client_row['id']); ?>" />
                                    <button class="button button-small" type="submit"><?php echo esc_html($client_is_inactive ? __('Aktywuj', 'erp-omd') : __('Dezaktywuj', 'erp-omd')); ?></button>
                                </form>
                                <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Usunąć klienta? Operacja usunie też jego projekty i dane powiązane.', 'erp-omd')); ?>');">
                                    <?php wp_nonce_field('erp_omd_delete_client'); ?>
                                    <input type="hidden" name="erp_omd_action" value="delete_client" />
                                    <input type="hidden" name="id" value="<?php echo esc_attr($client_row['id']); ?>" />
                                    <button class="button button-small button-link-delete" type="submit"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
            </form>

            <?php if ($selected_client) : ?>
                <hr />
                <h2><?php echo esc_html(sprintf(__('Stawki klienta — lista (%s)', 'erp-omd'), $selected_client['name'])); ?></h2>
                <table class="widefat striped">
                    <thead><tr><th><?php esc_html_e('Rola', 'erp-omd'); ?></th><th><?php esc_html_e('Stawka', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje', 'erp-omd'); ?></th></tr></thead>
                    <tbody>
                    <?php if (empty($client_rates)) : ?>
                        <tr><td colspan="3"><?php esc_html_e('Brak stawek klienta. Dodaj pierwszą stawkę, aby projekty mogły dziedziczyć wartości z poziomu klienta.', 'erp-omd'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($client_rates as $rate_item) : ?>
                            <?php
                            $rate_id = isset($rate_item['id']) ? (int) $rate_item['id'] : 0;
                            $role_name = $rate_item['role_name'] ?? '—';
                            $rate_value = isset($rate_item['rate']) ? (float) $rate_item['rate'] : 0.0;
                            ?>
                            <tr>
                                <td><?php echo esc_html($role_name); ?></td>
                                <td><?php echo esc_html(number_format_i18n($rate_value, 2)); ?></td>
                                <td>
                                    <?php if ($rate_id > 0) : ?>
                                        <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-clients', 'id' => (int) $selected_client['id'], 'edit' => 1, 'rate_id' => $rate_id], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                        <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Usunąć stawkę klienta?', 'erp-omd')); ?>');">
                                            <?php wp_nonce_field('erp_omd_delete_client_rate'); ?>
                                            <input type="hidden" name="erp_omd_action" value="delete_client_rate" />
                                            <input type="hidden" name="id" value="<?php echo esc_attr($rate_id); ?>" />
                                            <input type="hidden" name="client_id" value="<?php echo esc_attr($selected_client['id']); ?>" />
                                            <button class="button button-small button-link-delete" type="submit"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                        </form>
                                    <?php else : ?>
                                        <span>—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
    </section>
</div>
