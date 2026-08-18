<?php

class ERP_OMD_Finance_Module
{
    private $container;
    private $instances = [];

    public function __construct(ERP_OMD_Container $container)
    {
        $this->container = $container;
    }

    public function project_cost_repository()
    {
        return $this->get('project_cost_repository', function () {
            return new ERP_OMD_Project_Cost_Repository();
        });
    }

    public function project_revenue_repository()
    {
        return $this->get('project_revenue_repository', function () {
            return new ERP_OMD_Project_Revenue_Repository();
        });
    }

    public function project_financial_repository()
    {
        return $this->get('project_financial_repository', function () {
            return new ERP_OMD_Project_Financial_Repository();
        });
    }

    public function project_financial_service()
    {
        return $this->get('project_financial_service', function () {
            return new ERP_OMD_Project_Financial_Service(
                $this->container->project_repository(),
                $this->project_cost_repository(),
                $this->project_revenue_repository(),
                $this->project_financial_repository(),
                $this->container->time_entry_repository()
            );
        });
    }

    public function reporting_service()
    {
        return $this->get('reporting_service', function () {
            return new ERP_OMD_Reporting_Service(
                $this->container->project_repository(),
                $this->container->client_repository(),
                $this->container->employee_repository(),
                $this->container->salary_repository(),
                $this->project_cost_repository(),
                $this->project_revenue_repository(),
                $this->container->time_entry_repository(),
                $this->project_financial_service(),
                $this->container->estimate_item_repository()
            );
        });
    }

    public function alert_service()
    {
        return $this->get('alert_service', function () {
            return new ERP_OMD_Alert_Service(
                $this->container->employee_repository(),
                $this->container->client_repository(),
                $this->container->client_rate_repository(),
                $this->container->project_repository(),
                $this->container->project_rate_repository(),
                $this->project_financial_service(),
                $this->container->time_entry_repository()
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
