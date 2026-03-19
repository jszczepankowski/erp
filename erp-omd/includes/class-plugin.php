<?php

class ERP_OMD_Plugin
{
    /** @var ERP_OMD_Role_Repository */
    private $role_repository;

    /** @var ERP_OMD_Employee_Repository */
    private $employee_repository;

    /** @var ERP_OMD_Salary_History_Repository */
    private $salary_repository;

    /** @var ERP_OMD_Monthly_Hours_Service */
    private $monthly_hours_service;

    /** @var ERP_OMD_Employee_Service */
    private $employee_service;

    /** @var ERP_OMD_Admin */
    private $admin;

    /** @var ERP_OMD_REST_API */
    private $rest_api;

    public function __construct()
    {
        $this->role_repository = new ERP_OMD_Role_Repository();
        $this->employee_repository = new ERP_OMD_Employee_Repository();
        $this->salary_repository = new ERP_OMD_Salary_History_Repository();
        $this->monthly_hours_service = new ERP_OMD_Monthly_Hours_Service();
        $this->employee_service = new ERP_OMD_Employee_Service(
            $this->employee_repository,
            $this->salary_repository,
            $this->monthly_hours_service
        );
        $this->admin = new ERP_OMD_Admin(
            $this->role_repository,
            $this->employee_repository,
            $this->salary_repository,
            $this->employee_service,
            $this->monthly_hours_service
        );
        $this->rest_api = new ERP_OMD_REST_API(
            $this->role_repository,
            $this->employee_repository,
            $this->salary_repository,
            $this->employee_service,
            $this->monthly_hours_service
        );
    }

    public function boot()
    {
        ERP_OMD_Installer::maybe_upgrade();
        ERP_OMD_Capabilities::register_hooks();
        $this->admin->register_hooks();
        $this->rest_api->register_hooks();
    }
}
