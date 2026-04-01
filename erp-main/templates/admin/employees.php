<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Pracownicy', 'erp-omd'); ?></h1>

    <div class="erp-omd-page-sections">
        <section class="erp-omd-card">
            <h2><?php echo $employee ? esc_html__('Edytuj pracownika', 'erp-omd') : esc_html__('Nowy pracownik', 'erp-omd'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('erp_omd_save_employee'); ?>
                <input type="hidden" name="erp_omd_action" value="save_employee" />
                <input type="hidden" name="id" value="<?php echo esc_attr($employee['id'] ?? ''); ?>" />
                <div class="erp-omd-form-sections">
                    <section class="erp-omd-form-section">
                        <div class="erp-omd-form-section-header">
                            <h3><?php esc_html_e('Konto i dostęp', 'erp-omd'); ?></h3>
                        </div>
                        <div class="erp-omd-form-grid erp-omd-form-grid-employee-account">
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="erp-user-id"><?php esc_html_e('Konto WordPress', 'erp-omd'); ?></label>
                                <select id="erp-user-id" name="user_id" required>
                                    <option value=""><?php esc_html_e('Wybierz konto', 'erp-omd'); ?></option>
                                    <?php foreach ($users as $user) : ?>
                                        <option value="<?php echo esc_attr($user->ID); ?>" <?php selected((int) ($employee['user_id'] ?? 0), (int) $user->ID); ?>>
                                            <?php echo esc_html($user->user_login . ' (' . $user->user_email . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="erp-account-type"><?php esc_html_e('Typ konta', 'erp-omd'); ?></label>
                                <select id="erp-account-type" name="account_type">
                                    <option value="admin" <?php selected($employee['account_type'] ?? 'worker', 'admin'); ?>><?php esc_html_e('Administrator', 'erp-omd'); ?></option>
                                    <option value="manager" <?php selected($employee['account_type'] ?? 'worker', 'manager'); ?>><?php esc_html_e('Manager', 'erp-omd'); ?></option>
                                    <option value="worker" <?php selected($employee['account_type'] ?? 'worker', 'worker'); ?>><?php esc_html_e('Pracownik', 'erp-omd'); ?></option>
                                </select>
                            </div>
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="erp-status"><?php esc_html_e('Status', 'erp-omd'); ?></label>
                                <select id="erp-status" name="status">
                                    <option value="active" <?php selected($employee['status'] ?? 'active', 'active'); ?>><?php esc_html_e('Aktywny', 'erp-omd'); ?></option>
                                    <option value="inactive" <?php selected($employee['status'] ?? '', 'inactive'); ?>><?php esc_html_e('Nieaktywny', 'erp-omd'); ?></option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="erp-omd-form-section">
                        <div class="erp-omd-form-section-header">
                            <h3><?php esc_html_e('Role projektowe', 'erp-omd'); ?></h3>
                        </div>
                        <div class="erp-omd-form-grid">
                            <div class="erp-omd-form-field erp-omd-form-field-span-2">
                                <label for="erp-role-ids"><?php esc_html_e('Role projektowe', 'erp-omd'); ?></label>
                                <select id="erp-role-ids" name="role_ids[]" multiple size="6" class="erp-omd-multiselect">
                                    <?php foreach ($roles as $role_item) : ?>
                                        <option value="<?php echo esc_attr($role_item['id']); ?>" <?php selected(in_array((int) $role_item['id'], array_map('intval', $employee['role_ids'] ?? []), true)); ?>>
                                            <?php echo esc_html($role_item['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e('Wybierz jedną lub więcej ról raportowych.', 'erp-omd'); ?></p>
                            </div>
                            <div class="erp-omd-form-field">
                                <label for="erp-default-role"><?php esc_html_e('Domyślna rola', 'erp-omd'); ?></label>
                                <select id="erp-default-role" name="default_role_id">
                                    <option value="0"><?php esc_html_e('Brak', 'erp-omd'); ?></option>
                                    <?php foreach ($roles as $role_item) : ?>
                                        <option value="<?php echo esc_attr($role_item['id']); ?>" <?php selected((int) ($employee['default_role_id'] ?? 0), (int) $role_item['id']); ?>>
                                            <?php echo esc_html($role_item['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="erp-omd-form-actions">
                    <?php submit_button($employee ? __('Zapisz pracownika', 'erp-omd') : __('Dodaj pracownika', 'erp-omd')); ?>
                </div>
            </form>

            <?php if ($employee) : ?>
                <hr />
                <div class="erp-omd-section-header">
                    <div>
                        <h2><?php esc_html_e('Zmiana hasła', 'erp-omd'); ?></h2>
                        <p class="description"><?php esc_html_e('Zmiana hasła dotyczy powiązanego konta WordPress tego pracownika.', 'erp-omd'); ?></p>
                    </div>
                </div>
                <form method="post">
                    <?php wp_nonce_field('erp_omd_change_employee_password'); ?>
                    <input type="hidden" name="erp_omd_action" value="change_employee_password" />
                    <input type="hidden" name="employee_id" value="<?php echo esc_attr($employee['id']); ?>" />
                    <div class="erp-omd-form-sections">
                        <section class="erp-omd-form-section">
                            <div class="erp-omd-form-grid">
                                <div class="erp-omd-form-field erp-omd-form-field-compact">
                                    <label for="erp-new-password"><?php esc_html_e('Nowe hasło', 'erp-omd'); ?></label>
                                    <input id="erp-new-password" type="password" name="new_password" minlength="8" required />
                                </div>
                                <div class="erp-omd-form-field erp-omd-form-field-compact">
                                    <label for="erp-new-password-confirm"><?php esc_html_e('Potwierdź nowe hasło', 'erp-omd'); ?></label>
                                    <input id="erp-new-password-confirm" type="password" name="new_password_confirm" minlength="8" required />
                                </div>
                            </div>
                        </section>
                    </div>
                    <div class="erp-omd-form-actions">
                        <?php submit_button(__('Zmień hasło pracownika', 'erp-omd'), 'secondary'); ?>
                    </div>
                </form>

                <hr />
                <div class="erp-omd-section-header">
                    <div>
                        <h2><?php esc_html_e('Historia wynagrodzeń', 'erp-omd'); ?></h2>
                        <p class="description"><?php printf(esc_html__('Podpowiedź godzin dla bieżącego miesiąca: %s h', 'erp-omd'), esc_html($suggested_hours)); ?></p>
                    </div>
                </div>
                <form method="post">
                    <?php wp_nonce_field('erp_omd_save_salary'); ?>
                    <input type="hidden" name="erp_omd_action" value="save_salary" />
                    <input type="hidden" name="employee_id" value="<?php echo esc_attr($employee['id']); ?>" />
                    <div class="erp-omd-form-sections">
                        <section class="erp-omd-form-section">
                            <div class="erp-omd-form-grid erp-omd-form-grid-employee-salary">
                                <div class="erp-omd-form-field erp-omd-form-field-compact">
                                    <label for="erp-monthly-salary"><?php esc_html_e('Pensja miesięczna', 'erp-omd'); ?></label>
                                    <input id="erp-monthly-salary" type="number" step="0.01" min="0" name="monthly_salary" required />
                                </div>
                                <div class="erp-omd-form-field erp-omd-form-field-compact">
                                    <label for="erp-monthly-hours"><?php esc_html_e('Godziny miesięczne', 'erp-omd'); ?></label>
                                    <input id="erp-monthly-hours" type="number" step="0.01" min="1" name="monthly_hours" value="<?php echo esc_attr($suggested_hours); ?>" required />
                                </div>
                                <div class="erp-omd-form-field erp-omd-form-field-compact">
                                    <label for="erp-valid-from"><?php esc_html_e('Obowiązuje od', 'erp-omd'); ?></label>
                                    <input id="erp-valid-from" type="date" name="valid_from" required />
                                </div>
                                <div class="erp-omd-form-field erp-omd-form-field-compact">
                                    <label for="erp-valid-to"><?php esc_html_e('Obowiązuje do', 'erp-omd'); ?></label>
                                    <input id="erp-valid-to" type="date" name="valid_to" />
                                </div>
                            </div>
                        </section>
                    </div>
                    <div class="erp-omd-form-actions">
                        <?php submit_button(__('Dodaj wpis do historii wynagrodzeń', 'erp-omd'), 'secondary'); ?>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <section class="erp-omd-card">
            <div class="erp-omd-section-header">
                <div>
                    <h2><?php esc_html_e('Lista pracowników', 'erp-omd'); ?></h2>
                    <p class="description"><?php printf(esc_html__('Metryki na liście dotyczą bieżącego miesiąca: %s.', 'erp-omd'), esc_html($reporting_month_label)); ?></p>
                </div>
            </div>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Login', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Typ konta', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Status', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Aktualna pensja', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Aktualna stawka godzinowa', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Zaraportowane godziny', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Godziny do wypracowania', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Koszt godzinowy', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Zysk z pracownika', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Data logowania', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Akcje', 'erp-omd'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)) : ?>
                        <tr><td colspan="12"><?php esc_html_e('Brak pracowników.', 'erp-omd'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($employees as $item) : ?>
                            <?php $employee_is_inactive = ($item['status'] ?? '') === 'inactive'; ?>
                            <?php $inline_form_id = 'erp-omd-inline-employee-' . (int) $item['id']; ?>
                            <tr>
                                <td><?php echo esc_html($item['id']); ?></td>
                                <td>
                                    <?php echo esc_html($item['user_login']); ?>
                                    <?php $this->render_alert_icons($item['alerts'] ?? []); ?>
                                </td>
                                <td>
                                    <select name="account_type" form="<?php echo esc_attr($inline_form_id); ?>">
                                        <option value="admin" <?php selected((string) ($item['account_type'] ?? 'worker'), 'admin'); ?>><?php esc_html_e('Administrator', 'erp-omd'); ?></option>
                                        <option value="manager" <?php selected((string) ($item['account_type'] ?? 'worker'), 'manager'); ?>><?php esc_html_e('Manager', 'erp-omd'); ?></option>
                                        <option value="worker" <?php selected((string) ($item['account_type'] ?? 'worker'), 'worker'); ?>><?php esc_html_e('Pracownik', 'erp-omd'); ?></option>
                                    </select>
                                </td>
                                <td>
                                    <select name="status" form="<?php echo esc_attr($inline_form_id); ?>">
                                        <option value="active" <?php selected((string) ($item['status'] ?? 'active'), 'active'); ?>><?php esc_html_e('Aktywny', 'erp-omd'); ?></option>
                                        <option value="inactive" <?php selected((string) ($item['status'] ?? 'active'), 'inactive'); ?>><?php esc_html_e('Nieaktywny', 'erp-omd'); ?></option>
                                    </select>
                                </td>
                                <td><?php echo ! empty($item['current_monthly_salary']) ? esc_html(number_format_i18n((float) $item['current_monthly_salary'], 2)) : '—'; ?></td>
                                <td><?php echo ! empty($item['current_hourly_cost']) ? esc_html(number_format_i18n((float) $item['current_hourly_cost'], 2)) : '—'; ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) ($item['reported_hours'] ?? 0), 2)); ?></td>
                                <td><?php echo null !== ($item['target_monthly_hours'] ?? null) ? esc_html(number_format_i18n((float) $item['target_monthly_hours'], 2)) : '—'; ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) ($item['hourly_cost_total'] ?? 0), 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) ($item['employee_profit'] ?? 0), 2)); ?></td>
                                <td><?php echo ! empty($item['last_login_at']) ? esc_html($item['last_login_at']) : '—'; ?></td>
                                <td>
                                    <details class="erp-omd-list-actions">
                                        <summary class="button button-small"><?php esc_html_e('Akcje', 'erp-omd'); ?></summary>
                                        <div class="erp-omd-list-actions-menu">
                                            <form method="post" id="<?php echo esc_attr($inline_form_id); ?>" class="erp-omd-inline-form">
                                                <?php wp_nonce_field('erp_omd_inline_employee_update'); ?>
                                                <input type="hidden" name="erp_omd_action" value="inline_update_employee" />
                                                <input type="hidden" name="id" value="<?php echo esc_attr($item['id']); ?>" />
                                                <button class="button button-small button-primary" type="submit"><?php esc_html_e('Zapisz inline', 'erp-omd'); ?></button>
                                            </form>
                                            <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-employees', 'id' => $item['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                            <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js($employee_is_inactive ? __('Aktywować pracownika?', 'erp-omd') : __('Dezaktywować pracownika?', 'erp-omd')); ?>');">
                                                <?php wp_nonce_field('erp_omd_toggle_employee_active'); ?>
                                                <input type="hidden" name="erp_omd_action" value="toggle_employee_active" />
                                                <input type="hidden" name="id" value="<?php echo esc_attr($item['id']); ?>" />
                                                <button class="button button-small" type="submit"><?php echo esc_html($employee_is_inactive ? __('Aktywuj', 'erp-omd') : __('Dezaktywuj', 'erp-omd')); ?></button>
                                            </form>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($employee) : ?>
                <hr />
                <h2><?php esc_html_e('Historia wynagrodzeń', 'erp-omd'); ?></h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Obowiązuje od', 'erp-omd'); ?></th>
                            <th><?php esc_html_e('Obowiązuje do', 'erp-omd'); ?></th>
                            <th><?php esc_html_e('Pensja', 'erp-omd'); ?></th>
                            <th><?php esc_html_e('Godziny', 'erp-omd'); ?></th>
                            <th><?php esc_html_e('Koszt godzinowy', 'erp-omd'); ?></th>
                            <th><?php esc_html_e('Akcje', 'erp-omd'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($salary_rows)) : ?>
                            <tr><td colspan="6"><?php esc_html_e('Brak wpisów w historii wynagrodzeń.', 'erp-omd'); ?></td></tr>
                        <?php else : ?>
                            <?php foreach ($salary_rows as $salary_item) : ?>
                                <tr>
                                    <td><?php echo esc_html($salary_item['valid_from']); ?></td>
                                    <td><?php echo esc_html($salary_item['valid_to'] ?: '—'); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((float) $salary_item['monthly_salary'], 2)); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((float) $salary_item['monthly_hours'], 2)); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((float) $salary_item['hourly_cost'], 2)); ?></td>
                                    <td>
                                        <details class="erp-omd-list-actions">
                                            <summary class="button button-small"><?php esc_html_e('Akcje', 'erp-omd'); ?></summary>
                                            <div class="erp-omd-list-actions-menu">
                                                <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Usunąć wpis z historii wynagrodzeń?', 'erp-omd')); ?>');">
                                                    <?php wp_nonce_field('erp_omd_delete_salary'); ?>
                                                    <input type="hidden" name="erp_omd_action" value="delete_salary" />
                                                    <input type="hidden" name="salary_id" value="<?php echo esc_attr($salary_item['id']); ?>" />
                                                    <input type="hidden" name="employee_id" value="<?php echo esc_attr($employee['id']); ?>" />
                                                    <button class="button button-small button-link-delete" type="submit"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                                </form>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>
</div>
