<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Dashboard', 'erp-omd'); ?></h1>
    <div class="erp-omd-grid two-columns">
        <div class="erp-omd-card">
            <h2><?php esc_html_e('Zakres systemu', 'erp-omd'); ?></h2>
            <ul>
                <li><?php esc_html_e('Kadry i role: pracownicy, role projektowe, salary history oraz uprawnienia.', 'erp-omd'); ?></li>
                <li><?php esc_html_e('CRM i delivery: klienci, stawki klienta, projekty, kosztorysy i uwagi projektowe.', 'erp-omd'); ?></li>
                <li><?php esc_html_e('Operacje: time tracking, approval flow, snapshoty stawek i kosztów oraz raportowanie.', 'erp-omd'); ?></li>
                <li><?php esc_html_e('Kontrola i utrzymanie: alerty, załączniki, ustawienia lifecycle oraz REST API.', 'erp-omd'); ?></li>
            </ul>
        </div>
        <div class="erp-omd-card">
            <h2><?php esc_html_e('Szybkie metryki', 'erp-omd'); ?></h2>
            <p><strong><?php echo esc_html(count($employees)); ?></strong> <?php esc_html_e('pracowników', 'erp-omd'); ?></p>
            <p><strong><?php echo esc_html(count($roles)); ?></strong> <?php esc_html_e('ról projektowych', 'erp-omd'); ?></p>
            <p><strong><?php echo esc_html(count($clients)); ?></strong> <?php esc_html_e('klientów', 'erp-omd'); ?></p>
            <p><strong><?php echo esc_html(count($projects)); ?></strong> <?php esc_html_e('projektów', 'erp-omd'); ?></p>
            <p><strong><?php echo esc_html(count($alerts)); ?></strong> <?php esc_html_e('aktywnych alertów', 'erp-omd'); ?></p>
            <p><a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=erp-omd-alerts')); ?>"><?php esc_html_e('Przejdź do centrum alertów', 'erp-omd'); ?></a></p>
        </div>
        <div class="erp-omd-card">
            <h2><?php esc_html_e('Skróty', 'erp-omd'); ?></h2>
            <div class="erp-omd-action-group">
                <?php foreach ($dashboard_shortcuts as $shortcut) : ?>
                    <a class="button button-secondary" href="<?php echo esc_url($shortcut['url']); ?>"><?php echo esc_html($shortcut['label']); ?></a>
                <?php endforeach; ?>
            </div>
            <p class="description"><?php esc_html_e('Najczęściej używane akcje operacyjne dostępne bez przechodzenia przez pełną nawigację.', 'erp-omd'); ?></p>
        </div>
    </div>
</div>
