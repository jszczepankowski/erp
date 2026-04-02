<div class="wrap erp-omd-admin">
    <div class="erp-omd-dashboard-hero">
        <div class="erp-omd-dashboard-hero-copy">
            <h1><?php esc_html_e('ERP OMD — Dashboard', 'erp-omd'); ?></h1>
            <form method="get" class="erp-omd-inline-form" style="margin-top:12px;">
                <input type="hidden" name="page" value="erp-omd">
                <label for="erp-omd-dashboard-reporting-month" style="margin-right:8px;"><?php esc_html_e('Miesiąc dashboardu', 'erp-omd'); ?></label>
                <input id="erp-omd-dashboard-reporting-month" type="month" name="reporting_month" value="<?php echo esc_attr($reporting_month); ?>">
                <button type="submit" class="button button-secondary"><?php esc_html_e('Zastosuj miesiąc', 'erp-omd'); ?></button>
            </form>
        </div>
        <div class="erp-omd-kpi-grid">
            <div class="erp-omd-kpi erp-omd-kpi-accent erp-omd-kpi-profit">
                <span class="erp-omd-kpi-label"><?php esc_html_e('Wynik controllingowy', 'erp-omd'); ?></span>
                <strong><?php echo esc_html(number_format_i18n((float) ($omd_month_row['controlling_result'] ?? 0), 2)); ?></strong>
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
    $dashboard_metric_tiles = [
        [
            'icon' => 'groups',
            'value' => count($employees),
            'label' => __('Pracownicy', 'erp-omd'),
            'variant' => '',
        ],
        [
            'icon' => 'businessman',
            'value' => count($clients),
            'label' => __('Klienci', 'erp-omd'),
            'variant' => '',
        ],
        [
            'icon' => 'portfolio',
            'value' => count($projects),
            'label' => __('Projekty', 'erp-omd'),
            'variant' => '',
        ],
        [
            'icon' => 'clock',
            'value' => number_format_i18n((float) $monthly_totals['reported_hours'], 2),
            'label' => sprintf(__('Godziny · %s', 'erp-omd'), $reporting_month_label),
            'variant' => '',
        ],
        [
            'icon' => 'money-alt',
            'value' => number_format_i18n((float) $monthly_totals['hourly_cost_total'], 2),
            'label' => sprintf(__('Koszt pracy · %s', 'erp-omd'), $reporting_month_label),
            'variant' => 'erp-omd-metric-tile-muted',
        ],
        [
            'icon' => 'chart-line',
            'value' => number_format_i18n((float) $monthly_totals['employee_profit'], 2),
            'label' => sprintf(__('Zysk z pracy · %s', 'erp-omd'), $reporting_month_label),
            'variant' => 'erp-omd-metric-tile-accent',
        ],
    ];
    $dashboard_shortcut_icons = [
        __('Dodaj klienta', 'erp-omd') => 'plus-alt2',
        __('Dodaj projekt', 'erp-omd') => 'portfolio',
        __('Dodaj wpis czasu', 'erp-omd') => 'clock',
        __('Raport miesięczny', 'erp-omd') => 'media-spreadsheet',
    ];
    ?>
    <div class="erp-omd-shortcut-grid">
        <?php foreach ($dashboard_shortcuts as $shortcut) : ?>
            <?php $shortcut_icon = $dashboard_shortcut_icons[$shortcut['label']] ?? 'arrow-right-alt'; ?>
            <a class="erp-omd-shortcut-card" href="<?php echo esc_url($shortcut['url']); ?>">
                <span class="erp-omd-shortcut-icon dashicons dashicons-<?php echo esc_attr($shortcut_icon); ?>" aria-hidden="true"></span>
                <span>
                    <strong><?php echo esc_html($shortcut['label']); ?></strong>
                    <span><?php esc_html_e('Szybka akcja operacyjna', 'erp-omd'); ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <section class="erp-omd-metric-section">
        <div class="erp-omd-section-header">
            <h2><?php printf(esc_html__('Puls operacyjny za %s', 'erp-omd'), esc_html($reporting_month_label)); ?></h2>
        </div>
        <div class="erp-omd-metric-strip">
            <?php foreach ($dashboard_metric_tiles as $tile) : ?>
                <div class="erp-omd-metric-tile <?php echo esc_attr($tile['variant']); ?>">
                    <span class="erp-omd-metric-icon dashicons dashicons-<?php echo esc_attr($tile['icon']); ?>" aria-hidden="true"></span>
                    <span class="erp-omd-metric-copy">
                        <strong><?php echo esc_html((string) $tile['value']); ?></strong>
                        <span><?php echo esc_html($tile['label']); ?></span>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

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
                    <h2><?php esc_html_e('Miesięczny bilans operacyjny', 'erp-omd'); ?></h2>
                    <p><?php esc_html_e('Ujęcie bieżącego miesiąca: koszty projektowe, koszty czasu, zysk godzinowy i wynik controllingowy.', 'erp-omd'); ?></p>
                </div>
                <span class="erp-omd-chart-period"><?php echo esc_html($reporting_month_label); ?></span>
            </div>
            <div class="erp-omd-monthly-bars">
                <?php foreach ($dashboard_monthly_finance_metrics as $metric_row) : ?>
                    <?php
                    $metric_value = (float) ($metric_row['value'] ?? 0.0);
                    $metric_width = max(4, (int) round((abs($metric_value) / $dashboard_monthly_finance_max) * 100));
                    $metric_tone = (string) ($metric_row['tone'] ?? 'cost');
                    ?>
                    <div class="erp-omd-monthly-bars-row">
                        <div class="erp-omd-monthly-bars-label"><?php echo esc_html((string) ($metric_row['label'] ?? '')); ?></div>
                        <div class="erp-omd-monthly-bars-track">
                            <span class="erp-omd-monthly-bars-fill erp-omd-monthly-bars-fill-<?php echo esc_attr($metric_tone); ?>" style="width: <?php echo esc_attr((string) $metric_width); ?>%"></span>
                        </div>
                        <div class="erp-omd-monthly-bars-value <?php echo $metric_value < 0 ? 'erp-omd-monthly-bars-value-loss' : ''; ?>">
                            <?php echo esc_html(number_format_i18n($metric_value, 2)); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="erp-omd-card erp-omd-chart-card">
            <div class="erp-omd-chart-header">
                <div>
                    <h2><?php esc_html_e('Mapa alertów', 'erp-omd'); ?></h2>
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
</div>
