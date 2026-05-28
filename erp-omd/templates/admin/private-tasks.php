<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Lista zadań', 'erp-omd'); ?></h1>
    <div class="erp-omd-page-sections">
        <section class="erp-omd-card">
            <div class="erp-omd-section-header"><h2><?php esc_html_e('Lista zadań', 'erp-omd'); ?></h2></div>
            <form method="post" class="erp-omd-form-grid">
                <?php $is_edit_mode = ($dashboard_private_tasks_edit_id ?? '') !== ''; ?>
                <?php
                $editing_task = null;
                if ($is_edit_mode) {
                    foreach ((array) $dashboard_private_tasks as $task_candidate) {
                        if ((string) ($task_candidate['task_id'] ?? '') === (string) $dashboard_private_tasks_edit_id) { $editing_task = $task_candidate; break; }
                    }
                }
                ?>
                <?php wp_nonce_field($is_edit_mode ? 'erp_omd_update_admin_private_task' : 'erp_omd_save_admin_private_task'); ?>
                <input type="hidden" name="erp_omd_action" value="<?php echo esc_attr($is_edit_mode ? 'update_admin_private_task' : 'save_admin_private_task'); ?>">
                <?php if ($is_edit_mode && is_array($editing_task)) : ?>
                    <input type="hidden" name="task_id" value="<?php echo esc_attr((string) ($editing_task['task_id'] ?? '')); ?>" />
                <?php endif; ?>
                <input type="hidden" name="tasks_filter" value="<?php echo esc_attr((string) ($dashboard_private_tasks_filter ?? 'all')); ?>" />
                <div class="erp-omd-form-field erp-omd-task-field-main"><label for="erp-omd-admin-task-text"><?php esc_html_e('Treść zadania', 'erp-omd'); ?></label><textarea id="erp-omd-admin-task-text" name="task_text" rows="3" class="large-text" required></textarea></div>
                <div class="erp-omd-form-field erp-omd-task-field-side"><label for="erp-omd-admin-task-date"><?php esc_html_e('Termin', 'erp-omd'); ?></label><input id="erp-omd-admin-task-date" type="date" name="task_due_date" value="<?php echo esc_attr(current_time('Y-m-d')); ?>"></div>
                <script>
                (function(){var t=document.getElementById('erp-omd-admin-task-text'),d=document.getElementById('erp-omd-admin-task-date');<?php if ($is_edit_mode && is_array($editing_task)) : ?>if(t){t.value=<?php echo wp_json_encode((string) ($editing_task['text'] ?? '')); ?>;}if(d){d.value=<?php echo wp_json_encode((string) ($editing_task['due_date'] ?? '')); ?>;}<?php endif; ?>})();
                </script>
                <div class="erp-omd-form-field erp-omd-form-field-align-end erp-omd-task-field-side"><button type="submit" class="button button-primary"><?php echo $is_edit_mode ? esc_html__('Zapisz zmiany', 'erp-omd') : esc_html__('Dodaj zadanie', 'erp-omd'); ?></button></div>
            </form>

            <form method="post" id="erp-omd-bulk-private-tasks-form">
                <?php wp_nonce_field('erp_omd_bulk_admin_private_tasks'); ?>
                <input type="hidden" name="erp_omd_action" value="bulk_admin_private_tasks" />
                <input type="hidden" name="tasks_filter" value="<?php echo esc_attr((string) ($dashboard_private_tasks_filter ?? 'all')); ?>" />
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <select name="bulk_action">
                            <option value=""><?php esc_html_e('Akcje masowe', 'erp-omd'); ?></option>
                            <option value="delete"><?php esc_html_e('Usuń', 'erp-omd'); ?></option>
                            <option value="mark_done"><?php esc_html_e('Oznacz jako zrobione', 'erp-omd'); ?></option>
                            <option value="mark_todo"><?php esc_html_e('Oznacz jako niedokończone', 'erp-omd'); ?></option>
                        </select>
                        <button class="button action" type="submit"><?php esc_html_e('Zastosuj', 'erp-omd'); ?></button>
                    </div>
                    <div class="alignright actions">
                        <?php foreach (['all' => __('Wszystkie', 'erp-omd'), 'today' => __('Na dziś', 'erp-omd'), 'incomplete' => __('Niedokończone', 'erp-omd')] as $task_filter_key => $task_filter_label) : ?>
                            <?php $task_filter_url = add_query_arg(['page' => 'erp-omd-private-tasks', 'tasks_filter' => $task_filter_key], admin_url('admin.php')); ?>
                            <a class="button <?php echo ($dashboard_private_tasks_filter ?? 'all') === $task_filter_key ? 'button-primary' : ''; ?>" href="<?php echo esc_url($task_filter_url); ?>"><?php echo esc_html($task_filter_label); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>

                <table class="widefat striped">
                    <thead><tr><th scope="col" class="manage-column check-column"><input type="checkbox" class="erp-omd-select-all-task" /></th><th><?php esc_html_e('ID', 'erp-omd'); ?></th><th><?php esc_html_e('Data dodania', 'erp-omd'); ?></th><th><?php esc_html_e('Zadanie', 'erp-omd'); ?></th><th><?php esc_html_e('Termin', 'erp-omd'); ?></th><th><?php esc_html_e('Status', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje', 'erp-omd'); ?></th></tr></thead>
                    <tbody>
                    <?php if (! empty($dashboard_private_tasks)) : foreach ($dashboard_private_tasks as $i => $task_row) : $task_id = (string) ($task_row['task_id'] ?? ''); ?>
                        <tr>
                            <th scope="row" class="check-column"><input type="checkbox" name="task_ids[]" value="<?php echo esc_attr($task_id); ?>" form="erp-omd-bulk-private-tasks-form" /></th>
                            <td><?php echo esc_html((string) ($i + 1)); ?></td>
                            <td><?php echo esc_html((string) ($task_row['created_at'] ?? '—')); ?></td>
                            <td><?php echo esc_html((string) ($task_row['text'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($task_row['due_date'] ?? '—')); ?></td>
                            <td><?php echo ! empty($task_row['completed']) ? esc_html__('Zrobione', 'erp-omd') : esc_html__('Niedokończone', 'erp-omd'); ?></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-private-tasks', 'tasks_filter' => (string) ($dashboard_private_tasks_filter ?? 'all'), 'edit_task' => $task_id], admin_url('admin.php'))); ?>"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                <form method="post" class="erp-omd-inline-form">
                                    <?php wp_nonce_field('erp_omd_toggle_admin_private_task'); ?>
                                    <input type="hidden" name="erp_omd_action" value="toggle_admin_private_task" />
                                    <input type="hidden" name="task_id" value="<?php echo esc_attr($task_id); ?>" />
                                    <input type="hidden" name="tasks_filter" value="<?php echo esc_attr((string) ($dashboard_private_tasks_filter ?? 'all')); ?>" />
                                    <button type="submit" class="button button-small"><?php esc_html_e('Zmień status', 'erp-omd'); ?></button>
                                </form>
                                <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Usunąć zadanie?', 'erp-omd')); ?>');">
                                    <?php wp_nonce_field('erp_omd_delete_admin_private_task'); ?>
                                    <input type="hidden" name="erp_omd_action" value="delete_admin_private_task" />
                                    <input type="hidden" name="task_id" value="<?php echo esc_attr($task_id); ?>" />
                                    <input type="hidden" name="tasks_filter" value="<?php echo esc_attr((string) ($dashboard_private_tasks_filter ?? 'all')); ?>" />
                                    <button type="submit" class="button button-small button-link-delete"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="7"><?php esc_html_e('Brak zadań dla wybranego filtra.', 'erp-omd'); ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
        </section>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.erp-omd-select-all-task').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            document.querySelectorAll('input[name="task_ids[]"][form="erp-omd-bulk-private-tasks-form"]').forEach(function (checkbox) {
                checkbox.checked = !!toggle.checked;
            });
        });
    });
});
</script>
