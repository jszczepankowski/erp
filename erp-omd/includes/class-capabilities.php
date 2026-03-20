<?php

class ERP_OMD_Capabilities
{
    public static function register_hooks()
    {
        add_action('init', [__CLASS__, 'register_roles']);
    }

    public static function get_capabilities()
    {
        return [
            'erp_omd_access',
            'erp_omd_manage_settings',
            'erp_omd_manage_roles',
            'erp_omd_manage_employees',
            'erp_omd_manage_salary',
            'erp_omd_manage_account_types',
            'erp_omd_manage_clients',
            'erp_omd_manage_projects',
            'erp_omd_manage_time',
            'erp_omd_approve_time',
        ];
    }

    public static function activate()
    {
        self::register_roles();
    }

    public static function deactivate()
    {
        // Preserve roles and capabilities to avoid regressions between sprint packages.
    }

    public static function register_roles()
    {
        $all_caps = array_fill_keys(self::get_capabilities(), true);
        $manager_caps = [
            'read' => true,
            'erp_omd_access' => true,
            'erp_omd_manage_time' => true,
            'erp_omd_manage_roles' => true,
            'erp_omd_manage_employees' => true,
            'erp_omd_manage_salary' => true,
            'erp_omd_manage_clients' => true,
            'erp_omd_manage_projects' => true,
            'erp_omd_manage_time' => true,
            'erp_omd_approve_time' => true,
        ];
        $worker_caps = [
            'read' => true,
            'erp_omd_access' => true,
            'erp_omd_manage_time' => true,
        ];

        add_role('erp_omd_manager', __('ERP Manager', 'erp-omd'), $manager_caps);
        add_role('erp_omd_worker', __('ERP Worker', 'erp-omd'), $worker_caps);

        $administrator = get_role('administrator');
        if ($administrator instanceof WP_Role) {
            foreach ($all_caps as $cap => $grant) {
                $administrator->add_cap($cap, $grant);
            }
        }

        $manager = get_role('erp_omd_manager');
        if ($manager instanceof WP_Role) {
            foreach ($manager_caps as $cap => $grant) {
                $manager->add_cap($cap, $grant);
            }
        }

        $worker = get_role('erp_omd_worker');
        if ($worker instanceof WP_Role) {
            foreach ($worker_caps as $cap => $grant) {
                $worker->add_cap($cap, $grant);
            }
        }
    }
}
