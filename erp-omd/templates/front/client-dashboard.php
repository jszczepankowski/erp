<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($dashboard_title); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url(ERP_OMD_URL . 'assets/css/front.css?ver=' . ERP_OMD_VERSION); ?>">
    <?php wp_head(); ?>
</head>
<body class="erp-omd-front-body">
    <main class="erp-omd-front-shell erp-omd-front-shell-dashboard">
        <section class="erp-omd-front-card erp-omd-front-card-wide">
            <div class="erp-omd-front-topbar">
                <div>
                    <span class="erp-omd-front-eyebrow"><?php echo esc_html($front_brand_label); ?></span>
                    <h1><?php echo esc_html($dashboard_title); ?></h1>
                </div>
                <div class="erp-omd-front-actions">
                    <a class="erp-omd-front-button" href="<?php echo esc_url($front_client_url); ?>"><?php esc_html_e('Odśwież panel', 'erp-omd'); ?></a>
                    <a class="erp-omd-front-button erp-omd-front-button-secondary" href="<?php echo esc_url($front_logout_url); ?>"><?php esc_html_e('Wyloguj', 'erp-omd'); ?></a>
                </div>
            </div>
            <?php if (! empty($client_notice_type) && ! empty($client_notice_message)) : ?>
                <div class="erp-omd-front-notice erp-omd-front-notice-<?php echo esc_attr($client_notice_type); ?>">
                    <?php echo esc_html($client_notice_message); ?>
                </div>
            <?php endif; ?>

            <div class="erp-omd-front-grid erp-omd-front-grid-summary">
                <article class="erp-omd-front-panel">
                    <h2><?php esc_html_e('Twoje konto', 'erp-omd'); ?></h2>
                    <ul>
                        <li><strong><?php esc_html_e('Użytkownik:', 'erp-omd'); ?></strong> <?php echo esc_html($user->user_login); ?></li>
                        <li><strong><?php esc_html_e('Firma:', 'erp-omd'); ?></strong> <?php echo esc_html($client_profile['company'] ?? '—'); ?></li>
                        <li><strong><?php esc_html_e('Adres:', 'erp-omd'); ?></strong> <?php echo esc_html(trim((string) ($client_profile['street'] ?? '') . ' ' . (string) ($client_profile['apartment_number'] ?? '') . ', ' . (string) ($client_profile['postal_code'] ?? '') . ' ' . (string) ($client_profile['city'] ?? '') . ', ' . (string) ($client_profile['country'] ?? '')) ?: '—'); ?></li>
                        <li><strong><?php esc_html_e('Status:', 'erp-omd'); ?></strong> <?php echo esc_html($client_profile['status'] ?? '—'); ?></li>
                        <li><strong><?php esc_html_e('Email:', 'erp-omd'); ?></strong> <?php echo esc_html($client_profile['email'] ?? ($user->user_email ?? '—')); ?></li>
                        <li><strong><?php esc_html_e('Telefon:', 'erp-omd'); ?></strong> <?php echo esc_html($client_profile['phone'] ?? '—'); ?></li>
                        <li><strong><?php esc_html_e('Kontakt główny:', 'erp-omd'); ?></strong> <?php echo esc_html($client_profile['contact_person_name'] ?? '—'); ?></li>
                    </ul>
                </article>
                <article class="erp-omd-front-panel">
                    <h2><?php esc_html_e('Szybkie podsumowanie', 'erp-omd'); ?></h2>
                    <div class="erp-omd-front-metrics">
                        <div class="erp-omd-front-metric">
                            <span class="erp-omd-front-metric-label"><?php esc_html_e('Projekty', 'erp-omd'); ?></span>
                            <strong><?php echo esc_html((string) count($projects)); ?></strong>
                        </div>
                    </div>
                </article>
                <article class="erp-omd-front-panel">
                    <h2><?php esc_html_e('Stawki klienta', 'erp-omd'); ?></h2>
                    <div class="erp-omd-front-table-wrap">
                        <table class="erp-omd-front-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Rola', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Stawka', 'erp-omd'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($client_rates) : ?>
                                    <?php foreach ($client_rates as $client_rate_item) : ?>
                                        <tr>
                                            <td><?php echo esc_html((string) ($client_rate_item['role_name'] ?? '—')); ?></td>
                                            <td><?php echo esc_html(number_format_i18n((float) ($client_rate_item['rate'] ?? 0), 2)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr><td colspan="2"><?php esc_html_e('Brak zdefiniowanych stawek klienta.', 'erp-omd'); ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>

            <article class="erp-omd-front-panel">
                <div class="erp-omd-front-section-heading">
                    <h2><?php esc_html_e('Nowy wniosek projektowy', 'erp-omd'); ?></h2>
                </div>
                <form method="post" class="erp-omd-front-form erp-omd-front-form-inline">
                    <?php wp_nonce_field('erp_omd_front_client'); ?>
                    <input type="hidden" name="erp_omd_front_action" value="create_project_request" />
                    <div class="erp-omd-front-grid erp-omd-front-grid-two">
                        <div class="erp-omd-front-field">
                            <label for="erp-omd-client-request-project-name"><?php esc_html_e('Nowy wniosek projektowy: nazwa projektu', 'erp-omd'); ?></label>
                            <input id="erp-omd-client-request-project-name" type="text" name="project_name" required />
                        </div>
                        <div class="erp-omd-front-field">
                            <label for="erp-omd-client-request-billing-type"><?php esc_html_e('Typ projektu', 'erp-omd'); ?></label>
                            <select id="erp-omd-client-request-billing-type" name="billing_type">
                                <?php foreach (['time_material', 'fixed_price', 'retainer', 'mixed'] as $billing_type) : ?>
                                    <option value="<?php echo esc_attr($billing_type); ?>">
                                        <?php echo esc_html($project_billing_type_labels[$billing_type] ?? $billing_type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="erp-omd-front-field">
                            <label for="erp-omd-client-request-manager"><?php esc_html_e('Preferowany manager', 'erp-omd'); ?></label>
                            <select id="erp-omd-client-request-manager" name="preferred_manager_id">
                                <option value="0"><?php esc_html_e('Bez preferencji', 'erp-omd'); ?></option>
                                <?php foreach ((array) $client_request_available_managers as $manager_item) : ?>
                                    <option value="<?php echo esc_attr((string) ($manager_item['id'] ?? 0)); ?>">
                                        <?php echo esc_html((string) ($manager_item['user_login'] ?? ('#' . (int) ($manager_item['id'] ?? 0)))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="erp-omd-front-field">
                            <label for="erp-omd-client-request-start-date"><?php esc_html_e('Data rozpoczęcia', 'erp-omd'); ?></label>
                            <input id="erp-omd-client-request-start-date" type="date" name="start_date" />
                        </div>
                        <div class="erp-omd-front-field">
                            <label for="erp-omd-client-request-end-date"><?php esc_html_e('Data zakończenia', 'erp-omd'); ?></label>
                            <input id="erp-omd-client-request-end-date" type="date" name="end_date" />
                        </div>
                        <div class="erp-omd-front-field">
                            <label for="erp-omd-client-request-deadline"><?php esc_html_e('Deadline', 'erp-omd'); ?></label>
                            <input id="erp-omd-client-request-deadline" type="date" name="deadline" />
                        </div>
                        <div class="erp-omd-front-field">
                            <label for="erp-omd-client-request-brief"><?php esc_html_e('Brief / opis projektu', 'erp-omd'); ?></label>
                            <textarea id="erp-omd-client-request-brief" name="brief" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="erp-omd-front-actions">
                        <button type="submit" class="erp-omd-front-button erp-omd-front-button-primary"><?php esc_html_e('Wyślij wniosek projektowy', 'erp-omd'); ?></button>
                    </div>
                </form>
            </article>

            <article class="erp-omd-front-panel">
                <div class="erp-omd-front-section-heading">
                    <h2><?php esc_html_e('Projekty klienta (status + deadline)', 'erp-omd'); ?></h2>
                </div>
                <div class="erp-omd-front-inline-actions">
                    <?php
                    $scope_base_args = [
                        'sort_by' => $project_sort_by,
                        'sort_order' => $project_sort_order,
                    ];
                    if (! empty($history_month_filter)) {
                        $scope_base_args['history_month'] = $history_month_filter;
                    }
                    ?>
                    <a
                        class="erp-omd-front-button <?php echo $project_scope === 'current' ? 'erp-omd-front-button-primary' : 'erp-omd-front-button-ghost'; ?>"
                        href="<?php echo esc_url(add_query_arg(array_merge($scope_base_args, ['project_scope' => 'current']), $front_client_url)); ?>"
                    >
                        <?php esc_html_e('Bieżące', 'erp-omd'); ?>
                    </a>
                    <a
                        class="erp-omd-front-button <?php echo $project_scope === 'archive' ? 'erp-omd-front-button-primary' : 'erp-omd-front-button-ghost'; ?>"
                        href="<?php echo esc_url(add_query_arg(array_merge($scope_base_args, ['project_scope' => 'archive']), $front_client_url)); ?>"
                    >
                        <?php esc_html_e('Archiwum', 'erp-omd'); ?>
                    </a>
                </div>
                <?php if (! empty($history_month_filter)) : ?>
                    <div class="erp-omd-front-inline-actions">
                        <span class="erp-omd-front-eyebrow">
                            <?php
                            echo esc_html(
                                sprintf(
                                    /* translators: %s: month filter */
                                    __('Aktywny filtr miesiąca: %s', 'erp-omd'),
                                    $history_month_filter
                                )
                            );
                            ?>
                        </span>
                        <?php
                        $history_clear_args = ['project_scope' => $project_scope, 'sort_by' => $project_sort_by, 'sort_order' => $project_sort_order];
                        ?>
                        <a class="erp-omd-front-button erp-omd-front-button-ghost" href="<?php echo esc_url(add_query_arg($history_clear_args, $front_client_url)); ?>">
                            <?php esc_html_e('Wyczyść filtr miesiąca', 'erp-omd'); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($client_id <= 0) : ?>
                    <div class="erp-omd-front-notice erp-omd-front-notice-warning">
                        <?php esc_html_e('Brak przypisanego `erp_omd_client_id` dla tego użytkownika. Skontaktuj się z administratorem.', 'erp-omd'); ?>
                    </div>
                <?php endif; ?>

                <div class="erp-omd-front-table-wrap">
                    <?php
                    $sort_base_args = [];
                    $sort_base_args['project_scope'] = $project_scope;
                    if (! empty($history_month_filter)) {
                        $sort_base_args['history_month'] = $history_month_filter;
                    }
                    if (! empty($_GET['project_id'])) {
                        $sort_base_args['project_id'] = (int) $_GET['project_id'];
                    }
                    $render_sort_label = static function ($column_key, $label) use ($project_sort_by, $project_sort_order) {
                        if ($project_sort_by !== $column_key) {
                            return $label;
                        }

                        return $label . ' ' . ($project_sort_order === 'asc' ? '↑' : '↓');
                    };
                    $render_sort_url = static function ($column_key) use ($project_sort_by, $project_sort_order, $sort_base_args, $front_client_url) {
                        $next_order = ($project_sort_by === $column_key && $project_sort_order === 'asc') ? 'desc' : 'asc';

                        return add_query_arg(array_merge($sort_base_args, ['sort_by' => $column_key, 'sort_order' => $next_order]), $front_client_url);
                    };
                    ?>
                    <table class="erp-omd-front-table">
                        <thead>
                            <tr>
                                <th><a href="<?php echo esc_url($render_sort_url('name')); ?>"><?php echo esc_html($render_sort_label('name', __('Projekt', 'erp-omd'))); ?></a></th>
                                <th><a href="<?php echo esc_url($render_sort_url('status')); ?>"><?php echo esc_html($render_sort_label('status', __('Status', 'erp-omd'))); ?></a></th>
                                <th><a href="<?php echo esc_url($render_sort_url('budget')); ?>"><?php echo esc_html($render_sort_label('budget', __('Budżet projektu', 'erp-omd'))); ?></a></th>
                                <th><a href="<?php echo esc_url($render_sort_url('start_date')); ?>"><?php echo esc_html($render_sort_label('start_date', __('Data rozpoczęcia', 'erp-omd'))); ?></a></th>
                                <th><a href="<?php echo esc_url($render_sort_url('end_date')); ?>"><?php echo esc_html($render_sort_label('end_date', __('Data zakończenia', 'erp-omd'))); ?></a></th>
                                <th><a href="<?php echo esc_url($render_sort_url('billing_type')); ?>"><?php echo esc_html($render_sort_label('billing_type', __('Typ projektu', 'erp-omd'))); ?></a></th>
                                <th><a href="<?php echo esc_url($render_sort_url('deadline')); ?>"><?php echo esc_html($render_sort_label('deadline', __('Deadline', 'erp-omd'))); ?></a></th>
                                <th><?php esc_html_e('Szczegóły', 'erp-omd'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($projects) : ?>
                                <?php foreach ($projects as $project_item) : ?>
                                    <tr>
                                        <td><?php echo esc_html($project_item['name'] ?? '—'); ?></td>
                                        <td>
                                            <?php
                                            $project_status = (string) ($project_item['status'] ?? '');
                                            echo esc_html($project_status_labels[$project_status] ?? ($project_status !== '' ? $project_status : '—'));
                                            ?>
                                        </td>
                                        <td><?php echo esc_html(number_format_i18n((float) ($project_item['budget'] ?? 0), 2)); ?></td>
                                        <td><?php echo esc_html((string) ($project_item['start_date'] ?? '—')); ?></td>
                                        <td><?php echo esc_html((string) ($project_item['end_date'] ?? '—')); ?></td>
                                        <td>
                                            <?php
                                            $billing_type = (string) ($project_item['billing_type'] ?? '');
                                            echo esc_html($project_billing_type_labels[$billing_type] ?? ($billing_type !== '' ? $billing_type : '—'));
                                            ?>
                                        </td>
                                        <td><?php echo esc_html($project_item['deadline'] ?? '—'); ?></td>
                                        <td>
                                            <a
                                                class="erp-omd-front-button erp-omd-front-button-small"
                                                href="<?php echo esc_url(add_query_arg(['project_id' => (int) ($project_item['id'] ?? 0)], $front_client_url)); ?>"
                                            >
                                                <?php esc_html_e('Otwórz', 'erp-omd'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="8"><?php esc_html_e('Brak projektów do wyświetlenia.', 'erp-omd'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="erp-omd-front-panel">
                <div class="erp-omd-front-section-heading">
                    <h2><?php esc_html_e('Historia zleceń (miesięcznie)', 'erp-omd'); ?></h2>
                </div>
                <div class="erp-omd-front-table-wrap">
                    <table class="erp-omd-front-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Miesiąc', 'erp-omd'); ?></th>
                                <th><?php esc_html_e('Liczba projektów', 'erp-omd'); ?></th>
                                <th><?php esc_html_e('Suma budżetów', 'erp-omd'); ?></th>
                                <th><?php esc_html_e('Statusy', 'erp-omd'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($monthly_order_history)) : ?>
                                <?php foreach ($monthly_order_history as $history_row) : ?>
                                    <tr>
                                        <td>
                                            <?php $history_month_value = (string) ($history_row['month'] ?? ''); ?>
                                            <?php if (preg_match('/^\d{4}-\d{2}$/', $history_month_value)) : ?>
                                                <?php
                                                $history_link_args = ['project_scope' => $project_scope, 'sort_by' => $project_sort_by, 'sort_order' => $project_sort_order, 'history_month' => $history_month_value];
                                                ?>
                                                <a href="<?php echo esc_url(add_query_arg($history_link_args, $front_client_url)); ?>">
                                                    <?php echo esc_html($history_month_value); ?>
                                                </a>
                                            <?php else : ?>
                                                <?php echo esc_html($history_month_value !== '' ? $history_month_value : '—'); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html((string) ((int) ($history_row['projects_count'] ?? 0))); ?></td>
                                        <td><?php echo esc_html(number_format_i18n((float) ($history_row['budget_total'] ?? 0), 2)); ?></td>
                                        <td><?php echo esc_html((string) ($history_row['status_summary'] ?? '—')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr><td colspan="4"><?php esc_html_e('Brak historii zleceń dla klienta.', 'erp-omd'); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>

            <?php if ($selected_project_finance) : ?>
                <article class="erp-omd-front-panel">
                    <div class="erp-omd-front-section-heading">
                        <h2>
                            <?php
                            echo esc_html(
                                sprintf(
                                    /* translators: %s: project name */
                                    __('Finanse projektu: %s', 'erp-omd'),
                                    (string) ($selected_project_finance['project_name'] ?? '—')
                                )
                            );
                            ?>
                        </h2>
                    </div>

                    <div class="erp-omd-front-metrics">
                        <div class="erp-omd-front-metric">
                            <span class="erp-omd-front-metric-label"><?php esc_html_e('Budżet planowany', 'erp-omd'); ?></span>
                            <strong><?php echo esc_html(number_format_i18n((float) ($selected_project_finance['planned_budget'] ?? 0), 2)); ?></strong>
                        </div>
                        <div class="erp-omd-front-metric">
                            <span class="erp-omd-front-metric-label"><?php esc_html_e('Zwiększenia budżetu', 'erp-omd'); ?></span>
                            <strong><?php echo esc_html(number_format_i18n((float) ($selected_project_finance['budget_increases_total'] ?? 0), 2)); ?></strong>
                        </div>
                        <div class="erp-omd-front-metric">
                            <span class="erp-omd-front-metric-label"><?php esc_html_e('Budżet aktualny', 'erp-omd'); ?></span>
                            <strong><?php echo esc_html(number_format_i18n((float) ($selected_project_finance['budget_current'] ?? 0), 2)); ?></strong>
                        </div>
                    </div>
                </article>

                <div class="erp-omd-front-grid erp-omd-front-grid-manager">
                    <article class="erp-omd-front-panel">
                        <h2><?php esc_html_e('Pozycje przychodowe (zwiększenia)', 'erp-omd'); ?></h2>
                        <div class="erp-omd-front-table-wrap">
                            <table class="erp-omd-front-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Data', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Opis', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Kwota', 'erp-omd'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $budget_increases = (array) ($selected_project_finance['budget_increases'] ?? []); ?>
                                    <?php if ($budget_increases) : ?>
                                        <?php foreach ($budget_increases as $increase_item) : ?>
                                            <tr>
                                                <td><?php echo esc_html((string) ($increase_item['date'] ?? '—')); ?></td>
                                                <td><?php echo esc_html((string) ($increase_item['label'] ?? '—')); ?></td>
                                                <td><?php echo esc_html(number_format_i18n((float) ($increase_item['amount'] ?? 0), 2)); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr><td colspan="3"><?php esc_html_e('Brak pozycji przychodowych.', 'erp-omd'); ?></td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <article class="erp-omd-front-panel">
                        <h2><?php esc_html_e('Zaraportowany czas pracy', 'erp-omd'); ?></h2>
                        <div class="erp-omd-front-metrics">
                            <div class="erp-omd-front-metric">
                                <span class="erp-omd-front-metric-label"><?php esc_html_e('Godziny', 'erp-omd'); ?></span>
                                <strong><?php echo esc_html(number_format_i18n((float) ($selected_project_reported_hours ?? 0), 2)); ?></strong>
                            </div>
                            <div class="erp-omd-front-metric">
                                <span class="erp-omd-front-metric-label"><?php esc_html_e('Liczba wpisów', 'erp-omd'); ?></span>
                                <strong><?php echo esc_html((string) ((int) ($selected_project_reported_entries ?? 0))); ?></strong>
                            </div>
                        </div>
                        <div class="erp-omd-front-table-wrap">
                            <table class="erp-omd-front-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Data', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Godziny', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Rola', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Opis', 'erp-omd'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (! empty($selected_project_reported_items)) : ?>
                                        <?php foreach ($selected_project_reported_items as $reported_item) : ?>
                                            <tr>
                                                <td><?php echo esc_html((string) ($reported_item['entry_date'] ?? '—')); ?></td>
                                                <td><?php echo esc_html(number_format_i18n((float) ($reported_item['hours'] ?? 0), 2)); ?></td>
                                                <td><?php echo esc_html((string) ($reported_item['role_name'] ?? '—')); ?></td>
                                                <td><?php echo esc_html((string) ($reported_item['description'] ?? '')); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr><td colspan="4"><?php esc_html_e('Brak zaraportowanych wpisów czasu pracy.', 'erp-omd'); ?></td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </article>
                </div>

                <article class="erp-omd-front-panel">
                    <h2><?php esc_html_e('Historia zmian budżetu', 'erp-omd'); ?></h2>
                    <div class="erp-omd-front-table-wrap">
                        <table class="erp-omd-front-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Data', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Typ', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Opis', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Kwota', 'erp-omd'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $budget_history = (array) ($selected_project_finance['budget_history'] ?? []); ?>
                                <?php if ($budget_history) : ?>
                                    <?php foreach ($budget_history as $history_item) : ?>
                                        <tr>
                                            <td><?php echo esc_html((string) ($history_item['date'] ?? '—')); ?></td>
                                            <td><?php echo esc_html((string) ($history_item['type'] ?? '—')); ?></td>
                                            <td><?php echo esc_html((string) ($history_item['label'] ?? '—')); ?></td>
                                            <td><?php echo esc_html(number_format_i18n((float) ($history_item['amount'] ?? 0), 2)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr><td colspan="4"><?php esc_html_e('Brak historii zmian budżetu.', 'erp-omd'); ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="erp-omd-front-panel">
                    <h2><?php esc_html_e('Załączniki projektu', 'erp-omd'); ?></h2>
                    <div class="erp-omd-front-table-wrap">
                        <table class="erp-omd-front-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Źródło', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Etykieta', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Plik', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Wersja', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Typ', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Rozmiar', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Data dodania', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Akcje', 'erp-omd'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $client_attachment_rows = [];
                                foreach ((array) $selected_project_attachments as $project_attachment_item) {
                                    $project_attachment_item['source'] = __('Projekt', 'erp-omd');
                                    $project_attachment_item['source_key'] = 'project';
                                    $client_attachment_rows[] = $project_attachment_item;
                                }
                                foreach ((array) $selected_estimate_attachments as $estimate_attachment_item) {
                                    $estimate_attachment_item['source'] = __('Kosztorys', 'erp-omd');
                                    $estimate_attachment_item['source_key'] = 'estimate';
                                    $client_attachment_rows[] = $estimate_attachment_item;
                                }
                                usort(
                                    $client_attachment_rows,
                                    static function ($left, $right) {
                                        return [(string) ($right['created_at'] ?? ''), (int) ($right['id'] ?? 0)] <=> [(string) ($left['created_at'] ?? ''), (int) ($left['id'] ?? 0)];
                                    }
                                );
                                $attachment_version_totals = [];
                                foreach ($client_attachment_rows as $attachment_row) {
                                    $attachment_key = strtolower((string) ($attachment_row['source'] ?? '')) . '|' . strtolower(trim((string) ($attachment_row['label'] ?? '')));
                                    if (! isset($attachment_version_totals[$attachment_key])) {
                                        $attachment_version_totals[$attachment_key] = 0;
                                    }
                                    $attachment_version_totals[$attachment_key]++;
                                }
                                $attachment_version_remaining = $attachment_version_totals;
                                ?>
                                <?php if (! empty($client_attachment_rows)) : ?>
                                    <?php foreach ($client_attachment_rows as $project_attachment_item) : ?>
                                        <?php
                                        $attachment_id = (int) ($project_attachment_item['attachment_id'] ?? 0);
                                        $attachment_post = $attachment_id > 0 ? get_post($attachment_id) : null;
                                        $attachment_url = $attachment_id > 0 ? wp_get_attachment_url($attachment_id) : '';
                                        $attachment_title = $attachment_id > 0 ? get_the_title($attachment_id) : '';
                                        $attachment_file = $attachment_id > 0 ? get_attached_file($attachment_id) : '';
                                        $attachment_filetype = $attachment_id > 0 ? wp_check_filetype((string) $attachment_file) : [];
                                        $attachment_ext = strtolower((string) ($attachment_filetype['ext'] ?? ''));
                                        $attachment_size = (is_string($attachment_file) && $attachment_file !== '' && file_exists($attachment_file))
                                            ? size_format((int) filesize($attachment_file))
                                            : '—';
                                        $attachment_name = $attachment_title;
                                        if ($attachment_name === '' && is_object($attachment_post) && ! empty($attachment_post->post_name)) {
                                            $attachment_name = (string) $attachment_post->post_name;
                                        }
                                        if ($attachment_name === '') {
                                            $attachment_name = $attachment_id > 0 ? ('#' . $attachment_id) : '—';
                                        }
                                        $version_key = strtolower((string) ($project_attachment_item['source'] ?? '')) . '|' . strtolower(trim((string) ($project_attachment_item['label'] ?? '')));
                                        $version_number = (int) ($attachment_version_remaining[$version_key] ?? 1);
                                        if (isset($attachment_version_remaining[$version_key])) {
                                            $attachment_version_remaining[$version_key] = max(0, $attachment_version_remaining[$version_key] - 1);
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo esc_html((string) ($project_attachment_item['source'] ?? '—')); ?></td>
                                            <td><?php echo esc_html((string) ($project_attachment_item['label'] ?? '—')); ?></td>
                                            <td>
                                                <?php if ($attachment_url) : ?>
                                                    <a href="<?php echo esc_url($attachment_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($attachment_name); ?></a>
                                                <?php else : ?>
                                                    <?php echo esc_html($attachment_name); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo esc_html('v' . (string) max(1, $version_number)); ?></td>
                                            <td><?php echo esc_html($attachment_ext !== '' ? strtoupper($attachment_ext) : '—'); ?></td>
                                            <td><?php echo esc_html((string) $attachment_size); ?></td>
                                            <td><?php echo esc_html((string) ($project_attachment_item['created_at'] ?? '—')); ?></td>
                                            <td>
                                                <?php if ((string) ($project_attachment_item['source_key'] ?? '') === 'project' && (int) ($project_attachment_item['created_by_user_id'] ?? 0) === (int) $user->ID) : ?>
                                                    <form method="post" class="erp-omd-front-inline-form" onsubmit="return confirm('<?php echo esc_js(__('Usunąć załącznik?', 'erp-omd')); ?>');">
                                                        <?php wp_nonce_field('erp_omd_front_client'); ?>
                                                        <input type="hidden" name="erp_omd_front_action" value="delete_project_attachment" />
                                                        <input type="hidden" name="attachment_relation_id" value="<?php echo esc_attr((string) ($project_attachment_item['id'] ?? 0)); ?>" />
                                                        <input type="hidden" name="project_id" value="<?php echo esc_attr((string) $selected_project_id); ?>" />
                                                        <input type="hidden" name="project_scope" value="<?php echo esc_attr((string) $project_scope); ?>" />
                                                        <input type="hidden" name="sort_by" value="<?php echo esc_attr((string) $project_sort_by); ?>" />
                                                        <input type="hidden" name="sort_order" value="<?php echo esc_attr((string) $project_sort_order); ?>" />
                                                        <input type="hidden" name="history_month" value="<?php echo esc_attr((string) $history_month_filter); ?>" />
                                                        <button type="submit" class="erp-omd-front-button erp-omd-front-button-ghost"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                                    </form>
                                                <?php else : ?>
                                                    —
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr><td colspan="8"><?php esc_html_e('Brak załączników dla wybranego projektu.', 'erp-omd'); ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="erp-omd-front-panel">
                    <h2><?php esc_html_e('Historia uwag klienta', 'erp-omd'); ?></h2>
                    <?php if ($selected_project_id > 0) : ?>
                        <form method="post" enctype="multipart/form-data" class="erp-omd-front-form erp-omd-front-form-inline">
                            <?php wp_nonce_field('erp_omd_front_client'); ?>
                            <input type="hidden" name="erp_omd_front_action" value="create_project_note" />
                            <input type="hidden" name="project_id" value="<?php echo esc_attr((string) $selected_project_id); ?>" />
                            <input type="hidden" name="project_scope" value="<?php echo esc_attr((string) $project_scope); ?>" />
                            <input type="hidden" name="sort_by" value="<?php echo esc_attr((string) $project_sort_by); ?>" />
                            <input type="hidden" name="sort_order" value="<?php echo esc_attr((string) $project_sort_order); ?>" />
                            <input type="hidden" name="history_month" value="<?php echo esc_attr((string) $history_month_filter); ?>" />
                            <div class="erp-omd-front-grid erp-omd-front-grid-two">
                                <div class="erp-omd-front-field">
                                    <label for="erp-omd-client-note"><?php esc_html_e('Dodaj nową uwagę', 'erp-omd'); ?></label>
                                    <textarea id="erp-omd-client-note" name="note" rows="3"></textarea>
                                </div>
                                <div class="erp-omd-front-field">
                                    <label for="erp-omd-client-attachment-label"><?php esc_html_e('Etykieta załącznika (opcjonalnie)', 'erp-omd'); ?></label>
                                    <input id="erp-omd-client-attachment-label" type="text" name="attachment_label" />
                                </div>
                                <div class="erp-omd-front-field">
                                    <label for="erp-omd-client-attachment-file"><?php esc_html_e('Załącznik (pdf/jpg/jpeg/png/zip, max 30MB)', 'erp-omd'); ?></label>
                                    <input id="erp-omd-client-attachment-file" type="file" name="attachment_file" accept=".pdf,.jpg,.jpeg,.png,.zip" />
                                </div>
                            </div>
                            <div class="erp-omd-front-actions">
                                <button type="submit" class="erp-omd-front-button erp-omd-front-button-primary">
                                    <?php esc_html_e('Wyślij uwagę', 'erp-omd'); ?>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                    <div class="erp-omd-front-table-wrap">
                        <table class="erp-omd-front-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Data', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Autor', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Treść uwagi', 'erp-omd'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($selected_project_notes) : ?>
                                    <?php foreach ($selected_project_notes as $project_note_item) : ?>
                                        <tr>
                                            <td><?php echo esc_html((string) ($project_note_item['created_at'] ?? '—')); ?></td>
                                            <td><?php echo esc_html((string) ($project_note_item['author_login'] ?? '—')); ?></td>
                                            <td><?php echo esc_html((string) ($project_note_item['note'] ?? '')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr><td colspan="3"><?php esc_html_e('Brak uwag dla wybranego projektu.', 'erp-omd'); ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            <?php endif; ?>
        </section>
    </main>
    <?php wp_footer(); ?>
</body>
</html>
