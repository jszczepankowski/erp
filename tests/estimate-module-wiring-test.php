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
        public $estimate_repository;
        public $estimate_item_repository;
        public $client_repository;
        public $project_repository;
        public $project_cost_repository;
        public $estimate_audit_repository;
        public $project_request_repository;
        public $project_revenue_repository;

        public function __construct($estimate_repository, $estimate_item_repository, $client_repository, $project_repository, $project_cost_repository, $estimate_audit_repository, $project_request_repository, $project_revenue_repository)
        {
            $this->estimate_repository = $estimate_repository;
            $this->estimate_item_repository = $estimate_item_repository;
            $this->client_repository = $client_repository;
            $this->project_repository = $project_repository;
            $this->project_cost_repository = $project_cost_repository;
            $this->estimate_audit_repository = $estimate_audit_repository;
            $this->project_request_repository = $project_request_repository;
            $this->project_revenue_repository = $project_revenue_repository;
        }
    }
}

require_once __DIR__ . '/../erp-omd/includes/class-hr-module.php';
require_once __DIR__ . '/../erp-omd/includes/class-client-project-module.php';
require_once __DIR__ . '/../erp-omd/includes/class-finance-module.php';
require_once __DIR__ . '/../erp-omd/includes/class-estimate-module.php';
require_once __DIR__ . '/../erp-omd/includes/class-container.php';

$container = new ERP_OMD_Container();
$module = $container->estimate_module();

if ($module !== $container->estimate_module()) {
    throw new RuntimeException('Container should cache the estimate module instance.');
}
if ($module->estimate_repository() !== $module->estimate_repository()) {
    throw new RuntimeException('Estimate module should cache estimate repository instances.');
}
if ($module->estimate_item_repository() !== $module->estimate_item_repository()) {
    throw new RuntimeException('Estimate module should cache estimate item repository instances.');
}
if ($module->estimate_audit_repository() !== $module->estimate_audit_repository()) {
    throw new RuntimeException('Estimate module should cache estimate audit repository instances.');
}
if ($module->estimate_service() !== $module->estimate_service()) {
    throw new RuntimeException('Estimate module should cache estimate service instances.');
}
if ($container->estimate_repository() !== $module->estimate_repository() || $container->estimate_item_repository() !== $module->estimate_item_repository() || $container->estimate_audit_repository() !== $module->estimate_audit_repository()) {
    throw new RuntimeException('Container should delegate estimate repositories to the estimate module.');
}
if ($container->estimate_service() !== $module->estimate_service()) {
    throw new RuntimeException('Container should delegate estimate service to the estimate module.');
}

$service = $container->estimate_service();
if ($service->estimate_repository !== $module->estimate_repository() || $service->estimate_item_repository !== $module->estimate_item_repository() || $service->estimate_audit_repository !== $module->estimate_audit_repository()) {
    throw new RuntimeException('Estimate service should use module-managed estimate dependencies.');
}
if ($service->client_repository !== $container->client_repository() || $service->project_repository !== $container->project_repository() || $service->project_cost_repository !== $container->project_cost_repository() || $service->project_revenue_repository !== $container->project_revenue_repository()) {
    throw new RuntimeException('Estimate service should use container-managed cross-domain dependencies.');
}
if ($service->project_request_repository !== $container->project_request_repository()) {
    throw new RuntimeException('Estimate service should use container-managed project request repository.');
}

echo "Assertions: 10\n";
echo "Estimate module wiring test passed.\n";
