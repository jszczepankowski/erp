<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Ustawienia Sprint 8 RC', 'erp-omd'); ?></h1>
    <div class="erp-omd-card">
        <h2><?php esc_html_e('Konfiguracja lifecycle i alertów', 'erp-omd'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('erp_omd_save_settings'); ?>
            <input type="hidden" name="erp_omd_action" value="save_settings" />
            <p>
                <label for="erp-omd-alert-margin-threshold"><?php esc_html_e('Próg alertu niskiej marży (%)', 'erp-omd'); ?></label><br />
                <input id="erp-omd-alert-margin-threshold" type="number" min="0" step="0.01" name="alert_margin_threshold" value="<?php echo esc_attr($margin_threshold); ?>" />
            </p>
            <label>
                <input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked($delete_data); ?> />
                <?php esc_html_e('Usuń dane ERP OMD podczas uninstall pluginu.', 'erp-omd'); ?>
            </label>
            <?php submit_button(__('Zapisz ustawienia', 'erp-omd')); ?>
        </form>
    </div>
    <div class="erp-omd-card">
        <h2><?php esc_html_e('Status release candidate', 'erp-omd'); ?></h2>
        <ul>
            <li><strong><?php esc_html_e('Wersja pluginu:', 'erp-omd'); ?></strong> <?php echo esc_html(ERP_OMD_VERSION); ?></li>
            <li><strong><?php esc_html_e('Wersja bazy:', 'erp-omd'); ?></strong> <?php echo esc_html(ERP_OMD_DB_VERSION); ?></li>
            <li><strong><?php esc_html_e('Paczkowanie RC:', 'erp-omd'); ?></strong> <code>./scripts/build-sprint-8-rc.sh</code></li>
            <li><strong><?php esc_html_e('Sanity check RC:', 'erp-omd'); ?></strong> <code>./scripts/test-sprint-8.sh</code></li>
        </ul>
    </div>
</div>