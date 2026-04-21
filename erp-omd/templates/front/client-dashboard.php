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

            <div class="erp-omd-front-grid erp-omd-front-grid-summary">
                <article class="erp-omd-front-panel">
                    <h2><?php esc_html_e('Twoje konto', 'erp-omd'); ?></h2>
                    <ul>
                        <li><strong><?php esc_html_e('Użytkownik:', 'erp-omd'); ?></strong> <?php echo esc_html($user->user_login); ?></li>
                        <li><strong><?php esc_html_e('Email:', 'erp-omd'); ?></strong> <?php echo esc_html($user->user_email); ?></li>
                        <li><strong><?php esc_html_e('Klient ID:', 'erp-omd'); ?></strong> <?php echo esc_html($client_id > 0 ? (string) $client_id : '—'); ?></li>
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
            </div>

            <article class="erp-omd-front-panel">
                <div class="erp-omd-front-section-heading">
                    <h2><?php esc_html_e('Projekty klienta (status + deadline)', 'erp-omd'); ?></h2>
                </div>

                <?php if ($client_id <= 0) : ?>
                    <div class="erp-omd-front-notice erp-omd-front-notice-warning">
                        <?php esc_html_e('Brak przypisanego `erp_omd_client_id` dla tego użytkownika. Skontaktuj się z administratorem.', 'erp-omd'); ?>
                    </div>
                <?php endif; ?>

                <div class="erp-omd-front-table-wrap">
                    <table class="erp-omd-front-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Projekt', 'erp-omd'); ?></th>
                                <th><?php esc_html_e('Status', 'erp-omd'); ?></th>
                                <th><?php esc_html_e('Deadline', 'erp-omd'); ?></th>
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
                                    <td colspan="4"><?php esc_html_e('Brak projektów do wyświetlenia.', 'erp-omd'); ?></td>
                                </tr>
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
                        <div class="erp-omd-front-metric">
                            <span class="erp-omd-front-metric-label"><?php esc_html_e('Koszty projektu', 'erp-omd'); ?></span>
                            <strong><?php echo esc_html(number_format_i18n((float) ($selected_project_finance['cost_total'] ?? 0), 2)); ?></strong>
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
                        <h2><?php esc_html_e('Koszty projektu per pozycja', 'erp-omd'); ?></h2>
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
                                    <?php $cost_items = (array) ($selected_project_finance['cost_items'] ?? []); ?>
                                    <?php if ($cost_items) : ?>
                                        <?php foreach ($cost_items as $cost_item) : ?>
                                            <tr>
                                                <td><?php echo esc_html((string) ($cost_item['date'] ?? '—')); ?></td>
                                                <td><?php echo esc_html((string) ($cost_item['label'] ?? '—')); ?></td>
                                                <td><?php echo esc_html(number_format_i18n((float) ($cost_item['amount'] ?? 0), 2)); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr><td colspan="3"><?php esc_html_e('Brak pozycji kosztowych.', 'erp-omd'); ?></td></tr>
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
            <?php endif; ?>
        </section>
    </main>
    <?php wp_footer(); ?>
</body>
</html>
