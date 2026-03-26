<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Ustawienia', 'erp-omd'); ?></h1>
    <div class="erp-omd-card">
        <h2><?php esc_html_e('Konfiguracja lifecycle, alertów, backupów i powiadomień', 'erp-omd'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('erp_omd_save_settings'); ?>
            <input type="hidden" name="erp_omd_action" value="save_settings" />
            <div class="erp-omd-form-sections">
                <section class="erp-omd-form-section">
                    <div class="erp-omd-form-section-header">
                        <h3><?php esc_html_e('Lifecycle i alerty', 'erp-omd'); ?></h3>
                        <p><?php esc_html_e('Podstawowe ustawienia operacyjne i bezpieczeństwa danych.', 'erp-omd'); ?></p>
                    </div>
                    <div class="erp-omd-form-grid">
                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                            <label for="erp-omd-alert-margin-threshold"><?php esc_html_e('Próg alertu niskiej marży (%)', 'erp-omd'); ?></label>
                            <input id="erp-omd-alert-margin-threshold" type="number" min="0" step="0.01" name="alert_margin_threshold" value="<?php echo esc_attr($margin_threshold); ?>" />
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                            <label for="erp-omd-fixed-monthly-cost-total"><?php esc_html_e('Suma aktywnych kosztów stałych (miesięcznie)', 'erp-omd'); ?></label>
                            <input id="erp-omd-fixed-monthly-cost-total" type="text" readonly value="<?php echo esc_attr(number_format((float) $fixed_monthly_cost, 2, '.', '')); ?>" />
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label class="erp-omd-form-label">
                                <input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked($delete_data); ?> />
                                <?php esc_html_e('Usuń dane ERP OMD podczas uninstall pluginu.', 'erp-omd'); ?>
                            </label>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label class="erp-omd-form-label">
                                <input type="checkbox" name="front_admin_redirect_enabled" value="1" <?php checked($front_admin_redirect_enabled); ?> />
                                <?php esc_html_e('Przekierowuj użytkowników FRONT z wp-admin do ERP Front.', 'erp-omd'); ?>
                            </label>
                        </div>
                    </div>
                </section>

                <section class="erp-omd-form-section">
                    <div class="erp-omd-form-section-header">
                        <h3><?php esc_html_e('Stałe koszty miesięczne', 'erp-omd'); ?></h3>
                        <p><?php esc_html_e('Jedna tabela kosztów stałych z możliwością dodawania i usuwania pozycji.', 'erp-omd'); ?></p>
                    </div>
                    <?php
                    $fixed_cost_rows = $fixed_monthly_cost_items;
                    if (empty($fixed_cost_rows)) {
                        $fixed_cost_rows[] = ['name' => '', 'amount' => 0, 'valid_from' => '', 'valid_to' => '', 'active' => 1];
                    }
                    ?>
                    <div class="erp-omd-form-field erp-omd-form-field-span-2">
                        <table class="widefat striped erp-omd-fixed-cost-table" data-disable-table-tools="1">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Nazwa', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Kwota', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Od', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Do', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Aktywne', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Akcje', 'erp-omd'); ?></th>
                                </tr>
                            </thead>
                            <tbody data-fixed-cost-body="1" data-next-index="<?php echo esc_attr((string) count($fixed_cost_rows)); ?>">
                                <?php foreach ($fixed_cost_rows as $index => $fixed_cost_row) : ?>
                                    <tr>
                                        <td><input type="text" name="fixed_cost_items[<?php echo esc_attr((string) $index); ?>][name]" value="<?php echo esc_attr((string) ($fixed_cost_row['name'] ?? '')); ?>" /></td>
                                        <td><input type="number" min="0" step="0.01" name="fixed_cost_items[<?php echo esc_attr((string) $index); ?>][amount]" value="<?php echo esc_attr(number_format((float) ($fixed_cost_row['amount'] ?? 0), 2, '.', '')); ?>" /></td>
                                        <td><input type="date" name="fixed_cost_items[<?php echo esc_attr((string) $index); ?>][valid_from]" value="<?php echo esc_attr((string) ($fixed_cost_row['valid_from'] ?? '')); ?>" /></td>
                                        <td><input type="date" name="fixed_cost_items[<?php echo esc_attr((string) $index); ?>][valid_to]" value="<?php echo esc_attr((string) ($fixed_cost_row['valid_to'] ?? '')); ?>" /></td>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="fixed_cost_items[<?php echo esc_attr((string) $index); ?>][active]" value="1" <?php checked(! empty($fixed_cost_row['active'])); ?> />
                                                <?php esc_html_e('Tak', 'erp-omd'); ?>
                                            </label>
                                        </td>
                                        <td>
                                            <button type="button" class="button button-secondary erp-omd-remove-fixed-cost-row"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p>
                            <button type="button" class="button button-secondary" id="erp-omd-add-fixed-cost-row"><?php esc_html_e('Dodaj pozycję', 'erp-omd'); ?></button>
                        </p>
                        <p class="description"><?php esc_html_e('Pozycje bez nazwy i kwoty są pomijane przy zapisie.', 'erp-omd'); ?></p>
                    </div>
                </section>

                <section class="erp-omd-form-section">
                    <div class="erp-omd-form-section-header">
                        <h3><?php esc_html_e('Automatyczny backup bazy (co tydzień)', 'erp-omd'); ?></h3>
                        <p><?php esc_html_e('System zapisuje backup bazy do pliku ZIP na serwerze (katalog uploads/erp-omd-backups).', 'erp-omd'); ?></p>
                    </div>
                    <p>
                        <strong><?php esc_html_e('Ostatni backup:', 'erp-omd'); ?></strong>
                        <?php echo $last_backup_at !== '' ? esc_html($last_backup_at) : esc_html__('brak', 'erp-omd'); ?>
                        <?php if ($last_backup_status !== '') : ?>
                            · <em><?php echo esc_html($last_backup_status); ?></em>
                        <?php endif; ?>
                    </p>
                    <?php if ($last_backup_file !== '') : ?>
                        <p><code><?php echo esc_html($last_backup_file); ?></code></p>
                    <?php endif; ?>
                </section>

                <section class="erp-omd-form-section">
                    <div class="erp-omd-form-section-header">
                        <h3><?php esc_html_e('Powiadomienia o brakujących godzinach', 'erp-omd'); ?></h3>
                        <p><?php esc_html_e('Skonfiguruj scenariusz automatycznej wysyłki oraz listę pracowników, którzy mają otrzymywać przypomnienia.', 'erp-omd'); ?></p>
                    </div>
                    <div class="erp-omd-form-grid">
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label for="erp-omd-missing-hours-mode"><?php esc_html_e('Aktywny scenariusz', 'erp-omd'); ?></label>
                            <select id="erp-omd-missing-hours-mode" name="missing_hours_mode">
                                <option value="after_x_days" <?php selected($notification_settings['mode'], 'after_x_days'); ?>><?php esc_html_e('Wysłanie po X dniach', 'erp-omd'); ?></option>
                                <option value="day_of_month" <?php selected($notification_settings['mode'], 'day_of_month'); ?>><?php esc_html_e('Wysłanie w dniu X', 'erp-omd'); ?></option>
                            </select>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                            <label for="erp-omd-missing-hours-after-days"><?php esc_html_e('Ile dni od ostatniego raportu', 'erp-omd'); ?></label>
                            <input id="erp-omd-missing-hours-after-days" type="number" min="1" max="60" step="1" name="missing_hours_after_days" value="<?php echo esc_attr((string) $notification_settings['after_days']); ?>" />
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                            <label for="erp-omd-missing-hours-day-of-month"><?php esc_html_e('Dzień miesiąca (1-31)', 'erp-omd'); ?></label>
                            <input id="erp-omd-missing-hours-day-of-month" type="number" min="1" max="31" step="1" name="missing_hours_day_of_month" value="<?php echo esc_attr((string) $notification_settings['day_of_month']); ?>" />
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label for="erp-omd-missing-hours-mail-subject"><?php esc_html_e('Temat maila', 'erp-omd'); ?></label>
                            <input id="erp-omd-missing-hours-mail-subject" type="text" name="missing_hours_mail_subject" value="<?php echo esc_attr((string) $notification_settings['subject']); ?>" />
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label for="erp-omd-notification-sender-email"><?php esc_html_e('Nadawca e-mail powiadomień (wp_mail)', 'erp-omd'); ?></label>
                            <input id="erp-omd-notification-sender-email" type="email" name="notification_sender_email" value="<?php echo esc_attr((string) $notification_sender_email); ?>" placeholder="<?php echo esc_attr(get_option('admin_email', '')); ?>" />
                            <p class="description"><?php esc_html_e('Pozostaw puste, aby używać domyślnego adresu WordPress.', 'erp-omd'); ?></p>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label for="erp-omd-missing-hours-mail-body"><?php esc_html_e('Treść maila', 'erp-omd'); ?></label>
                            <?php wp_editor((string) ($notification_settings['body'] ?? ''), 'erp-omd-missing-hours-mail-body-editor', [
                                'textarea_name' => 'missing_hours_mail_body',
                                'textarea_rows' => 8,
                                'media_buttons' => false,
                                'teeny' => true,
                            ]); ?>
                            <p class="description"><?php esc_html_e('Dostępne tokeny: {login}, {employee_id}, {last_reported_date}, {days_since_last_report}.', 'erp-omd'); ?></p>
                        </div>
                    </div>

                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('ID', 'erp-omd'); ?></th>
                                <th><?php esc_html_e('Login', 'erp-omd'); ?></th>
                                <th><?php esc_html_e('Ostatnie powiadomienie', 'erp-omd'); ?></th>
                                <th><?php esc_html_e('Aktywne / Nieaktywne', 'erp-omd'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($employees)) : ?>
                                <tr>
                                    <td colspan="4"><?php esc_html_e('Brak pracowników.', 'erp-omd'); ?></td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($employees as $employee) : ?>
                                    <tr>
                                        <td><?php echo esc_html((string) ($employee['id'] ?? 0)); ?></td>
                                        <td><?php echo esc_html((string) ($employee['user_login'] ?? '—')); ?></td>
                                        <td><?php echo ! empty($employee['last_notification_at']) ? esc_html((string) $employee['last_notification_at']) : '—'; ?></td>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="missing_hours_recipients_active[]" value="<?php echo esc_attr((string) ($employee['id'] ?? 0)); ?>" <?php checked(! empty($employee['notification_active'])); ?> />
                                                <?php esc_html_e('Aktywne', 'erp-omd'); ?>
                                            </label>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </section>

                <section class="erp-omd-form-section">
                    <div class="erp-omd-form-section-header">
                        <h3><?php esc_html_e('Ekran logowania FRONT', 'erp-omd'); ?></h3>
                        <p><?php esc_html_e('Wybierz logo dla lewej kolumny oraz grafikę hero wyświetlaną po prawej stronie ekranu logowania.', 'erp-omd'); ?></p>
                    </div>
                    <div class="erp-omd-settings-media-grid">
                        <div class="erp-omd-settings-media-card">
                            <div class="erp-omd-media-preview">
                                <img src="<?php echo esc_url($front_login_logo_url); ?>" alt="<?php esc_attr_e('Podgląd logo logowania FRONT', 'erp-omd'); ?>" <?php echo $front_login_logo_url === '' ? 'hidden' : ''; ?>>
                            </div>
                            <div class="erp-omd-attachment-form">
                                <input type="hidden" name="front_login_logo_id" value="<?php echo esc_attr($front_login_logo_id); ?>" class="erp-omd-media-id">
                                <button type="button" class="button erp-omd-media-button" data-media-title="<?php esc_attr_e('Wybierz logo logowania', 'erp-omd'); ?>" data-media-button="<?php esc_attr_e('Użyj jako logo', 'erp-omd'); ?>"><?php esc_html_e('Wybierz logo', 'erp-omd'); ?></button>
                                <span class="erp-omd-media-name"><?php echo esc_html($front_login_logo_name); ?></span>
                            </div>
                        </div>

                        <div class="erp-omd-settings-media-card">
                            <div class="erp-omd-media-preview">
                                <img src="<?php echo esc_url($front_login_cover_url); ?>" alt="<?php esc_attr_e('Podgląd grafiki logowania FRONT', 'erp-omd'); ?>" <?php echo $front_login_cover_url === '' ? 'hidden' : ''; ?>>
                            </div>
                            <div class="erp-omd-attachment-form">
                                <input type="hidden" name="front_login_cover_id" value="<?php echo esc_attr($front_login_cover_id); ?>" class="erp-omd-media-id">
                                <button type="button" class="button erp-omd-media-button" data-media-title="<?php esc_attr_e('Wybierz grafikę sekcji prawej', 'erp-omd'); ?>" data-media-button="<?php esc_attr_e('Użyj jako grafikę', 'erp-omd'); ?>"><?php esc_html_e('Wybierz grafikę', 'erp-omd'); ?></button>
                                <span class="erp-omd-media-name"><?php echo esc_html($front_login_cover_name); ?></span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <?php submit_button(__('Zapisz ustawienia', 'erp-omd')); ?>
        </form>
    </div>
</div>
