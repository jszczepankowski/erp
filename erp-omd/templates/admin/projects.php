<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Projekty', 'erp-omd'); ?></h1>
    <div class="erp-omd-card">
        <h2><?php echo $project ? esc_html__('Edytuj projekt', 'erp-omd') : esc_html__('Nowy projekt', 'erp-omd'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('erp_omd_save_project'); ?>
            <input type="hidden" name="erp_omd_action" value="save_project" />
            <input type="hidden" name="id" value="<?php echo esc_attr($project['id'] ?? ''); ?>" />
            <table class="form-table">
                <tr><th colspan="2"><h3 class="erp-omd-form-section-title"><?php esc_html_e('Podstawy projektu', 'erp-omd'); ?></h3></th></tr>
                <tr><th><label for="project-client"><?php esc_html_e('Klient', 'erp-omd'); ?></label></th><td><select id="project-client" name="client_id" required><option value=""><?php esc_html_e('Wybierz klienta', 'erp-omd'); ?></option><?php foreach ($clients as $client_item) : ?><option value="<?php echo esc_attr($client_item['id']); ?>" <?php selected((int) ($project['client_id'] ?? 0), (int) $client_item['id']); ?>><?php echo esc_html($client_item['name']); ?></option><?php endforeach; ?></select></td></tr>
                <tr><th><label for="project-name"><?php esc_html_e('Nazwa', 'erp-omd'); ?></label></th><td><input id="project-name" class="regular-text" type="text" name="name" value="<?php echo esc_attr($project['name'] ?? ''); ?>" required /></td></tr>
                <tr>
                    <th><label for="project-billing-type"><?php esc_html_e('Typ rozliczenia', 'erp-omd'); ?></label></th>
                    <td>
                        <select id="project-billing-type" name="billing_type">
                            <option value="time_material" <?php selected($project['billing_type'] ?? 'time_material', 'time_material'); ?>><?php esc_html_e('Time & Material', 'erp-omd'); ?></option>
                            <option value="fixed_price" <?php selected($project['billing_type'] ?? '', 'fixed_price'); ?>><?php esc_html_e('Ryczałt', 'erp-omd'); ?></option>
                            <option value="retainer" <?php selected($project['billing_type'] ?? '', 'retainer'); ?>><?php esc_html_e('Abonament', 'erp-omd'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr><th colspan="2"><h3 class="erp-omd-form-section-title"><?php esc_html_e('Finanse i lifecycle', 'erp-omd'); ?></h3></th></tr>
                <tr id="erp-omd-project-budget-row"><th><label for="project-budget"><?php esc_html_e('Budżet', 'erp-omd'); ?></label></th><td><input id="project-budget" type="number" step="0.01" min="0" name="budget" value="<?php echo esc_attr($project['budget'] ?? '0'); ?>" /></td></tr>
                <tr id="erp-omd-project-retainer-row"><th><label for="project-retainer-fee"><?php esc_html_e('Abonament — opłata miesięczna', 'erp-omd'); ?></label></th><td><input id="project-retainer-fee" type="number" step="0.01" min="0" name="retainer_monthly_fee" value="<?php echo esc_attr($project['retainer_monthly_fee'] ?? '0'); ?>" /></td></tr>
                <tr>
                    <th><label for="project-status"><?php esc_html_e('Status', 'erp-omd'); ?></label></th>
                    <td>
                        <select id="project-status" name="status">
                            <?php foreach (['do_rozpoczecia', 'w_realizacji', 'w_akceptacji', 'do_faktury', 'zakonczony', 'inactive'] as $project_status) : ?>
                                <option value="<?php echo esc_attr($project_status); ?>" <?php selected($project['status'] ?? 'do_rozpoczecia', $project_status); ?>><?php echo esc_html($this->project_status_label($project_status)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr><th><label for="project-start-date"><?php esc_html_e('Data rozpoczęcia', 'erp-omd'); ?></label></th><td><input id="project-start-date" type="date" name="start_date" value="<?php echo esc_attr($project['start_date'] ?? ''); ?>" /></td></tr>
                <tr><th><label for="project-end-date"><?php esc_html_e('Data zakończenia', 'erp-omd'); ?></label></th><td><input id="project-end-date" type="date" name="end_date" value="<?php echo esc_attr($project['end_date'] ?? ''); ?>" /></td></tr>
                <tr><th><label for="project-alert-threshold"><?php esc_html_e('Próg marży projektu (%)', 'erp-omd'); ?></label></th><td><input id="project-alert-threshold" type="number" step="0.01" min="0" name="alert_margin_threshold" value="<?php echo esc_attr($project['alert_margin_threshold'] ?? ''); ?>" /><p class="description"><?php esc_html_e('Opcjonalnie nadpisuje próg klienta lub globalny próg alertów.', 'erp-omd'); ?></p></td></tr>
                <tr>
                    <th><label for="project-manager"><?php esc_html_e('Manager projektu', 'erp-omd'); ?></label></th>
                    <td>
                        <select id="project-manager" name="manager_id">
                            <option value="0"><?php esc_html_e('Brak', 'erp-omd'); ?></option>
                            <?php foreach ($employees_for_select as $employee_item) : ?>
                                <option value="<?php echo esc_attr($employee_item['id']); ?>" <?php selected((int) ($project['manager_id'] ?? 0), (int) $employee_item['id']); ?>>
                                    <?php echo esc_html($employee_item['user_login'] . ' (' . $this->account_type_label($employee_item['account_type']) . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr><th colspan="2"><h3 class="erp-omd-form-section-title"><?php esc_html_e('Powiązania i opis', 'erp-omd'); ?></h3></th></tr>
                <tr><th><label for="project-estimate-id"><?php esc_html_e('ID estymacji', 'erp-omd'); ?></label></th><td><input id="project-estimate-id" type="number" min="0" name="estimate_id" value="<?php echo esc_attr($project['estimate_id'] ?? ''); ?>" /></td></tr>
                <tr><th><label for="project-brief"><?php esc_html_e('Opis projektu', 'erp-omd'); ?></label></th><td><textarea id="project-brief" class="large-text" rows="5" name="brief"><?php echo esc_textarea($project['brief'] ?? ''); ?></textarea></td></tr>
            </table>
            <?php submit_button($project ? __('Zapisz projekt', 'erp-omd') : __('Dodaj projekt', 'erp-omd')); ?>
        </form>

        <?php if ($project) : ?>
            <div id="erp-omd-project-details">
            <hr />
            <div class="erp-omd-detail-grid">
                <div class="erp-omd-detail-card">
                    <h3><?php esc_html_e('Widok 360° projektu', 'erp-omd'); ?></h3>
                    <p><strong><?php esc_html_e('Klient:', 'erp-omd'); ?></strong> <?php echo esc_html($project['client_name'] ?? '—'); ?></p>
                    <p><strong><?php esc_html_e('Status:', 'erp-omd'); ?></strong> <span class="erp-omd-badge <?php echo esc_attr($this->status_badge_class($project['status'] ?? 'do_rozpoczecia', 'project')); ?>"><?php echo esc_html($this->project_status_label($project['status'] ?? 'do_rozpoczecia')); ?></span></p>
                    <p><strong><?php esc_html_e('Typ:', 'erp-omd'); ?></strong> <?php echo esc_html($this->billing_type_label($project['billing_type'] ?? 'time_material')); ?></p>
                    <p><strong><?php esc_html_e('Manager:', 'erp-omd'); ?></strong> <?php echo esc_html($project['manager_login'] ?? '—'); ?></p>
                </div>
                <div class="erp-omd-detail-card">
                    <h3><?php esc_html_e('Kontekst operacyjny', 'erp-omd'); ?></h3>
                    <p><strong><?php esc_html_e('Budżet:', 'erp-omd'); ?></strong> <?php echo esc_html(number_format_i18n((float) ($project['budget'] ?? 0), 2)); ?></p>
                    <p><strong><?php esc_html_e('Abonament:', 'erp-omd'); ?></strong> <?php echo esc_html(number_format_i18n((float) ($project['retainer_monthly_fee'] ?? 0), 2)); ?></p>
                    <p><strong><?php esc_html_e('Start:', 'erp-omd'); ?></strong> <?php echo esc_html($project['start_date'] ?? '—'); ?></p>
                    <p><strong><?php esc_html_e('Koniec:', 'erp-omd'); ?></strong> <?php echo esc_html($project['end_date'] ?? '—'); ?></p>
                    <p><strong><?php esc_html_e('Próg marży:', 'erp-omd'); ?></strong> <?php echo esc_html(($project['alert_margin_threshold'] ?? '') !== '' && $project['alert_margin_threshold'] !== null ? number_format_i18n((float) $project['alert_margin_threshold'], 2) . '%' : '—'); ?></p>
                </div>
            </div>
            <h2><?php esc_html_e('Finanse projektu', 'erp-omd'); ?></h2>
            <table class="widefat striped">
                <tbody>
                    <tr><th><?php esc_html_e('Przychód', 'erp-omd'); ?></th><td><?php echo esc_html(number_format_i18n((float) ($project_financial['revenue'] ?? 0), 2)); ?></td></tr>
                    <tr><th><?php esc_html_e('Koszt', 'erp-omd'); ?></th><td><?php echo esc_html(number_format_i18n((float) ($project_financial['cost'] ?? 0), 2)); ?></td></tr>
                    <tr><th><?php esc_html_e('Zysk', 'erp-omd'); ?></th><td><?php echo esc_html(number_format_i18n((float) ($project_financial['profit'] ?? 0), 2)); ?></td></tr>
                    <tr><th><?php esc_html_e('Marża %', 'erp-omd'); ?></th><td><?php echo esc_html(number_format_i18n((float) ($project_financial['margin'] ?? 0), 2)); ?></td></tr>
                    <tr><th><?php esc_html_e('Wykorzystanie budżetu %', 'erp-omd'); ?></th><td><?php echo esc_html(number_format_i18n((float) ($project_financial['budget_usage'] ?? 0), 2)); ?></td></tr>
                    <tr><th><?php esc_html_e('Przychód z czasu pracy', 'erp-omd'); ?></th><td><?php echo esc_html(number_format_i18n((float) ($project_financial['time_revenue'] ?? 0), 2)); ?></td></tr>
                    <tr><th><?php esc_html_e('Koszt czasu pracy', 'erp-omd'); ?></th><td><?php echo esc_html(number_format_i18n((float) ($project_financial['time_cost'] ?? 0), 2)); ?></td></tr>
                    <tr><th><?php esc_html_e('Koszt bezpośredni', 'erp-omd'); ?></th><td><?php echo esc_html(number_format_i18n((float) ($project_financial['direct_cost'] ?? 0), 2)); ?></td></tr>
                </tbody>
            </table>
            <hr />
            <h2><?php esc_html_e('Stawki projektowe', 'erp-omd'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('erp_omd_save_project_rate'); ?>
                <input type="hidden" name="erp_omd_action" value="save_project_rate" />
                <input type="hidden" name="project_id" value="<?php echo esc_attr($project['id']); ?>" />
                <table class="form-table">
                    <tr><th><label for="project-rate-role"><?php esc_html_e('Rola', 'erp-omd'); ?></label></th><td><select id="project-rate-role" name="role_id" required><?php foreach ($roles as $role_item) : ?><option value="<?php echo esc_attr($role_item['id']); ?>"><?php echo esc_html($role_item['name']); ?></option><?php endforeach; ?></select></td></tr>
                    <tr><th><label for="project-rate-value"><?php esc_html_e('Stawka', 'erp-omd'); ?></label></th><td><input id="project-rate-value" type="number" step="0.01" min="0" name="rate" required /></td></tr>
                </table>
                <?php submit_button(__('Zapisz stawkę projektową', 'erp-omd'), 'secondary'); ?>
            </form>
            <hr />
            <h2><?php esc_html_e('Koszty projektu', 'erp-omd'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('erp_omd_save_project_cost'); ?>
                <input type="hidden" name="erp_omd_action" value="save_project_cost" />
                <input type="hidden" name="project_id" value="<?php echo esc_attr($project['id']); ?>" />
                <table class="form-table">
                    <tr><th><label for="project-cost-amount"><?php esc_html_e('Kwota', 'erp-omd'); ?></label></th><td><input id="project-cost-amount" type="number" step="0.01" min="0" name="amount" required /></td></tr>
                    <tr><th><label for="project-cost-date"><?php esc_html_e('Data kosztu', 'erp-omd'); ?></label></th><td><input id="project-cost-date" type="date" name="cost_date" value="<?php echo esc_attr(gmdate('Y-m-d')); ?>" required /></td></tr>
                    <tr><th><label for="project-cost-description"><?php esc_html_e('Opis', 'erp-omd'); ?></label></th><td><textarea id="project-cost-description" class="large-text" rows="3" name="description"></textarea></td></tr>
                </table>
                <?php submit_button(__('Dodaj koszt projektu', 'erp-omd'), 'secondary'); ?>
            </form>
            <hr />
            <h2><?php esc_html_e('Historia uwag klienta', 'erp-omd'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('erp_omd_add_project_note'); ?>
                <input type="hidden" name="erp_omd_action" value="add_project_note" />
                <input type="hidden" name="project_id" value="<?php echo esc_attr($project['id']); ?>" />
                <textarea class="large-text" rows="4" name="note" required></textarea>
                <?php submit_button(__('Dodaj uwagę klienta', 'erp-omd'), 'secondary'); ?>
            </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="erp-omd-card">
        <h2><?php esc_html_e('Lista projektów', 'erp-omd'); ?></h2>
        <div class="erp-omd-section-header">
            <form method="get" class="erp-omd-filter-form">
                <input type="hidden" name="page" value="erp-omd-projects" />
                <input type="search" name="search" class="regular-text" placeholder="<?php echo esc_attr__('Szukaj projektu, klienta, managera…', 'erp-omd'); ?>" value="<?php echo esc_attr($project_filters['search'] ?? ''); ?>" />
                <select name="client_id"><option value="0"><?php esc_html_e('Wszyscy klienci', 'erp-omd'); ?></option><?php foreach ($clients as $client_item) : ?><option value="<?php echo esc_attr($client_item['id']); ?>" <?php selected((int) ($project_filters['client_id'] ?? 0), (int) $client_item['id']); ?>><?php echo esc_html($client_item['name']); ?></option><?php endforeach; ?></select>
                <select name="manager_id"><option value="0"><?php esc_html_e('Wszyscy managerowie', 'erp-omd'); ?></option><?php foreach ($employees_for_select as $employee_item) : ?><option value="<?php echo esc_attr($employee_item['id']); ?>" <?php selected((int) ($project_filters['manager_id'] ?? 0), (int) $employee_item['id']); ?>><?php echo esc_html($employee_item['user_login']); ?></option><?php endforeach; ?></select>
                <select name="status"><option value=""><?php esc_html_e('Wszystkie statusy', 'erp-omd'); ?></option><?php foreach (['do_rozpoczecia', 'w_realizacji', 'w_akceptacji', 'do_faktury', 'zakonczony', 'inactive'] as $project_status) : ?><option value="<?php echo esc_attr($project_status); ?>" <?php selected($project_filters['status'] ?? '', $project_status); ?>><?php echo esc_html($this->project_status_label($project_status)); ?></option><?php endforeach; ?></select>
                <button class="button" type="submit"><?php esc_html_e('Filtruj', 'erp-omd'); ?></button>
            </form>
            <form method="post" class="erp-omd-action-group">
                <?php wp_nonce_field('erp_omd_save_saved_view'); ?>
                <input type="hidden" name="erp_omd_action" value="save_saved_view" />
                <input type="hidden" name="screen" value="projects" />
                <input type="hidden" name="page_slug" value="erp-omd-projects" />
                <?php foreach ($project_filters as $filter_key => $filter_value) : ?><input type="hidden" name="filters[<?php echo esc_attr($filter_key); ?>]" value="<?php echo esc_attr((string) $filter_value); ?>" /><?php endforeach; ?>
                <select onchange="if(this.value){window.location.href=this.value;}">
                    <option value=""><?php esc_html_e('Zapisane widoki', 'erp-omd'); ?></option>
                    <?php foreach ($saved_views as $saved_view) : ?><option value="<?php echo esc_url(add_query_arg(array_merge(['page' => 'erp-omd-projects'], $saved_view['params']), admin_url('admin.php'))); ?>"><?php echo esc_html($saved_view['label']); ?></option><?php endforeach; ?>
                </select>
                <input type="text" name="label" class="regular-text" placeholder="<?php echo esc_attr__('Nazwa widoku', 'erp-omd'); ?>" required />
                <button class="button button-secondary" type="submit"><?php esc_html_e('Zapisz widok', 'erp-omd'); ?></button>
            </form>
        </div>
        <form method="post">
            <?php wp_nonce_field('erp_omd_bulk_projects'); ?>
            <input type="hidden" name="erp_omd_action" value="bulk_projects" />
            <div class="tablenav top"><div class="alignleft actions"><select name="bulk_action"><option value=""><?php esc_html_e('Akcje masowe', 'erp-omd'); ?></option><option value="activate"><?php esc_html_e('Aktywuj', 'erp-omd'); ?></option><option value="deactivate"><?php esc_html_e('Dezaktywuj', 'erp-omd'); ?></option></select><button class="button action" type="submit"><?php esc_html_e('Zastosuj', 'erp-omd'); ?></button></div></div>
        <table class="widefat striped">
            <thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.erp-omd-project-checkbox').forEach(function(checkbox){ checkbox.checked = this.checked; }.bind(this));" /></th><th>ID</th><th><?php esc_html_e('Klient', 'erp-omd'); ?></th><th><?php esc_html_e('Nazwa', 'erp-omd'); ?></th><th><?php esc_html_e('Typ', 'erp-omd'); ?></th><th><?php esc_html_e('Manager', 'erp-omd'); ?></th><th><?php esc_html_e('Koszt', 'erp-omd'); ?></th><th><?php esc_html_e('Przychód', 'erp-omd'); ?></th><th><?php esc_html_e('Zysk', 'erp-omd'); ?></th><th><?php esc_html_e('Marża %', 'erp-omd'); ?></th><th><?php esc_html_e('Status', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje', 'erp-omd'); ?></th></tr></thead>
            <tbody>
                <?php if (empty($projects)) : ?>
                    <tr><td colspan="12"><?php esc_html_e('Brak projektów dla wybranych filtrów. Zmień kryteria albo dodaj nowy projekt.', 'erp-omd'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($projects as $project_row) : ?>
                        <?php $list_financial = $project_financials_by_project[(int) $project_row['id']] ?? []; ?>
                        <tr>
                            <td><input class="erp-omd-project-checkbox" type="checkbox" name="project_ids[]" value="<?php echo esc_attr($project_row['id']); ?>" /></td>
                            <td><?php echo esc_html($project_row['id']); ?></td>
                            <td><?php echo esc_html($project_row['client_name']); ?></td>
                            <td>
                                <?php echo esc_html($project_row['name']); ?>
                                <?php if (! empty($project_row['alerts'])) : ?>
                                    <div class="erp-omd-badge-list">
                                        <?php foreach ($project_row['alerts'] as $project_alert) : ?>
                                            <span class="erp-omd-badge erp-omd-badge-<?php echo esc_attr($project_alert['severity']); ?>"><?php echo esc_html($project_alert['message']); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($this->billing_type_label($project_row['billing_type'])); ?></td>
                            <td><?php echo esc_html($project_row['manager_login'] ?: '—'); ?></td>
                            <td><?php echo esc_html(number_format_i18n((float) ($list_financial['cost'] ?? 0), 2)); ?></td>
                            <td><?php echo esc_html(number_format_i18n((float) ($list_financial['revenue'] ?? 0), 2)); ?></td>
                            <td><?php echo esc_html(number_format_i18n((float) ($list_financial['profit'] ?? 0), 2)); ?></td>
                            <td><?php echo esc_html(number_format_i18n((float) ($list_financial['margin'] ?? 0), 2)); ?></td>
                            <td><span class="erp-omd-badge <?php echo esc_attr($this->status_badge_class($project_row['status'], 'project')); ?>"><?php echo esc_html($this->project_status_label($project_row['status'])); ?></span></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-projects', 'id' => $project_row['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-projects', 'id' => $project_row['id']], admin_url('admin.php')) . '#erp-omd-project-details'); ?>"><?php esc_html_e('Szczegóły', 'erp-omd'); ?></a>
                                <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Zduplikować projekt?', 'erp-omd')); ?>');">
                                    <?php wp_nonce_field('erp_omd_duplicate_project'); ?>
                                    <input type="hidden" name="erp_omd_action" value="duplicate_project" />
                                    <input type="hidden" name="id" value="<?php echo esc_attr($project_row['id']); ?>" />
                                    <button class="button button-small" type="submit"><?php esc_html_e('Duplikuj', 'erp-omd'); ?></button>
                                </form>
                                <?php $project_is_inactive = ($project_row['status'] ?? '') === 'inactive'; ?>
                                <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js($project_is_inactive ? __('Aktywować projekt?', 'erp-omd') : __('Dezaktywować projekt?', 'erp-omd')); ?>');">
                                    <?php wp_nonce_field('erp_omd_toggle_project_active'); ?>
                                    <input type="hidden" name="erp_omd_action" value="toggle_project_active" />
                                    <input type="hidden" name="id" value="<?php echo esc_attr($project_row['id']); ?>" />
                                    <button class="button button-small" type="submit"><?php echo esc_html($project_is_inactive ? __('Aktywuj', 'erp-omd') : __('Dezaktywuj', 'erp-omd')); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </form>

        <?php if ($project) : ?>
            <hr />
            <div class="erp-omd-section-header">
                <div>
                    <h2><?php esc_html_e('Załączniki', 'erp-omd'); ?></h2>
                    <p class="description"><?php esc_html_e('Dodaj plik z biblioteki mediów WordPress do projektu.', 'erp-omd'); ?></p>
                </div>
            </div>
            <form method="post" class="erp-omd-attachment-form">
                <?php wp_nonce_field('erp_omd_add_attachment_project_' . (int) $project['id']); ?>
                <input type="hidden" name="erp_omd_action" value="add_attachment" />
                <input type="hidden" name="entity_type" value="project" />
                <input type="hidden" name="entity_id" value="<?php echo esc_attr($project['id']); ?>" />
                <input type="hidden" name="attachment_id" value="" class="erp-omd-media-id" />
                <button type="button" class="button erp-omd-media-button"><?php esc_html_e('Wybierz z Media Library', 'erp-omd'); ?></button>
                <span class="erp-omd-media-name"><?php esc_html_e('Nie wybrano pliku.', 'erp-omd'); ?></span>
                <input type="text" name="label" class="regular-text" placeholder="<?php echo esc_attr__('Etykieta załącznika', 'erp-omd'); ?>" />
                <button type="submit" class="button button-secondary"><?php esc_html_e('Dodaj załącznik', 'erp-omd'); ?></button>
            </form>
            <table class="widefat striped">
                <thead><tr><th><?php esc_html_e('Etykieta', 'erp-omd'); ?></th><th><?php esc_html_e('Plik', 'erp-omd'); ?></th><th><?php esc_html_e('Dodano', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje', 'erp-omd'); ?></th></tr></thead>
                <tbody>
                    <?php if (empty($project_attachments)) : ?>
                        <tr><td colspan="4"><?php esc_html_e('Brak załączników dla tego projektu.', 'erp-omd'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($project_attachments as $project_attachment) : ?>
                            <?php
                            $attachment_post = get_post((int) ($project_attachment['attachment_id'] ?? 0));
                            $attachment_title = get_the_title((int) ($project_attachment['attachment_id'] ?? 0));
                            $attachment_url = wp_get_attachment_url((int) ($project_attachment['attachment_id'] ?? 0));
                            $attachment_name = $attachment_title ?: ((is_object($attachment_post) && ! empty($attachment_post->post_name)) ? $attachment_post->post_name : ('#' . (int) $project_attachment['attachment_id']));
                            ?>
                            <tr>
                                <td><?php echo esc_html($project_attachment['label'] ?: '—'); ?></td>
                                <td>
                                    <?php if ($attachment_url) : ?>
                                        <a href="<?php echo esc_url($attachment_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($attachment_name); ?></a>
                                    <?php else : ?>
                                        <?php echo esc_html($attachment_name); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($project_attachment['created_at'] ?? '—'); ?></td>
                                <td>
                                    <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Usunąć załącznik?', 'erp-omd')); ?>');">
                                        <?php wp_nonce_field('erp_omd_delete_attachment_' . (int) $project_attachment['id']); ?>
                                        <input type="hidden" name="erp_omd_action" value="delete_attachment" />
                                        <input type="hidden" name="attachment_relation_id" value="<?php echo esc_attr($project_attachment['id']); ?>" />
                                        <button class="button button-small button-link-delete" type="submit"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <hr />
            <h2><?php esc_html_e('Stawki projektowe — lista', 'erp-omd'); ?></h2>
            <table class="widefat striped">
                <thead><tr><th><?php esc_html_e('Rola', 'erp-omd'); ?></th><th><?php esc_html_e('Stawka', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje', 'erp-omd'); ?></th></tr></thead>
                <tbody>
                    <?php if (empty($project_rates)) : ?>
                        <tr><td colspan="3"><?php esc_html_e('Brak stawek projektowych. Projekt będzie dziedziczył stawki klienta, jeśli są skonfigurowane.', 'erp-omd'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($project_rates as $project_rate) : ?>
                            <tr>
                                <td><?php echo esc_html($project_rate['role_name'] ?? '—'); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) ($project_rate['rate'] ?? 0), 2)); ?></td>
                                <td>
                                    <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Usunąć stawkę projektową?', 'erp-omd')); ?>');">
                                        <?php wp_nonce_field('erp_omd_delete_project_rate'); ?>
                                        <input type="hidden" name="erp_omd_action" value="delete_project_rate" />
                                        <input type="hidden" name="id" value="<?php echo esc_attr($project_rate['id'] ?? 0); ?>" />
                                        <input type="hidden" name="project_id" value="<?php echo esc_attr($project['id']); ?>" />
                                        <button class="button button-small button-link-delete" type="submit"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <hr />
            <h2><?php esc_html_e('Koszty projektu — lista', 'erp-omd'); ?></h2>
            <table class="widefat striped">
                <thead><tr><th><?php esc_html_e('Data', 'erp-omd'); ?></th><th><?php esc_html_e('Kwota', 'erp-omd'); ?></th><th><?php esc_html_e('Opis', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje', 'erp-omd'); ?></th></tr></thead>
                <tbody>
                    <?php if (empty($project_cost_rows)) : ?>
                        <tr><td colspan="4"><?php esc_html_e('Brak kosztów projektu. Dodaj koszt, jeśli chcesz uwzględnić wydatki poza czasem pracy.', 'erp-omd'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($project_cost_rows as $project_cost_row) : ?>
                            <tr>
                                <td><?php echo esc_html($project_cost_row['cost_date']); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) ($project_cost_row['amount'] ?? 0), 2)); ?></td>
                                <td><?php echo esc_html($project_cost_row['description'] ?: '—'); ?></td>
                                <td>
                                    <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Usunąć koszt projektu?', 'erp-omd')); ?>');">
                                        <?php wp_nonce_field('erp_omd_delete_project_cost'); ?>
                                        <input type="hidden" name="erp_omd_action" value="delete_project_cost" />
                                        <input type="hidden" name="project_cost_id" value="<?php echo esc_attr($project_cost_row['id']); ?>" />
                                        <input type="hidden" name="project_id" value="<?php echo esc_attr($project['id']); ?>" />
                                        <button class="button button-small button-link-delete" type="submit"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <hr />
            <h2><?php esc_html_e('Uwagi klienta — lista', 'erp-omd'); ?></h2>
            <table class="widefat striped">
                <thead><tr><th><?php esc_html_e('Data', 'erp-omd'); ?></th><th><?php esc_html_e('Autor', 'erp-omd'); ?></th><th><?php esc_html_e('Treść', 'erp-omd'); ?></th></tr></thead>
                <tbody>
                    <?php if (empty($project_notes)) : ?>
                        <tr><td colspan="3"><?php esc_html_e('Brak uwag klienta.', 'erp-omd'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($project_notes as $note_item) : ?>
                            <tr>
                                <td><?php echo esc_html($note_item['created_at']); ?></td>
                                <td><?php echo esc_html($note_item['author_login'] ?: '—'); ?></td>
                                <td><?php echo esc_html($note_item['note']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var billingTypeField = document.getElementById('project-billing-type');
    var budgetRow = document.getElementById('erp-omd-project-budget-row');
    var retainerRow = document.getElementById('erp-omd-project-retainer-row');

    if (!billingTypeField || !budgetRow || !retainerRow) {
        return;
    }

    var toggleProjectBillingRows = function () {
        budgetRow.style.display = billingTypeField.value === 'fixed_price' ? '' : 'none';
        retainerRow.style.display = billingTypeField.value === 'retainer' ? '' : 'none';
    };

    billingTypeField.addEventListener('change', toggleProjectBillingRows);
    toggleProjectBillingRows();
});
</script>
