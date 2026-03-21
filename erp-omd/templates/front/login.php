<?php
/** @var WP_Error|null $error */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($front_brand_label); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url(ERP_OMD_URL . 'assets/css/front.css?ver=' . ERP_OMD_VERSION); ?>">
    <?php wp_head(); ?>
</head>
<body class="erp-omd-front-body">
    <main class="erp-omd-front-shell">
        <section class="erp-omd-front-card erp-omd-front-card-login">
            <span class="erp-omd-front-eyebrow"><?php echo esc_html($front_brand_label); ?></span>
            <h1><?php esc_html_e('Zaloguj się do panelu FRONT', 'erp-omd'); ?></h1>
            <p class="erp-omd-front-lead"><?php esc_html_e('To nowy punkt wejścia dla pracowników i managerów. Korzysta z WordPress auth, ale prowadzi do dedykowanego panelu zamiast surowego wp-admin.', 'erp-omd'); ?></p>

            <?php if ($logged_out) : ?>
                <div class="erp-omd-front-notice erp-omd-front-notice-success"><?php esc_html_e('Wylogowano poprawnie.', 'erp-omd'); ?></div>
            <?php endif; ?>

            <?php if ($denied) : ?>
                <div class="erp-omd-front-notice erp-omd-front-notice-warning"><?php esc_html_e('To konto nie ma jeszcze dostępu do panelu FRONT.', 'erp-omd'); ?></div>
            <?php endif; ?>

            <?php if ($error instanceof WP_Error) : ?>
                <div class="erp-omd-front-notice erp-omd-front-notice-error"><?php echo esc_html($error->get_error_message()); ?></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url($front_login_url); ?>" class="erp-omd-front-form">
                <?php wp_nonce_field('erp_omd_front_login'); ?>
                <input type="hidden" name="erp_omd_front_action" value="login">
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

                <label for="erp-omd-front-login"><?php esc_html_e('Login lub email', 'erp-omd'); ?></label>
                <input id="erp-omd-front-login" name="log" type="text" autocomplete="username" required>

                <label for="erp-omd-front-password"><?php esc_html_e('Hasło', 'erp-omd'); ?></label>
                <input id="erp-omd-front-password" name="pwd" type="password" autocomplete="current-password" required>

                <label class="erp-omd-front-checkbox">
                    <input type="checkbox" name="rememberme" value="forever">
                    <span><?php esc_html_e('Zapamiętaj mnie', 'erp-omd'); ?></span>
                </label>

                <button type="submit" class="erp-omd-front-button erp-omd-front-button-primary"><?php esc_html_e('Wejdź do systemu', 'erp-omd'); ?></button>
            </form>
        </section>
    </main>
    <?php wp_footer(); ?>
</body>
</html>
