<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Role projektowe', 'erp-omd'); ?></h1>
    <div class="erp-omd-grid two-columns">
        <div class="erp-omd-card">
            <h2><?php echo $role ? esc_html__('Edytuj rolę', 'erp-omd') : esc_html__('Nowa rola', 'erp-omd'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('erp_omd_save_role'); ?>
                <input type="hidden" name="erp_omd_action" value="save_role" />
                <input type="hidden" name="id" value="<?php echo esc_attr($role['id'] ?? ''); ?>" />
                <table class="form-table">
                    <tr>
                        <th><label for="erp-role-name"><?php esc_html_e('Nazwa', 'erp-omd'); ?></label></th>
                        <td><input id="erp-role-name" class="regular-text" type="text" name="name" value="<?php echo esc_attr($role['name'] ?? ''); ?>" required /></td>
                    </tr>
                    <tr>
                        <th><label for="erp-role-slug"><?php esc_html_e('Slug', 'erp-omd'); ?></label></th>
                        <td><input id="erp-role-slug" class="regular-text" type="text" name="slug" value="<?php echo esc_attr($role['slug'] ?? ''); ?>" required /></td>
                    </tr>
                    <tr>
                        <th><label for="erp-role-status"><?php esc_html_e('Status', 'erp-omd'); ?></label></th>
                        <td>
                            <select id="erp-role-status" name="status">
                                <option value="active" <?php selected($role['status'] ?? 'active', 'active'); ?>>active</option>
                                <option value="inactive" <?php selected($role['status'] ?? '', 'inactive'); ?>>inactive</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="erp-role-description"><?php esc_html_e('Opis', 'erp-omd'); ?></label></th>
                        <td><textarea id="erp-role-description" class="large-text" rows="4" name="description"><?php echo esc_textarea($role['description'] ?? ''); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button($role ? __('Zapisz zmiany', 'erp-omd') : __('Dodaj rolę', 'erp-omd')); ?>
            </form>
        </div>
        <div class="erp-omd-card">
            <h2><?php esc_html_e('Lista ról', 'erp-omd'); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Nazwa', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Slug', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Status', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Akcje', 'erp-omd'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($roles)) : ?>
                        <tr><td colspan="5"><?php esc_html_e('Brak ról projektowych.', 'erp-omd'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($roles as $item) : ?>
                            <tr>
                                <td><?php echo esc_html($item['id']); ?></td>
                                <td><?php echo esc_html($item['name']); ?></td>
                                <td><?php echo esc_html($item['slug']); ?></td>
                                <td><?php echo esc_html($item['status']); ?></td>
                                <td>
                                    <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-roles', 'id' => $item['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                    <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Usunąć rolę?', 'erp-omd')); ?>');">
                                        <?php wp_nonce_field('erp_omd_delete_role'); ?>
                                        <input type="hidden" name="erp_omd_action" value="delete_role" />
                                        <input type="hidden" name="id" value="<?php echo esc_attr($item['id']); ?>" />
                                        <button class="button button-small button-link-delete" type="submit"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
