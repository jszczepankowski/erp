<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Ustawienia', 'erp-omd'); ?></h1>
    <div class="erp-omd-card">
        <h2><?php esc_html_e('Konfiguracja lifecycle, alertów i logowania FRONT', 'erp-omd'); ?></h2>
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
                        <div class="erp-omd-form-field erp-omd-form-field-span-2">
                            <label class="erp-omd-form-label">
                                <input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked($delete_data); ?> />
                                <?php esc_html_e('Usuń dane ERP OMD podczas uninstall pluginu.', 'erp-omd'); ?>
                            </label>
                        </div>
                    </div>
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
