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
        'ERP_OMD_Admin' => 'includes/class-admin-runtime.php',
        'ERP_OMD_Frontend' => 'includes/class-frontend-runtime.php',
        'ERP_OMD_REST_API' => 'includes/class-rest-api.php',
        'ERP_OMD_Backup_Manager' => 'includes/class-backup-manager.php',
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
        'ERP_OMD_Supplier_Repository' => 'includes/repositories/class-supplier-repository.php',
        'ERP_OMD_Cost_Invoice_Repository' => 'includes/repositories/class-cost-invoice-repository.php',
        'ERP_OMD_Cost_Invoice_Audit_Repository' => 'includes/repositories/class-cost-invoice-audit-repository.php',
        'ERP_OMD_Project_Calendar_Sync_Repository' => 'includes/repositories/class-project-calendar-sync-repository.php',
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
        'ERP_OMD_Project_Attachment_Service' => 'includes/services/class-project-attachment-service.php',
        'ERP_OMD_Google_Calendar_Sync_Service' => 'includes/services/class-google-calendar-sync-service.php',
        'ERP_OMD_Period_Service' => 'includes/services/class-omd-period-service.php',
        'ERP_OMD_Cost_Invoice_Workflow_Service' => 'includes/services/class-cost-invoice-workflow-service.php',
        'ERP_OMD_KSeF_Import_Service' => 'includes/services/class-ksef-import-service.php',
        'ERP_OMD_Client_Portal_Service' => 'includes/services/class-client-portal-service.php',
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

        if (file_exists($file)) {
            require_once $file;
        }
    }
}
