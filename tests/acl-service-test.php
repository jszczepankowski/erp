<?php

declare(strict_types=1);

$serviceSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/services/class-acl-service.php');
$adminSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-admin-runtime.php');
$restSource = (string) file_get_contents(__DIR__ . '/../erp-omd/includes/class-rest-api.php');

if ($serviceSource === '' || $adminSource === '' || $restSource === '') {
    throw new RuntimeException('Unable to load ACL-related sources.');
}

$serviceFragments = [
    'class ERP_OMD_Acl_Service',
    'USER_CAP_OVERRIDES_META_KEY',
    'USER_MENU_OVERRIDES_META_KEY',
    'OPTION_ACL_AUDIT_LOG',
    'ALLOWED_MENU_SLUGS',
    'function can_user(',
    'function can_view_menu_page(',
    "if (\$decision === 'deny')",
    "if (\$decision === 'allow')",
    'function append_acl_audit_log(',
    "update_option(self::OPTION_ACL_AUDIT_LOG, \$log, false);",
];

$adminFragments = [
    "case 'save_employee_acl_overrides':",
    'handle_employee_acl_overrides_save',
    'ERP_OMD_Acl_Service::USER_CAP_OVERRIDES_META_KEY',
    'ERP_OMD_Acl_Service::USER_MENU_OVERRIDES_META_KEY',
    'ERP_OMD_Acl_Service::ALLOWED_MENU_SLUGS',
    'append_acl_audit_log(',
    'sanitize_acl_override_map(array $overrides, array $allowed_keys = [])',
    'if ($allowed_lookup !== [] && ! isset($allowed_lookup[$normalized_key]))',
    'private function require_capability($capability)',
    "\$this->acl_service->can_user(\$user_id, (string) \$capability)",
];

$restFragments = [
    "'/employees/(?P<id>\\d+)/acl'",
    "'/acl-audit'",
    "'/acl-config'",
    'function update_employee_acl(',
    'function reset_employee_acl(',
    'function list_acl_audit(',
    'function get_acl_config(',
    'validate_acl_update_guardrails(',
    'erp_omd_acl_self_lockout',
    'erp_omd_acl_privilege_escalation',
    'ERP_OMD_Acl_Service::ALLOWED_MENU_SLUGS',
    'sanitize_acl_override_map(array $map, array $allowed_keys = [])',
    'private function current_user_can_acl($capability)',
    "can_manage_settings() { return \$this->current_user_can_acl('erp_omd_manage_settings')",
];

$assertions = 0;
foreach ($serviceFragments as $fragment) {
    $assertions++;
    if (strpos($serviceSource, $fragment) === false) {
        throw new RuntimeException('Missing ACL service fragment: ' . $fragment);
    }
}
foreach ($adminFragments as $fragment) {
    $assertions++;
    if (strpos($adminSource, $fragment) === false) {
        throw new RuntimeException('Missing admin ACL fragment: ' . $fragment);
    }
}
foreach ($restFragments as $fragment) {
    $assertions++;
    if (strpos($restSource, $fragment) === false) {
        throw new RuntimeException('Missing REST ACL fragment: ' . $fragment);
    }
}

echo "Assertions: {$assertions}\n";
echo "ACL service integration test passed.\n";
