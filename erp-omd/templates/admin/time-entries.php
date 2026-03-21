<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Czas pracy', 'erp-omd'); ?></h1>

    <div class="erp-omd-page-sections">
        <section class="erp-omd-card">
            <h2><?php echo ($entry && $can_edit_selected_entry) ? esc_html__('Edytuj wpis czasu', 'erp-omd') : esc_html__('Nowy wpis czasu', 'erp-omd'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('erp_omd_save_time_entry'); ?>
                <input type="hidden" name="erp_omd_action" value="save_time_entry" />
                <input type="hidden" name="id" value="<?php echo esc_attr($entry['id'] ?? ''); ?>" />
                <p class="erp-omd-form-intro"><?php esc_html_e('Układ czasu pracy został uporządkowany sekcjami, dzięki czemu szybciej przechodzimy od kontekstu wpisu do akceptacji.', 'erp-omd'); ?></p>
                <div class="erp-omd-form-sections">
                    <section class="erp-omd-form-section">
                        <div class="erp-omd-form-section-header">
                            <h3><?php esc_html_e('Kontekst wpisu', 'erp-omd'); ?></h3>
                            <p><?php esc_html_e('Pracownik, klient, projekt i rola raportowana w jednym bloku.', 'erp-omd'); ?></p>
                        </div>
                        <div class="erp-omd-form-grid">
                            <div class="erp-omd-form-field">
                                <label for="time-employee"><?php esc_html_e('Pracownik', 'erp-omd'); ?></label>
                                <select id="time-employee" name="employee_id" <?php disabled(! $can_select_any_employee); ?>>
                                    <?php foreach ($employees_for_select as $employee_item) : ?>
                                        <option value="<?php echo esc_attr($employee_item['id']); ?>" <?php selected((int) ($selected_employee_id ?? 0), (int) $employee_item['id']); ?>>
                                            <?php echo esc_html($employee_item['user_login']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (! $can_select_any_employee) : ?>
                                    <input type="hidden" name="employee_id" value="<?php echo esc_attr($selected_employee_id); ?>" />
                                <?php endif; ?>
                            </div>
                            <div class="erp-omd-form-field">
                                <label for="time-client"><?php esc_html_e('Klient', 'erp-omd'); ?></label>
                                <select id="time-client" name="client_id" data-project-target="#time-project">
                                    <option value="0"><?php esc_html_e('Wybierz klienta', 'erp-omd'); ?></option>
                                    <?php foreach ($clients_for_time as $client_item) : ?>
                                        <option value="<?php echo esc_attr($client_item['id']); ?>" <?php selected((int) ($selected_time_client_id ?? 0), (int) $client_item['id']); ?>><?php echo esc_html($client_item['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="erp-omd-form-field">
                                <label for="time-project"><?php esc_html_e('Projekt', 'erp-omd'); ?></label>
                                <select id="time-project" name="project_id" required>
                                    <option value=""><?php esc_html_e('Wybierz projekt', 'erp-omd'); ?></option>
                                    <?php foreach ($projects_for_time as $project_item) : ?>
                                        <option value="<?php echo esc_attr($project_item['id']); ?>" data-client-id="<?php echo esc_attr($project_item['client_id']); ?>" <?php selected((int) ($entry['project_id'] ?? 0), (int) $project_item['id']); ?>><?php echo esc_html($project_item['name'] . ' [' . $this->project_status_label($project_item['status']) . ']'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="erp-omd-form-field">
                                <label for="time-role"><?php esc_html_e('Rola', 'erp-omd'); ?></label>
                                <select id="time-role" name="role_id" required>
                                    <?php foreach ($roles as $role_item) : ?>
                                        <option value="<?php echo esc_attr($role_item['id']); ?>" <?php selected((int) ($entry['role_id'] ?? 0), (int) $role_item['id']); ?>><?php echo esc_html($role_item['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="erp-omd-form-section">
                        <div class="erp-omd-form-section-header">
                            <h3><?php esc_html_e('Czas i status', 'erp-omd'); ?></h3>
                            <p><?php esc_html_e('Wartość wpisu, data wykonania i etap akceptacji.', 'erp-omd'); ?></p>
                        </div>
                        <div class="erp-omd-form-grid">
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="time-hours"><?php esc_html_e('Godziny', 'erp-omd'); ?></label>
                                <input id="time-hours" type="number" step="0.01" min="0.01" name="hours" value="<?php echo esc_attr($entry['hours'] ?? ''); ?>" required />
                                <div class="erp-omd-quick-hours" aria-label="<?php esc_attr_e('Szybkie ustawianie godzin', 'erp-omd'); ?>">
                                    <button type="button" class="button button-secondary erp-omd-quick-hours-button" data-target="#time-hours" data-hours="0.25"><?php esc_html_e('15 min', 'erp-omd'); ?></button>
                                    <button type="button" class="button button-secondary erp-omd-quick-hours-button" data-target="#time-hours" data-hours="0.50"><?php esc_html_e('30 min', 'erp-omd'); ?></button>
                                    <button type="button" class="button button-secondary erp-omd-quick-hours-button" data-target="#time-hours" data-hours="0.75"><?php esc_html_e('45 min', 'erp-omd'); ?></button>
                                </div>
                            </div>
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="time-entry-date"><?php esc_html_e('Data', 'erp-omd'); ?></label>
                                <input id="time-entry-date" type="date" name="entry_date" value="<?php echo esc_attr($entry['entry_date'] ?? gmdate('Y-m-d')); ?>" required />
                            </div>
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="time-status"><?php esc_html_e('Status', 'erp-omd'); ?></label>
                                <select id="time-status" name="status" <?php disabled(! $can_set_status); ?>>
                                    <?php foreach (['submitted', 'approved', 'rejected'] as $time_status) : ?>
                                        <option value="<?php echo esc_attr($time_status); ?>" <?php selected($entry['status'] ?? 'submitted', $time_status); ?>><?php echo esc_html($this->time_status_label($time_status)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (! $can_set_status) : ?><input type="hidden" name="status" value="<?php echo esc_attr($entry['status'] ?? 'submitted'); ?>" /><?php endif; ?>
                            </div>
                            <div class="erp-omd-form-field erp-omd-form-field-span-2">
                                <label for="time-description"><?php esc_html_e('Opis', 'erp-omd'); ?></label>
                                <textarea id="time-description" class="large-text" rows="4" name="description"><?php echo esc_textarea($entry['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="erp-omd-form-actions">
                    <?php submit_button(($entry && $can_edit_selected_entry) ? __('Zapisz wpis czasu', 'erp-omd') : __('Dodaj wpis czasu', 'erp-omd')); ?>
                </div>
            </form>
        </section>

        <section class="erp-omd-card">
            <div class="erp-omd-section-header">
                <div>
                    <h2><?php esc_html_e('Lista wpisów czasu', 'erp-omd'); ?></h2>
                    <p class="description"><?php esc_html_e('Zapisane widoki zostały usunięte globalnie — zostawiamy prostsze, jednorazowe filtrowanie.', 'erp-omd'); ?></p>
                </div>
            </div>
            <form method="get" class="erp-omd-filter-form">
                <input type="hidden" name="page" value="erp-omd-time" />
                <input type="date" name="entry_date" value="<?php echo esc_attr($filters['entry_date'] ?? ''); ?>" />
                <?php if ($can_select_any_employee) : ?>
                    <select name="employee_id"><option value=""><?php esc_html_e('Wszyscy pracownicy', 'erp-omd'); ?></option><?php foreach ($employees_for_select as $employee_item) : ?><option value="<?php echo esc_attr($employee_item['id']); ?>" <?php selected((string) ($filters['employee_id'] ?? ''), (string) $employee_item['id']); ?>><?php echo esc_html($employee_item['user_login']); ?></option><?php endforeach; ?></select>
                <?php endif; ?>
                <select id="time-filter-client" name="client_id" data-project-target="#time-filter-project"><option value=""><?php esc_html_e('Wszyscy klienci', 'erp-omd'); ?></option><?php foreach ($clients_for_time as $client_item) : ?><option value="<?php echo esc_attr($client_item['id']); ?>" <?php selected((string) ($filters['client_id'] ?? ''), (string) $client_item['id']); ?>><?php echo esc_html($client_item['name']); ?></option><?php endforeach; ?></select>
                <select id="time-filter-project" name="project_id"><option value=""><?php esc_html_e('Wszystkie projekty', 'erp-omd'); ?></option><?php foreach ($projects_for_time as $project_item) : ?><option value="<?php echo esc_attr($project_item['id']); ?>" data-client-id="<?php echo esc_attr($project_item['client_id']); ?>" <?php selected((string) ($filters['project_id'] ?? ''), (string) $project_item['id']); ?>><?php echo esc_html($project_item['name']); ?></option><?php endforeach; ?></select>
                <select name="status"><option value=""><?php esc_html_e('Wszystkie statusy', 'erp-omd'); ?></option><?php foreach (['submitted', 'approved', 'rejected'] as $time_status) : ?><option value="<?php echo esc_attr($time_status); ?>" <?php selected((string) ($filters['status'] ?? ''), $time_status); ?>><?php echo esc_html($this->time_status_label($time_status)); ?></option><?php endforeach; ?></select>
                <button class="button" type="submit"><?php esc_html_e('Filtruj', 'erp-omd'); ?></button>
            </form>
            <form method="post">
                <?php wp_nonce_field('erp_omd_bulk_time_entries'); ?>
                <input type="hidden" name="erp_omd_action" value="bulk_time_entries" />
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <label class="screen-reader-text" for="bulk-time-action"><?php esc_html_e('Akcja masowa', 'erp-omd'); ?></label>
                        <select id="bulk-time-action" name="bulk_action">
                            <option value=""><?php esc_html_e('Akcje masowe', 'erp-omd'); ?></option>
                            <?php if (current_user_can('administrator') || current_user_can('erp_omd_approve_time')) : ?>
                                <option value="submitted"><?php esc_html_e('Ustaw status: Zgłoszony', 'erp-omd'); ?></option>
                                <option value="approved"><?php esc_html_e('Ustaw status: Zaakceptowany', 'erp-omd'); ?></option>
                                <option value="rejected"><?php esc_html_e('Ustaw status: Odrzucony', 'erp-omd'); ?></option>
                            <?php endif; ?>
                            <?php if ($can_delete_entries) : ?>
                                <option value="delete"><?php esc_html_e('Usuń wpisy', 'erp-omd'); ?></option>
                            <?php endif; ?>
                        </select>
                        <button class="button action" type="submit"><?php esc_html_e('Zastosuj', 'erp-omd'); ?></button>
                    </div>
                </div>
                <table class="widefat striped">
                    <thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.erp-omd-time-entry-checkbox').forEach(function(checkbox){ checkbox.checked = this.checked; }.bind(this));" /></th><th><?php esc_html_e('Data', 'erp-omd'); ?></th><th><?php esc_html_e('Pracownik', 'erp-omd'); ?></th><th><?php esc_html_e('Projekt', 'erp-omd'); ?></th><th><?php esc_html_e('Rola', 'erp-omd'); ?></th><th><?php esc_html_e('Godziny', 'erp-omd'); ?></th><th><?php esc_html_e('Opis', 'erp-omd'); ?></th><th><?php esc_html_e('Status', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje', 'erp-omd'); ?></th></tr></thead>
                    <tbody>
                        <?php if (empty($time_entries)) : ?>
                            <tr><td colspan="9"><?php esc_html_e('Brak wpisów czasu.', 'erp-omd'); ?></td></tr>
                        <?php else : ?>
                            <?php foreach ($time_entries as $time_row) : ?>
                                <tr>
                                    <td><input class="erp-omd-time-entry-checkbox" type="checkbox" name="time_entry_ids[]" value="<?php echo esc_attr($time_row['id']); ?>" /></td>
                                    <td><?php echo esc_html($time_row['entry_date']); ?></td>
                                    <td><?php echo esc_html($time_row['employee_login']); ?></td>
                                    <td><?php echo esc_html($time_row['project_name']); ?></td>
                                    <td><?php echo esc_html($time_row['role_name']); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((float) $time_row['hours'], 2)); ?></td>
                                    <td><?php echo esc_html($time_row['description'] ?: '—'); ?></td>
                                    <td><span class="erp-omd-badge <?php echo esc_attr($this->status_badge_class($time_row['status'], 'time')); ?>"><?php echo esc_html($this->time_status_label($time_row['status'])); ?></span></td>
                                    <td>
                                        <details class="erp-omd-list-actions">
                                            <summary class="button button-small"><?php esc_html_e('Akcje', 'erp-omd'); ?></summary>
                                            <div class="erp-omd-list-actions-menu">
                                                <?php if ($can_edit_any_entry) : ?>
                                                    <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-time', 'id' => $time_row['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                                <?php endif; ?>
                                                <?php if ($this->time_entry_service->can_approve_entry($time_row, wp_get_current_user()) && $time_row['status'] !== 'approved') : ?>
                                                    <form method="post" class="erp-omd-inline-form">
                                                        <?php wp_nonce_field('erp_omd_change_time_status'); ?>
                                                        <input type="hidden" name="erp_omd_action" value="change_time_status" />
                                                        <input type="hidden" name="id" value="<?php echo esc_attr($time_row['id']); ?>" />
                                                        <input type="hidden" name="status" value="approved" />
                                                        <button class="button button-small" type="submit"><?php esc_html_e('Akceptuj', 'erp-omd'); ?></button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($this->time_entry_service->can_approve_entry($time_row, wp_get_current_user()) && $time_row['status'] !== 'rejected') : ?>
                                                    <form method="post" class="erp-omd-inline-form">
                                                        <?php wp_nonce_field('erp_omd_change_time_status'); ?>
                                                        <input type="hidden" name="erp_omd_action" value="change_time_status" />
                                                        <input type="hidden" name="id" value="<?php echo esc_attr($time_row['id']); ?>" />
                                                        <input type="hidden" name="status" value="rejected" />
                                                        <button class="button button-small" type="submit"><?php esc_html_e('Odrzuć', 'erp-omd'); ?></button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($can_delete_entries) : ?>
                                                    <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Usunąć wpis czasu?', 'erp-omd')); ?>');">
                                                        <?php wp_nonce_field('erp_omd_delete_time_entry'); ?>
                                                        <input type="hidden" name="erp_omd_action" value="delete_time_entry" />
                                                        <input type="hidden" name="id" value="<?php echo esc_attr($time_row['id']); ?>" />
                                                        <button class="button button-small button-link-delete" type="submit"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </section>
    </div>
</div>
