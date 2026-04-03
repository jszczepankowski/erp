<div class="wrap erp-omd-admin">
    <h1><?php esc_html_e('ERP OMD — Raporty i analityka', 'erp-omd'); ?></h1>

    <nav class="nav-tab-wrapper erp-omd-nav-tabs">
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-reports', 'tab' => 'reports'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $report_filters['tab'] === 'reports' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Raporty', 'erp-omd'); ?></a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'erp-omd-reports', 'tab' => 'calendar'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $report_filters['tab'] === 'calendar' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Kalendarz', 'erp-omd'); ?></a>
    </nav>

    <div class="erp-omd-page-sections">
        <section class="erp-omd-card">
            <div class="erp-omd-section-header">
                <div>
                    <h2><?php esc_html_e('Filtry raportowe', 'erp-omd'); ?></h2>
               </div>
            </div>
            <form method="get">
                <input type="hidden" name="page" value="erp-omd-reports" />
                <input type="hidden" name="tab" value="<?php echo esc_attr($report_filters['tab']); ?>" />
                <div class="erp-omd-form-sections">
                    <section class="erp-omd-form-section">
                        <div class="erp-omd-form-section-header">
                            <h3><?php esc_html_e('Zakres raportu', 'erp-omd'); ?></h3>
                            <p><?php esc_html_e('Wybierz typ raportu oraz okres, a następnie zawęź dane według filtrów.', 'erp-omd'); ?></p>
                        </div>
                        <div class="erp-omd-form-grid">
                            <?php if ($report_filters['tab'] === 'reports') : ?>
                                <div class="erp-omd-form-field">
                                    <label for="report-type"><?php esc_html_e('Typ raportu', 'erp-omd'); ?></label>
                                    <select id="report-type" name="report_type">
                                        <option value="" <?php selected($report_filters['report_type'], ''); ?>><?php esc_html_e('— Wybierz typ raportu —', 'erp-omd'); ?></option>
                                        <option value="projects" <?php selected($report_filters['report_type'], 'projects'); ?>><?php esc_html_e('Raport projektów', 'erp-omd'); ?></option>
                                        <option value="clients" <?php selected($report_filters['report_type'], 'clients'); ?>><?php esc_html_e('Raport klientów', 'erp-omd'); ?></option>
                                        <option value="invoice" <?php selected($report_filters['report_type'], 'invoice'); ?>><?php esc_html_e('Projekty do faktury', 'erp-omd'); ?></option>
                                        <option value="time_entries" <?php selected($report_filters['report_type'], 'time_entries'); ?>><?php esc_html_e('Czas pracy', 'erp-omd'); ?></option>
                                        <option value="monthly" <?php selected($report_filters['report_type'], 'monthly'); ?>><?php esc_html_e('Raport miesięczny', 'erp-omd'); ?></option>
                                        <option value="omd_rozliczenia" <?php selected($report_filters['report_type'], 'omd_rozliczenia'); ?>><?php esc_html_e('Raport operacyjny OMD', 'erp-omd'); ?></option>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="report-month"><?php esc_html_e('Miesiąc', 'erp-omd'); ?></label>
                                <input id="report-month" type="date" name="month" value="<?php echo esc_attr($report_filters['month'] . '-01'); ?>" />
                            </div>
                            <div class="erp-omd-form-field">
                                <label for="report-client"><?php esc_html_e('Klient', 'erp-omd'); ?></label>
                                <select id="report-client" name="client_id" data-project-target="#report-project">
                                    <option value="0"><?php esc_html_e('Wszyscy klienci', 'erp-omd'); ?></option>
                                    <?php foreach ($clients as $client_item) : ?>
                                        <option value="<?php echo esc_attr($client_item['id']); ?>" <?php selected((int) $report_filters['client_id'], (int) $client_item['id']); ?>><?php echo esc_html($client_item['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="erp-omd-form-field">
                                <label for="report-project"><?php esc_html_e('Projekt', 'erp-omd'); ?></label>
                                <select id="report-project" name="project_id">
                                    <option value=""><?php esc_html_e('Wszystkie projekty', 'erp-omd'); ?></option>
                                    <?php foreach ($projects as $project_item) : ?>
                                        <option value="<?php echo esc_attr($project_item['id']); ?>" data-client-id="<?php echo esc_attr((string) ($project_item['client_id'] ?? 0)); ?>" <?php selected((int) $report_filters['project_id'], (int) $project_item['id']); ?>><?php echo esc_html($project_item['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="erp-omd-form-field">
                                <label for="report-employee"><?php esc_html_e('Pracownik', 'erp-omd'); ?></label>
                                <select id="report-employee" name="employee_id">
                                    <option value="0"><?php esc_html_e('Wszyscy pracownicy', 'erp-omd'); ?></option>
                                    <?php foreach ($employees as $employee_item) : ?>
                                        <option value="<?php echo esc_attr($employee_item['id']); ?>" <?php selected((int) $report_filters['employee_id'], (int) $employee_item['id']); ?>><?php echo esc_html($employee_item['user_login']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="erp-omd-form-field">
                                <label for="report-status"><?php esc_html_e('Status', 'erp-omd'); ?></label>
                                <select id="report-status" name="status">
                                    <option value=""><?php esc_html_e('Wszystkie statusy', 'erp-omd'); ?></option>
                                    <?php foreach ($status_options as $status_option) : ?>
                                        <option value="<?php echo esc_attr($status_option); ?>" <?php selected($report_filters['status'], $status_option); ?>><?php echo esc_html($status_labels[$status_option] ?? $status_option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (in_array($report_filters['report_type'], ['projects', 'clients', 'invoice'], true)) : ?>
                                <div class="erp-omd-form-field erp-omd-form-field-compact">
                                    <label for="report-detail"><?php esc_html_e('Wersja raportu', 'erp-omd'); ?></label>
                                    <select id="report-detail" name="detail">
                                        <option value="simple" <?php selected($report_filters['detail'], 'simple'); ?>><?php esc_html_e('Podstawowa', 'erp-omd'); ?></option>
                                        <option value="detail" <?php selected($report_filters['detail'], 'detail'); ?>><?php esc_html_e('Szczegółowa', 'erp-omd'); ?></option>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <div class="erp-omd-form-field erp-omd-form-field-compact">
                                <label for="dashboard-queue-limit"><?php esc_html_e('Limit kolejki dashboardu', 'erp-omd'); ?></label>
                                <input id="dashboard-queue-limit" type="number" min="1" max="100" name="dashboard_queue_limit" value="<?php echo esc_attr((string) ($dashboard_preview_filters['queue_limit'] ?? 25)); ?>" />
                            </div>
                            <?php if ($report_filters['report_type'] === 'time_entries') : ?>
                                <div class="erp-omd-form-field">
                                    <label for="report-per-page"><?php esc_html_e('Wierszy na stronę', 'erp-omd'); ?></label>
                                    <select id="report-per-page" name="per_page">
                                        <?php foreach ([25, 50, 100] as $per_page_option) : ?>
                                            <option value="<?php echo esc_attr((string) $per_page_option); ?>" <?php selected((int) ($report_filters['per_page'] ?? 25), $per_page_option); ?>><?php echo esc_html((string) $per_page_option); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
                <div class="erp-omd-form-actions">
                    <input type="hidden" name="page_num" value="1" />
                    <button class="button button-primary" type="submit"><?php esc_html_e('Filtruj', 'erp-omd'); ?></button>
                </div>
            </form>
        </section>

        <?php if ($report_filters['tab'] === 'reports') : ?>
            <section class="erp-omd-card">
                <div class="erp-omd-section-header">
                    <div>
                        <h2><?php echo esc_html($report_title); ?></h2>
                        <p class="description"><?php esc_html_e('Dane raportowe budowane na podstawie projektów, wpisów czasu i finansów.', 'erp-omd'); ?></p>
                        <?php if (! empty($report_error_notice)) : ?>
                            <p class="notice notice-error" style="padding:8px 12px;"><?php echo esc_html($report_error_notice); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($report_filters['report_type'] !== '') : ?>
                        <form method="post" class="erp-omd-inline-form">
                            <?php wp_nonce_field('erp_omd_export_report'); ?>
                            <input type="hidden" name="erp_omd_action" value="export_report" />
                            <input type="hidden" name="report_type" value="<?php echo esc_attr($report_filters['report_type']); ?>" />
                            <input type="hidden" name="month" value="<?php echo esc_attr($report_filters['month']); ?>" />
                            <input type="hidden" name="client_id" value="<?php echo esc_attr($report_filters['client_id']); ?>" />
                            <input type="hidden" name="project_id" value="<?php echo esc_attr($report_filters['project_id']); ?>" />
                            <input type="hidden" name="employee_id" value="<?php echo esc_attr($report_filters['employee_id']); ?>" />
                            <input type="hidden" name="status" value="<?php echo esc_attr($report_filters['status']); ?>" />
                            <input type="hidden" name="mode" value="<?php echo esc_attr($report_filters['mode']); ?>" />
                            <input type="hidden" name="detail" value="<?php echo esc_attr($report_filters['detail']); ?>" />
                            <input type="hidden" name="page_num" value="<?php echo esc_attr((string) ($report_filters['page_num'] ?? 1)); ?>" />
                            <input type="hidden" name="per_page" value="<?php echo esc_attr((string) ($report_filters['per_page'] ?? 25)); ?>" />
                            <button class="button button-secondary" type="submit"><?php esc_html_e('Eksport CSV', 'erp-omd'); ?></button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php if ($report_filters['report_type'] === '') : ?>
                    <p class="description"><?php esc_html_e('Wybierz typ raportu i kliknij „Filtruj”, aby wyświetlić dane.', 'erp-omd'); ?></p>
                <?php elseif ($report_filters['report_type'] === 'clients') : ?>
                    <table class="widefat striped">
                        <thead><tr><th><?php esc_html_e('Klient', 'erp-omd'); ?></th><th><?php esc_html_e('Projekty', 'erp-omd'); ?></th><th><?php esc_html_e('Godziny', 'erp-omd'); ?></th><th><?php esc_html_e('Przychód czasu', 'erp-omd'); ?></th><th><?php esc_html_e('Koszt czasu', 'erp-omd'); ?></th><th><?php esc_html_e('Koszt bezpośredni', 'erp-omd'); ?></th><th><?php esc_html_e('Przychód łącznie', 'erp-omd'); ?></th><th><?php esc_html_e('Koszt łącznie', 'erp-omd'); ?></th><th><?php esc_html_e('Zysk', 'erp-omd'); ?></th><th><?php esc_html_e('Marża %', 'erp-omd'); ?></th></tr></thead>
                        <tbody>
                        <?php if (empty($report_rows)) : ?><tr><td colspan="10"><?php esc_html_e('Brak danych dla wybranych filtrów.', 'erp-omd'); ?></td></tr><?php endif; ?>
                        <?php foreach ($report_rows as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row['client_name']); ?></td>
                                <td><?php echo esc_html($row['projects_count']); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['reported_hours'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['filtered_time_revenue'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['filtered_time_cost'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['filtered_direct_cost'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['revenue'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['cost'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['profit'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['margin'], 2)); ?></td>
                            </tr>
                            <?php if ($report_filters['detail'] === 'detail' && ! empty($row['projects'])) : ?>
                                <tr>
                                    <td colspan="10">
                                        <strong><?php esc_html_e('Szczegóły klienta:', 'erp-omd'); ?></strong>
                                        <ul style="margin:8px 0 0 18px;">
                                            <?php foreach ((array) $row['projects'] as $project_detail_row) : ?>
                                                <li>
                                                    <?php
                                                    echo esc_html(
                                                        sprintf(
                                                            '%1$s | %2$s h | %3$s',
                                                            (string) ($project_detail_row['project_name'] ?? '—'),
                                                            number_format_i18n((float) ($project_detail_row['reported_hours'] ?? 0), 2),
                                                            number_format_i18n((float) ($project_detail_row['profit'] ?? 0), 2)
                                                        )
                                                    );
                                                    ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif ($report_filters['report_type'] === 'monthly') : ?>
                    <table class="widefat striped">
                        <thead><tr><th><?php esc_html_e('Miesiąc', 'erp-omd'); ?></th><th><?php esc_html_e('Projekty', 'erp-omd'); ?></th><th><?php esc_html_e('Klienci', 'erp-omd'); ?></th><th><?php esc_html_e('Godziny', 'erp-omd'); ?></th><th><?php esc_html_e('Przychód czasu', 'erp-omd'); ?></th><th><?php esc_html_e('Koszt czasu', 'erp-omd'); ?></th><th><?php esc_html_e('Koszt bezpośredni', 'erp-omd'); ?></th><th><?php esc_html_e('Zysk projektowy', 'erp-omd'); ?></th><th><?php esc_html_e('Wynik', 'erp-omd'); ?></th></tr></thead>
                        <tbody>
                        <?php if (empty($report_rows)) : ?><tr><td colspan="9"><?php esc_html_e('Brak danych dla wybranych filtrów.', 'erp-omd'); ?></td></tr><?php endif; ?>
                        <?php foreach ($report_rows as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row['month']); ?></td>
                                <td><?php echo esc_html($row['projects_count']); ?></td>
                                <td><?php echo esc_html($row['clients_count']); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['hours'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['time_revenue'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['time_cost'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['direct_cost'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) ($row['project_budget_profit'] ?? 0), 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['profit'], 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif ($report_filters['report_type'] === 'omd_rozliczenia') : ?>
                    <p class="description">
                        <?php esc_html_e('Legenda OMD: wynik operacyjny = budżety aktywnych projektów + zysk godzinowy - koszt projektów; narzut controllingowy = koszt pensji + koszty stałe; wynik controllingowy = wynik operacyjny - narzut controllingowy.', 'erp-omd'); ?>
                    </p>
                    <table class="widefat striped">
                        <thead><tr><th><?php esc_html_e('Miesiąc', 'erp-omd'); ?></th><th><?php esc_html_e('Koszt pensji', 'erp-omd'); ?></th><th><?php esc_html_e('Koszt projektów', 'erp-omd'); ?></th><th><?php esc_html_e('Koszty czasu', 'erp-omd'); ?></th><th><?php esc_html_e('Stałe koszty', 'erp-omd'); ?></th><th><?php esc_html_e('Budżety aktywnych projektów', 'erp-omd'); ?></th><th><?php esc_html_e('Przychód czasu', 'erp-omd'); ?></th><th><?php esc_html_e('Zysk godzinowy', 'erp-omd'); ?></th><th><?php esc_html_e('Wynik operacyjny', 'erp-omd'); ?></th><th><?php esc_html_e('Narzut controllingowy', 'erp-omd'); ?></th><th><?php esc_html_e('Wynik controllingowy', 'erp-omd'); ?></th></tr></thead>
                        <tbody>
                        <?php if (empty($report_rows)) : ?><tr><td colspan="11"><?php esc_html_e('Brak danych dla wybranych filtrów.', 'erp-omd'); ?></td></tr><?php endif; ?>
                        <?php foreach ($report_rows as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row['month']); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['salary_cost'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['project_direct_cost'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['time_cost'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['fixed_cost'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['active_project_budgets'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['time_revenue'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['hourly_profit'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['operational_result'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['controlling_overhead'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['controlling_result'], 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif ($report_filters['report_type'] === 'time_entries') : ?>
                    <table class="widefat striped">
                        <thead><tr><th><?php esc_html_e('Data', 'erp-omd'); ?></th><th><?php esc_html_e('Pracownik', 'erp-omd'); ?></th><th><?php esc_html_e('Klient', 'erp-omd'); ?></th><th><?php esc_html_e('Projekt', 'erp-omd'); ?></th><th><?php esc_html_e('Rola', 'erp-omd'); ?></th><th><?php esc_html_e('Godziny', 'erp-omd'); ?></th><th><?php esc_html_e('Stawka klienta', 'erp-omd'); ?></th><th><?php esc_html_e('Kwota', 'erp-omd'); ?></th><th><?php esc_html_e('Status', 'erp-omd'); ?></th><th><?php esc_html_e('Opis', 'erp-omd'); ?></th></tr></thead>
                        <tbody>
                        <?php if (empty($report_rows)) : ?><tr><td colspan="10"><?php esc_html_e('Brak danych dla wybranych filtrów.', 'erp-omd'); ?></td></tr><?php endif; ?>
                        <?php foreach ($report_rows as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row['entry_date']); ?></td>
                                <td><?php echo esc_html($row['employee_login']); ?></td>
                                <td><?php echo esc_html($row['client_name']); ?></td>
                                <td><?php echo esc_html($row['project_name']); ?></td>
                                <td><?php echo esc_html($row['role_name']); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['hours'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['rate_snapshot'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['amount'], 2)); ?></td>
                                <td><?php echo esc_html($row['status']); ?></td>
                                <td><?php echo esc_html($row['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (! empty($report_pagination) && (int) ($report_pagination['total_pages'] ?? 1) > 1) : ?>
                        <div class="tablenav">
                            <div class="tablenav-pages">
                                <?php
                                $base_args = [
                                    'page' => 'erp-omd-reports',
                                    'tab' => 'reports',
                                    'report_type' => $report_filters['report_type'],
                                    'month' => $report_filters['month'],
                                    'client_id' => (int) $report_filters['client_id'],
                                    'project_id' => (int) $report_filters['project_id'],
                                    'employee_id' => (int) $report_filters['employee_id'],
                                    'status' => $report_filters['status'],
                                    'mode' => $report_filters['mode'],
                                    'detail' => $report_filters['detail'],
                                    'per_page' => (int) ($report_filters['per_page'] ?? 25),
                                ];
                                $current_page = (int) ($report_pagination['page_num'] ?? 1);
                                $total_pages = (int) ($report_pagination['total_pages'] ?? 1);
                                ?>
                                <span class="displaying-num"><?php echo esc_html(sprintf(__('Wyniki: %d', 'erp-omd'), (int) ($report_pagination['total_items'] ?? 0))); ?></span>
                                <?php if (! empty($report_pagination['has_prev'])) : ?>
                                    <a class="button" href="<?php echo esc_url(add_query_arg(array_merge($base_args, ['page_num' => $current_page - 1]), admin_url('admin.php'))); ?>">&laquo;</a>
                                <?php endif; ?>
                                <span class="paging-input"><?php echo esc_html(sprintf(__('%1$d z %2$d', 'erp-omd'), $current_page, $total_pages)); ?></span>
                                <?php if (! empty($report_pagination['has_next'])) : ?>
                                    <a class="button" href="<?php echo esc_url(add_query_arg(array_merge($base_args, ['page_num' => $current_page + 1]), admin_url('admin.php'))); ?>">&raquo;</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead><tr><th><?php esc_html_e('Klient', 'erp-omd'); ?></th><th><?php esc_html_e('Projekt', 'erp-omd'); ?></th><th><?php esc_html_e('Typ rozliczenia', 'erp-omd'); ?></th><th><?php esc_html_e('Budżet', 'erp-omd'); ?></th><th><?php esc_html_e('Godziny', 'erp-omd'); ?></th><th><?php esc_html_e('Przychód czasu', 'erp-omd'); ?></th><th><?php esc_html_e('Koszt czasu', 'erp-omd'); ?></th><th><?php esc_html_e('Koszt bezpośredni', 'erp-omd'); ?></th><th><?php esc_html_e('Przychód łącznie', 'erp-omd'); ?></th><th><?php esc_html_e('Koszt łącznie', 'erp-omd'); ?></th><th><?php esc_html_e('Zysk', 'erp-omd'); ?></th><th><?php esc_html_e('Marża %', 'erp-omd'); ?></th><th><?php esc_html_e('Budżet %', 'erp-omd'); ?></th><th><?php esc_html_e('Status', 'erp-omd'); ?></th><?php if ($report_filters['report_type'] === 'invoice') : ?><th><?php esc_html_e('Pozycje do faktury', 'erp-omd'); ?></th><?php endif; ?></tr></thead>
                        <tbody>
                        <?php if (empty($report_rows)) : ?><tr><td colspan="<?php echo $report_filters['report_type'] === 'invoice' ? '15' : '14'; ?>"><?php esc_html_e('Brak danych dla wybranych filtrów.', 'erp-omd'); ?></td></tr><?php endif; ?>
                        <?php foreach ($report_rows as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row['client_name']); ?></td>
                                <td><?php echo esc_html($row['project_name']); ?></td>
                                <td><?php echo esc_html($this->billing_type_label($row['billing_type'])); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['budget'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['reported_hours'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['filtered_time_revenue'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['filtered_time_cost'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['filtered_direct_cost'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['revenue'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['cost'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['profit'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['margin'], 2)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $row['budget_usage'], 2)); ?></td>
                                <td><span class="erp-omd-badge <?php echo esc_attr($this->status_badge_class($row['status'], 'project')); ?>"><?php echo esc_html($this->project_status_label($row['status'])); ?></span></td>
                                <?php if ($report_filters['report_type'] === 'invoice') : ?>
                                    <td><?php echo esc_html((string) ((int) ($row['invoice_items_count'] ?? 0))); ?></td>
                                <?php endif; ?>
                            </tr>
                            <?php if ($report_filters['detail'] === 'detail' && ! empty($row['detail'])) : ?>
                                <?php
                                $detail_time_entries_count = count((array) (($row['detail']['time_entries'] ?? [])));
                                $detail_direct_cost_count = count((array) (($row['detail']['direct_cost_items'] ?? [])));
                                ?>
                                <tr>
                                    <td colspan="<?php echo $report_filters['report_type'] === 'invoice' ? '15' : '14'; ?>">
                                        <?php
                                        echo esc_html(
                                            sprintf(
                                                __('Szczegóły projektu: wpisy czasu=%1$d | koszty bezpośrednie=%2$d', 'erp-omd'),
                                                (int) $detail_time_entries_count,
                                                (int) $detail_direct_cost_count
                                            )
                                        );
                                        ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($report_filters['report_type'] === 'projects' && ! empty($report_rows)) : ?>
                            <?php
                            $summary_hours = array_sum(array_map(static function ($item) { return (float) ($item['reported_hours'] ?? 0); }, $report_rows));
                            $summary_time_revenue = array_sum(array_map(static function ($item) { return (float) ($item['filtered_time_revenue'] ?? 0); }, $report_rows));
                            $summary_time_cost = array_sum(array_map(static function ($item) { return (float) ($item['filtered_time_cost'] ?? 0); }, $report_rows));
                            $summary_direct_cost = array_sum(array_map(static function ($item) { return (float) ($item['filtered_direct_cost'] ?? 0); }, $report_rows));
                            $summary_revenue = array_sum(array_map(static function ($item) { return (float) ($item['revenue'] ?? 0); }, $report_rows));
                            $summary_cost = array_sum(array_map(static function ($item) { return (float) ($item['cost'] ?? 0); }, $report_rows));
                            $summary_profit = array_sum(array_map(static function ($item) { return (float) ($item['profit'] ?? 0); }, $report_rows));
                            ?>
                            <tr>
                                <td colspan="4"><strong><?php esc_html_e('Podsumowanie', 'erp-omd'); ?></strong></td>
                                <td><strong><?php echo esc_html(number_format_i18n($summary_hours, 2)); ?></strong></td>
                                <td><strong><?php echo esc_html(number_format_i18n($summary_time_revenue, 2)); ?></strong></td>
                                <td><strong><?php echo esc_html(number_format_i18n($summary_time_cost, 2)); ?></strong></td>
                                <td><strong><?php echo esc_html(number_format_i18n($summary_direct_cost, 2)); ?></strong></td>
                                <td><strong><?php echo esc_html(number_format_i18n($summary_revenue, 2)); ?></strong></td>
                                <td><strong><?php echo esc_html(number_format_i18n($summary_cost, 2)); ?></strong></td>
                                <td><strong><?php echo esc_html(number_format_i18n($summary_profit, 2)); ?></strong></td>
                                <td colspan="3">—</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        <?php elseif ($report_filters['tab'] === 'calendar') : ?>
            <section class="erp-omd-card">
                <h2><?php echo esc_html(sprintf(__('Kalendarz miesiąca %s', 'erp-omd'), $calendar_data['month'])); ?></h2>
                <p class="description"><?php echo esc_html(sprintf(__('Łącznie godzin: %1$s | wpisów: %2$s | Zaakceptowancyh: %3$s | Zgłoszonych: %4$s | Odrzuconych: %5$s', 'erp-omd'), number_format_i18n((float) $calendar_data['totals']['hours'], 2), (int) $calendar_data['totals']['entries_count'], number_format_i18n((float) $calendar_data['totals']['approved_hours'], 2), number_format_i18n((float) $calendar_data['totals']['submitted_hours'], 2), number_format_i18n((float) $calendar_data['totals']['rejected_hours'], 2))); ?></p>
                <table class="widefat striped erp-omd-calendar-table">
                    <thead><tr><th><?php esc_html_e('Pon', 'erp-omd'); ?></th><th><?php esc_html_e('Wt', 'erp-omd'); ?></th><th><?php esc_html_e('Śr', 'erp-omd'); ?></th><th><?php esc_html_e('Czw', 'erp-omd'); ?></th><th><?php esc_html_e('Pt', 'erp-omd'); ?></th><th><?php esc_html_e('Sob', 'erp-omd'); ?></th><th><?php esc_html_e('Nd', 'erp-omd'); ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($calendar_data['weeks'] as $week) : ?>
                        <tr>
                            <?php foreach ($week as $day) : ?>
                                <td class="erp-omd-calendar-cell">
                                    <?php if ($day) : ?>
                                        <div class="erp-omd-calendar-day"><?php echo esc_html($day['day']); ?></div>
                                        <div><strong><?php esc_html_e('Godziny:', 'erp-omd'); ?></strong> <?php echo esc_html(number_format_i18n((float) $day['hours'], 2)); ?></div>
                                        <div><strong><?php esc_html_e('Wpisy:', 'erp-omd'); ?></strong> <?php echo esc_html($day['entries_count']); ?></div>
                                        <div class="description"><?php echo esc_html(sprintf(__('A: %1$s | Z: %2$s | O: %3$s', 'erp-omd'), number_format_i18n((float) $day['approved_hours'], 2), number_format_i18n((float) $day['submitted_hours'], 2), number_format_i18n((float) $day['rejected_hours'], 2))); ?></div>
                                    <?php else : ?>
                                        &nbsp;
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>
    </div>
</div>
