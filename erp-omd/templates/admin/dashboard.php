<div class="wrap erp-omd-admin">
    <div class="erp-omd-dashboard-hero">
        <div class="erp-omd-dashboard-hero-copy">
            <h1><?php esc_html_e('ERP OMD — Dashboard', 'erp-omd'); ?></h1>
            <p><?php esc_html_e('Premiumowy, spokojny cockpit operacyjny: najważniejsze liczby, skróty i alerty w jednej, czystej warstwie roboczej.', 'erp-omd'); ?></p>
        </div>
        <div class="erp-omd-kpi-grid">
            <div class="erp-omd-kpi">
                <span class="erp-omd-kpi-label"><?php esc_html_e('Pracownicy', 'erp-omd'); ?></span>
                <strong><?php echo esc_html(count($employees)); ?></strong>
            </div>
            <div class="erp-omd-kpi">
                <span class="erp-omd-kpi-label"><?php esc_html_e('Projekty', 'erp-omd'); ?></span>
                <strong><?php echo esc_html(count($projects)); ?></strong>
            </div>
            <div class="erp-omd-kpi erp-omd-kpi-accent erp-omd-kpi-profit">
                <span class="erp-omd-kpi-label"><?php esc_html_e('Zysk miesięczny', 'erp-omd'); ?></span>
                <strong><?php echo esc_html(number_format_i18n((float) $monthly_totals['employee_profit'], 2)); ?></strong>
            </div>
        </div>
    </div>
    <?php
    $dashboard_cost_total = max(0.0, (float) ($monthly_totals['hourly_cost_total'] ?? 0));
    $dashboard_profit_total = max(0.0, (float) ($monthly_totals['employee_profit'] ?? 0));
    $dashboard_revenue_total = $dashboard_cost_total + $dashboard_profit_total;
    $dashboard_performance_total = max(1.0, $dashboard_revenue_total);
    $dashboard_cost_share = max(2, (int) round(($dashboard_cost_total / $dashboard_performance_total) * 100));
    $dashboard_profit_share = max(2, 100 - $dashboard_cost_share);
    $dashboard_alert_total = max(1, array_sum($alert_summary));
    $dashboard_alert_error = (int) round(((int) $alert_summary['error'] / $dashboard_alert_total) * 100);
    $dashboard_alert_warning_end = (int) round((((int) $alert_summary['error'] + (int) $alert_summary['warning']) / $dashboard_alert_total) * 100);
    ?>
    <div class="erp-omd-dashboard-visuals">
        <div class="erp-omd-card erp-omd-chart-card">
            <div class="erp-omd-chart-header">
                <div>
                    <h2><?php esc_html_e('Wykres rentowności miesiąca', 'erp-omd'); ?></h2>
                    <p><?php esc_html_e('Szybki obraz relacji między przychodem, kosztem i zyskiem dla bieżącego okresu raportowego.', 'erp-omd'); ?></p>
                </div>
                <span class="erp-omd-chart-period"><?php echo esc_html($reporting_month_label); ?></span>
            </div>
            <div class="erp-omd-chart-rail" aria-hidden="true">
                <span class="erp-omd-chart-segment erp-omd-chart-segment-cost" style="width: <?php echo esc_attr((string) $dashboard_cost_share); ?>%"></span>
                <span class="erp-omd-chart-segment erp-omd-chart-segment-profit" style="width: <?php echo esc_attr((string) $dashboard_profit_share); ?>%"></span>
            </div>
            <div class="erp-omd-chart-legend">
                <div class="erp-omd-chart-legend-item">
                    <span class="erp-omd-chart-dot erp-omd-chart-dot-revenue"></span>
                    <span>
                        <strong><?php echo esc_html(number_format_i18n($dashboard_revenue_total, 2)); ?></strong>
                        <span><?php esc_html_e('Przychód', 'erp-omd'); ?></span>
                    </span>
                </div>
                <div class="erp-omd-chart-legend-item">
                    <span class="erp-omd-chart-dot erp-omd-chart-dot-cost"></span>
                    <span>
                        <strong><?php echo esc_html(number_format_i18n($dashboard_cost_total, 2)); ?></strong>
                        <span><?php esc_html_e('Koszt', 'erp-omd'); ?></span>
                    </span>
                </div>
                <div class="erp-omd-chart-legend-item">
                    <span class="erp-omd-chart-dot erp-omd-chart-dot-profit"></span>
                    <span>
                        <strong><?php echo esc_html(number_format_i18n($dashboard_profit_total, 2)); ?></strong>
                        <span><?php esc_html_e('Zysk', 'erp-omd'); ?></span>
                    </span>
                </div>
            </div>
        </div>
        <div class="erp-omd-card erp-omd-chart-card">
            <div class="erp-omd-chart-header">
                <div>
                    <h2><?php esc_html_e('Mapa alertów', 'erp-omd'); ?></h2>
                    <p><?php esc_html_e('Rozkład aktywnych alertów pomaga szybciej ocenić, czy dominują błędy, ostrzeżenia czy informacje operacyjne.', 'erp-omd'); ?></p>
                </div>
            </div>
            <div class="erp-omd-donut-chart">
                <div class="erp-omd-donut-chart-visual" style="--erp-omd-chart-error: <?php echo esc_attr((string) $dashboard_alert_error); ?>%; --erp-omd-chart-warning-end: <?php echo esc_attr((string) $dashboard_alert_warning_end); ?>%;">
                    <span class="erp-omd-donut-chart-total"><?php echo esc_html((string) array_sum($alert_summary)); ?></span>
                    <span class="erp-omd-donut-chart-label"><?php esc_html_e('Alertów', 'erp-omd'); ?></span>
                </div>
                <div class="erp-omd-chart-legend">
                    <div class="erp-omd-chart-legend-item">
                        <span class="erp-omd-chart-dot erp-omd-chart-dot-error"></span>
                        <span>
                            <strong><?php echo esc_html((string) ((int) $alert_summary['error'])); ?></strong>
                            <span><?php esc_html_e('Błędy krytyczne', 'erp-omd'); ?></span>
                        </span>
                    </div>
                    <div class="erp-omd-chart-legend-item">
                        <span class="erp-omd-chart-dot erp-omd-chart-dot-warning"></span>
                        <span>
                            <strong><?php echo esc_html((string) ((int) $alert_summary['warning'])); ?></strong>
                            <span><?php esc_html_e('Ostrzeżenia', 'erp-omd'); ?></span>
                        </span>
                    </div>
                    <div class="erp-omd-chart-legend-item">
                        <span class="erp-omd-chart-dot erp-omd-chart-dot-info"></span>
                        <span>
                            <strong><?php echo esc_html((string) ((int) $alert_summary['info'])); ?></strong>
                            <span><?php esc_html_e('Informacje', 'erp-omd'); ?></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
            <h2><?php printf(esc_html__('Wydajność za %s', 'erp-omd'), esc_html($reporting_month_label)); ?></h2>
            <p><strong><?php echo esc_html(number_format_i18n((float) $monthly_totals['reported_hours'], 2)); ?></strong> <?php esc_html_e('zatwierdzonych godzin', 'erp-omd'); ?></p>
            <p><strong><?php echo esc_html(number_format_i18n((float) $monthly_totals['hourly_cost_total'], 2)); ?></strong> <?php esc_html_e('kosztu godzinowego', 'erp-omd'); ?></p>
            <p><strong><?php echo esc_html(number_format_i18n((float) $monthly_totals['employee_profit'], 2)); ?></strong> <?php esc_html_e('zysku pracowniczego', 'erp-omd'); ?></p>
            <p><strong><?php echo esc_html((int) $monthly_totals['active_employees']); ?></strong> <?php esc_html_e('pracowników z zatwierdzonym czasem', 'erp-omd'); ?></p>
            <p><a class="button button-secondary" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-reports', 'tab' => 'reports', 'report_type' => 'monthly'], admin_url('admin.php'))); ?>"><?php esc_html_e('Otwórz raport miesięczny', 'erp-omd'); ?></a></p>
        </div>
        <div class="erp-omd-card">
            <h2><?php esc_html_e('Skrót alertów', 'erp-omd'); ?></h2>
            <p><strong><?php echo esc_html((int) $alert_summary['error']); ?></strong> <?php esc_html_e('błędów krytycznych', 'erp-omd'); ?></p>
            <p><strong><?php echo esc_html((int) $alert_summary['warning']); ?></strong> <?php esc_html_e('ostrzeżeń', 'erp-omd'); ?></p>
            <p><strong><?php echo esc_html((int) $alert_summary['info']); ?></strong> <?php esc_html_e('powiadomień informacyjnych', 'erp-omd'); ?></p>
            <?php if (! empty($dashboard_recent_alerts)) : ?>
                <ul>
                    <?php foreach ($dashboard_recent_alerts as $alert) : ?>
                        <li>
                            <strong><?php echo esc_html(strtoupper((string) ($alert['severity'] ?? 'info'))); ?>:</strong>
                            <?php echo esc_html((string) ($alert['message'] ?? '')); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="description"><?php esc_html_e('Brak aktywnych alertów.', 'erp-omd'); ?></p>
            <?php endif; ?>
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
