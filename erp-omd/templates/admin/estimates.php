<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('Kosztorysy', 'erp-omd'); ?></h1>

    <div class="erp-omd-grid erp-omd-grid--two">
        <section class="erp-omd-card">
            <h2><?php echo $estimate ? esc_html__('Edytuj kosztorys', 'erp-omd') : esc_html__('Nowy kosztorys', 'erp-omd'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('erp_omd_save_estimate'); ?>
                <input type="hidden" name="erp_omd_action" value="save_estimate">
                <input type="hidden" name="id" value="<?php echo esc_attr($estimate['id'] ?? 0); ?>">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="estimate-client-id"><?php esc_html_e('Klient', 'erp-omd'); ?></label></th>
                        <td>
                            <select id="estimate-client-id" name="client_id" required>
                                <option value=""><?php esc_html_e('Wybierz klienta', 'erp-omd'); ?></option>
                                <?php foreach ($clients as $client_row) : ?>
                                    <option value="<?php echo esc_attr($client_row['id']); ?>" <?php selected((int) ($estimate['client_id'] ?? 0), (int) $client_row['id']); ?>><?php echo esc_html($client_row['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="estimate-status"><?php esc_html_e('Status', 'erp-omd'); ?></label></th>
                        <td>
                            <select id="estimate-status" name="status">
                                <?php foreach (['wstepny', 'do_akceptacji', 'zaakceptowany'] as $status_option) : ?>
                                    <option value="<?php echo esc_attr($status_option); ?>" <?php selected((string) ($estimate['status'] ?? 'wstepny'), $status_option); ?>><?php echo esc_html($status_option); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (! empty($estimate['accepted_at'])) : ?>
                                <p class="description"><?php echo esc_html(sprintf(__('Zaakceptowano: %s', 'erp-omd'), $estimate['accepted_at'])); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button($estimate ? __('Zapisz kosztorys', 'erp-omd') : __('Dodaj kosztorys', 'erp-omd')); ?>
            </form>

            <?php if ($estimate) : ?>
                <hr>
                <h2><?php echo $editing_estimate_item ? esc_html__('Edytuj pozycję kosztorysu', 'erp-omd') : esc_html__('Nowa pozycja kosztorysu', 'erp-omd'); ?></h2>
                <form method="post">
                    <?php wp_nonce_field('erp_omd_save_estimate_item'); ?>
                    <input type="hidden" name="erp_omd_action" value="save_estimate_item">
                    <input type="hidden" name="estimate_id" value="<?php echo esc_attr($estimate['id']); ?>">
                    <input type="hidden" name="item_id" value="<?php echo esc_attr($editing_estimate_item['id'] ?? 0); ?>">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="estimate-item-name"><?php esc_html_e('Nazwa', 'erp-omd'); ?></label></th>
                            <td><input id="estimate-item-name" name="name" type="text" class="regular-text" value="<?php echo esc_attr($editing_estimate_item['name'] ?? ''); ?>" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="estimate-item-qty"><?php esc_html_e('Ilość', 'erp-omd'); ?></label></th>
                            <td><input id="estimate-item-qty" name="qty" type="number" step="0.01" min="0.01" value="<?php echo esc_attr($editing_estimate_item['qty'] ?? '1'); ?>" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="estimate-item-price"><?php esc_html_e('Cena', 'erp-omd'); ?></label></th>
                            <td><input id="estimate-item-price" name="price" type="number" step="0.01" min="0" value="<?php echo esc_attr($editing_estimate_item['price'] ?? '0'); ?>" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="estimate-item-cost-internal"><?php esc_html_e('Koszt wewnętrzny', 'erp-omd'); ?></label></th>
                            <td><input id="estimate-item-cost-internal" name="cost_internal" type="number" step="0.01" min="0" value="<?php echo esc_attr($editing_estimate_item['cost_internal'] ?? '0'); ?>" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="estimate-item-comment"><?php esc_html_e('Komentarz', 'erp-omd'); ?></label></th>
                            <td><textarea id="estimate-item-comment" name="comment" rows="3" class="large-text"><?php echo esc_textarea($editing_estimate_item['comment'] ?? ''); ?></textarea></td>
                        </tr>
                    </table>
                    <?php submit_button($editing_estimate_item ? __('Zapisz pozycję', 'erp-omd') : __('Dodaj pozycję', 'erp-omd')); ?>
                </form>
            <?php endif; ?>
        </section>

        <section class="erp-omd-card">
            <h2><?php esc_html_e('Lista kosztorysów', 'erp-omd'); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Klient', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Status', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Netto', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Brutto', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Projekt', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Akcje', 'erp-omd'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estimates as $estimate_row) : ?>
                        <tr>
                            <td>#<?php echo esc_html($estimate_row['id']); ?></td>
                            <td><?php echo esc_html($estimate_row['client_name']); ?></td>
                            <td><?php echo esc_html($estimate_row['status']); ?></td>
                            <td><?php echo esc_html(number_format_i18n((float) ($estimate_row['total_net'] ?? 0), 2)); ?></td>
                            <td><?php echo esc_html(number_format_i18n((float) ($estimate_row['total_gross'] ?? 0), 2)); ?></td>
                            <td>
                                <?php if (! empty($estimate_row['project_id'])) : ?>
                                    <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-projects', 'id' => (int) $estimate_row['project_id']], admin_url('admin.php'))); ?>"><?php echo esc_html($estimate_row['project_name'] ?: ('#' . $estimate_row['project_id'])); ?></a>
                                <?php else : ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-estimates', 'id' => (int) $estimate_row['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                <?php if (($estimate_row['status'] ?? '') !== 'zaakceptowany') : ?>
                                    <form method="post" style="display:inline-block;">
                                        <?php wp_nonce_field('erp_omd_accept_estimate'); ?>
                                        <input type="hidden" name="erp_omd_action" value="accept_estimate">
                                        <input type="hidden" name="estimate_id" value="<?php echo esc_attr($estimate_row['id']); ?>">
                                        <button type="submit" class="button button-small button-primary"><?php esc_html_e('Akceptuj', 'erp-omd'); ?></button>
                                    </form>
                                    <form method="post" style="display:inline-block;" onsubmit="return confirm('<?php echo esc_js(__('Usunąć kosztorys?', 'erp-omd')); ?>');">
                                        <?php wp_nonce_field('erp_omd_delete_estimate'); ?>
                                        <input type="hidden" name="erp_omd_action" value="delete_estimate">
                                        <input type="hidden" name="id" value="<?php echo esc_attr($estimate_row['id']); ?>">
                                        <button type="submit" class="button button-small button-link-delete"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($estimate) : ?>
                <hr>
                <h2><?php echo esc_html(sprintf(__('Pozycje kosztorysu #%d', 'erp-omd'), $estimate['id'])); ?></h2>
                <?php if ($linked_project) : ?>
                    <p><strong><?php esc_html_e('Powiązany projekt:', 'erp-omd'); ?></strong> <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-projects', 'id' => (int) $linked_project['id']], admin_url('admin.php'))); ?>"><?php echo esc_html($linked_project['name']); ?></a></p>
                <?php endif; ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Nazwa', 'erp-omd'); ?></th>
                            <th><?php esc_html_e('Ilość', 'erp-omd'); ?></th>
                            <th><?php esc_html_e('Cena', 'erp-omd'); ?></th>
                            <th><?php esc_html_e('Koszt wewnętrzny', 'erp-omd'); ?></th>
                            <th><?php esc_html_e('Komentarz', 'erp-omd'); ?></th>
                            <th><?php esc_html_e('Akcje', 'erp-omd'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estimate_items as $item_row) : ?>
                            <tr>
                                <td><?php echo esc_html($item_row['name']); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $item_row['qty'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $item_row['price'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $item_row['cost_internal'], 2)); ?></td>
                                <td><?php echo esc_html($item_row['comment']); ?></td>
                                <td>
                                    <?php if (($estimate['status'] ?? '') !== 'zaakceptowany') : ?>
                                        <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-estimates', 'id' => (int) $estimate['id'], 'item_id' => (int) $item_row['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                        <form method="post" style="display:inline-block;" onsubmit="return confirm('<?php echo esc_js(__('Usunąć pozycję kosztorysu?', 'erp-omd')); ?>');">
                                            <?php wp_nonce_field('erp_omd_delete_estimate_item'); ?>
                                            <input type="hidden" name="erp_omd_action" value="delete_estimate_item">
                                            <input type="hidden" name="estimate_id" value="<?php echo esc_attr($estimate['id']); ?>">
                                            <input type="hidden" name="item_id" value="<?php echo esc_attr($item_row['id']); ?>">
                                            <button type="submit" class="button button-small button-link-delete"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                        </form>
                                    <?php else : ?>
                                        &mdash;
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h3><?php esc_html_e('Podsumowanie', 'erp-omd'); ?></h3>
                <ul>
                    <li><strong><?php esc_html_e('Netto:', 'erp-omd'); ?></strong> <?php echo esc_html(number_format_i18n((float) $estimate_totals['net'], 2)); ?></li>
                    <li><strong><?php esc_html_e('VAT 23%:', 'erp-omd'); ?></strong> <?php echo esc_html(number_format_i18n((float) $estimate_totals['tax'], 2)); ?></li>
                    <li><strong><?php esc_html_e('Brutto:', 'erp-omd'); ?></strong> <?php echo esc_html(number_format_i18n((float) $estimate_totals['gross'], 2)); ?></li>
                    <li><strong><?php esc_html_e('Koszt wewnętrzny:', 'erp-omd'); ?></strong> <?php echo esc_html(number_format_i18n((float) $estimate_totals['internal_cost'], 2)); ?></li>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>
