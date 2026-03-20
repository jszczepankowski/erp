<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Centrum alertów', 'erp-omd'); ?></h1>
    <div class="erp-omd-card">
        <p class="description"><?php esc_html_e('Alerty obejmują przekroczenie budżetu, niską marżę, brakujące stawki oraz przypomnienia o 3 dniach bez wpisu czasu.', 'erp-omd'); ?></p>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Typ', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Encja', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Komunikat', 'erp-omd'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($alerts)) : ?>
                    <tr><td colspan="3"><?php esc_html_e('Brak aktywnych alertów.', 'erp-omd'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($alerts as $alert) : ?>
                        <tr>
                            <td><span class="erp-omd-badge erp-omd-badge-<?php echo esc_attr($alert['severity']); ?>"><?php echo esc_html($alert['code']); ?></span></td>
                            <td><?php echo esc_html(($alert['entity_type'] ?? '') . ' #' . (int) ($alert['entity_id'] ?? 0)); ?></td>
                            <td><?php echo esc_html($alert['message']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
