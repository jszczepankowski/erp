<?php

class ERP_OMD_Autoloader
{
    /**
     * @var array<string,string>
     */
    private static $class_map = [
        'ERP_OMD_Plugin' => 'includes/class-plugin.php',
        'ERP_OMD_Installer' => 'includes/class-installer.php',
        'ERP_OMD_Capabilities' => 'includes/class-capabilities.php',
        'ERP_OMD_Admin' => 'includes/class-admin.php',
        'ERP_OMD_Frontend' => 'includes/class-frontend.php',
        'ERP_OMD_REST_API' => 'includes/class-rest-api.php',
        'ERP_OMD_Cron_Manager' => 'includes/class-cron-manager.php',
        'ERP_OMD_Role_Repository' => 'includes/repositories/class-role-repository.php',
        'ERP_OMD_Employee_Repository' => 'includes/repositories/class-employee-repository.php',
        'ERP_OMD_Salary_History_Repository' => 'includes/repositories/class-salary-history-repository.php',
        'ERP_OMD_Client_Repository' => 'includes/repositories/class-client-repository.php',
        'ERP_OMD_Client_Rate_Repository' => 'includes/repositories/class-client-rate-repository.php',
        'ERP_OMD_Project_Repository' => 'includes/repositories/class-project-repository.php',
        'ERP_OMD_Project_Request_Repository' => 'includes/repositories/class-project-request-repository.php',
        'ERP_OMD_Estimate_Repository' => 'includes/repositories/class-estimate-repository-v2.php',
        'ERP_OMD_Estimate_Item_Repository' => 'includes/repositories/class-estimate-item-repository.php',
        'ERP_OMD_Estimate_Audit_Repository' => 'includes/repositories/class-estimate-audit-repository.php',
        'ERP_OMD_Project_Note_Repository' => 'includes/repositories/class-project-note-repository.php',
        'ERP_OMD_Project_Rate_Repository' => 'includes/repositories/class-project-rate-repository.php',
        'ERP_OMD_Project_Cost_Repository' => 'includes/repositories/class-project-cost-repository.php',
        'ERP_OMD_Project_Revenue_Repository' => 'includes/repositories/class-project-revenue-repository.php',
        'ERP_OMD_Project_Financial_Repository' => 'includes/repositories/class-project-financial-repository.php',
        'ERP_OMD_Time_Entry_Repository' => 'includes/repositories/class-time-entry-repository.php',
        'ERP_OMD_Attachment_Repository' => 'includes/repositories/class-attachment-repository.php',
        'ERP_OMD_Period_Repository' => 'includes/repositories/class-omd-period-repository.php',
        'ERP_OMD_Adjustment_Audit_Repository' => 'includes/repositories/class-omd-adjustment-audit-repository.php',
        'ERP_OMD_Monthly_Hours_Service' => 'includes/services/class-monthly-hours-service.php',
        'ERP_OMD_Employee_Service' => 'includes/services/class-employee-service.php',
        'ERP_OMD_Client_Project_Service' => 'includes/services/class-client-project-service.php',
        'ERP_OMD_Project_Request_Service' => 'includes/services/class-project-request-service.php',
        'ERP_OMD_Estimate_Service' => 'includes/services/class-estimate-service.php',
        'ERP_OMD_Time_Entry_Service' => 'includes/services/class-time-entry-service.php',
        'ERP_OMD_Project_Financial_Service' => 'includes/services/class-project-financial-service.php',
        'ERP_OMD_Reporting_Service' => 'includes/services/class-reporting-service-v2.php',
        'ERP_OMD_Alert_Service' => 'includes/services/class-alert-service.php',
        'ERP_OMD_Period_Service' => 'includes/services/class-omd-period-service.php',
    ];

    public static function register()
    {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    public static function autoload($class)
    {
        if (! isset(self::$class_map[$class])) {
            return;
        }

        $file = ERP_OMD_PATH . self::$class_map[$class];

        if ($class === 'ERP_OMD_Cron_Manager' && ! self::is_cron_manager_file_safe($file)) {
            self::register_cron_manager_fallback();
            return;
        }

        if (file_exists($file)) {
            require_once $file;
        }
    }

    private static function is_cron_manager_file_safe($file)
    {
        if (! is_string($file) || $file === '' || ! file_exists($file) || ! is_readable($file)) {
            return false;
        }

        $source = (string) file_get_contents($file);
        if ($source === '') {
            return false;
        }

        preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(/', $source, $matches);
        $method_names = array_map('strtolower', $matches[1] ?? []);
        $counts = array_count_values($method_names);
        $duplicates = array_filter($counts, static function ($count) {
            return $count > 1;
        });

        if (! empty($duplicates)) {
            error_log('ERP OMD: class-cron-manager.php contains duplicated methods: ' . implode(', ', array_keys($duplicates)));
            return false;
        }

        return true;
    }

    private static function register_cron_manager_fallback()
    {
        if (class_exists('ERP_OMD_Cron_Manager', false)) {
            return;
        }

        eval('class ERP_OMD_Cron_Manager {
            const WEEKLY_BACKUP_HOOK = "erp_omd_weekly_db_backup";
            const MISSING_HOURS_HOOK = "erp_omd_daily_missing_hours_notifications";
            public static function register_hooks() {}
            public static function activate() {}
            public static function deactivate() {}
            public static function run_weekly_backup() {
                update_option("erp_omd_last_backup_status", "cron_manager_invalid_file");
                update_option("erp_omd_last_backup_at", current_time("mysql"));
            }
            public static function restore_backup_bundle_from_zip($zip_path) {
                throw new RuntimeException("Cron manager source file is invalid (duplicated methods). Please redeploy plugin files.");
            }
            public static function run_missing_hours_notifications() {}
        }');
    }
}
