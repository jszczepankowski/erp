<?php

class ERP_OMD_KSeF_Module
{
    private $container;
    private $instances = [];

    public function __construct(ERP_OMD_Container $container)
    {
        $this->container = $container;
    }

    public function supplier_repository()
    {
        return $this->get('supplier_repository', function () {
            return new ERP_OMD_Supplier_Repository();
        });
    }

    public function cost_invoice_repository()
    {
        return $this->get('cost_invoice_repository', function () {
            return new ERP_OMD_Cost_Invoice_Repository();
        });
    }

    public function cost_invoice_item_repository()
    {
        return $this->get('cost_invoice_item_repository', function () {
            return new ERP_OMD_Cost_Invoice_Item_Repository();
        });
    }

    public function cost_invoice_audit_repository()
    {
        return $this->get('cost_invoice_audit_repository', function () {
            return new ERP_OMD_Cost_Invoice_Audit_Repository();
        });
    }

    public function cost_invoice_workflow_service()
    {
        return $this->get('cost_invoice_workflow_service', function () {
            return new ERP_OMD_Cost_Invoice_Workflow_Service(
                $this->cost_invoice_repository(),
                $this->cost_invoice_audit_repository(),
                $this->supplier_repository(),
                $this->container->project_repository(),
                $this->cost_invoice_item_repository()
            );
        });
    }

    public function ksef_import_service()
    {
        return $this->get('ksef_import_service', function () {
            return new ERP_OMD_KSeF_Import_Service(
                $this->cost_invoice_workflow_service(),
                $this->cost_invoice_repository(),
                $this->cost_invoice_audit_repository(),
                null,
                null,
                $this->supplier_repository(),
                $this->container->client_repository()
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
