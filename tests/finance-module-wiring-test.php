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
    'ERP_OMD_Estimate_Item_Repository',
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
        public $project_repository;
        public $project_cost_repository;
        public $project_revenue_repository;
        public $project_financial_repository;
        public $time_entry_repository;

        public function __construct($project_repository, $project_cost_repository, $project_revenue_repository, $project_financial_repository, $time_entry_repository)
        {
            $this->project_repository = $project_repository;
            $this->project_cost_repository = $project_cost_repository;
            $this->project_revenue_repository = $project_revenue_repository;
            $this->project_financial_repository = $project_financial_repository;
            $this->time_entry_repository = $time_entry_repository;
        }
    }
}

if (! class_exists('ERP_OMD_Reporting_Service')) {
    class ERP_OMD_Reporting_Service
    {
        public $project_repository;
        public $client_repository;
        public $employee_repository;
        public $salary_repository;
        public $project_cost_repository;
        public $project_revenue_repository;
        public $time_entry_repository;
        public $project_financial_service;
        public $estimate_item_repository;

        public function __construct($project_repository, $client_repository, $employee_repository, $salary_repository, $project_cost_repository, $project_revenue_repository, $time_entry_repository, $project_financial_service, $estimate_item_repository)
        {
            $this->project_repository = $project_repository;
            $this->client_repository = $client_repository;
            $this->employee_repository = $employee_repository;
            $this->salary_repository = $salary_repository;
            $this->project_cost_repository = $project_cost_repository;
            $this->project_revenue_repository = $project_revenue_repository;
            $this->time_entry_repository = $time_entry_repository;
            $this->project_financial_service = $project_financial_service;
            $this->estimate_item_repository = $estimate_item_repository;
        }
    }
}

if (! class_exists('ERP_OMD_Alert_Service')) {
    class ERP_OMD_Alert_Service
    {
        public $employee_repository;
        public $client_repository;
        public $client_rate_repository;
        public $project_repository;
        public $project_rate_repository;
        public $project_financial_service;
        public $time_entry_repository;

        public function __construct($employee_repository, $client_repository, $client_rate_repository, $project_repository, $project_rate_repository, $project_financial_service, $time_entry_repository)
        {
            $this->employee_repository = $employee_repository;
            $this->client_repository = $client_repository;
            $this->client_rate_repository = $client_rate_repository;
            $this->project_repository = $project_repository;
            $this->project_rate_repository = $project_rate_repository;
            $this->project_financial_service = $project_financial_service;
            $this->time_entry_repository = $time_entry_repository;
        }
    }
}

if (! class_exists('ERP_OMD_Estimate_Repository')) {
    class ERP_OMD_Estimate_Repository {}
}

require_once __DIR__ . '/../erp-omd/includes/class-hr-module.php';
require_once __DIR__ . '/../erp-omd/includes/class-client-project-module.php';
require_once __DIR__ . '/../erp-omd/includes/class-finance-module.php';
require_once __DIR__ . '/../erp-omd/includes/class-container.php';

$container = new ERP_OMD_Container();
$module = $container->finance_module();

if ($module !== $container->finance_module()) {
    throw new RuntimeException('Container should cache the finance module instance.');
}
if ($module->project_cost_repository() !== $module->project_cost_repository()) {
    throw new RuntimeException('Finance module should cache project cost repository instances.');
}
if ($module->project_revenue_repository() !== $module->project_revenue_repository()) {
    throw new RuntimeException('Finance module should cache project revenue repository instances.');
}
if ($module->project_financial_repository() !== $module->project_financial_repository()) {
    throw new RuntimeException('Finance module should cache project financial repository instances.');
}
if ($container->project_cost_repository() !== $module->project_cost_repository() || $container->project_revenue_repository() !== $module->project_revenue_repository()) {
    throw new RuntimeException('Container should delegate finance repositories to the module.');
}

$financialService = $container->project_financial_service();
if ($financialService !== $module->project_financial_service()) {
    throw new RuntimeException('Container should delegate project financial service to the module.');
}
if ($financialService->project_cost_repository !== $module->project_cost_repository() || $financialService->project_revenue_repository !== $module->project_revenue_repository() || $financialService->project_financial_repository !== $module->project_financial_repository()) {
    throw new RuntimeException('Project financial service should be wired with module-managed repositories.');
}

$reportingService = $container->reporting_service();
if ($reportingService !== $module->reporting_service()) {
    throw new RuntimeException('Container should delegate reporting service to the module.');
}
if ($reportingService->project_financial_service !== $financialService || $reportingService->project_cost_repository !== $module->project_cost_repository() || $reportingService->project_revenue_repository !== $module->project_revenue_repository()) {
    throw new RuntimeException('Reporting service should be wired with module-managed finance dependencies.');
}

$alertService = $container->alert_service();
if ($alertService !== $module->alert_service()) {
    throw new RuntimeException('Container should delegate alert service to the module.');
}
if ($alertService->project_financial_service !== $financialService || $alertService->project_repository !== $container->project_repository()) {
    throw new RuntimeException('Alert service should be wired with finance and project dependencies.');
}

echo "Assertions: 11\n";
echo "Finance module wiring test passed.\n";
