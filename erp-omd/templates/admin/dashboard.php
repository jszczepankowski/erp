<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Sprint 1', 'erp-omd'); ?></h1>
    <div class="erp-omd-grid two-columns">
        <div class="erp-omd-card">
            <h2><?php esc_html_e('Status wdrożenia Sprintu 1', 'erp-omd'); ?></h2>
            <ul>
                <li><?php esc_html_e('Migracje DB: roles, employees, employee_roles, salary_history.', 'erp-omd'); ?></li>
                <li><?php esc_html_e('Role kont: admin, manager, worker.', 'erp-omd'); ?></li>
                <li><?php esc_html_e('Admin UI: dashboard, role, pracownicy, settings/uninstall.', 'erp-omd'); ?></li>
                <li><?php esc_html_e('REST API: roles CRUD, employees CRUD, salary history CRUD.', 'erp-omd'); ?></li>
            </ul>
        </div>
        <div class="erp-omd-card">
            <h2><?php esc_html_e('Szybkie metryki', 'erp-omd'); ?></h2>
            <p><strong><?php echo esc_html(count($employees)); ?></strong> <?php esc_html_e('pracowników', 'erp-omd'); ?></p>
            <p><strong><?php echo esc_html(count($roles)); ?></strong> <?php esc_html_e('ról projektowych', 'erp-omd'); ?></p>
            <p><?php esc_html_e('Godziny miesięczne są ręczne z automatyczną podpowiedzią opartą o dni robocze.', 'erp-omd'); ?></p>
        </div>
    </div>
</div>
