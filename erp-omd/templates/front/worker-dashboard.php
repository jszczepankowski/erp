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
                    <a class="erp-omd-front-button" href="<?php echo esc_url($front_worker_url); ?>"><?php esc_html_e('Odśwież panel', 'erp-omd'); ?></a>
                    <?php if (user_can($user, 'erp_omd_front_manager')) : ?>
                        <a class="erp-omd-front-button" href="<?php echo esc_url($front_manager_url); ?>"><?php esc_html_e('Panel managera', 'erp-omd'); ?></a>
                    <?php endif; ?>
                    <a class="erp-omd-front-button" href="<?php echo esc_url(admin_url()); ?>"><?php esc_html_e('wp-admin', 'erp-omd'); ?></a>
                    <a class="erp-omd-front-button erp-omd-front-button-secondary" href="<?php echo esc_url($front_logout_url); ?>"><?php esc_html_e('Wyloguj', 'erp-omd'); ?></a>
                </div>
            </div>

            <?php if ($worker_notice_type && $worker_notice_message) : ?>
                <div class="erp-omd-front-notice erp-omd-front-notice-<?php echo esc_attr($worker_notice_type); ?>"><?php echo esc_html($worker_notice_message); ?></div>
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
                            <span class="erp-omd-front-metric-label"><?php esc_html_e('Widoczne wpisy', 'erp-omd'); ?></span>
                            <strong><?php echo esc_html((string) count($time_entries)); ?></strong>
                        </div>
                        <div class="erp-omd-front-metric">
                            <span class="erp-omd-front-metric-label"><?php esc_html_e('Godziny', 'erp-omd'); ?></span>
                            <strong><?php echo esc_html(number_format_i18n($hours_total, 2)); ?></strong>
                        </div>
                        <div class="erp-omd-front-metric">
                            <span class="erp-omd-front-metric-label"><?php esc_html_e('Submitted', 'erp-omd'); ?></span>
                            <strong><?php echo esc_html((string) $status_totals['submitted']); ?></strong>
                        </div>
                        <div class="erp-omd-front-metric">
                            <span class="erp-omd-front-metric-label"><?php esc_html_e('Approved', 'erp-omd'); ?></span>
                            <strong><?php echo esc_html((string) $status_totals['approved']); ?></strong>
                        </div>
                    </div>
                </article>
            </div>

            <div class="erp-omd-front-panel erp-omd-front-panel-calendar">
                <div class="erp-omd-front-section-heading">
                    <h2><?php esc_html_e('Rytm pracy', 'erp-omd'); ?></h2>
                    <p><?php esc_html_e('Szybkie skróty pomagają przełączać zakres listy, a kalendarz pokazuje rozkład Twoich godzin w wybranym miesiącu.', 'erp-omd'); ?></p>
                </div>

                <div class="erp-omd-front-inline-actions erp-omd-front-focus-actions">
                    <?php foreach ([
                        'today' => __('Dziś', 'erp-omd'),
                        'week' => __('Ten tydzień', 'erp-omd'),
                        'month' => __('Ten miesiąc', 'erp-omd'),
                        'all' => __('Wszystko', 'erp-omd'),
                    ] as $focus_key => $focus_label) : ?>
                        <a
                            class="erp-omd-front-button <?php echo $worker_filters['focus'] === $focus_key ? 'erp-omd-front-button-primary' : ''; ?>"
                            href="<?php echo esc_url($this->front_url('worker', [
                                'focus' => $focus_key,
                                'project_id' => $worker_filters['project_id'],
                                'status' => $worker_filters['status'],
                                'calendar_month' => $worker_filters['calendar_month'],
                                'selected_date' => $selected_day,
                            ])); ?>"
                        >
                            <?php echo esc_html($focus_label); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="erp-omd-front-calendar-toolbar">
                    <div class="erp-omd-front-inline-actions">
                        <a href="<?php echo esc_url($calendar_navigation['previous_url']); ?>" class="erp-omd-front-button erp-omd-front-button-small"><?php esc_html_e('← Poprzedni', 'erp-omd'); ?></a>
                        <strong><?php echo esc_html($calendar_navigation['label']); ?></strong>
                        <a href="<?php echo esc_url($calendar_navigation['next_url']); ?>" class="erp-omd-front-button erp-omd-front-button-small"><?php esc_html_e('Następny →', 'erp-omd'); ?></a>
                    </div>
                    <form method="get" action="<?php echo esc_url($front_worker_url); ?>" class="erp-omd-front-inline-actions">
                        <input type="hidden" name="focus" value="<?php echo esc_attr($worker_filters['focus']); ?>">
                        <input type="hidden" name="project_id" value="<?php echo esc_attr((string) $worker_filters['project_id']); ?>">
                        <input type="hidden" name="status" value="<?php echo esc_attr($worker_filters['status']); ?>">
                        <input type="hidden" name="selected_date" value="<?php echo esc_attr($selected_day); ?>">
                        <input type="month" name="calendar_month" value="<?php echo esc_attr($worker_filters['calendar_month']); ?>">
                        <button type="submit" class="erp-omd-front-button"><?php esc_html_e('Pokaż miesiąc', 'erp-omd'); ?></button>
                    </form>
                </div>

                <div class="erp-omd-front-grid erp-omd-front-grid-summary">
                    <article class="erp-omd-front-panel erp-omd-front-panel-compact">
                        <h3><?php esc_html_e('Godziny w miesiącu', 'erp-omd'); ?></h3>
                        <strong><?php echo esc_html(number_format_i18n((float) ($calendar_data['totals']['hours'] ?? 0), 2)); ?></strong>
                    </article>
                    <article class="erp-omd-front-panel erp-omd-front-panel-compact">
                        <h3><?php esc_html_e('Submitted', 'erp-omd'); ?></h3>
                        <strong><?php echo esc_html(number_format_i18n((float) ($calendar_data['totals']['submitted_hours'] ?? 0), 2)); ?></strong>
                    </article>
                    <article class="erp-omd-front-panel erp-omd-front-panel-compact">
                        <h3><?php esc_html_e('Approved', 'erp-omd'); ?></h3>
                        <strong><?php echo esc_html(number_format_i18n((float) ($calendar_data['totals']['approved_hours'] ?? 0), 2)); ?></strong>
                    </article>
                    <article class="erp-omd-front-panel erp-omd-front-panel-compact">
                        <h3><?php esc_html_e('Rejected', 'erp-omd'); ?></h3>
                        <strong><?php echo esc_html(number_format_i18n((float) ($calendar_data['totals']['rejected_hours'] ?? 0), 2)); ?></strong>
                    </article>
                </div>

                <div class="erp-omd-front-calendar">
                    <div class="erp-omd-front-calendar-header">
                        <?php foreach ([__('Pon', 'erp-omd'), __('Wt', 'erp-omd'), __('Śr', 'erp-omd'), __('Czw', 'erp-omd'), __('Pt', 'erp-omd'), __('Sob', 'erp-omd'), __('Nd', 'erp-omd')] as $weekday) : ?>
                            <span><?php echo esc_html($weekday); ?></span>
                        <?php endforeach; ?>
                    </div>

                    <?php foreach ($calendar_data['weeks'] as $week) : ?>
                        <div class="erp-omd-front-calendar-row">
                            <?php foreach ($week as $day) : ?>
                                <?php if ($day === null) : ?>
                                    <div class="erp-omd-front-calendar-day erp-omd-front-calendar-day-empty"></div>
                                <?php else : ?>
                                    <a
                                        class="erp-omd-front-calendar-day <?php echo $selected_day === $day['date'] ? 'erp-omd-front-calendar-day-active' : ''; ?>"
                                        href="<?php echo esc_url($this->front_url('worker', [
                                            'focus' => $worker_filters['focus'],
                                            'project_id' => $worker_filters['project_id'],
                                            'status' => $worker_filters['status'],
                                            'calendar_month' => $worker_filters['calendar_month'],
                                            'selected_date' => $day['date'],
                                        ])); ?>"
                                    >
                                        <div class="erp-omd-front-calendar-day-number"><?php echo esc_html((string) $day['day']); ?></div>
                                        <div class="erp-omd-front-calendar-day-hours"><?php echo esc_html(number_format_i18n((float) $day['hours'], 2)); ?>h</div>
                                        <div class="erp-omd-front-calendar-day-meta">
                                            <span><?php printf(esc_html__('Wpisy: %d', 'erp-omd'), (int) $day['entries_count']); ?></span>
                                            <span>S: <?php echo esc_html(number_format_i18n((float) $day['submitted_hours'], 2)); ?></span>
                                            <span>A: <?php echo esc_html(number_format_i18n((float) $day['approved_hours'], 2)); ?></span>
                                        </div>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="erp-omd-front-grid erp-omd-front-grid-summary">
                <article class="erp-omd-front-panel">
                    <div class="erp-omd-front-section-heading">
                        <h2>
                            <?php
                            printf(
                                esc_html__('Szczegóły dnia %s', 'erp-omd'),
                                esc_html($selected_day !== '' ? $selected_day : __('—', 'erp-omd'))
                            );
                            ?>
                        </h2>
                        <p><?php esc_html_e('Kliknij dzień w kalendarzu, aby zobaczyć jego wpisy i od razu raportować czas na wybraną datę.', 'erp-omd'); ?></p>
                    </div>

                    <div class="erp-omd-front-metrics">
                        <div class="erp-omd-front-metric">
                            <span class="erp-omd-front-metric-label"><?php esc_html_e('Godziny dnia', 'erp-omd'); ?></span>
                            <strong><?php echo esc_html(number_format_i18n((float) $selected_day_totals['hours'], 2)); ?></strong>
                        </div>
                        <div class="erp-omd-front-metric">
                            <span class="erp-omd-front-metric-label"><?php esc_html_e('Submitted', 'erp-omd'); ?></span>
                            <strong><?php echo esc_html((string) $selected_day_totals['submitted']); ?></strong>
                        </div>
                        <div class="erp-omd-front-metric">
                            <span class="erp-omd-front-metric-label"><?php esc_html_e('Approved', 'erp-omd'); ?></span>
                            <strong><?php echo esc_html((string) $selected_day_totals['approved']); ?></strong>
                        </div>
                        <div class="erp-omd-front-metric">
                            <span class="erp-omd-front-metric-label"><?php esc_html_e('Rejected', 'erp-omd'); ?></span>
                            <strong><?php echo esc_html((string) $selected_day_totals['rejected']); ?></strong>
                        </div>
                    </div>

                    <div class="erp-omd-front-day-list">
                        <?php if ($selected_day_entries) : ?>
                            <?php foreach ($selected_day_entries as $day_entry) : ?>
                                <article class="erp-omd-front-day-item">
                                    <div>
                                        <strong><?php echo esc_html($day_entry['project_name'] ?? '—'); ?></strong>
                                        <p><?php echo esc_html($day_entry['role_name'] ?? '—'); ?> · <?php echo esc_html(number_format_i18n((float) ($day_entry['hours'] ?? 0), 2)); ?>h</p>
                                    </div>
                                    <span class="erp-omd-front-status erp-omd-front-status-<?php echo esc_attr((string) $day_entry['status']); ?>">
                                        <?php echo esc_html(ucfirst((string) $day_entry['status'])); ?>
                                    </span>
                                </article>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p class="erp-omd-front-lead"><?php esc_html_e('Brak wpisów dla wybranego dnia i aktywnych filtrów.', 'erp-omd'); ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            </div>

            <div class="erp-omd-front-grid erp-omd-front-grid-worker">
                <article class="erp-omd-front-panel erp-omd-front-panel-form">
                    <div class="erp-omd-front-section-heading">
                        <h2><?php echo ! empty($worker_form_defaults['id']) ? esc_html__('Edytuj wpis czasu', 'erp-omd') : esc_html__('Dodaj wpis czasu', 'erp-omd'); ?></h2>
                        <p><?php esc_html_e('Pracownik może zapisywać i poprawiać wyłącznie własne wpisy w statusie submitted.', 'erp-omd'); ?></p>
                    </div>

                    <?php if ($recent_entry_templates) : ?>
                        <div class="erp-omd-front-templates">
                            <strong><?php esc_html_e('Szybkie szablony', 'erp-omd'); ?></strong>
                            <div class="erp-omd-front-template-list">
                                <?php foreach ($recent_entry_templates as $template) : ?>
                                    <button
                                        type="button"
                                        class="erp-omd-front-template-button"
                                        data-project-id="<?php echo esc_attr((string) $template['project_id']); ?>"
                                        data-role-id="<?php echo esc_attr((string) $template['role_id']); ?>"
                                        data-hours="<?php echo esc_attr((string) $template['hours']); ?>"
                                        data-description="<?php echo esc_attr($template['description']); ?>"
                                    >
                                        <span><?php echo esc_html($template['project_name']); ?></span>
                                        <small><?php echo esc_html($template['role_name']); ?> · <?php echo esc_html(number_format_i18n((float) $template['hours'], 2)); ?>h</small>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?php echo esc_url($worker_form_action); ?>" class="erp-omd-front-form">
                        <?php wp_nonce_field('erp_omd_front_worker'); ?>
                        <input type="hidden" name="erp_omd_front_action" value="save_time_entry">
                        <input type="hidden" name="id" value="<?php echo esc_attr((string) ($worker_form_defaults['id'] ?? 0)); ?>">
                        <input type="hidden" name="selected_date" value="<?php echo esc_attr($selected_day); ?>">

                        <label for="erp-omd-front-project"><?php esc_html_e('Projekt', 'erp-omd'); ?></label>
                        <select id="erp-omd-front-project" name="project_id" required>
                            <option value=""><?php esc_html_e('Wybierz projekt w realizacji', 'erp-omd'); ?></option>
                            <?php foreach ($available_projects as $project_item) : ?>
                                <option value="<?php echo esc_attr((string) $project_item['id']); ?>" <?php selected((int) ($worker_form_defaults['project_id'] ?? 0), (int) $project_item['id']); ?>>
                                    <?php echo esc_html($project_item['client_name'] . ' → ' . $project_item['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="erp-omd-front-role"><?php esc_html_e('Rola', 'erp-omd'); ?></label>
                        <select id="erp-omd-front-role" name="role_id" required>
                            <?php foreach ($available_roles as $role_item) : ?>
                                <option value="<?php echo esc_attr((string) $role_item['id']); ?>" <?php selected((int) ($worker_form_defaults['role_id'] ?? 0), (int) $role_item['id']); ?>>
                                    <?php echo esc_html($role_item['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <div class="erp-omd-front-form-row">
                            <div>
                                <label for="erp-omd-front-hours"><?php esc_html_e('Godziny', 'erp-omd'); ?></label>
                                <input id="erp-omd-front-hours" name="hours" type="number" min="0.25" step="0.25" value="<?php echo esc_attr((string) ($worker_form_defaults['hours'] ?? '')); ?>" required>
                            </div>
                            <div>
                                <label for="erp-omd-front-entry-date"><?php esc_html_e('Data', 'erp-omd'); ?></label>
                                <input id="erp-omd-front-entry-date" name="entry_date" type="date" value="<?php echo esc_attr((string) ($worker_form_defaults['entry_date'] ?? '')); ?>" required>
                            </div>
                        </div>

                        <div class="erp-omd-front-quick-hours">
                            <?php foreach ([0.5, 1, 2, 4, 8] as $quick_hours) : ?>
                                <button type="button" class="erp-omd-front-button erp-omd-front-button-ghost erp-omd-front-quick-hours-button" data-hours="<?php echo esc_attr((string) $quick_hours); ?>">
                                    <?php echo esc_html(number_format_i18n($quick_hours, $quick_hours === (int) $quick_hours ? 0 : 2)); ?>h
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <label for="erp-omd-front-description"><?php esc_html_e('Opis pracy', 'erp-omd'); ?></label>
                        <textarea id="erp-omd-front-description" name="description" rows="5" required><?php echo esc_textarea((string) ($worker_form_defaults['description'] ?? '')); ?></textarea>

                        <div class="erp-omd-front-inline-actions">
                            <button type="submit" class="erp-omd-front-button erp-omd-front-button-primary">
                                <?php echo ! empty($worker_form_defaults['id']) ? esc_html__('Zapisz zmiany', 'erp-omd') : esc_html__('Dodaj wpis czasu', 'erp-omd'); ?>
                            </button>
                            <?php if (! empty($worker_form_defaults['id'])) : ?>
                                <a href="<?php echo esc_url($front_worker_url); ?>" class="erp-omd-front-button"><?php esc_html_e('Anuluj edycję', 'erp-omd'); ?></a>
                            <?php endif; ?>
                        </div>
                    </form>
                </article>

                <article class="erp-omd-front-panel">
                    <div class="erp-omd-front-section-heading">
                        <h2><?php esc_html_e('Twoje wpisy czasu', 'erp-omd'); ?></h2>
                        <p><?php esc_html_e('Filtry są lokalne dla Twojego widoku i nie pokazują wpisów innych pracowników.', 'erp-omd'); ?></p>
                    </div>

                    <form method="get" action="<?php echo esc_url($front_worker_url); ?>" class="erp-omd-front-filter-form">
                        <input type="hidden" name="erp_omd_front" value="worker">
                        <input type="hidden" name="selected_date" value="<?php echo esc_attr($selected_day); ?>">
                        <div>
                            <label for="erp-omd-front-filter-date"><?php esc_html_e('Data', 'erp-omd'); ?></label>
                            <input id="erp-omd-front-filter-date" type="date" name="entry_date" value="<?php echo esc_attr((string) ($worker_filters['entry_date'] ?? '')); ?>">
                        </div>
                        <div>
                            <label for="erp-omd-front-filter-month"><?php esc_html_e('Miesiąc kalendarza', 'erp-omd'); ?></label>
                            <input id="erp-omd-front-filter-month" type="month" name="calendar_month" value="<?php echo esc_attr((string) ($worker_filters['calendar_month'] ?? '')); ?>">
                        </div>
                        <div>
                            <label for="erp-omd-front-filter-project"><?php esc_html_e('Projekt', 'erp-omd'); ?></label>
                            <select id="erp-omd-front-filter-project" name="project_id">
                                <option value="0"><?php esc_html_e('Wszystkie projekty', 'erp-omd'); ?></option>
                                <?php foreach ($available_projects as $project_item) : ?>
                                    <option value="<?php echo esc_attr((string) $project_item['id']); ?>" <?php selected((int) ($worker_filters['project_id'] ?? 0), (int) $project_item['id']); ?>>
                                        <?php echo esc_html($project_item['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="erp-omd-front-filter-status"><?php esc_html_e('Status', 'erp-omd'); ?></label>
                            <select id="erp-omd-front-filter-status" name="status">
                                <option value=""><?php esc_html_e('Wszystkie statusy', 'erp-omd'); ?></option>
                                <?php foreach (['submitted', 'approved', 'rejected'] as $status_option) : ?>
                                    <option value="<?php echo esc_attr($status_option); ?>" <?php selected((string) ($worker_filters['status'] ?? ''), $status_option); ?>>
                                        <?php echo esc_html(ucfirst($status_option)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="erp-omd-front-filter-focus"><?php esc_html_e('Zakres listy', 'erp-omd'); ?></label>
                            <select id="erp-omd-front-filter-focus" name="focus">
                                <?php foreach ([
                                    'today' => __('Dziś', 'erp-omd'),
                                    'week' => __('Ten tydzień', 'erp-omd'),
                                    'month' => __('Ten miesiąc', 'erp-omd'),
                                    'all' => __('Wszystko', 'erp-omd'),
                                ] as $focus_key => $focus_label) : ?>
                                    <option value="<?php echo esc_attr($focus_key); ?>" <?php selected((string) ($worker_filters['focus'] ?? 'month'), $focus_key); ?>>
                                        <?php echo esc_html($focus_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="erp-omd-front-inline-actions">
                            <button type="submit" class="erp-omd-front-button erp-omd-front-button-primary"><?php esc_html_e('Filtruj', 'erp-omd'); ?></button>
                            <a href="<?php echo esc_url($front_worker_url); ?>" class="erp-omd-front-button"><?php esc_html_e('Reset', 'erp-omd'); ?></a>
                        </div>
                    </form>

                    <div class="erp-omd-front-table-wrap">
                        <table class="erp-omd-front-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Data', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Projekt', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Rola', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Godz.', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Status', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Opis', 'erp-omd'); ?></th>
                                    <th><?php esc_html_e('Akcje', 'erp-omd'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($time_entries) : ?>
                                    <?php foreach ($time_entries as $time_entry) : ?>
                                        <tr>
                                            <td><?php echo esc_html($time_entry['entry_date']); ?></td>
                                            <td><?php echo esc_html($time_entry['project_name'] ?? '—'); ?></td>
                                            <td><?php echo esc_html($time_entry['role_name'] ?? '—'); ?></td>
                                            <td><?php echo esc_html(number_format_i18n((float) ($time_entry['hours'] ?? 0), 2)); ?></td>
                                            <td>
                                                <span class="erp-omd-front-status erp-omd-front-status-<?php echo esc_attr((string) $time_entry['status']); ?>">
                                                    <?php echo esc_html(ucfirst((string) $time_entry['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo esc_html(wp_trim_words((string) ($time_entry['description'] ?? ''), 14)); ?></td>
                                            <td>
                                                <div class="erp-omd-front-inline-actions">
                                                    <?php if ($this->time_entry_service->can_edit_entry($time_entry, $user)) : ?>
                                                        <a href="<?php echo esc_url($front_worker_url . '?entry_id=' . (int) $time_entry['id']); ?>" class="erp-omd-front-button erp-omd-front-button-small"><?php esc_html_e('Edytuj', 'erp-omd'); ?></a>
                                                    <?php endif; ?>

                                                    <?php if ($this->time_entry_service->can_delete_entry($user, $time_entry)) : ?>
                                                        <form method="post" action="<?php echo esc_url($worker_form_action); ?>" onsubmit="return window.confirm('<?php echo esc_js(__('Usunąć ten wpis czasu?', 'erp-omd')); ?>');">
                                                            <?php wp_nonce_field('erp_omd_front_worker'); ?>
                                                            <input type="hidden" name="erp_omd_front_action" value="delete_time_entry">
                                                            <input type="hidden" name="id" value="<?php echo esc_attr((string) $time_entry['id']); ?>">
                                                            <button type="submit" class="erp-omd-front-button erp-omd-front-button-small"><?php esc_html_e('Usuń', 'erp-omd'); ?></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="7"><?php esc_html_e('Brak wpisów spełniających aktualne filtry.', 'erp-omd'); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var hoursInput = document.getElementById('erp-omd-front-hours');
            var projectInput = document.getElementById('erp-omd-front-project');
            var roleInput = document.getElementById('erp-omd-front-role');
            var descriptionInput = document.getElementById('erp-omd-front-description');
            document.querySelectorAll('.erp-omd-front-quick-hours-button').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (hoursInput) {
                        hoursInput.value = button.getAttribute('data-hours');
                        hoursInput.focus();
                    }
                });
            });

            document.querySelectorAll('.erp-omd-front-template-button').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (projectInput) {
                        projectInput.value = button.getAttribute('data-project-id');
                    }
                    if (roleInput) {
                        roleInput.value = button.getAttribute('data-role-id');
                    }
                    if (hoursInput) {
                        hoursInput.value = button.getAttribute('data-hours');
                    }
                    if (descriptionInput) {
                        descriptionInput.value = button.getAttribute('data-description');
                        descriptionInput.focus();
                    }
                });
            });
        });
    </script>
    <?php wp_footer(); ?>
</body>
</html>
