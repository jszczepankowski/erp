<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Wnioski projektowe', 'erp-omd'); ?></h1>

    <section class="erp-omd-card">
        <h2><?php esc_html_e('Lista wniosków', 'erp-omd'); ?></h2>
        <form method="get" class="erp-omd-filter-form">
            <input type="hidden" name="page" value="erp-omd-requests" />
            <input type="search" name="search" class="regular-text" placeholder="<?php echo esc_attr__('Szukaj po nazwie projektu, kliencie, statusie…', 'erp-omd'); ?>" value="<?php echo esc_attr($request_filters['search'] ?? ''); ?>">
            <select name="status">
                <option value=""><?php esc_html_e('Wszystkie statusy', 'erp-omd'); ?></option>
                <?php foreach (['new', 'under_review', 'approved', 'rejected', 'converted'] as $status_option) : ?>
                    <option value="<?php echo esc_attr($status_option); ?>" <?php selected((string) ($request_filters['status'] ?? ''), $status_option); ?>><?php echo esc_html($status_option); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button" type="submit"><?php esc_html_e('Filtruj', 'erp-omd'); ?></button>
        </form>

        <table class="widefat striped">
            <thead>
                <tr>
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
                    <tr><td colspan="8"><?php esc_html_e('Brak wniosków projektowych.', 'erp-omd'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($project_requests as $request_row) : ?>
                        <tr>
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
    </section>
</div>
