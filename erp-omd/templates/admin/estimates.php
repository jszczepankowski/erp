<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('Kosztorysy', 'erp-omd'); ?></h1>

    <section class="erp-omd-card">
            <h2><?php echo $estimate ? esc_html__('Edytuj kosztorys', 'erp-omd') : esc_html__('Nowy kosztorys', 'erp-omd'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('erp_omd_save_estimate'); ?>
                <input type="hidden" name="erp_omd_action" value="save_estimate">
                <input type="hidden" name="id" value="<?php echo esc_attr($estimate['id'] ?? 0); ?>">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="estimate-name"><?php esc_html_e('Nazwa kosztorysu', 'erp-omd'); ?></label></th>
                        <td>
                            <input id="estimate-name" name="name" type="text" class="regular-text" value="<?php echo esc_attr($estimate['name'] ?? ''); ?>" required>
                            <p class="description"><?php esc_html_e('Np. Audyt SEO Q2 2026 albo Kampania launchowa.', 'erp-omd'); ?></p>
                        </td>
                    </tr>
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
                            <?php if (($estimate['status'] ?? '') === 'zaakceptowany') : ?>
                                <p class="description"><?php esc_html_e('Zaakceptowany kosztorys zachowuje zablokowane pozycje i dane klienta, ale administrator nadal może zmienić jego status.', 'erp-omd'); ?></p>
                            <?php endif; ?>
                            <?php if (! empty($estimate['accepted_at'])) : ?>
                                <p class="description"><?php echo esc_html(sprintf(__('Zaakceptowano: %s', 'erp-omd'), $estimate['accepted_at'])); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button($estimate ? __('Zapisz kosztorys', 'erp-omd') : __('Dodaj kosztorys', 'erp-omd')); ?>
                <?php if ($estimate) : ?>
                    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=erp-omd-estimates&id=' . (int) $estimate['id'])); ?>"><?php esc_html_e('Przejdź do szczegółów', 'erp-omd'); ?></a>
                <?php endif; ?>
            </form>

            <?php if ($selected_estimate) : ?>
                <hr>
                <div class="erp-omd-section-header">
                    <h2><?php echo $editing_estimate_item ? esc_html__('Edytuj pozycję kosztorysu', 'erp-omd') : esc_html__('Pozycje kosztorysu', 'erp-omd'); ?></h2>
                    <?php if (($selected_estimate['status'] ?? '') !== 'zaakceptowany') : ?>
                        <a class="button button-secondary" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-estimates', 'id' => (int) $selected_estimate['id'], 'edit' => 1], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj kosztorys', 'erp-omd'); ?></a>
                    <?php endif; ?>
                </div>
                <?php if (($selected_estimate['status'] ?? '') !== 'zaakceptowany') : ?>
                    <form method="post">
                        <?php wp_nonce_field('erp_omd_save_estimate_item'); ?>
                        <input type="hidden" name="erp_omd_action" value="save_estimate_item">
                        <input type="hidden" name="estimate_id" value="<?php echo esc_attr($selected_estimate['id']); ?>">
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
                        <?php submit_button($editing_estimate_item ? __('Zapisz pozycję', 'erp-omd') : __('Dodaj pozycję', 'erp-omd'), 'secondary'); ?>
                        <?php if ($editing_estimate_item) : ?>
                            <a class="button" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-estimates', 'id' => (int) $selected_estimate['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Anuluj edycję pozycji', 'erp-omd'); ?></a>
                        <?php endif; ?>
                    </form>
                <?php else : ?>
                    <p><?php esc_html_e('Po zaakceptowaniu pozycje kosztorysu są tylko do odczytu — można je przeglądać i eksportować, ale status kosztorysu administrator może nadal zmienić z poziomu formularza edycji.', 'erp-omd'); ?></p>
                <?php endif; ?>
            <?php endif; ?>
    </section>

    <section class="erp-omd-card">
            <h2><?php esc_html_e('Lista kosztorysów', 'erp-omd'); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Nazwa kosztorysu', 'erp-omd'); ?></th>
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
                        <?php $estimate_label = trim((string) ($estimate_row['name'] ?? '')) !== '' ? (string) $estimate_row['name'] : sprintf(__('Kosztorys #%d', 'erp-omd'), (int) $estimate_row['id']); ?>
                        <tr>
                            <td>#<?php echo esc_html($estimate_row['id']); ?></td>
                            <td>
                                <?php echo esc_html($estimate_label); ?>
                                <?php if (! empty($estimate_row['alerts'])) : ?>
                                    <div class="erp-omd-badge-list">
                                        <?php foreach ($estimate_row['alerts'] as $estimate_alert) : ?>
                                            <span class="erp-omd-badge erp-omd-badge-<?php echo esc_attr($estimate_alert['severity']); ?>"><?php echo esc_html($estimate_alert['message']); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
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
                                <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-estimates', 'id' => (int) $estimate_row['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Szczegóły', 'erp-omd'); ?></a>
                                <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-estimates', 'id' => (int) $estimate_row['id'], 'edit' => 1], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
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

            <?php if ($selected_estimate) : ?>
                <?php $selected_estimate_label = trim((string) ($selected_estimate['name'] ?? '')) !== '' ? (string) $selected_estimate['name'] : sprintf(__('Kosztorys #%d', 'erp-omd'), (int) $selected_estimate['id']); ?>
                <hr>
                <div class="erp-omd-section-header">
                    <div>
                        <h2><?php echo esc_html($selected_estimate_label); ?></h2>
                        <p class="description"><?php echo esc_html(sprintf(__('Pozycje kosztorysu #%d', 'erp-omd'), (int) $selected_estimate['id'])); ?></p>
                    </div>
                    <div class="erp-omd-action-group">
                        <form method="post" class="erp-omd-inline-form">
                            <?php wp_nonce_field('erp_omd_export_estimate'); ?>
                            <input type="hidden" name="erp_omd_action" value="export_estimate">
                            <input type="hidden" name="estimate_id" value="<?php echo esc_attr($selected_estimate['id']); ?>">
                            <input type="hidden" name="export_variant" value="client">
                            <button type="submit" class="button button-secondary"><?php esc_html_e('Eksport dla klienta', 'erp-omd'); ?></button>
                        </form>
                        <form method="post" class="erp-omd-inline-form">
                            <?php wp_nonce_field('erp_omd_export_estimate'); ?>
                            <input type="hidden" name="erp_omd_action" value="export_estimate">
                            <input type="hidden" name="estimate_id" value="<?php echo esc_attr($selected_estimate['id']); ?>">
                            <input type="hidden" name="export_variant" value="agency">
                            <button type="submit" class="button button-secondary"><?php esc_html_e('Eksport dla agencji', 'erp-omd'); ?></button>
                        </form>
                    </div>
                </div>
                <?php if ($linked_project) : ?>
                    <p><strong><?php esc_html_e('Powiązany projekt:', 'erp-omd'); ?></strong> <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-projects', 'id' => (int) $linked_project['id']], admin_url('admin.php'))); ?>"><?php echo esc_html($linked_project['name']); ?></a></p>
                    <?php $linked_project_alerts = $selected_estimate['alerts'] ?? []; ?>
                    <?php if (! empty($linked_project_alerts)) : ?>
                        <div class="erp-omd-badge-list">
                            <?php foreach ($linked_project_alerts as $linked_project_alert) : ?>
                                <span class="erp-omd-badge erp-omd-badge-<?php echo esc_attr($linked_project_alert['severity']); ?>"><?php echo esc_html($linked_project_alert['message']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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
                        <?php if (empty($estimate_items)) : ?>
                            <tr>
                                <td colspan="6"><?php esc_html_e('Brak pozycji kosztorysu.', 'erp-omd'); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($estimate_items as $item_row) : ?>
                            <tr>
                                <td><?php echo esc_html($item_row['name']); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $item_row['qty'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $item_row['price'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $item_row['cost_internal'], 2)); ?></td>
                                <td><?php echo esc_html($item_row['comment']); ?></td>
                                <td>
                                    <?php if (($selected_estimate['status'] ?? '') !== 'zaakceptowany') : ?>
                                        <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-estimates', 'id' => (int) $selected_estimate['id'], 'edit' => 1, 'item_id' => (int) $item_row['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                        <form method="post" style="display:inline-block;" onsubmit="return confirm('<?php echo esc_js(__('Usunąć pozycję kosztorysu?', 'erp-omd')); ?>');">
                                            <?php wp_nonce_field('erp_omd_delete_estimate_item'); ?>
                                            <input type="hidden" name="erp_omd_action" value="delete_estimate_item">
                                            <input type="hidden" name="estimate_id" value="<?php echo esc_attr($selected_estimate['id']); ?>">
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

                <hr>
                <div class="erp-omd-section-header">
                    <div>
                        <h3><?php esc_html_e('Załączniki', 'erp-omd'); ?></h3>
                        <p class="description"><?php esc_html_e('Dodaj plik z biblioteki mediów WordPress do kosztorysu.', 'erp-omd'); ?></p>
                    </div>
                </div>
                <form method="post" class="erp-omd-attachment-form">
                    <?php wp_nonce_field('erp_omd_add_attachment_estimate_' . (int) $selected_estimate['id']); ?>
                    <input type="hidden" name="erp_omd_action" value="add_attachment">
                    <input type="hidden" name="entity_type" value="estimate">
                    <input type="hidden" name="entity_id" value="<?php echo esc_attr($selected_estimate['id']); ?>">
                    <input type="hidden" name="attachment_id" value="" class="erp-omd-media-id">
                    <button type="button" class="button erp-omd-media-button"><?php esc_html_e('Wybierz z Media Library', 'erp-omd'); ?></button>
                    <span class="erp-omd-media-name"><?php esc_html_e('Nie wybrano pliku.', 'erp-omd'); ?></span>
                    <input type="text" name="label" class="regular-text" placeholder="<?php echo esc_attr__('Etykieta załącznika', 'erp-omd'); ?>">
                    <button type="submit" class="button button-secondary"><?php esc_html_e('Dodaj załącznik', 'erp-omd'); ?></button>
                </form>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Etykieta', 'erp-omd'); ?></th>
                            <th><?php esc_html_e('Plik', 'erp-omd'); ?></th>
                            <th><?php esc_html_e('Dodano', 'erp-omd'); ?></th>
                            <th><?php esc_html_e('Akcje', 'erp-omd'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($estimate_attachments)) : ?>
                            <tr>
                                <td colspan="4"><?php esc_html_e('Brak załączników dla tego kosztorysu.', 'erp-omd'); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($estimate_attachments as $estimate_attachment) : ?>
                            <?php
                            $attachment_post = get_post((int) ($estimate_attachment['attachment_id'] ?? 0));
                            $attachment_title = get_the_title((int) ($estimate_attachment['attachment_id'] ?? 0));
                            $attachment_url = wp_get_attachment_url((int) ($estimate_attachment['attachment_id'] ?? 0));
                            $attachment_name = $attachment_title ?: ((is_object($attachment_post) && ! empty($attachment_post->post_name)) ? $attachment_post->post_name : ('#' . (int) $estimate_attachment['attachment_id']));
                            ?>
                            <tr>
                                <td><?php echo esc_html($estimate_attachment['label'] ?: '—'); ?></td>
                                <td>
                                    <?php if ($attachment_url) : ?>
                                        <a href="<?php echo esc_url($attachment_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($attachment_name); ?></a>
                                    <?php else : ?>
                                        <?php echo esc_html($attachment_name); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($estimate_attachment['created_at'] ?? '—'); ?></td>
                                <td>
                                    <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Usunąć załącznik?', 'erp-omd')); ?>');">
                                        <?php wp_nonce_field('erp_omd_delete_attachment_' . (int) $estimate_attachment['id']); ?>
                                        <input type="hidden" name="erp_omd_action" value="delete_attachment">
                                        <input type="hidden" name="attachment_relation_id" value="<?php echo esc_attr($estimate_attachment['id']); ?>">
                                        <button type="submit" class="button button-small button-link-delete"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
    </section>
</div>
