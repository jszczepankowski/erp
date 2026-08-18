<?php

declare(strict_types=1);

foreach ([
    'ERP_OMD_Role_Repository',
    'ERP_OMD_Employee_Repository',
    'ERP_OMD_Salary_History_Repository',
 'ERP_OMD_Monthly_Hours_Service',
    'ERP_OMD_Client_Repository',
    'ERP_OMD_Client_Rate_Repository',
    'ERP_OMD_Project_Repository',
    'ERP_OMD_Project_Request_Repository',
    'ERP_OMD_Project_Note_Repository',
    'ERP_OMD_Project_Rate_Repository',
    'ERP_OMD_Attachment_Repository',
    'ERP_OMD_Project_Cost_Repository',
    'ERP_OMD_Project_Revenue_Repository',
    'ERP_OMD_Project_Financial_Repository',
    'ERP_OMD_Time_Entry_Repository',
    'ERP_OMD_Estimate_Repository',
    'ERP_OMD_Estimate_Item_Repository',
    'ERP_OMD_Estimate_Audit_Repository',
    'ERP_OMD_Project_Calendar_Sync_Repository',
    'ERP_OMD_Supplier_Repository',
    'ERP_OMD_Cost_Invoice_Repository',
    'ERP_OMD_Cost_Invoice_Item_Repository',
    'ERP_OMD_Cost_Invoice_Audit_Repository',
] as $className) {
    if (! class_exists($className)) {
        eval('class ' . $className . ' {}');
    }
}

if (! class_exists('ERP_OMD_Employee_Service')) {
    class ERP_OMD_Employee_Service
    {
        public function __construct($employee_repository, $salary_repository, $monthly_hours_service) {}
    }
}

if (! class_exists('ERP_OMD_Project_Attachment_Service')) {
    class ERP_OMD_Project_Attachment_Service
    {
        public function __construct($attachment_repository) {}
    }
}

if (! class_exists('ERP_OMD_Client_Project_Service')) {
    class ERP_OMD_Client_Project_Service
    {
        public function __construct($client_repository, $employee_repository, $role_repository, $project_repository, $time_entry_repository, $alert_service, $project_attachment_service) {}
    }
}

if (! class_exists('ERP_OMD_Project_Request_Service')) {
    class ERP_OMD_Project_Request_Service
    {
        public function __construct($client_repository, $employee_repository, $estimate_repository, $project_repository, $client_project_service) {}
    }
}

if (! class_exists('ERP_OMD_Project_Financial_Service')) {
    class ERP_OMD_Project_Financial_Service
    {
        public function __construct($project_repository, $project_cost_repository, $project_revenue_repository, $project_financial_repository, $time_entry_repository) {}
    }
}

if (! class_exists('ERP_OMD_Reporting_Service')) {
    class ERP_OMD_Reporting_Service
    {
        public function __construct($project_repository, $client_repository, $employee_repository, $salary_repository, $project_cost_repository, $project_revenue_repository, $time_entry_repository, $project_financial_service, $estimate_item_repository) {}
    }
}

if (! class_exists('ERP_OMD_Alert_Service')) {
    class ERP_OMD_Alert_Service
    {
        public function __construct($employee_repository, $client_repository, $client_rate_repository, $project_repository, $project_rate_repository, $project_financial_service, $time_entry_repository) {}
    }
}

if (! class_exists('ERP_OMD_Estimate_Service')) {
    class ERP_OMD_Estimate_Service
    {
        public function __construct($estimate_repository, $estimate_item_repository, $client_repository, $project_repository, $project_cost_repository, $estimate_audit_repository, $project_request_repository, $project_revenue_repository) {}
    }
}

if (! class_exists('ERP_OMD_Cost_Invoice_Workflow_Service')) {
    class ERP_OMD_Cost_Invoice_Workflow_Service
    {
        public function __construct($invoice_repository, $audit_repository, $supplier_repository, $project_repository, $item_repository = null) {}
    }
}

if (! class_exists('ERP_OMD_KSeF_Import_Service')) {
    class ERP_OMD_KSeF_Import_Service
    {
        public function __construct($workflow_service, $invoice_repository, $audit_repository, $auth_provider = null, $http_client = null, $supplier_repository = null, $client_repository = null) {}
    }
}

if (! class_exists('ERP_OMD_Google_Calendar_Sync_Service')) {
    class ERP_OMD_Google_Calendar_Sync_Service
    {
        public $project_repository;
        public $calendar_sync_repository;

        public function __construct($project_repository, $calendar_sync_repository)
        {
            $this->project_repository = $project_repository;
            $this->calendar_sync_repository = $calendar_sync_repository;
        }
    }
}

require_once __DIR__ . '/../erp-omd/includes/class-hr-module.php';
require_once __DIR__ . '/../erp-omd/includes/class-client-project-module.php';
require_once __DIR__ . '/../erp-omd/includes/class-finance-module.php';
require_once __DIR__ . '/../erp-omd/includes/class-estimate-module.php';
require_once __DIR__ . '/../erp-omd/includes/class-ksef-module.php';
require_once __DIR__ . '/../erp-omd/includes/class-calendar-module.php';
require_once __DIR__ . '/../erp-omd/includes/class-container.php';

$container = new ERP_OMD_Container();
$module = $container->calendar_module();

if ($module !== $container->calendar_module()) {
    throw new RuntimeException('Container should cache the calendar module instance.');
}
if ($module->project_calendar_sync_repository() !== $module->project_calendar_sync_repository()) {
    throw new RuntimeException('Calendar module should cache project calendar sync repository instances.');
}
if ($module->google_calendar_sync_service() !== $module->google_calendar_sync_service()) {
    throw new RuntimeException('Calendar module should cache Google Calendar sync service instances.');
}
if ($container->project_calendar_sync_repository() !== $module->project_calendar_sync_repository()) {
    throw new RuntimeException('Container should delegate calendar sync repository to the calendar module.');
}
if ($container->google_calendar_sync_service() !== $module->google_calendar_sync_service()) {
    throw new RuntimeException('Container should delegate Google Calendar sync service to the calendar module.');
}

$service = $container->google_calendar_sync_service();
if ($service->project_repository !== $container->project_repository()) {
    throw new RuntimeException('Google Calendar sync service should use container-managed project repository.');
}
if ($service->calendar_sync_repository !== $module->project_calendar_sync_repository()) {
    throw new RuntimeException('Google Calendar sync service should use module-managed calendar sync repository.');
}

echo "Assertions: 7\n";
echo "Calendar module wiring test passed.\n";
