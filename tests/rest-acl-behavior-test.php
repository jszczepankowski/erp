<?php

declare(strict_types=1);

if (! defined('ERP_OMD_VERSION')) {
    define('ERP_OMD_VERSION', '0.9.0');
}
if (! defined('ERP_OMD_DB_VERSION')) {
    define('ERP_OMD_DB_VERSION', '6.2.0');
}

require_once __DIR__ . '/rest-api-test.php';

final class RestAclBehaviorTestRunner
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
            new ERP_OMD_Alert_Service(),
            new ERP_OMD_Adjustment_Audit_Repository()
        );

        $GLOBALS['erp_omd_current_user_caps'] = ['erp_omd_manage_employees'];
        $GLOBALS['erp_omd_is_super_admin'] = false;
        $this->assertSame(false, $api->can_access_acl_audit(), 'ACL audit should be blocked for non-admin/non-super-admin.');

        $GLOBALS['erp_omd_current_user_caps'] = ['administrator'];
        $this->assertSame(true, $api->can_access_acl_audit(), 'ACL audit should be allowed for administrator.');

        $GLOBALS['erp_omd_current_user_caps'] = ['erp_omd_manage_employees'];
        $GLOBALS['erp_omd_is_super_admin'] = true;
        $this->assertSame(true, $api->can_access_acl_audit(), 'ACL audit should be allowed for super-admin.');
        $GLOBALS['erp_omd_is_super_admin'] = false;
        $GLOBALS['erp_omd_current_user_id'] = 1;

        $denySelfCritical = $api->update_employee_acl(new WP_REST_Request([
            'id' => 1,
            'capability_overrides' => ['erp_omd_manage_employees' => 'deny'],
            'menu_overrides' => [],
        ]));
        $this->assertSame(true, $denySelfCritical instanceof WP_Error, 'Self-lockout should return WP_Error.');
        $this->assertSame('erp_omd_acl_self_lockout', $denySelfCritical->get_error_code(), 'Self-lockout should return expected code.');
        $this->assertSame(422, (int) (($denySelfCritical->get_error_data()['status'] ?? 0)), 'Self-lockout should return HTTP 422.');

        $GLOBALS['erp_omd_current_user_caps'] = ['erp_omd_manage_employees'];
        $escalation = $api->update_employee_acl(new WP_REST_Request([
            'id' => 1,
            'capability_overrides' => ['erp_omd_manage_settings' => 'allow'],
            'menu_overrides' => [],
        ]));
        $this->assertSame(true, $escalation instanceof WP_Error, 'Privilege escalation should return WP_Error.');
        $this->assertSame('erp_omd_acl_privilege_escalation', $escalation->get_error_code(), 'Privilege escalation should return expected code.');
        $this->assertSame(403, (int) (($escalation->get_error_data()['status'] ?? 0)), 'Privilege escalation should return HTTP 403.');

        $GLOBALS['erp_omd_current_user_caps'] = ['administrator', 'erp_omd_manage_settings', 'erp_omd_manage_employees'];
        $GLOBALS['erp_omd_user_meta'][1]['erp_omd_user_capability_overrides'] = ['erp_omd_manage_projects' => 'allow'];
        $GLOBALS['erp_omd_user_meta'][1]['erp_omd_user_menu_visibility_overrides'] = ['erp-omd-projects' => 'deny'];
        $resetResponse = $api->reset_employee_acl(new WP_REST_Request(['id' => 1]));
        $resetData = $resetResponse instanceof WP_REST_Response ? (array) $resetResponse->get_data() : (array) $resetResponse;
        $this->assertSame([], (array) ($resetData['capability_overrides'] ?? []), 'Reset ACL should clear capability overrides.');
        $this->assertSame([], (array) ($resetData['menu_overrides'] ?? []), 'Reset ACL should clear menu overrides.');

        $exportResponse = $api->export_acl_audit_csv(new WP_REST_Request([
            'target_user_id' => 1,
            'change_type' => 'capability_override',
            'per_page' => 5,
        ]));
        $this->assertSame(true, $exportResponse instanceof WP_REST_Response, 'ACL audit export should return REST response.');
        $exportData = (array) $exportResponse->get_data();
        $this->assertSame('acl-audit.csv', (string) ($exportData['filename'] ?? ''), 'ACL audit export should provide stable filename.');
        $csvContent = (string) ($exportData['content'] ?? '');
        $this->assertSame(true, strpos($csvContent, 'changed_at,actor_user_id,target_user_id,change_type') === 0, 'ACL audit CSV should contain expected header.');

        echo "Assertions: {$this->assertions}\n";
        echo "REST ACL behavior tests passed.\n";
    }

    private function assertSame($expected, $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new RuntimeException($message . ' Expected: ' . var_export($expected, true) . ' Actual: ' . var_export($actual, true));
        }
    }
}

(new RestAclBehaviorTestRunner())->run();
