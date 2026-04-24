<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Wnioski projektowe', 'erp-omd'); ?></h1>

    <section class="erp-omd-card">
        <h2 class="nav-tab-wrapper">
            <a class="nav-tab <?php echo ($request_filters['tab'] ?? 'employee') === 'employee' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-requests', 'tab' => 'employee'], admin_url('admin.php'))); ?>">
                <?php esc_html_e('Wnioski pracowników', 'erp-omd'); ?>
            </a>
            <a class="nav-tab <?php echo ($request_filters['tab'] ?? 'employee') === 'client' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-requests', 'tab' => 'client'], admin_url('admin.php'))); ?>">
                <?php esc_html_e('Wnioski klientów', 'erp-omd'); ?>
            </a>
        </h2>
        <h2>
            <?php echo ($request_filters['tab'] ?? 'employee') === 'client'
                ? esc_html__('Lista wniosków klientów', 'erp-omd')
                : esc_html__('Lista wniosków pracowników', 'erp-omd'); ?>
        </h2>
        <?php if ($selected_request) : ?>
            <div id="erp-omd-request-details" class="erp-omd-detail-grid erp-omd-detail-grid-vertical">
                <div class="erp-omd-detail-card">
                    <h3><?php esc_html_e('Podgląd szczegółów wniosku', 'erp-omd'); ?></h3>
                    <div class="erp-omd-detail-list erp-omd-detail-list-horizontal">
                        <div class="erp-omd-detail-item"><strong><?php esc_html_e('Nazwa projektu', 'erp-omd'); ?></strong><span><?php echo esc_html((string) ($selected_request['project_name'] ?? '—')); ?></span></div>
                        <div class="erp-omd-detail-item"><strong><?php esc_html_e('Klient', 'erp-omd'); ?></strong><span><?php echo esc_html((string) ($selected_request['client_name'] ?? '—')); ?></span></div>
                        <div class="erp-omd-detail-item"><strong><?php esc_html_e('Typ rozliczenia', 'erp-omd'); ?></strong><span><?php echo esc_html($this->billing_type_label((string) ($selected_request['billing_type'] ?? ''))); ?></span></div>
                        <div class="erp-omd-detail-item"><strong><?php esc_html_e('Budżet', 'erp-omd'); ?></strong><span><?php echo (float) ($selected_request['budget'] ?? 0) > 0 ? esc_html(number_format_i18n((float) ($selected_request['budget'] ?? 0), 2)) : esc_html__('brak', 'erp-omd'); ?></span></div>
                        <div class="erp-omd-detail-item"><strong><?php esc_html_e('Data rozpoczęcia', 'erp-omd'); ?></strong><span><?php echo esc_html((string) ($selected_request['start_date'] ?? '—')); ?></span></div>
                        <div class="erp-omd-detail-item"><strong><?php esc_html_e('Data zakończenia', 'erp-omd'); ?></strong><span><?php echo esc_html((string) ($selected_request['end_date'] ?? '—')); ?></span></div>
                        <div class="erp-omd-detail-item"><strong><?php esc_html_e('Brief', 'erp-omd'); ?></strong><span><?php echo esc_html((string) ($selected_request['brief'] ?? '—')); ?></span></div>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <div id="erp-omd-request-details" class="erp-omd-detail-grid erp-omd-detail-grid-vertical">
                <div class="erp-omd-detail-card">
                    <h3><?php esc_html_e('Podgląd szczegółów wniosku', 'erp-omd'); ?></h3>
                    <p><?php esc_html_e('Wybierz akcję „Podgląd szczegółów” przy wybranym wniosku.', 'erp-omd'); ?></p>
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

        <form method="post">
            <?php wp_nonce_field('erp_omd_bulk_project_requests'); ?>
            <input type="hidden" name="erp_omd_action" value="bulk_project_requests" />
            <input type="hidden" name="tab" value="<?php echo esc_attr((string) ($request_filters['tab'] ?? 'employee')); ?>" />
            <input type="hidden" name="status_filter" value="<?php echo esc_attr((string) ($request_filters['status'] ?? '')); ?>" />
            <input type="hidden" name="search_filter" value="<?php echo esc_attr((string) ($request_filters['search'] ?? '')); ?>" />
            <div style="margin: 10px 0; display:flex; gap:8px; align-items:center;">
                <select name="bulk_action">
                    <option value=""><?php esc_html_e('Masowe akcje', 'erp-omd'); ?></option>
                    <option value="approve"><?php esc_html_e('Zatwierdź', 'erp-omd'); ?></option>
                    <option value="reject"><?php esc_html_e('Odrzuć', 'erp-omd'); ?></option>
                    <option value="delete"><?php esc_html_e('Usuń', 'erp-omd'); ?></option>
                </select>
                <button class="button action" type="submit"><?php esc_html_e('Zastosuj', 'erp-omd'); ?></button>
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
                            <td><input type="checkbox" name="request_ids[]" value="<?php echo esc_attr((string) ($request_row['id'] ?? 0)); ?>" /></td>
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
        </form>
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
