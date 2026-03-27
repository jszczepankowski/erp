<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Czas pracy', 'erp-omd'); ?></h1>

    <div class="erp-omd-page-sections">
        <section class="erp-omd-card">
            <h2><?php echo ($entry && $can_edit_selected_entry) ? esc_html__('Edytuj wpis czasu', 'erp-omd') : esc_html__('Nowy wpis czasu', 'erp-omd'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('erp_omd_save_time_entry'); ?>
                <input type="hidden" name="erp_omd_action" value="save_time_entry" />
                <input type="hidden" name="id" value="<?php echo esc_attr($entry['id'] ?? ''); ?>" />
                <div class="erp-omd-form-sections">
                    <section class="erp-omd-form-section">
                        <div class="erp-omd-form-section-header">
                            <h3><?php esc_html_e('Kontekst wpisu', 'erp-omd'); ?></h3>
                        </div>
                        <div class="erp-omd-form-grid erp-omd-form-grid-time-context">
                            <div class="erp-omd-form-field">
                                <label for="time-employee"><?php esc_html_e('Pracownik', 'erp-omd'); ?></label>
                                <select id="time-employee" name="employee_id" <?php disabled(! $can_select_any_employee); ?>>
                                    <?php if ($can_select_any_employee) : ?><option value=""><?php esc_html_e('Wybierz', 'erp-omd'); ?></option><?php endif; ?>
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
                                <select id="time-client" name="client_id" data-project-target="#time-project" data-project-requires-client="1">
                                    <option value="0"><?php esc_html_e('Wybierz', 'erp-omd'); ?></option>
                                    <?php foreach ($clients_for_time as $client_item) : ?>
                                        <option value="<?php echo esc_attr($client_item['id']); ?>" <?php selected((int) ($selected_time_client_id ?? 0), (int) $client_item['id']); ?>><?php echo esc_html($client_item['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="erp-omd-form-field">
                                <label for="time-project"><?php esc_html_e('Projekt', 'erp-omd'); ?></label>
                                <select id="time-project" name="project_id" data-role-target="#time-role" <?php disabled((int) ($selected_time_client_id ?? 0) <= 0); ?> required>
                                    <option value=""><?php esc_html_e('Wybierz', 'erp-omd'); ?></option>
                                    <?php foreach ($projects_for_time as $project_item) : ?>
                                        <option value="<?php echo esc_attr($project_item['id']); ?>" data-client-id="<?php echo esc_attr($project_item['client_id']); ?>" <?php selected((int) ($entry['project_id'] ?? 0), (int) $project_item['id']); ?>><?php echo esc_html($project_item['name'] . ' [' . $this->project_status_label($project_item['status']) . ']'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="erp-omd-form-field">
                                <label for="time-role"><?php esc_html_e('Rola', 'erp-omd'); ?></label>
                                <select id="time-role" name="role_id" <?php disabled((int) ($entry['project_id'] ?? 0) <= 0); ?> required>
                                    <option value=""><?php esc_html_e('Wybierz', 'erp-omd'); ?></option>
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
						</div>
                        <div class="erp-omd-form-grid erp-omd-form-grid-time-status">
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
                </div>
            </div>
            <form method="get" class="erp-omd-filter-form">
                <input type="hidden" name="page" value="erp-omd-time" />
                <input type="hidden" name="paged" value="1" />
                <input type="date" name="entry_date" value="<?php echo esc_attr($filters['entry_date'] ?? ''); ?>" />
                <?php if ($can_select_any_employee) : ?>
                    <select name="employee_id"><option value=""><?php esc_html_e('Wszyscy pracownicy', 'erp-omd'); ?></option><?php foreach ($employees_for_select as $employee_item) : ?><option value="<?php echo esc_attr($employee_item['id']); ?>" <?php selected((string) ($filters['employee_id'] ?? ''), (string) $employee_item['id']); ?>><?php echo esc_html($employee_item['user_login']); ?></option><?php endforeach; ?></select>
                <?php endif; ?>
                <select id="time-filter-client" name="client_id" data-project-target="#time-filter-project"><option value=""><?php esc_html_e('Wszyscy klienci', 'erp-omd'); ?></option><?php foreach ($clients_for_time as $client_item) : ?><option value="<?php echo esc_attr($client_item['id']); ?>" <?php selected((string) ($filters['client_id'] ?? ''), (string) $client_item['id']); ?>><?php echo esc_html($client_item['name']); ?></option><?php endforeach; ?></select>
                <select id="time-filter-project" name="project_id"><option value=""><?php esc_html_e('Wszystkie projekty', 'erp-omd'); ?></option><?php foreach ($projects_for_time as $project_item) : ?><option value="<?php echo esc_attr($project_item['id']); ?>" data-client-id="<?php echo esc_attr($project_item['client_id']); ?>" <?php selected((string) ($filters['project_id'] ?? ''), (string) $project_item['id']); ?>><?php echo esc_html($project_item['name']); ?></option><?php endforeach; ?></select>
                <select name="status"><option value=""><?php esc_html_e('Wszystkie statusy', 'erp-omd'); ?></option><?php foreach (['submitted', 'approved', 'rejected'] as $time_status) : ?><option value="<?php echo esc_attr($time_status); ?>" <?php selected((string) ($filters['status'] ?? ''), $time_status); ?>><?php echo esc_html($this->time_status_label($time_status)); ?></option><?php endforeach; ?></select>
                <select name="per_page">
                    <?php foreach ((array) ($time_entries_pagination['allowed_per_page'] ?? [25, 50, 100, 200]) as $size) : ?>
                        <option value="<?php echo esc_attr((string) $size); ?>" <?php selected((int) ($filters['per_page'] ?? 25), (int) $size); ?>><?php echo esc_html(sprintf(__('%d / strona', 'erp-omd'), (int) $size)); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button" type="submit"><?php esc_html_e('Filtruj', 'erp-omd'); ?></button>
            </form>
            <form method="post" id="erp-omd-bulk-time-entries-form">
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
                            <?php if (current_user_can('administrator')) : ?>
                                <option value="change_project"><?php esc_html_e('Zmień projekt przypięcia', 'erp-omd'); ?></option>
                            <?php endif; ?>
                            <?php if ($can_delete_entries) : ?>
                                <option value="delete"><?php esc_html_e('Usuń wpisy', 'erp-omd'); ?></option>
                            <?php endif; ?>
                        </select>
                        <?php if (current_user_can('administrator')) : ?>
                            <label class="screen-reader-text" for="bulk-time-target-project"><?php esc_html_e('Nowy projekt', 'erp-omd'); ?></label>
                            <select id="bulk-time-target-project" name="target_project_id">
                                <option value="0"><?php esc_html_e('Wybierz projekt docelowy', 'erp-omd'); ?></option>
                                <?php foreach ($projects_for_time as $project_item) : ?>
                                    <option value="<?php echo esc_attr($project_item['id']); ?>">
                                        <?php echo esc_html(($project_item['client_name'] ?? '—') . ' — ' . ($project_item['name'] ?? ('#' . (int) $project_item['id']))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        <button class="button action" type="submit"><?php esc_html_e('Zastosuj', 'erp-omd'); ?></button>
                    </div>
                </div>
            </form>
            <table class="widefat striped">
                <thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.erp-omd-time-entry-checkbox').forEach(function(checkbox){ checkbox.checked = this.checked; }.bind(this));" /></th><th><?php esc_html_e('Data', 'erp-omd'); ?></th><th><?php esc_html_e('Pracownik', 'erp-omd'); ?></th><th><?php esc_html_e('Klient', 'erp-omd'); ?></th><th><?php esc_html_e('Projekt', 'erp-omd'); ?></th><th><?php esc_html_e('Rola', 'erp-omd'); ?></th><th><?php esc_html_e('Godziny', 'erp-omd'); ?></th><th><?php esc_html_e('Opis', 'erp-omd'); ?></th><th><?php esc_html_e('Status', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje', 'erp-omd'); ?></th></tr></thead>
                <tbody>
                        <?php if (empty($time_entries)) : ?>
                            <tr><td colspan="10"><?php esc_html_e('Brak wpisów czasu.', 'erp-omd'); ?></td></tr>
                        <?php else : ?>
                            <?php foreach ($time_entries as $time_row) : ?>
                                <?php $inline_time_form_id = 'erp-omd-inline-time-' . (int) $time_row['id']; ?>
                                <tr>
                                    <td><input class="erp-omd-time-entry-checkbox" type="checkbox" name="time_entry_ids[]" value="<?php echo esc_attr($time_row['id']); ?>" form="erp-omd-bulk-time-entries-form" /></td>
                                    <td><?php echo esc_html($time_row['entry_date']); ?></td>
                                    <td><?php echo esc_html($time_row['employee_login']); ?></td>
                                    <td><?php echo esc_html($time_row['client_name'] ?? '—'); ?></td>
                                    <td><?php echo esc_html($time_row['project_name']); ?></td>
                                    <td><?php echo esc_html($time_row['role_name']); ?></td>
                                    <td><input type="number" min="0.01" step="0.01" name="hours" value="<?php echo esc_attr((string) $time_row['hours']); ?>" form="<?php echo esc_attr($inline_time_form_id); ?>" /></td>
                                    <td><input type="text" name="description" value="<?php echo esc_attr((string) ($time_row['description'] ?: '')); ?>" form="<?php echo esc_attr($inline_time_form_id); ?>" /></td>
                                    <td>
                                        <select name="status" form="<?php echo esc_attr($inline_time_form_id); ?>">
                                            <?php foreach (['submitted', 'approved', 'rejected'] as $inline_status) : ?>
                                                <option value="<?php echo esc_attr($inline_status); ?>" <?php selected((string) ($time_row['status'] ?? 'submitted'), $inline_status); ?>><?php echo esc_html($this->time_status_label($inline_status)); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <details class="erp-omd-list-actions">
                                            <summary class="button button-small"><?php esc_html_e('Akcje', 'erp-omd'); ?></summary>
                                            <div class="erp-omd-list-actions-menu">
                                                <form method="post" id="<?php echo esc_attr($inline_time_form_id); ?>" class="erp-omd-inline-form">
                                                    <?php wp_nonce_field('erp_omd_inline_time_entry_update'); ?>
                                                    <input type="hidden" name="erp_omd_action" value="inline_update_time_entry" />
                                                    <input type="hidden" name="id" value="<?php echo esc_attr($time_row['id']); ?>" />
                                                    <button class="button button-small button-primary" type="submit"><?php esc_html_e('Zapisz inline', 'erp-omd'); ?></button>
                                                </form>
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
            <?php if (($time_entries_pagination['total_pages'] ?? 1) > 1) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        $base_args = [
                            'page' => 'erp-omd-time',
                            'entry_date' => $filters['entry_date'] ?? '',
                            'employee_id' => $filters['employee_id'] ?? '',
                            'client_id' => $filters['client_id'] ?? '',
                            'project_id' => $filters['project_id'] ?? '',
                            'status' => $filters['status'] ?? '',
                            'per_page' => $filters['per_page'] ?? 25,
                        ];
                        $current_page = (int) ($time_entries_pagination['current_page'] ?? 1);
                        $total_pages = (int) ($time_entries_pagination['total_pages'] ?? 1);
                        ?>
                        <?php if ($current_page > 1) : ?>
                            <a class="button" href="<?php echo esc_url(add_query_arg(array_merge($base_args, ['paged' => $current_page - 1]), admin_url('admin.php'))); ?>">&laquo; <?php esc_html_e('Poprzednia', 'erp-omd'); ?></a>
                        <?php endif; ?>
                        <span class="displaying-num">
                            <?php
                            echo esc_html(
                                sprintf(
                                    __('Strona %1$d z %2$d · rekordów: %3$d', 'erp-omd'),
                                    $current_page,
                                    $total_pages,
                                    (int) ($time_entries_pagination['total_items'] ?? 0)
                                )
                            );
                            ?>
                        </span>
                        <?php if ($current_page < $total_pages) : ?>
                            <a class="button" href="<?php echo esc_url(add_query_arg(array_merge($base_args, ['paged' => $current_page + 1]), admin_url('admin.php'))); ?>"><?php esc_html_e('Następna', 'erp-omd'); ?> &raquo;</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
