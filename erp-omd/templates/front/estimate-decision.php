<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($decision_page_title); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url(ERP_OMD_URL . 'assets/css/front.css?ver=' . ERP_OMD_VERSION); ?>">
    <?php wp_head(); ?>
</head>
<body class="erp-omd-front-body erp-omd-front-estimate-decision">
<?php wp_body_open(); ?>
<main class="erp-omd-front-shell erp-omd-front-shell-estimate-decision">
    <section class="erp-omd-front-card erp-omd-front-card-estimate-decision">
        <span class="erp-omd-front-eyebrow"><?php echo esc_html($front_brand_label); ?></span>
        <h1><?php echo esc_html($decision_page_title); ?></h1>
        <p class="erp-omd-front-lead"><?php esc_html_e('Podejmij decyzję jednym kliknięciem. Po akceptacji kosztorys zostanie przetworzony automatycznie.', 'erp-omd'); ?></p>

        <?php if ($notice_message !== '') : ?>
            <div class="erp-omd-front-notice erp-omd-front-notice-<?php echo esc_attr($notice_type === 'success' ? 'success' : 'error'); ?>">
                <?php echo esc_html($notice_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($estimate) : ?>
            <div class="erp-omd-front-detail-grid">
                <div class="erp-omd-front-detail-item">
                    <strong><?php esc_html_e('Kosztorys', 'erp-omd'); ?></strong>
                    <span><?php echo esc_html((string) ($estimate['name'] ?? ('#' . (int) ($estimate['id'] ?? 0)))); ?></span>
                </div>
                <div class="erp-omd-front-detail-item">
                    <strong><?php esc_html_e('Klient', 'erp-omd'); ?></strong>
                    <span><?php echo esc_html((string) ($estimate['client_name'] ?? '—')); ?></span>
                </div>
                <div class="erp-omd-front-detail-item">
                    <strong><?php esc_html_e('Wartość netto', 'erp-omd'); ?></strong>
                    <span><?php echo esc_html(number_format_i18n((float) ($estimate_totals['net'] ?? 0), 2)); ?></span>
                </div>
                <div class="erp-omd-front-detail-item">
                    <strong><?php esc_html_e('Wartość brutto', 'erp-omd'); ?></strong>
                    <span><?php echo esc_html(number_format_i18n((float) ($estimate_totals['gross'] ?? 0), 2)); ?></span>
                </div>
            </div>

            <?php if ($token_valid && $token_expiry_label !== '') : ?>
                <div class="erp-omd-front-inline-message">
                    <strong><?php esc_html_e('Ważność linku:', 'erp-omd'); ?></strong>
                    <?php echo esc_html($token_expiry_label); ?>
                </div>
            <?php endif; ?>

            <?php if (! empty($estimate_items)) : ?>
                <div class="erp-omd-front-table-wrap">
                    <table class="erp-omd-front-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Pozycja', 'erp-omd'); ?></th>
                                <th><?php esc_html_e('Ilość', 'erp-omd'); ?></th>
                                <th><?php esc_html_e('Cena', 'erp-omd'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estimate_items as $item_row) : ?>
                                <tr>
                                    <td><?php echo esc_html((string) ($item_row['name'] ?? '—')); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((float) ($item_row['qty'] ?? 0), 2)); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((float) ($item_row['price'] ?? 0), 2)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($token_valid) : ?>
            <form method="post" class="erp-omd-front-form erp-omd-front-estimate-decision-form">
                <?php wp_nonce_field('erp_omd_front_estimate_decision'); ?>
                <input type="hidden" name="erp_omd_front_action" value="estimate_decision" />
                <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>" />

                <div class="erp-omd-front-estimate-decision-choice">
                    <label>
                        <input type="radio" name="decision" value="accept" <?php checked($selected_decision, 'accept'); ?>>
                        <?php esc_html_e('Akceptuję kosztorys', 'erp-omd'); ?>
                    </label>
                    <label>
                        <input type="radio" name="decision" value="reject" <?php checked($selected_decision, 'reject'); ?>>
                        <?php esc_html_e('Odrzucam kosztorys', 'erp-omd'); ?>
                    </label>
                </div>

                <div class="erp-omd-front-form-field">
                    <label for="erp-omd-estimate-reject-comment"><?php esc_html_e('Komentarz (wymagany przy odrzuceniu)', 'erp-omd'); ?></label>
                    <textarea id="erp-omd-estimate-reject-comment" name="comment" rows="4"><?php echo esc_textarea($comment_value); ?></textarea>
                </div>

                <button class="erp-omd-front-button erp-omd-front-button-secondary" type="submit"><?php esc_html_e('Wyślij decyzję', 'erp-omd'); ?></button>
            </form>
        <?php endif; ?>
    </section>
</main>
<?php wp_footer(); ?>
</body>
</html>
