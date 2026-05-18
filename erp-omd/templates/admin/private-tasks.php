<section class="erp-omd-card">
    <div class="erp-omd-section-header">
        <h2><?php esc_html_e('Taski prywatne', 'erp-omd'); ?></h2>
    </div>

    <form method="post" class="erp-omd-form-grid">
        <?php wp_nonce_field('erp_omd_save_admin_private_task'); ?>
        <input type="hidden" name="erp_omd_action" value="save_admin_private_task">
        <div class="erp-omd-form-field erp-omd-form-field-span-2">
            <label for="erp-omd-admin-task-text"><?php esc_html_e('Treść taska', 'erp-omd'); ?></label>
            <textarea id="erp-omd-admin-task-text" name="task_text" rows="3" class="large-text" required></textarea>
        </div>
        <div class="erp-omd-form-field">
            <label for="erp-omd-admin-task-date"><?php esc_html_e('Termin', 'erp-omd'); ?></label>
            <input id="erp-omd-admin-task-date" type="date" name="task_due_date" value="<?php echo esc_attr(current_time('Y-m-d')); ?>">
        </div>
        <div class="erp-omd-form-field erp-omd-form-field-align-end">
            <button type="submit" class="button button-primary"><?php esc_html_e('Dodaj task', 'erp-omd'); ?></button>
        </div>
    </form>

    <div class="tablenav top">
        <div class="alignleft actions">
            <?php foreach (['all' => __('Wszystkie', 'erp-omd'), 'today' => __('Na dziś', 'erp-omd'), 'incomplete' => __('Niedokończone', 'erp-omd')] as $task_filter_key => $task_filter_label) : ?>
                <?php $task_filter_url = add_query_arg(['page' => 'erp-omd-private-tasks', 'tasks_filter' => $task_filter_key], admin_url('admin.php')); ?>
                <a class="button <?php echo ($dashboard_private_tasks_filter ?? 'all') === $task_filter_key ? 'button-primary' : ''; ?>" href="<?php echo esc_url($task_filter_url); ?>"><?php echo esc_html($task_filter_label); ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Data dodania', 'erp-omd'); ?></th>
                <th><?php esc_html_e('Termin', 'erp-omd'); ?></th>
                <th><?php esc_html_e('Task', 'erp-omd'); ?></th>
                <th><?php esc_html_e('Status', 'erp-omd'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (! empty($dashboard_private_tasks)) : foreach ($dashboard_private_tasks as $task_row) : ?>
                <tr>
                    <td><?php echo esc_html((string) ($task_row['created_at'] ?? '—')); ?></td>
                    <td><?php echo esc_html((string) ($task_row['due_date'] ?? '—')); ?></td>
                    <td><?php echo esc_html((string) ($task_row['text'] ?? '')); ?></td>
                    <td>
                        <form method="post" class="erp-omd-inline-form">
                            <?php wp_nonce_field('erp_omd_toggle_admin_private_task'); ?>
                            <input type="hidden" name="erp_omd_action" value="toggle_admin_private_task">
                            <input type="hidden" name="task_created_at" value="<?php echo esc_attr((string) ($task_row['created_at'] ?? '')); ?>">
                            <button type="submit" class="button button-secondary button-small"><?php echo ! empty($task_row['completed']) ? esc_html__('Zrobione', 'erp-omd') : esc_html__('Niedokończone', 'erp-omd'); ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; else : ?>
                <tr><td colspan="4"><?php esc_html_e('Brak tasków dla wybranego filtra.', 'erp-omd'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>
