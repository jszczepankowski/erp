<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Kalendarz agencji', 'erp-omd'); ?></h1>

    <?php
    $events_by_date = [];
    foreach ((array) $events as $event) {
        $date_key = (string) ($event['date_start'] ?? '');
        if ($date_key === '') { continue; }
        $events_by_date[$date_key][] = $event;
    }
    $calendar_cursor = DateTimeImmutable::createFromFormat('Y-m-d', $calendar_month . '-01') ?: new DateTimeImmutable(current_time('Y-m') . '-01');
    $calendar_grid_start = $calendar_cursor->modify('-' . ((int) $calendar_cursor->format('N') - 1) . ' days');
    $calendar_month_end = $calendar_cursor->modify('last day of this month');
    $calendar_grid_end = $calendar_month_end->modify('+' . (7 - (int) $calendar_month_end->format('N')) . ' days');
    $calendar_weeks = [];
    $calendar_week = [];
    $calendar_period = new DatePeriod($calendar_grid_start, new DateInterval('P1D'), $calendar_grid_end->modify('+1 day'));
    foreach ($calendar_period as $calendar_day_cursor) {
        $calendar_date_key = $calendar_day_cursor->format('Y-m-d');
        $calendar_week[] = [
            'day' => $calendar_day_cursor->format('j'),
            'is_current_month' => $calendar_day_cursor->format('Y-m') === $calendar_month,
            'events' => $events_by_date[$calendar_date_key] ?? [],
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
                            <?php if (! empty($day['events'])) : ?>
                                <ul style="margin:6px 0 0 18px;">
                                    <?php foreach ((array) $day['events'] as $day_event) : ?>
                                        <li style="margin-bottom:4px;">
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=erp-omd-projects&id=' . (int) ($day_event['project_id'] ?? 0))); ?>">
                                                <?php echo esc_html((string) ($day_event['project_name'] ?? '')); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
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
