<?php

class ERP_OMD_Estimate_Module
{
    private $container;
    private $instances = [];

    public function __construct(ERP_OMD_Container $container)
    {
        $this->container = $container;
    }

    public function estimate_repository()
    {
        return $this->get('estimate_repository', function () {
            return new ERP_OMD_Estimate_Repository();
        });
    }

    public function estimate_item_repository()
    {
        return $this->get('estimate_item_repository', function () {
            return new ERP_OMD_Estimate_Item_Repository();
        });
    }

    public function estimate_audit_repository()
    {
        return $this->get('estimate_audit_repository', function () {
            return new ERP_OMD_Estimate_Audit_Repository();
        });
    }

    public function estimate_service()
    {
        return $this->get('estimate_service', function () {
            return new ERP_OMD_Estimate_Service(
                $this->estimate_repository(),
                $this->estimate_item_repository(),
                $this->container->client_repository(),
                $this->container->project_repository(),
                $this->container->project_cost_repository(),
                $this->estimate_audit_repository(),
                $this->container->project_request_repository(),
                $this->container->project_revenue_repository()
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
