<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Kalendarz agencji', 'erp-omd'); ?></h1>

    <?php
    $calendar_cursor = DateTimeImmutable::createFromFormat('Y-m-d', $calendar_month . '-01') ?: new DateTimeImmutable(current_time('Y-m') . '-01');
    $calendar_grid_start = $calendar_cursor->modify('-' . ((int) $calendar_cursor->format('N') - 1) . ' days');
    $calendar_month_end = $calendar_cursor->modify('last day of this month');
    $calendar_grid_end = $calendar_month_end->modify('+' . (7 - (int) $calendar_month_end->format('N')) . ' days');
    $calendar_items_by_date = [];
    foreach ((array) $events as $event) {
        $event_type = (string) ($event['event_type'] ?? 'deadline');
        $date_start = (string) ($event['date_start'] ?? '');
        $date_end = (string) ($event['date_end'] ?? $date_start);
        if ($date_start === '') {
            continue;
        }

        if ($event_type === 'range') {
            $event_start = DateTimeImmutable::createFromFormat('Y-m-d', $date_start);
            $event_end = DateTimeImmutable::createFromFormat('Y-m-d', $date_end);
            if (! $event_start || ! $event_end || $event_end < $event_start) {
                continue;
            }

            $render_start = $event_start < $calendar_grid_start ? $calendar_grid_start : $event_start;
            $render_end = $event_end > $calendar_grid_end ? $calendar_grid_end : $event_end;
            if ($render_end < $render_start) {
                continue;
            }

            $range_period = new DatePeriod($render_start, new DateInterval('P1D'), $render_end->modify('+1 day'));
            foreach ($range_period as $range_day) {
                $range_day_key = $range_day->format('Y-m-d');
                $calendar_items_by_date[$range_day_key][] = [
                    'type' => 'range',
                    'project_id' => (int) ($event['project_id'] ?? 0),
                    'project_name' => (string) ($event['project_name'] ?? ''),
                    'is_range_start' => $range_day->format('Y-m-d') === $event_start->format('Y-m-d'),
                    'is_range_end' => $range_day->format('Y-m-d') === $event_end->format('Y-m-d'),
                ];
            }
            continue;
        }

        $calendar_items_by_date[$date_start][] = [
            'type' => 'deadline',
            'project_id' => (int) ($event['project_id'] ?? 0),
            'project_name' => (string) ($event['project_name'] ?? ''),
        ];
    }
    $calendar_weeks = [];
    $calendar_week = [];
    $calendar_period = new DatePeriod($calendar_grid_start, new DateInterval('P1D'), $calendar_grid_end->modify('+1 day'));
    foreach ($calendar_period as $calendar_day_cursor) {
        $calendar_date_key = $calendar_day_cursor->format('Y-m-d');
        $calendar_week[] = [
            'day' => $calendar_day_cursor->format('j'),
            'is_current_month' => $calendar_day_cursor->format('Y-m') === $calendar_month,
            'items' => $calendar_items_by_date[$calendar_date_key] ?? [],
        ];
        if (count($calendar_week) === 7) {
            $calendar_weeks[] = $calendar_week;
            $calendar_week = [];
        }
    }
    ?>

    <div class="erp-omd-card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
            <div>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=erp-omd-calendar&calendar_month=' . $previous_month)); ?>">&larr; <?php esc_html_e('Poprzedni miesiąc', 'erp-omd'); ?></a>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=erp-omd-calendar&calendar_month=' . $next_month)); ?>"><?php esc_html_e('Następny miesiąc', 'erp-omd'); ?> &rarr;</a>
            </div>
            <h2 style="margin:0;"><?php echo esc_html($calendar_month); ?></h2>
            <form method="post" style="margin:0;">
                <?php wp_nonce_field('erp_omd_google_calendar_sync_now'); ?>
                <input type="hidden" name="erp_omd_action" value="google_calendar_sync_now" />
                <button type="submit" class="button button-primary"><?php esc_html_e('Synchronizuj teraz', 'erp-omd'); ?></button>
            </form>
        </div>

        <table class="widefat striped erp-omd-calendar-table" style="margin-top:16px;">
            <thead><tr><th><?php esc_html_e('Pon', 'erp-omd'); ?></th><th><?php esc_html_e('Wt', 'erp-omd'); ?></th><th><?php esc_html_e('Śr', 'erp-omd'); ?></th><th><?php esc_html_e('Czw', 'erp-omd'); ?></th><th><?php esc_html_e('Pt', 'erp-omd'); ?></th><th><?php esc_html_e('Sob', 'erp-omd'); ?></th><th><?php esc_html_e('Nd', 'erp-omd'); ?></th></tr></thead>
            <tbody>
            <?php foreach ((array) $calendar_weeks as $week) : ?>
                <tr>
                    <?php foreach ((array) $week as $day) : ?>
                        <td class="erp-omd-calendar-cell">
                            <?php $is_current_month_day = (bool) ($day['is_current_month'] ?? false); ?>
                            <div class="erp-omd-calendar-day" style="<?php echo $is_current_month_day ? '' : 'opacity:.45;'; ?>"><?php echo esc_html((string) ($day['day'] ?? '')); ?></div>
                            <?php if (! empty($day['items'])) : ?>
                                <div class="erp-omd-calendar-markers">
                                    <?php foreach ((array) $day['items'] as $day_item) : ?>
                                        <?php
                                        $marker_classes = ['erp-omd-calendar-chip'];
                                        $marker_type = (string) ($day_item['type'] ?? 'deadline');
                                        if ($marker_type === 'range') {
                                            $marker_classes[] = 'erp-omd-calendar-chip-range';
                                            if (! empty($day_item['is_range_start'])) {
                                                $marker_classes[] = 'erp-omd-calendar-chip-range-start';
                                            }
                                            if (! empty($day_item['is_range_end'])) {
                                                $marker_classes[] = 'erp-omd-calendar-chip-range-end';
                                            }
                                        } else {
                                            $marker_classes[] = 'erp-omd-calendar-chip-deadline';
                                        }
                                        ?>
                                        <a
                                            class="<?php echo esc_attr(implode(' ', $marker_classes)); ?>"
                                            href="<?php echo esc_url(admin_url('admin.php?page=erp-omd-projects&id=' . (int) ($day_item['project_id'] ?? 0))); ?>"
                                        >
                                            <?php if ($marker_type === 'deadline') : ?>
                                                <span class="erp-omd-calendar-chip-dot" aria-hidden="true"></span>
                                                <?php echo esc_html(sprintf(__('Deadline: %s', 'erp-omd'), (string) ($day_item['project_name'] ?? ''))); ?>
                                            <?php else : ?>
                                                <?php echo esc_html((string) ($day_item['project_name'] ?? '')); ?>
                                            <?php endif; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <div class="description">—</div>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <table class="widefat striped" style="margin-top:16px;">
            <thead>
                <tr>
                    <th><?php esc_html_e('Data', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Typ eventu', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Projekt', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Status projektu', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Status sync', 'erp-omd'); ?></th>
                    <th><?php esc_html_e('Błąd sync', 'erp-omd'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($events)) : ?>
                    <tr>
                        <td colspan="6"><?php esc_html_e('Brak eventów projektowych dla wybranego miesiąca.', 'erp-omd'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($events as $event) : ?>
                        <tr>
                            <td>
                                <?php
                                $date_start = (string) ($event['date_start'] ?? '');
                                $date_end = (string) ($event['date_end'] ?? '');
                                echo esc_html($date_start === $date_end ? $date_start : ($date_start . ' → ' . $date_end));
                                ?>
                            </td>
                            <td><?php echo esc_html((string) ($event['event_type'] ?? '')); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=erp-omd-projects&id=' . (int) ($event['project_id'] ?? 0))); ?>">
                                    <?php echo esc_html((string) ($event['project_name'] ?? '')); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html((string) ($event['status'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($event['sync_status'] ?? 'pending')); ?></td>
                            <td><?php echo esc_html((string) ($event['sync_error'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
