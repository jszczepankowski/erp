<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('Kosztorysy', 'erp-omd'); ?></h1>

    <section class="erp-omd-card">
            <h2><?php echo $estimate ? esc_html__('Edytuj kosztorys', 'erp-omd') : esc_html__('Nowy kosztorys', 'erp-omd'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('erp_omd_save_estimate'); ?>
                <input type="hidden" name="erp_omd_action" value="save_estimate">
                <input type="hidden" name="id" value="<?php echo esc_attr($estimate['id'] ?? 0); ?>">
                <p class="erp-omd-form-intro"><?php esc_html_e('Kosztorys ma teraz krótsze sekcje z lepiej wykorzystaną szerokością ekranu, dzięki czemu łatwiej przejść od podstaw do lifecycle.', 'erp-omd'); ?></p>
                <div class="erp-omd-form-sections">
                    <section class="erp-omd-form-section">
                        <div class="erp-omd-form-section-header">
                            <h3><?php esc_html_e('Podstawy kosztorysu', 'erp-omd'); ?></h3>
                            <p><?php esc_html_e('Nazwa i klient, dla którego przygotowujemy wycenę.', 'erp-omd'); ?></p>
                        </div>
                        <div class="erp-omd-form-grid">
                            <div class="erp-omd-form-field erp-omd-form-field-span-2">
                                <label for="estimate-name"><?php esc_html_e('Nazwa kosztorysu', 'erp-omd'); ?></label>
                                <input id="estimate-name" name="name" type="text" class="regular-text" value="<?php echo esc_attr($estimate['name'] ?? ''); ?>" required>
                                <p class="description"><?php esc_html_e('Np. Audyt SEO Q2 2026 albo Kampania launchowa.', 'erp-omd'); ?></p>
                            </div>
                            <div class="erp-omd-form-field">
                                <label for="estimate-client-id"><?php esc_html_e('Klient', 'erp-omd'); ?></label>
                                <select id="estimate-client-id" name="client_id" required>
                                    <option value=""><?php esc_html_e('Wybierz klienta', 'erp-omd'); ?></option>
                                    <?php foreach ($clients as $client_row) : ?>
                                        <option value="<?php echo esc_attr($client_row['id']); ?>" <?php selected((int) ($estimate['client_id'] ?? 0), (int) $client_row['id']); ?>><?php echo esc_html($client_row['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </section>
                    <section class="erp-omd-form-section">
                        <div class="erp-omd-form-section-header">
                            <h3><?php esc_html_e('Lifecycle', 'erp-omd'); ?></h3>
                            <p><?php esc_html_e('Stan pracy nad kosztorysem oraz informacje o akceptacji.', 'erp-omd'); ?></p>
                        </div>
                        <div class="erp-omd-form-grid">
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="estimate-status"><?php esc_html_e('Status', 'erp-omd'); ?></label>
                                <select id="estimate-status" name="status">
                                    <?php foreach (['wstepny', 'do_akceptacji', 'zaakceptowany'] as $status_option) : ?>
                                        <option value="<?php echo esc_attr($status_option); ?>" <?php selected((string) ($estimate['status'] ?? 'wstepny'), $status_option); ?>><?php echo esc_html($status_option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="erp-omd-form-field">
                                <span class="erp-omd-form-label"><?php esc_html_e('Informacja o akceptacji', 'erp-omd'); ?></span>
                                <?php if (($estimate['status'] ?? '') === 'zaakceptowany') : ?>
                                    <p class="description"><?php esc_html_e('Zaakceptowany kosztorys zachowuje zablokowane pozycje i dane klienta, ale administrator nadal może zmienić jego status.', 'erp-omd'); ?></p>
                                <?php else : ?>
                                    <p class="description"><?php esc_html_e('Po akceptacji kosztorys pozostaje czytelny, ale jego pozycje przechodzą w tryb tylko do odczytu.', 'erp-omd'); ?></p>
                                <?php endif; ?>
                                <?php if (! empty($estimate['accepted_at'])) : ?>
                                    <p class="description"><?php echo esc_html(sprintf(__('Zaakceptowano: %s', 'erp-omd'), $estimate['accepted_at'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                    <?php if (! $estimate) : ?>
                        <section class="erp-omd-form-section">
                            <div class="erp-omd-form-section-header">
                                <h3><?php esc_html_e('Pierwsza pozycja kosztorysu', 'erp-omd'); ?></h3>
                                <p><?php esc_html_e('Kosztorys nie może zostać utworzony bez minimum jednej pozycji.', 'erp-omd'); ?></p>
                            </div>
                            <div class="erp-omd-form-grid">
                                <div class="erp-omd-form-field erp-omd-form-field-span-2">
                                    <label for="estimate-initial-item-name"><?php esc_html_e('Nazwa pozycji', 'erp-omd'); ?></label>
                                    <input id="estimate-initial-item-name" name="initial_item_name" type="text" class="regular-text" required>
                                </div>
                                <div class="erp-omd-form-field erp-omd-form-field-compact">
                                    <label for="estimate-initial-item-qty"><?php esc_html_e('Ilość', 'erp-omd'); ?></label>
                                    <input id="estimate-initial-item-qty" name="initial_item_qty" type="number" step="0.01" min="0.01" value="1" required>
                                </div>
                                <div class="erp-omd-form-field erp-omd-form-field-compact">
                                    <label for="estimate-initial-item-price"><?php esc_html_e('Cena', 'erp-omd'); ?></label>
                                    <input id="estimate-initial-item-price" name="initial_item_price" type="number" step="0.01" min="0" value="0" required>
                                </div>
                                <div class="erp-omd-form-field erp-omd-form-field-compact">
                                    <label for="estimate-initial-item-cost"><?php esc_html_e('Koszt wewnętrzny', 'erp-omd'); ?></label>
                                    <input id="estimate-initial-item-cost" name="initial_item_cost_internal" type="number" step="0.01" min="0" value="0" required>
                                </div>
                                <div class="erp-omd-form-field erp-omd-form-field-span-2">
                                    <label for="estimate-initial-item-comment"><?php esc_html_e('Komentarz', 'erp-omd'); ?></label>
                                    <textarea id="estimate-initial-item-comment" name="initial_item_comment" rows="3" class="large-text"></textarea>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>
                <div class="erp-omd-form-actions">
                    <?php submit_button($estimate ? __('Zapisz kosztorys', 'erp-omd') : __('Dodaj kosztorys', 'erp-omd')); ?>
                    <?php if ($estimate) : ?>
                        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=erp-omd-estimates&id=' . (int) $estimate['id'])); ?>"><?php esc_html_e('Przejdź do szczegółów', 'erp-omd'); ?></a>
                    <?php endif; ?>
                </div>
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
                        <div class="erp-omd-form-sections">
                            <section class="erp-omd-form-section">
                                <div class="erp-omd-form-grid">
                                    <div class="erp-omd-form-field erp-omd-form-field-span-2">
                                        <label for="estimate-item-name"><?php esc_html_e('Nazwa', 'erp-omd'); ?></label>
                                        <input id="estimate-item-name" name="name" type="text" class="regular-text" value="<?php echo esc_attr($editing_estimate_item['name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="erp-omd-form-grid erp-omd-form-grid-estimate-item-pricing erp-omd-form-field erp-omd-form-field-span-2">
                                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                                            <label for="estimate-item-qty"><?php esc_html_e('Ilość', 'erp-omd'); ?></label>
                                            <input id="estimate-item-qty" name="qty" type="number" step="0.01" min="0.01" value="<?php echo esc_attr($editing_estimate_item['qty'] ?? '1'); ?>" required>
                                        </div>
                                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                                            <label for="estimate-item-price"><?php esc_html_e('Cena', 'erp-omd'); ?></label>
                                            <input id="estimate-item-price" name="price" type="number" step="0.01" min="0" value="<?php echo esc_attr($editing_estimate_item['price'] ?? '0'); ?>" required>
                                        </div>
                                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                                            <label for="estimate-item-cost-internal"><?php esc_html_e('Koszt wewnętrzny', 'erp-omd'); ?></label>
                                            <input id="estimate-item-cost-internal" name="cost_internal" type="number" step="0.01" min="0" value="<?php echo esc_attr($editing_estimate_item['cost_internal'] ?? '0'); ?>" required>
                                        </div>
                                    </div>
                                    <div class="erp-omd-form-field erp-omd-form-field-span-2">
                                        <label for="estimate-item-comment"><?php esc_html_e('Komentarz', 'erp-omd'); ?></label>
                                        <textarea id="estimate-item-comment" name="comment" rows="3" class="large-text"><?php echo esc_textarea($editing_estimate_item['comment'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </section>
                        </div>
                        <div class="erp-omd-form-actions">
                            <?php submit_button($editing_estimate_item ? __('Zapisz pozycję', 'erp-omd') : __('Dodaj pozycję', 'erp-omd'), 'secondary'); ?>
                            <?php if ($editing_estimate_item) : ?>
                                <a class="button" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-estimates', 'id' => (int) $selected_estimate['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Anuluj edycję pozycji', 'erp-omd'); ?></a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <form method="post" class="erp-omd-inline-form">
                        <?php wp_nonce_field('erp_omd_save_estimate'); ?>
                        <input type="hidden" name="erp_omd_action" value="save_estimate">
                        <input type="hidden" name="id" value="<?php echo esc_attr((string) ($selected_estimate['id'] ?? 0)); ?>">
                        <input type="hidden" name="name" value="<?php echo esc_attr((string) ($selected_estimate['name'] ?? '')); ?>">
                        <input type="hidden" name="client_id" value="<?php echo esc_attr((string) ($selected_estimate['client_id'] ?? 0)); ?>">
                        <input type="hidden" name="status" value="<?php echo esc_attr((string) ($selected_estimate['status'] ?? 'wstepny')); ?>">
                        <div class="erp-omd-form-actions">
                            <?php submit_button(__('Zapisz kosztorys', 'erp-omd'), 'primary'); ?>
                        </div>
                    </form>
                <?php else : ?>
                    <p><?php esc_html_e('Po zaakceptowaniu pozycje kosztorysu są tylko do odczytu — można je przeglądać i eksportować, ale status kosztorysu administrator może nadal zmienić z poziomu formularza edycji.', 'erp-omd'); ?></p>
                <?php endif; ?>
            <?php endif; ?>
    </section>

    <section class="erp-omd-card">
            <h2><?php esc_html_e('Lista kosztorysów', 'erp-omd'); ?></h2>
            <form method="get" class="erp-omd-filter-form">
                <input type="hidden" name="page" value="erp-omd-estimates" />
                <input type="search" name="search" class="regular-text" placeholder="<?php echo esc_attr__('Szukaj kosztorysu, klienta, projektu…', 'erp-omd'); ?>" value="<?php echo esc_attr($estimate_filters['search'] ?? ''); ?>">
                <select name="client_id"><option value="0"><?php esc_html_e('Wszyscy klienci', 'erp-omd'); ?></option><?php foreach ($clients as $client_row) : ?><option value="<?php echo esc_attr($client_row['id']); ?>" <?php selected((int) ($estimate_filters['client_id'] ?? 0), (int) $client_row['id']); ?>><?php echo esc_html($client_row['name']); ?></option><?php endforeach; ?></select>
                <select name="status"><option value=""><?php esc_html_e('Wszystkie statusy', 'erp-omd'); ?></option><?php foreach (['wstepny', 'do_akceptacji', 'zaakceptowany'] as $status_option) : ?><option value="<?php echo esc_attr($status_option); ?>" <?php selected($estimate_filters['status'] ?? '', $status_option); ?>><?php echo esc_html($status_option); ?></option><?php endforeach; ?></select>
                <button class="button" type="submit"><?php esc_html_e('Filtruj', 'erp-omd'); ?></button>
            </form>
            <form method="post" id="erp-omd-bulk-estimates-form">
                <?php wp_nonce_field('erp_omd_bulk_estimates'); ?>
                <input type="hidden" name="erp_omd_action" value="bulk_estimates">
                <div class="tablenav top"><div class="alignleft actions"><select name="bulk_action"><option value=""><?php esc_html_e('Akcje masowe', 'erp-omd'); ?></option><option value="accept"><?php esc_html_e('Akceptuj', 'erp-omd'); ?></option><option value="delete"><?php esc_html_e('Usuń', 'erp-omd'); ?></option></select><button class="button action" type="submit"><?php esc_html_e('Zastosuj', 'erp-omd'); ?></button></div></div>
            </form>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" onclick="document.querySelectorAll('.erp-omd-estimate-checkbox').forEach(function(checkbox){ checkbox.checked = this.checked; }.bind(this));" /></th>
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
                    <?php if (empty($estimates)) : ?><tr><td colspan="9"><?php esc_html_e('Brak kosztorysów dla wybranych filtrów. Zmień kryteria albo dodaj nowy kosztorys.', 'erp-omd'); ?></td></tr><?php endif; ?>
                    <?php foreach ($estimates as $estimate_row) : ?>
                        <?php $estimate_label = trim((string) ($estimate_row['name'] ?? '')) !== '' ? (string) $estimate_row['name'] : sprintf(__('Kosztorys #%d', 'erp-omd'), (int) $estimate_row['id']); ?>
                        <tr>
                            <td><input class="erp-omd-estimate-checkbox" type="checkbox" name="estimate_ids[]" value="<?php echo esc_attr($estimate_row['id']); ?>" form="erp-omd-bulk-estimates-form" /></td>
                            <td>#<?php echo esc_html($estimate_row['id']); ?></td>
                            <td>
                                <?php echo esc_html($estimate_label); ?>
                                <?php $this->render_alert_icons($estimate_row['alerts'] ?? []); ?>
                            </td>
                            <td><?php echo esc_html($estimate_row['client_name']); ?></td>
                            <td><span class="erp-omd-badge <?php echo esc_attr($this->status_badge_class($estimate_row['status'], 'estimate')); ?>"><?php echo esc_html($estimate_row['status']); ?></span></td>
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
                                <details class="erp-omd-list-actions">
                                    <summary class="button button-small"><?php esc_html_e('Akcje', 'erp-omd'); ?></summary>
                                    <div class="erp-omd-list-actions-menu">
                                        <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-estimates', 'id' => (int) $estimate_row['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Szczegóły', 'erp-omd'); ?></a>
                                        <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-estimates', 'id' => (int) $estimate_row['id'], 'edit' => 1], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                        <?php if (($estimate_row['status'] ?? '') !== 'zaakceptowany') : ?>
                                            <form method="post" class="erp-omd-inline-form">
                                                <?php wp_nonce_field('erp_omd_accept_estimate'); ?>
                                                <input type="hidden" name="erp_omd_action" value="accept_estimate">
                                                <input type="hidden" name="estimate_id" value="<?php echo esc_attr($estimate_row['id']); ?>">
                                                <button type="submit" class="button button-small button-primary"><?php esc_html_e('Akceptuj', 'erp-omd'); ?></button>
                                            </form>
                                            <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Usunąć kosztorys?', 'erp-omd')); ?>');">
                                                <?php wp_nonce_field('erp_omd_delete_estimate'); ?>
                                                <input type="hidden" name="erp_omd_action" value="delete_estimate">
                                                <input type="hidden" name="id" value="<?php echo esc_attr($estimate_row['id']); ?>">
                                                <button type="submit" class="button button-small button-link-delete"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </details>
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
                <div class="erp-omd-form-sections">
                    <section class="erp-omd-form-section">
                        <div class="erp-omd-form-section-header">
                            <h3><?php esc_html_e('Podsumowanie kosztorysu', 'erp-omd'); ?></h3>
                            <p><?php esc_html_e('Najważniejsze informacje i wartości finansowe w układzie zgodnym z innymi ekranami administracyjnymi.', 'erp-omd'); ?></p>
                        </div>
                        <div class="erp-omd-detail-grid">
                            <div class="erp-omd-detail-card">
                                <h3><?php esc_html_e('Status i kontekst', 'erp-omd'); ?></h3>
                                <div class="erp-omd-detail-list">
                                    <div class="erp-omd-detail-item"><strong><?php esc_html_e('Klient', 'erp-omd'); ?></strong><span><?php echo esc_html($selected_estimate['client_name'] ?? '—'); ?></span></div>
                                    <div class="erp-omd-detail-item"><strong><?php esc_html_e('Status', 'erp-omd'); ?></strong><span><span class="erp-omd-badge <?php echo esc_attr($this->status_badge_class($selected_estimate['status'], 'estimate')); ?>"><?php echo esc_html($selected_estimate['status']); ?></span></span></div>
                                    <div class="erp-omd-detail-item"><strong><?php esc_html_e('Akceptacja', 'erp-omd'); ?></strong><span><?php echo esc_html($selected_estimate['accepted_at'] ?? '—'); ?></span></div>
                                </div>
                            </div>
                            <div class="erp-omd-detail-card">
                                <h3><?php esc_html_e('Wartości finansowe', 'erp-omd'); ?></h3>
                                <div class="erp-omd-detail-list">
                                    <div class="erp-omd-detail-item"><strong><?php esc_html_e('Netto', 'erp-omd'); ?></strong><span><?php echo esc_html(number_format_i18n((float) $estimate_totals['net'], 2)); ?></span></div>
                                    <div class="erp-omd-detail-item"><strong><?php esc_html_e('VAT 23%', 'erp-omd'); ?></strong><span><?php echo esc_html(number_format_i18n((float) $estimate_totals['tax'], 2)); ?></span></div>
                                    <div class="erp-omd-detail-item"><strong><?php esc_html_e('Brutto', 'erp-omd'); ?></strong><span><?php echo esc_html(number_format_i18n((float) $estimate_totals['gross'], 2)); ?></span></div>
                                    <div class="erp-omd-detail-item"><strong><?php esc_html_e('Koszt wewnętrzny', 'erp-omd'); ?></strong><span><?php echo esc_html(number_format_i18n((float) $estimate_totals['internal_cost'], 2)); ?></span></div>
                                </div>
                            </div>
                        </div>
                        <?php if ($linked_project) : ?>
                            <div class="erp-omd-detail-item">
                                <strong><?php esc_html_e('Powiązany projekt', 'erp-omd'); ?></strong>
                                <span><a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-projects', 'id' => (int) $linked_project['id']], admin_url('admin.php'))); ?>"><?php echo esc_html($linked_project['name']); ?></a><?php $this->render_alert_icons($selected_estimate['alerts'] ?? []); ?></span>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="erp-omd-form-section">
                        <div class="erp-omd-form-section-header">
                            <h3><?php esc_html_e('Pozycje kosztorysu', 'erp-omd'); ?></h3>
                            <p><?php esc_html_e('Lista pozycji i operacje edycji w jednym, sekcyjnym widoku.', 'erp-omd'); ?></p>
                        </div>
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
                                        <td colspan="6"><?php esc_html_e('Brak pozycji kosztorysu. Dodaj pozycję, aby móc policzyć netto, brutto i koszt wewnętrzny.', 'erp-omd'); ?></td>
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
                                                <details class="erp-omd-list-actions">
                                                    <summary class="button button-small"><?php esc_html_e('Akcje', 'erp-omd'); ?></summary>
                                                    <div class="erp-omd-list-actions-menu">
                                                        <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-estimates', 'id' => (int) $selected_estimate['id'], 'edit' => 1, 'item_id' => (int) $item_row['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                                        <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Usunąć pozycję kosztorysu?', 'erp-omd')); ?>');">
                                                            <?php wp_nonce_field('erp_omd_delete_estimate_item'); ?>
                                                            <input type="hidden" name="erp_omd_action" value="delete_estimate_item">
                                                            <input type="hidden" name="estimate_id" value="<?php echo esc_attr($selected_estimate['id']); ?>">
                                                            <input type="hidden" name="item_id" value="<?php echo esc_attr($item_row['id']); ?>">
                                                            <button type="submit" class="button button-small button-link-delete"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                                        </form>
                                                    </div>
                                                </details>
                                            <?php else : ?>
                                                &mdash;
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </section>

                    <section class="erp-omd-form-section">
                        <div class="erp-omd-form-section-header">
                            <h3><?php esc_html_e('Załączniki', 'erp-omd'); ?></h3>
                            <p><?php esc_html_e('Dodaj plik z biblioteki mediów WordPress do kosztorysu.', 'erp-omd'); ?></p>
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
                    </section>
                </div>
            <?php endif; ?>
    </section>
</div>
