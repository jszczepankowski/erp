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
    <main class="erp-omd-front-shell erp-omd-front-shell-login">
        <section class="erp-omd-front-login-layout">
            <div class="erp-omd-front-login-pane erp-omd-front-login-pane-form">
                <div class="erp-omd-front-login-panel">
                    <div class="erp-omd-front-login-brand">
                        <?php if (! empty($front_login_logo_url)) : ?>
                            <img src="<?php echo esc_url($front_login_logo_url); ?>" alt="<?php echo esc_attr($front_brand_label); ?>" class="erp-omd-front-login-logo">
                        <?php else : ?>
                            <span class="erp-omd-front-eyebrow"><?php echo esc_html($front_brand_label); ?></span>
                        <?php endif; ?>
                        <h1><?php esc_html_e('Zaloguj do systemu', 'erp-omd'); ?></h1>
                        <p class="erp-omd-front-lead"><?php esc_html_e('Panel FRONT utrzymuje administracyjny styl ERP OMD i prowadzi pracownika lub managera prosto do właściwego widoku pracy.', 'erp-omd'); ?></p>
                    </div>

                    <?php if ($logged_out) : ?>
                        <div class="erp-omd-front-notice erp-omd-front-notice-success"><?php esc_html_e('Wylogowano poprawnie.', 'erp-omd'); ?></div>
                    <?php endif; ?>

                    <?php if ($denied) : ?>
                        <div class="erp-omd-front-notice erp-omd-front-notice-warning"><?php esc_html_e('To konto nie ma jeszcze dostępu do panelu FRONT.', 'erp-omd'); ?></div>
                    <?php endif; ?>

                    <?php if ($error instanceof WP_Error) : ?>
                        <div class="erp-omd-front-notice erp-omd-front-notice-error"><?php echo esc_html($error->get_error_message()); ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?php echo esc_url($front_login_url); ?>" class="erp-omd-front-form erp-omd-front-login-form">
                        <?php wp_nonce_field('erp_omd_front_login'); ?>
                        <input type="hidden" name="erp_omd_front_action" value="login">
                        <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

                        <div class="erp-omd-front-form-field">
                            <label for="erp-omd-front-login"><?php esc_html_e('Login lub email', 'erp-omd'); ?></label>
                            <input id="erp-omd-front-login" name="log" type="text" autocomplete="username" required>
                        </div>

                        <div class="erp-omd-front-form-field">
                            <label for="erp-omd-front-password"><?php esc_html_e('Hasło', 'erp-omd'); ?></label>
                            <input id="erp-omd-front-password" name="pwd" type="password" autocomplete="current-password" required>
                        </div>

                        <div class="erp-omd-front-login-actions">
                            <label class="erp-omd-front-checkbox">
                                <input type="checkbox" name="rememberme" value="forever">
                                <span><?php esc_html_e('Zapamiętaj mnie', 'erp-omd'); ?></span>
                            </label>

                            <button type="submit" class="erp-omd-front-button erp-omd-front-button-secondary"><?php esc_html_e('Zaloguj do systemu', 'erp-omd'); ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="erp-omd-front-login-pane erp-omd-front-login-pane-visual">
                <div class="erp-omd-front-login-visual"<?php echo ! empty($front_login_cover_url) ? ' style="background-image: url(' . esc_url($front_login_cover_url) . ');"' : ''; ?>>
                    <div class="erp-omd-front-login-visual-overlay">
                        <span class="erp-omd-front-eyebrow"><?php esc_html_e('ERP OMD', 'erp-omd'); ?></span>
                        <strong><?php esc_html_e('Operacyjny porządek i spokojny interfejs pracy.', 'erp-omd'); ?></strong>
                        <p><?php esc_html_e('Ta część jest gotowa na konfigurowalną grafikę z ustawień wtyczki i utrzymuje premium, jasny charakter całego panelu.', 'erp-omd'); ?></p>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php wp_footer(); ?>
</body>
</html>
