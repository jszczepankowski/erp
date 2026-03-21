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
                    <a class="erp-omd-front-button" href="<?php echo esc_url($front_manager_url); ?>"><?php esc_html_e('Odśwież panel', 'erp-omd'); ?></a>
                    <a class="erp-omd-front-button erp-omd-front-button-secondary" href="<?php echo esc_url($front_logout_url); ?>"><?php esc_html_e('Wyloguj', 'erp-omd'); ?></a>
                </div>
            </div>

            <?php if ($manager_notice_type && $manager_notice_message) : ?>
                <div class="erp-omd-front-notice erp-omd-front-notice-<?php echo esc_attr($manager_notice_type); ?>"><?php echo esc_html($manager_notice_message); ?></div>
            <?php endif; ?>

            <div class="erp-omd-front-grid erp-omd-front-grid-summary">
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
                    <h2><?php esc_html_e('Szybkie podsumowanie', 'erp-omd'); ?></h2>
                    <div class="erp-omd-front-metrics">
                        <div class="erp-omd-front-metric">
                            <span class="erp-omd-front-metric-label"><?php esc_html_e('Twoje projekty', 'erp-omd'); ?></span>
                            <strong><?php echo esc_html((string) $dashboard_metrics['projects_count']); ?></strong>
                        </div>
                        <div class="erp-omd-front-metric">
                            <span class="erp-omd-front-metric-label"><?php esc_html_e('Kolejka akceptacji', 'erp-omd'); ?></span>
                            <strong><?php echo esc_html((string) $dashboard_metrics['queue_count']); ?></strong>
                        </div>
                        <div class="erp-omd-front-metric">
                            <span class="erp-omd-front-metric-label"><?php esc_html_e('Godziny do decyzji', 'erp-omd'); ?></span>
                            <strong><?php echo esc_html(number_format_i18n((float) $dashboard_metrics['submitted_hours'], 2)); ?></strong>
                        </div>
                        <div class="erp-omd-front-metric">
                            <span class="erp-omd-front-metric-label"><?php esc_html_e('Widoczne kosztorysy', 'erp-omd'); ?></span>
                            <strong><?php echo esc_html((string) $dashboard_metrics['linked_estimates_count']); ?></strong>
                        </div>
                        <div class="erp-omd-front-metric">
                            <span class="erp-omd-front-metric-label"><?php esc_html_e('Wnioski', 'erp-omd'); ?></span>
                            <strong><?php echo esc_html((string) $dashboard_metrics['project_requests_count']); ?></strong>
                        </div>
                    </div>
                </article>
            </div>

            <div class="erp-omd-front-grid erp-omd-front-grid-manager">
                <article class="erp-omd-front-panel">
                    <div class="erp-omd-front-section-heading">
                        <h2><?php esc_html_e('Twoje projekty', 'erp-omd'); ?></h2>
                        <p><?php esc_html_e('Wybierz projekt, aby zobaczyć finanse, alerty, powiązane kosztorysy i kolejkę wpisów czasu.', 'erp-omd'); ?></p>
                    </div>

                    <?php if ($managed_projects) : ?>
                        <div class="erp-omd-front-stack">
                            <?php foreach ($managed_projects as $project) : ?>
                                <?php
                                $project_id = (int) ($project['id'] ?? 0);
                                $project_financial = (array) ($project['financial'] ?? []);
                                $project_alerts = (array) ($project['alerts'] ?? []);
                                $project_url = add_query_arg(
                                    [
                                        'project_id' => $project_id,
                                        'estimate_id' => (int) ($selected_estimate['id'] ?? 0),
                                    ],
                                    $front_manager_url
                                ) . '#project-detail';
                                ?>
                                <a class="erp-omd-front-project-card <?php echo $selected_project && (int) ($selected_project['id'] ?? 0) === $project_id ? 'erp-omd-front-project-card-active' : ''; ?>" href="<?php echo esc_url($project_url); ?>">
                                    <div class="erp-omd-front-project-card-header">
                                        <div>
                                            <strong><?php echo esc_html($project['name'] ?? ('#' . $project_id)); ?></strong>
                                            <p><?php echo esc_html($project['client_name'] ?? '—'); ?> · <?php echo esc_html($project['billing_type'] ?? '—'); ?></p>
                                        </div>
                                        <span class="erp-omd-front-chip"><?php echo esc_html($project['status'] ?? '—'); ?></span>
                                    </div>
                                    <div class="erp-omd-front-project-card-meta">
                                        <span><?php printf(esc_html__('Marża: %s%%', 'erp-omd'), esc_html(number_format_i18n((float) ($project_financial['margin'] ?? 0), 2))); ?></span>
                                        <span><?php printf(esc_html__('Zgłoszone wpisy: %d', 'erp-omd'), (int) ($project['pending_entries_count'] ?? 0)); ?></span>
                                        <span><?php printf(esc_html__('Alerty: %d', 'erp-omd'), count($project_alerts)); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p class="erp-omd-front-lead"><?php esc_html_e('Nie masz jeszcze przypisanych projektów managerskich.', 'erp-omd'); ?></p>
                    <?php endif; ?>
                </article>

                <article id="project-detail" class="erp-omd-front-panel">
                    <div class="erp-omd-front-section-heading">
                        <h2><?php esc_html_e('Karta projektu', 'erp-omd'); ?></h2>
                        <p><?php esc_html_e('Podstawowe dane biznesowe, marża, alerty i powiązane kosztorysy dla wybranego projektu.', 'erp-omd'); ?></p>
                    </div>

                    <?php if ($selected_project) : ?>
                        <?php
                        $selected_project_financial = (array) ($selected_project['financial'] ?? []);
                        $selected_project_alerts = (array) ($selected_project['alerts'] ?? []);
                        $selected_project_estimates = array_values(
                            array_filter(
                                $available_estimates,
                                static function ($estimate) use ($selected_project) {
                                    return (int) ($estimate['project_id'] ?? 0) === (int) ($selected_project['id'] ?? 0)
                                        || (int) ($estimate['id'] ?? 0) === (int) ($selected_project['estimate_id'] ?? 0);
                                }
                            )
                        );
                        ?>
                        <div class="erp-omd-front-detail-grid">
                            <div class="erp-omd-front-detail-item">
                                <strong><?php esc_html_e('Projekt', 'erp-omd'); ?></strong>
                                <span><?php echo esc_html($selected_project['name'] ?? '—'); ?></span>
                            </div>
                            <div class="erp-omd-front-detail-item">
                                <strong><?php esc_html_e('Klient', 'erp-omd'); ?></strong>
                                <span><?php echo esc_html($selected_project['client_name'] ?? '—'); ?></span>
                            </div>
                            <div class="erp-omd-front-detail-item">
                                <strong><?php esc_html_e('Status', 'erp-omd'); ?></strong>
                                <span><?php echo esc_html($selected_project['status'] ?? '—'); ?></span>
                            </div>
                            <div class="erp-omd-front-detail-item">
                                <strong><?php esc_html_e('Typ rozliczenia', 'erp-omd'); ?></strong>
                                <span><?php echo esc_html($selected_project['billing_type'] ?? '—'); ?></span>
                            </div>
                            <div class="erp-omd-front-detail-item">
                                <strong><?php esc_html_e('Budżet', 'erp-omd'); ?></strong>
                                <span><?php echo esc_html(number_format_i18n((float) ($selected_project['budget'] ?? 0), 2)); ?></span>
                            </div>
                            <div class="erp-omd-front-detail-item">
                                <strong><?php esc_html_e('Manager', 'erp-omd'); ?></strong>
                                <span><?php echo esc_html($selected_project['manager_login'] ?? '—'); ?></span>
                            </div>
                        </div>

                        <div class="erp-omd-front-metrics">
                            <div class="erp-omd-front-metric">
                                <span class="erp-omd-front-metric-label"><?php esc_html_e('Przychód', 'erp-omd'); ?></span>
                                <strong><?php echo esc_html(number_format_i18n((float) ($selected_project_financial['revenue'] ?? 0), 2)); ?></strong>
                            </div>
                            <div class="erp-omd-front-metric">
                                <span class="erp-omd-front-metric-label"><?php esc_html_e('Koszt', 'erp-omd'); ?></span>
                                <strong><?php echo esc_html(number_format_i18n((float) ($selected_project_financial['cost'] ?? 0), 2)); ?></strong>
                            </div>
                            <div class="erp-omd-front-metric">
                                <span class="erp-omd-front-metric-label"><?php esc_html_e('Zysk', 'erp-omd'); ?></span>
                                <strong><?php echo esc_html(number_format_i18n((float) ($selected_project_financial['profit'] ?? 0), 2)); ?></strong>
                            </div>
                            <div class="erp-omd-front-metric">
                                <span class="erp-omd-front-metric-label"><?php esc_html_e('Marża %', 'erp-omd'); ?></span>
                                <strong><?php echo esc_html(number_format_i18n((float) ($selected_project_financial['margin'] ?? 0), 2)); ?></strong>
                            </div>
                        </div>

                        <div class="erp-omd-front-grid erp-omd-front-grid-summary">
                            <div class="erp-omd-front-panel erp-omd-front-panel-subtle">
                                <div class="erp-omd-front-section-heading">
                                    <h3><?php esc_html_e('Alerty projektu', 'erp-omd'); ?></h3>
                                    <p><?php esc_html_e('Sygnały wymagające uwagi managera dla bieżącego projektu.', 'erp-omd'); ?></p>
                                </div>
                                <?php if ($selected_project_alerts) : ?>
                                    <div class="erp-omd-front-stack">
                                        <?php foreach ($selected_project_alerts as $alert) : ?>
                                            <div class="erp-omd-front-inline-message">
                                                <span class="erp-omd-front-chip"><?php echo esc_html($alert['severity'] ?? 'info'); ?></span>
                                                <span><?php echo esc_html($alert['message'] ?? ''); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else : ?>
                                    <p class="erp-omd-front-lead"><?php esc_html_e('Brak aktywnych alertów dla tego projektu.', 'erp-omd'); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="erp-omd-front-panel erp-omd-front-panel-subtle">
                                <div class="erp-omd-front-section-heading">
                                    <h3><?php esc_html_e('Powiązane kosztorysy', 'erp-omd'); ?></h3>
                                    <p><?php esc_html_e('Kosztorysy bezpośrednio spięte z projektem lub wskazane jako źródło projektu.', 'erp-omd'); ?></p>
                                </div>
                                <?php if ($selected_project_estimates) : ?>
                                    <div class="erp-omd-front-stack">
                                        <?php foreach ($selected_project_estimates as $estimate) : ?>
                                            <?php $estimate_url = add_query_arg(['project_id' => (int) ($selected_project['id'] ?? 0), 'estimate_id' => (int) ($estimate['id'] ?? 0)], $front_manager_url) . '#estimate-detail'; ?>
                                            <a class="erp-omd-front-inline-message erp-omd-front-inline-message-link" href="<?php echo esc_url($estimate_url); ?>">
                                                <strong><?php echo esc_html($estimate['name'] ?? ('#' . (int) ($estimate['id'] ?? 0))); ?></strong>
                                                <span><?php echo esc_html($estimate['status'] ?? '—'); ?> · <?php echo esc_html($estimate['client_name'] ?? '—'); ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else : ?>
                                    <p class="erp-omd-front-lead"><?php esc_html_e('Brak powiązanych kosztorysów dla wybranego projektu.', 'erp-omd'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else : ?>
                        <p class="erp-omd-front-lead"><?php esc_html_e('Wybierz projekt z listy, aby otworzyć jego kartę.', 'erp-omd'); ?></p>
                    <?php endif; ?>
                </article>
            </div>

            <div class="erp-omd-front-grid erp-omd-front-grid-manager erp-omd-front-grid-estimates">
                <article class="erp-omd-front-panel erp-omd-front-panel-form">
                    <div class="erp-omd-front-section-heading">
                        <h2><?php esc_html_e('Nowy kosztorys', 'erp-omd'); ?></h2>
                        <p><?php esc_html_e('Manager może utworzyć kosztorys bez przechodzenia do wp-admin. Formularz zapisuje kosztorys od razu z pierwszą pozycją.', 'erp-omd'); ?></p>
                    </div>

                    <form method="post" action="<?php echo esc_url($manager_form_action); ?>" class="erp-omd-front-form">
                        <?php wp_nonce_field('erp_omd_front_manager'); ?>
                        <input type="hidden" name="erp_omd_front_action" value="create_estimate">

                        <label for="erp-omd-front-estimate-client"><?php esc_html_e('Klient', 'erp-omd'); ?></label>
                        <select id="erp-omd-front-estimate-client" name="client_id" required>
                            <option value="0"><?php esc_html_e('Wybierz klienta', 'erp-omd'); ?></option>
                            <?php foreach ($available_clients as $client_item) : ?>
                                <option value="<?php echo esc_attr((string) $client_item['id']); ?>"><?php echo esc_html($client_item['name']); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label for="erp-omd-front-estimate-name"><?php esc_html_e('Nazwa kosztorysu', 'erp-omd'); ?></label>
                        <input id="erp-omd-front-estimate-name" type="text" name="name" required>

                        <label for="erp-omd-front-estimate-status"><?php esc_html_e('Status', 'erp-omd'); ?></label>
                        <select id="erp-omd-front-estimate-status" name="status">
                            <?php foreach (['wstepny' => __('Wstępny', 'erp-omd'), 'do_akceptacji' => __('Do akceptacji', 'erp-omd')] as $estimate_status => $estimate_status_label) : ?>
                                <option value="<?php echo esc_attr($estimate_status); ?>"><?php echo esc_html($estimate_status_label); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <div class="erp-omd-front-panel erp-omd-front-panel-subtle">
                            <div class="erp-omd-front-section-heading">
                                <h3><?php esc_html_e('Pierwsza pozycja kosztorysu', 'erp-omd'); ?></h3>
                                <p><?php esc_html_e('Jedna pozycja jest wymagana, aby kosztorys od razu nadawał się do dalszej pracy i akceptacji.', 'erp-omd'); ?></p>
                            </div>

                            <label for="erp-omd-front-estimate-item-name"><?php esc_html_e('Nazwa pozycji', 'erp-omd'); ?></label>
                            <input id="erp-omd-front-estimate-item-name" type="text" name="item_name" required>

                            <div class="erp-omd-front-form-row">
                                <div>
                                    <label for="erp-omd-front-estimate-item-qty"><?php esc_html_e('Ilość', 'erp-omd'); ?></label>
                                    <input id="erp-omd-front-estimate-item-qty" type="number" min="0.01" step="0.01" name="item_qty" value="1" required>
                                </div>
                                <div>
                                    <label for="erp-omd-front-estimate-item-price"><?php esc_html_e('Cena sprzedaży', 'erp-omd'); ?></label>
                                    <input id="erp-omd-front-estimate-item-price" type="number" min="0" step="0.01" name="item_price" value="0" required>
                                </div>
                            </div>

                            <div class="erp-omd-front-form-row">
                                <div>
                                    <label for="erp-omd-front-estimate-item-cost"><?php esc_html_e('Koszt wewnętrzny', 'erp-omd'); ?></label>
                                    <input id="erp-omd-front-estimate-item-cost" type="number" min="0" step="0.01" name="item_cost_internal" value="0" required>
                                </div>
                                <div>
                                    <label for="erp-omd-front-estimate-item-comment"><?php esc_html_e('Komentarz', 'erp-omd'); ?></label>
                                    <textarea id="erp-omd-front-estimate-item-comment" name="item_comment" rows="4"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="erp-omd-front-inline-actions">
                            <button type="submit" class="erp-omd-front-button erp-omd-front-button-primary"><?php esc_html_e('Utwórz kosztorys', 'erp-omd'); ?></button>
                        </div>
                    </form>
                </article>

                <article id="estimate-detail" class="erp-omd-front-panel">
                    <div class="erp-omd-front-section-heading">
                        <h2><?php esc_html_e('Kosztorysy', 'erp-omd'); ?></h2>
                        <p><?php esc_html_e('Wszystkie kosztorysy widoczne z perspektywy Twoich klientów i projektów. Możesz przełączać szczegóły bez opuszczania frontu.', 'erp-omd'); ?></p>
                    </div>

                    <?php if ($available_estimates) : ?>
                        <div class="erp-omd-front-estimate-list">
                            <?php foreach ($available_estimates as $estimate_row) : ?>
                                <?php
                                $estimate_id = (int) ($estimate_row['id'] ?? 0);
                                $estimate_totals = (array) ($estimate_row['totals'] ?? []);
                                $estimate_url = add_query_arg(
                                    [
                                        'project_id' => (int) ($selected_project['id'] ?? 0),
                                        'estimate_id' => $estimate_id,
                                    ],
                                    $front_manager_url
                                ) . '#estimate-detail';
                                ?>
                                <a class="erp-omd-front-project-card <?php echo $selected_estimate && (int) ($selected_estimate['id'] ?? 0) === $estimate_id ? 'erp-omd-front-project-card-active' : ''; ?>" href="<?php echo esc_url($estimate_url); ?>">
                                    <div class="erp-omd-front-project-card-header">
                                        <div>
                                            <strong><?php echo esc_html($estimate_row['name'] ?? ('#' . $estimate_id)); ?></strong>
                                            <p><?php echo esc_html($estimate_row['client_name'] ?? '—'); ?><?php if (! empty($estimate_row['project_name'])) : ?> · <?php echo esc_html($estimate_row['project_name']); ?><?php endif; ?></p>
                                        </div>
                                        <span class="erp-omd-front-chip"><?php echo esc_html($estimate_row['status'] ?? '—'); ?></span>
                                    </div>
                                    <div class="erp-omd-front-project-card-meta">
                                        <span><?php printf(esc_html__('Pozycje: %d', 'erp-omd'), (int) ($estimate_row['items_count'] ?? 0)); ?></span>
                                        <span><?php printf(esc_html__('Netto: %s', 'erp-omd'), esc_html(number_format_i18n((float) ($estimate_totals['net'] ?? 0), 2))); ?></span>
                                        <span><?php printf(esc_html__('Brutto: %s', 'erp-omd'), esc_html(number_format_i18n((float) ($estimate_totals['gross'] ?? 0), 2))); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($selected_estimate) : ?>
                            <?php $selected_estimate_totals = (array) ($selected_estimate['totals'] ?? []); ?>
                            <div class="erp-omd-front-panel erp-omd-front-panel-subtle erp-omd-front-estimate-summary">
                                <div class="erp-omd-front-section-heading">
                                    <h3><?php echo esc_html($selected_estimate['name'] ?? ('#' . (int) ($selected_estimate['id'] ?? 0))); ?></h3>
                                    <p><?php esc_html_e('Szczegóły kosztorysu, sumy i pierwsze pozycje. Jeśli kosztorys nie jest jeszcze zaakceptowany, możesz zaakceptować go z poziomu frontu managera.', 'erp-omd'); ?></p>
                                </div>

                                <div class="erp-omd-front-detail-grid">
                                    <div class="erp-omd-front-detail-item">
                                        <strong><?php esc_html_e('Klient', 'erp-omd'); ?></strong>
                                        <span><?php echo esc_html($selected_estimate['client_name'] ?? '—'); ?></span>
                                    </div>
                                    <div class="erp-omd-front-detail-item">
                                        <strong><?php esc_html_e('Status', 'erp-omd'); ?></strong>
                                        <span><?php echo esc_html($selected_estimate['status'] ?? '—'); ?></span>
                                    </div>
                                    <div class="erp-omd-front-detail-item">
                                        <strong><?php esc_html_e('Projekt', 'erp-omd'); ?></strong>
                                        <span><?php echo esc_html($selected_estimate['project_name'] ?? '—'); ?></span>
                                    </div>
                                    <div class="erp-omd-front-detail-item">
                                        <strong><?php esc_html_e('Zaakceptowano', 'erp-omd'); ?></strong>
                                        <span><?php echo esc_html($selected_estimate['accepted_at'] ?? '—'); ?></span>
                                    </div>
                                </div>

                                <div class="erp-omd-front-metrics">
                                    <div class="erp-omd-front-metric">
                                        <span class="erp-omd-front-metric-label"><?php esc_html_e('Netto', 'erp-omd'); ?></span>
                                        <strong><?php echo esc_html(number_format_i18n((float) ($selected_estimate_totals['net'] ?? 0), 2)); ?></strong>
                                    </div>
                                    <div class="erp-omd-front-metric">
                                        <span class="erp-omd-front-metric-label"><?php esc_html_e('VAT', 'erp-omd'); ?></span>
                                        <strong><?php echo esc_html(number_format_i18n((float) ($selected_estimate_totals['tax'] ?? 0), 2)); ?></strong>
                                    </div>
                                    <div class="erp-omd-front-metric">
                                        <span class="erp-omd-front-metric-label"><?php esc_html_e('Brutto', 'erp-omd'); ?></span>
                                        <strong><?php echo esc_html(number_format_i18n((float) ($selected_estimate_totals['gross'] ?? 0), 2)); ?></strong>
                                    </div>
                                    <div class="erp-omd-front-metric">
                                        <span class="erp-omd-front-metric-label"><?php esc_html_e('Koszt wewnętrzny', 'erp-omd'); ?></span>
                                        <strong><?php echo esc_html(number_format_i18n((float) ($selected_estimate_totals['internal_cost'] ?? 0), 2)); ?></strong>
                                    </div>
                                </div>

                                <?php if (($selected_estimate['status'] ?? '') !== 'zaakceptowany') : ?>
                                    <div class="erp-omd-front-inline-actions erp-omd-front-estimate-actions">
                                        <form method="post" action="<?php echo esc_url($manager_form_action); ?>">
                                            <?php wp_nonce_field('erp_omd_front_manager'); ?>
                                            <input type="hidden" name="erp_omd_front_action" value="accept_estimate">
                                            <input type="hidden" name="estimate_id" value="<?php echo esc_attr((string) ($selected_estimate['id'] ?? 0)); ?>">
                                            <button type="submit" class="erp-omd-front-button erp-omd-front-button-primary"><?php esc_html_e('Akceptuj kosztorys', 'erp-omd'); ?></button>
                                        </form>
                                    </div>
                                <?php endif; ?>

                                <div class="erp-omd-front-table-wrap">
                                    <table class="erp-omd-front-table">
                                        <thead>
                                            <tr>
                                                <th><?php esc_html_e('Pozycja', 'erp-omd'); ?></th>
                                                <th><?php esc_html_e('Ilość', 'erp-omd'); ?></th>
                                                <th><?php esc_html_e('Cena', 'erp-omd'); ?></th>
                                                <th><?php esc_html_e('Koszt wewnętrzny', 'erp-omd'); ?></th>
                                                <th><?php esc_html_e('Komentarz', 'erp-omd'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (! empty($selected_estimate['items'])) : ?>
                                                <?php foreach ((array) $selected_estimate['items'] as $estimate_item) : ?>
                                                    <tr>
                                                        <td><?php echo esc_html($estimate_item['name'] ?? '—'); ?></td>
                                                        <td><?php echo esc_html(number_format_i18n((float) ($estimate_item['qty'] ?? 0), 2)); ?></td>
                                                        <td><?php echo esc_html(number_format_i18n((float) ($estimate_item['price'] ?? 0), 2)); ?></td>
                                                        <td><?php echo esc_html(number_format_i18n((float) ($estimate_item['cost_internal'] ?? 0), 2)); ?></td>
                                                        <td><?php echo esc_html($estimate_item['comment'] ?? '—'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else : ?>
                                                <tr>
                                                    <td colspan="5"><?php esc_html_e('Ten kosztorys nie ma jeszcze pozycji.', 'erp-omd'); ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else : ?>
                        <p class="erp-omd-front-lead"><?php esc_html_e('Brak kosztorysów widocznych dla Twojego zakresu odpowiedzialności.', 'erp-omd'); ?></p>
                    <?php endif; ?>
                </article>
            </div>

            <div class="erp-omd-front-panel">
                <div class="erp-omd-front-section-heading">
                    <h2><?php esc_html_e('Kolejka wpisów czasu do akceptacji', 'erp-omd'); ?></h2>
                    <p><?php esc_html_e('Szybkie decyzje operacyjne dla wpisów w statusie „submitted” przypisanych do Twoich projektów.', 'erp-omd'); ?></p>
                </div>

                <div class="erp-omd-front-metrics">
                    <div class="erp-omd-front-metric">
                        <span class="erp-omd-front-metric-label"><?php esc_html_e('Wpisy', 'erp-omd'); ?></span>
                        <strong><?php echo esc_html((string) count($approval_queue)); ?></strong>
                    </div>
                    <div class="erp-omd-front-metric">
                        <span class="erp-omd-front-metric-label"><?php esc_html_e('Godziny', 'erp-omd'); ?></span>
                        <strong><?php echo esc_html(number_format_i18n((float) $queue_summary['hours'], 2)); ?></strong>
                    </div>
                    <div class="erp-omd-front-metric">
                        <span class="erp-omd-front-metric-label"><?php esc_html_e('Pracownicy', 'erp-omd'); ?></span>
                        <strong><?php echo esc_html((string) ($queue_summary['employees_count'] ?? 0)); ?></strong>
                    </div>
                </div>

                <div class="erp-omd-front-table-wrap">
                    <table class="erp-omd-front-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Data', 'erp-omd'); ?></th>
                                <th><?php esc_html_e('Pracownik', 'erp-omd'); ?></th>
                                <th><?php esc_html_e('Projekt', 'erp-omd'); ?></th>
                                <th><?php esc_html_e('Rola', 'erp-omd'); ?></th>
                                <th><?php esc_html_e('Godziny', 'erp-omd'); ?></th>
                                <th><?php esc_html_e('Opis', 'erp-omd'); ?></th>
                                <th><?php esc_html_e('Akcje', 'erp-omd'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($approval_queue) : ?>
                                <?php foreach ($approval_queue as $queue_entry) : ?>
                                    <tr>
                                        <td><?php echo esc_html($queue_entry['entry_date'] ?? '—'); ?></td>
                                        <td><?php echo esc_html($queue_entry['employee_login'] ?? '—'); ?></td>
                                        <td><?php echo esc_html($queue_entry['project_name'] ?? '—'); ?></td>
                                        <td><?php echo esc_html($queue_entry['role_name'] ?? '—'); ?></td>
                                        <td><?php echo esc_html(number_format_i18n((float) ($queue_entry['hours'] ?? 0), 2)); ?></td>
                                        <td><?php echo esc_html(wp_trim_words((string) ($queue_entry['description'] ?? ''), 18)); ?></td>
                                        <td>
                                            <div class="erp-omd-front-inline-actions">
                                                <form method="post" action="<?php echo esc_url($manager_form_action); ?>">
                                                    <?php wp_nonce_field('erp_omd_front_manager'); ?>
                                                    <input type="hidden" name="erp_omd_front_action" value="approve_time_entry">
                                                    <input type="hidden" name="id" value="<?php echo esc_attr((string) ($queue_entry['id'] ?? 0)); ?>">
                                                    <input type="hidden" name="project_id" value="<?php echo esc_attr((string) ($queue_entry['project_id'] ?? 0)); ?>">
                                                    <button type="submit" class="erp-omd-front-button erp-omd-front-button-primary erp-omd-front-button-small"><?php esc_html_e('Akceptuj', 'erp-omd'); ?></button>
                                                </form>

                                                <form method="post" action="<?php echo esc_url($manager_form_action); ?>">
                                                    <?php wp_nonce_field('erp_omd_front_manager'); ?>
                                                    <input type="hidden" name="erp_omd_front_action" value="reject_time_entry">
                                                    <input type="hidden" name="id" value="<?php echo esc_attr((string) ($queue_entry['id'] ?? 0)); ?>">
                                                    <input type="hidden" name="project_id" value="<?php echo esc_attr((string) ($queue_entry['project_id'] ?? 0)); ?>">
                                                    <button type="submit" class="erp-omd-front-button erp-omd-front-button-small"><?php esc_html_e('Odrzuć', 'erp-omd'); ?></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="7"><?php esc_html_e('Brak wpisów czasu oczekujących na decyzję dla wybranego zakresu projektów.', 'erp-omd'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="erp-omd-front-grid erp-omd-front-grid-manager">
                <article class="erp-omd-front-panel">
                    <div class="erp-omd-front-section-heading">
                        <h2><?php esc_html_e('Nowy wniosek projektowy', 'erp-omd'); ?></h2>
                        <p><?php esc_html_e('Zgłoś inicjację projektu bez omijania właściwego lifecycle projektu. Wniosek trafia do review i może zostać później skonwertowany do projektu.', 'erp-omd'); ?></p>
                    </div>

                    <form method="post" action="<?php echo esc_url($manager_form_action); ?>" class="erp-omd-front-form">
                        <?php wp_nonce_field('erp_omd_front_manager'); ?>
                        <input type="hidden" name="erp_omd_front_action" value="create_project_request">

                        <label for="erp-omd-front-request-client"><?php esc_html_e('Klient', 'erp-omd'); ?></label>
                        <select id="erp-omd-front-request-client" name="client_id" required>
                            <option value="0"><?php esc_html_e('Wybierz klienta', 'erp-omd'); ?></option>
                            <?php foreach ($available_clients as $client_item) : ?>
                                <option value="<?php echo esc_attr((string) $client_item['id']); ?>" <?php selected((int) ($request_form_defaults['client_id'] ?? 0), (int) $client_item['id']); ?>>
                                    <?php echo esc_html($client_item['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="erp-omd-front-request-project-name"><?php esc_html_e('Nazwa projektu', 'erp-omd'); ?></label>
                        <input id="erp-omd-front-request-project-name" type="text" name="project_name" value="<?php echo esc_attr((string) ($request_form_defaults['project_name'] ?? '')); ?>" required>

                        <div class="erp-omd-front-form-row">
                            <div>
                                <label for="erp-omd-front-request-billing-type"><?php esc_html_e('Typ rozliczenia', 'erp-omd'); ?></label>
                                <select id="erp-omd-front-request-billing-type" name="billing_type">
                                    <?php foreach ([
                                        'time_material' => __('Time & Material', 'erp-omd'),
                                        'fixed_price' => __('Fixed price', 'erp-omd'),
                                        'retainer' => __('Retainer', 'erp-omd'),
                                    ] as $billing_type => $billing_label) : ?>
                                        <option value="<?php echo esc_attr($billing_type); ?>" <?php selected((string) ($request_form_defaults['billing_type'] ?? 'time_material'), $billing_type); ?>>
                                            <?php echo esc_html($billing_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="erp-omd-front-request-manager"><?php esc_html_e('Preferowany manager', 'erp-omd'); ?></label>
                                <select id="erp-omd-front-request-manager" name="preferred_manager_id">
                                    <option value="0"><?php esc_html_e('Brak preferencji', 'erp-omd'); ?></option>
                                    <?php foreach ($available_managers as $manager_item) : ?>
                                        <option value="<?php echo esc_attr((string) $manager_item['id']); ?>" <?php selected((int) ($request_form_defaults['preferred_manager_id'] ?? 0), (int) $manager_item['id']); ?>>
                                            <?php echo esc_html($manager_item['user_login']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <label for="erp-omd-front-request-estimate"><?php esc_html_e('Kosztorys (opcjonalnie)', 'erp-omd'); ?></label>
                        <select id="erp-omd-front-request-estimate" name="estimate_id">
                            <option value="0"><?php esc_html_e('Bez kosztorysu', 'erp-omd'); ?></option>
                            <?php foreach ($available_estimates as $estimate_item) : ?>
                                <option value="<?php echo esc_attr((string) $estimate_item['id']); ?>" <?php selected((int) ($request_form_defaults['estimate_id'] ?? 0), (int) $estimate_item['id']); ?>>
                                    <?php echo esc_html(($estimate_item['name'] ?? ('#' . (int) $estimate_item['id'])) . ' · ' . ($estimate_item['client_name'] ?? '—')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="erp-omd-front-request-brief"><?php esc_html_e('Brief / uzasadnienie', 'erp-omd'); ?></label>
                        <textarea id="erp-omd-front-request-brief" name="brief" rows="6" required><?php echo esc_textarea((string) ($request_form_defaults['brief'] ?? '')); ?></textarea>

                        <div class="erp-omd-front-inline-actions">
                            <button type="submit" class="erp-omd-front-button erp-omd-front-button-primary"><?php esc_html_e('Wyślij wniosek', 'erp-omd'); ?></button>
                        </div>
                    </form>
                </article>

                <article class="erp-omd-front-panel">
                    <div class="erp-omd-front-section-heading">
                        <h2><?php esc_html_e('Wnioski projektowe', 'erp-omd'); ?></h2>
                        <p><?php esc_html_e('Lista zgłoszeń widocznych dla Ciebie: własne wnioski oraz wnioski przypisane do Ciebie jako preferowanego managera.', 'erp-omd'); ?></p>
                    </div>

                    <?php if ($project_requests) : ?>
                        <div class="erp-omd-front-stack">
                            <?php foreach ($project_requests as $project_request) : ?>
                                <div class="erp-omd-front-project-card">
                                    <div class="erp-omd-front-project-card-header">
                                        <div>
                                            <strong><?php echo esc_html($project_request['project_name'] ?? ('#' . (int) ($project_request['id'] ?? 0))); ?></strong>
                                            <p>
                                                <?php echo esc_html($project_request['client_name'] ?? '—'); ?>
                                                · <?php echo esc_html($project_request['billing_type'] ?? '—'); ?>
                                                · <?php echo esc_html($project_request['requester_login'] ?? '—'); ?>
                                            </p>
                                        </div>
                                        <span class="erp-omd-front-chip"><?php echo esc_html($project_request['status'] ?? 'new'); ?></span>
                                    </div>

                                    <div class="erp-omd-front-project-card-meta">
                                        <span><?php printf(esc_html__('Preferowany manager: %s', 'erp-omd'), esc_html($project_request['preferred_manager_login'] ?? '—')); ?></span>
                                        <span><?php printf(esc_html__('Kosztorys: %s', 'erp-omd'), (int) ($project_request['estimate_id'] ?? 0) > 0 ? '#' . (int) $project_request['estimate_id'] : __('brak', 'erp-omd')); ?></span>
                                        <?php if (! empty($project_request['converted_project_name'])) : ?>
                                            <span><?php printf(esc_html__('Projekt: %s', 'erp-omd'), esc_html($project_request['converted_project_name'])); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <p class="erp-omd-front-lead"><?php echo esc_html($project_request['brief'] ?? ''); ?></p>

                                    <?php $can_review_request = $this->can_review_project_request($project_request, $employee, $user); ?>
                                    <div class="erp-omd-front-inline-actions">
                                        <?php if ($can_review_request && in_array((string) ($project_request['status'] ?? 'new'), ['new', 'rejected'], true)) : ?>
                                            <form method="post" action="<?php echo esc_url($manager_form_action); ?>">
                                                <?php wp_nonce_field('erp_omd_front_manager'); ?>
                                                <input type="hidden" name="erp_omd_front_action" value="review_project_request">
                                                <input type="hidden" name="request_id" value="<?php echo esc_attr((string) ($project_request['id'] ?? 0)); ?>">
                                                <button type="submit" class="erp-omd-front-button erp-omd-front-button-small"><?php esc_html_e('Przekaż do analizy', 'erp-omd'); ?></button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($can_review_request && in_array((string) ($project_request['status'] ?? 'new'), ['new', 'under_review'], true)) : ?>
                                            <form method="post" action="<?php echo esc_url($manager_form_action); ?>">
                                                <?php wp_nonce_field('erp_omd_front_manager'); ?>
                                                <input type="hidden" name="erp_omd_front_action" value="approve_project_request">
                                                <input type="hidden" name="request_id" value="<?php echo esc_attr((string) ($project_request['id'] ?? 0)); ?>">
                                                <button type="submit" class="erp-omd-front-button erp-omd-front-button-primary erp-omd-front-button-small"><?php esc_html_e('Zatwierdź', 'erp-omd'); ?></button>
                                            </form>

                                            <form method="post" action="<?php echo esc_url($manager_form_action); ?>">
                                                <?php wp_nonce_field('erp_omd_front_manager'); ?>
                                                <input type="hidden" name="erp_omd_front_action" value="reject_project_request">
                                                <input type="hidden" name="request_id" value="<?php echo esc_attr((string) ($project_request['id'] ?? 0)); ?>">
                                                <button type="submit" class="erp-omd-front-button erp-omd-front-button-small"><?php esc_html_e('Odrzuć', 'erp-omd'); ?></button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($can_review_request && (string) ($project_request['status'] ?? '') === 'approved') : ?>
                                            <form method="post" action="<?php echo esc_url($manager_form_action); ?>">
                                                <?php wp_nonce_field('erp_omd_front_manager'); ?>
                                                <input type="hidden" name="erp_omd_front_action" value="convert_project_request">
                                                <input type="hidden" name="request_id" value="<?php echo esc_attr((string) ($project_request['id'] ?? 0)); ?>">
                                                <button type="submit" class="erp-omd-front-button erp-omd-front-button-primary erp-omd-front-button-small"><?php esc_html_e('Konwertuj do projektu', 'erp-omd'); ?></button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (! $can_review_request) : ?>
                                            <span class="erp-omd-front-chip"><?php esc_html_e('Tylko podgląd', 'erp-omd'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p class="erp-omd-front-lead"><?php esc_html_e('Brak widocznych wniosków projektowych.', 'erp-omd'); ?></p>
                    <?php endif; ?>
                </article>
            </div>
        </section>
    </main>
    <?php wp_footer(); ?>
</body>
</html>
