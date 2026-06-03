<?php

declare(strict_types=1);

if (! class_exists('ERP_OMD_Role_Repository')) {
    class ERP_OMD_Role_Repository {}
}

if (! class_exists('ERP_OMD_Employee_Repository')) {
    class ERP_OMD_Employee_Repository {}
}

if (! class_exists('ERP_OMD_Salary_History_Repository')) {
    class ERP_OMD_Salary_History_Repository {}
}

if (! class_exists('ERP_OMD_Monthly_Hours_Service')) {
    class ERP_OMD_Monthly_Hours_Service {}
}

if (! class_exists('ERP_OMD_Employee_Service')) {
    class ERP_OMD_Employee_Service
    {
        public $employee_repository;
        public $salary_repository;
        public $monthly_hours_service;

        public function __construct($employee_repository, $salary_repository, $monthly_hours_service)
        {
            $this->employee_repository = $employee_repository;
            $this->salary_repository = $salary_repository;
            $this->monthly_hours_service = $monthly_hours_service;
        }
    }
}

require_once __DIR__ . '/../erp-omd/includes/class-hr-module.php';
require_once __DIR__ . '/../erp-omd/includes/class-container.php';

$module = new ERP_OMD_HR_Module();
$employeeRepository = $module->employee_repository();
$salaryRepository = $module->salary_repository();
$monthlyHoursService = $module->monthly_hours_service();
$employeeService = $module->employee_service();

if ($module->role_repository() !== $module->role_repository()) {
    throw new RuntimeException('HR module should cache role repository instances.');
}
if ($employeeRepository !== $module->employee_repository()) {
    throw new RuntimeException('HR module should cache employee repository instances.');
}
if ($salaryRepository !== $module->salary_repository()) {
    throw new RuntimeException('HR module should cache salary repository instances.');
}
if ($monthlyHoursService !== $module->monthly_hours_service()) {
    throw new RuntimeException('HR module should cache monthly hours service instances.');
}
if ($employeeService !== $module->employee_service()) {
    throw new RuntimeException('HR module should cache employee service instances.');
}
if ($employeeService->employee_repository !== $employeeRepository || $employeeService->salary_repository !== $salaryRepository || $employeeService->monthly_hours_service !== $monthlyHoursService) {
    throw new RuntimeException('HR module should wire employee service with module-managed dependencies.');
}

$container = new ERP_OMD_Container();
if ($container->hr_module() !== $container->hr_module()) {
    throw new RuntimeException('Container should cache the HR module instance.');
}
if ($container->employee_repository() !== $container->hr_module()->employee_repository()) {
    throw new RuntimeException('Container employee_repository() should delegate to HR module.');
}
if ($container->employee_service() !== $container->hr_module()->employee_service()) {
    throw new RuntimeException('Container employee_service() should delegate to HR module.');
}

echo "Assertions: 9\n";
echo "HR module wiring test passed.\n";
