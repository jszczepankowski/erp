<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Sprint 8 RC', 'erp-omd'); ?></h1>
    <div class="erp-omd-grid two-columns">
        <div class="erp-omd-card">
            <h2><?php esc_html_e('Status wdrożenia Sprintu 8', 'erp-omd'); ?></h2>
            <ul>
                <li><?php esc_html_e('Sprint 1: pracownicy, role, salary history, uninstall i REST API.', 'erp-omd'); ?></li>
                <li><?php esc_html_e('Sprint 2: klienci, stawki klienta, projekty i historia uwag klienta.', 'erp-omd'); ?></li>
                <li><?php esc_html_e('Sprint 3: stawki projektowe, time tracking, snapshoty i approval flow.', 'erp-omd'); ?></li>
                <li><?php esc_html_e('Sprint 4–6: finanse projektów, kosztorysy, raporty i kalendarz.', 'erp-omd'); ?></li>
                <li><?php esc_html_e('Sprint 7: alerty, załączniki, soft delete i lifecycle polish.', 'erp-omd'); ?></li>
                <li><?php esc_html_e('Sprint 8: hardening produkcyjny, API finalne, UX/admin polish i release candidate.', 'erp-omd'); ?></li>
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
            <h2><?php esc_html_e('Release candidate', 'erp-omd'); ?></h2>
            <p><strong><?php esc_html_e('Wersja pluginu:', 'erp-omd'); ?></strong> <?php echo esc_html(ERP_OMD_VERSION); ?></p>
            <p><strong><?php esc_html_e('API finalne:', 'erp-omd'); ?></strong> <?php esc_html_e('role, pracownicy, klienci, projekty, kosztorysy, time tracking, raporty, alerty, załączniki, meta i system.', 'erp-omd'); ?></p>
            <p><strong><?php esc_html_e('Paczka RC:', 'erp-omd'); ?></strong> <code>dist/erp-omd-sprint-8-rc.zip</code></p>
        </div>
    </div>
</div>
