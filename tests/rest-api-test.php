<?php

declare(strict_types=1);

if (! defined('ERP_OMD_VERSION')) {
    define('ERP_OMD_VERSION', '0.8.0-rc1');
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
    class WP_REST_Request
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
    class ERP_OMD_Project_Repository { public function all() { return [['id' => 10]]; } public function find($id) { return ['id' => $id, 'client_id' => 1]; } }
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
    class ERP_OMD_Project_Cost_Repository { public function for_project($id) { return []; } public function find($id) { return ['id' => $id, 'project_id' => 10]; } }
}
if (! class_exists('ERP_OMD_Project_Financial_Repository')) {
    class ERP_OMD_Project_Financial_Repository {}
}
if (! class_exists('ERP_OMD_Time_Entry_Repository')) {
    class ERP_OMD_Time_Entry_Repository { public function all(array $filters = []) { return []; } public function find($id) { return ['id' => $id, 'project_id' => 10, 'created_by_user_id' => 1, 'status' => 'submitted']; } }
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
    class ERP_OMD_Reporting_Service { public function sanitize_filters($filters) { return array_merge(['report_type' => 'projects'], $filters); } public function build_report($type, $filters) { return []; } public function export_definition($type, $filters) { return ['filename' => 'export.csv', 'headers' => [], 'rows' => []]; } public function build_calendar($filters) { return []; } }
}
if (! class_exists('ERP_OMD_Alert_Service')) {
    class ERP_OMD_Alert_Service { public function all_alerts() { return [['severity' => 'warning', 'code' => 'project_low_margin', 'entity_type' => 'project', 'entity_id' => 10, 'message' => 'Low margin']]; } }
}

require_once __DIR__ . '/../erp-omd/includes/class-rest-api.php';

final class RestApiTestRunner
{
    private $assertions = 0;

    public function run(): void
    {
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
            new ERP_OMD_Alert_Service()
        );

        $meta = $api->get_meta();
        $this->assertSame('0.8.0-rc1', $meta['plugin_version'], 'Meta endpoint should expose plugin version.');
        $this->assertSame(['project', 'estimate'], $meta['attachment_entity_types'], 'Meta endpoint should expose supported attachment entity types.');

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

        echo "Assertions: {$this->assertions}\n";
        echo "REST API tests passed.\n";
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
