<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Finanse', 'erp-omd'); ?></h1>

    <section class="erp-omd-card">
        <form method="get" class="erp-omd-filter-form">
            <input type="hidden" name="page" value="erp-omd-finances" />
            <label for="erp-omd-finance-month"><strong><?php esc_html_e('Miesiąc analizy', 'erp-omd'); ?></strong></label>
            <input id="erp-omd-finance-month" type="month" name="month" value="<?php echo esc_attr($month); ?>" />
            <button class="button button-primary" type="submit"><?php esc_html_e('Pokaż', 'erp-omd'); ?></button>
        </form>
    </section>

    <?php
    $all_profit_values = [];
    foreach ([$top_projects_best, $top_projects_worst, $top_clients_best, $top_clients_worst] as $group) {
        foreach ((array) $group as $row) {
            $all_profit_values[] = abs((float) ($row['profit'] ?? 0.0));
        }
    }
    $max_abs_profit = max(1.0, (float) (empty($all_profit_values) ? 1.0 : max($all_profit_values)));

    $render_profit_chart = static function (array $rows, $title, $css_class = '', $show_client_column = false) use ($max_abs_profit) {
        ?>
        <section class="erp-omd-card <?php echo esc_attr($css_class); ?>">
            <h2><?php echo esc_html($title); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <?php if ($show_client_column) : ?>
                            <th><?php esc_html_e('Klient', 'erp-omd'); ?></th>
                        <?php endif; ?>
                        <th><?php esc_html_e('Nazwa', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Wykres wyniku', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Wynik', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Marża %', 'erp-omd'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)) : ?>
                        <tr><td colspan="<?php echo $show_client_column ? '5' : '4'; ?>"><?php esc_html_e('Brak danych dla wybranego miesiąca.', 'erp-omd'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($rows as $row) : ?>
                            <?php
                            $profit = (float) ($row['profit'] ?? 0.0);
                            $bar_width = max(4, (int) round((abs($profit) / $max_abs_profit) * 100));
                            $bar_color = $profit >= 0 ? '#22a06b' : '#d92d20';
                            ?>
                            <tr>
                                <?php if ($show_client_column) : ?>
                                    <td><?php echo esc_html((string) ($row['client_name'] ?? '—')); ?></td>
                                <?php endif; ?>
                                <td><?php echo esc_html((string) ($row['name'] ?? '—')); ?></td>
                                <td>
                                    <div style="background:#f1f3f5;border-radius:4px;height:14px;position:relative;overflow:hidden;">
                                        <span style="display:block;height:14px;width:<?php echo esc_attr((string) $bar_width); ?>%;background:<?php echo esc_attr($bar_color); ?>;"></span>
                                    </div>
                                </td>
                                <td><?php echo esc_html(number_format_i18n($profit, 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) ($row['margin'] ?? 0.0), 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
        <?php
    };
    ?>

    <section class="erp-omd-card">
        <h2><?php esc_html_e('Trend 12m (line)', 'erp-omd'); ?></h2>
        <?php
        $trend_points = [];
        $trend_max = 1.0;
        foreach ((array) $trend_rows as $trend_row) {
            $value = (float) ($trend_row['controlling_result'] ?? 0.0);
            $trend_points[] = ['month' => (string) ($trend_row['month'] ?? ''), 'value' => $value];
            $trend_max = max($trend_max, abs($value));
        }
        $line_points = [];
        $count_points = count($trend_points);
        foreach ($trend_points as $index => $point_row) {
            $x = $count_points > 1 ? (int) round(($index / ($count_points - 1)) * 100) : 0;
            $y = (int) round(50 - (($point_row['value'] / $trend_max) * 45));
            $line_points[] = $x . ',' . $y;
        }
        ?>
        <svg viewBox="0 0 100 100" width="100%" height="220" role="img" aria-label="<?php esc_attr_e('Trend 12 miesięcy', 'erp-omd'); ?>">
            <line x1="0" y1="50" x2="100" y2="50" stroke="#d0d7de" stroke-width="0.8"></line>
            <polyline fill="none" stroke="#0969da" stroke-width="2" points="<?php echo esc_attr(implode(' ', $line_points)); ?>"></polyline>
        </svg>
    </section>

    <section class="erp-omd-card">
        <h2><?php esc_html_e('Przychód vs koszt (bar)', 'erp-omd'); ?></h2>
        <?php
        $max_bar_value = max(1.0, (float) max($selected_month_summary['revenue'], $selected_month_summary['cost']));
        $revenue_width = (int) round(($selected_month_summary['revenue'] / $max_bar_value) * 100);
        $cost_width = (int) round(($selected_month_summary['cost'] / $max_bar_value) * 100);
        ?>
        <div style="display:grid;gap:12px;">
            <div>
                <strong><?php esc_html_e('Przychód', 'erp-omd'); ?></strong>
                <div style="height:14px;background:#f1f3f5;border-radius:4px;overflow:hidden;"><span style="display:block;height:14px;width:<?php echo esc_attr((string) $revenue_width); ?>%;background:#22a06b;"></span></div>
                <small><?php echo esc_html(number_format_i18n((float) $selected_month_summary['revenue'], 2)); ?></small>
            </div>
            <div>
                <strong><?php esc_html_e('Koszt', 'erp-omd'); ?></strong>
                <div style="height:14px;background:#f1f3f5;border-radius:4px;overflow:hidden;"><span style="display:block;height:14px;width:<?php echo esc_attr((string) $cost_width); ?>%;background:#d1242f;"></span></div>
                <small><?php echo esc_html(number_format_i18n((float) $selected_month_summary['cost'], 2)); ?></small>
            </div>
        </div>
    </section>

    <section class="erp-omd-card">
        <h2><?php esc_html_e('Struktura kosztów (donut)', 'erp-omd'); ?></h2>
        <?php
        $cost_parts = [
            'Pensje' => (float) ($selected_month_summary['salary_cost'] ?? 0.0),
            'Koszty projektowe' => (float) ($selected_month_summary['project_direct_cost'] ?? 0.0),
            'Koszt czasu' => (float) ($selected_month_summary['time_cost'] ?? 0.0),
            'Koszty stałe' => (float) ($selected_month_summary['fixed_cost'] ?? 0.0),
        ];
        $total_cost_parts = max(1.0, array_sum($cost_parts));
        $colors = ['#0969da', '#22a06b', '#d1242f', '#8250df'];
        $offset = 0.0;
        ?>
        <div style="display:flex;gap:24px;align-items:center;">
            <svg viewBox="0 0 42 42" width="220" height="220" aria-label="<?php esc_attr_e('Struktura kosztów', 'erp-omd'); ?>">
                <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="#f1f3f5" stroke-width="6"></circle>
                <?php $index = 0; foreach ($cost_parts as $label => $value) : ?>
                    <?php $percent = (($value / $total_cost_parts) * 100); ?>
                    <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="<?php echo esc_attr($colors[$index % count($colors)]); ?>" stroke-width="6" stroke-dasharray="<?php echo esc_attr((string) $percent); ?> <?php echo esc_attr((string) (100 - $percent)); ?>" stroke-dashoffset="<?php echo esc_attr((string) (-$offset)); ?>"></circle>
                    <?php $offset += $percent; $index++; ?>
                <?php endforeach; ?>
            </svg>
            <ul>
                <?php $index = 0; foreach ($cost_parts as $label => $value) : ?>
                    <li><span style="display:inline-block;width:10px;height:10px;background:<?php echo esc_attr($colors[$index % count($colors)]); ?>;border-radius:50%;margin-right:6px;"></span><?php echo esc_html($label . ': ' . number_format_i18n((float) $value, 2)); ?></li>
                    <?php $index++; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <div class="erp-omd-page-sections">
        <?php $render_profit_chart($top_projects_best, __('TOP 5 najbardziej opłacalnych projektów', 'erp-omd'), '', true); ?>
        <?php $render_profit_chart($top_projects_worst, __('TOP 5 najmniej opłacalnych projektów', 'erp-omd'), '', true); ?>
        <?php $render_profit_chart($top_clients_best, __('TOP 5 najbardziej opłacalnych klientów', 'erp-omd')); ?>
        <?php $render_profit_chart($top_clients_worst, __('TOP 5 najmniej opłacalnych klientów', 'erp-omd')); ?>
    </div>
</div>
