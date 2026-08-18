<?php

class ERP_OMD_HR_Module
{
    private $instances = [];

    public function role_repository()
    {
        return $this->get('role_repository', function () {
            return new ERP_OMD_Role_Repository();
        });
    }

    public function employee_repository()
    {
        return $this->get('employee_repository', function () {
            return new ERP_OMD_Employee_Repository();
        });
    }

    public function salary_repository()
    {
        return $this->get('salary_repository', function () {
            return new ERP_OMD_Salary_History_Repository();
        });
    }

    public function monthly_hours_service()
    {
        return $this->get('monthly_hours_service', function () {
            return new ERP_OMD_Monthly_Hours_Service();
        });
    }

    public function employee_service()
    {
        return $this->get('employee_service', function () {
            return new ERP_OMD_Employee_Service(
                $this->employee_repository(),
                $this->salary_repository(),
                $this->monthly_hours_service()
            );
        });
    }

    private function get($key, callable $factory)
    {
        if (! array_key_exists($key, $this->instances)) {
            $this->instances[$key] = $factory();
        }

        return $this->instances[$key];
    }
}
