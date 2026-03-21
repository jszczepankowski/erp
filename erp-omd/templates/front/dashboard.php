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
                    <p class="erp-omd-front-lead"><?php echo esc_html($dashboard_intro); ?></p>
                </div>
                <div class="erp-omd-front-actions">
                    <?php if (user_can($user, 'erp_omd_front_worker')) : ?>
                        <a class="erp-omd-front-button" href="<?php echo esc_url($front_worker_url); ?>"><?php esc_html_e('Panel pracownika', 'erp-omd'); ?></a>
                    <?php endif; ?>
                    <?php if (user_can($user, 'erp_omd_front_manager')) : ?>
                        <a class="erp-omd-front-button" href="<?php echo esc_url($front_manager_url); ?>"><?php esc_html_e('Panel managera', 'erp-omd'); ?></a>
                    <?php endif; ?>
                    <a class="erp-omd-front-button" href="<?php echo esc_url(admin_url()); ?>"><?php esc_html_e('wp-admin', 'erp-omd'); ?></a>
                    <a class="erp-omd-front-button erp-omd-front-button-secondary" href="<?php echo esc_url($front_logout_url); ?>"><?php esc_html_e('Wyloguj', 'erp-omd'); ?></a>
                </div>
            </div>

            <div class="erp-omd-front-grid">
                <article class="erp-omd-front-panel">
                    <h2><?php esc_html_e('Twoje konto', 'erp-omd'); ?></h2>
                    <ul>
                        <li><strong><?php esc_html_e('Użytkownik:', 'erp-omd'); ?></strong> <?php echo esc_html($user->user_login); ?></li>
                        <li><strong><?php esc_html_e('Email:', 'erp-omd'); ?></strong> <?php echo esc_html($user->user_email); ?></li>
                        <li><strong><?php esc_html_e('Typ ERP:', 'erp-omd'); ?></strong> <?php echo esc_html($employee['account_type'] ?? '—'); ?></li>
                        <li><strong><?php esc_html_e('Status:', 'erp-omd'); ?></strong> <?php echo esc_html($employee['status'] ?? '—'); ?></li>
                    </ul>
                </article>

                <article class="erp-omd-front-panel">
                    <h2><?php esc_html_e('Co już działa w FRONT-1', 'erp-omd'); ?></h2>
                    <ul>
                        <li><?php esc_html_e('Dedykowany adres logowania frontendowego.', 'erp-omd'); ?></li>
                        <li><?php esc_html_e('Routing do panelu pracownika lub managera po zalogowaniu.', 'erp-omd'); ?></li>
                        <li><?php esc_html_e('Ochrona tras przed niezalogowanym użytkownikiem.', 'erp-omd'); ?></li>
                        <li><?php esc_html_e('Wylogowanie z poprawnym redirectem.', 'erp-omd'); ?></li>
                    </ul>
                </article>
            </div>
        </section>
    </main>
    <?php wp_footer(); ?>
</body>
</html>
