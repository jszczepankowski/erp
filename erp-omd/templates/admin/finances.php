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

    $render_profit_chart = static function (array $rows, $title, $css_class = '') use ($max_abs_profit) {
        ?>
        <section class="erp-omd-card <?php echo esc_attr($css_class); ?>">
            <h2><?php echo esc_html($title); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Nazwa', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Wykres wyniku', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Wynik', 'erp-omd'); ?></th>
                        <th><?php esc_html_e('Marża %', 'erp-omd'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)) : ?>
                        <tr><td colspan="4"><?php esc_html_e('Brak danych dla wybranego miesiąca.', 'erp-omd'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($rows as $row) : ?>
                            <?php
                            $profit = (float) ($row['profit'] ?? 0.0);
                            $bar_width = max(4, (int) round((abs($profit) / $max_abs_profit) * 100));
                            $bar_color = $profit >= 0 ? '#22a06b' : '#d92d20';
                            ?>
                            <tr>
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

    <div class="erp-omd-page-sections">
        <?php $render_profit_chart($top_projects_best, __('TOP 5 najbardziej opłacalnych projektów', 'erp-omd')); ?>
        <?php $render_profit_chart($top_projects_worst, __('TOP 5 najmniej opłacalnych projektów', 'erp-omd')); ?>
        <?php $render_profit_chart($top_clients_best, __('TOP 5 najbardziej opłacalnych klientów', 'erp-omd')); ?>
        <?php $render_profit_chart($top_clients_worst, __('TOP 5 najmniej opłacalnych klientów', 'erp-omd')); ?>
    </div>
</div>
