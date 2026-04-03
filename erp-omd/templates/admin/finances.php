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

    <div class="erp-omd-finance-visual-grid">
        <section class="erp-omd-card erp-omd-finance-visual-card">
            <h2><?php esc_html_e('Trend 12m', 'erp-omd'); ?></h2>
            <?php
            $trend_points = [];
            foreach ((array) $trend_rows as $trend_row) {
                $trend_points[] = [
                    'month' => (string) ($trend_row['month'] ?? ''),
                    'value' => (float) ($trend_row['controlling_result'] ?? 0.0),
                ];
            }
            $trend_values = array_map(static function ($point_row) {
                return (float) ($point_row['value'] ?? 0.0);
            }, $trend_points);
            $trend_max = empty($trend_values) ? 1.0 : max(0.0, max($trend_values));
            $trend_min = empty($trend_values) ? 0.0 : min(0.0, min($trend_values));
            $trend_range = max(1.0, $trend_max - $trend_min);
            $chart_left = 56;
            $chart_right = 676;
            $chart_top = 18;
            $chart_bottom = 214;
            $chart_width = $chart_right - $chart_left;
            $chart_height = $chart_bottom - $chart_top;
            $count_points = count($trend_points);
            $line_points = [];
            $bars = [];
            $baseline_y = $chart_bottom - ((0 - $trend_min) / $trend_range) * $chart_height;
            foreach ($trend_points as $index => $point_row) {
                $x = $count_points > 1 ? $chart_left + (($index / ($count_points - 1)) * $chart_width) : $chart_left + ($chart_width / 2);
                $y = $chart_bottom - ((($point_row['value'] ?? 0.0) - $trend_min) / $trend_range) * $chart_height;
                $line_points[] = round($x, 2) . ',' . round($y, 2);
                $bars[] = [
                    'x' => max($chart_left + 2, $x - 10),
                    'y' => min($baseline_y, $y),
                    'height' => max(3, abs($baseline_y - $y)),
                    'color' => ((float) $point_row['value'] >= 0.0) ? '#c89a5a' : '#c86b5f',
                    'label' => (string) ($point_row['month'] ?? ''),
                ];
            }
            ?>
            <svg class="erp-omd-finance-trend-chart" viewBox="0 0 700 240" role="img" aria-label="<?php esc_attr_e('Trend 12 miesięcy', 'erp-omd'); ?>">
                <line x1="<?php echo esc_attr((string) $chart_left); ?>" y1="<?php echo esc_attr((string) $chart_top); ?>" x2="<?php echo esc_attr((string) $chart_left); ?>" y2="<?php echo esc_attr((string) $chart_bottom); ?>" class="erp-omd-finance-axis"></line>
                <line x1="<?php echo esc_attr((string) $chart_left); ?>" y1="<?php echo esc_attr((string) $chart_bottom); ?>" x2="<?php echo esc_attr((string) $chart_right); ?>" y2="<?php echo esc_attr((string) $chart_bottom); ?>" class="erp-omd-finance-axis"></line>
                <line x1="<?php echo esc_attr((string) $chart_left); ?>" y1="<?php echo esc_attr((string) round($baseline_y, 2)); ?>" x2="<?php echo esc_attr((string) $chart_right); ?>" y2="<?php echo esc_attr((string) round($baseline_y, 2)); ?>" class="erp-omd-finance-baseline"></line>
                <?php foreach ($bars as $bar) : ?>
                    <rect x="<?php echo esc_attr((string) round($bar['x'], 2)); ?>" y="<?php echo esc_attr((string) round($bar['y'], 2)); ?>" width="20" height="<?php echo esc_attr((string) round($bar['height'], 2)); ?>" fill="<?php echo esc_attr($bar['color']); ?>" opacity="0.45"></rect>
                <?php endforeach; ?>
                <polyline fill="none" stroke="#212123" stroke-width="2.4" points="<?php echo esc_attr(implode(' ', $line_points)); ?>"></polyline>
                <?php foreach ($trend_points as $index => $point_row) : ?>
                    <?php
                    $x = $count_points > 1 ? $chart_left + (($index / ($count_points - 1)) * $chart_width) : $chart_left + ($chart_width / 2);
                    $y = $chart_bottom - ((($point_row['value'] ?? 0.0) - $trend_min) / $trend_range) * $chart_height;
                    ?>
                    <circle cx="<?php echo esc_attr((string) round($x, 2)); ?>" cy="<?php echo esc_attr((string) round($y, 2)); ?>" r="3.2" fill="#ddb178"></circle>
                <?php endforeach; ?>
            </svg>
        </section>

        <section class="erp-omd-card erp-omd-finance-visual-card">
            <h2><?php esc_html_e('Przychód vs koszt', 'erp-omd'); ?></h2>
            <?php
            $max_bar_value = max(1.0, (float) max($selected_month_summary['revenue'], $selected_month_summary['cost']));
            $revenue_width = (int) round(($selected_month_summary['revenue'] / $max_bar_value) * 100);
            $cost_width = (int) round(($selected_month_summary['cost'] / $max_bar_value) * 100);
            ?>
            <div class="erp-omd-finance-bars">
                <div class="erp-omd-finance-bar-row">
                    <strong><?php esc_html_e('Przychód', 'erp-omd'); ?></strong>
                    <div class="erp-omd-finance-bar-track"><span class="erp-omd-finance-bar-fill erp-omd-finance-bar-fill-revenue" style="width:<?php echo esc_attr((string) $revenue_width); ?>%"></span></div>
                    <small><?php echo esc_html(number_format_i18n((float) $selected_month_summary['revenue'], 2)); ?></small>
                </div>
                <div class="erp-omd-finance-bar-row">
                    <strong><?php esc_html_e('Koszt', 'erp-omd'); ?></strong>
                    <div class="erp-omd-finance-bar-track"><span class="erp-omd-finance-bar-fill erp-omd-finance-bar-fill-cost" style="width:<?php echo esc_attr((string) $cost_width); ?>%"></span></div>
                    <small><?php echo esc_html(number_format_i18n((float) $selected_month_summary['cost'], 2)); ?></small>
                </div>
            </div>
        </section>

        <section class="erp-omd-card erp-omd-finance-visual-card">
            <h2><?php esc_html_e('Struktura kosztów', 'erp-omd'); ?></h2>
            <?php
            $cost_parts = [
                'Pensje' => (float) ($selected_month_summary['salary_cost'] ?? 0.0),
                'Koszty projektowe' => (float) ($selected_month_summary['project_direct_cost'] ?? 0.0),
                'Koszt czasu' => (float) ($selected_month_summary['time_cost'] ?? 0.0),
                'Koszty stałe' => (float) ($selected_month_summary['fixed_cost'] ?? 0.0),
            ];
            $total_cost_parts = max(1.0, array_sum($cost_parts));
            $colors = ['#212123', '#c89a5a', '#c86b5f', '#b9b3ac'];
            $offset = 0.0;
            ?>
            <div class="erp-omd-finance-donut-wrap">
                <svg viewBox="0 0 42 42" width="180" height="180" aria-label="<?php esc_attr_e('Struktura kosztów', 'erp-omd'); ?>">
                    <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="#f3eee7" stroke-width="6"></circle>
                    <?php $index = 0; foreach ($cost_parts as $label => $value) : ?>
                        <?php $percent = (($value / $total_cost_parts) * 100); ?>
                        <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="<?php echo esc_attr($colors[$index % count($colors)]); ?>" stroke-width="6" stroke-dasharray="<?php echo esc_attr((string) $percent); ?> <?php echo esc_attr((string) (100 - $percent)); ?>" stroke-dashoffset="<?php echo esc_attr((string) (-$offset)); ?>"></circle>
                        <?php $offset += $percent; $index++; ?>
                    <?php endforeach; ?>
                </svg>
                <ul class="erp-omd-finance-donut-legend">
                    <?php $index = 0; foreach ($cost_parts as $label => $value) : ?>
                        <li><span style="background:<?php echo esc_attr($colors[$index % count($colors)]); ?>"></span><?php echo esc_html($label . ': ' . number_format_i18n((float) $value, 2)); ?></li>
                        <?php $index++; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
    </div>

    <div class="erp-omd-page-sections">
        <?php $render_profit_chart($top_projects_best, __('TOP 5 najbardziej opłacalnych projektów', 'erp-omd'), '', true); ?>
        <?php $render_profit_chart($top_projects_worst, __('TOP 5 najmniej opłacalnych projektów', 'erp-omd'), '', true); ?>
        <?php $render_profit_chart($top_clients_best, __('TOP 5 najbardziej opłacalnych klientów', 'erp-omd')); ?>
        <?php $render_profit_chart($top_clients_worst, __('TOP 5 najmniej opłacalnych klientów', 'erp-omd')); ?>
    </div>
</div>
