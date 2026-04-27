<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Ustawienia', 'erp-omd'); ?></h1>
    <nav class="nav-tab-wrapper erp-omd-nav-tabs">
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-settings', 'tab' => 'fixed_costs'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $settings_tab === 'fixed_costs' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Koszty miesięczne', 'erp-omd'); ?></a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-settings', 'tab' => 'backup_restore'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $settings_tab === 'backup_restore' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Backup/odtwarzanie', 'erp-omd'); ?></a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-settings', 'tab' => 'missing_hours'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $settings_tab === 'missing_hours' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Mail o braku godzin', 'erp-omd'); ?></a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-settings', 'tab' => 'estimate_mail_templates'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $settings_tab === 'estimate_mail_templates' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Maile kosztorysowe', 'erp-omd'); ?></a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-settings', 'tab' => 'front_login'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $settings_tab === 'front_login' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Ekran logowania', 'erp-omd'); ?></a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-settings', 'tab' => 'lifecycle_alerts'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $settings_tab === 'lifecycle_alerts' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Lifecycle i alerty', 'erp-omd'); ?></a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-settings', 'tab' => 'google_calendar'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $settings_tab === 'google_calendar' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Google Calendar', 'erp-omd'); ?></a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-settings', 'tab' => 'ksef'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $settings_tab === 'ksef' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('KSeF', 'erp-omd'); ?></a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-settings', 'tab' => 'reports_v1_monitoring'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $settings_tab === 'reports_v1_monitoring' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Reports SLO i monitoring', 'erp-omd'); ?></a>
    </nav>
    <div class="erp-omd-card">
        <h2><?php esc_html_e('Konfiguracja ustawień ERP OMD', 'erp-omd'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('erp_omd_save_settings'); ?>
            <input type="hidden" name="erp_omd_action" value="save_settings" />
            <input type="hidden" name="settings_tab" value="<?php echo esc_attr($settings_tab); ?>" />
            <div class="erp-omd-form-sections">
                <?php if ($settings_tab === 'lifecycle_alerts') : ?>
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
                            <label for="erp-omd-company-nip"><?php esc_html_e('NIP naszej firmy (KSeF)', 'erp-omd'); ?></label>
                            <input id="erp-omd-company-nip" type="text" name="company_nip" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" value="<?php echo esc_attr((string) $company_nip); ?>" />
                            <p class="description"><?php esc_html_e('Używany do klasyfikacji KSeF: Nabywca = kosztowa, Sprzedawca = sprzedażowa.', 'erp-omd'); ?></p>
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
                <?php elseif ($settings_tab === 'fixed_costs') : ?>
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
                    <div class="erp-omd-form-grid">
                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                            <label for="erp-omd-fixed-monthly-cost-total"><?php esc_html_e('Suma aktywnych kosztów stałych (miesięcznie)', 'erp-omd'); ?></label>
                            <input id="erp-omd-fixed-monthly-cost-total" type="text" readonly value="<?php echo esc_attr(number_format((float) $fixed_monthly_cost, 2, '.', '')); ?>" />
                        </div>
                    </div>
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
                        <p>
                            <button type="submit" class="button button-primary"><?php esc_html_e('Zapisz koszty', 'erp-omd'); ?></button>
                        </p>
                    </div>
                </section>
                <?php elseif ($settings_tab === 'backup_restore') : ?>
                <section class="erp-omd-form-section">
                    <div class="erp-omd-form-section-header">
                        <h3><?php esc_html_e('Backup/odtwarzanie', 'erp-omd'); ?></h3>
                        <p><?php esc_html_e('Operacje backupu i odtwarzania danych ERP OMD (SQL + ustawienia).', 'erp-omd'); ?></p>
                    </div>
                    <div style="display:flex; gap:16px; align-items:flex-start; flex-wrap:wrap;">
                        <div style="flex:1 1 320px; min-width:280px;">
                            <h4 style="margin-top:0;"><?php esc_html_e('Automatyczny backup bazy (co tydzień)', 'erp-omd'); ?></h4>
                            <p><?php esc_html_e('System zapisuje backup bazy SQL i ustawień wtyczki do pliku ZIP na serwerze (katalog uploads/erp-omd-backups).', 'erp-omd'); ?></p>
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
                            <button type="submit" class="button button-secondary" form="erp-omd-manual-backup-form"><?php esc_html_e('Uruchom backup teraz', 'erp-omd'); ?></button>
                            <?php
                            $last_backup_download_url = '';
                            if ($last_backup_file !== '') {
                                $uploads = wp_upload_dir();
                                $base_dir = (string) ($uploads['basedir'] ?? '');
                                $base_url = (string) ($uploads['baseurl'] ?? '');
                                if ($base_dir !== '' && $base_url !== '' && strpos((string) $last_backup_file, $base_dir) === 0) {
                                    $relative_path = ltrim(substr((string) $last_backup_file, strlen($base_dir)), '/\\');
                                    $last_backup_download_url = trailingslashit($base_url) . str_replace('\\', '/', $relative_path);
                                }
                            }
                            ?>
                            <?php if ($last_backup_download_url !== '') : ?>
                                <a class="button" href="<?php echo esc_url($last_backup_download_url); ?>" download><?php esc_html_e('Pobierz backup', 'erp-omd'); ?></a>
                            <?php endif; ?>
                        </div>
                        <div style="flex:1 1 320px; min-width:280px;">
                            <h4 style="margin-top:0;"><?php esc_html_e('Odtworzenie z backupu', 'erp-omd'); ?></h4>
                            <p><?php esc_html_e('Wgraj paczkę ZIP pobraną z backupu ERP OMD, aby odtworzyć SQL + ustawienia pluginu.', 'erp-omd'); ?></p>
                            <p>
                                <label for="erp-omd-restore-backup-zip"><?php esc_html_e('Plik backupu ZIP', 'erp-omd'); ?></label><br />
                                <input id="erp-omd-restore-backup-zip" type="file" name="restore_backup_zip" form="erp-omd-restore-backup-form" accept=".zip,application/zip" required />
                            </p>
                            <p class="description"><?php esc_html_e('Uwaga: operacja nadpisze bieżące dane ERP OMD (tabele erp_omd_*) i ustawienia opcji erp_omd_*.', 'erp-omd'); ?></p>
                            <button type="submit" class="button button-primary" form="erp-omd-restore-backup-form"><?php esc_html_e('Odtwórz z backupu', 'erp-omd'); ?></button>
                        </div>
                    </div>
                </section>
                <?php elseif ($settings_tab === 'missing_hours') : ?>
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
                </section>
                <section class="erp-omd-form-section">
                    <div class="erp-omd-form-section-header">
                        <h3><?php esc_html_e('Odbiorcy powiadomień o brakujących godzinach', 'erp-omd'); ?></h3>
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
                <?php elseif ($settings_tab === 'estimate_mail_templates') : ?>
                <section class="erp-omd-form-section">
                    <div class="erp-omd-form-section-header">
                        <h3><?php esc_html_e('Szablony maili kosztorysów', 'erp-omd'); ?></h3>
                        <p><?php esc_html_e('Edytuj wiadomości wysyłane do klienta przy przekazaniu kosztorysu do akceptacji oraz po jego akceptacji.', 'erp-omd'); ?></p>
                    </div>
                    <div class="erp-omd-form-grid">
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label for="erp-omd-estimate-client-mail-subject"><?php esc_html_e('E-mail z kosztorysem do klienta (link akceptacji) — temat maila do klienta', 'erp-omd'); ?></label>
                            <input id="erp-omd-estimate-client-mail-subject" type="text" name="estimate_client_mail_subject" value="<?php echo esc_attr((string) ($estimate_mail_settings['subject'] ?? '')); ?>" />
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label for="erp-omd-estimate-client-mail-body"><?php esc_html_e('E-mail z kosztorysem do klienta (link akceptacji) — treść maila do klienta', 'erp-omd'); ?></label>
                            <?php wp_editor((string) ($estimate_mail_settings['body'] ?? ''), 'erp-omd-estimate-client-mail-body-editor', [
                                'textarea_name' => 'estimate_client_mail_body',
                                'textarea_rows' => 8,
                                'media_buttons' => false,
                                'teeny' => false,
                            ]); ?>
                            <p class="description"><?php esc_html_e('Dostępne tokeny: {estimate_name}, {client_name}, {decision_url}, {expires_at}.', 'erp-omd'); ?></p>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label for="erp-omd-estimate-thank-you-mail-subject"><?php esc_html_e('E-mail podziękowania po akceptacji kosztorysu — temat podziękowania', 'erp-omd'); ?></label>
                            <input id="erp-omd-estimate-thank-you-mail-subject" type="text" name="estimate_client_thank_you_mail_subject" value="<?php echo esc_attr((string) ($estimate_thank_you_mail_settings['subject'] ?? '')); ?>" />
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label for="erp-omd-estimate-thank-you-mail-body"><?php esc_html_e('E-mail podziękowania po akceptacji kosztorysu — treść podziękowania', 'erp-omd'); ?></label>
                            <?php wp_editor((string) ($estimate_thank_you_mail_settings['body'] ?? ''), 'erp-omd-estimate-thank-you-mail-body-editor', [
                                'textarea_name' => 'estimate_client_thank_you_mail_body',
                                'textarea_rows' => 8,
                                'media_buttons' => false,
                                'teeny' => false,
                            ]); ?>
                            <p class="description"><?php esc_html_e('Dostępne tokeny: {estimate_name}, {client_name}, {final_gross}, {final_net}, {client_note}. Tabela pozycji jest dodawana automatycznie pod treścią.', 'erp-omd'); ?></p>
                        </div>
                    </div>
                </section>
                <?php elseif ($settings_tab === 'front_login') : ?>
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
                <?php elseif ($settings_tab === 'ksef') : ?>
                <section class="erp-omd-form-section">
                    <div class="erp-omd-form-section-header">
                        <h3><?php esc_html_e('KSeF Sync Hub', 'erp-omd'); ?></h3>
                        <p><?php esc_html_e('Konfiguracja środowiska i diagnostyka pipeline API KSeF (auth/export/sync/import).', 'erp-omd'); ?></p>
                    </div>
                    <div class="erp-omd-form-grid">
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label class="erp-omd-form-label">
                                <input type="checkbox" name="ksef_sync_hub_enabled" value="1" <?php checked(! empty($ksef_sync_hub_enabled)); ?> />
                                <?php esc_html_e('Włącz KSeF Sync Hub (cron incremental sync)', 'erp-omd'); ?>
                            </label>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                            <label for="erp-omd-ksef-sync-hub-env"><?php esc_html_e('Środowisko', 'erp-omd'); ?></label>
                            <select id="erp-omd-ksef-sync-hub-env" name="ksef_sync_hub_env">
                                <option value="TEST" <?php selected((string) $ksef_sync_hub_env, 'TEST'); ?>>TEST</option>
                                <option value="DEMO" <?php selected((string) $ksef_sync_hub_env, 'DEMO'); ?>>DEMO</option>
                                <option value="PRD" <?php selected((string) $ksef_sync_hub_env, 'PRD'); ?>>PRD</option>
                            </select>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                            <label for="erp-omd-ksef-sync-hub-mode"><?php esc_html_e('Tryb pipeline', 'erp-omd'); ?></label>
                            <select id="erp-omd-ksef-sync-hub-mode" name="ksef_sync_hub_mode">
                                <option value="dry_run" <?php selected((string) $ksef_sync_hub_mode, 'dry_run'); ?>>dry_run</option>
                                <option value="active" <?php selected((string) $ksef_sync_hub_mode, 'active'); ?>>active</option>
                            </select>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                            <label for="erp-omd-ksef-sync-backfill-hours"><?php esc_html_e('Backfill (h)', 'erp-omd'); ?></label>
                            <input id="erp-omd-ksef-sync-backfill-hours" type="number" min="1" max="168" step="1" name="ksef_sync_backfill_hours" value="<?php echo esc_attr((string) $ksef_sync_backfill_hours); ?>" />
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label for="erp-omd-ksef-api-base-url"><?php esc_html_e('Base URL API KSeF', 'erp-omd'); ?></label>
                            <input id="erp-omd-ksef-api-base-url" type="text" name="ksef_api_base_url" value="<?php echo esc_attr((string) $ksef_api_base_url); ?>" placeholder="https://ksef.mf.gov.pl" />
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label for="erp-omd-ksef-sync-subject-types"><?php esc_html_e('SubjectType (lista)', 'erp-omd'); ?></label>
                            <input id="erp-omd-ksef-sync-subject-types" type="text" name="ksef_sync_subject_types" value="<?php echo esc_attr(implode(', ', (array) $ksef_sync_subject_types)); ?>" />
                            <p class="description"><?php esc_html_e('Wpisz listę po przecinku, np. seller,buyer.', 'erp-omd'); ?></p>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label for="erp-omd-ksef-sync-hub-context"><?php esc_html_e('ContextIdentifier (KSeF auth)', 'erp-omd'); ?></label>
                            <input id="erp-omd-ksef-sync-hub-context" type="text" name="ksef_sync_hub_context_identifier" value="<?php echo esc_attr((string) $ksef_sync_hub_context_identifier); ?>" />
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label for="erp-omd-ksef-sync-hub-ap-token"><?php esc_html_e('Token KSeF z AP (sekret)', 'erp-omd'); ?></label>
                            <input id="erp-omd-ksef-sync-hub-ap-token" type="password" name="ksef_sync_hub_ap_token" value="" autocomplete="new-password" />
                            <?php if ($ksef_sync_hub_ap_token_masked !== '') : ?>
                                <p class="description"><?php echo esc_html(sprintf(__('Obecnie zapisany token AP: %s', 'erp-omd'), (string) $ksef_sync_hub_ap_token_masked)); ?></p>
                            <?php endif; ?>
                            <label class="erp-omd-form-label">
                                <input type="checkbox" name="ksef_sync_hub_ap_token_clear" value="1" />
                                <?php esc_html_e('Wyczyść zapisany token AP', 'erp-omd'); ?>
                            </label>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label for="erp-omd-ksef-sync-hub-public-key"><?php esc_html_e('Klucz publiczny MF (PEM) dla wybranego środowiska', 'erp-omd'); ?></label>
                            <textarea id="erp-omd-ksef-sync-hub-public-key" name="ksef_sync_hub_public_key_pem" rows="6" class="large-text"><?php echo esc_textarea((string) $ksef_sync_hub_public_key_pem); ?></textarea>
                            <p class="description"><?php esc_html_e('Wklej klucz publiczny KSeF (MF) odpowiadający aktualnemu środowisku TEST/DEMO/PRD.', 'erp-omd'); ?></p>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label class="erp-omd-form-label">
                                <input type="checkbox" name="ksef_auto_create_supplier" value="1" <?php checked(! empty($ksef_auto_create_supplier)); ?> />
                                <?php esc_html_e('Auto-dodawanie dostawcy po NIP, jeśli brak dopasowania (KSeF cost)', 'erp-omd'); ?>
                            </label>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <p><strong><?php esc_html_e('Status auth tokenów:', 'erp-omd'); ?></strong>
                                <?php echo ! empty($ksef_auth_tokens['access_token']) ? esc_html__('token dostępny', 'erp-omd') : esc_html__('brak tokenu', 'erp-omd'); ?>
                                <?php if (! empty($ksef_auth_tokens['access_expires_at'])) : ?>
                                    (<?php echo esc_html(sprintf(__('wygasa: %s', 'erp-omd'), (string) $ksef_auth_tokens['access_expires_at'])); ?>)
                                <?php endif; ?>
                            </p>
                            <p><strong><?php esc_html_e('Ostatni stan sync:', 'erp-omd'); ?></strong>
                                <?php echo esc_html((string) ($ksef_sync_state['status'] ?? '—')); ?>
                                <?php if (! empty($ksef_sync_state['last_run_at'])) : ?>
                                    (<?php echo esc_html((string) $ksef_sync_state['last_run_at']); ?>)
                                <?php endif; ?>
                            </p>
                            <p><strong><?php esc_html_e('Checkpoint HWM (pierwszy SubjectType):', 'erp-omd'); ?></strong>
                                <?php echo $ksef_sync_hwm_preview !== '' ? esc_html($ksef_sync_hwm_preview) : '—'; ?>
                            </p>
                            <p><strong><?php esc_html_e('Ostatni dry-run:', 'erp-omd'); ?></strong>
                                <?php if (! empty($ksef_sync_hub_last_dry_run['run_at'])) : ?>
                                    <?php echo esc_html(sprintf('%s | %s | %s', (string) $ksef_sync_hub_last_dry_run['run_at'], (string) ($ksef_sync_hub_last_dry_run['status'] ?? ''), (string) ($ksef_sync_hub_last_dry_run['error_code'] ?? ''))); ?>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </p>
                            <p>
                                <button type="submit" class="button" form="erp-omd-ksef-sync-hub-dry-run-form"><?php esc_html_e('Dry-run connector check', 'erp-omd'); ?></button>
                            </p>
                        </div>
                    </div>
                </section>
                <?php elseif ($settings_tab === 'google_calendar') : ?>
                <section class="erp-omd-form-section">
                    <div class="erp-omd-form-section-header">
                        <h3><?php esc_html_e('Google Calendar', 'erp-omd'); ?></h3>
                        <p><?php esc_html_e('Konfiguracja OAuth i synchronizacji eventów projektów (globalny kalendarz).', 'erp-omd'); ?></p>
                    </div>
                    <div class="erp-omd-form-grid">
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label for="erp-omd-google-calendar-client-id"><?php esc_html_e('Client ID', 'erp-omd'); ?></label>
                            <input id="erp-omd-google-calendar-client-id" type="text" name="google_calendar_client_id" value="<?php echo esc_attr((string) $google_calendar_client_id); ?>" autocomplete="off" />
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label for="erp-omd-google-calendar-client-secret"><?php esc_html_e('Client Secret', 'erp-omd'); ?></label>
                            <input id="erp-omd-google-calendar-client-secret" type="password" name="google_calendar_client_secret" value="" autocomplete="new-password" />
                            <?php if ($google_calendar_client_secret_masked !== '') : ?>
                                <p class="description"><?php echo esc_html(sprintf(__('Obecny secret: %s (pozostaw puste, aby nie zmieniać).', 'erp-omd'), $google_calendar_client_secret_masked)); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label for="erp-omd-google-calendar-redirect-uri"><?php esc_html_e('Redirect URI', 'erp-omd'); ?></label>
                            <input id="erp-omd-google-calendar-redirect-uri" type="text" name="google_calendar_redirect_uri" value="<?php echo esc_attr((string) $google_calendar_redirect_uri); ?>" />
                            <p class="description"><?php esc_html_e('Ten adres musi być wpisany 1:1 w Google Cloud Console (Authorized redirect URIs). Uwaga: protokół (http/https), domena i parametry query muszą być identyczne.', 'erp-omd'); ?></p>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                            <label for="erp-omd-google-calendar-scope"><?php esc_html_e('Scope', 'erp-omd'); ?></label>
                            <select id="erp-omd-google-calendar-scope" name="google_calendar_scope">
                                <option value="https://www.googleapis.com/auth/calendar" <?php selected((string) $google_calendar_scope, 'https://www.googleapis.com/auth/calendar'); ?>>calendar</option>
                                <option value="https://www.googleapis.com/auth/calendar.events" <?php selected((string) $google_calendar_scope, 'https://www.googleapis.com/auth/calendar.events'); ?>>calendar.events</option>
                            </select>
                            <p class="description"><?php esc_html_e('Aby pobierać listę kalendarzy, użyj scope „calendar”. Po zmianie scope wymagane jest ponowne połączenie OAuth.', 'erp-omd'); ?></p>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                            <label for="erp-omd-google-calendar-id"><?php esc_html_e('Calendar ID (globalny)', 'erp-omd'); ?></label>
                            <?php if (! empty($google_calendar_available_calendars)) : ?>
                                <select id="erp-omd-google-calendar-id" name="google_calendar_calendar_id">
                                    <?php foreach ((array) $google_calendar_available_calendars as $google_calendar_item) : ?>
                                        <?php
                                        $calendar_option_id = (string) ($google_calendar_item['id'] ?? '');
                                        $calendar_option_summary = (string) ($google_calendar_item['summary'] ?? $calendar_option_id);
                                        if ($calendar_option_id === '') {
                                            continue;
                                        }
                                        ?>
                                        <option value="<?php echo esc_attr($calendar_option_id); ?>" <?php selected((string) $google_calendar_calendar_id, $calendar_option_id); ?>>
                                            <?php echo esc_html($calendar_option_summary . (! empty($google_calendar_item['primary']) ? ' (primary)' : '')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e('Nie widzisz kalendarza? Użyj przycisku „Pobierz kalendarze Google” poniżej.', 'erp-omd'); ?></p>
                            <?php else : ?>
                                <input id="erp-omd-google-calendar-id" type="text" name="google_calendar_calendar_id" value="<?php echo esc_attr((string) $google_calendar_calendar_id); ?>" />
                            <?php endif; ?>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label for="erp-omd-google-calendar-technical-email"><?php esc_html_e('Konto techniczne (email)', 'erp-omd'); ?></label>
                            <input id="erp-omd-google-calendar-technical-email" type="email" name="google_calendar_technical_account_email" value="<?php echo esc_attr((string) $google_calendar_technical_account_email); ?>" />
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <p>
                                <strong><?php esc_html_e('Status połączenia:', 'erp-omd'); ?></strong>
                                <?php echo $google_calendar_connected ? esc_html__('Połączono', 'erp-omd') : esc_html__('Wymaga autoryzacji', 'erp-omd'); ?>
                            </p>
                            <p>
                                <strong><?php esc_html_e('Ostatnia udana synchronizacja:', 'erp-omd'); ?></strong>
                                <?php echo $google_calendar_last_sync_at !== '' ? esc_html($google_calendar_last_sync_at) : '—'; ?>
                            </p>
                            <p>
                                <strong><?php esc_html_e('Ostatni błąd:', 'erp-omd'); ?></strong>
                                <?php echo $google_calendar_last_error !== '' ? esc_html($google_calendar_last_error) : '—'; ?>
                            </p>
                            <p>
                                <button type="submit" class="button button-secondary" form="erp-omd-google-calendar-connect-form"><?php esc_html_e('Połącz z Google', 'erp-omd'); ?></button>
                                <button type="submit" class="button button-link-delete" form="erp-omd-google-calendar-disconnect-form"><?php esc_html_e('Odłącz', 'erp-omd'); ?></button>
                                <button type="submit" class="button" form="erp-omd-google-calendar-sync-now-form"><?php esc_html_e('Synchronizuj teraz', 'erp-omd'); ?></button>
                                <button type="submit" class="button" form="erp-omd-google-calendar-fetch-calendars-form"><?php esc_html_e('Pobierz kalendarze Google', 'erp-omd'); ?></button>
                            </p>
                        </div>
                    </div>
                </section>
                <?php elseif ($settings_tab === 'reports_v1_monitoring') : ?>
                <section class="erp-omd-form-section">
                    <div class="erp-omd-form-section-header">
                        <h3 id="reports-v1-slo-monitoring"><?php esc_html_e('Reports SLO i monitoring', 'erp-omd'); ?></h3>
                        <p><?php esc_html_e('Ustawienia i status kalibracji SLO wydzielone do osobnego boxu na końcu ekranu.', 'erp-omd'); ?></p>
                    </div>
                    <div class="erp-omd-form-grid erp-omd-form-grid-reports-slo">
                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                            <label for="erp-omd-reports-v1-freshness-minutes"><?php esc_html_e('Maks. wiek metryk Reports v1 (min)', 'erp-omd'); ?></label>
                            <input id="erp-omd-reports-v1-freshness-minutes" type="number" min="5" step="1" name="reports_v1_metrics_freshness_minutes" value="<?php echo esc_attr((string) $reports_v1_metrics_freshness_minutes); ?>" />
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                            <label for="erp-omd-reports-v1-slo-p95-max"><?php esc_html_e('SLO: maks. p95 czasu raportu (ms)', 'erp-omd'); ?></label>
                            <input id="erp-omd-reports-v1-slo-p95-max" type="number" min="100" max="30000" step="50" name="reports_v1_slo_generation_p95_max" value="<?php echo esc_attr((string) $reports_v1_slo_generation_p95_max); ?>" />
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-compact">
                            <label for="erp-omd-reports-v1-slo-p95-recommended"><?php esc_html_e('Rekomendowany próg p95 (ms)', 'erp-omd'); ?></label>
                            <input id="erp-omd-reports-v1-slo-p95-recommended" type="number" readonly value="<?php echo esc_attr((string) $reports_v1_slo_recommended_p95_max); ?>" />
                            <p class="description"><?php echo esc_html(sprintf(__('Próbki do rekomendacji: %d', 'erp-omd'), (int) $reports_v1_slo_calibration_sample_count)); ?></p>
                        </div>
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label class="erp-omd-form-label">
                                <input type="checkbox" name="apply_reports_v1_recommended_p95_max" value="1" />
                                <?php esc_html_e('Przy zapisie zastosuj rekomendowany próg p95 na podstawie logu metryk.', 'erp-omd'); ?>
                            </label>
                            <label class="erp-omd-form-label" style="margin-top:8px;">
                                <input type="checkbox" name="confirm_reports_v1_slo_calibration_decision" value="1" />
                                <?php esc_html_e('Potwierdź finalną decyzję progu p95 (zapisz wpis audytowy decyzji).', 'erp-omd'); ?>
                            </label>
                            <label class="erp-omd-form-label" style="margin-top:8px;">
                                <input type="checkbox" name="confirm_reports_v1_slo_calibration_closure" value="1" />
                                <?php esc_html_e('Formalnie zamknij kalibrację SLO (po potwierdzonej decyzji) i przejdź do monitoringu steady-state.', 'erp-omd'); ?>
                            </label>
                            <p class="description">
                                <?php
                                echo esc_html(
                                    sprintf(
                                        __('Status kalibracji SLO: %1$s | brakujące próbki: %2$d | akcja: %3$s', 'erp-omd'),
                                        ! empty($reports_v1_slo_calibration_decision_ready) ? __('ready', 'erp-omd') : __('pending', 'erp-omd'),
                                        (int) $reports_v1_slo_samples_missing_to_calibration,
                                        (string) $reports_v1_slo_calibration_next_action
                                    )
                                );
                                ?>
                            </p>
                            <?php if (! empty($reports_v1_slo_last_decision)) : ?>
                                <p class="description">
                                    <?php
                                    echo esc_html(
                                        sprintf(
                                            __('Ostatnia decyzja SLO: próg=%1$d ms | rekomendacja=%2$d ms | próbki=%3$d | data=%4$s | user_id=%5$d', 'erp-omd'),
                                            (int) ($reports_v1_slo_last_decision['threshold_ms'] ?? 0),
                                            (int) ($reports_v1_slo_last_decision['recommended_threshold_ms'] ?? 0),
                                            (int) ($reports_v1_slo_last_decision['sample_count'] ?? 0),
                                            (string) ($reports_v1_slo_last_decision['decided_at'] ?? ''),
                                            (int) ($reports_v1_slo_last_decision['decided_by_user_id'] ?? 0)
                                        )
                                    );
                                    ?>
                                </p>
                            <?php endif; ?>
                            <?php if (! empty($reports_v1_slo_closure_confirmed)) : ?>
                                <p class="description">
                                    <?php
                                    echo esc_html(
                                        sprintf(
                                            __('Kalibracja formalnie zamknięta: data=%1$s | user_id=%2$d | decyzja=%3$s | próg=%4$d ms', 'erp-omd'),
                                            (string) ($reports_v1_slo_closure['closed_at'] ?? ''),
                                            (int) ($reports_v1_slo_closure['closed_by_user_id'] ?? 0),
                                            (string) ($reports_v1_slo_closure['decision_decided_at'] ?? ''),
                                            (int) ($reports_v1_slo_closure['decision_threshold_ms'] ?? 0)
                                        )
                                    );
                                    ?>
                                </p>
                            <?php endif; ?>
                            <p class="description"><?php esc_html_e('Reports v1 jest aktywny dla wszystkich użytkowników ERP OMD. Legacy rollout/canary został wygaszony.', 'erp-omd'); ?></p>
                        </div>
                    </div>
                </section>
                <?php endif; ?>
            </div>
            <?php if ($settings_tab !== 'backup_restore') : ?>
                <?php submit_button(__('Zapisz ustawienia', 'erp-omd')); ?>
            <?php endif; ?>
        </form>
        <form id="erp-omd-manual-backup-form" method="post">
            <?php wp_nonce_field('erp_omd_run_manual_backup'); ?>
            <input type="hidden" name="erp_omd_action" value="run_manual_backup" />
            <input type="hidden" name="settings_tab" value="backup_restore" />
        </form>
        <form id="erp-omd-restore-backup-form" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('erp_omd_restore_backup_bundle'); ?>
            <input type="hidden" name="erp_omd_action" value="restore_backup_bundle" />
            <input type="hidden" name="settings_tab" value="backup_restore" />
        </form>
        <form id="erp-omd-google-calendar-connect-form" method="post" action="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-settings', 'tab' => 'google_calendar'], admin_url('admin.php'))); ?>">
            <?php wp_nonce_field('erp_omd_google_calendar_connect'); ?>
            <input type="hidden" name="erp_omd_action" value="google_calendar_connect" />
        </form>
        <form id="erp-omd-google-calendar-disconnect-form" method="post" action="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-settings', 'tab' => 'google_calendar'], admin_url('admin.php'))); ?>">
            <?php wp_nonce_field('erp_omd_google_calendar_disconnect'); ?>
            <input type="hidden" name="erp_omd_action" value="google_calendar_disconnect" />
        </form>
        <form id="erp-omd-google-calendar-sync-now-form" method="post" action="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-settings', 'tab' => 'google_calendar'], admin_url('admin.php'))); ?>">
            <?php wp_nonce_field('erp_omd_google_calendar_sync_now'); ?>
            <input type="hidden" name="erp_omd_action" value="google_calendar_sync_now" />
        </form>
        <form id="erp-omd-google-calendar-fetch-calendars-form" method="post" action="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-settings', 'tab' => 'google_calendar'], admin_url('admin.php'))); ?>">
            <?php wp_nonce_field('erp_omd_google_calendar_fetch_calendars'); ?>
            <input type="hidden" name="erp_omd_action" value="google_calendar_fetch_calendars" />
        </form>
        <form id="erp-omd-ksef-sync-hub-dry-run-form" method="post" action="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-settings', 'tab' => 'ksef'], admin_url('admin.php'))); ?>">
            <?php wp_nonce_field('erp_omd_ksef_sync_hub_dry_run'); ?>
            <input type="hidden" name="erp_omd_action" value="ksef_sync_hub_dry_run" />
        </form>
    </div>
</div>
