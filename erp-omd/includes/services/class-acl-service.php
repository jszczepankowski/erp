<?php

class ERP_OMD_Acl_Service
{
    public const USER_CAP_OVERRIDES_META_KEY = 'erp_omd_user_capability_overrides';
    public const USER_MENU_OVERRIDES_META_KEY = 'erp_omd_user_menu_visibility_overrides';
    public const OPTION_ACL_AUDIT_LOG = 'erp_omd_acl_audit_log';
    public const ALLOWED_MENU_SLUGS = [
        'erp-omd',
        'erp-omd-private-tasks',
        'erp-omd-employees',
        'erp-omd-roles',
        'erp-omd-clients',
        'erp-omd-time',
        'erp-omd-estimates',
        'erp-omd-estimates-new',
        'erp-omd-projects',
        'erp-omd-projects-new',
        'erp-omd-requests',
        'erp-omd-calendar',
        'erp-omd-cost-invoices',
        'erp-omd-reports',
        'erp-omd-alerts',
        'erp-omd-settings',
    ];
    public const CRITICAL_CAPABILITIES = [
        'erp_omd_manage_settings',
        'erp_omd_manage_roles',
        'erp_omd_manage_employees',
        'erp_omd_manage_clients',
        'erp_omd_manage_projects',
        'erp_omd_manage_time',
        'erp_omd_approve_time',
    ];

    /**
     * @param int $user_id
     * @param string $capability
     */
    public function can_user($user_id, $capability)
    {
        $user_id = (int) $user_id;
        $capability = sanitize_key((string) $capability);
        if ($user_id <= 0 || $capability === '') {
            return false;
        }

        $decision = $this->resolve_override((array) get_user_meta($user_id, self::USER_CAP_OVERRIDES_META_KEY, true), $capability);
        if ($decision === 'deny') {
            return false;
        }
        if ($decision === 'allow') {
            return true;
        }

        return user_can($user_id, $capability);
    }

    /**
     * @param int $user_id
     * @param string $page_slug
     */
    public function can_view_menu_page($user_id, $page_slug)
    {
        $user_id = (int) $user_id;
        $page_slug = sanitize_key((string) $page_slug);
        if ($user_id <= 0 || $page_slug === '') {
            return false;
        }

        $decision = $this->resolve_override((array) get_user_meta($user_id, self::USER_MENU_OVERRIDES_META_KEY, true), $page_slug);
        if ($decision === 'deny') {
            return false;
        }
        if ($decision === 'allow') {
            return true;
        }

        return true;
    }

    /**
     * @param array<string,string> $overrides
     * @param string $key
     * @return string
     */
    private function resolve_override(array $overrides, $key)
    {
        $raw = strtolower((string) ($overrides[$key] ?? ''));
        if ($raw === 'deny' || $raw === 'allow') {
            return $raw;
        }

        return 'inherit';
    }

    /**
     * @param int $actor_user_id
     * @param int $target_user_id
     * @param array<string,string> $before_capability_overrides
     * @param array<string,string> $after_capability_overrides
     * @param array<string,string> $before_menu_overrides
     * @param array<string,string> $after_menu_overrides
     */
    public function append_acl_audit_log($actor_user_id, $target_user_id, array $before_capability_overrides, array $after_capability_overrides, array $before_menu_overrides, array $after_menu_overrides)
    {
        $entry = null;
        $log = (array) get_option(self::OPTION_ACL_AUDIT_LOG, []);
        $change_type = 'acl_override';
        if ($before_capability_overrides !== $after_capability_overrides) {
            $change_type = 'capability_override';
        }
        if ($before_menu_overrides !== $after_menu_overrides) {
            $change_type = $change_type === 'capability_override' ? 'capability_and_menu_override' : 'menu_override';
        }
        $entry = [
            'id' => 'acl_' . wp_generate_uuid4(),
            'actor_user_id' => (int) $actor_user_id,
            'target_user_id' => (int) $target_user_id,
            'changed_at' => current_time('mysql'),
            'change_type' => $change_type,
            'before' => [
                'capability_overrides' => $before_capability_overrides,
                'menu_overrides' => $before_menu_overrides,
            ],
            'after' => [
                'capability_overrides' => $after_capability_overrides,
                'menu_overrides' => $after_menu_overrides,
            ],
        ];
        $log[] = $entry;
        if (count($log) > 1000) {
            $log = array_slice($log, -1000);
        }
        update_option(self::OPTION_ACL_AUDIT_LOG, $log, false);

        if (class_exists('ERP_OMD_Acl_Audit_Repository')) {
            $repo = new ERP_OMD_Acl_Audit_Repository();
            $repo->insert([
                'event_id' => (string) ($entry['id'] ?? ''),
                'actor_user_id' => (int) ($entry['actor_user_id'] ?? 0),
                'target_user_id' => (int) ($entry['target_user_id'] ?? 0),
                'changed_at' => (string) ($entry['changed_at'] ?? ''),
                'change_type' => (string) ($entry['change_type'] ?? 'acl_override'),
                'before' => (array) ($entry['before'] ?? []),
                'after' => (array) ($entry['after'] ?? []),
            ]);
        }
    }
}
