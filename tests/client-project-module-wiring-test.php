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
    'ERP_OMD_Estimate_Repository',
    'ERP_OMD_Project_Cost_Repository',
    'ERP_OMD_Project_Revenue_Repository',
    'ERP_OMD_Project_Financial_Repository',
    'ERP_OMD_Time_Entry_Repository',
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
        public $attachment_repository;

        public function __construct($attachment_repository)
        {
            $this->attachment_repository = $attachment_repository;
        }
    }
}

if (! class_exists('ERP_OMD_Project_Financial_Service')) {
    class ERP_OMD_Project_Financial_Service
    {
        public function __construct($project_repository, $project_cost_repository, $project_revenue_repository, $project_financial_repository, $time_entry_repository) {}
    }
}

if (! class_exists('ERP_OMD_Alert_Service')) {
    class ERP_OMD_Alert_Service
    {
        public function __construct($employee_repository, $client_repository, $client_rate_repository, $project_repository, $project_rate_repository, $project_financial_service, $time_entry_repository) {}
    }
}

if (! class_exists('ERP_OMD_Client_Project_Service')) {
    class ERP_OMD_Client_Project_Service
    {
        public $client_repository;
        public $employee_repository;
        public $role_repository;
        public $project_repository;
        public $time_entry_repository;
        public $alert_service;
        public $project_attachment_service;

        public function __construct($client_repository, $employee_repository, $role_repository, $project_repository, $time_entry_repository, $alert_service, $project_attachment_service)
        {
            $this->client_repository = $client_repository;
            $this->employee_repository = $employee_repository;
            $this->role_repository = $role_repository;
            $this->project_repository = $project_repository;
            $this->time_entry_repository = $time_entry_repository;
            $this->alert_service = $alert_service;
            $this->project_attachment_service = $project_attachment_service;
        }
    }
}

if (! class_exists('ERP_OMD_Project_Request_Service')) {
    class ERP_OMD_Project_Request_Service
    {
        public $client_repository;
        public $employee_repository;
        public $estimate_repository;
        public $project_repository;
        public $client_project_service;

        public function __construct($client_repository, $employee_repository, $estimate_repository, $project_repository, $client_project_service)
        {
            $this->client_repository = $client_repository;
            $this->employee_repository = $employee_repository;
            $this->estimate_repository = $estimate_repository;
            $this->project_repository = $project_repository;
            $this->client_project_service = $client_project_service;
        }
    }
}

require_once __DIR__ . '/../erp-omd/includes/class-hr-module.php';
require_once __DIR__ . '/../erp-omd/includes/class-client-project-module.php';
require_once __DIR__ . '/../erp-omd/includes/class-container.php';

$container = new ERP_OMD_Container();
$module = $container->client_project_module();

if ($module !== $container->client_project_module()) {
    throw new RuntimeException('Container should cache the client/project module instance.');
}
if ($module->client_repository() !== $module->client_repository()) {
    throw new RuntimeException('Client/project module should cache client repository instances.');
}
if ($module->project_repository() !== $module->project_repository()) {
    throw new RuntimeException('Client/project module should cache project repository instances.');
}
if ($module->project_attachment_service() !== $module->project_attachment_service()) {
    throw new RuntimeException('Client/project module should cache project attachment service instances.');
}
if ($container->client_repository() !== $module->client_repository() || $container->project_repository() !== $module->project_repository()) {
    throw new RuntimeException('Container should delegate client/project repositories to the module.');
}
if ($container->project_attachment_service() !== $module->project_attachment_service()) {
    throw new RuntimeException('Container should delegate project attachment service to the module.');
}

$clientProjectService = $container->client_project_service();
if ($clientProjectService !== $module->client_project_service()) {
    throw new RuntimeException('Container should delegate client project service to the module.');
}
if ($clientProjectService->client_repository !== $module->client_repository() || $clientProjectService->project_repository !== $module->project_repository() || $clientProjectService->project_attachment_service !== $module->project_attachment_service()) {
    throw new RuntimeException('Client project service should be wired with module-managed dependencies.');
}

$projectRequestService = $container->project_request_service();
if ($projectRequestService !== $module->project_request_service()) {
    throw new RuntimeException('Container should delegate project request service to the module.');
}
if ($projectRequestService->client_project_service !== $clientProjectService || $projectRequestService->client_repository !== $module->client_repository() || $projectRequestService->project_repository !== $module->project_repository()) {
    throw new RuntimeException('Project request service should be wired with module-managed client/project dependencies.');
}

echo "Assertions: 10\n";
echo "Client/project module wiring test passed.\n";
