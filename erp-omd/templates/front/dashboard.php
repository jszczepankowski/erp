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

            <?php
            $manager_tabs = [
                'dodaj-wpis' => __('Dodaj wpis', 'erp-omd'),
                'wpisy-godzin' => __('Wpisy godzin', 'erp-omd'),
                'projekty' => __('Projekty', 'erp-omd'),
                'kosztorysy' => __('Kosztorysy', 'erp-omd'),
                'akceptacje' => __('Akceptacje', 'erp-omd'),
                'wnioski' => __('Wnioski', 'erp-omd'),
            ];
            ?>
            <nav class="erp-omd-front-inline-actions erp-omd-front-tabs" aria-label="<?php esc_attr_e('Nawigacja panelu managera', 'erp-omd'); ?>">
                <?php foreach ($manager_tabs as $manager_tab_key => $manager_tab_label) : ?>
                    <button
                        type="button"
                        class="erp-omd-front-button erp-omd-front-button-ghost"
                        data-manager-tab-button="<?php echo esc_attr($manager_tab_key); ?>"
                    >
                        <?php echo esc_html($manager_tab_label); ?>
                    </button>
                <?php endforeach; ?>
            </nav>

            <div class="erp-omd-front-grid erp-omd-front-grid-summary">
                <article class="erp-omd-front-panel" data-collapsible-section="manager-projects">
                    <h2><?php esc_html_e('Twoje konto', 'erp-omd'); ?></h2>
                    <ul>
                        <li><strong><?php esc_html_e('Użytkownik:', 'erp-omd'); ?></strong> <?php echo esc_html($user->user_login); ?></li>
                        <li><strong><?php esc_html_e('Email:', 'erp-omd'); ?></strong> <?php echo esc_html($user->user_email); ?></li>
                        <li><strong><?php esc_html_e('Typ ERP:', 'erp-omd'); ?></strong> <?php echo esc_html($employee['account_type'] ?? '—'); ?></li>
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

            <div class="erp-omd-front-grid erp-omd-front-grid-manager erp-omd-front-grid-manager-full" data-manager-tab-pane="dodaj-wpis">
                <article class="erp-omd-front-panel">
                    <div class="erp-omd-front-section-heading">
                        <h2><?php esc_html_e('Dodaj wpis czasu', 'erp-omd'); ?></h2>
                    </div>
                    <form method="post" action="<?php echo esc_url($manager_form_action); ?>" class="erp-omd-front-form">
                        <?php wp_nonce_field('erp_omd_front_manager'); ?>
                        <input type="hidden" name="erp_omd_front_action" value="save_manager_time_entry">

                        <div class="erp-omd-front-form-row erp-omd-front-form-row-time-context">
                            <div>
                                <label for="erp-omd-manager-time-client"><?php esc_html_e('Klient', 'erp-omd'); ?></label>
                                <select id="erp-omd-manager-time-client" name="client_id" required>
                                    <option value=""><?php esc_html_e('Wybierz klienta', 'erp-omd'); ?></option>
                                    <?php foreach ($manager_time_clients as $manager_time_client) : ?>
                                        <option value="<?php echo esc_attr((string) ($manager_time_client['id'] ?? 0)); ?>" <?php selected((int) ($manager_time_defaults['client_id'] ?? 0), (int) ($manager_time_client['id'] ?? 0)); ?>>
                                            <?php echo esc_html($manager_time_client['name'] ?? '—'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="erp-omd-manager-time-project"><?php esc_html_e('Projekt', 'erp-omd'); ?></label>
                                <select id="erp-omd-manager-time-project" name="project_id" required>
                                    <option value=""><?php esc_html_e('Wybierz projekt klienta', 'erp-omd'); ?></option>
                                    <?php foreach ($manager_time_projects as $manager_time_project) : ?>
                                        <option
                                            value="<?php echo esc_attr((string) ($manager_time_project['id'] ?? 0)); ?>"
                                            data-client-id="<?php echo esc_attr((string) ($manager_time_project['client_id'] ?? 0)); ?>"
                                            <?php selected((int) ($manager_time_defaults['project_id'] ?? 0), (int) ($manager_time_project['id'] ?? 0)); ?>
                                        >
                                            <?php echo esc_html($manager_time_project['name'] ?? '—'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="erp-omd-manager-time-role"><?php esc_html_e('Rola', 'erp-omd'); ?></label>
                                <select id="erp-omd-manager-time-role" name="role_id" required>
                                    <option value=""><?php esc_html_e('Wybierz rolę', 'erp-omd'); ?></option>
                                    <?php foreach ($manager_time_roles as $manager_time_role) : ?>
                                        <option value="<?php echo esc_attr((string) ($manager_time_role['id'] ?? 0)); ?>" <?php selected((int) ($manager_time_defaults['role_id'] ?? 0), (int) ($manager_time_role['id'] ?? 0)); ?>>
                                            <?php echo esc_html($manager_time_role['name'] ?? '—'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="erp-omd-front-form-row">
                            <div>
                                <label for="erp-omd-manager-time-hours"><?php esc_html_e('Godziny', 'erp-omd'); ?></label>
                                <input id="erp-omd-manager-time-hours" name="hours" type="number" min="0.25" step="0.25" value="<?php echo esc_attr((string) ($manager_time_defaults['hours'] ?? '')); ?>" required>
                            </div>
                            <div>
                                <label for="erp-omd-manager-time-entry-date"><?php esc_html_e('Data', 'erp-omd'); ?></label>
                                <input id="erp-omd-manager-time-entry-date" name="entry_date" type="date" value="<?php echo esc_attr((string) ($manager_time_defaults['entry_date'] ?? current_time('Y-m-d'))); ?>" required>
                            </div>
                        </div>

                        <label for="erp-omd-manager-time-description"><?php esc_html_e('Opis pracy', 'erp-omd'); ?></label>
                        <textarea id="erp-omd-manager-time-description" name="description" rows="4" required><?php echo esc_textarea((string) ($manager_time_defaults['description'] ?? '')); ?></textarea>

                        <div class="erp-omd-front-inline-actions">
                            <button type="submit" class="erp-omd-front-button erp-omd-front-button-primary"><?php esc_html_e('Dodaj wpis czasu', 'erp-omd'); ?></button>
                        </div>
                    </form>
                </article>
            </div>

            <div class="erp-omd-front-grid erp-omd-front-grid-manager erp-omd-front-grid-manager-full" data-manager-tab-pane="wpisy-godzin">
                <article class="erp-omd-front-panel">
                    <div class="erp-omd-front-section-heading">
                        <h2><?php esc_html_e('Twoje wpisy czasu', 'erp-omd'); ?></h2>
                    </div>

                    <div class="erp-omd-front-table-wrap">
                        <table class="erp-omd-front-table" data-table-enhanced="1">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Data', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Klient', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Projekt', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Rola', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Godz.', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Status', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Opis', 'erp-omd'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($manager_time_entries) : ?>
                                    <?php foreach ($manager_time_entries as $time_entry) : ?>
                                        <tr>
                                            <td><?php echo esc_html($time_entry['entry_date'] ?? '—'); ?></td>
                                            <td><?php echo esc_html($time_entry['client_name'] ?? '—'); ?></td>
                                            <td><?php echo esc_html($time_entry['project_name'] ?? '—'); ?></td>
                                            <td><?php echo esc_html($time_entry['role_name'] ?? '—'); ?></td>
                                            <td><?php echo esc_html(number_format_i18n((float) ($time_entry['hours'] ?? 0), 2)); ?></td>
                                            <td>
                                                <span class="erp-omd-front-status erp-omd-front-status-<?php echo esc_attr((string) ($time_entry['status'] ?? '')); ?>">
                                                    <?php echo esc_html([
                                                        'submitted' => __('Oczekuje', 'erp-omd'),
                                                        'approved' => __('Zaakceptowany', 'erp-omd'),
                                                        'rejected' => __('Odrzucony', 'erp-omd'),
                                                    ][(string) ($time_entry['status'] ?? '')] ?? (string) ($time_entry['status'] ?? '—')); ?>
                                                </span>
                                            </td>
                                            <td><?php echo esc_html(wp_trim_words((string) ($time_entry['description'] ?? ''), 14)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="7"><?php esc_html_e('Brak wpisów czasu dla bieżącego managera.', 'erp-omd'); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>

            <div class="erp-omd-front-grid erp-omd-front-grid-manager erp-omd-front-grid-manager-full" data-manager-tab-pane="projekty">
                <article class="erp-omd-front-panel">
                    <div class="erp-omd-front-section-heading">
                        <h2><?php esc_html_e('Twoje projekty', 'erp-omd'); ?></h2>
                    </div>

                    <?php if ($managed_projects) : ?>
                        <?php
                        $project_filter_clients = [];
                        $project_filter_statuses = [];
                        $project_filter_billing_types = [];
                        foreach ($managed_projects as $managed_project_row) {
                            $project_filter_clients[(string) ($managed_project_row['client_name'] ?? '—')] = (string) ($managed_project_row['client_name'] ?? '—');
                            $project_filter_statuses[(string) ($managed_project_row['status'] ?? '')] = $this->project_status_label((string) ($managed_project_row['status'] ?? ''));
                            $project_filter_billing_types[(string) ($managed_project_row['billing_type'] ?? '')] = $this->billing_type_label((string) ($managed_project_row['billing_type'] ?? ''));
                        }
                        asort($project_filter_clients);
                        asort($project_filter_statuses);
                        asort($project_filter_billing_types);
                        ?>
                        <form class="erp-omd-front-filter-form erp-omd-front-project-filter-form" data-project-table-filters="1" onsubmit="return false;">
                            <div>
                                <label for="erp-omd-front-projects-filter-client"><?php esc_html_e('Klient', 'erp-omd'); ?></label>
                                <select id="erp-omd-front-projects-filter-client" data-project-filter="client">
                                    <option value=""><?php esc_html_e('Wszyscy klienci', 'erp-omd'); ?></option>
                                    <?php foreach ($project_filter_clients as $project_filter_client_value => $project_filter_client_label) : ?>
                                        <option value="<?php echo esc_attr($project_filter_client_value); ?>"><?php echo esc_html($project_filter_client_label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="erp-omd-front-projects-filter-status"><?php esc_html_e('Status', 'erp-omd'); ?></label>
                                <select id="erp-omd-front-projects-filter-status" data-project-filter="status">
                                    <option value=""><?php esc_html_e('Wszystkie statusy', 'erp-omd'); ?></option>
                                    <?php foreach ($project_filter_statuses as $project_filter_status_value => $project_filter_status_label) : ?>
                                        <option value="<?php echo esc_attr($project_filter_status_value); ?>"><?php echo esc_html($project_filter_status_label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="erp-omd-front-projects-filter-billing"><?php esc_html_e('Typ rozliczenia', 'erp-omd'); ?></label>
                                <select id="erp-omd-front-projects-filter-billing" data-project-filter="billing-type">
                                    <option value=""><?php esc_html_e('Wszystkie typy', 'erp-omd'); ?></option>
                                    <?php foreach ($project_filter_billing_types as $project_filter_billing_value => $project_filter_billing_label) : ?>
                                        <option value="<?php echo esc_attr($project_filter_billing_value); ?>"><?php echo esc_html($project_filter_billing_label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                        <div class="erp-omd-front-table-wrap">
                            <table class="erp-omd-front-table erp-omd-front-table-sortable" data-projects-table="1" data-table-enhanced="1">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('ID', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Klient', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Nazwa', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Typ', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Koszt', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Przychód', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Zysk', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Marża %', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Status', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Zgłoszone wpisy', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Alerty', 'erp-omd'); ?></th>
                                        <th><?php esc_html_e('Akcja', 'erp-omd'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
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
                                        <tr
                                            class="<?php echo $selected_project && (int) ($selected_project['id'] ?? 0) === $project_id ? 'erp-omd-front-table-row-active' : ''; ?>"
                                            data-client="<?php echo esc_attr((string) ($project['client_name'] ?? '—')); ?>"
                                            data-status="<?php echo esc_attr((string) ($project['status'] ?? '')); ?>"
                                            data-billing-type="<?php echo esc_attr((string) ($project['billing_type'] ?? '')); ?>"
                                        >
                                            <td><?php echo esc_html((string) $project_id); ?></td>
                                            <td><?php echo esc_html($project['client_name'] ?? '—'); ?></td>
                                            <td><?php echo esc_html($project['name'] ?? ('#' . $project_id)); ?></td>
                                            <td><?php echo esc_html($this->billing_type_label($project['billing_type'] ?? '')); ?></td>
                                            <td><?php echo esc_html(number_format_i18n((float) ($project_financial['cost'] ?? 0), 2)); ?></td>
                                            <td><?php echo esc_html(number_format_i18n((float) ($project_financial['revenue'] ?? 0), 2)); ?></td>
                                            <td><?php echo esc_html(number_format_i18n((float) ($project_financial['profit'] ?? 0), 2)); ?></td>
                                            <td><?php echo esc_html(number_format_i18n((float) ($project_financial['margin'] ?? 0), 2)); ?></td>
                                            <td><?php echo esc_html($this->project_status_label($project['status'] ?? '')); ?></td>
                                            <td><?php echo esc_html((string) ((int) ($project['pending_entries_count'] ?? 0))); ?></td>
                                            <td><?php echo esc_html((string) count($project_alerts)); ?></td>
                                            <td><a class="erp-omd-front-button erp-omd-front-button-small" href="<?php echo esc_url($project_url); ?>"><?php esc_html_e('Otwórz kartę', 'erp-omd'); ?></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p class="erp-omd-front-lead"><?php esc_html_e('Nie masz jeszcze przypisanych projektów managerskich.', 'erp-omd'); ?></p>
                    <?php endif; ?>
                </article>

                <article id="project-detail" class="erp-omd-front-panel" data-collapsible-section="manager-project-card">
                    <div class="erp-omd-front-section-heading">
                        <h2><?php esc_html_e('Karta projektu', 'erp-omd'); ?></h2>
                    </div>

                    <?php if ($selected_project) : ?>
                        <?php
                        $selected_project_financial = (array) ($selected_project['financial'] ?? []);
                        $selected_project_alerts = (array) ($selected_project['alerts'] ?? []);
                        $selected_project_cost_rows = $this->project_costs->for_project((int) ($selected_project['id'] ?? 0));
                        $selected_project_revenue_rows = $this->project_revenues->for_project((int) ($selected_project['id'] ?? 0));
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
                        <form method="post" action="<?php echo esc_url($manager_form_action); ?>">
                            <?php wp_nonce_field('erp_omd_front_manager'); ?>
                            <input type="hidden" name="erp_omd_front_action" value="update_project_status">
                            <input type="hidden" name="project_id" value="<?php echo esc_attr((string) ($selected_project['id'] ?? 0)); ?>">
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
                                <select id="erp-omd-front-project-status" name="status">
                                    <?php foreach ([
                                        'do_rozpoczecia' => __('Do rozpoczęcia', 'erp-omd'),
                                        'w_realizacji' => __('W realizacji', 'erp-omd'),
                                        'w_akceptacji' => __('W akceptacji', 'erp-omd'),
                                        'do_faktury' => __('Do faktury', 'erp-omd'),
                                        'zakonczony' => __('Zakończony', 'erp-omd'),
                                        'inactive' => __('Nieaktywny', 'erp-omd'),
                                    ] as $project_status => $project_status_label) : ?>
                                        <option value="<?php echo esc_attr($project_status); ?>" <?php selected((string) ($selected_project['status'] ?? ''), $project_status); ?>>
                                            <?php echo esc_html($project_status_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="erp-omd-front-detail-item">
                                <strong><?php esc_html_e('Typ rozliczenia', 'erp-omd'); ?></strong>
                                <span><?php echo esc_html($this->billing_type_label((string) ($selected_project['billing_type'] ?? ''))); ?></span>
                            </div>
                            <div class="erp-omd-front-detail-item">
                                <strong><?php esc_html_e('Budżet', 'erp-omd'); ?></strong>
                                <span><?php echo esc_html(number_format_i18n((float) ($selected_project['budget'] ?? 0), 2)); ?></span>
                            </div>
                            <div class="erp-omd-front-detail-item">
                                <strong><?php esc_html_e('Managerowie', 'erp-omd'); ?></strong>
                                <span><?php echo esc_html(($selected_project['manager_logins_display'] ?? '') !== '' ? $selected_project['manager_logins_display'] : ($selected_project['manager_login'] ?? '—')); ?></span>
                            </div>
                            <div class="erp-omd-front-detail-item">
                                <strong><?php esc_html_e('Zapis statusu', 'erp-omd'); ?></strong>
                                <button type="submit" class="erp-omd-front-button erp-omd-front-button-primary"><?php esc_html_e('Zapisz status', 'erp-omd'); ?></button>
                            </div>
                            </div>
                        </form>

                        <div class="erp-omd-front-metrics">
                            <div class="erp-omd-front-metric erp-omd-front-metric-revenue">
                                <span class="erp-omd-front-metric-label"><?php esc_html_e('Przychód', 'erp-omd'); ?></span>
                                <strong><?php echo esc_html(number_format_i18n((float) ($selected_project_financial['revenue'] ?? 0), 2)); ?></strong>
                            </div>
                            <div class="erp-omd-front-metric erp-omd-front-metric-cost">
                                <span class="erp-omd-front-metric-label"><?php esc_html_e('Koszt', 'erp-omd'); ?></span>
                                <strong><?php echo esc_html(number_format_i18n((float) ($selected_project_financial['cost'] ?? 0), 2)); ?></strong>
                            </div>
                            <div class="erp-omd-front-metric">
                                <span class="erp-omd-front-metric-label"><?php esc_html_e('Koszt czasu pracy', 'erp-omd'); ?></span>
                                <strong><?php echo esc_html(number_format_i18n((float) ($selected_project_financial['time_cost'] ?? 0), 2)); ?></strong>
                            </div>
                            <div class="erp-omd-front-metric erp-omd-front-metric-profit">
                                <span class="erp-omd-front-metric-label"><?php esc_html_e('Zysk', 'erp-omd'); ?></span>
                                <strong><?php echo esc_html(number_format_i18n((float) ($selected_project_financial['profit'] ?? 0), 2)); ?></strong>
                            </div>
                            <div class="erp-omd-front-metric">
                                <span class="erp-omd-front-metric-label"><?php esc_html_e('Marża %', 'erp-omd'); ?></span>
                                <strong><?php echo esc_html(number_format_i18n((float) ($selected_project_financial['margin'] ?? 0), 2)); ?></strong>
                            </div>
                        </div>

                        <div class="erp-omd-front-panel erp-omd-front-panel-subtle">
                            <div class="erp-omd-front-section-heading">
                                <h3><?php esc_html_e('Dodaj koszt projektu', 'erp-omd'); ?></h3>
                            </div>
                            <form method="post" action="<?php echo esc_url($manager_form_action); ?>" class="erp-omd-front-form">
                                <?php wp_nonce_field('erp_omd_front_manager'); ?>
                                <input type="hidden" name="erp_omd_front_action" value="add_project_cost">
                                <input type="hidden" name="project_id" value="<?php echo esc_attr((string) ($selected_project['id'] ?? 0)); ?>">
                                <div class="erp-omd-front-form-row">
                                    <div>
                                        <label for="erp-omd-front-project-cost-amount"><?php esc_html_e('Kwota', 'erp-omd'); ?></label>
                                        <input id="erp-omd-front-project-cost-amount" type="number" min="0" step="0.01" name="amount" value="0" required>
                                    </div>
                                    <div>
                                        <label for="erp-omd-front-project-cost-date"><?php esc_html_e('Data kosztu', 'erp-omd'); ?></label>
                                        <input id="erp-omd-front-project-cost-date" type="date" name="cost_date" value="<?php echo esc_attr(current_time('Y-m-d')); ?>" required>
                                    </div>
                                </div>
                                <label for="erp-omd-front-project-cost-description"><?php esc_html_e('Opis', 'erp-omd'); ?></label>
                                <textarea id="erp-omd-front-project-cost-description" name="description" rows="3" required></textarea>
                                <div class="erp-omd-front-inline-actions">
                                    <button type="submit" class="erp-omd-front-button erp-omd-front-button-primary"><?php esc_html_e('Dodaj koszt', 'erp-omd'); ?></button>
                                </div>
                            </form>
                            <div class="erp-omd-front-table-wrap">
                                <table class="erp-omd-front-table" data-table-enhanced="1">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Data', 'erp-omd'); ?></th>
                                            <th><?php esc_html_e('Kwota', 'erp-omd'); ?></th>
                                            <th><?php esc_html_e('Opis', 'erp-omd'); ?></th>
                                            <th><?php esc_html_e('Akcje', 'erp-omd'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><?php echo esc_html(current_time('Y-m-d')); ?></td>
                                            <td><?php echo esc_html(number_format_i18n((float) ($selected_project_financial['time_cost'] ?? 0), 2)); ?></td>
                                            <td><?php esc_html_e('Koszt pracy', 'erp-omd'); ?></td>
                                            <td>&mdash;</td>
                                        </tr>
                                        <?php if (empty($selected_project_cost_rows)) : ?>
                                            <tr>
                                                <td colspan="4"><?php esc_html_e('Brak kosztów projektu.', 'erp-omd'); ?></td>
                                            </tr>
                                        <?php else : ?>
                                            <?php foreach ($selected_project_cost_rows as $project_cost_row) : ?>
                                                <tr>
                                                    <td><?php echo esc_html($project_cost_row['cost_date'] ?? '—'); ?></td>
                                                    <td><?php echo esc_html(number_format_i18n((float) ($project_cost_row['amount'] ?? 0), 2)); ?></td>
                                                    <td><?php echo esc_html($project_cost_row['description'] ?? ''); ?></td>
                                                    <td>
                                                        <form method="post" action="<?php echo esc_url($manager_form_action); ?>" onsubmit="return window.confirm('<?php echo esc_js(__('Usunąć koszt projektu?', 'erp-omd')); ?>');">
                                                            <?php wp_nonce_field('erp_omd_front_manager'); ?>
                                                            <input type="hidden" name="erp_omd_front_action" value="delete_project_cost">
                                                            <input type="hidden" name="project_id" value="<?php echo esc_attr((string) ($selected_project['id'] ?? 0)); ?>">
                                                            <input type="hidden" name="project_cost_id" value="<?php echo esc_attr((string) ($project_cost_row['id'] ?? 0)); ?>">
                                                            <button type="submit" class="erp-omd-front-button erp-omd-front-button-small"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php if ((string) ($selected_project['billing_type'] ?? '') === 'mixed') : ?>
                        <div class="erp-omd-front-panel erp-omd-front-panel-subtle">
                            <div class="erp-omd-front-section-heading">
                                <h3><?php esc_html_e('Dodaj pozycję przychodową', 'erp-omd'); ?></h3>
                            </div>
                            <form method="post" action="<?php echo esc_url($manager_form_action); ?>" class="erp-omd-front-form">
                                <?php wp_nonce_field('erp_omd_front_manager'); ?>
                                <input type="hidden" name="erp_omd_front_action" value="add_project_revenue">
                                <input type="hidden" name="project_id" value="<?php echo esc_attr((string) ($selected_project['id'] ?? 0)); ?>">
                                <div class="erp-omd-front-form-row">
                                    <div>
                                        <label for="erp-omd-front-project-revenue-amount"><?php esc_html_e('Kwota', 'erp-omd'); ?></label>
                                        <input id="erp-omd-front-project-revenue-amount" type="number" min="0" step="0.01" name="amount" value="0" required>
                                    </div>
                                    <div>
                                        <label for="erp-omd-front-project-revenue-date"><?php esc_html_e('Data pozycji', 'erp-omd'); ?></label>
                                        <input id="erp-omd-front-project-revenue-date" type="date" name="revenue_date" value="<?php echo esc_attr(current_time('Y-m-d')); ?>" required>
                                    </div>
                                </div>
                                <label for="erp-omd-front-project-revenue-description"><?php esc_html_e('Opis', 'erp-omd'); ?></label>
                                <textarea id="erp-omd-front-project-revenue-description" name="description" rows="3" required></textarea>
                                <div class="erp-omd-front-inline-actions">
                                    <button type="submit" class="erp-omd-front-button erp-omd-front-button-primary"><?php esc_html_e('Dodaj pozycję przychodową', 'erp-omd'); ?></button>
                                </div>
                            </form>
                            <div class="erp-omd-front-table-wrap">
                                <table class="erp-omd-front-table" data-table-enhanced="1">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Data', 'erp-omd'); ?></th>
                                            <th><?php esc_html_e('Kwota', 'erp-omd'); ?></th>
                                            <th><?php esc_html_e('Opis', 'erp-omd'); ?></th>
                                            <th><?php esc_html_e('Akcje', 'erp-omd'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($selected_project_revenue_rows)) : ?>
                                            <tr>
                                                <td colspan="4"><?php esc_html_e('Brak pozycji przychodowych projektu.', 'erp-omd'); ?></td>
                                            </tr>
                                        <?php else : ?>
                                            <?php foreach ($selected_project_revenue_rows as $project_revenue_row) : ?>
                                                <tr>
                                                    <td><?php echo esc_html($project_revenue_row['revenue_date'] ?? '—'); ?></td>
                                                    <td><?php echo esc_html(number_format_i18n((float) ($project_revenue_row['amount'] ?? 0), 2)); ?></td>
                                                    <td><?php echo esc_html($project_revenue_row['description'] ?? ''); ?></td>
                                                    <td>
                                                        <form method="post" action="<?php echo esc_url($manager_form_action); ?>" onsubmit="return window.confirm('<?php echo esc_js(__('Usunąć pozycję przychodową projektu?', 'erp-omd')); ?>');">
                                                            <?php wp_nonce_field('erp_omd_front_manager'); ?>
                                                            <input type="hidden" name="erp_omd_front_action" value="delete_project_revenue">
                                                            <input type="hidden" name="project_id" value="<?php echo esc_attr((string) ($selected_project['id'] ?? 0)); ?>">
                                                            <input type="hidden" name="project_revenue_id" value="<?php echo esc_attr((string) ($project_revenue_row['id'] ?? 0)); ?>">
                                                            <button type="submit" class="erp-omd-front-button erp-omd-front-button-small"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="erp-omd-front-grid erp-omd-front-grid-summary">
                            <div class="erp-omd-front-panel erp-omd-front-panel-subtle">
                                <div class="erp-omd-front-section-heading">
                                    <h3><?php esc_html_e('Alerty projektu', 'erp-omd'); ?></h3>                                </div>
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

            <div class="erp-omd-front-grid erp-omd-front-grid-manager erp-omd-front-grid-manager-thirty-seventy erp-omd-front-estimates-section" data-manager-tab-pane="kosztorysy">
                <article class="erp-omd-front-panel erp-omd-front-panel-form" data-collapsible-section="manager-new-estimate">
                    <div class="erp-omd-front-section-heading">
                        <h2><?php esc_html_e('Nowy kosztorys', 'erp-omd'); ?></h2>
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
                                <h3><?php esc_html_e('Pozycje kosztorysu', 'erp-omd'); ?></h3>
                            </div>

                            <div id="erp-omd-front-estimate-items">
                                <div class="erp-omd-front-estimate-item-row" data-item-row="1">
                                    <label><?php esc_html_e('Nazwa pozycji', 'erp-omd'); ?></label>
                                    <input type="text" name="item_name[]" required>

                                    <div class="erp-omd-front-form-row">
                                        <div>
                                            <label><?php esc_html_e('Ilość', 'erp-omd'); ?></label>
                                            <input type="number" min="0.01" step="0.01" name="item_qty[]" value="1" required>
                                        </div>
                                        <div>
                                            <label><?php esc_html_e('Cena sprzedaży', 'erp-omd'); ?></label>
                                            <input type="number" min="0" step="0.01" name="item_price[]" value="0" required>
                                        </div>
                                    </div>

                                    <label><?php esc_html_e('Koszt wewnętrzny pozycji (całkowity)', 'erp-omd'); ?></label>
                                    <input type="number" min="0" step="0.01" name="item_cost_internal[]" value="0" required>

                                    <label><?php esc_html_e('Komentarz', 'erp-omd'); ?></label>
                                    <textarea name="item_comment[]" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="erp-omd-front-inline-actions">
                                <button type="button" class="erp-omd-front-button erp-omd-front-button-ghost" id="erp-omd-front-add-item"><?php esc_html_e('Dodaj pozycję', 'erp-omd'); ?></button>
                            </div>
                            <div class="erp-omd-front-metrics erp-omd-front-metrics-estimate-totals">
                                <div class="erp-omd-front-metric">
                                    <span class="erp-omd-front-metric-label"><?php esc_html_e('Suma netto', 'erp-omd'); ?></span>
                                    <strong id="erp-omd-front-estimate-net">0.00</strong>
                                </div>
                                <div class="erp-omd-front-metric">
                                    <span class="erp-omd-front-metric-label"><?php esc_html_e('VAT 23%', 'erp-omd'); ?></span>
                                    <strong id="erp-omd-front-estimate-tax">0.00</strong>
                                </div>
                                <div class="erp-omd-front-metric">
                                    <span class="erp-omd-front-metric-label"><?php esc_html_e('Suma brutto', 'erp-omd'); ?></span>
                                    <strong id="erp-omd-front-estimate-gross">0.00</strong>
                                </div>
                            </div>
                        </div>

                        <div class="erp-omd-front-inline-actions">
                            <button type="submit" class="erp-omd-front-button erp-omd-front-button-primary"><?php esc_html_e('Utwórz kosztorys', 'erp-omd'); ?></button>
                        </div>
                    </form>
                </article>

                <article id="estimate-detail" class="erp-omd-front-panel" data-collapsible-section="manager-estimates">
                    <div class="erp-omd-front-section-heading">
                        <h2><?php esc_html_e('Kosztorysy', 'erp-omd'); ?></h2>
                    </div>
                    <form method="get" action="<?php echo esc_url($front_manager_url); ?>" class="erp-omd-front-inline-actions">
                        <input type="hidden" name="project_id" value="<?php echo esc_attr((string) ($selected_project['id'] ?? 0)); ?>">
                        <input type="hidden" name="estimate_id" value="<?php echo esc_attr((string) ($selected_estimate['id'] ?? 0)); ?>">
                        <input type="hidden" name="tab" value="kosztorysy">
                        <label for="erp-omd-front-estimate-status-filter"><?php esc_html_e('Filtr statusu', 'erp-omd'); ?></label>
                        <select id="erp-omd-front-estimate-status-filter" name="estimate_status">
                            <option value=""><?php esc_html_e('Wszystkie statusy', 'erp-omd'); ?></option>
                            <?php foreach (['wstepny', 'do_akceptacji', 'zaakceptowany'] as $status_option) : ?>
                                <option value="<?php echo esc_attr($status_option); ?>" <?php selected((string) ($estimate_status_filter ?? ''), $status_option); ?>><?php echo esc_html($status_option); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="erp-omd-front-button erp-omd-front-button-small"><?php esc_html_e('Filtruj', 'erp-omd'); ?></button>
                    </form>

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
                                        'estimate_status' => (string) ($estimate_status_filter ?? ''),
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
                            <?php
                            $selected_estimate_totals = (array) ($selected_estimate['totals'] ?? []);
                            $selected_estimate_profit = (float) ($selected_estimate_totals['net'] ?? 0) - (float) ($selected_estimate_totals['internal_cost'] ?? 0);
                            ?>
                            <div class="erp-omd-front-panel erp-omd-front-panel-subtle erp-omd-front-estimate-summary">
                                <div class="erp-omd-front-section-heading">
                                    <h3><?php echo esc_html($selected_estimate['name'] ?? ('#' . (int) ($selected_estimate['id'] ?? 0))); ?></h3>
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
                                    <div class="erp-omd-front-metric">
                                        <span class="erp-omd-front-metric-label"><?php esc_html_e('Zysk', 'erp-omd'); ?></span>
                                        <strong><?php echo esc_html(number_format_i18n($selected_estimate_profit, 2)); ?></strong>
                                    </div>
                                </div>

                                <div class="erp-omd-front-inline-actions">
                                    <form method="post" action="<?php echo esc_url($manager_form_action); ?>" class="erp-omd-front-inline-form">
                                        <?php wp_nonce_field('erp_omd_front_manager'); ?>
                                        <input type="hidden" name="erp_omd_front_action" value="export_estimate_csv">
                                        <input type="hidden" name="estimate_id" value="<?php echo esc_attr((string) ($selected_estimate['id'] ?? 0)); ?>">
                                        <button type="submit" class="erp-omd-front-button erp-omd-front-button-ghost"><?php esc_html_e('Eksport CSV dla klienta', 'erp-omd'); ?></button>
                                    </form>
                                </div>
                                <div class="erp-omd-front-panel erp-omd-front-panel-subtle">
                                    <div class="erp-omd-front-section-heading">
                                        <h3><?php esc_html_e('Edycja kosztorysu', 'erp-omd'); ?></h3>
                                    </div>
                                    <form method="post" action="<?php echo esc_url($manager_form_action); ?>" class="erp-omd-front-form">
                                        <?php wp_nonce_field('erp_omd_front_manager'); ?>
                                        <input type="hidden" name="erp_omd_front_action" value="update_estimate_status">
                                        <input type="hidden" name="estimate_id" value="<?php echo esc_attr((string) ($selected_estimate['id'] ?? 0)); ?>">

                                        <div class="erp-omd-front-detail-grid">
                                            <div class="erp-omd-front-detail-item">
                                                <strong><?php esc_html_e('Status kosztorysu', 'erp-omd'); ?></strong>
                                                <select id="erp-omd-front-edit-estimate-status" name="status">
                                                    <?php foreach (['wstepny' => __('Wstępny', 'erp-omd'), 'do_akceptacji' => __('Do akceptacji', 'erp-omd'), 'zaakceptowany' => __('Zaakceptowany', 'erp-omd')] as $estimate_status => $estimate_status_label) : ?>
                                                        <option value="<?php echo esc_attr($estimate_status); ?>" <?php selected((string) ($selected_estimate['status'] ?? ''), $estimate_status); ?>>
                                                            <?php echo esc_html($estimate_status_label); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="erp-omd-front-detail-item">
                                                <strong><?php esc_html_e('Zapis statusu', 'erp-omd'); ?></strong>
                                                <button type="submit" class="erp-omd-front-button erp-omd-front-button-primary"><?php esc_html_e('Zapisz status kosztorysu', 'erp-omd'); ?></button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="erp-omd-front-table-wrap">
                                    <form method="post" action="<?php echo esc_url($manager_form_action); ?>" class="erp-omd-front-form">
                                        <?php wp_nonce_field('erp_omd_front_manager'); ?>
                                        <input type="hidden" name="erp_omd_front_action" value="save_estimate_items">
                                        <input type="hidden" name="estimate_id" value="<?php echo esc_attr((string) ($selected_estimate['id'] ?? 0)); ?>">
                                        <table class="erp-omd-front-table" data-table-enhanced="1">
                                            <thead>
                                                <tr>
                                                    <th><?php esc_html_e('Pozycja', 'erp-omd'); ?></th>
                                                    <th><?php esc_html_e('Ilość', 'erp-omd'); ?></th>
                                                    <th><?php esc_html_e('Cena', 'erp-omd'); ?></th>
                                                    <th><?php esc_html_e('Koszt wewnętrzny', 'erp-omd'); ?></th>
                                                    <th><?php esc_html_e('Zysk', 'erp-omd'); ?></th>
                                                    <th><?php esc_html_e('Komentarz', 'erp-omd'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ((array) ($selected_estimate['items'] ?? []) as $estimate_item) : ?>
                                                    <?php
                                                    $item_qty = (float) ($estimate_item['qty'] ?? 0);
                                                    $item_price = (float) ($estimate_item['price'] ?? 0);
                                                    $item_cost_internal = (float) ($estimate_item['cost_internal'] ?? 0);
                                                    $item_profit = ($item_qty * $item_price) - $item_cost_internal;
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <input type="hidden" name="item_id[]" value="<?php echo esc_attr((string) ($estimate_item['id'] ?? 0)); ?>">
                                                            <input type="text" name="item_name[]" value="<?php echo esc_attr((string) ($estimate_item['name'] ?? '')); ?>" <?php echo (string) ($selected_estimate['status'] ?? '') === 'zaakceptowany' ? 'readonly' : ''; ?> required>
                                                        </td>
                                                        <td><input type="number" min="0.01" step="0.01" name="item_qty[]" value="<?php echo esc_attr((string) $item_qty); ?>" <?php echo (string) ($selected_estimate['status'] ?? '') === 'zaakceptowany' ? 'readonly' : ''; ?> required></td>
                                                        <td><input type="number" min="0" step="0.01" name="item_price[]" value="<?php echo esc_attr((string) $item_price); ?>" <?php echo (string) ($selected_estimate['status'] ?? '') === 'zaakceptowany' ? 'readonly' : ''; ?> required></td>
                                                        <td><input type="number" min="0" step="0.01" name="item_cost_internal[]" value="<?php echo esc_attr((string) $item_cost_internal); ?>" <?php echo (string) ($selected_estimate['status'] ?? '') === 'zaakceptowany' ? 'readonly' : ''; ?> required></td>
                                                        <td><?php echo esc_html(number_format_i18n($item_profit, 2)); ?></td>
                                                        <td><textarea name="item_comment[]" rows="2" <?php echo (string) ($selected_estimate['status'] ?? '') === 'zaakceptowany' ? 'readonly' : ''; ?>><?php echo esc_textarea((string) ($estimate_item['comment'] ?? '')); ?></textarea></td>
                                                    </tr>
                                                <?php endforeach; ?>

                                                <?php if (($selected_estimate['status'] ?? '') !== 'zaakceptowany') : ?>
                                                    <tr>
                                                        <td>
                                                            <input type="hidden" name="item_id[]" value="0">
                                                            <input type="text" name="item_name[]" value="" placeholder="<?php esc_attr_e('Nowa pozycja', 'erp-omd'); ?>">
                                                        </td>
                                                        <td><input type="number" min="0.01" step="0.01" name="item_qty[]" value=""></td>
                                                        <td><input type="number" min="0" step="0.01" name="item_price[]" value=""></td>
                                                        <td><input type="number" min="0" step="0.01" name="item_cost_internal[]" value=""></td>
                                                        <td>—</td>
                                                        <td><textarea name="item_comment[]" rows="2"></textarea></td>
                                                    </tr>
                                                <?php endif; ?>

                                                <?php if (empty($selected_estimate['items']) && (string) ($selected_estimate['status'] ?? '') === 'zaakceptowany') : ?>
                                                    <tr>
                                                        <td colspan="6"><?php esc_html_e('Ten kosztorys nie ma jeszcze pozycji.', 'erp-omd'); ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>

                                        <?php if (($selected_estimate['status'] ?? '') !== 'zaakceptowany') : ?>
                                            <div class="erp-omd-front-inline-actions erp-omd-front-estimate-actions">
                                                <button type="submit" class="erp-omd-front-button erp-omd-front-button-secondary"><?php esc_html_e('Zapisz pozycje', 'erp-omd'); ?></button>
                                            </div>
                                        <?php endif; ?>
                                    </form>
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
                            </div>
                        <?php endif; ?>
                    <?php else : ?>
                        <p class="erp-omd-front-lead"><?php esc_html_e('Brak kosztorysów widocznych dla Twojego zakresu odpowiedzialności.', 'erp-omd'); ?></p>
                    <?php endif; ?>
                </article>
            </div>

            <div class="erp-omd-front-panel" data-collapsible-section="manager-approval-queue" data-manager-tab-pane="akceptacje">
                <div class="erp-omd-front-section-heading">
                    <h2><?php esc_html_e('Kolejka wpisów czasu do akceptacji', 'erp-omd'); ?></h2>
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

                <?php
                $approval_queue_employee_filters = [];
                $approval_queue_project_filters = [];
                $approval_queue_role_filters = [];
                foreach ($approval_queue as $approval_queue_entry_filter) {
                    $approval_queue_employee_value = (string) ($approval_queue_entry_filter['employee_login'] ?? '—');
                    $approval_queue_project_value = (string) ($approval_queue_entry_filter['project_name'] ?? '—');
                    $approval_queue_role_value = (string) ($approval_queue_entry_filter['role_name'] ?? '—');
                    $approval_queue_employee_filters[$approval_queue_employee_value] = $approval_queue_employee_value;
                    $approval_queue_project_filters[$approval_queue_project_value] = $approval_queue_project_value;
                    $approval_queue_role_filters[$approval_queue_role_value] = $approval_queue_role_value;
                }
                asort($approval_queue_employee_filters);
                asort($approval_queue_project_filters);
                asort($approval_queue_role_filters);
                ?>

                <form class="erp-omd-front-filter-form" data-approval-queue-filters="1" onsubmit="return false;">
                    <div>
                        <label for="erp-omd-front-queue-filter-employee"><?php esc_html_e('Pracownik', 'erp-omd'); ?></label>
                        <select id="erp-omd-front-queue-filter-employee" data-queue-filter="employee">
                            <option value=""><?php esc_html_e('Wszyscy pracownicy', 'erp-omd'); ?></option>
                            <?php foreach ($approval_queue_employee_filters as $approval_queue_employee_option) : ?>
                                <option value="<?php echo esc_attr($approval_queue_employee_option); ?>"><?php echo esc_html($approval_queue_employee_option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="erp-omd-front-queue-filter-project"><?php esc_html_e('Projekt', 'erp-omd'); ?></label>
                        <select id="erp-omd-front-queue-filter-project" data-queue-filter="project">
                            <option value=""><?php esc_html_e('Wszystkie projekty', 'erp-omd'); ?></option>
                            <?php foreach ($approval_queue_project_filters as $approval_queue_project_option) : ?>
                                <option value="<?php echo esc_attr($approval_queue_project_option); ?>"><?php echo esc_html($approval_queue_project_option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="erp-omd-front-queue-filter-role"><?php esc_html_e('Rola', 'erp-omd'); ?></label>
                        <select id="erp-omd-front-queue-filter-role" data-queue-filter="role">
                            <option value=""><?php esc_html_e('Wszystkie role', 'erp-omd'); ?></option>
                            <?php foreach ($approval_queue_role_filters as $approval_queue_role_option) : ?>
                                <option value="<?php echo esc_attr($approval_queue_role_option); ?>"><?php echo esc_html($approval_queue_role_option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <div class="erp-omd-front-table-wrap">
                    <table class="erp-omd-front-table erp-omd-front-table-sortable" data-approval-queue-table="1" data-table-enhanced="1">
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
                                    <tr
                                        data-queue-employee="<?php echo esc_attr((string) ($queue_entry['employee_login'] ?? '—')); ?>"
                                        data-queue-project="<?php echo esc_attr((string) ($queue_entry['project_name'] ?? '—')); ?>"
                                        data-queue-role="<?php echo esc_attr((string) ($queue_entry['role_name'] ?? '—')); ?>"
                                    >
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

            <div class="erp-omd-front-grid erp-omd-front-grid-manager erp-omd-front-grid-manager-third" data-manager-tab-pane="wnioski">
                <article class="erp-omd-front-panel" data-collapsible-section="manager-new-request">
                    <div class="erp-omd-front-section-heading">
                        <h2><?php esc_html_e('Nowy wniosek projektowy', 'erp-omd'); ?></h2>
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
                                        'time_material' => __('Godzinowy', 'erp-omd'),
                                        'fixed_price' => __('Ryczałt', 'erp-omd'),
                                        'retainer' => __('Abonament', 'erp-omd'),
                                        'mixed' => __('Hybryda (ryczałt + godziny)', 'erp-omd'),
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

                        <div class="erp-omd-front-form-row">
                            <div>
                                <label for="erp-omd-front-request-start-date"><?php esc_html_e('Data rozpoczęcia', 'erp-omd'); ?></label>
                                <input id="erp-omd-front-request-start-date" type="date" name="start_date" value="<?php echo esc_attr((string) ($request_form_defaults['start_date'] ?? '')); ?>">
                            </div>
                            <div>
                                <label for="erp-omd-front-request-end-date"><?php esc_html_e('Data zakończenia', 'erp-omd'); ?></label>
                                <input id="erp-omd-front-request-end-date" type="date" name="end_date" value="<?php echo esc_attr((string) ($request_form_defaults['end_date'] ?? '')); ?>">
                            </div>
                        </div>

                        <label for="erp-omd-front-request-brief"><?php esc_html_e('Brief / uzasadnienie', 'erp-omd'); ?></label>
                        <textarea id="erp-omd-front-request-brief" name="brief" rows="6" required><?php echo esc_textarea((string) ($request_form_defaults['brief'] ?? '')); ?></textarea>

                        <div class="erp-omd-front-form-row">
                            <div>
                                <label for="erp-omd-front-request-start-date"><?php esc_html_e('Data rozpoczęcia', 'erp-omd'); ?></label>
                                <input id="erp-omd-front-request-start-date" type="date" name="start_date" value="<?php echo esc_attr((string) ($request_form_defaults['start_date'] ?? '')); ?>">
                            </div>
                            <div>
                                <label for="erp-omd-front-request-end-date"><?php esc_html_e('Data zakończenia', 'erp-omd'); ?></label>
                                <input id="erp-omd-front-request-end-date" type="date" name="end_date" value="<?php echo esc_attr((string) ($request_form_defaults['end_date'] ?? '')); ?>">
                            </div>
                        </div>

                        <div class="erp-omd-front-inline-actions">
                            <button type="submit" class="erp-omd-front-button erp-omd-front-button-primary"><?php esc_html_e('Wyślij wniosek', 'erp-omd'); ?></button>
                        </div>
                    </form>
                </article>

                <article class="erp-omd-front-panel">
                    <div class="erp-omd-front-section-heading">
                        <h2><?php esc_html_e('Wnioski projektowe', 'erp-omd'); ?></h2>
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
                                                · <?php echo esc_html($this->billing_type_label((string) ($project_request['billing_type'] ?? ''))); ?>
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
    <script src="<?php echo esc_url(ERP_OMD_URL . 'assets/js/front-shared.js?ver=' . ERP_OMD_VERSION); ?>"></script>
    <script>
    (function () {
        if (window.erpOmdFrontShared && typeof window.erpOmdFrontShared.dedupeProjectRequestDateFields === 'function') {
            window.erpOmdFrontShared.dedupeProjectRequestDateFields();
        }

        var setupManagerTabs = function () {
            var storageKey = 'erp_omd_front_manager_active_tab';
            var allowedTabs = ['dodaj-wpis', 'wpisy-godzin', 'projekty', 'kosztorysy', 'akceptacje', 'wnioski'];
            var params = new URLSearchParams(window.location.search);
            var urlTab = params.get('manager_tab');
            var storedTab = localStorage.getItem(storageKey);
            var activeTab = allowedTabs.indexOf(urlTab) !== -1 ? urlTab : (allowedTabs.indexOf(storedTab) !== -1 ? storedTab : 'projekty');

            localStorage.setItem(storageKey, activeTab);
            if (allowedTabs.indexOf(urlTab) === -1) {
                params.set('manager_tab', activeTab);
                history.replaceState({}, '', window.location.pathname + '?' + params.toString() + window.location.hash);
            }

            document.querySelectorAll('[data-manager-tab-pane]').forEach(function (panel) {
                panel.hidden = panel.getAttribute('data-manager-tab-pane') !== activeTab;
            });

            document.querySelectorAll('[data-manager-tab-button]').forEach(function (button) {
                var isActive = button.getAttribute('data-manager-tab-button') === activeTab;
                button.classList.toggle('erp-omd-front-button-primary', isActive);
                button.classList.toggle('erp-omd-front-button-ghost', !isActive);
                button.setAttribute('aria-current', isActive ? 'page' : 'false');
                button.addEventListener('click', function () {
                    var nextTab = button.getAttribute('data-manager-tab-button');
                    if (allowedTabs.indexOf(nextTab) === -1) {
                        return;
                    }
                    localStorage.setItem(storageKey, nextTab);
                    params.set('manager_tab', nextTab);
                    history.replaceState({}, '', window.location.pathname + '?' + params.toString() + window.location.hash);
                    document.querySelectorAll('[data-manager-tab-pane]').forEach(function (panel) {
                        panel.hidden = panel.getAttribute('data-manager-tab-pane') !== nextTab;
                    });
                    document.querySelectorAll('[data-manager-tab-button]').forEach(function (candidate) {
                        var candidateActive = candidate.getAttribute('data-manager-tab-button') === nextTab;
                        candidate.classList.toggle('erp-omd-front-button-primary', candidateActive);
                        candidate.classList.toggle('erp-omd-front-button-ghost', !candidateActive);
                        candidate.setAttribute('aria-current', candidateActive ? 'page' : 'false');
                    });
                });
            });
        };

        var setupTableEnhancements = function () {
            var parseSortableValue = function (value) {
                var normalized = (value || '').replace(/\s+/g, ' ').trim();
                var numeric = normalized.replace(',', '.').replace(/[^\d.-]/g, '');
                return numeric !== '' && !Number.isNaN(Number(numeric)) ? Number(numeric) : normalized.toLowerCase();
            };

            document.querySelectorAll('table[data-table-enhanced="1"]').forEach(function (table) {
                var tbody = table.querySelector('tbody');
                if (!tbody) {
                    return;
                }

                var allRows = Array.from(tbody.querySelectorAll('tr'));
                if (allRows.length === 0) {
                    return;
                }

                var wrap = table.closest('.erp-omd-front-table-wrap');
                var controls = document.createElement('div');
                controls.className = 'erp-omd-front-table-tools';
                controls.innerHTML =
                    '<label class="erp-omd-front-table-search">' +
                        '<span><?php echo esc_js(__('Szukaj:', 'erp-omd')); ?></span>' +
                        '<input type="search" class="erp-omd-front-table-search-input" placeholder="<?php echo esc_js(__('np. klient / projekt / status', 'erp-omd')); ?>">' +
                    '</label>' +
                    '<label class="erp-omd-front-table-size">' +
                        '<span><?php echo esc_js(__('Widok:', 'erp-omd')); ?></span>' +
                        '<select class="erp-omd-front-table-size-select">' +
                            '<option value="25">25</option>' +
                            '<option value="50">50</option>' +
                            '<option value="100" selected>100</option>' +
                            '<option value="200">200</option>' +
                        '</select>' +
                    '</label>' +
                    '<div class="erp-omd-front-table-pagination">' +
                        '<button type="button" class="erp-omd-front-button erp-omd-front-button-small erp-omd-front-table-prev">←</button>' +
                        '<span class="erp-omd-front-table-page-meta">1/1</span>' +
                        '<button type="button" class="erp-omd-front-button erp-omd-front-button-small erp-omd-front-table-next">→</button>' +
                    '</div>' +
                    '<span class="erp-omd-front-table-results"></span>';
                if (wrap) {
                    wrap.parentNode.insertBefore(controls, wrap);
                }

                var searchInput = controls.querySelector('.erp-omd-front-table-search-input');
                var resultsNode = controls.querySelector('.erp-omd-front-table-results');
                var pageSizeSelect = controls.querySelector('.erp-omd-front-table-size-select');
                var paginationMeta = controls.querySelector('.erp-omd-front-table-page-meta');
                var paginationPrev = controls.querySelector('.erp-omd-front-table-prev');
                var paginationNext = controls.querySelector('.erp-omd-front-table-next');
                var activeSort = { index: -1, dir: 'asc' };
                var currentPage = 1;
                var pageSize = 100;

                var applyView = function () {
                    var query = ((searchInput && searchInput.value) || '').toLowerCase().trim();
                    var visibleRows = [];

                    allRows.forEach(function (row) {
                        var matches = query === '' || row.textContent.toLowerCase().indexOf(query) !== -1;
                        row.hidden = !matches;
                        if (matches) {
                            visibleRows.push(row);
                        }
                    });

                    if (activeSort.index >= 0) {
                        visibleRows.sort(function (rowA, rowB) {
                            var cellA = rowA.children[activeSort.index];
                            var cellB = rowB.children[activeSort.index];
                            var valueA = parseSortableValue(cellA ? cellA.textContent : '');
                            var valueB = parseSortableValue(cellB ? cellB.textContent : '');
                            if (valueA === valueB) {
                                return 0;
                            }
                            var comparison = valueA > valueB ? 1 : -1;
                            return activeSort.dir === 'asc' ? comparison : -comparison;
                        });
                        visibleRows.forEach(function (row) {
                            tbody.appendChild(row);
                        });
                    }

                    var pagesCount = Math.max(1, Math.ceil(visibleRows.length / pageSize));
                    currentPage = Math.min(currentPage, pagesCount);
                    currentPage = Math.max(1, currentPage);
                    var start = (currentPage - 1) * pageSize;
                    var end = start + pageSize;

                    visibleRows.forEach(function (row, index) {
                        row.hidden = index < start || index >= end;
                    });

                    if (paginationMeta) {
                        paginationMeta.textContent = currentPage + '/' + pagesCount;
                    }
                    if (paginationPrev) {
                        paginationPrev.disabled = currentPage <= 1;
                    }
                    if (paginationNext) {
                        paginationNext.disabled = currentPage >= pagesCount;
                    }

                    resultsNode.textContent = '<?php echo esc_js(__('Widoczne wiersze:', 'erp-omd')); ?> ' + visibleRows.length + '/' + allRows.length;
                };

                table.querySelectorAll('thead th').forEach(function (header, index) {
                    if (header.textContent.trim() === '<?php echo esc_js(__('Akcja', 'erp-omd')); ?>' || header.textContent.trim() === '<?php echo esc_js(__('Akcje', 'erp-omd')); ?>') {
                        return;
                    }
                    header.classList.add('erp-omd-front-table-th-sortable');
                    header.addEventListener('click', function () {
                        if (activeSort.index === index) {
                            activeSort.dir = activeSort.dir === 'asc' ? 'desc' : 'asc';
                        } else {
                            activeSort.index = index;
                            activeSort.dir = 'asc';
                        }
                        table.querySelectorAll('thead th').forEach(function (th) {
                            th.removeAttribute('data-sort-dir');
                        });
                        header.setAttribute('data-sort-dir', activeSort.dir);
                        applyView();
                    });
                });

                if (searchInput) {
                    searchInput.addEventListener('input', function () {
                        currentPage = 1;
                        applyView();
                    });
                }
                    if (pageSizeSelect) {
                        pageSizeSelect.addEventListener('change', function () {
                            pageSize = Number(pageSizeSelect.value) || 100;
                            currentPage = 1;
                            applyView();
                        });
                    }
                if (paginationPrev) {
                    paginationPrev.addEventListener('click', function () {
                        currentPage -= 1;
                        applyView();
                    });
                }
                if (paginationNext) {
                    paginationNext.addEventListener('click', function () {
                        currentPage += 1;
                        applyView();
                    });
                }
                applyView();
            });
        };

        var setupProjectsTableFilters = function () {
            var table = document.querySelector('table[data-projects-table="1"]');
            if (!table) {
                return;
            }

            var tbody = table.querySelector('tbody');
            if (!tbody) {
                return;
            }

            var rows = Array.from(tbody.querySelectorAll('tr'));
            var form = document.querySelector('[data-project-table-filters="1"]');
            if (!form || rows.length === 0) {
                return;
            }

            var clientFilter = form.querySelector('[data-project-filter="client"]');
            var statusFilter = form.querySelector('[data-project-filter="status"]');
            var billingTypeFilter = form.querySelector('[data-project-filter="billing-type"]');
            var sortState = { index: -1, dir: 'asc' };

            var applyFiltersAndSort = function () {
                var selectedClient = clientFilter ? clientFilter.value : '';
                var selectedStatus = statusFilter ? statusFilter.value : '';
                var selectedBillingType = billingTypeFilter ? billingTypeFilter.value : '';
                var visibleRows = [];

                rows.forEach(function (row) {
                    var matchesClient = selectedClient === '' || row.getAttribute('data-client') === selectedClient;
                    var matchesStatus = selectedStatus === '' || row.getAttribute('data-status') === selectedStatus;
                    var matchesBillingType = selectedBillingType === '' || row.getAttribute('data-billing-type') === selectedBillingType;
                    var isVisible = matchesClient && matchesStatus && matchesBillingType;
                    row.hidden = !isVisible;
                    if (isVisible) {
                        visibleRows.push(row);
                    }
                });

                if (sortState.index >= 0) {
                    visibleRows.sort(function (rowA, rowB) {
                        var cellA = rowA.children[sortState.index];
                        var cellB = rowB.children[sortState.index];
                        var valueA = (cellA ? cellA.textContent : '').trim().toLowerCase();
                        var valueB = (cellB ? cellB.textContent : '').trim().toLowerCase();
                        var numericA = Number(valueA.replace(',', '.').replace(/[^\d.-]/g, ''));
                        var numericB = Number(valueB.replace(',', '.').replace(/[^\d.-]/g, ''));
                        var comparableA = Number.isNaN(numericA) ? valueA : numericA;
                        var comparableB = Number.isNaN(numericB) ? valueB : numericB;
                        if (comparableA === comparableB) {
                            return 0;
                        }
                        var comparison = comparableA > comparableB ? 1 : -1;
                        return sortState.dir === 'asc' ? comparison : -comparison;
                    });
                    visibleRows.forEach(function (row) {
                        tbody.appendChild(row);
                    });
                }
            };

            table.querySelectorAll('thead th').forEach(function (header, index) {
                if (header.textContent.trim() === '<?php echo esc_js(__('Akcja', 'erp-omd')); ?>') {
                    return;
                }
                header.classList.add('erp-omd-front-table-th-sortable');
                header.addEventListener('click', function () {
                    if (sortState.index === index) {
                        sortState.dir = sortState.dir === 'asc' ? 'desc' : 'asc';
                    } else {
                        sortState.index = index;
                        sortState.dir = 'asc';
                    }
                    table.querySelectorAll('thead th').forEach(function (th) {
                        th.removeAttribute('data-sort-dir');
                    });
                    header.setAttribute('data-sort-dir', sortState.dir);
                    applyFiltersAndSort();
                });
            });

            [clientFilter, statusFilter, billingTypeFilter].forEach(function (filterField) {
                if (!filterField) {
                    return;
                }
                filterField.addEventListener('change', applyFiltersAndSort);
            });

            applyFiltersAndSort();
        };

        var setupManagerTimeEntryForm = function () {
            var clientInput = document.getElementById('erp-omd-manager-time-client');
            var projectInput = document.getElementById('erp-omd-manager-time-project');
            if (!clientInput || !projectInput) {
                return;
            }

            var syncProjectOptions = function () {
                var selectedClientId = clientInput.value;
                var hasVisibleSelectedOption = false;

                Array.prototype.forEach.call(projectInput.options, function (option) {
                    if (option.value === '') {
                        option.hidden = false;
                        return;
                    }

                    var optionClientId = option.getAttribute('data-client-id') || '';
                    var visible = selectedClientId !== '' && optionClientId === selectedClientId;
                    option.hidden = !visible;

                    if (visible && option.selected) {
                        hasVisibleSelectedOption = true;
                    }
                });

                if (!hasVisibleSelectedOption) {
                    projectInput.value = '';
                }
            };

            clientInput.addEventListener('change', syncProjectOptions);
            syncProjectOptions();
        };

        var setupApprovalQueueFilters = function () {
            var table = document.querySelector('table[data-approval-queue-table="1"]');
            var form = document.querySelector('[data-approval-queue-filters="1"]');
            if (!table || !form) {
                return;
            }

            var tbody = table.querySelector('tbody');
            if (!tbody) {
                return;
            }

            var rows = Array.from(tbody.querySelectorAll('tr'));
            if (rows.length === 0) {
                return;
            }

            var employeeFilter = form.querySelector('[data-queue-filter="employee"]');
            var projectFilter = form.querySelector('[data-queue-filter="project"]');
            var roleFilter = form.querySelector('[data-queue-filter="role"]');
            var sortState = { index: -1, dir: 'asc' };

            var parseComparableValue = function (value) {
                var normalized = (value || '').replace(/\s+/g, ' ').trim().toLowerCase();
                var numeric = normalized.replace(',', '.').replace(/[^\d.-]/g, '');
                if (numeric !== '' && !Number.isNaN(Number(numeric))) {
                    return Number(numeric);
                }
                return normalized;
            };

            var applyFiltersAndSort = function () {
                var selectedEmployee = employeeFilter ? employeeFilter.value : '';
                var selectedProject = projectFilter ? projectFilter.value : '';
                var selectedRole = roleFilter ? roleFilter.value : '';
                var visibleRows = [];

                rows.forEach(function (row) {
                    var matchesEmployee = selectedEmployee === '' || row.getAttribute('data-queue-employee') === selectedEmployee;
                    var matchesProject = selectedProject === '' || row.getAttribute('data-queue-project') === selectedProject;
                    var matchesRole = selectedRole === '' || row.getAttribute('data-queue-role') === selectedRole;
                    var isVisible = matchesEmployee && matchesProject && matchesRole;
                    row.hidden = !isVisible;
                    if (isVisible) {
                        visibleRows.push(row);
                    }
                });

                if (sortState.index >= 0) {
                    visibleRows.sort(function (rowA, rowB) {
                        var cellA = rowA.children[sortState.index];
                        var cellB = rowB.children[sortState.index];
                        var valueA = parseComparableValue(cellA ? cellA.textContent : '');
                        var valueB = parseComparableValue(cellB ? cellB.textContent : '');
                        if (valueA === valueB) {
                            return 0;
                        }
                        var comparison = valueA > valueB ? 1 : -1;
                        return sortState.dir === 'asc' ? comparison : -comparison;
                    });
                    visibleRows.forEach(function (row) {
                        tbody.appendChild(row);
                    });
                }
            };

            table.querySelectorAll('thead th').forEach(function (header, index) {
                if (header.textContent.trim() === '<?php echo esc_js(__('Akcje', 'erp-omd')); ?>') {
                    return;
                }
                header.classList.add('erp-omd-front-table-th-sortable');
                header.addEventListener('click', function () {
                    if (sortState.index === index) {
                        sortState.dir = sortState.dir === 'asc' ? 'desc' : 'asc';
                    } else {
                        sortState.index = index;
                        sortState.dir = 'asc';
                    }
                    table.querySelectorAll('thead th').forEach(function (th) {
                        th.removeAttribute('data-sort-dir');
                    });
                    header.setAttribute('data-sort-dir', sortState.dir);
                    applyFiltersAndSort();
                });
            });

            [employeeFilter, projectFilter, roleFilter].forEach(function (filterField) {
                if (!filterField) {
                    return;
                }
                filterField.addEventListener('change', applyFiltersAndSort);
            });

            applyFiltersAndSort();
        };

        setupManagerTabs();
        setupTableEnhancements();
        setupProjectsTableFilters();
        setupManagerTimeEntryForm();
        setupApprovalQueueFilters();

        var setupCollapsibleSections = function () {
            var storagePrefix = 'erp_omd_front_manager_section_';
            document.querySelectorAll('[data-collapsible-section]').forEach(function (panel) {
                var sectionKey = panel.getAttribute('data-collapsible-section');
                if (!sectionKey) {
                    return;
                }

                var headerNode = panel.querySelector(':scope > .erp-omd-front-section-heading');
                if (!headerNode) {
                    var heading = panel.querySelector(':scope > h2, :scope > h3');
                    if (!heading) {
                        return;
                    }
                    headerNode = document.createElement('div');
                    headerNode.className = 'erp-omd-front-collapsible-header';
                    heading.parentNode.insertBefore(headerNode, heading);
                    headerNode.appendChild(heading);
                }

                if (headerNode.querySelector('.erp-omd-front-collapse-toggle')) {
                    return;
                }

                var contentNodes = Array.from(panel.children).filter(function (child) {
                    return child !== headerNode;
                });

                var toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.className = 'erp-omd-front-collapse-toggle';
                headerNode.appendChild(toggle);

                var storageKey = storagePrefix + sectionKey;
                var isCollapsed = localStorage.getItem(storageKey) === '1';
                var applyState = function () {
                    contentNodes.forEach(function (node) {
                        node.hidden = isCollapsed;
                    });
                    toggle.textContent = isCollapsed ? '<?php echo esc_js(__('Rozwiń', 'erp-omd')); ?>' : '<?php echo esc_js(__('Zwiń', 'erp-omd')); ?>';
                    panel.classList.toggle('erp-omd-front-panel-collapsed', isCollapsed);
                };

                toggle.addEventListener('click', function () {
                    isCollapsed = !isCollapsed;
                    localStorage.setItem(storageKey, isCollapsed ? '1' : '0');
                    applyState();
                });

                applyState();
            });
        };

        setupCollapsibleSections();

        var itemsContainer = document.getElementById('erp-omd-front-estimate-items');
        var addButton = document.getElementById('erp-omd-front-add-item');
        var netNode = document.getElementById('erp-omd-front-estimate-net');
        var taxNode = document.getElementById('erp-omd-front-estimate-tax');
        var grossNode = document.getElementById('erp-omd-front-estimate-gross');

        if (itemsContainer && addButton && netNode && taxNode && grossNode) {
            var formatAmount = function (value) { return Number(value || 0).toFixed(2); };
            var updateTotals = function () {
                var net = 0;
                itemsContainer.querySelectorAll('.erp-omd-front-estimate-item-row').forEach(function (row) {
                    var qtyInput = row.querySelector('input[name="item_qty[]"]');
                    var priceInput = row.querySelector('input[name="item_price[]"]');
                    var qty = parseFloat(qtyInput ? qtyInput.value : '0');
                    var price = parseFloat(priceInput ? priceInput.value : '0');
                    net += qty * price;
                });
                var tax = net * 0.23;
                var gross = net + tax;
                netNode.textContent = formatAmount(net);
                taxNode.textContent = formatAmount(tax);
                grossNode.textContent = formatAmount(gross);
            };

            var bindRow = function (row) {
                row.querySelectorAll('input').forEach(function (input) {
                    input.addEventListener('input', updateTotals);
                });
                var removeButton = row.querySelector('.erp-omd-front-remove-item');
                if (removeButton) {
                    removeButton.addEventListener('click', function () {
                        if (itemsContainer.querySelectorAll('.erp-omd-front-estimate-item-row').length <= 1) {
                            return;
                        }
                        row.remove();
                        updateTotals();
                    });
                }
            };

            var firstRow = itemsContainer.querySelector('.erp-omd-front-estimate-item-row');
            if (firstRow) {
                bindRow(firstRow);
            }

            addButton.addEventListener('click', function () {
                var row = itemsContainer.querySelector('.erp-omd-front-estimate-item-row');
                if (!row) {
                    return;
                }
                var clone = row.cloneNode(true);
                clone.querySelectorAll('input[type="text"], textarea').forEach(function (node) { node.value = ''; });
                clone.querySelectorAll('input[type="number"]').forEach(function (node) {
                    node.value = node.name === 'item_qty[]' ? '1' : '0';
                });
                if (!clone.querySelector('.erp-omd-front-remove-item')) {
                    var removeWrap = document.createElement('div');
                    removeWrap.className = 'erp-omd-front-inline-actions';
                    removeWrap.innerHTML = '<button type="button" class="erp-omd-front-button erp-omd-front-button-ghost erp-omd-front-remove-item"><?php echo esc_js(__('Usuń pozycję', 'erp-omd')); ?></button>';
                    clone.appendChild(removeWrap);
                }
                itemsContainer.appendChild(clone);
                bindRow(clone);
                updateTotals();
            });
            updateTotals();
        }
    })();
    </script>
    <?php wp_footer(); ?>
</body>
</html>
