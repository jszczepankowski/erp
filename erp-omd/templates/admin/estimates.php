<?php $estimate_status_labels = ['wstepny' => __('Wstępny', 'erp-omd'), 'do_akceptacji' => __('Do akceptacji', 'erp-omd'), 'odrzucony' => __('Odrzucony', 'erp-omd'), 'zaakceptowany' => __('Zaakceptowany', 'erp-omd')]; ?>
<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('Kosztorysy', 'erp-omd'); ?></h1>

    <?php $show_estimate_editor = ! $selected_estimate || $estimate; ?>
    <?php if ($show_estimate_editor) : ?>
    <section class="erp-omd-card">
            <h2><?php echo $estimate ? esc_html__('Edytuj kosztorys', 'erp-omd') : esc_html__('Nowy kosztorys', 'erp-omd'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('erp_omd_save_estimate'); ?>
                <input type="hidden" name="erp_omd_action" value="save_estimate">
                <input type="hidden" name="id" value="<?php echo esc_attr($estimate['id'] ?? 0); ?>">
                <div class="erp-omd-form-sections">
                    <section class="erp-omd-form-section">
                        <div class="erp-omd-form-section-header">
                            <h3><?php esc_html_e('Podstawy kosztorysu + lifecycle', 'erp-omd'); ?></h3>
                            <p><?php esc_html_e('Nazwa, klient i status kosztorysu w jednym wierszu.', 'erp-omd'); ?></p>
                        </div>
                        <div class="erp-omd-form-grid erp-omd-form-grid-estimate-basics-lifecycle">
                            <div class="erp-omd-form-field">
                                <label for="estimate-name"><?php esc_html_e('Nazwa kosztorysu', 'erp-omd'); ?></label>
                                <input id="estimate-name" name="name" type="text" class="regular-text" value="<?php echo esc_attr($estimate['name'] ?? ''); ?>" required>
                                <p class="description"><?php esc_html_e('Np. Kampania launchowa produktu.', 'erp-omd'); ?></p>
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
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="estimate-status"><?php esc_html_e('Status', 'erp-omd'); ?></label>
                                <select id="estimate-status" name="status">
                                    <?php foreach (['wstepny', 'do_akceptacji', 'odrzucony', 'zaakceptowany'] as $status_option) : ?>
                                        <option value="<?php echo esc_attr($status_option); ?>" <?php selected((string) ($estimate['status'] ?? 'wstepny'), $status_option); ?>><?php echo esc_html($estimate_status_labels[$status_option] ?? $status_option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="erp-omd-form-grid" style="margin-top:12px;">
                            <div class="erp-omd-form-field">
                                <label for="estimate-preferred-delivery-date"><?php esc_html_e('Preferowany termin realizacji', 'erp-omd'); ?></label>
                                <input id="estimate-preferred-delivery-date" name="preferred_delivery_date" type="date" value="<?php echo esc_attr((string) ($estimate_accept_meta['preferred_delivery_date'] ?? '')); ?>">
                            </div>
                            <div class="erp-omd-form-field">
                                <label for="estimate-invoice-nip"><?php esc_html_e('NIP / dane podmiotu do faktury', 'erp-omd'); ?></label>
                                <input id="estimate-invoice-nip" name="invoice_nip" type="text" value="<?php echo esc_attr((string) ($estimate_accept_meta['invoice_nip'] ?? '')); ?>">
                            </div>
                            <div class="erp-omd-form-field erp-omd-form-field-span-2">
                                <label for="estimate-delivery-address"><?php esc_html_e('Szczegóły miejsca dostawy', 'erp-omd'); ?></label>
                                <textarea id="estimate-delivery-address" name="delivery_address" rows="2" class="large-text"><?php echo esc_textarea((string) ($estimate_accept_meta['delivery_address'] ?? '')); ?></textarea>
                            </div>
                            <div class="erp-omd-form-field erp-omd-form-field-span-2">
                                <label for="estimate-note"><?php esc_html_e('Uwagi do kosztorysu', 'erp-omd'); ?></label>
                                <textarea id="estimate-note" name="estimate_note" rows="3" class="large-text"><?php echo esc_textarea((string) ($estimate_accept_meta['note'] ?? '')); ?></textarea>
                            </div>
                        </div>
                    </section>
                    <?php if (! $estimate) : ?>
                        <section class="erp-omd-form-section">
                            <div class="erp-omd-form-section-header">
                                <h3><?php esc_html_e('Pozycje kosztorysu', 'erp-omd'); ?></h3>
                                <p><?php esc_html_e('Dodaj minimum jedną pozycję. Możesz od razu dodać wiele pozycji przed zapisaniem kosztorysu.', 'erp-omd'); ?></p>
                            </div>
                            <div class="erp-omd-estimate-create-items" data-admin-initial-items>
                                <div class="erp-omd-form-grid erp-omd-estimate-create-item-row" data-admin-initial-item-row>
                                    <div class="erp-omd-form-grid erp-omd-form-grid-estimate-item-row erp-omd-form-grid-estimate-item-row-with-suggest erp-omd-form-field erp-omd-form-field-span-2" data-admin-price-row>
                                        <div class="erp-omd-form-field">
                                            <label><?php esc_html_e('Nazwa pozycji', 'erp-omd'); ?></label>
                                            <input name="initial_item_name[]" type="text" class="regular-text" required>
                                        </div>
                                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                                            <label><?php esc_html_e('Ilość', 'erp-omd'); ?></label>
                                            <input name="initial_item_qty[]" type="number" step="0.01" min="0.01" value="1" required>
                                        </div>
                                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                                            <label><?php esc_html_e('Koszt wewnętrzny', 'erp-omd'); ?></label>
                                            <input name="initial_item_cost_internal[]" type="number" step="0.01" min="0" value="0" required data-cost-input>
                                        </div>
                                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                                            <label><?php esc_html_e('Marża (%)', 'erp-omd'); ?></label>
                                            <input name="initial_item_margin_percent[]" type="number" step="0.01" min="0" max="500" value="0" required data-margin-input>
                                        </div>
                                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                                            <label><?php esc_html_e('Cena', 'erp-omd'); ?></label>
                                            <input name="initial_item_price[]" type="number" step="0.01" min="0" value="0" required data-price-input>
                                        </div>
                                        <div class="erp-omd-form-field erp-omd-form-field-compact erp-omd-form-field-inline-action">
                                            <label>&nbsp;</label>
                                            <button type="button" class="button button-secondary" data-admin-suggest-price><?php esc_html_e('Zasugeruj cenę', 'erp-omd'); ?></button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="initial_item_price_source[]" value="manual">
                                    <div class="erp-omd-form-field erp-omd-form-field-span-2">
                                        <label><?php esc_html_e('Komentarz', 'erp-omd'); ?></label>
                                        <textarea name="initial_item_comment[]" rows="3" class="large-text"></textarea>
                                    </div>
                                        <div class="erp-omd-form-field erp-omd-form-field-span-2 erp-omd-estimate-create-item-actions">
                                        <button type="button" class="button button-link-delete" data-admin-remove-item><?php esc_html_e('Usuń pozycję', 'erp-omd'); ?></button>
                                    </div>
                                </div>
                            </div>
                            <div class="erp-omd-form-actions">
                                <button type="button" class="button button-secondary" data-admin-add-item><?php esc_html_e('Dodaj kolejną pozycję', 'erp-omd'); ?></button>
                            </div>
                            <div class="erp-omd-estimate-create-preview">
                                <h4><?php esc_html_e('Pozycje kosztorysu (podgląd)', 'erp-omd'); ?></h4>
                                <table class="widefat striped">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Nazwa pozycji', 'erp-omd'); ?></th>
                                            <th><?php esc_html_e('Ilość', 'erp-omd'); ?></th>
                                            <th><?php esc_html_e('Cena', 'erp-omd'); ?></th>
                                            <th><?php esc_html_e('Wartość', 'erp-omd'); ?></th>
                                            <th><?php esc_html_e('Koszt wewnętrzny', 'erp-omd'); ?></th>
                                            <th><?php esc_html_e('Marża', 'erp-omd'); ?></th>
                                            <th><?php esc_html_e('Komentarz', 'erp-omd'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody data-admin-preview-items>
                                        <tr><td colspan="7"><?php esc_html_e('Brak pozycji do podglądu.', 'erp-omd'); ?></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>
                <div class="erp-omd-form-actions">
                    <?php submit_button($estimate ? __('Zapisz kosztorys', 'erp-omd') : __('Utwórz kosztorys', 'erp-omd')); ?>
                    <?php if ($estimate) : ?>
                        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=erp-omd-estimates&id=' . (int) $estimate['id'])); ?>"><?php esc_html_e('Przejdź do szczegółów', 'erp-omd'); ?></a>
                    <?php endif; ?>
                </div>
            </form>

    </section>
    <?php endif; ?>

    <?php if (! $estimate) : ?>
        <script>
            (function () {
                var root = document.querySelector('[data-admin-initial-items]');
                var addButton = document.querySelector('[data-admin-add-item]');
                var previewBody = document.querySelector('[data-admin-preview-items]');
                if (!root || !addButton) {
                    return;
                }

                var escapeHtml = function (value) {
                    return String(value)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                };

                var renderPreview = function () {
                    if (!previewBody) {
                        return;
                    }

                    var rows = root.querySelectorAll('[data-admin-initial-item-row]');
                    var previewRows = [];

                    rows.forEach(function (row) {
                        var name = (row.querySelector('input[name="initial_item_name[]"]') || {}).value || '';
                        var qtyRaw = (row.querySelector('input[name="initial_item_qty[]"]') || {}).value || '0';
                        var priceRaw = (row.querySelector('input[name="initial_item_price[]"]') || {}).value || '0';
                        var internalRaw = (row.querySelector('input[name="initial_item_cost_internal[]"]') || {}).value || '0';
                        var comment = (row.querySelector('textarea[name="initial_item_comment[]"]') || {}).value || '';
                        var marginRaw = (row.querySelector('input[name="initial_item_margin_percent[]"]') || {}).value || '0';
                        var qty = parseFloat(qtyRaw) || 0;
                        var price = parseFloat(priceRaw) || 0;
                        var internalCost = parseFloat(internalRaw) || 0;
                        var total = qty * price;
                        var margin = parseFloat(marginRaw) || 0;
                        if (name === '' && qty === 0 && price === 0 && internalCost === 0 && comment === '') {
                            return;
                        }
                        previewRows.push(
                            '<tr>' +
                            '<td>' + escapeHtml(name || '—') + '</td>' +
                            '<td>' + escapeHtml(qty.toFixed(2)) + '</td>' +
                            '<td>' + escapeHtml(price.toFixed(2)) + '</td>' +
                            '<td>' + escapeHtml(total.toFixed(2)) + '</td>' +
                            '<td>' + escapeHtml(internalCost.toFixed(2)) + '</td>' +
                            '<td>' + escapeHtml(margin.toFixed(2)) + '%</td>' +
                            '<td>' + escapeHtml(comment || '—') + '</td>' +
                            '</tr>'
                        );
                    });

                    previewBody.innerHTML = previewRows.length > 0
                        ? previewRows.join('')
                        : '<tr><td colspan="7"><?php echo esc_js(__('Brak pozycji do podglądu.', 'erp-omd')); ?></td></tr>';
                };

                var updateRemoveButtons = function () {
                    var rows = root.querySelectorAll('[data-admin-initial-item-row]');
                    rows.forEach(function (row, index) {
                        var removeButton = row.querySelector('[data-admin-remove-item]');
                        if (removeButton) {
                            removeButton.disabled = rows.length === 1;
                            removeButton.hidden = rows.length === 1;
                        }
                        var nameInput = row.querySelector('input[name="initial_item_name[]"]');
                        if (nameInput) {
                            nameInput.required = index === 0;
                        }
                    });
                };

                addButton.addEventListener('click', function () {
                    var firstRow = root.querySelector('[data-admin-initial-item-row]');
                    if (!firstRow) {
                        return;
                    }
                    var clone = firstRow.cloneNode(true);
                    clone.querySelectorAll('input, textarea').forEach(function (field) {
                        if (field.name === 'initial_item_qty[]') {
                            field.value = '1';
                        } else if (field.name === 'initial_item_price[]' || field.name === 'initial_item_cost_internal[]') {
                            field.value = '0';
                        } else if (field.name === 'initial_item_margin_percent[]') {
                            field.value = '0';
                        } else if (field.name === 'initial_item_price_source[]') {
                            field.value = 'manual';
                        } else {
                            field.value = '';
                        }
                        field.required = false;
                    });
                    root.appendChild(clone);
                    updateRemoveButtons();
                    renderPreview();
                });

                root.addEventListener('click', function (event) {
                    var suggestButton = event.target.closest('[data-admin-suggest-price]');
                    if (suggestButton) {
                        var suggestRow = suggestButton.closest('[data-admin-initial-item-row]');
                        if (!suggestRow) {
                            return;
                        }
                        var costInput = suggestRow.querySelector('input[name="initial_item_cost_internal[]"]');
                        var marginInput = suggestRow.querySelector('input[name="initial_item_margin_percent[]"]');
                        var priceInput = suggestRow.querySelector('input[name="initial_item_price[]"]');
                        var priceSourceInput = suggestRow.querySelector('input[name="initial_item_price_source[]"]');
                        var cost = parseFloat((costInput || {}).value || '0');
                        var margin = parseFloat((marginInput || {}).value || '0');
                        if (!isFinite(cost) || cost < 0 || !isFinite(margin) || margin < 0 || margin > 500 || !priceInput) {
                            return;
                        }
                        var suggestedPrice = Math.round((cost * (1 + (margin / 100))) * 100) / 100;
                        priceInput.value = suggestedPrice.toFixed(2);
                        if (priceSourceInput) {
                            priceSourceInput.value = 'suggested';
                        }
                        renderPreview();
                        return;
                    }
                    var button = event.target.closest('[data-admin-remove-item]');
                    if (!button) {
                        return;
                    }
                    var row = button.closest('[data-admin-initial-item-row]');
                    if (!row) {
                        return;
                    }
                    var rows = root.querySelectorAll('[data-admin-initial-item-row]');
                    if (rows.length <= 1) {
                        return;
                    }
                    row.remove();
                    updateRemoveButtons();
                    renderPreview();
                });

                root.addEventListener('input', function (event) {
                    var priceField = event.target.closest('input[name="initial_item_price[]"]');
                    if (priceField) {
                        var row = priceField.closest('[data-admin-initial-item-row]');
                        var sourceField = row ? row.querySelector('input[name="initial_item_price_source[]"]') : null;
                        if (sourceField) {
                            sourceField.value = 'manual';
                        }
                    }
                    renderPreview();
                });

                updateRemoveButtons();
                renderPreview();
            }());
        </script>
    <?php endif; ?>
    <script>
        (function () {
            var suggest = function (row) {
                var costInput = row.querySelector('[data-cost-input]');
                var marginInput = row.querySelector('[data-margin-input]');
                var priceInput = row.querySelector('[data-price-input]');
                var rowForm = row.closest('form');
                var sourceInput = rowForm ? rowForm.querySelector('input[name="price_source"],input[name="initial_item_price_source[]"]') : null;
                var normalizeNumber = function (value) {
                    return parseFloat(String(value || '0').replace(',', '.'));
                };
                var cost = normalizeNumber((costInput || {}).value || '0');
                var margin = normalizeNumber((marginInput || {}).value || '0');
                if (!isFinite(cost) || cost < 0 || !isFinite(margin) || margin < 0 || margin > 500 || !priceInput) {
                    return;
                }
                var suggestedPrice = Math.round((cost * (1 + (margin / 100))) * 100) / 100;
                priceInput.value = suggestedPrice.toFixed(2);
                if (sourceInput) {
                    sourceInput.value = 'suggested';
                }
            };

            document.addEventListener('click', function (event) {
                var button = event.target.closest('[data-admin-suggest-price]');
                if (!button) {
                    return;
                }
                var row = button.closest('[data-admin-price-row]');
                if (!row) {
                    return;
                }
                suggest(row);
            });


            document.addEventListener('input', function (event) {
                var marginInput = event.target.closest('[data-margin-input]');
                if (!marginInput) {
                    return;
                }
                var marginValue = parseFloat(String(marginInput.value || '0').replace(',', '.'));
                if (isFinite(marginValue) && marginValue > 500) {
                    marginInput.setCustomValidity('<?php echo esc_js(__('Marża jest za wysoka. Maksymalna wartość to 500%.', 'erp-omd')); ?>');
                } else {
                    marginInput.setCustomValidity('');
                }
            });

            document.addEventListener('input', function (event) {
                var priceInput = event.target.closest('[data-price-input]');
                if (!priceInput) {
                    return;
                }
                var row = priceInput.closest('[data-admin-price-row]');
                var rowForm = row ? row.closest('form') : null;
                var sourceInput = rowForm ? rowForm.querySelector('input[name="price_source"],input[name="initial_item_price_source[]"]') : null;
                if (sourceInput) {
                    sourceInput.value = 'manual';
                }
            });

        }());
    </script>

    <section class="erp-omd-card">
            <?php if ($selected_estimate) : ?>
                <?php $selected_estimate_label = trim((string) ($selected_estimate['name'] ?? '')) !== '' ? (string) $selected_estimate['name'] : sprintf(__('Kosztorys #%d', 'erp-omd'), (int) $selected_estimate['id']); ?>
                <hr>
                <div class="erp-omd-section-header">
                    <div>
                        <h2><?php echo esc_html($selected_estimate_label); ?></h2>
                        <p class="description"><?php echo esc_html(sprintf(__('Pozycje kosztorysu #%d', 'erp-omd'), (int) $selected_estimate['id'])); ?></p>
                    </div>
                    <div class="erp-omd-action-group">
                        <?php if (! empty($estimate_decision_url)) : ?>
                            <input type="text" readonly value="<?php echo esc_attr($estimate_decision_url); ?>" style="min-width:320px;" onclick="this.select();">
                        <?php endif; ?>
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
                                    <div class="erp-omd-detail-item"><strong><?php esc_html_e('Status', 'erp-omd'); ?></strong><span><span class="erp-omd-badge <?php echo esc_attr($this->status_badge_class($selected_estimate['status'], 'estimate')); ?>"><?php echo esc_html($estimate_status_labels[(string) ($selected_estimate['status'] ?? '')] ?? (string) ($selected_estimate['status'] ?? '—')); ?></span></span></div>
                                    <div class="erp-omd-detail-item"><strong><?php esc_html_e('Akceptacja', 'erp-omd'); ?></strong><span><?php echo esc_html($selected_estimate['accepted_at'] ?? '—'); ?></span></div>
                                    <div class="erp-omd-detail-item"><strong><?php esc_html_e('Wysłano do klienta', 'erp-omd'); ?></strong><span><?php echo esc_html($selected_estimate['sent_to_client_at'] ?? '—'); ?></span></div>
                                    <div class="erp-omd-detail-item"><strong><?php esc_html_e('Uwaga klienta', 'erp-omd'); ?></strong><span><?php echo esc_html($selected_estimate['client_decision_note'] ?? '—'); ?></span></div>
                                    <?php $estimate_accept_meta = (array) get_option('erp_omd_estimate_acceptance_meta_' . (int) ($selected_estimate['id'] ?? 0), []); ?>
                                    <div class="erp-omd-detail-item"><strong><?php esc_html_e('Inne miejsce dostawy', 'erp-omd'); ?></strong><span><?php echo ! empty($estimate_accept_meta['delivery_other']) ? esc_html__('Tak', 'erp-omd') : esc_html__('Nie', 'erp-omd'); ?></span></div>
                                    <div class="erp-omd-detail-item"><strong><?php esc_html_e('Adres do dostawy', 'erp-omd'); ?></strong><span><?php echo esc_html((string) ($estimate_accept_meta['delivery_address'] ?? '—')); ?></span></div>
                                    <div class="erp-omd-detail-item"><strong><?php esc_html_e('Preferowany termin realizacji', 'erp-omd'); ?></strong><span><?php echo esc_html((string) ($estimate_accept_meta['preferred_delivery_date'] ?? '—')); ?></span></div>
                                    <div class="erp-omd-detail-item"><strong><?php esc_html_e('Faktura na inny podmiot', 'erp-omd'); ?></strong><span><?php echo ! empty($estimate_accept_meta['invoice_other_entity']) ? esc_html__('Tak', 'erp-omd') : esc_html__('Nie', 'erp-omd'); ?></span></div>
                                    <div class="erp-omd-detail-item"><strong><?php esc_html_e('NIP do faktury', 'erp-omd'); ?></strong><span><?php echo esc_html((string) ($estimate_accept_meta['invoice_nip'] ?? '—')); ?></span></div>
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
                            <h3><?php echo $editing_estimate_item ? esc_html__('Edycja pozycji kosztorysu', 'erp-omd') : esc_html__('Pozycje kosztorysu', 'erp-omd'); ?></h3>
                            <p><?php esc_html_e('Lista pozycji kosztorysu z możliwością ich edycji i usuwania.', 'erp-omd'); ?></p>
                        </div>

                        <?php if ($editing_estimate_item && ($selected_estimate['status'] ?? '') !== 'zaakceptowany') : ?>
                            <form method="post">
                                <?php wp_nonce_field('erp_omd_save_estimate_item'); ?>
                                <input type="hidden" name="erp_omd_action" value="save_estimate_item">
                                <input type="hidden" name="estimate_id" value="<?php echo esc_attr($selected_estimate['id']); ?>">
                                <input type="hidden" name="item_id" value="<?php echo esc_attr($editing_estimate_item['id'] ?? 0); ?>">
                                <div class="erp-omd-form-grid">
                                    <div class="erp-omd-form-grid erp-omd-form-grid-estimate-item-row erp-omd-form-grid-estimate-item-row-with-suggest erp-omd-form-field erp-omd-form-field-span-2" data-admin-price-row>
                                        <div class="erp-omd-form-field">
                                            <label for="estimate-item-name"><?php esc_html_e('Nazwa pozycji', 'erp-omd'); ?></label>
                                            <input id="estimate-item-name" name="name" type="text" class="regular-text" value="<?php echo esc_attr($editing_estimate_item['name'] ?? ''); ?>" required>
                                        </div>
                                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                                            <label for="estimate-item-qty"><?php esc_html_e('Ilość', 'erp-omd'); ?></label>
                                            <input id="estimate-item-qty" name="qty" type="number" step="0.01" min="0.01" value="<?php echo esc_attr($editing_estimate_item['qty'] ?? '1'); ?>" required>
                                        </div>
                                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                                            <label for="estimate-item-cost-internal"><?php esc_html_e('Koszt wewnętrzny', 'erp-omd'); ?></label>
                                            <input id="estimate-item-cost-internal" name="cost_internal" type="number" step="0.01" min="0" value="<?php echo esc_attr($editing_estimate_item['cost_internal'] ?? '0'); ?>" required data-cost-input>
                                        </div>
                                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                                            <label for="estimate-item-margin-percent"><?php esc_html_e('Marża (%)', 'erp-omd'); ?></label>
                                            <input id="estimate-item-margin-percent" name="margin_percent" type="number" step="0.01" min="0" max="500" value="<?php echo esc_attr($editing_estimate_item['margin_percent'] ?? '0'); ?>" required data-margin-input>
                                        </div>
                                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                                            <label for="estimate-item-price"><?php esc_html_e('Cena', 'erp-omd'); ?></label>
                                            <input id="estimate-item-price" name="price" type="number" step="0.01" min="0" value="<?php echo esc_attr($editing_estimate_item['price'] ?? '0'); ?>" required data-price-input>
                                        </div>
                                        <div class="erp-omd-form-field erp-omd-form-field-compact erp-omd-form-field-inline-action">
                                            <label>&nbsp;</label>
                                            <button type="button" class="button button-secondary" data-admin-suggest-price><?php esc_html_e('Zasugeruj cenę', 'erp-omd'); ?></button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="price_source" value="<?php echo esc_attr((string) ($editing_estimate_item['price_source'] ?? 'manual')); ?>">
                                    <div class="erp-omd-form-field erp-omd-form-field-span-2">
                                        <label for="estimate-item-comment"><?php esc_html_e('Komentarz', 'erp-omd'); ?></label>
                                        <textarea id="estimate-item-comment" name="comment" rows="3" class="large-text"><?php echo esc_textarea($editing_estimate_item['comment'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <div class="erp-omd-form-actions">
                                    <?php submit_button(__('Zapisz pozycję', 'erp-omd'), 'secondary'); ?>
                                    <a class="button" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-estimates', 'id' => (int) $selected_estimate['id'], 'edit' => 1], admin_url('admin.php'))); ?>"><?php esc_html_e('Anuluj edycję pozycji', 'erp-omd'); ?></a>
                                </div>
                            </form>
                        <?php endif; ?>

                        <?php if ($estimate && ($selected_estimate['status'] ?? '') !== 'zaakceptowany') : ?>
                            <form method="post">
                                <?php wp_nonce_field('erp_omd_save_estimate_item'); ?>
                                <input type="hidden" name="erp_omd_action" value="save_estimate_item">
                                <input type="hidden" name="estimate_id" value="<?php echo esc_attr($selected_estimate['id']); ?>">
                                <input type="hidden" name="item_id" value="0">
                                <div class="erp-omd-form-grid">
                                    <div class="erp-omd-form-grid erp-omd-form-grid-estimate-item-row erp-omd-form-grid-estimate-item-row-with-suggest erp-omd-form-field erp-omd-form-field-span-2" data-admin-price-row>
                                        <div class="erp-omd-form-field">
                                            <label><?php esc_html_e('Nazwa pozycji', 'erp-omd'); ?></label>
                                            <input name="name" type="text" class="regular-text" required>
                                        </div>
                                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                                            <label><?php esc_html_e('Ilość', 'erp-omd'); ?></label>
                                            <input name="qty" type="number" step="0.01" min="0.01" value="1" required>
                                        </div>
                                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                                            <label><?php esc_html_e('Koszt wewnętrzny', 'erp-omd'); ?></label>
                                            <input name="cost_internal" type="number" step="0.01" min="0" value="0" required data-cost-input>
                                        </div>
                                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                                            <label><?php esc_html_e('Marża (%)', 'erp-omd'); ?></label>
                                            <input name="margin_percent" type="number" step="0.01" min="0" max="500" value="0" required data-margin-input>
                                        </div>
                                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                                            <label><?php esc_html_e('Cena', 'erp-omd'); ?></label>
                                            <input name="price" type="number" step="0.01" min="0" value="0" required data-price-input>
                                        </div>
                                        <div class="erp-omd-form-field erp-omd-form-field-compact erp-omd-form-field-inline-action">
                                            <label>&nbsp;</label>
                                            <button type="button" class="button button-secondary" data-admin-suggest-price><?php esc_html_e('Zasugeruj cenę', 'erp-omd'); ?></button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="price_source" value="manual">
                                    <div class="erp-omd-form-field erp-omd-form-field-span-2">
                                        <label><?php esc_html_e('Komentarz', 'erp-omd'); ?></label>
                                        <input name="comment" type="text" class="regular-text">
                                    </div>
                                </div>
                                <div class="erp-omd-form-actions">
                                    <?php submit_button(__('Dodaj pozycję', 'erp-omd'), 'secondary'); ?>
                                </div>
                            </form>
                        <?php endif; ?>

                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Nazwa', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Ilość', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Cena', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Koszt wewnętrzny', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Marża', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Źródło ceny', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Komentarz', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Akcje', 'erp-omd'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($estimate_items)) : ?>
                                    <tr>
                                        <td colspan="8"><?php esc_html_e('Brak pozycji kosztorysu.', 'erp-omd'); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($estimate_items as $item_row) : ?>
                                    <tr>
                                        <td><?php echo esc_html($item_row['name']); ?></td>
                                        <td><?php echo esc_html(number_format_i18n((float) $item_row['qty'], 2)); ?></td>
                                        <td><?php echo esc_html(number_format_i18n((float) $item_row['price'], 2)); ?></td>
                                        <td><?php echo esc_html(number_format_i18n((float) $item_row['cost_internal'], 2)); ?></td>
                                        <td><?php echo esc_html(number_format_i18n((float) ($item_row['margin_percent'] ?? 0), 2)); ?>%</td>
                                        <td><?php echo esc_html((string) ($item_row['price_source'] ?? 'manual')); ?></td>
                                        <td><?php echo esc_html($item_row['comment']); ?></td>
                                        <td>
                                            <?php if (($selected_estimate['status'] ?? '') !== 'zaakceptowany') : ?>
                                                <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-estimates', 'id' => (int) $selected_estimate['id'], 'edit' => 1, 'item_id' => (int) $item_row['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                                <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Usunąć pozycję kosztorysu?', 'erp-omd')); ?>');">
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
                    </section>

                    
                </div>
            <?php endif; ?>

            <div class="erp-omd-section-header">
                <h2><?php esc_html_e('Lista kosztorysów', 'erp-omd'); ?></h2>
                <form method="get" class="erp-omd-filter-form">
                    <input type="hidden" name="page" value="erp-omd-estimates" />
                    <input type="hidden" name="per_page" value="<?php echo esc_attr((string) ($estimate_filters['per_page'] ?? 100)); ?>">
                    <input type="hidden" name="page_num" value="1">
                    <input type="month" name="month" value="<?php echo esc_attr($estimate_filters['month'] ?? ''); ?>">
                    <button class="button" type="submit"><?php esc_html_e('Ustaw miesiąc', 'erp-omd'); ?></button>
                </form>
            </div>
            <form method="get" class="erp-omd-filter-form">
                <input type="hidden" name="page" value="erp-omd-estimates" />
                <input type="hidden" name="month" value="<?php echo esc_attr($estimate_filters['month'] ?? ''); ?>">
                <input type="hidden" name="per_page" value="<?php echo esc_attr((string) ($estimate_filters['per_page'] ?? 100)); ?>">
                <input type="hidden" name="page_num" value="1">
                <input type="search" name="search" class="regular-text" placeholder="<?php echo esc_attr__('Szukaj kosztorysu, klienta, projektu…', 'erp-omd'); ?>" value="<?php echo esc_attr($estimate_filters['search'] ?? ''); ?>">
                <select name="client_id"><option value="0"><?php esc_html_e('Wszyscy klienci', 'erp-omd'); ?></option><?php foreach ($clients as $client_row) : ?><option value="<?php echo esc_attr($client_row['id']); ?>" <?php selected((int) ($estimate_filters['client_id'] ?? 0), (int) $client_row['id']); ?>><?php echo esc_html($client_row['name']); ?></option><?php endforeach; ?></select>
                <select name="status"><option value=""><?php esc_html_e('Wszystkie statusy', 'erp-omd'); ?></option><?php foreach (['wstepny', 'do_akceptacji', 'odrzucony', 'zaakceptowany'] as $status_option) : ?><option value="<?php echo esc_attr($status_option); ?>" <?php selected($estimate_filters['status'] ?? '', $status_option); ?>><?php echo esc_html($estimate_status_labels[$status_option] ?? $status_option); ?></option><?php endforeach; ?></select>
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
                        <th><?php esc_html_e('Wysłano do klienta', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Akcje', 'erp-omd'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($estimates)) : ?><tr><td colspan="10"><?php esc_html_e('Brak kosztorysów dla wybranych filtrów. Zmień kryteria albo dodaj nowy kosztorys.', 'erp-omd'); ?></td></tr><?php endif; ?>
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
                            <td><span class="erp-omd-badge <?php echo esc_attr($this->status_badge_class($estimate_row['status'], 'estimate')); ?>"><?php echo esc_html($estimate_status_labels[(string) ($estimate_row['status'] ?? '')] ?? (string) ($estimate_row['status'] ?? '—')); ?></span></td>
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
                                <?php if (! empty($estimate_row['sent_to_client_at'])) : ?>
                                    <span class="erp-omd-badge erp-omd-badge-success"><?php echo esc_html($estimate_row['sent_to_client_at']); ?></span>
                                <?php else : ?>
                                    <span class="erp-omd-badge erp-omd-badge-muted"><?php esc_html_e('Nie', 'erp-omd'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <details class="erp-omd-list-actions">
                                    <summary class="button button-small"><?php esc_html_e('Akcje', 'erp-omd'); ?></summary>
                                    <div class="erp-omd-list-actions-menu">
                                        <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-estimates', 'id' => (int) $estimate_row['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Szczegóły', 'erp-omd'); ?></a>
                                        <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-estimates', 'id' => (int) $estimate_row['id'], 'edit' => 1], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                        <form method="post" class="erp-omd-inline-form">
                                            <?php wp_nonce_field('erp_omd_duplicate_estimate'); ?>
                                            <input type="hidden" name="erp_omd_action" value="duplicate_estimate">
                                            <input type="hidden" name="estimate_id" value="<?php echo esc_attr((string) ($estimate_row['id'] ?? 0)); ?>">
                                            <button type="submit" class="button button-small"><?php esc_html_e('Powiel', 'erp-omd'); ?></button>
                                        </form>
                                        <?php if (($estimate_row['status'] ?? '') === 'do_akceptacji') : ?>
                                            <form method="post" class="erp-omd-inline-form">
                                                <?php wp_nonce_field('erp_omd_send_estimate_client_link'); ?>
                                                <input type="hidden" name="erp_omd_action" value="send_estimate_client_link" />
                                                <input type="hidden" name="estimate_id" value="<?php echo esc_attr((string) ($estimate_row['id'] ?? 0)); ?>" />
                                                <button class="button button-small" type="submit"><?php esc_html_e('Wyślij do klienta', 'erp-omd'); ?></button>
                                            </form>
                                        <?php endif; ?>
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
            <?php if (($estimate_pagination['total_pages'] ?? 1) > 1) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        $estimate_base_args = [
                            'page' => 'erp-omd-estimates',
                            'search' => (string) ($estimate_filters['search'] ?? ''),
                            'status' => (string) ($estimate_filters['status'] ?? ''),
                            'client_id' => (int) ($estimate_filters['client_id'] ?? 0),
                            'month' => (string) ($estimate_filters['month'] ?? ''),
                            'per_page' => (int) ($estimate_filters['per_page'] ?? 100),
                        ];
                        $estimate_current_page = (int) ($estimate_pagination['page_num'] ?? 1);
                        $estimate_total_pages = (int) ($estimate_pagination['total_pages'] ?? 1);
                        ?>
                        <span class="displaying-num"><?php echo esc_html((string) ((int) ($estimate_pagination['total_items'] ?? 0))); ?></span>
                        <?php if ($estimate_current_page > 1) : ?>
                            <a class="button" href="<?php echo esc_url(add_query_arg(array_merge($estimate_base_args, ['page_num' => $estimate_current_page - 1]), admin_url('admin.php'))); ?>">&laquo;</a>
                        <?php endif; ?>
                        <span class="paging-input"><?php echo esc_html((string) $estimate_current_page . ' / ' . (string) $estimate_total_pages); ?></span>
                        <?php if ($estimate_current_page < $estimate_total_pages) : ?>
                            <a class="button" href="<?php echo esc_url(add_query_arg(array_merge($estimate_base_args, ['page_num' => $estimate_current_page + 1]), admin_url('admin.php'))); ?>">&raquo;</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
    </section>
</div>
