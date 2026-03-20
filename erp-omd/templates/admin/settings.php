<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Ustawienia Sprint 1', 'erp-omd'); ?></h1>
    <div class="erp-omd-card">
        <h2><?php esc_html_e('Uninstall', 'erp-omd'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('erp_omd_save_settings'); ?>
            <input type="hidden" name="erp_omd_action" value="save_settings" />
            <label>
                <input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked($delete_data); ?> />
                <?php esc_html_e('Usuń dane ERP OMD podczas uninstall pluginu.', 'erp-omd'); ?>
            </label>
            <?php submit_button(__('Zapisz ustawienia', 'erp-omd')); ?>
        </form>
    </div>
</div>
