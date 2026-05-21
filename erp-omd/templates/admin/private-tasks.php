<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Lista zadań', 'erp-omd'); ?></h1>
    <div class="erp-omd-page-sections">
        <section class="erp-omd-card">
            <div class="erp-omd-section-header"><h2><?php esc_html_e('Lista zadań', 'erp-omd'); ?></h2></div>
            <div id="erp-omd-private-task-notice" style="display:none;margin:10px 0;"></div>
            <form method="post" class="erp-omd-form-grid" id="erp-omd-private-task-editor">
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
                <input type="hidden" name="task_id" id="erp-omd-admin-task-id" value="<?php echo esc_attr($is_edit_mode && is_array($editing_task) ? (string) ($editing_task['task_id'] ?? '') : ''); ?>" />
                <input type="hidden" name="tasks_filter" value="<?php echo esc_attr((string) ($dashboard_private_tasks_filter ?? 'all')); ?>" />
                <div class="erp-omd-form-field erp-omd-task-field-main"><label for="erp-omd-admin-task-text"><?php esc_html_e('Treść zadania', 'erp-omd'); ?></label><textarea id="erp-omd-admin-task-text" name="task_text" rows="3" class="large-text" required></textarea></div>
                <div class="erp-omd-form-field erp-omd-task-field-side"><label for="erp-omd-admin-task-date"><?php esc_html_e('Termin', 'erp-omd'); ?></label><input id="erp-omd-admin-task-date" type="date" name="task_due_date" value="<?php echo esc_attr(current_time('Y-m-d')); ?>"></div>
                <script>
                (function(){var t=document.getElementById('erp-omd-admin-task-text'),d=document.getElementById('erp-omd-admin-task-date');<?php if ($is_edit_mode && is_array($editing_task)) : ?>if(t){t.value=<?php echo wp_json_encode((string) ($editing_task['text'] ?? '')); ?>;}if(d){d.value=<?php echo wp_json_encode((string) ($editing_task['due_date'] ?? '')); ?>;}<?php endif; ?>})();
                </script>
                <div class="erp-omd-form-field erp-omd-form-field-align-end erp-omd-task-field-side">
                    <button type="submit" class="button button-primary" id="erp-omd-task-submit"><?php echo $is_edit_mode ? esc_html__('Zapisz zmiany', 'erp-omd') : esc_html__('Dodaj zadanie', 'erp-omd'); ?></button>
                    <button type="button" class="button" id="erp-omd-task-cancel-edit" style="<?php echo $is_edit_mode ? '' : 'display:none;'; ?>"><?php esc_html_e('Anuluj edycję', 'erp-omd'); ?></button>
                </div>
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

                <table class="widefat striped">
                    <thead><tr><th scope="col" class="manage-column check-column"><input type="checkbox" class="erp-omd-select-all-task" /></th><th><?php esc_html_e('ID', 'erp-omd'); ?></th><th><?php esc_html_e('Data dodania', 'erp-omd'); ?></th><th><?php esc_html_e('Zadanie', 'erp-omd'); ?></th><th><?php esc_html_e('Termin', 'erp-omd'); ?></th><th><?php esc_html_e('Status', 'erp-omd'); ?></th><th><?php esc_html_e('Akcje', 'erp-omd'); ?></th></tr></thead>
                    <tbody>
                    <?php if (! empty($dashboard_private_tasks)) : foreach ($dashboard_private_tasks as $i => $task_row) : $task_id = (string) ($task_row['task_id'] ?? ''); ?>
                        <tr data-task-id="<?php echo esc_attr($task_id); ?>" data-task-text="<?php echo esc_attr((string) ($task_row['text'] ?? '')); ?>" data-task-due-date="<?php echo esc_attr((string) ($task_row['due_date'] ?? '')); ?>" data-task-completed="<?php echo ! empty($task_row['completed']) ? '1' : '0'; ?>">
                            <th scope="row" class="check-column"><input type="checkbox" name="task_ids[]" value="<?php echo esc_attr($task_id); ?>" /></th>
                            <td><?php echo esc_html((string) ($i + 1)); ?></td>
                            <td><?php echo esc_html((string) ($task_row['created_at'] ?? '—')); ?></td>
                            <td><?php echo esc_html((string) ($task_row['text'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($task_row['due_date'] ?? '—')); ?></td>
                            <td><?php echo ! empty($task_row['completed']) ? esc_html__('Zrobione', 'erp-omd') : esc_html__('Niedokończone', 'erp-omd'); ?></td>
                            <td>
                                <button type="button" class="button button-small erp-omd-task-edit-btn"><?php esc_html_e('Edytuj', 'erp-omd'); ?></button>
                                <form method="post" class="erp-omd-inline-form">
                                    <?php wp_nonce_field('erp_omd_toggle_admin_private_task'); ?>
                                    <input type="hidden" name="erp_omd_action" value="toggle_admin_private_task" />
                                    <input type="hidden" name="task_id" value="<?php echo esc_attr($task_id); ?>" />
                                    <input type="hidden" name="tasks_filter" value="<?php echo esc_attr((string) ($dashboard_private_tasks_filter ?? 'all')); ?>" />
                                    <button type="submit" class="button button-small erp-omd-task-toggle-btn" data-task-id="<?php echo esc_attr($task_id); ?>"><?php esc_html_e('Zmień status', 'erp-omd'); ?></button>
                                </form>
                                <form method="post" class="erp-omd-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Usunąć zadanie?', 'erp-omd')); ?>');">
                                    <?php wp_nonce_field('erp_omd_delete_admin_private_task'); ?>
                                    <input type="hidden" name="erp_omd_action" value="delete_admin_private_task" />
                                    <input type="hidden" name="task_id" value="<?php echo esc_attr($task_id); ?>" />
                                    <input type="hidden" name="tasks_filter" value="<?php echo esc_attr((string) ($dashboard_private_tasks_filter ?? 'all')); ?>" />
                                    <button type="submit" class="button button-small button-link-delete erp-omd-task-delete-btn" data-task-id="<?php echo esc_attr($task_id); ?>"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="7"><?php esc_html_e('Brak zadań dla wybranego filtra.', 'erp-omd'); ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </section>
    </div>
</div>
<script>
(function () {
    const apiRoot = <?php echo wp_json_encode(esc_url_raw(rest_url('erp-omd/v1/private-tasks'))); ?>;
    const headers = Object.assign({'Content-Type': 'application/json'}, window.erpOmdAsync ? window.erpOmdAsync.defaultAsyncHeaders() : {});
    const restNonce = <?php echo wp_json_encode(wp_create_nonce('wp_rest')); ?>;
    if (!headers['X-WP-Nonce']) {
        headers['X-WP-Nonce'] = (window.wpApiSettings && window.wpApiSettings.nonce) ? window.wpApiSettings.nonce : restNonce;
    }
    const cardEl = document.querySelector('.erp-omd-card');
    if (!cardEl) return;
    const getEls = () => ({
        textEl: document.getElementById('erp-omd-admin-task-text'),
        dateEl: document.getElementById('erp-omd-admin-task-date'),
        idEl: document.getElementById('erp-omd-admin-task-id'),
        submitEl: document.getElementById('erp-omd-task-submit'),
        cancelEl: document.getElementById('erp-omd-task-cancel-edit'),
        noticeEl: document.getElementById('erp-omd-private-task-notice')
    });
    const showNotice = (ok, msg) => {
        const {noticeEl} = getEls();
        if (!noticeEl) return;
        noticeEl.style.display = 'block';
        noticeEl.className = ok ? 'notice notice-success' : 'notice notice-error';
        noticeEl.innerHTML = '<p>' + (msg || '') + '</p>';
    };
    const parseResponse = async (response, fallback) => {
        if (window.erpOmdAsync && window.erpOmdAsync.parseAsyncResponse) return window.erpOmdAsync.parseAsyncResponse(response, fallback);
        try {
            const payload = await response.json();
            return payload;
        } catch (e) {
            return {ok: false, message: fallback || 'Błąd odpowiedzi serwera.'};
        }
    };
    const bindEditButtons = () => {
        document.querySelectorAll('.erp-omd-task-edit-btn').forEach((btn) => {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', () => {
                const row = btn.closest('tr');
                if (!row) return;
                const {idEl, textEl, dateEl, submitEl, cancelEl} = getEls();
                if (!idEl || !textEl || !dateEl || !submitEl || !cancelEl) return;
                idEl.value = row.getAttribute('data-task-id') || '';
                textEl.value = row.getAttribute('data-task-text') || '';
                dateEl.value = row.getAttribute('data-task-due-date') || '';
                submitEl.textContent = 'Zapisz zmiany';
                cancelEl.style.display = 'inline-block';
                textEl.focus();
            });
        });
    };
    const refreshCard = async () => {
        if (!cardEl) return;
        const response = await fetch(window.location.href, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const nextCard = doc.querySelector('.erp-omd-card');
        if (nextCard) {
            cardEl.innerHTML = nextCard.innerHTML;
            bindEditButtons();
        }
    };
    const resetEditor = () => {
        const {idEl, submitEl, cancelEl} = getEls();
        if (!idEl || !submitEl || !cancelEl) return;
        idEl.value = '';
        submitEl.textContent = 'Dodaj zadanie';
        cancelEl.style.display = 'none';
    };
    const {cancelEl} = getEls();
    if (cancelEl) {
        cancelEl.addEventListener('click', resetEditor);
    }
    cardEl.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!form || form.id !== 'erp-omd-private-task-editor') return;
        event.preventDefault();
        const {textEl, dateEl, idEl} = getEls();
        if (!textEl || !dateEl || !idEl) return;
        const payload = {task_text: textEl.value || '', task_due_date: dateEl.value || ''};
        const taskId = idEl.value || '';
        const url = taskId ? apiRoot + '/' + encodeURIComponent(taskId) : apiRoot;
        const method = taskId ? 'PUT' : 'POST';
        const res = await fetch(url, {method, headers, credentials: 'same-origin', body: JSON.stringify(payload)});
        const parsed = await parseResponse(res, 'Błąd zapisu zadania.');
        showNotice(!!parsed.ok, parsed.message || '');
        if (!parsed.ok) return;
        await refreshCard();
        resetEditor();
    });
    cardEl.addEventListener('click', async (event) => {
        const toggleBtn = event.target.closest('.erp-omd-task-toggle-btn');
        if (toggleBtn) {
            event.preventDefault();
            const row = toggleBtn.closest('tr');
            const taskId = toggleBtn.getAttribute('data-task-id') || '';
            const currentCompleted = row ? (row.getAttribute('data-task-completed') === '1') : false;
            const payload = {
                task_text: row ? (row.getAttribute('data-task-text') || '') : '',
                task_due_date: row ? (row.getAttribute('data-task-due-date') || '') : '',
                completed: currentCompleted ? 0 : 1
            };
            const res = await fetch(apiRoot + '/' + encodeURIComponent(taskId), {method: 'PUT', headers, credentials: 'same-origin', body: JSON.stringify(payload)});
            const parsed = await parseResponse(res, 'Błąd zmiany statusu zadania.');
            showNotice(!!parsed.ok, parsed.message || '');
            if (parsed.ok) await refreshCard();
            return;
        }
        const deleteBtn = event.target.closest('.erp-omd-task-delete-btn');
        if (deleteBtn) {
            event.preventDefault();
            if (!window.confirm('Usunąć zadanie?')) return;
            const taskId = deleteBtn.getAttribute('data-task-id') || '';
            const res = await fetch(apiRoot + '/' + encodeURIComponent(taskId), {method: 'DELETE', headers, credentials: 'same-origin'});
            const parsed = await parseResponse(res, 'Błąd usuwania zadania.');
            showNotice(!!parsed.ok, parsed.message || '');
            if (parsed.ok) await refreshCard();
        }
    });
    bindEditButtons();
})();
</script>
