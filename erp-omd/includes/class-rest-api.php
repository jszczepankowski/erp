<?php

class ERP_OMD_REST_API
{
    private $roles;
    private $employees;
    private $salary_history;
    private $employee_service;
    private $monthly_hours_service;
    private $clients;
    private $client_rates;
    private $projects;
    private $estimates;
    private $estimate_items;
    private $project_notes;
    private $client_project_service;
    private $estimate_service;
    private $project_rates;
    private $project_costs;
    private $project_financials;
    private $time_entries;
    private $attachments;
    private $time_entry_service;
    private $project_financial_service;
    private $reporting_service;
    private $alert_service;
    private $period_service;
    private $adjustment_audit;


    public function __construct(
        ERP_OMD_Role_Repository $roles,
        ERP_OMD_Employee_Repository $employees,
        ERP_OMD_Salary_History_Repository $salary_history,
        ERP_OMD_Employee_Service $employee_service,
        ERP_OMD_Monthly_Hours_Service $monthly_hours_service,
        ERP_OMD_Client_Repository $clients,
        ERP_OMD_Client_Rate_Repository $client_rates,
        ERP_OMD_Project_Repository $projects,
        ERP_OMD_Estimate_Repository $estimates,
        ERP_OMD_Estimate_Item_Repository $estimate_items,
        ERP_OMD_Project_Note_Repository $project_notes,
        ERP_OMD_Client_Project_Service $client_project_service,
        ERP_OMD_Estimate_Service $estimate_service,
        ERP_OMD_Project_Rate_Repository $project_rates,
        ERP_OMD_Project_Cost_Repository $project_costs,
        ERP_OMD_Project_Financial_Repository $project_financials,
        ERP_OMD_Time_Entry_Repository $time_entries,
        ERP_OMD_Attachment_Repository $attachments,
        ERP_OMD_Time_Entry_Service $time_entry_service,
        ERP_OMD_Project_Financial_Service $project_financial_service,
        ERP_OMD_Reporting_Service $reporting_service,
        ERP_OMD_Alert_Service $alert_service,
        $period_service = null,
        $adjustment_audit_repository = null

    ) {
        $this->roles = $roles;
        $this->employees = $employees;
        $this->salary_history = $salary_history;
        $this->employee_service = $employee_service;
        $this->monthly_hours_service = $monthly_hours_service;
        $this->clients = $clients;
        $this->client_rates = $client_rates;
        $this->projects = $projects;
        $this->estimates = $estimates;
        $this->estimate_items = $estimate_items;
        $this->project_notes = $project_notes;
        $this->client_project_service = $client_project_service;
        $this->estimate_service = $estimate_service;
        $this->project_rates = $project_rates;
        $this->project_costs = $project_costs;
        $this->project_financials = $project_financials;
        $this->time_entries = $time_entries;
        $this->attachments = $attachments;
        $this->time_entry_service = $time_entry_service;
        $this->project_financial_service = $project_financial_service;
        $this->reporting_service = $reporting_service;
        $this->alert_service = $alert_service;
        $this->period_service = $period_service ?: new ERP_OMD_Period_Service(new ERP_OMD_Period_Repository());
        $this->adjustment_audit = $adjustment_audit_repository ?: new ERP_OMD_Adjustment_Audit_Repository();
    }

    public function register_hooks()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        $this->register_role_routes();
        $this->register_employee_routes();
        $this->register_client_routes();
        $this->register_estimate_routes();
        $this->register_project_routes();
        $this->register_time_routes();
        $this->register_report_routes();
        register_rest_route('erp-omd/v1', '/periods', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => function () {
                return rest_ensure_response($this->period_service->list_periods());
            }, 'permission_callback' => [$this, 'can_access_reports']],
        ]);
        register_rest_route('erp-omd/v1', '/periods/(?P<month>\\d{4}-\\d{2})', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => function (WP_REST_Request $request) {
                $month = sanitize_text_field((string) $request['month']);
                $period = $this->period_service->ensure_month_exists($month, get_current_user_id());
                $signals = $this->readiness_signals_for_month($month);
                $checklist = $this->period_service->build_readiness_checklist($signals);
                return rest_ensure_response([
                    'period' => $period,
                    'checklist' => $checklist,
                    'readiness_signals' => [
                        'time_entries_finalized' => (bool) ($signals['time_entries_finalized'] ?? false),
                        'project_costs_verified' => (bool) ($signals['project_costs_verified'] ?? false),
                        'project_client_completeness' => (bool) ($signals['project_client_completeness'] ?? false),
                        'critical_settlement_locks' => (bool) ($signals['critical_settlement_locks'] ?? false),
                    ],
                    'readiness_meta' => (array) ($signals['_meta'] ?? []),
                ]);
            }, 'permission_callback' => [$this, 'can_access_reports']],
        ]);
        register_rest_route('erp-omd/v1', '/periods/(?P<month>\\d{4}-\\d{2})/transition', [
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => function (WP_REST_Request $request) {
                $month = sanitize_text_field((string) $request['month']);
                $to_status = sanitize_text_field((string) $request->get_param('to_status'));
                if (! in_array($to_status, [ERP_OMD_Period_Service::STATUS_DO_ROZLICZENIA, ERP_OMD_Period_Service::STATUS_ZAMKNIETY], true)) {
                    return new WP_Error('erp_omd_period_transition_invalid', __('Invalid target period status.', 'erp-omd'), ['status' => 422]);
                }

                try {
                    $result = $this->period_service->transition_month($month, $to_status, $this->readiness_signals_for_month($month));
                } catch (InvalidArgumentException $exception) {
                    return new WP_Error('erp_omd_period_transition_blocked', $exception->getMessage(), ['status' => 422]);
                } catch (RuntimeException $exception) {
                    return new WP_Error('erp_omd_period_transition_failed', $exception->getMessage(), ['status' => 500]);
                }

                return rest_ensure_response($result);
            }, 'permission_callback' => [$this, 'can_manage_settings']],
        ]);
        register_rest_route('erp-omd/v1', '/adjustments', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => function (WP_REST_Request $request) {
                $filters = [
                    'month' => sanitize_text_field((string) $request->get_param('month')),
                    'entity_type' => sanitize_text_field((string) $request->get_param('entity_type')),
                ];

                return rest_ensure_response($this->adjustment_audit->all(array_filter($filters)));
            }, 'permission_callback' => [$this, 'can_manage_settings']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => function (WP_REST_Request $request) {
                $month = sanitize_text_field((string) $request->get_param('month'));
                if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
                    return new WP_Error('erp_omd_adjustment_month_invalid', __('Adjustment month is required in YYYY-MM format.', 'erp-omd'), ['status' => 422]);
                }

                $entity_type = sanitize_text_field((string) $request->get_param('entity_type'));
                $entity_id = (int) $request->get_param('entity_id');
                $field_name = sanitize_key((string) $request->get_param('field_name'));
                $reason = $this->sanitize_adjustment_reason($request);
                if ($entity_type === '' || $entity_id <= 0 || $field_name === '' || $reason === '') {
                    return new WP_Error('erp_omd_adjustment_invalid', __('entity_type, entity_id, field_name and reason are required.', 'erp-omd'), ['status' => 422]);
                }

                $payload = [
                    'month' => $month,
                    'entity_type' => $entity_type,
                    'entity_id' => $entity_id,
                    'field_name' => $field_name,
                    'old_value' => $request->get_param('old_value') !== null ? wp_json_encode($request->get_param('old_value')) : null,
                    'new_value' => $request->get_param('new_value') !== null ? wp_json_encode($request->get_param('new_value')) : null,
                    'reason' => $reason,
                    'adjustment_type' => $this->resolve_adjustment_type($month),
                    'changed_by' => (int) get_current_user_id(),
                    'changed_at' => current_time('mysql'),
                ];

                $id = $this->adjustment_audit->create($payload);
                $created = method_exists($this->adjustment_audit, 'find') ? $this->adjustment_audit->find($id) : array_merge(['id' => $id], $payload);

                return new WP_REST_Response($created, 201);
            }, 'permission_callback' => [$this, 'can_manage_settings']],
        ]);
        register_rest_route('erp-omd/v1', '/dashboard-v1', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => function (WP_REST_Request $request) {
                $month = sanitize_text_field((string) $request->get_param('month'));
                if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
                    $month = gmdate('Y-m');
                }

                $scope = sanitize_key((string) $request->get_param('profitability_scope'));
                if (! in_array($scope, ['client', 'project'], true)) {
                    $scope = 'project';
                }
                $mode = sanitize_text_field((string) $request->get_param('mode'));
                if (! in_array($mode, [ERP_OMD_Period_Service::STATUS_LIVE, ERP_OMD_Period_Service::STATUS_DO_ROZLICZENIA, ERP_OMD_Period_Service::STATUS_ZAMKNIETY], true)) {
                    $mode = ERP_OMD_Period_Service::STATUS_LIVE;
                }
                $adjustments_limit = (int) $request->get_param('adjustments_limit');
                if ($adjustments_limit <= 0) {
                    $adjustments_limit = 10;
                }
                if ($adjustments_limit > 50) {
                    $adjustments_limit = 50;
                }
                $queue_limit = (int) $request->get_param('queue_limit');
                if ($queue_limit <= 0) {
                    $queue_limit = 25;
                }
                if ($queue_limit > 200) {
                    $queue_limit = 200;
                }
                $profitability_limit = (int) $request->get_param('profitability_limit');
                if ($profitability_limit <= 0) {
                    $profitability_limit = 5;
                }
                if ($profitability_limit > 20) {
                    $profitability_limit = 20;
                }

                $period = $this->period_service->ensure_month_exists($month, get_current_user_id());
                $readiness_signals = $this->readiness_signals_for_month($month);
                $readiness_checklist = $this->period_service->build_readiness_checklist($readiness_signals);
                $trend = $this->reporting_service->build_report('omd_rozliczenia', ['month' => $month, 'report_type' => 'omd_rozliczenia', 'mode' => $mode]);
                $trend_3m = array_slice((array) $trend, -3);
                $project_rows = $this->reporting_service->build_project_report(['month' => $month, 'report_type' => 'projects', 'mode' => $mode]);
                $client_rows = $this->reporting_service->build_client_report(['month' => $month, 'report_type' => 'clients', 'mode' => $mode]);
                $queue_rows = (array) $this->reporting_service->build_invoice_report(['month' => $month, 'report_type' => 'invoice', 'mode' => $mode]);
                $queue_rows_total = count($queue_rows);
                $queue_rows = $this->enrich_queue_rows($queue_rows, $month, $queue_limit);
                $adjustments = $this->adjustment_audit->all(['month' => $month]);

                $source_rows = $scope === 'client' ? $client_rows : $project_rows;
                $ranked_scope = $this->rank_profitability_rows($source_rows, $profitability_limit);
                $ranked_projects = $this->rank_profitability_rows($project_rows, $profitability_limit);
                $ranked_clients = $this->rank_profitability_rows($client_rows, $profitability_limit);
                $ranked_scope = $this->enrich_ranked_profitability_rows($ranked_scope, $scope, $month);
                $ranked_projects = $this->enrich_ranked_profitability_rows($ranked_projects, 'project', $month);
                $ranked_clients = $this->enrich_ranked_profitability_rows($ranked_clients, 'client', $month);

                $adjustment_impact = 0.0;
                foreach ($adjustments as $row) {
                    $old = json_decode((string) ($row['old_value'] ?? ''), true);
                    $new = json_decode((string) ($row['new_value'] ?? ''), true);
                    if (is_array($old) && is_array($new)) {
                        $old_amount = (float) ($old['amount'] ?? $old['hours'] ?? 0);
                        $new_amount = (float) ($new['amount'] ?? $new['hours'] ?? 0);
                        $adjustment_impact += ($new_amount - $old_amount);
                    }
                }
                $adjustment_items = $this->enrich_adjustment_rows($adjustments, $month, $adjustments_limit);

                return rest_ensure_response([
                    'api_version' => 'v1',
                    'generated_at' => current_time('mysql'),
                    'applied_limits' => [
                        'adjustments_items' => $adjustments_limit,
                        'queue_items' => $queue_limit,
                        'profitability_items' => $profitability_limit,
                    ],
                    'month' => $month,
                    'mode' => $mode,
                    'status_month' => $period,
                    'readiness_checklist' => $readiness_checklist,
                    'readiness_meta' => (array) ($readiness_signals['_meta'] ?? []),
                    'status_actions' => $this->dashboard_status_actions($period, $readiness_checklist),
                    'metric_definitions' => $this->dashboard_metric_definitions(),
                    'drilldown_links' => $this->dashboard_drilldown_links($month),
                    'trend_3m' => $trend_3m,
                    'profitability_scope' => $scope,
                    'profitability_top' => $ranked_scope['top'],
                    'profitability_bottom' => $ranked_scope['bottom'],
                    'profitability_by_scope' => [
                        'project' => $ranked_projects,
                        'client' => $ranked_clients,
                    ],
                    'settlement_queue' => [
                        'count' => $queue_rows_total,
                        'items' => $queue_rows,
                    ],
                    'adjustments' => [
                        'count' => count($adjustments),
                        'impact' => round($adjustment_impact, 2),
                        'items' => $adjustment_items,
                    ],
                ]);
            }, 'permission_callback' => [$this, 'can_access_reports']],
        ]);
        $this->register_hardening_routes();
    }

    private function dashboard_metric_definitions()
    {
        return [
            'trend_3m' => __('Three most recent month rows from omd_rozliczenia report.', 'erp-omd'),
            'profitability_top' => __('Top 5 entities by margin in selected scope.', 'erp-omd'),
            'profitability_bottom' => __('Bottom 5 entities by margin in selected scope.', 'erp-omd'),
            'settlement_queue.count' => __('Number of rows in invoice settlement queue for selected month.', 'erp-omd'),
            'adjustments.impact' => __('Sum of (new-old) amount/hours deltas from adjustment audit rows.', 'erp-omd'),
            'adjustments.items' => __('Recent adjustment audit rows limited by adjustments_limit.', 'erp-omd'),
            'readiness_checklist.ready' => __('Boolean period-close readiness based on checklist validators.', 'erp-omd'),
            'applied_limits' => __('Server-applied list limits after defaulting and max clamping.', 'erp-omd'),
        ];
    }

    private function dashboard_drilldown_links($month)
    {
        $base = '/wp-admin/admin.php?page=erp-omd-reports';
        if (function_exists('admin_url')) {
            $base = admin_url('admin.php?page=erp-omd-reports');
        }

        return [
            'settlement_queue' => $base . '&report_type=invoice&month=' . rawurlencode((string) $month),
            'adjustments' => $base . '&report_type=time&month=' . rawurlencode((string) $month) . '&adjustments=1',
            'profitability_projects' => $base . '&report_type=projects&month=' . rawurlencode((string) $month),
            'profitability_clients' => $base . '&report_type=clients&month=' . rawurlencode((string) $month),
        ];
    }

    private function rank_profitability_rows(array $rows, $limit = 5)
    {
        usort($rows, static function ($a, $b) {
            return (float) ($b['margin'] ?? 0) <=> (float) ($a['margin'] ?? 0);
        });

        $limit = max(1, (int) $limit);

        return [
            'top' => array_slice($rows, 0, $limit),
            'bottom' => array_slice(array_reverse($rows), 0, $limit),
        ];
    }

    private function enrich_ranked_profitability_rows(array $ranked, $scope, $month)
    {
        foreach (['top', 'bottom'] as $bucket) {
            if (! isset($ranked[$bucket]) || ! is_array($ranked[$bucket])) {
                continue;
            }

            $ranked[$bucket] = array_map(function ($row) use ($scope, $month) {
                if (! is_array($row)) {
                    return $row;
                }

                $row['drilldown_link'] = $this->profitability_row_drilldown_link($scope, $row, $month);
                return $row;
            }, $ranked[$bucket]);
        }

        return $ranked;
    }

    private function profitability_row_drilldown_link($scope, array $row, $month)
    {
        $base = '/wp-admin/admin.php?page=erp-omd-reports';
        if (function_exists('admin_url')) {
            $base = admin_url('admin.php?page=erp-omd-reports');
        }

        if ($scope === 'client') {
            return $base . '&report_type=clients&month=' . rawurlencode((string) $month) . '&client_id=' . (int) ($row['id'] ?? 0);
        }

        return $base . '&report_type=projects&month=' . rawurlencode((string) $month) . '&project_id=' . (int) ($row['id'] ?? 0);
    }

    private function enrich_queue_rows(array $rows, $month, $limit = 25)
    {
        return array_map(function ($row) use ($month) {
            if (! is_array($row)) {
                return $row;
            }

            $base = '/wp-admin/admin.php?page=erp-omd-reports';
            if (function_exists('admin_url')) {
                $base = admin_url('admin.php?page=erp-omd-reports');
            }

            $link = $base . '&report_type=invoice&month=' . rawurlencode((string) $month);
            if (isset($row['project_id'])) {
                $link .= '&project_id=' . (int) $row['project_id'];
            }
            if (isset($row['client_id'])) {
                $link .= '&client_id=' . (int) $row['client_id'];
            }

            $row['drilldown_link'] = $link;
            return $row;
        }, array_slice($rows, 0, max(1, (int) $limit)));
    }

    private function enrich_adjustment_rows(array $rows, $month, $limit = 10)
    {
        $base = '/wp-admin/admin.php?page=erp-omd-reports&report_type=time';
        if (function_exists('admin_url')) {
            $base = admin_url('admin.php?page=erp-omd-reports&report_type=time');
        }

        return array_map(function ($row) use ($month, $base) {
            if (! is_array($row)) {
                return $row;
            }

            $entity_type = (string) ($row['entity_type'] ?? '');
            $entity_id = (int) ($row['entity_id'] ?? 0);
            $drilldown_link = $base . '&month=' . rawurlencode((string) $month) . '&adjustments=1';
            if ($entity_type !== '') {
                $drilldown_link .= '&entity_type=' . rawurlencode($entity_type);
            }
            if ($entity_id > 0) {
                $drilldown_link .= '&entity_id=' . $entity_id;
            }

            return [
                'id' => (int) ($row['id'] ?? 0),
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'field_name' => (string) ($row['field_name'] ?? ''),
                'adjustment_type' => (string) ($row['adjustment_type'] ?? ''),
                'reason' => (string) ($row['reason'] ?? ''),
                'changed_at' => (string) ($row['changed_at'] ?? ''),
                'drilldown_link' => $drilldown_link,
            ];
        }, array_slice($rows, 0, max(1, (int) $limit)));
    }

    private function dashboard_status_actions(array $period, array $checklist)
    {
        $status = (string) ($period['status'] ?? ERP_OMD_Period_Service::STATUS_LIVE);
        $targets = [
            ERP_OMD_Period_Service::STATUS_DO_ROZLICZENIA,
            ERP_OMD_Period_Service::STATUS_ZAMKNIETY,
        ];

        $actions = [];
        foreach ($targets as $target_status) {
            $can_transition = $this->period_service->can_transition($status, $target_status);
            if (! $can_transition) {
                continue;
            }

            $enabled = true;
            $reason = '';
            if (
                $status === ERP_OMD_Period_Service::STATUS_LIVE
                && $target_status === ERP_OMD_Period_Service::STATUS_DO_ROZLICZENIA
                && ! (bool) ($checklist['ready'] ?? false)
            ) {
                $enabled = false;
                $reason = __('LIVE -> DO_ROZLICZENIA requires readiness checklist == ready.', 'erp-omd');
            }

            $actions[] = [
                'to_status' => $target_status,
                'enabled' => $enabled,
                'reason' => $reason,
            ];
        }

        return $actions;
    }

    private function register_role_routes()
    {
        register_rest_route('erp-omd/v1', '/roles', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_roles'], 'permission_callback' => [$this, 'can_manage_roles']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_role'], 'permission_callback' => [$this, 'can_manage_roles']],
        ]);
        register_rest_route('erp-omd/v1', '/roles/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_role'], 'permission_callback' => [$this, 'can_manage_roles']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_role'], 'permission_callback' => [$this, 'can_manage_roles']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_role'], 'permission_callback' => [$this, 'can_manage_roles']],
        ]);
    }

    private function register_employee_routes()
    {
        register_rest_route('erp-omd/v1', '/employees', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_employees'], 'permission_callback' => [$this, 'can_manage_employees']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_employee'], 'permission_callback' => [$this, 'can_manage_employees']],
        ]);
        register_rest_route('erp-omd/v1', '/employees/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_employee'], 'permission_callback' => [$this, 'can_manage_employees']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_employee'], 'permission_callback' => [$this, 'can_manage_employees']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_employee'], 'permission_callback' => [$this, 'can_manage_employees']],
        ]);
        register_rest_route('erp-omd/v1', '/employees/(?P<id>\d+)/salary', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_salary_history'], 'permission_callback' => [$this, 'can_manage_salary']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_salary_history'], 'permission_callback' => [$this, 'can_manage_salary']],
        ]);
        register_rest_route('erp-omd/v1', '/salary/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_salary_history'], 'permission_callback' => [$this, 'can_manage_salary']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_salary_history'], 'permission_callback' => [$this, 'can_manage_salary']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_salary_history'], 'permission_callback' => [$this, 'can_manage_salary']],
        ]);
        register_rest_route('erp-omd/v1', '/monthly-hours/(?P<year_month>\d{4}-\d{2})', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_monthly_hours_suggestion'], 'permission_callback' => [$this, 'can_manage_salary']],
        ]);
    }

    private function register_client_routes()
    {
        register_rest_route('erp-omd/v1', '/clients', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_clients'], 'permission_callback' => [$this, 'can_manage_clients']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_client'], 'permission_callback' => [$this, 'can_manage_clients']],
        ]);
        register_rest_route('erp-omd/v1', '/clients/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_client'], 'permission_callback' => [$this, 'can_manage_clients']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_client'], 'permission_callback' => [$this, 'can_manage_clients']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_client'], 'permission_callback' => [$this, 'can_manage_clients']],
        ]);
        register_rest_route('erp-omd/v1', '/clients/(?P<id>\d+)/rates', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_client_rates'], 'permission_callback' => [$this, 'can_manage_clients']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_client_rate'], 'permission_callback' => [$this, 'can_manage_clients']],
        ]);
        register_rest_route('erp-omd/v1', '/client-rates/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_client_rate'], 'permission_callback' => [$this, 'can_manage_clients']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_client_rate'], 'permission_callback' => [$this, 'can_manage_clients']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_client_rate'], 'permission_callback' => [$this, 'can_manage_clients']],
        ]);
    }

    private function register_estimate_routes()
    {
        register_rest_route('erp-omd/v1', '/estimates', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_estimates'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_estimate'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/estimates/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_estimate'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_estimate'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_estimate'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/estimates/(?P<id>\d+)/items', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_estimate_items'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_estimate_item'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/estimate-items/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_estimate_item'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_estimate_item'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_estimate_item'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/estimates/(?P<id>\d+)/accept', [
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'accept_estimate'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
    }

    private function register_project_routes()
    {
        register_rest_route('erp-omd/v1', '/projects', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_projects'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_project'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/projects/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_project'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_project'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_project'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/projects/(?P<id>\d+)/notes', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_project_notes'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_project_note'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/projects/(?P<id>\d+)/rates', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_project_rates'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_project_rate'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/projects/(?P<id>\d+)/costs', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_project_costs'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_project_cost'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/projects/(?P<id>\d+)/finance', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_project_finance'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/project-rates/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_project_rate'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_project_rate'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_project_rate'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/project-costs/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_project_cost'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_project_cost'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_project_cost'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
    }

    private function register_time_routes()
    {
        register_rest_route('erp-omd/v1', '/time', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_time_entries'], 'permission_callback' => [$this, 'can_manage_time']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_time_entry'], 'permission_callback' => [$this, 'can_manage_time']],
        ]);
        register_rest_route('erp-omd/v1', '/time/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_time_entry'], 'permission_callback' => [$this, 'can_manage_time']],
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_time_entry'], 'permission_callback' => [$this, 'can_manage_time']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_time_entry'], 'permission_callback' => [$this, 'can_manage_time']],
        ]);
        register_rest_route('erp-omd/v1', '/time/(?P<id>\d+)/status', [
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'change_time_entry_status'], 'permission_callback' => [$this, 'can_approve_time']],
        ]);
    }

    private function register_report_routes()
    {
        register_rest_route('erp-omd/v1', '/reports', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_reports'], 'permission_callback' => [$this, 'can_access_reports']],
        ]);
        register_rest_route('erp-omd/v1', '/reports/export', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'export_report_definition'], 'permission_callback' => [$this, 'can_access_reports']],
        ]);
        register_rest_route('erp-omd/v1', '/calendar', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_calendar'], 'permission_callback' => [$this, 'can_access_reports']],
        ]);
    }

    private function register_hardening_routes()
    {
        register_rest_route('erp-omd/v1', '/alerts', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_alerts'], 'permission_callback' => [$this, 'can_access_reports']],
        ]);
        register_rest_route('erp-omd/v1', '/attachments', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'list_attachments'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'create_attachment'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/attachments/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_attachment'], 'permission_callback' => [$this, 'can_manage_projects']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_attachment'], 'permission_callback' => [$this, 'can_manage_projects']],
        ]);
        register_rest_route('erp-omd/v1', '/meta', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_meta'], 'permission_callback' => [$this, 'can_access_reports']],
        ]);
        register_rest_route('erp-omd/v1', '/system', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_system_status'], 'permission_callback' => [$this, 'can_manage_settings']],
        ]);
    }


    public function can_manage_roles() { return current_user_can('erp_omd_manage_roles'); }
    public function can_manage_employees() { return current_user_can('erp_omd_manage_employees'); }
    public function can_manage_salary() { return current_user_can('erp_omd_manage_salary'); }
    public function can_manage_clients() { return current_user_can('erp_omd_manage_clients'); }
    public function can_manage_projects() { return current_user_can('erp_omd_manage_projects'); }
    public function can_manage_time() { return current_user_can('erp_omd_manage_time'); }
    public function can_approve_time() { return current_user_can('erp_omd_approve_time') || current_user_can('administrator'); }
    public function can_access_reports() { return current_user_can('erp_omd_access') || current_user_can('administrator'); }
    public function can_manage_settings() { return current_user_can('erp_omd_manage_settings') || current_user_can('administrator'); }


    // existing modules omitted no, implemented below.
    public function list_roles() { return rest_ensure_response($this->roles->all()); }
    public function get_role(WP_REST_Request $request) { return $this->find_or_error($this->roles->find((int) $request['id']), 'erp_omd_role_not_found', __('Role not found.', 'erp-omd')); }
    public function create_role(WP_REST_Request $request) { $payload = $this->sanitize_role_payload($request); if ($this->roles->slug_exists($payload['slug'])) { return new WP_Error('erp_omd_role_slug_exists', __('Role slug must be unique.', 'erp-omd'), ['status' => 422]); } $id = $this->roles->create($payload); return new WP_REST_Response($this->roles->find($id), 201); }
    public function update_role(WP_REST_Request $request) { $id = (int) $request['id']; if (! $this->roles->find($id)) { return new WP_Error('erp_omd_role_not_found', __('Role not found.', 'erp-omd'), ['status' => 404]); } $payload = $this->sanitize_role_payload($request); if ($this->roles->slug_exists($payload['slug'], $id)) { return new WP_Error('erp_omd_role_slug_exists', __('Role slug must be unique.', 'erp-omd'), ['status' => 422]); } $this->roles->update($id, $payload); return rest_ensure_response($this->roles->find($id)); }
    public function delete_role(WP_REST_Request $request) { $this->roles->delete((int) $request['id']); return new WP_REST_Response(null, 204); }

    public function list_employees() { return rest_ensure_response($this->employees->all()); }
    public function get_employee(WP_REST_Request $request) { return $this->find_or_error($this->employees->find((int) $request['id']), 'erp_omd_employee_not_found', __('Employee not found.', 'erp-omd')); }
    public function create_employee(WP_REST_Request $request) { $payload = $this->sanitize_employee_payload($request); $errors = $this->employee_service->validate_employee($payload); if ($errors) { return new WP_Error('erp_omd_employee_invalid', implode(' ', $errors), ['status' => 422]); } $id = $this->employees->create($payload); $this->sync_wp_role($payload['user_id'], $payload['account_type']); return new WP_REST_Response($this->employees->find($id), 201); }
    public function update_employee(WP_REST_Request $request) { $id = (int) $request['id']; if (! $this->employees->find($id)) { return new WP_Error('erp_omd_employee_not_found', __('Employee not found.', 'erp-omd'), ['status' => 404]); } $payload = $this->sanitize_employee_payload($request); $errors = $this->employee_service->validate_employee($payload, $id); if ($errors) { return new WP_Error('erp_omd_employee_invalid', implode(' ', $errors), ['status' => 422]); } $this->employees->update($id, $payload); $this->sync_wp_role($payload['user_id'], $payload['account_type']); return rest_ensure_response($this->employees->find($id)); }
    public function delete_employee(WP_REST_Request $request) { $this->employees->deactivate((int) $request['id']); return new WP_REST_Response(null, 204); }
    public function list_salary_history(WP_REST_Request $request) { return rest_ensure_response($this->salary_history->for_employee((int) $request['id'])); }
    public function create_salary_history(WP_REST_Request $request) { $payload = $this->employee_service->prepare_salary_payload($this->sanitize_salary_payload($request, (int) $request['id'])); $errors = $this->employee_service->validate_salary($payload); if ($errors) { return new WP_Error('erp_omd_salary_invalid', implode(' ', $errors), ['status' => 422]); } $id = $this->salary_history->create($payload); return new WP_REST_Response($this->salary_history->find($id), 201); }
    public function get_salary_history(WP_REST_Request $request) { return $this->find_or_error($this->salary_history->find((int) $request['id']), 'erp_omd_salary_not_found', __('Salary history not found.', 'erp-omd')); }
    public function update_salary_history(WP_REST_Request $request) { $id = (int) $request['id']; $existing = $this->salary_history->find($id); if (! $existing) { return new WP_Error('erp_omd_salary_not_found', __('Salary history not found.', 'erp-omd'), ['status' => 404]); } $payload = $this->employee_service->prepare_salary_payload($this->sanitize_salary_payload($request, (int) $existing['employee_id'])); $errors = $this->employee_service->validate_salary($payload, $id); if ($errors) { return new WP_Error('erp_omd_salary_invalid', implode(' ', $errors), ['status' => 422]); } $this->salary_history->update($id, $payload); return rest_ensure_response($this->salary_history->find($id)); }
    public function delete_salary_history(WP_REST_Request $request) { $this->salary_history->delete((int) $request['id']); return new WP_REST_Response(null, 204); }
    public function get_monthly_hours_suggestion(WP_REST_Request $request) { return rest_ensure_response(['year_month' => (string) $request['year_month'], 'suggested_hours' => $this->monthly_hours_service->suggested_hours((string) $request['year_month'])]); }

    public function list_clients() { return rest_ensure_response($this->clients->all()); }
    public function get_client(WP_REST_Request $request) { return $this->find_or_error($this->clients->find((int) $request['id']), 'erp_omd_client_not_found', __('Client not found.', 'erp-omd')); }
    public function create_client(WP_REST_Request $request) { $payload = $this->client_project_service->prepare_client($this->sanitize_client_payload($request)); $errors = $this->client_project_service->validate_client($payload); if ($errors) { return new WP_Error('erp_omd_client_invalid', implode(' ', $errors), ['status' => 422]); } $id = $this->clients->create($payload); return new WP_REST_Response($this->clients->find($id), 201); }
    public function update_client(WP_REST_Request $request) { $id = (int) $request['id']; $existing = $this->clients->find($id); if (! $existing) { return new WP_Error('erp_omd_client_not_found', __('Client not found.', 'erp-omd'), ['status' => 404]); } $payload = $this->client_project_service->prepare_client(array_merge($existing, $this->sanitize_client_payload($request))); $errors = $this->client_project_service->validate_client($payload, $id); if ($errors) { return new WP_Error('erp_omd_client_invalid', implode(' ', $errors), ['status' => 422]); } $this->clients->update($id, $payload); return rest_ensure_response($this->clients->find($id)); }
    public function delete_client(WP_REST_Request $request) { $this->clients->deactivate((int) $request['id']); return new WP_REST_Response(null, 204); }
    public function list_client_rates(WP_REST_Request $request) { return rest_ensure_response($this->client_rates->for_client((int) $request['id'])); }
    public function create_client_rate(WP_REST_Request $request) { $client_id = (int) $request['id']; $role_id = (int) $request->get_param('role_id'); $rate = (float) $request->get_param('rate'); $errors = $this->client_project_service->validate_client_rate($client_id, $role_id, $rate); if ($errors) { return new WP_Error('erp_omd_client_rate_invalid', implode(' ', $errors), ['status' => 422]); } $id = $this->client_rates->upsert($client_id, $role_id, $rate); return new WP_REST_Response($this->client_rates->find($id), 201); }
    public function get_client_rate(WP_REST_Request $request) { return $this->find_or_error($this->client_rates->find((int) $request['id']), 'erp_omd_client_rate_not_found', __('Client rate not found.', 'erp-omd')); }
    public function update_client_rate(WP_REST_Request $request) { $id = (int) $request['id']; $existing = $this->client_rates->find($id); if (! $existing) { return new WP_Error('erp_omd_client_rate_not_found', __('Client rate not found.', 'erp-omd'), ['status' => 404]); } $role_id = (int) ($request->get_param('role_id') ?: $existing['role_id']); $rate = (float) ($request->get_param('rate') ?: $existing['rate']); $errors = $this->client_project_service->validate_client_rate((int) $existing['client_id'], $role_id, $rate); if ($errors) { return new WP_Error('erp_omd_client_rate_invalid', implode(' ', $errors), ['status' => 422]); } $upserted_id = $this->client_rates->upsert((int) $existing['client_id'], $role_id, $rate); if ($upserted_id !== $id) { $this->client_rates->delete($id); } return rest_ensure_response($this->client_rates->find($upserted_id)); }
    public function delete_client_rate(WP_REST_Request $request) { $this->client_rates->delete((int) $request['id']); return new WP_REST_Response(null, 204); }

    public function list_estimates()
    {
        $estimates = $this->estimates->all();
        foreach ($estimates as &$estimate) {
            $items = $this->estimate_items->for_estimate((int) $estimate['id']);
            $estimate['totals'] = $this->estimate_service->calculate_totals($items);
        }
        unset($estimate);

        return rest_ensure_response($estimates);
    }

    public function get_estimate(WP_REST_Request $request)
    {
        $estimate = $this->estimates->find((int) $request['id']);
        if (! $estimate) {
            return new WP_Error('erp_omd_estimate_not_found', __('Estimate not found.', 'erp-omd'), ['status' => 404]);
        }
        $estimate['items'] = $this->estimate_items->for_estimate((int) $estimate['id']);
        $estimate['totals'] = $this->estimate_service->calculate_totals($estimate['items']);

        return rest_ensure_response($estimate);
    }

    public function create_estimate(WP_REST_Request $request)
    {
        $payload = $this->sanitize_estimate_payload($request);
        $errors = $this->estimate_service->validate_estimate($payload);
        if ($errors) {
            return new WP_Error('erp_omd_estimate_invalid', implode(' ', $errors), ['status' => 422]);
        }
        $id = $this->estimates->create($payload);

        return new WP_REST_Response($this->estimates->find($id), 201);
    }

    public function update_estimate(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $existing = $this->estimates->find($id);
        if (! $existing) {
            return new WP_Error('erp_omd_estimate_not_found', __('Estimate not found.', 'erp-omd'), ['status' => 404]);
        }
        $payload = $this->sanitize_estimate_payload($request, $existing);
        $errors = $this->estimate_service->validate_estimate($payload, $existing);
        if ($errors) {
            return new WP_Error('erp_omd_estimate_invalid', implode(' ', $errors), ['status' => 422]);
        }

        $should_accept_via_status = ($existing['status'] ?? '') !== 'zaakceptowany' && $payload['status'] === 'zaakceptowany';
        if ($should_accept_via_status) {
            $update_payload = $payload;
            $update_payload['status'] = (string) ($existing['status'] ?? 'wstepny');
            $this->estimates->update($id, $update_payload);
            $accept_result = $this->estimate_service->accept($id);
            if ($accept_result instanceof WP_Error) {
                return $accept_result;
            }
        } else {
            $this->estimates->update($id, $payload);
        }

        return rest_ensure_response($this->estimates->find($id));
    }

    public function delete_estimate(WP_REST_Request $request)
    {
        $existing = $this->estimates->find((int) $request['id']);
        if (! $existing) {
            return new WP_REST_Response(null, 204);
        }
        if (($existing['status'] ?? '') === 'zaakceptowany') {
            return new WP_Error('erp_omd_estimate_locked', __('Accepted estimate cannot be deleted.', 'erp-omd'), ['status' => 422]);
        }
        $this->estimates->delete((int) $request['id']);

        return new WP_REST_Response(null, 204);
    }

    public function list_estimate_items(WP_REST_Request $request)
    {
        $estimate = $this->estimates->find((int) $request['id']);
        if (! $estimate) {
            return new WP_Error('erp_omd_estimate_not_found', __('Estimate not found.', 'erp-omd'), ['status' => 404]);
        }

        return rest_ensure_response($this->estimate_items->for_estimate((int) $estimate['id']));
    }

    public function create_estimate_item(WP_REST_Request $request)
    {
        $estimate = $this->estimates->find((int) $request['id']);
        $payload = $this->sanitize_estimate_item_payload($request, (int) $request['id']);
        $errors = $this->estimate_service->validate_item($payload, $estimate);
        if ($errors) {
            return new WP_Error('erp_omd_estimate_item_invalid', implode(' ', $errors), ['status' => 422]);
        }
        $id = $this->estimate_items->create($payload);

        return new WP_REST_Response($this->estimate_items->find($id), 201);
    }

    public function get_estimate_item(WP_REST_Request $request)
    {
        $item = $this->estimate_items->find((int) $request['id']);
        if (! $item) {
            return new WP_Error('erp_omd_estimate_item_not_found', __('Estimate item not found.', 'erp-omd'), ['status' => 404]);
        }

        return rest_ensure_response($item);
    }

    public function update_estimate_item(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $existing = $this->estimate_items->find($id);
        if (! $existing) {
            return new WP_Error('erp_omd_estimate_item_not_found', __('Estimate item not found.', 'erp-omd'), ['status' => 404]);
        }
        $estimate = $this->estimates->find((int) $existing['estimate_id']);
        $payload = $this->sanitize_estimate_item_payload($request, (int) $existing['estimate_id'], $existing);
        $errors = $this->estimate_service->validate_item($payload, $estimate, $existing);
        if ($errors) {
            return new WP_Error('erp_omd_estimate_item_invalid', implode(' ', $errors), ['status' => 422]);
        }
        $this->estimate_items->update($id, $payload);

        return rest_ensure_response($this->estimate_items->find($id));
    }

    public function delete_estimate_item(WP_REST_Request $request)
    {
        $existing = $this->estimate_items->find((int) $request['id']);
        if (! $existing) {
            return new WP_REST_Response(null, 204);
        }
        $estimate = $this->estimates->find((int) $existing['estimate_id']);
        if ($estimate && ($estimate['status'] ?? '') === 'zaakceptowany') {
            return new WP_Error('erp_omd_estimate_item_locked', __('Accepted estimate items cannot be deleted.', 'erp-omd'), ['status' => 422]);
        }
        $this->estimate_items->delete((int) $request['id']);

        return new WP_REST_Response(null, 204);
    }

    public function accept_estimate(WP_REST_Request $request)
    {
        $result = $this->estimate_service->accept((int) $request['id']);
        if ($result instanceof WP_Error) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    public function list_projects() { return rest_ensure_response($this->projects->all()); }
    public function get_project(WP_REST_Request $request) { return $this->find_or_error($this->projects->find((int) $request['id']), 'erp_omd_project_not_found', __('Project not found.', 'erp-omd')); }
    public function create_project(WP_REST_Request $request) { $payload = $this->client_project_service->prepare_project($this->sanitize_project_payload($request)); $errors = $this->client_project_service->validate_project($payload); if ($errors) { return new WP_Error('erp_omd_project_invalid', implode(' ', $errors), ['status' => 422]); } $id = $this->projects->create($payload); $this->project_financial_service->rebuild_for_project($id); return new WP_REST_Response($this->projects->find($id), 201); }
    public function update_project(WP_REST_Request $request) { $id = (int) $request['id']; $existing = $this->projects->find($id); if (! $existing) { return new WP_Error('erp_omd_project_not_found', __('Project not found.', 'erp-omd'), ['status' => 404]); } $payload = $this->client_project_service->prepare_project(array_merge($existing, $this->sanitize_project_payload($request)), $existing); $errors = $this->client_project_service->validate_project($payload, $existing); if ($errors) { return new WP_Error('erp_omd_project_invalid', implode(' ', $errors), ['status' => 422]); } $this->projects->update($id, $payload); $this->project_financial_service->rebuild_for_project($id); return rest_ensure_response($this->projects->find($id)); }
    public function delete_project(WP_REST_Request $request) { $this->projects->deactivate((int) $request['id']); return new WP_REST_Response(null, 204); }
    public function list_project_notes(WP_REST_Request $request) { return rest_ensure_response($this->project_notes->for_project((int) $request['id'])); }
    public function create_project_note(WP_REST_Request $request) { $project_id = (int) $request['id']; if (! $this->projects->find($project_id)) { return new WP_Error('erp_omd_project_not_found', __('Project not found.', 'erp-omd'), ['status' => 404]); } $note = sanitize_textarea_field((string) $request->get_param('note')); if ($note === '') { return new WP_Error('erp_omd_project_note_invalid', __('Project note is required.', 'erp-omd'), ['status' => 422]); } $id = $this->project_notes->create($project_id, $note, get_current_user_id()); return new WP_REST_Response($this->project_notes->for_project($project_id)[0] ?? ['id' => $id], 201); }
    public function list_project_rates(WP_REST_Request $request) { return rest_ensure_response($this->project_rates->for_project((int) $request['id'])); }
    public function create_project_rate(WP_REST_Request $request) { $project_id = (int) $request['id']; $role_id = (int) $request->get_param('role_id'); $rate = (float) $request->get_param('rate'); if (! $this->projects->find($project_id) || ! $this->roles->find($role_id) || $rate < 0) { return new WP_Error('erp_omd_project_rate_invalid', __('Project rate payload is invalid.', 'erp-omd'), ['status' => 422]); } $id = $this->project_rates->upsert($project_id, $role_id, $rate); return new WP_REST_Response($this->project_rates->find($id), 201); }
    public function get_project_rate(WP_REST_Request $request) { return $this->find_or_error($this->project_rates->find((int) $request['id']), 'erp_omd_project_rate_not_found', __('Project rate not found.', 'erp-omd')); }
    public function update_project_rate(WP_REST_Request $request) { $id = (int) $request['id']; $existing = $this->project_rates->find($id); if (! $existing) { return new WP_Error('erp_omd_project_rate_not_found', __('Project rate not found.', 'erp-omd'), ['status' => 404]); } $role_id = (int) ($request->get_param('role_id') ?: $existing['role_id']); $rate = (float) ($request->get_param('rate') ?: $existing['rate']); if (! $this->roles->find($role_id) || $rate < 0) { return new WP_Error('erp_omd_project_rate_invalid', __('Project rate payload is invalid.', 'erp-omd'), ['status' => 422]); } $upserted_id = $this->project_rates->upsert((int) $existing['project_id'], $role_id, $rate); if ($upserted_id !== $id) { $this->project_rates->delete($id); } return rest_ensure_response($this->project_rates->find($upserted_id)); }
    public function delete_project_rate(WP_REST_Request $request) { $this->project_rates->delete((int) $request['id']); return new WP_REST_Response(null, 204); }
    public function list_project_costs(WP_REST_Request $request) { return rest_ensure_response($this->project_costs->for_project((int) $request['id'])); }
    public function create_project_cost(WP_REST_Request $request) { $project_id = (int) $request['id']; $payload = $this->sanitize_project_cost_payload($request, $project_id); $month = erp_omd_month_from_date($payload['cost_date']); if ($this->is_month_locked_for_current_user($month)) { return new WP_Error('erp_omd_period_locked', __('Month is locked for non-admin users.', 'erp-omd'), ['status' => 403]); } $reason = $this->sanitize_adjustment_reason($request); if ($this->is_month_locked_for_admin($month) && $reason === '') { return new WP_Error('erp_omd_adjustment_reason_required', __('Adjustment reason is required for locked month admin corrections.', 'erp-omd'), ['status' => 422]); } $errors = $this->project_financial_service->validate_project_cost($payload); if ($errors) { return new WP_Error('erp_omd_project_cost_invalid', implode(' ', $errors), ['status' => 422]); } $id = $this->project_costs->create($payload); $this->project_financial_service->rebuild_for_project($project_id); $created = $this->project_costs->find($id); if ($reason !== '') { $this->log_adjustment_audit($month, 'project_cost', $id, null, $created, $reason); } return new WP_REST_Response($created, 201); }
    public function get_project_cost(WP_REST_Request $request) { return $this->find_or_error($this->project_costs->find((int) $request['id']), 'erp_omd_project_cost_not_found', __('Project cost not found.', 'erp-omd')); }
    public function update_project_cost(WP_REST_Request $request) { $id = (int) $request['id']; $existing = $this->project_costs->find($id); if (! $existing) { return new WP_Error('erp_omd_project_cost_not_found', __('Project cost not found.', 'erp-omd'), ['status' => 404]); } $payload = $this->sanitize_project_cost_payload($request, (int) $existing['project_id']); $month = erp_omd_month_from_date($payload['cost_date']); if ($this->is_month_locked_for_current_user($month)) { return new WP_Error('erp_omd_period_locked', __('Month is locked for non-admin users.', 'erp-omd'), ['status' => 403]); } $reason = $this->sanitize_adjustment_reason($request); if ($this->is_month_locked_for_admin($month) && $reason === '') { return new WP_Error('erp_omd_adjustment_reason_required', __('Adjustment reason is required for locked month admin corrections.', 'erp-omd'), ['status' => 422]); } $errors = $this->project_financial_service->validate_project_cost($payload); if ($errors) { return new WP_Error('erp_omd_project_cost_invalid', implode(' ', $errors), ['status' => 422]); } $this->project_costs->update($id, $payload); $this->project_financial_service->rebuild_for_project((int) $existing['project_id']); $updated = $this->project_costs->find($id); if ($reason !== '') { $this->log_adjustment_audit($month, 'project_cost', $id, $existing, $updated, $reason); } return rest_ensure_response($updated); }
    public function delete_project_cost(WP_REST_Request $request) { $existing = $this->project_costs->find((int) $request['id']); if ($existing) { $month = erp_omd_month_from_date($existing['cost_date'] ?? ''); if ($this->is_month_locked_for_current_user($month)) { return new WP_Error('erp_omd_period_locked', __('Month is locked for non-admin users.', 'erp-omd'), ['status' => 403]); } $reason = $this->sanitize_adjustment_reason($request); if ($this->is_month_locked_for_admin($month) && $reason === '') { return new WP_Error('erp_omd_adjustment_reason_required', __('Adjustment reason is required for locked month admin corrections.', 'erp-omd'), ['status' => 422]); } $this->project_costs->delete((int) $request['id']); $this->project_financial_service->rebuild_for_project((int) $existing['project_id']); if ($reason !== '') { $this->log_adjustment_audit($month, 'project_cost', (int) $request['id'], $existing, null, $reason); } } return new WP_REST_Response(null, 204); }
    public function get_project_finance(WP_REST_Request $request) { $project_id = (int) $request['id']; if (! $this->projects->find($project_id)) { return new WP_Error('erp_omd_project_not_found', __('Project not found.', 'erp-omd'), ['status' => 404]); } return rest_ensure_response($this->project_financial_service->rebuild_for_project($project_id)); }

    public function list_time_entries(WP_REST_Request $request)
    {
        $current_user = wp_get_current_user();
        $filters = [
            'employee_id' => $request->get_param('employee_id'),
            'project_id' => $request->get_param('project_id'),
            'status' => $request->get_param('status'),
            'entry_date' => $request->get_param('entry_date'),
        ];
        $filters = $this->time_entry_service->get_visible_filters_for_user($current_user, array_filter($filters));
        $entries = $this->time_entries->all(array_filter($filters, [$this, 'is_query_filter']));

        return rest_ensure_response($this->time_entry_service->filter_visible_entries($entries, $current_user));
    }

    public function get_time_entry(WP_REST_Request $request)
    {
        $entry = $this->time_entries->find((int) $request['id']);
        if (! $entry) {
            return new WP_Error('erp_omd_time_not_found', __('Time entry not found.', 'erp-omd'), ['status' => 404]);
        }
        if (! $this->time_entry_service->can_view_entry($entry, wp_get_current_user())) {
            return new WP_Error('erp_omd_time_forbidden', __('You cannot view this time entry.', 'erp-omd'), ['status' => 403]);
        }

        return rest_ensure_response($entry);
    }

    public function create_time_entry(WP_REST_Request $request)
    {
        $payload = $this->sanitize_time_entry_payload($request);
        $month = erp_omd_month_from_date($payload['entry_date']);
        if ($this->is_month_locked_for_current_user($month)) { return new WP_Error('erp_omd_period_locked', __('Month is locked for non-admin users.', 'erp-omd'), ['status' => 403]); }
        $reason = $this->sanitize_adjustment_reason($request);
        if ($this->is_month_locked_for_admin($month) && $reason === '') { return new WP_Error('erp_omd_adjustment_reason_required', __('Adjustment reason is required for locked month admin corrections.', 'erp-omd'), ['status' => 422]); }
        if (! current_user_can('administrator') && ! current_user_can('erp_omd_approve_time')) {
            $payload['employee_id'] = $this->current_employee_id();
            $payload['status'] = 'submitted';
        }
        $payload['created_by_user_id'] = get_current_user_id();
        $payload['approved_by_user_id'] = 0;
        $payload['approved_at'] = null;
        $payload = $this->time_entry_service->prepare($payload);
        $errors = $this->time_entry_service->validate($payload);
        if ($errors) { return new WP_Error('erp_omd_time_invalid', implode(' ', $errors), ['status' => 422]); }
        $id = $this->time_entries->create($payload);
        $this->project_financial_service->rebuild_for_project((int) $payload['project_id']);
        $created = $this->time_entries->find($id);
        if ($reason !== '') { $this->log_adjustment_audit($month, 'time_entry', $id, null, $created, $reason); }
        return new WP_REST_Response($created, 201);
    }
    public function update_time_entry(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $existing = $this->time_entries->find($id);
        if (! $existing) { return new WP_Error('erp_omd_time_not_found', __('Time entry not found.', 'erp-omd'), ['status' => 404]); }
        if (! current_user_can('administrator')) { return new WP_Error('erp_omd_time_forbidden', __('Only administrator can edit time entries.', 'erp-omd'), ['status' => 403]); }
        $locked_month = erp_omd_month_from_date($existing['entry_date'] ?? '');
        if ($this->is_month_locked_for_current_user($locked_month)) { return new WP_Error('erp_omd_period_locked', __('Month is locked for non-admin users.', 'erp-omd'), ['status' => 403]); }
        $reason = $this->sanitize_adjustment_reason($request);
        if ($this->is_month_locked_for_admin($locked_month) && $reason === '') { return new WP_Error('erp_omd_adjustment_reason_required', __('Adjustment reason is required for locked month admin corrections.', 'erp-omd'), ['status' => 422]); }
        $payload = $this->sanitize_time_entry_payload($request);
        if (! current_user_can('administrator') && ! current_user_can('erp_omd_approve_time')) {
            $payload['employee_id'] = $this->current_employee_id();
            $payload['status'] = 'submitted';
        }
        $payload['created_by_user_id'] = (int) $existing['created_by_user_id'];
        $payload['approved_by_user_id'] = in_array($payload['status'], ['approved', 'rejected'], true) ? get_current_user_id() : 0;
        $payload['approved_at'] = in_array($payload['status'], ['approved', 'rejected'], true) ? current_time('mysql') : null;
        $payload = $this->time_entry_service->prepare($payload);
        $errors = $this->time_entry_service->validate($payload, $id);
        if ($errors) { return new WP_Error('erp_omd_time_invalid', implode(' ', $errors), ['status' => 422]); }
        $this->time_entries->update($id, $payload);
        $this->project_financial_service->rebuild_for_project((int) $payload['project_id']);
        $updated = $this->time_entries->find($id);
        if ($reason !== '') { $this->log_adjustment_audit($locked_month, 'time_entry', $id, $existing, $updated, $reason); }
        return rest_ensure_response($updated);
    }
    public function delete_time_entry(WP_REST_Request $request)
    {
        if (! current_user_can('administrator')) { return new WP_Error('erp_omd_time_forbidden', __('Only administrator can delete time entries.', 'erp-omd'), ['status' => 403]); }
        $existing = $this->time_entries->find((int) $request['id']);
        $reason = $this->sanitize_adjustment_reason($request);
        if ($existing && $this->is_month_locked_for_current_user(erp_omd_month_from_date($existing['entry_date'] ?? ''))) { return new WP_Error('erp_omd_period_locked', __('Month is locked for non-admin users.', 'erp-omd'), ['status' => 403]); }
        if ($existing && $this->is_month_locked_for_admin(erp_omd_month_from_date($existing['entry_date'] ?? '')) && $reason === '') { return new WP_Error('erp_omd_adjustment_reason_required', __('Adjustment reason is required for locked month admin corrections.', 'erp-omd'), ['status' => 422]); }
        $this->time_entries->delete((int) $request['id']);
        if ($existing) {
            $this->project_financial_service->rebuild_for_project((int) $existing['project_id']);
            if ($reason !== '') { $this->log_adjustment_audit(erp_omd_month_from_date($existing['entry_date'] ?? ''), 'time_entry', (int) $request['id'], $existing, null, $reason); }
        }
        return new WP_REST_Response(null, 204);
    }
    public function change_time_entry_status(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $existing = $this->time_entries->find($id);
        if (! $existing) { return new WP_Error('erp_omd_time_not_found', __('Time entry not found.', 'erp-omd'), ['status' => 404]); }
        $status = sanitize_text_field((string) $request->get_param('status'));
        if (! in_array($status, ['submitted', 'approved', 'rejected'], true)) { return new WP_Error('erp_omd_time_status_invalid', __('Invalid time entry status.', 'erp-omd'), ['status' => 422]); }
        if (! $this->time_entry_service->can_approve_entry($existing, wp_get_current_user())) {
            return new WP_Error('erp_omd_time_forbidden', __('Only the assigned project manager or administrator can approve this time entry.', 'erp-omd'), ['status' => 403]);
        }
        $payload = array_merge($existing, ['status' => $status, 'approved_by_user_id' => get_current_user_id(), 'approved_at' => current_time('mysql')]);
        $this->time_entries->update($id, $payload);
        $this->project_financial_service->rebuild_for_project((int) $existing['project_id']);
        return rest_ensure_response($this->time_entries->find($id));
    }

    public function list_reports(WP_REST_Request $request)
    {
        $filters = $this->reporting_service->sanitize_filters($request->get_params());
        $report_type = sanitize_key((string) ($request->get_param('report_type') ?: $filters['report_type']));

        return rest_ensure_response([
            'filters' => $filters,
            'report_type' => $report_type,
            'rows' => $this->reporting_service->build_report($report_type, $filters),
        ]);
    }

    public function export_report_definition(WP_REST_Request $request)
    {
        $filters = $this->reporting_service->sanitize_filters($request->get_params());
        $report_type = sanitize_key((string) ($request->get_param('report_type') ?: $filters['report_type']));

        return rest_ensure_response($this->reporting_service->export_definition($report_type, $filters));
    }

    public function get_calendar(WP_REST_Request $request)
    {
        $filters = $this->reporting_service->sanitize_filters($request->get_params());
        $filters['tab'] = 'calendar';

        return rest_ensure_response($this->reporting_service->build_calendar($filters));
    }

    public function list_alerts(WP_REST_Request $request)
    {
        $entity_type = sanitize_key((string) $request->get_param('entity_type'));
        $entity_id = (int) $request->get_param('entity_id');
        $alerts = $this->alert_service->all_alerts();

        if ($entity_type !== '') {
            $alerts = array_values(array_filter($alerts, static function ($alert) use ($entity_type, $entity_id) {
                if (($alert['entity_type'] ?? '') !== $entity_type) {
                    return false;
                }

                if ($entity_id > 0 && (int) ($alert['entity_id'] ?? 0) !== $entity_id) {
                    return false;
                }

                return true;
            }));
        }

        return rest_ensure_response($alerts);
    }

    public function list_attachments(WP_REST_Request $request)
    {
        $entity_type = sanitize_key((string) $request->get_param('entity_type'));
        $entity_id = (int) $request->get_param('entity_id');

        if (! in_array($entity_type, ['project', 'estimate'], true) || $entity_id <= 0) {
            return new WP_Error('erp_omd_attachment_invalid_entity', __('Attachment entity_type and entity_id are required.', 'erp-omd'), ['status' => 422]);
        }

        if (! $this->entity_exists($entity_type, $entity_id)) {
            return new WP_Error('erp_omd_attachment_entity_not_found', __('Attachment entity not found.', 'erp-omd'), ['status' => 404]);
        }

        return rest_ensure_response($this->attachments->for_entity($entity_type, $entity_id));
    }

    public function get_attachment(WP_REST_Request $request)
    {
        return $this->find_or_error($this->attachments->find((int) $request['id']), 'erp_omd_attachment_not_found', __('Attachment relation not found.', 'erp-omd'));
    }

    public function create_attachment(WP_REST_Request $request)
    {
        $entity_type = sanitize_key((string) $request->get_param('entity_type'));
        $entity_id = (int) $request->get_param('entity_id');
        $attachment_id = (int) $request->get_param('attachment_id');
        $label = sanitize_text_field((string) $request->get_param('label'));

        if (! in_array($entity_type, ['project', 'estimate'], true) || $entity_id <= 0 || $attachment_id <= 0) {
            return new WP_Error('erp_omd_attachment_invalid_payload', __('Attachment payload is invalid.', 'erp-omd'), ['status' => 422]);
        }
        if (! $this->entity_exists($entity_type, $entity_id)) {
            return new WP_Error('erp_omd_attachment_entity_not_found', __('Attachment entity not found.', 'erp-omd'), ['status' => 404]);
        }
        if (! wp_attachment_is_image($attachment_id) && ! get_post($attachment_id)) {
            return new WP_Error('erp_omd_attachment_media_not_found', __('WordPress media attachment not found.', 'erp-omd'), ['status' => 404]);
        }

        $id = $this->attachments->create([
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'attachment_id' => $attachment_id,
            'label' => $label,
            'created_by_user_id' => get_current_user_id(),
        ]);

        return new WP_REST_Response($this->attachments->find($id), 201);
    }

    public function delete_attachment(WP_REST_Request $request)
    {
        $existing = $this->attachments->find((int) $request['id']);
        if (! $existing) {
            return new WP_REST_Response(null, 204);
        }

        $this->attachments->delete((int) $request['id']);

        return new WP_REST_Response(null, 204);
    }

    public function get_meta()
    {
        return rest_ensure_response([
            'plugin_version' => ERP_OMD_VERSION,
            'db_version' => ERP_OMD_DB_VERSION,
            'billing_types' => [
                ['value' => 'time_material', 'label' => __('Godzinowy', 'erp-omd')],
                ['value' => 'fixed_price', 'label' => __('Ryczałt', 'erp-omd')],
                ['value' => 'retainer', 'label' => __('Abonament', 'erp-omd')],
            ],
            'project_statuses' => ['do_rozpoczecia', 'w_realizacji', 'w_akceptacji', 'do_faktury', 'zakonczony', 'archiwum'],
            'estimate_statuses' => ['wstepny', 'do_akceptacji', 'zaakceptowany'],
            'time_statuses' => ['submitted', 'approved', 'rejected'],
            'report_types' => ['projects', 'clients', 'invoice', 'monthly'],
            'attachment_entity_types' => ['project', 'estimate'],
            'export_variants' => ['client', 'agency', 'variant_a', 'variant_b'],
        ]);
    }

    public function get_system_status()
    {
        $reports_v1_rollout = sanitize_key((string) get_option('erp_omd_reports_v1_rollout', 'all'));
        if (! in_array($reports_v1_rollout, ['off', 'admins', 'all'], true)) {
            $reports_v1_rollout = 'all';
        }
        $current_user = wp_get_current_user();
        $reports_v1_enabled_for_current_user = $reports_v1_rollout === 'all'
            || ($reports_v1_rollout === 'admins' && user_can($current_user, 'administrator'));

        return rest_ensure_response([
            'plugin_version' => ERP_OMD_VERSION,
            'db_version' => ERP_OMD_DB_VERSION,
            'delete_data_on_uninstall' => (bool) get_option('erp_omd_delete_data_on_uninstall', false),
            'alert_margin_threshold' => (float) get_option('erp_omd_alert_margin_threshold', 10),
            'feature_flags' => [
                'reports_v1_rollout' => $reports_v1_rollout,
                'reports_v1_enabled_for_current_user' => $reports_v1_enabled_for_current_user,
            ],
            'counts' => [
                'roles' => count($this->roles->all()),
                'employees' => count($this->employees->all()),
                'clients' => count($this->clients->all()),
                'projects' => count($this->projects->all()),
                'estimates' => count($this->estimates->all()),
                'alerts' => count($this->alert_service->all_alerts()),
            ],
            'current_user' => [
                'id' => (int) get_current_user_id(),
                'can_manage_settings' => $this->can_manage_settings(),
                'can_manage_projects' => $this->can_manage_projects(),
                'can_manage_time' => $this->can_manage_time(),
                'can_access_reports' => $this->can_access_reports(),
            ],
        ]);
    }


    private function find_or_error($record, $code, $message) { return $record ? rest_ensure_response($record) : new WP_Error($code, $message, ['status' => 404]); }
    private function sanitize_role_payload(WP_REST_Request $request) { return ['name' => sanitize_text_field((string) $request->get_param('name')), 'slug' => sanitize_title((string) $request->get_param('slug')), 'description' => sanitize_textarea_field((string) $request->get_param('description')), 'status' => sanitize_text_field((string) $request->get_param('status')) ?: 'active']; }
    private function sanitize_employee_payload(WP_REST_Request $request) { $role_ids = $request->get_param('role_ids'); return ['user_id' => (int) $request->get_param('user_id'), 'default_role_id' => (int) $request->get_param('default_role_id'), 'account_type' => sanitize_text_field((string) $request->get_param('account_type')) ?: 'worker', 'status' => sanitize_text_field((string) $request->get_param('status')) ?: 'active', 'role_ids' => is_array($role_ids) ? array_map('intval', $role_ids) : []]; }
    private function sanitize_salary_payload(WP_REST_Request $request, $employee_id) { return ['employee_id' => $employee_id, 'monthly_salary' => (float) $request->get_param('monthly_salary'), 'monthly_hours' => (float) $request->get_param('monthly_hours'), 'valid_from' => sanitize_text_field((string) $request->get_param('valid_from')), 'valid_to' => sanitize_text_field((string) $request->get_param('valid_to'))]; }
    private function sanitize_client_payload(WP_REST_Request $request) { return ['name' => sanitize_text_field((string) $request->get_param('name')), 'company' => sanitize_text_field((string) $request->get_param('company')), 'nip' => sanitize_text_field((string) $request->get_param('nip')), 'email' => sanitize_email((string) $request->get_param('email')), 'phone' => sanitize_text_field((string) $request->get_param('phone')), 'contact_person_name' => sanitize_text_field((string) $request->get_param('contact_person_name')), 'contact_person_email' => sanitize_email((string) $request->get_param('contact_person_email')), 'contact_person_phone' => sanitize_text_field((string) $request->get_param('contact_person_phone')), 'city' => sanitize_text_field((string) $request->get_param('city')), 'street' => sanitize_text_field((string) $request->get_param('street')), 'apartment_number' => sanitize_text_field((string) $request->get_param('apartment_number')), 'postal_code' => sanitize_text_field((string) $request->get_param('postal_code')), 'country' => sanitize_text_field((string) $request->get_param('country')), 'status' => sanitize_text_field((string) $request->get_param('status')) ?: 'active', 'account_manager_id' => (int) $request->get_param('account_manager_id'), 'alert_margin_threshold' => sanitize_text_field((string) $request->get_param('alert_margin_threshold'))]; }
    private function sanitize_estimate_payload(WP_REST_Request $request, array $existing = null) { return ['client_id' => (int) ($request->get_param('client_id') ?: ($existing['client_id'] ?? 0)), 'name' => sanitize_text_field((string) ($request->get_param('name') ?: ($existing['name'] ?? ''))), 'status' => sanitize_text_field((string) ($request->get_param('status') ?: ($existing['status'] ?? 'wstepny'))) ?: 'wstepny', 'accepted_by_user_id' => (int) ($existing['accepted_by_user_id'] ?? 0), 'accepted_at' => $existing['accepted_at'] ?? null]; }
    private function sanitize_estimate_item_payload(WP_REST_Request $request, $estimate_id, array $existing = null) { return ['estimate_id' => (int) $estimate_id, 'name' => sanitize_text_field((string) ($request->get_param('name') ?: ($existing['name'] ?? ''))), 'qty' => (float) ($request->get_param('qty') !== null ? $request->get_param('qty') : ($existing['qty'] ?? 0)), 'price' => (float) ($request->get_param('price') !== null ? $request->get_param('price') : ($existing['price'] ?? 0)), 'cost_internal' => (float) ($request->get_param('cost_internal') !== null ? $request->get_param('cost_internal') : ($existing['cost_internal'] ?? 0)), 'comment' => sanitize_textarea_field((string) ($request->get_param('comment') !== null ? $request->get_param('comment') : ($existing['comment'] ?? '')) )]; }
    private function sanitize_project_payload(WP_REST_Request $request) { $manager_ids = $request->get_param('manager_ids'); $operational_close_month = sanitize_text_field((string) $request->get_param('operational_close_month')); if (preg_match('/^\\d{4}-\\d{2}$/', $operational_close_month) !== 1) { $operational_close_month = ''; } return ['client_id' => (int) $request->get_param('client_id'), 'name' => sanitize_text_field((string) $request->get_param('name')), 'billing_type' => sanitize_text_field((string) $request->get_param('billing_type')) ?: 'time_material', 'budget' => (float) $request->get_param('budget'), 'retainer_monthly_fee' => (float) $request->get_param('retainer_monthly_fee'), 'status' => sanitize_text_field((string) $request->get_param('status')) ?: 'do_rozpoczecia', 'start_date' => sanitize_text_field((string) $request->get_param('start_date')), 'end_date' => sanitize_text_field((string) $request->get_param('end_date')), 'operational_close_month' => $operational_close_month, 'manager_id' => (int) $request->get_param('manager_id'), 'manager_ids' => is_array($manager_ids) ? array_map('intval', $manager_ids) : [], 'estimate_id' => (int) $request->get_param('estimate_id'), 'brief' => sanitize_textarea_field((string) $request->get_param('brief')), 'alert_margin_threshold' => sanitize_text_field((string) $request->get_param('alert_margin_threshold'))]; }
    private function sanitize_project_cost_payload(WP_REST_Request $request, $project_id) { return ['project_id' => (int) $project_id, 'amount' => (float) $request->get_param('amount'), 'description' => sanitize_textarea_field((string) $request->get_param('description')), 'cost_date' => sanitize_text_field((string) $request->get_param('cost_date')), 'created_by_user_id' => get_current_user_id()]; }
    private function sanitize_time_entry_payload(WP_REST_Request $request) { return ['employee_id' => (int) $request->get_param('employee_id'), 'project_id' => (int) $request->get_param('project_id'), 'role_id' => (int) $request->get_param('role_id'), 'hours' => (float) $request->get_param('hours'), 'entry_date' => sanitize_text_field((string) $request->get_param('entry_date')), 'description' => sanitize_textarea_field((string) $request->get_param('description')), 'status' => sanitize_text_field((string) $request->get_param('status')) ?: 'submitted']; }


    private function current_employee_id()
    {
        $employee = $this->employees->find_by_user_id(get_current_user_id());
        return (int) ($employee['id'] ?? 0);
    }

    private function is_query_filter($value)
    {
        return $value !== '' && $value !== null;
    }

    private function is_month_locked_for_current_user($month)
    {
        if (current_user_can('administrator')) {
            return false;
        }

        return $this->period_service->is_month_locked_for_regular_user($this->period_service->resolve_month_status($month));
    }

    private function is_month_locked_for_admin($month)
    {
        if (! current_user_can('administrator')) {
            return false;
        }

        return $this->period_service->is_month_locked_for_regular_user($this->period_service->resolve_month_status($month));
    }

    private function readiness_signals_for_month($month)
    {
        $entries = (array) $this->time_entries->all();
        $relevant_project_ids = [];
        $submitted_or_rejected_entries = 0;
        $time_entries_finalized = true;
        foreach ($entries as $entry) {
            if (substr((string) ($entry['entry_date'] ?? ''), 0, 7) !== $month) {
                continue;
            }

            $project_id = (int) ($entry['project_id'] ?? 0);
            if ($project_id > 0) {
                $relevant_project_ids[$project_id] = true;
            }

            if (in_array((string) ($entry['status'] ?? ''), ['submitted', 'rejected'], true)) {
                $submitted_or_rejected_entries++;
                $time_entries_finalized = false;
            }
        }

        $project_costs_verified = true;
        $project_client_completeness = true;
        $invalid_cost_rows = 0;
        $incomplete_relevant_projects = 0;
        $relevant_projects = 0;
        $projects = (array) $this->projects->all();
        foreach ($projects as $project) {
            $project_id = (int) ($project['id'] ?? 0);
            if ($project_id <= 0) {
                continue;
            }

            $cost_rows = (array) $this->project_costs->for_project($project_id);
            $project_has_cost_for_month = false;
            foreach ($cost_rows as $cost_row) {
                if (substr((string) ($cost_row['cost_date'] ?? ''), 0, 7) !== $month) {
                    continue;
                }

                $project_has_cost_for_month = true;
                $relevant_project_ids[$project_id] = true;

                if ((float) ($cost_row['amount'] ?? 0) <= 0 || trim((string) ($cost_row['description'] ?? '')) === '') {
                    $invalid_cost_rows++;
                    $project_costs_verified = false;
                    break 2;
                }
            }

            if (! $this->is_project_relevant_for_month($project, $month, isset($relevant_project_ids[$project_id]) || $project_has_cost_for_month)) {
                continue;
            }
            $relevant_projects++;

            $project_status = (string) ($project['status'] ?? '');
            if ($project_status !== 'archiwum') {
                if ((int) ($project['client_id'] ?? 0) <= 0 || trim((string) ($project['name'] ?? '')) === '') {
                    $incomplete_relevant_projects++;
                    $project_client_completeness = false;
                }
            }
        }

        $critical_settlement_locks = true;
        $critical_alerts = 0;
        foreach ((array) $this->alert_service->all_alerts() as $alert) {
            if ((string) ($alert['severity'] ?? '') !== 'error') {
                continue;
            }

            if (substr((string) ($alert['month'] ?? $month), 0, 7) === $month) {
                $critical_alerts++;
                $critical_settlement_locks = false;
            }
        }

        return [
            'time_entries_finalized' => $time_entries_finalized,
            'project_costs_verified' => $project_costs_verified,
            'project_client_completeness' => $project_client_completeness,
            'critical_settlement_locks' => $critical_settlement_locks,
            '_meta' => [
                'submitted_or_rejected_entries' => $submitted_or_rejected_entries,
                'invalid_cost_rows' => $invalid_cost_rows,
                'incomplete_relevant_projects' => $incomplete_relevant_projects,
                'critical_alerts' => $critical_alerts,
                'relevant_projects' => $relevant_projects,
            ],
        ];
    }

    private function is_project_relevant_for_month(array $project, $month, $has_activity_for_month)
    {
        if ($has_activity_for_month) {
            return true;
        }

        if ((string) ($project['operational_close_month'] ?? '') === $month) {
            return true;
        }

        return $this->project_overlaps_month($project, $month);
    }

    private function project_overlaps_month(array $project, $month)
    {
        $start_of_month = $month . '-01';
        $end_of_month = $month . '-31';

        $project_start = (string) ($project['start_date'] ?? '');
        $project_end = (string) ($project['end_date'] ?? '');

        if ($project_start === '' && $project_end === '') {
            return false;
        }

        if ($project_start !== '' && $project_start > $end_of_month) {
            return false;
        }

        if ($project_end !== '' && $project_end < $start_of_month) {
            return false;
        }

        return true;
    }

    private function sanitize_adjustment_reason(WP_REST_Request $request)
    {
        return trim(sanitize_textarea_field((string) $request->get_param('reason')));
    }

    private function resolve_adjustment_type($month)
    {
        $period = $this->period_service->ensure_month_exists($month);
        $status = (string) ($period['status'] ?? ERP_OMD_Period_Service::STATUS_LIVE);
        if ($status !== ERP_OMD_Period_Service::STATUS_ZAMKNIETY) {
            return 'STANDARD';
        }

        $window_until = (string) ($period['correction_window_until'] ?? '');
        if ($window_until === '') {
            return 'EMERGENCY_ADJUSTMENT';
        }

        try {
            $now = new DateTimeImmutable(current_time('mysql'));
            $deadline = new DateTimeImmutable($window_until);
            return $this->period_service->is_emergency_adjustment_required($now, $deadline) ? 'EMERGENCY_ADJUSTMENT' : 'STANDARD';
        } catch (Exception $exception) {
            return 'EMERGENCY_ADJUSTMENT';
        }
    }

    private function log_adjustment_audit($month, $entity_type, $entity_id, $old_value, $new_value, $reason)
    {
        $this->adjustment_audit->create([
            'month' => $month,
            'entity_type' => $entity_type,
            'entity_id' => (int) $entity_id,
            'field_name' => 'payload',
            'old_value' => $old_value !== null ? wp_json_encode($old_value) : null,
            'new_value' => $new_value !== null ? wp_json_encode($new_value) : null,
            'reason' => $reason,
            'adjustment_type' => $this->resolve_adjustment_type($month),
            'changed_by' => (int) get_current_user_id(),
            'changed_at' => current_time('mysql'),
        ]);
    }

    private function sync_wp_role($user_id, $account_type)
    {
        $user = get_user_by('id', $user_id);
        if (! $user instanceof WP_User) { return; }
        if ($account_type === 'admin') {
            $user->set_role('administrator');
        } elseif ($account_type === 'manager') {
            $user->set_role('erp_omd_manager');
        } elseif ($account_type === 'worker') {
            $user->set_role('erp_omd_worker');
        }
    }

    private function entity_exists($entity_type, $entity_id)
    {
        if ($entity_type === 'project') {
            return (bool) $this->projects->find($entity_id);
        }

        if ($entity_type === 'estimate') {
            return (bool) $this->estimates->find($entity_id);
        }

        return false;
    }
}

if (! function_exists('erp_omd_month_from_date')) {
    function erp_omd_month_from_date($date_value)
    {
        $date = sanitize_text_field((string) $date_value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return gmdate('Y-m');
        }

        return substr($date, 0, 7);
    }
}
