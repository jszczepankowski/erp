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
        'ERP_OMD_REST_API' => 'includes/class-rest-api.php',
        'ERP_OMD_Role_Repository' => 'includes/repositories/class-role-repository.php',
        'ERP_OMD_Employee_Repository' => 'includes/repositories/class-employee-repository.php',
        'ERP_OMD_Salary_History_Repository' => 'includes/repositories/class-salary-history-repository.php',
        'ERP_OMD_Client_Repository' => 'includes/repositories/class-client-repository.php',
        'ERP_OMD_Client_Rate_Repository' => 'includes/repositories/class-client-rate-repository.php',
        'ERP_OMD_Project_Repository' => 'includes/repositories/class-project-repository.php',
        'ERP_OMD_Estimate_Repository' => 'includes/repositories/class-estimate-repository.php',
        'ERP_OMD_Estimate_Item_Repository' => 'includes/repositories/class-estimate-item-repository.php',
        'ERP_OMD_Project_Note_Repository' => 'includes/repositories/class-project-note-repository.php',
        'ERP_OMD_Project_Rate_Repository' => 'includes/repositories/class-project-rate-repository.php',
        'ERP_OMD_Project_Cost_Repository' => 'includes/repositories/class-project-cost-repository.php',
        'ERP_OMD_Project_Financial_Repository' => 'includes/repositories/class-project-financial-repository.php',
        'ERP_OMD_Time_Entry_Repository' => 'includes/repositories/class-time-entry-repository.php',
        'ERP_OMD_Attachment_Repository' => 'includes/repositories/class-attachment-repository.php',
        'ERP_OMD_Monthly_Hours_Service' => 'includes/services/class-monthly-hours-service.php',
        'ERP_OMD_Employee_Service' => 'includes/services/class-employee-service.php',
        'ERP_OMD_Client_Project_Service' => 'includes/services/class-client-project-service.php',
        'ERP_OMD_Estimate_Service' => 'includes/services/class-estimate-service.php',
        'ERP_OMD_Time_Entry_Service' => 'includes/services/class-time-entry-service.php',
        'ERP_OMD_Project_Financial_Service' => 'includes/services/class-project-financial-service.php',
        'ERP_OMD_Reporting_Service' => 'includes/services/class-reporting-service.php',
        'ERP_OMD_Alert_Service' => 'includes/services/class-alert-service.php',
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
