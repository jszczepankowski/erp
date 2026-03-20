<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Projekty', 'erp-omd'); ?></h1>
    <div class="erp-omd-grid two-columns">
        <div class="erp-omd-card">
            <h2><?php echo $project ? esc_html__('Edytuj projekt', 'erp-omd') : esc_html__('Nowy projekt', 'erp-omd'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('erp_omd_save_project'); ?>
                <input type="hidden" name="erp_omd_action" value="save_project" />
                <input type="hidden" name="id" value="<?php echo esc_attr($project['id'] ?? ''); ?>" />
                <table class="form-table">
                    <tr><th><label for="project-client"><?php esc_html_e('Klient', 'erp-omd'); ?></label></th><td><select id="project-client" name="client_id" required><option value=""><?php esc_html_e('Wybierz klienta', 'erp-omd'); ?></option><?php foreach ($clients as $client_item) : ?><option value="<?php echo esc_attr($client_item['id']); ?>" <?php selected((int) ($project['client_id'] ?? 0), (int) $client_item['id']); ?>><?php echo esc_html($client_item['name']); ?></option><?php endforeach; ?></select></td></tr>
                    <tr><th><label for="project-name"><?php esc_html_e('Nazwa', 'erp-omd'); ?></label></th><td><input id="project-name" class="regular-text" type="text" name="name" value="<?php echo esc_attr($project['name'] ?? ''); ?>" required /></td></tr>
                    <tr>
                        <th><label for="project-billing-type"><?php esc_html_e('Typ rozliczenia', 'erp-omd'); ?></label></th>
                        <td>
                            <select id="project-billing-type" name="billing_type">
                                <option value="time_material" <?php selected($project['billing_type'] ?? 'time_material', 'time_material'); ?>>time_material</option>
                                <option value="fixed_price" <?php selected($project['billing_type'] ?? '', 'fixed_price'); ?>>fixed_price</option>
                                <option value="retainer" <?php selected($project['billing_type'] ?? '', 'retainer'); ?>>retainer</option>
                            </select>
                        </td>
                    </tr>
                    <tr><th><label for="project-budget"><?php esc_html_e('Budżet', 'erp-omd'); ?></label></th><td><input id="project-budget" type="number" step="0.01" min="0" name="budget" value="<?php echo esc_attr($project['budget'] ?? '0'); ?>" /></td></tr>
                    <tr><th><label for="project-retainer-fee"><?php esc_html_e('Retainer — miesięczna opłata', 'erp-omd'); ?></label></th><td><input id="project-retainer-fee" type="number" step="0.01" min="0" name="retainer_monthly_fee" value="<?php echo esc_attr($project['retainer_monthly_fee'] ?? '0'); ?>" /></td></tr>
                    <tr>
                        <th><label for="project-status"><?php esc_html_e('Status', 'erp-omd'); ?></label></th>
                        <td>
                            <select id="project-status" name="status">
                                <?php foreach (['do_rozpoczecia', 'w_realizacji', 'w_akceptacji', 'do_faktury', 'zakonczony', 'inactive'] as $project_status) : ?>
                                    <option value="<?php echo esc_attr($project_status); ?>" <?php selected($project['status'] ?? 'do_rozpoczecia', $project_status); ?>><?php echo esc_html($project_status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr><th><label for="project-start-date"><?php esc_html_e('Start date', 'erp-omd'); ?></label></th><td><input id="project-start-date" type="date" name="start_date" value="<?php echo esc_attr($project['start_date'] ?? ''); ?>" /></td></tr>
                    <tr><th><label for="project-end-date"><?php esc_html_e('End date', 'erp-omd'); ?></label></th><td><input id="project-end-date" type="date" name="end_date" value="<?php echo esc_attr($project['end_date'] ?? ''); ?>" /></td></tr>
                    <tr>
                        <th><label for="project-manager"><?php esc_html_e('Manager projektu', 'erp-omd'); ?></label></th>
                        <td>
                            <select id="project-manager" name="manager_id">
                                <option value="0"><?php esc_html_e('Brak', 'erp-omd'); ?></option>
                                <?php foreach ($employees_for_select as $employee_item) : ?>
                                    <option value="<?php echo esc_attr($employee_item['id']); ?>" <?php selected((int) ($project['manager_id'] ?? 0), (int) $employee_item['id']); ?>>
                                        <?php echo esc_html($employee_item['user_login'] . ' (' . $employee_item['account_type'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr><th><label for="project-estimate-id"><?php esc_html_e('Estimate ID', 'erp-omd'); ?></label></th><td><input id="project-estimate-id" type="number" min="0" name="estimate_id" value="<?php echo esc_attr($project['estimate_id'] ?? ''); ?>" /></td></tr>
                    <tr><th><label for="project-brief"><?php esc_html_e('Brief', 'erp-omd'); ?></label></th><td><textarea id="project-brief" class="large-text" rows="5" name="brief"><?php echo esc_textarea($project['brief'] ?? ''); ?></textarea></td></tr>
                </table>
                <?php submit_button($project ? __('Zapisz projekt', 'erp-omd') : __('Dodaj projekt', 'erp-omd')); ?>
            </form>

            <?php if ($project) : ?>
                <hr />
                <h2><?php esc_html_e('Historia uwag klienta', 'erp-omd'); ?></h2>
                <form method="post">
                    <?php wp_nonce_field('erp_omd_add_project_note'); ?>
                    <input type="hidden" name="erp_omd_action" value="add_project_note" />
                    <input type="hidden" name="project_id" value="<?php echo esc_attr($project['id']); ?>" />
                    <textarea class="large-text" rows="4" name="note" required></textarea>
                    <?php submit_button(__('Dodaj uwagę klienta', 'erp-omd'), 'secondary'); ?>
                </form>
            <?php endif; ?>
        </div>
        <div class="erp-omd-card">
            <h2><?php esc_html_e('Lista projektów', 'erp-omd'); ?></h2>
            <table class="widefat striped">
                <thead><tr><th>ID</th><th><?php esc_html_e('Nazwa', 'erp-omd'); ?></th><th><?php esc_html_e('Klient', 'erp-omd'); ?></th><th><?php esc_html_e('Typ', 'erp-omd'); ?></th><th><?php esc_html_e('Status', 'erp-omd'); ?></th><th><?php esc_html_e('Manager', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje', 'erp-omd'); ?></th></tr></thead>
                <tbody>
                    <?php if (empty($projects)) : ?>
                        <tr><td colspan="7"><?php esc_html_e('Brak projektów.', 'erp-omd'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($projects as $project_row) : ?>
                            <tr>
                                <td><?php echo esc_html($project_row['id']); ?></td>
                                <td><?php echo esc_html($project_row['name']); ?></td>
                                <td><?php echo esc_html($project_row['client_name']); ?></td>
                                <td><?php echo esc_html($project_row['billing_type']); ?></td>
                                <td><?php echo esc_html($project_row['status']); ?></td>
                                <td><?php echo esc_html($project_row['manager_login'] ?: '—'); ?></td>
                                <td>
                                    <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-projects', 'id' => $project_row['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                    <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Dezaktywować projekt?', 'erp-omd')); ?>');">
                                        <?php wp_nonce_field('erp_omd_deactivate_project'); ?>
                                        <input type="hidden" name="erp_omd_action" value="deactivate_project" />
                                        <input type="hidden" name="id" value="<?php echo esc_attr($project_row['id']); ?>" />
                                        <button class="button button-small" type="submit"><?php esc_html_e('Deactivate', 'erp-omd'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($project) : ?>
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
</div>
