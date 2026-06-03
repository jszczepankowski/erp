<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Wnioski projektowe', 'erp-omd'); ?></h1>

    <nav class="nav-tab-wrapper erp-omd-nav-tabs">
        <a class="nav-tab <?php echo ($request_filters['tab'] ?? 'employee') === 'employee' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-requests', 'tab' => 'employee'], admin_url('admin.php'))); ?>">
            <?php esc_html_e('Wnioski pracowników', 'erp-omd'); ?>
        </a>
        <a class="nav-tab <?php echo ($request_filters['tab'] ?? 'employee') === 'client' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-requests', 'tab' => 'client'], admin_url('admin.php'))); ?>">
            <?php esc_html_e('Wnioski klientów', 'erp-omd'); ?>
        </a>
    </nav>

    <section class="erp-omd-card">
        <h2>
            <?php echo ($request_filters['tab'] ?? 'employee') === 'client'
                ? esc_html__('Lista wniosków klientów', 'erp-omd')
                : esc_html__('Lista wniosków pracowników', 'erp-omd'); ?>
        </h2>
        <?php if ($selected_request) : ?>
            <div id="erp-omd-request-details" class="erp-omd-detail-grid erp-omd-detail-grid-vertical">
                <div class="erp-omd-detail-card">
                    <h3><?php echo ! empty($request_edit_mode) ? esc_html__('Edycja wniosku projektowego', 'erp-omd') : esc_html__('Podgląd szczegółów wniosku', 'erp-omd'); ?></h3>
                    <?php if (! empty($request_edit_mode)) : ?>
                        <form method="post" class="erp-omd-form-sections">
                            <?php wp_nonce_field('erp_omd_update_project_request'); ?>
                            <input type="hidden" name="erp_omd_action" value="update_project_request" />
                            <input type="hidden" name="request_id" value="<?php echo esc_attr((string) ($selected_request['id'] ?? 0)); ?>" />
                            <input type="hidden" name="tab" value="<?php echo esc_attr((string) ($request_filters['tab'] ?? 'employee')); ?>" />
                            <div class="erp-omd-form-grid erp-omd-form-grid-project-basics">
                                <div class="erp-omd-form-field"><label for="request-project-name"><?php esc_html_e('Nazwa projektu', 'erp-omd'); ?></label><input id="request-project-name" type="text" name="project_name" value="<?php echo esc_attr((string) ($selected_request['project_name'] ?? '')); ?>" required /></div>
                                <div class="erp-omd-form-field"><label for="request-client-id"><?php esc_html_e('Klient', 'erp-omd'); ?></label><select id="request-client-id" name="client_id" required><option value=""><?php esc_html_e('Wybierz klienta', 'erp-omd'); ?></option><?php foreach ((array) $clients as $client_item) : ?><option value="<?php echo esc_attr((string) ($client_item['id'] ?? 0)); ?>" <?php selected((int) ($selected_request['client_id'] ?? 0), (int) ($client_item['id'] ?? 0)); ?>><?php echo esc_html((string) ($client_item['name'] ?? '')); ?></option><?php endforeach; ?></select></div>
                                <div class="erp-omd-form-field"><label for="request-billing-type"><?php esc_html_e('Typ rozliczenia', 'erp-omd'); ?></label><select id="request-billing-type" name="billing_type"><?php foreach (['time_material', 'fixed_price', 'retainer', 'mixed'] as $billing_type) : ?><option value="<?php echo esc_attr($billing_type); ?>" <?php selected((string) ($selected_request['billing_type'] ?? 'time_material'), $billing_type); ?>><?php echo esc_html($this->billing_type_label($billing_type)); ?></option><?php endforeach; ?></select></div>
                            </div>
                            <div class="erp-omd-form-grid erp-omd-form-grid-project-lifecycle">
                                <div class="erp-omd-form-field"><label for="request-budget"><?php esc_html_e('Budżet', 'erp-omd'); ?></label><input id="request-budget" type="number" step="0.01" min="0" name="budget" value="<?php echo esc_attr((string) ($selected_request['budget'] ?? 0)); ?>" /></div>
                                <div class="erp-omd-form-field"><label for="request-start-date"><?php esc_html_e('Data rozpoczęcia', 'erp-omd'); ?></label><input id="request-start-date" type="date" name="start_date" value="<?php echo esc_attr((string) ($selected_request['start_date'] ?? '')); ?>" /></div>
                                <div class="erp-omd-form-field"><label for="request-end-date"><?php esc_html_e('Data zakończenia', 'erp-omd'); ?></label><input id="request-end-date" type="date" name="end_date" value="<?php echo esc_attr((string) ($selected_request['end_date'] ?? '')); ?>" /></div>
                                <div class="erp-omd-form-field"><label for="request-status"><?php esc_html_e('Status', 'erp-omd'); ?></label><select id="request-status" name="status"><?php foreach (['new', 'under_review', 'approved', 'rejected', 'converted'] as $status_option) : ?><option value="<?php echo esc_attr($status_option); ?>" <?php selected((string) ($selected_request['status'] ?? 'new'), $status_option); ?>><?php echo esc_html($status_option); ?></option><?php endforeach; ?></select></div>
                            </div>
                            <div class="erp-omd-form-grid">
                                <div class="erp-omd-form-field"><label for="request-preferred-manager"><?php esc_html_e('Preferowany manager', 'erp-omd'); ?></label><select id="request-preferred-manager" name="preferred_manager_id"><option value="0"><?php esc_html_e('Brak', 'erp-omd'); ?></option><?php foreach ((array) $employees_for_select as $employee_item) : ?><option value="<?php echo esc_attr((string) ($employee_item['id'] ?? 0)); ?>" <?php selected((int) ($selected_request['preferred_manager_id'] ?? 0), (int) ($employee_item['id'] ?? 0)); ?>><?php echo esc_html((string) ($employee_item['user_login'] ?? ('#' . (int) ($employee_item['id'] ?? 0)))); ?></option><?php endforeach; ?></select></div>
                                <div class="erp-omd-form-field"><label for="request-estimate-id"><?php esc_html_e('ID estymacji', 'erp-omd'); ?></label><select id="request-estimate-id" name="estimate_id"><option value="0"><?php esc_html_e('Brak', 'erp-omd'); ?></option><?php foreach ((array) $estimates as $estimate_item) : ?><option value="<?php echo esc_attr((string) ($estimate_item['id'] ?? 0)); ?>" <?php selected((int) ($selected_request['estimate_id'] ?? 0), (int) ($estimate_item['id'] ?? 0)); ?>>#<?php echo esc_html((string) ($estimate_item['id'] ?? 0)); ?> — <?php echo esc_html((string) ($estimate_item['name'] ?? '')); ?></option><?php endforeach; ?></select></div>
                                <div class="erp-omd-form-field erp-omd-form-field-span-2"><label for="request-brief"><?php esc_html_e('Brief', 'erp-omd'); ?></label><textarea id="request-brief" name="brief" rows="4"><?php echo esc_textarea((string) ($selected_request['brief'] ?? '')); ?></textarea></div>
                            </div>
                            <div class="erp-omd-form-actions"><button type="submit" class="button button-primary"><?php esc_html_e('Zapisz wniosek', 'erp-omd'); ?></button> <a class="button" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-requests', 'tab' => (string) ($request_filters['tab'] ?? 'employee'), 'id' => (int) ($selected_request['id'] ?? 0)], admin_url('admin.php')) . '#erp-omd-request-details'); ?>"><?php esc_html_e('Anuluj edycję', 'erp-omd'); ?></a></div>
                        </form>
                    <?php else : ?>
                    <div class="erp-omd-detail-list erp-omd-detail-list-horizontal">
                        <div class="erp-omd-detail-item"><strong><?php esc_html_e('Nazwa projektu', 'erp-omd'); ?></strong><span><?php echo esc_html((string) ($selected_request['project_name'] ?? '—')); ?></span></div>
                        <div class="erp-omd-detail-item"><strong><?php esc_html_e('Klient', 'erp-omd'); ?></strong><span><?php echo esc_html((string) ($selected_request['client_name'] ?? '—')); ?></span></div>
                        <div class="erp-omd-detail-item"><strong><?php esc_html_e('Typ rozliczenia', 'erp-omd'); ?></strong><span><?php echo esc_html($this->billing_type_label((string) ($selected_request['billing_type'] ?? ''))); ?></span></div>
                        <div class="erp-omd-detail-item"><strong><?php esc_html_e('Budżet', 'erp-omd'); ?></strong><span><?php echo (float) ($selected_request['budget'] ?? 0) > 0 ? esc_html(number_format_i18n((float) ($selected_request['budget'] ?? 0), 2)) : esc_html__('brak', 'erp-omd'); ?></span></div>
                        <div class="erp-omd-detail-item"><strong><?php esc_html_e('Data rozpoczęcia', 'erp-omd'); ?></strong><span><?php echo esc_html((string) ($selected_request['start_date'] ?? '—')); ?></span></div>
                        <div class="erp-omd-detail-item"><strong><?php esc_html_e('Data zakończenia', 'erp-omd'); ?></strong><span><?php echo esc_html((string) ($selected_request['end_date'] ?? '—')); ?></span></div>
                        <div class="erp-omd-detail-item"><strong><?php esc_html_e('Brief', 'erp-omd'); ?></strong><span><?php echo esc_html((string) ($selected_request['brief'] ?? '—')); ?></span></div>
                    </div>
                    <p><a class="button button-secondary" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-requests', 'tab' => (string) ($request_filters['tab'] ?? 'employee'), 'id' => (int) ($selected_request['id'] ?? 0), 'edit_request' => 1], admin_url('admin.php')) . '#erp-omd-request-details'); ?>"><?php esc_html_e('Edytuj wniosek', 'erp-omd'); ?></a></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <form method="get" class="erp-omd-filter-form">
            <input type="hidden" name="page" value="erp-omd-requests" />
            <input type="hidden" name="tab" value="<?php echo esc_attr((string) ($request_filters['tab'] ?? 'employee')); ?>" />
            <input type="search" name="search" class="regular-text" placeholder="<?php echo esc_attr__('Szukaj po nazwie projektu, kliencie, statusie…', 'erp-omd'); ?>" value="<?php echo esc_attr($request_filters['search'] ?? ''); ?>">
            <select name="status">
                <option value=""><?php esc_html_e('Wszystkie statusy', 'erp-omd'); ?></option>
                <?php foreach (['new', 'under_review', 'approved', 'rejected', 'converted'] as $status_option) : ?>
                    <option value="<?php echo esc_attr($status_option); ?>" <?php selected((string) ($request_filters['status'] ?? ''), $status_option); ?>><?php echo esc_html($status_option); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button" type="submit"><?php esc_html_e('Filtruj', 'erp-omd'); ?></button>
        </form>

        <form id="erp-omd-bulk-requests-form" method="post">
            <?php wp_nonce_field('erp_omd_bulk_project_requests'); ?>
            <input type="hidden" name="erp_omd_action" value="bulk_project_requests" />
            <input type="hidden" name="tab" value="<?php echo esc_attr((string) ($request_filters['tab'] ?? 'employee')); ?>" />
            <input type="hidden" name="status_filter" value="<?php echo esc_attr((string) ($request_filters['status'] ?? '')); ?>" />
            <input type="hidden" name="search_filter" value="<?php echo esc_attr((string) ($request_filters['search'] ?? '')); ?>" />
        </form>
            <div style="margin: 10px 0; display:flex; gap:8px; align-items:center;">
                <select name="bulk_action" form="erp-omd-bulk-requests-form">
                    <option value=""><?php esc_html_e('Masowe akcje', 'erp-omd'); ?></option>
                    <option value="approve"><?php esc_html_e('Zatwierdź', 'erp-omd'); ?></option>
                    <option value="reject"><?php esc_html_e('Odrzuć', 'erp-omd'); ?></option>
                    <option value="delete"><?php esc_html_e('Usuń', 'erp-omd'); ?></option>
                </select>
                <button class="button action" type="submit" form="erp-omd-bulk-requests-form"><?php esc_html_e('Zastosuj', 'erp-omd'); ?></button>
            </div>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th><input type="checkbox" id="erp-omd-request-check-all" /></th>
                    <th><?php esc_html_e('ID', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Projekt', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Klient', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Typ', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Requester', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Preferowany manager', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Status', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Akcje', 'erp-omd'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($project_requests)) : ?>
                    <tr><td colspan="9"><?php esc_html_e('Brak wniosków projektowych.', 'erp-omd'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($project_requests as $request_row) : ?>
                        <tr>
                            <td><input type="checkbox" name="request_ids[]" value="<?php echo esc_attr((string) ($request_row['id'] ?? 0)); ?>" form="erp-omd-bulk-requests-form" /></td>
                            <td>#<?php echo esc_html((string) ($request_row['id'] ?? 0)); ?></td>
                            <td><?php echo esc_html((string) ($request_row['project_name'] ?? '—')); ?></td>
                            <td><?php echo esc_html((string) ($request_row['client_name'] ?? '—')); ?></td>
                            <td><?php echo esc_html($this->billing_type_label((string) ($request_row['billing_type'] ?? ''))); ?></td>
                            <td><?php echo esc_html((string) ($request_row['requester_login'] ?? '—')); ?></td>
                            <td><?php echo esc_html((string) ($request_row['preferred_manager_login'] ?? '—')); ?></td>
                            <td><span class="erp-omd-badge <?php echo esc_attr($this->status_badge_class((string) ($request_row['status'] ?? 'new'), 'estimate')); ?>"><?php echo esc_html((string) ($request_row['status'] ?? 'new')); ?></span></td>
                            <td>
                                <details class="erp-omd-list-actions">
                                    <summary class="button button-small"><?php esc_html_e('Akcje', 'erp-omd'); ?></summary>
                                    <div class="erp-omd-list-actions-menu">
                                        <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-requests', 'tab' => (string) ($request_filters['tab'] ?? 'employee'), 'status' => (string) ($request_filters['status'] ?? ''), 'search' => (string) ($request_filters['search'] ?? ''), 'id' => (int) ($request_row['id'] ?? 0)], admin_url('admin.php')) . '#erp-omd-request-details'); ?>"><?php esc_html_e('Podgląd szczegółów', 'erp-omd'); ?></a>
                                        <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-requests', 'tab' => (string) ($request_filters['tab'] ?? 'employee'), 'status' => (string) ($request_filters['status'] ?? ''), 'search' => (string) ($request_filters['search'] ?? ''), 'id' => (int) ($request_row['id'] ?? 0), 'edit_request' => 1], admin_url('admin.php')) . '#erp-omd-request-details'); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                        <?php foreach (['under_review' => __('Do analizy', 'erp-omd'), 'approved' => __('Zatwierdź', 'erp-omd'), 'rejected' => __('Odrzuć', 'erp-omd')] as $target_status => $target_label) : ?>
                                            <form method="post" class="erp-omd-inline-form">
                                                <?php wp_nonce_field('erp_omd_update_project_request_status'); ?>
                                                <input type="hidden" name="erp_omd_action" value="update_project_request_status" />
                                                <input type="hidden" name="request_id" value="<?php echo esc_attr((string) ($request_row['id'] ?? 0)); ?>" />
                                                <input type="hidden" name="status" value="<?php echo esc_attr($target_status); ?>" />
                                                <button class="button button-small" type="submit"><?php echo esc_html($target_label); ?></button>
                                            </form>
                                        <?php endforeach; ?>

                                        <?php if ((string) ($request_row['status'] ?? '') === 'approved') : ?>
                                            <form method="post" class="erp-omd-inline-form">
                                                <?php wp_nonce_field('erp_omd_convert_project_request'); ?>
                                                <input type="hidden" name="erp_omd_action" value="convert_project_request" />
                                                <input type="hidden" name="request_id" value="<?php echo esc_attr((string) ($request_row['id'] ?? 0)); ?>" />
                                                <button class="button button-small button-primary" type="submit"><?php esc_html_e('Konwertuj do projektu', 'erp-omd'); ?></button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Usunąć ten wniosek projektowy?', 'erp-omd')); ?>');">
                                            <?php wp_nonce_field('erp_omd_delete_project_request'); ?>
                                            <input type="hidden" name="erp_omd_action" value="delete_project_request" />
                                            <input type="hidden" name="request_id" value="<?php echo esc_attr((string) ($request_row['id'] ?? 0)); ?>" />
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
        <script>
        (function (document) {
            var checkAll = document.getElementById('erp-omd-request-check-all');
            if (!checkAll) {
                return;
            }
            checkAll.addEventListener('change', function () {
                document.querySelectorAll('input[name="request_ids[]"]').forEach(function (checkbox) {
                    checkbox.checked = checkAll.checked;
                });
            });
        }(document));
        </script>
    </section>
</div>
