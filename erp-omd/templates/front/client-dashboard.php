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
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="3"><?php esc_html_e('Brak projektów do wyświetlenia.', 'erp-omd'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </main>
    <?php wp_footer(); ?>
</body>
</html>
