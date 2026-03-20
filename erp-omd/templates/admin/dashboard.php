<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Sprint 3', 'erp-omd'); ?></h1>
    <div class="erp-omd-grid two-columns">
        <div class="erp-omd-card">
            <h2><?php esc_html_e('Status wdrożenia Sprintu 3', 'erp-omd'); ?></h2>
            <ul>
                <li><?php esc_html_e('Sprint 1: pracownicy, role, salary history, uninstall i REST API.', 'erp-omd'); ?></li>
                <li><?php esc_html_e('Sprint 2: klienci, stawki klienta, projekty i historia uwag klienta.', 'erp-omd'); ?></li>
                <li><?php esc_html_e('Sprint 3: stawki projektowe, time tracking, snapshoty i approval flow.', 'erp-omd'); ?></li>
                <li><?php esc_html_e('Admin UI: dashboard, role, pracownicy, klienci, projekty, czas pracy, settings.', 'erp-omd'); ?></li>
            </ul>
        </div>
        <div class="erp-omd-card">
            <h2><?php esc_html_e('Szybkie metryki', 'erp-omd'); ?></h2>
            <p><strong><?php echo esc_html(count($employees)); ?></strong> <?php esc_html_e('pracowników', 'erp-omd'); ?></p>
            <p><strong><?php echo esc_html(count($roles)); ?></strong> <?php esc_html_e('ról projektowych', 'erp-omd'); ?></p>
            <p><strong><?php echo esc_html(count($clients)); ?></strong> <?php esc_html_e('klientów', 'erp-omd'); ?></p>
            <p><strong><?php echo esc_html(count($projects)); ?></strong> <?php esc_html_e('projektów', 'erp-omd'); ?></p>
        </div>
    </div>
</div>
