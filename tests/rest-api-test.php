<?php

declare(strict_types=1);

if (! defined('ERP_OMD_VERSION')) {
    define('ERP_OMD_VERSION', '0.9.0');
}
if (! defined('ERP_OMD_DB_VERSION')) {
    define('ERP_OMD_DB_VERSION', '6.2.0');
}

if (! function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}
if (! function_exists('sanitize_key')) {
    function sanitize_key($key)
    {
        return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key));
    }
}
if (! function_exists('sanitize_text_field')) {
    function sanitize_text_field($text)
    {
        return trim((string) $text);
    }
}
if (! function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($text)
    {
        return trim((string) $text);
    }
}
if (! function_exists('sanitize_title')) {
    function sanitize_title($text)
    {
        return strtolower(trim((string) $text));
    }
}
if (! function_exists('sanitize_email')) {
    function sanitize_email($text)
    {
        return trim((string) $text);
    }
}
if (! function_exists('rest_ensure_response')) {
    function rest_ensure_response($data)
    {
        return $data;
    }
}
if (! function_exists('add_action')) {
    function add_action($hook, $callback)
    {
        return true;
    }
}
if (! function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args)
    {
        $GLOBALS['erp_omd_registered_rest_routes'][] = [
            'namespace' => $namespace,
            'route' => $route,
            'args' => $args,
        ];

        return true;
    }
}
if (! function_exists('current_user_can')) {
    function current_user_can($capability)
    {
        return in_array($capability, ['administrator', 'erp_omd_manage_settings', 'erp_omd_manage_projects', 'erp_omd_manage_time', 'erp_omd_access'], true);
    }
}
if (! function_exists('get_current_user_id')) {
    function get_current_user_id()
    {
        return 99;
    }
}
if (! function_exists('get_option')) {
    function get_option($key, $default = false)
    {
        $options = [
            'erp_omd_delete_data_on_uninstall' => false,
            'erp_omd_alert_margin_threshold' => 10,
        ];

        return $options[$key] ?? $default;
    }
}
if (! function_exists('current_time')) {
    function current_time($format)
    {
        return $format === 'mysql' ? '2026-03-20 12:00:00' : '2026-03-20';
    }
}
if (! function_exists('wp_attachment_is_image')) {
    function wp_attachment_is_image($attachment_id)
    {
        return false;
    }
}
if (! function_exists('get_post')) {
    function get_post($post_id)
    {
        if ((int) $post_id <= 0) {
            return null;
        }

        return (object) ['ID' => (int) $post_id, 'post_name' => 'attachment-' . (int) $post_id];
    }
}
if (! function_exists('get_user_by')) {
    function get_user_by($field, $value)
    {
        return new WP_User((int) $value);
    }
}
if (! function_exists('wp_get_current_user')) {
    function wp_get_current_user()
    {
        return new WP_User(99);
    }
}

if (! class_exists('WP_User')) {
    class WP_User
    {
        public $ID;
        public $role = '';

        public function __construct($id)
        {
            $this->ID = (int) $id;
        }

        public function set_role($role)
        {
            $this->role = (string) $role;
        }
    }
}

if (! class_exists('WP_REST_Server')) {
    class WP_REST_Server
    {
        public const READABLE = 'GET';
        public const CREATABLE = 'POST';
        public const EDITABLE = 'PUT';
        public const DELETABLE = 'DELETE';
    }
}

if (! class_exists('WP_REST_Request')) {
    class WP_REST_Request implements ArrayAccess
    {
        private $params;

        public function __construct(array $params = [])
        {
            $this->params = $params;
        }

        public function get_param($key)
        {
            return $this->params[$key] ?? null;
        }

        public function get_params()
        {
            return $this->params;
        }

        public function offsetExists($offset): bool
        {
            return array_key_exists((string) $offset, $this->params);
        }

        public function offsetGet($offset)
        {
            return $this->params[(string) $offset] ?? null;
        }

        public function offsetSet($offset, $value): void
        {
            $this->params[(string) $offset] = $value;
        }

        public function offsetUnset($offset): void
        {
            unset($this->params[(string) $offset]);
        }
    }
}

if (! class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        private $data;
        private $status;

        public function __construct($data = null, $status = 200)
        {
            $this->data = $data;
            $this->status = (int) $status;
        }

        public function get_data()
        {
            return $this->data;
        }

        public function get_status()
        {
            return $this->status;
        }
    }
}

if (! class_exists('WP_Error')) {
    class WP_Error
    {
        private $code;
        private $message;
        private $data;

        public function __construct($code, $message, $data = [])
        {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }

        public function get_error_code()
        {
            return $this->code;
        }

        public function get_error_message()
        {
            return $this->message;
        }

        public function get_error_data()
        {
            return $this->data;
        }
    }
}

if (! class_exists('ERP_OMD_Role_Repository')) {
    class ERP_OMD_Role_Repository { public function all() { return [['id' => 1]]; } public function find($id) { return ['id' => $id]; } public function slug_exists($slug, $id = null) { return false; } }
}
if (! class_exists('ERP_OMD_Employee_Repository')) {
    class ERP_OMD_Employee_Repository { public function all() { return [['id' => 1]]; } public function find($id) { return ['id' => $id, 'user_id' => 1]; } public function find_by_user_id($id) { return ['id' => 1, 'user_id' => $id]; } }
}
if (! class_exists('ERP_OMD_Salary_History_Repository')) {
    class ERP_OMD_Salary_History_Repository { public function for_employee($id) { return []; } public function find($id) { return ['id' => $id, 'employee_id' => 1]; } }
}
if (! class_exists('ERP_OMD_Employee_Service')) {
    class ERP_OMD_Employee_Service { public function validate_employee($payload, $id = null) { return []; } public function prepare_salary_payload($payload) { return $payload; } public function validate_salary($payload, $id = null) { return []; } }
}
if (! class_exists('ERP_OMD_Monthly_Hours_Service')) {
    class ERP_OMD_Monthly_Hours_Service { public function suggested_hours($month) { return 168; } }
}
if (! class_exists('ERP_OMD_Client_Repository')) {
    class ERP_OMD_Client_Repository { public function all() { return [['id' => 1]]; } public function find($id) { return ['id' => $id]; } }
}
if (! class_exists('ERP_OMD_Client_Rate_Repository')) {
    class ERP_OMD_Client_Rate_Repository { public function for_client($id) { return []; } public function find($id) { return ['id' => $id, 'client_id' => 1, 'role_id' => 1, 'rate' => 100]; } }
}
if (! class_exists('ERP_OMD_Project_Repository')) {
    class ERP_OMD_Project_Repository {
        public function all()
        {
            return [
                ['id' => 10, 'client_id' => 1, 'name' => 'Projekt A', 'status' => 'w_realizacji', 'start_date' => '2026-03-01', 'end_date' => '2026-03-31'],
                ['id' => 11, 'client_id' => 0, 'name' => '', 'status' => 'do_rozpoczecia', 'start_date' => '2026-05-01', 'end_date' => '2026-05-31'],
                ['id' => 12, 'client_id' => 0, 'name' => '', 'status' => 'do_rozpoczecia', 'operational_close_month' => '2026-03', 'start_date' => '', 'end_date' => ''],
            ];
        }
        public function find($id) { return ['id' => $id, 'client_id' => 1]; }
    }
}
if (! class_exists('ERP_OMD_Estimate_Repository')) {
    class ERP_OMD_Estimate_Repository { public function all() { return [['id' => 20]]; } public function find($id) { return ['id' => $id, 'client_id' => 1, 'status' => 'wstepny']; } }
}
if (! class_exists('ERP_OMD_Estimate_Item_Repository')) {
    class ERP_OMD_Estimate_Item_Repository { public function for_estimate($id) { return []; } public function find($id) { return ['id' => $id, 'estimate_id' => 20]; } }
}
if (! class_exists('ERP_OMD_Project_Note_Repository')) {
    class ERP_OMD_Project_Note_Repository { public function for_project($id) { return []; } }
}
if (! class_exists('ERP_OMD_Client_Project_Service')) {
    class ERP_OMD_Client_Project_Service { public function prepare_client($payload) { return $payload; } public function validate_client($payload, $id = null) { return []; } public function validate_client_rate($client_id, $role_id, $rate, $valid_from = '', $valid_to = '') { return []; } public function prepare_project($payload, $existing = null) { return $payload; } public function validate_project($payload, $existing = null) { return []; } }
}
if (! class_exists('ERP_OMD_Estimate_Service')) {
    class ERP_OMD_Estimate_Service { public function validate_estimate($payload, $existing = null) { return []; } public function calculate_totals($items) { return ['net' => 0, 'tax' => 0, 'gross' => 0, 'internal_cost' => 0]; } public function validate_item($payload, $estimate = null, $existing = null) { return []; } public function accept($id) { return ['accepted_project_id' => 10]; } }
}
if (! class_exists('ERP_OMD_Project_Rate_Repository')) {
    class ERP_OMD_Project_Rate_Repository { public function for_project($id) { return []; } public function find($id) { return ['id' => $id, 'project_id' => 10, 'role_id' => 1, 'rate' => 100]; } }
}
if (! class_exists('ERP_OMD_Project_Cost_Repository')) {
    class ERP_OMD_Project_Cost_Repository {
        public function for_project($id)
        {
            if ((int) $id !== 10) {
                return [];
            }

            return [
                ['project_id' => (int) $id, 'cost_date' => '2026-03-10', 'amount' => 100.0, 'description' => 'Hosting'],
            ];
        }
        public function find($id) { return ['id' => $id, 'project_id' => 10]; }
    }
}
if (! class_exists('ERP_OMD_Project_Financial_Repository')) {
    class ERP_OMD_Project_Financial_Repository {}
}
if (! class_exists('ERP_OMD_Time_Entry_Repository')) {
    class ERP_OMD_Time_Entry_Repository {
        public function all(array $filters = [])
        {
            return [
                ['id' => 1, 'entry_date' => '2026-03-05', 'status' => 'submitted', 'project_id' => 10],
            ];
        }
        public function find($id) { return ['id' => $id, 'project_id' => 10, 'created_by_user_id' => 1, 'status' => 'submitted']; }
    }
}
if (! class_exists('ERP_OMD_Attachment_Repository')) {
    class ERP_OMD_Attachment_Repository
    {
        private $rows = [
            ['id' => 1, 'entity_type' => 'project', 'entity_id' => 10, 'attachment_id' => 555, 'label' => 'Makieta', 'created_at' => '2026-03-20 12:00:00'],
        ];

        public function for_entity($entity_type, $entity_id)
        {
            return array_values(array_filter($this->rows, static function ($row) use ($entity_type, $entity_id) {
                return $row['entity_type'] === $entity_type && (int) $row['entity_id'] === (int) $entity_id;
            }));
        }

        public function find($id)
        {
            foreach ($this->rows as $row) {
                if ((int) $row['id'] === (int) $id) {
                    return $row;
                }
            }

            return null;
        }

        public function create(array $data)
        {
            $id = count($this->rows) + 1;
            $data['id'] = $id;
            $data['created_at'] = '2026-03-20 12:00:00';
            $this->rows[] = $data;

            return $id;
        }

        public function delete($id)
        {
            $this->rows = array_values(array_filter($this->rows, static function ($row) use ($id) {
                return (int) $row['id'] !== (int) $id;
            }));

            return true;
        }
    }
}
if (! class_exists('ERP_OMD_Time_Entry_Service')) {
    class ERP_OMD_Time_Entry_Service { public function get_visible_filters_for_user($user, array $filters) { return $filters; } public function filter_visible_entries($entries, $user) { return $entries; } public function can_view_entry($entry, $user) { return true; } public function prepare($payload) { return $payload; } public function validate($payload, $id = null) { return []; } public function can_approve_entry($entry, $user) { return true; } }
}
if (! class_exists('ERP_OMD_Project_Financial_Service')) {
    class ERP_OMD_Project_Financial_Service { public function rebuild_for_project($id) { return ['project_id' => $id]; } public function validate_project_cost($payload) { return []; } }
}
if (! class_exists('ERP_OMD_Reporting_Service')) {
    class ERP_OMD_Reporting_Service {
        public function sanitize_filters($filters) { return array_merge(['report_type' => 'projects'], $filters); }
        public function build_report($type, $filters) { return [['month' => '2026-01', 'margin' => 10], ['month' => '2026-02', 'margin' => 12], ['month' => '2026-03', 'margin' => 11]]; }
        public function export_definition($type, $filters) { return ['filename' => 'export.csv', 'headers' => [], 'rows' => []]; }
        public function build_calendar($filters) { return []; }
        public function build_project_report($filters) { return [['id' => 10, 'margin' => 20], ['id' => 11, 'margin' => 5]]; }
        public function build_client_report($filters) { return [['id' => 1, 'margin' => 15], ['id' => 2, 'margin' => 8]]; }
        public function build_invoice_report($filters) { return [['id' => 2001, 'project_id' => 10], ['id' => 2002, 'client_id' => 2]]; }
    }
}
if (! class_exists('ERP_OMD_Alert_Service')) {
    class ERP_OMD_Alert_Service { public function all_alerts() { return [['severity' => 'warning', 'code' => 'project_low_margin', 'entity_type' => 'project', 'entity_id' => 10, 'message' => 'Low margin']]; } }
}
if (! class_exists('ERP_OMD_Period_Service')) {
    class ERP_OMD_Period_Service {
        public const STATUS_LIVE = 'LIVE';
        public const STATUS_DO_ROZLICZENIA = 'DO_ROZLICZENIA';
        public const STATUS_ZAMKNIETY = 'ZAMKNIETY';

        private $rows = [];

        public function list_periods() { return array_values($this->rows); }
        public function ensure_month_exists($month, $updated_by = 0)
        {
            if (! isset($this->rows[$month])) {
                $this->rows[$month] = ['month' => $month, 'status' => 'LIVE', 'closed_at' => null, 'correction_window_until' => null, 'updated_by' => (int) $updated_by];
            }

            return $this->rows[$month];
        }
        public function build_readiness_checklist(array $signals)
        {
            $checks = [];
            foreach (['time_entries_finalized', 'project_costs_verified', 'project_client_completeness', 'critical_settlement_locks'] as $key) {
                $checks[$key] = ! empty($signals[$key]);
            }
            $blockers = array_keys(array_filter($checks, static function ($passed) { return ! $passed; }));

            return ['ready' => $blockers === [], 'checks' => $checks, 'blockers' => $blockers];
        }
        public function transition_month($month, $status, array $readiness)
        {
            $existing = $this->ensure_month_exists($month, get_current_user_id());
            $checklist = $this->build_readiness_checklist($readiness);
            if ($existing['status'] === 'LIVE' && $status === 'DO_ROZLICZENIA' && ! $checklist['ready']) {
                throw new InvalidArgumentException('LIVE -> DO_ROZLICZENIA requires readiness checklist == ready.');
            }
            $this->rows[$month]['status'] = $status;

            return ['period' => $this->rows[$month], 'checklist' => $checklist];
        }
        public function can_transition($from_status, $to_status)
        {
            if ($from_status === self::STATUS_LIVE && $to_status === self::STATUS_DO_ROZLICZENIA) {
                return true;
            }

            if ($from_status === self::STATUS_DO_ROZLICZENIA && $to_status === self::STATUS_ZAMKNIETY) {
                return true;
            }

            return false;
        }
        public function is_month_locked_for_regular_user($status) { return in_array((string) $status, ['DO_ROZLICZENIA', 'ZAMKNIETY'], true); }
        public function resolve_month_status($month) { return (string) ($this->ensure_month_exists($month)['status'] ?? 'LIVE'); }
        public function is_emergency_adjustment_required(DateTimeImmutable $now, DateTimeImmutable $deadline) { return $now > $deadline; }
    }
}
if (! class_exists('ERP_OMD_Adjustment_Audit_Repository')) {
    class ERP_OMD_Adjustment_Audit_Repository {
        public function create($payload) { return 1; }
        public function all($filters = []) {
            return [
                [
                    'id' => 501,
                    'entity_type' => 'project_cost',
                    'entity_id' => 10,
                    'field_name' => 'amount',
                    'old_value' => '{"amount":100}',
                    'new_value' => '{"amount":125}',
                    'adjustment_type' => 'STANDARD',
                    'reason' => 'Korekta faktury',
                    'changed_at' => '2026-03-20 12:00:00',
                ],
                [
                    'id' => 502,
                    'entity_type' => 'time_entry',
                    'entity_id' => 22,
                    'field_name' => 'hours',
                    'old_value' => '{"hours":2}',
                    'new_value' => '{"hours":3}',
                    'adjustment_type' => 'STANDARD',
                    'reason' => 'Korekta godzin',
                    'changed_at' => '2026-03-20 13:00:00',
                ],
            ];
        }
    }
}

require_once __DIR__ . '/../erp-omd/includes/class-rest-api.php';

final class RestApiTestRunner
{
    private $assertions = 0;

    public function run(): void
    {
        $GLOBALS['erp_omd_registered_rest_routes'] = [];
        $restApiSource = file_get_contents(__DIR__ . '/../erp-omd/includes/class-rest-api.php');
        $duplicateLegacyMethodCount = preg_match_all('/function\s+register_period_routes\s*\(/', (string) $restApiSource);
        $this->assertSame(0, (int) $duplicateLegacyMethodCount, 'REST API source should not contain legacy duplicated register_period_routes declarations.');
        $duplicateManagementMethodCount = preg_match_all('/function\s+register_period_management_routes\s*\(/', (string) $restApiSource);
        $this->assertSame(0, (int) $duplicateManagementMethodCount, 'REST API source should not contain dedicated period management registration method declarations.');
        $this->assertSame(0, (int) preg_match_all('/function\s+get_period_status\s*\(/', (string) $restApiSource), 'REST API source should not redeclare legacy get_period_status method.');
        $this->assertSame(0, (int) preg_match_all('/function\s+list_periods\s*\(/', (string) $restApiSource), 'REST API source should not redeclare legacy list_periods method.');
        $this->assertSame(0, (int) preg_match_all('/function\s+transition_period_status\s*\(/', (string) $restApiSource), 'REST API source should not redeclare legacy transition_period_status method.');
        $this->assertSame(0, (int) preg_match_all('/function\s+list_adjustments\s*\(/', (string) $restApiSource), 'REST API source should not redeclare legacy list_adjustments method.');
        $this->assertSame(0, (int) preg_match_all('/function\s+create_adjustment\s*\(/', (string) $restApiSource), 'REST API source should not redeclare legacy create_adjustment method.');
        $this->assertSame(0, (int) preg_match_all('/function\s+month_from_date\s*\(/', (string) $restApiSource), 'REST API source should not declare month_from_date as a class method.');
        $this->assertSame(0, (int) preg_match_all('/function\s+period_status_endpoint\s*\(/', (string) $restApiSource), 'REST API source should not declare period_status_endpoint as a class method.');
        $this->assertSame(0, (int) preg_match_all('/function\s+periods_index_endpoint_v1\s*\(/', (string) $restApiSource), 'REST API source should not declare periods_index_endpoint_v1 as a class method.');
        $this->assertSame(0, (int) preg_match_all('/function\s+period_transition_endpoint_v1\s*\(/', (string) $restApiSource), 'REST API source should not declare period_transition_endpoint_v1 as a class method.');
        $this->assertSame(0, (int) preg_match_all('/function\s+adjustments_index_endpoint_v1\s*\(/', (string) $restApiSource), 'REST API source should not declare adjustments_index_endpoint_v1 as a class method.');
        $this->assertSame(0, (int) preg_match_all('/function\s+adjustments_create_endpoint_v1\s*\(/', (string) $restApiSource), 'REST API source should not declare adjustments_create_endpoint_v1 as a class method.');

        $periodRouteCount = preg_match_all("/register_rest_route\('erp-omd\/v1', '\/periods/", (string) $restApiSource);
        $this->assertSame(3, (int) $periodRouteCount, 'REST API source should register all three period routes directly in register_routes.');
        $adjustmentsRouteCount = preg_match_all("/register_rest_route\('erp-omd\/v1', '\/adjustments'/", (string) $restApiSource);
        $this->assertSame(1, (int) $adjustmentsRouteCount, 'REST API source should register adjustments route directly in register_routes.');
        $dashboardRouteCount = preg_match_all("/register_rest_route\('erp-omd\/v1', '\/dashboard-v1'/", (string) $restApiSource);
        $this->assertSame(1, (int) $dashboardRouteCount, 'REST API source should register dashboard-v1 route directly in register_routes.');

        preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', (string) $restApiSource, $methodMatches);
        $methodNames = $methodMatches[1] ?? [];
        $duplicateMethodNames = array_keys(array_filter(array_count_values($methodNames), static function ($count) {
            return (int) $count > 1;
        }));
        $this->assertSame([], $duplicateMethodNames, 'REST API source should not contain duplicate method declarations.');

        $api = new ERP_OMD_REST_API(
            new ERP_OMD_Role_Repository(),
            new ERP_OMD_Employee_Repository(),
            new ERP_OMD_Salary_History_Repository(),
            new ERP_OMD_Employee_Service(),
            new ERP_OMD_Monthly_Hours_Service(),
            new ERP_OMD_Client_Repository(),
            new ERP_OMD_Client_Rate_Repository(),
            new ERP_OMD_Project_Repository(),
            new ERP_OMD_Estimate_Repository(),
            new ERP_OMD_Estimate_Item_Repository(),
            new ERP_OMD_Project_Note_Repository(),
            new ERP_OMD_Client_Project_Service(),
            new ERP_OMD_Estimate_Service(),
            new ERP_OMD_Project_Rate_Repository(),
            new ERP_OMD_Project_Cost_Repository(),
            new ERP_OMD_Project_Financial_Repository(),
            new ERP_OMD_Time_Entry_Repository(),
            new ERP_OMD_Attachment_Repository(),
            new ERP_OMD_Time_Entry_Service(),
            new ERP_OMD_Project_Financial_Service(),
            new ERP_OMD_Reporting_Service(),
            new ERP_OMD_Alert_Service(),
            new ERP_OMD_Period_Service(),
            new ERP_OMD_Adjustment_Audit_Repository()
        );

        $meta = $api->get_meta();
        $this->assertSame('0.9.0', $meta['plugin_version'], 'Meta endpoint should expose plugin version.');
        $this->assertSame(['project', 'estimate'], $meta['attachment_entity_types'], 'Meta endpoint should expose supported attachment entity types.');
        $this->assertSame(['client', 'agency', 'variant_a', 'variant_b'], $meta['export_variants'], 'Meta endpoint should expose estimate export variants and aliases.');

        $alerts = $api->list_alerts(new WP_REST_Request(['entity_type' => 'project', 'entity_id' => 10]));
        $this->assertSame(1, count($alerts), 'Alert endpoint should filter alerts by entity.');
        $this->assertSame('project_low_margin', $alerts[0]['code'], 'Filtered alert should keep original code.');

        $attachments = $api->list_attachments(new WP_REST_Request(['entity_type' => 'project', 'entity_id' => 10]));
        $this->assertSame(1, count($attachments), 'Attachment endpoint should return entity attachments.');

        $created = $api->create_attachment(new WP_REST_Request(['entity_type' => 'estimate', 'entity_id' => 20, 'attachment_id' => 556, 'label' => 'PDF']));
        $this->assertSame(201, $created->get_status(), 'Attachment create endpoint should return 201 status.');
        $this->assertSame('estimate', $created->get_data()['entity_type'], 'Attachment create endpoint should persist entity type.');

        $system = $api->get_system_status();
        $this->assertSame(1, $system['counts']['alerts'], 'System status should include alert count.');
        $this->assertSame(true, $system['current_user']['can_manage_settings'], 'System status should expose current user capabilities.');

        $api->register_routes();
        $periodStatusCallback = $this->findRouteCallback('/periods/(?P<month>\\d{4}-\\d{2})', WP_REST_Server::READABLE);
        $periodStatusPayload = $periodStatusCallback(new WP_REST_Request(['month' => '2026-03']));
        $this->assertSame(false, $periodStatusPayload['checklist']['ready'], 'Period status endpoint should expose non-ready checklist for month with submitted entries.');
        $this->assertSame(true, in_array('time_entries_finalized', $periodStatusPayload['checklist']['blockers'], true), 'Period status endpoint should include time_entries_finalized as a blocker.');
        $this->assertSame(false, $periodStatusPayload['checklist']['checks']['project_client_completeness'], 'Checklist should validate client completeness for projects explicitly closed operationally in selected month.');
        $this->assertSame(true, in_array('project_client_completeness', $periodStatusPayload['checklist']['blockers'], true), 'Checklist should flag project_client_completeness when operationally-closed project data is incomplete.');
        $this->assertSame(false, $periodStatusPayload['readiness_signals']['time_entries_finalized'], 'Period status endpoint should expose normalized readiness_signals.');
        $this->assertSame(1, $periodStatusPayload['readiness_meta']['submitted_or_rejected_entries'], 'Readiness meta should count blocking submitted/rejected entries in selected month.');
        $this->assertSame(2, $periodStatusPayload['readiness_meta']['relevant_projects'], 'Readiness meta should count projects relevant for selected month closure.');

        $transitionCallback = $this->findRouteCallback('/periods/(?P<month>\\d{4}-\\d{2})/transition', WP_REST_Server::CREATABLE);
        $transitionBlocked = $transitionCallback(new WP_REST_Request(['month' => '2026-03', 'to_status' => 'DO_ROZLICZENIA']));
        $this->assertSame('erp_omd_period_transition_blocked', $transitionBlocked->get_error_code(), 'Transition endpoint should block LIVE -> DO_ROZLICZENIA when checklist is not ready.');

        $transitionInvalid = $transitionCallback(new WP_REST_Request(['month' => '2026-03', 'to_status' => 'LIVE']));
        $this->assertSame('erp_omd_period_transition_invalid', $transitionInvalid->get_error_code(), 'Transition endpoint should reject unsupported target statuses.');

        $dashboardCallback = $this->findRouteCallback('/dashboard-v1', WP_REST_Server::READABLE);
        $dashboardPayload = $dashboardCallback(new WP_REST_Request(['month' => '2026-03', 'mode' => 'ZAMKNIETY', 'profitability_scope' => 'project', 'adjustments_limit' => 1, 'queue_limit' => 1, 'profitability_limit' => 1]));
        $this->assertSame('v1', $dashboardPayload['api_version'], 'Dashboard endpoint should expose explicit contract version.');
        $this->assertSame('2026-03-20 12:00:00', $dashboardPayload['generated_at'], 'Dashboard endpoint should expose deterministic generation timestamp.');
        $this->assertSame(1, $dashboardPayload['applied_limits']['adjustments_items'], 'Dashboard endpoint should expose applied adjustments item limit.');
        $this->assertSame(1, $dashboardPayload['applied_limits']['queue_items'], 'Dashboard endpoint should expose applied queue item limit.');
        $this->assertSame(1, $dashboardPayload['applied_limits']['profitability_items'], 'Dashboard endpoint should expose applied profitability item limit.');
        $this->assertSame('2026-03', $dashboardPayload['month'], 'Dashboard endpoint should preserve explicit month filter.');
        $this->assertSame('ZAMKNIETY', $dashboardPayload['mode'], 'Dashboard endpoint should expose applied reporting mode.');
        $this->assertSame(false, $dashboardPayload['readiness_checklist']['ready'], 'Dashboard endpoint should expose readiness checklist snapshot for selected month.');
        $this->assertSame(1, $dashboardPayload['readiness_meta']['submitted_or_rejected_entries'], 'Dashboard readiness meta should expose submitted/rejected entry counter.');
        $this->assertSame('DO_ROZLICZENIA', $dashboardPayload['status_actions'][0]['to_status'], 'Dashboard endpoint should expose next status action for current month.');
        $this->assertSame(false, $dashboardPayload['status_actions'][0]['enabled'], 'Dashboard status action should be disabled when checklist is not ready.');
        $this->assertSame(true, isset($dashboardPayload['metric_definitions']['trend_3m']), 'Dashboard endpoint should expose metric definitions for frontend tooltip rendering.');
        $this->assertSame(true, isset($dashboardPayload['metric_definitions']['readiness_checklist.ready']), 'Dashboard endpoint should expose readiness definition tooltip key.');
        $this->assertSame(true, isset($dashboardPayload['metric_definitions']['applied_limits']), 'Dashboard endpoint should define applied limits semantics for clients.');
        $this->assertSame(true, isset($dashboardPayload['drilldown_links']['settlement_queue']), 'Dashboard endpoint should expose drilldown links for queue and adjustments.');
        $this->assertSame('/wp-admin/admin.php?page=erp-omd-reports&report_type=invoice&month=2026-03', $dashboardPayload['drilldown_links']['settlement_queue'], 'Dashboard queue drilldown should target invoice report for selected month.');
        $this->assertSame(true, isset($dashboardPayload['profitability_by_scope']['project']['top']), 'Dashboard endpoint should expose project ranking buckets for scope switch without reload.');
        $this->assertSame(true, isset($dashboardPayload['profitability_by_scope']['project']['top'][0]['drilldown_link']), 'Dashboard profitability rows should expose drilldown links for detailed reports.');
        $this->assertSame('/wp-admin/admin.php?page=erp-omd-reports&report_type=projects&month=2026-03&project_id=10', $dashboardPayload['profitability_by_scope']['project']['top'][0]['drilldown_link'], 'Project profitability drilldown link should include month and project_id.');
        $this->assertSame(1, count($dashboardPayload['profitability_by_scope']['client']['bottom']), 'Dashboard endpoint should honor profitability_limit for scope rankings.');
        $this->assertSame('/wp-admin/admin.php?page=erp-omd-reports&report_type=invoice&month=2026-03&project_id=10', $dashboardPayload['settlement_queue']['items'][0]['drilldown_link'], 'Queue rows should include month-aware drilldown link to invoice report.');
        $this->assertSame(26.0, $dashboardPayload['adjustments']['impact'], 'Dashboard adjustment impact should sum delta between new and old values.');
        $this->assertSame('/wp-admin/admin.php?page=erp-omd-reports&report_type=time&month=2026-03&adjustments=1&entity_type=project_cost&entity_id=10', $dashboardPayload['adjustments']['items'][0]['drilldown_link'], 'Dashboard adjustment rows should expose drilldown link with entity context.');
        $this->assertSame(1, count($dashboardPayload['settlement_queue']['items']), 'Dashboard should honor queue_limit for serialized queue rows.');
        $this->assertSame(1, count($dashboardPayload['adjustments']['items']), 'Dashboard should honor adjustments_limit for serialized adjustment rows.');
        $this->assertSame(2, $dashboardPayload['settlement_queue']['count'], 'Dashboard endpoint should expose invoice queue count.');

        echo "Assertions: {$this->assertions}\n";
        echo "REST API tests passed.\n";
    }

    private function findRouteCallback(string $route, string $method)
    {
        foreach ((array) ($GLOBALS['erp_omd_registered_rest_routes'] ?? []) as $registration) {
            if (($registration['namespace'] ?? '') !== 'erp-omd/v1' || ($registration['route'] ?? '') !== $route) {
                continue;
            }

            foreach ((array) ($registration['args'] ?? []) as $endpoint) {
                if (($endpoint['methods'] ?? '') === $method) {
                    return $endpoint['callback'];
                }
            }
        }

        throw new RuntimeException('Unable to find registered callback for route ' . $route . ' [' . $method . ']');
    }

    private function assertSame($expected, $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new RuntimeException($message . ' Expected: ' . var_export($expected, true) . ' Actual: ' . var_export($actual, true));
        }
    }
}

(new RestApiTestRunner())->run();
