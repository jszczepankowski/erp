<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Kalendarz agencji', 'erp-omd'); ?></h1>

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
