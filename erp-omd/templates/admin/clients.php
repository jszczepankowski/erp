<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Klienci', 'erp-omd'); ?></h1>
    <div class="erp-omd-card">
        <h2><?php echo $client ? esc_html__('Edytuj klienta', 'erp-omd') : esc_html__('Nowy klient', 'erp-omd'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('erp_omd_save_client'); ?>
            <input type="hidden" name="erp_omd_action" value="save_client" />
            <input type="hidden" name="id" value="<?php echo esc_attr($client['id'] ?? ''); ?>" />
            <table class="form-table">
                <tr><th><label for="client-name"><?php esc_html_e('Nazwa', 'erp-omd'); ?></label></th><td><input id="client-name" class="regular-text" type="text" name="name" value="<?php echo esc_attr($client['name'] ?? ''); ?>" required /></td></tr>
                <tr><th><label for="client-company"><?php esc_html_e('Firma', 'erp-omd'); ?></label></th><td><input id="client-company" class="regular-text" type="text" name="company" value="<?php echo esc_attr($client['company'] ?? ''); ?>" /></td></tr>
                <tr><th><label for="client-nip"><?php esc_html_e('NIP', 'erp-omd'); ?></label></th><td><input id="client-nip" class="regular-text" type="text" name="nip" value="<?php echo esc_attr($client['nip'] ?? ''); ?>" /></td></tr>
                <tr><th><label for="client-email"><?php esc_html_e('Email', 'erp-omd'); ?></label></th><td><input id="client-email" class="regular-text" type="email" name="email" value="<?php echo esc_attr($client['email'] ?? ''); ?>" /></td></tr>
                <tr><th><label for="client-phone"><?php esc_html_e('Telefon', 'erp-omd'); ?></label></th><td><input id="client-phone" class="regular-text" type="text" name="phone" value="<?php echo esc_attr($client['phone'] ?? ''); ?>" /></td></tr>
                <tr><th><label for="contact-person-name"><?php esc_html_e('Osoba kontaktowa — imię i nazwisko', 'erp-omd'); ?></label></th><td><input id="contact-person-name" class="regular-text" type="text" name="contact_person_name" value="<?php echo esc_attr($client['contact_person_name'] ?? ''); ?>" /></td></tr>
                <tr><th><label for="contact-person-email"><?php esc_html_e('Osoba kontaktowa — email', 'erp-omd'); ?></label></th><td><input id="contact-person-email" class="regular-text" type="email" name="contact_person_email" value="<?php echo esc_attr($client['contact_person_email'] ?? ''); ?>" /></td></tr>
                <tr><th><label for="contact-person-phone"><?php esc_html_e('Osoba kontaktowa — telefon', 'erp-omd'); ?></label></th><td><input id="contact-person-phone" class="regular-text" type="text" name="contact_person_phone" value="<?php echo esc_attr($client['contact_person_phone'] ?? ''); ?>" /></td></tr>
                <tr><th><label for="client-city"><?php esc_html_e('Miasto', 'erp-omd'); ?></label></th><td><input id="client-city" class="regular-text" type="text" name="city" value="<?php echo esc_attr($client['city'] ?? ''); ?>" /></td></tr>
                <tr>
                    <th><label for="client-status"><?php esc_html_e('Status', 'erp-omd'); ?></label></th>
                    <td>
                        <select id="client-status" name="status">
                            <option value="active" <?php selected($client['status'] ?? 'active', 'active'); ?>>active</option>
                            <option value="inactive" <?php selected($client['status'] ?? '', 'inactive'); ?>>inactive</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="client-account-manager"><?php esc_html_e('Account manager', 'erp-omd'); ?></label></th>
                    <td>
                        <select id="client-account-manager" name="account_manager_id">
                            <option value="0"><?php esc_html_e('Brak', 'erp-omd'); ?></option>
                            <?php foreach ($employees_for_select as $employee_item) : ?>
                                <option value="<?php echo esc_attr($employee_item['id']); ?>" <?php selected((int) ($client['account_manager_id'] ?? 0), (int) $employee_item['id']); ?>>
                                    <?php echo esc_html($employee_item['user_login'] . ' (' . $employee_item['account_type'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button($client ? __('Zapisz klienta', 'erp-omd') : __('Dodaj klienta', 'erp-omd')); ?>
        </form>

        <?php if ($client) : ?>
            <hr />
            <h2><?php esc_html_e('Stawki klienta', 'erp-omd'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('erp_omd_save_client_rate'); ?>
                <input type="hidden" name="erp_omd_action" value="save_client_rate" />
                <input type="hidden" name="client_id" value="<?php echo esc_attr($client['id']); ?>" />
                <table class="form-table">
                    <tr>
                        <th><label for="client-rate-role"><?php esc_html_e('Rola', 'erp-omd'); ?></label></th>
                        <td>
                            <select id="client-rate-role" name="role_id" required>
                                <option value=""><?php esc_html_e('Wybierz rolę', 'erp-omd'); ?></option>
                                <?php foreach ($roles as $role_item) : ?>
                                    <option value="<?php echo esc_attr($role_item['id']); ?>"><?php echo esc_html($role_item['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="client-rate-value"><?php esc_html_e('Stawka', 'erp-omd'); ?></label></th>
                        <td><input id="client-rate-value" type="number" step="0.01" min="0" name="rate" required /></td>
                    </tr>
                </table>
                <?php submit_button(__('Zapisz stawkę klienta', 'erp-omd'), 'secondary'); ?>
            </form>
        <?php endif; ?>
    </div>

    <div class="erp-omd-card">
        <h2><?php esc_html_e('Lista klientów', 'erp-omd'); ?></h2>
        <p class="description"><?php printf(esc_html__('Zysk na liście dotyczy bieżącego miesiąca: %s.', 'erp-omd'), esc_html($reporting_month_label)); ?></p>
        <table class="widefat striped">
                <thead><tr><th>ID</th><th><?php esc_html_e('Nazwa', 'erp-omd'); ?></th><th><?php esc_html_e('Firma', 'erp-omd'); ?></th><th><?php esc_html_e('Status', 'erp-omd'); ?></th><th><?php esc_html_e('Account manager', 'erp-omd'); ?></th><th><?php esc_html_e('Zysk', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje', 'erp-omd'); ?></th></tr></thead>
                <tbody>
                <?php if (empty($clients)) : ?>
                    <tr><td colspan="7"><?php esc_html_e('Brak klientów.', 'erp-omd'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($clients as $client_row) : ?>
                        <tr>
                            <td><?php echo esc_html($client_row['id']); ?></td>
                            <td><?php echo esc_html($client_row['name']); ?></td>
                            <td><?php echo esc_html($client_row['company']); ?></td>
                            <td><?php echo esc_html($client_row['status']); ?></td>
                            <td><?php echo esc_html($client_row['account_manager_login'] ?: '—'); ?></td>
                            <td><?php echo esc_html(number_format_i18n((float) ($client_row['monthly_profit'] ?? 0), 2)); ?></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-clients', 'id' => $client_row['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Dezaktywować klienta?', 'erp-omd')); ?>');">
                                    <?php wp_nonce_field('erp_omd_deactivate_client'); ?>
                                    <input type="hidden" name="erp_omd_action" value="deactivate_client" />
                                    <input type="hidden" name="id" value="<?php echo esc_attr($client_row['id']); ?>" />
                                    <button class="button button-small" type="submit"><?php esc_html_e('Deactivate', 'erp-omd'); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

        <?php if ($client) : ?>
            <hr />
            <h2><?php esc_html_e('Stawki klienta — lista', 'erp-omd'); ?></h2>
            <table class="widefat striped">
                <thead><tr><th><?php esc_html_e('Rola', 'erp-omd'); ?></th><th><?php esc_html_e('Stawka', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje', 'erp-omd'); ?></th></tr></thead>
                <tbody>
                <?php if (empty($client_rates)) : ?>
                    <tr><td colspan="3"><?php esc_html_e('Brak stawek klienta.', 'erp-omd'); ?></td></tr>
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
                                    <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Usunąć stawkę klienta?', 'erp-omd')); ?>');">
                                        <?php wp_nonce_field('erp_omd_delete_client_rate'); ?>
                                        <input type="hidden" name="erp_omd_action" value="delete_client_rate" />
                                        <input type="hidden" name="id" value="<?php echo esc_attr($rate_id); ?>" />
                                        <input type="hidden" name="client_id" value="<?php echo esc_attr($client['id'] ?? 0); ?>" />
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
    </div>
</div>
