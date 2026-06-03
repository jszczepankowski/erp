<?php

class ERP_OMD_Client_Project_Module
{
    private $container;
    private $instances = [];

    public function __construct(ERP_OMD_Container $container)
    {
        $this->container = $container;
    }

    public function client_repository()
    {
        return $this->get('client_repository', function () {
            return new ERP_OMD_Client_Repository();
        });
    }

    public function client_rate_repository()
    {
        return $this->get('client_rate_repository', function () {
            return new ERP_OMD_Client_Rate_Repository();
        });
    }

    public function project_repository()
    {
        return $this->get('project_repository', function () {
            return new ERP_OMD_Project_Repository();
        });
    }

    public function project_request_repository()
    {
        return $this->get('project_request_repository', function () {
            return new ERP_OMD_Project_Request_Repository();
        });
    }

    public function project_note_repository()
    {
        return $this->get('project_note_repository', function () {
            return new ERP_OMD_Project_Note_Repository();
        });
    }

    public function project_rate_repository()
    {
        return $this->get('project_rate_repository', function () {
            return new ERP_OMD_Project_Rate_Repository();
        });
    }

    public function attachment_repository()
    {
        return $this->get('attachment_repository', function () {
            return new ERP_OMD_Attachment_Repository();
        });
    }

    public function project_attachment_service()
    {
        return $this->get('project_attachment_service', function () {
            return new ERP_OMD_Project_Attachment_Service($this->attachment_repository());
        });
    }

    public function client_project_service()
    {
        return $this->get('client_project_service', function () {
            return new ERP_OMD_Client_Project_Service(
                $this->client_repository(),
                $this->container->employee_repository(),
                $this->container->role_repository(),
                $this->project_repository(),
                $this->container->time_entry_repository(),
                $this->container->alert_service(),
                $this->project_attachment_service()
            );
        });
    }

    public function project_request_service()
    {
        return $this->get('project_request_service', function () {
            return new ERP_OMD_Project_Request_Service(
                $this->client_repository(),
                $this->container->employee_repository(),
                $this->container->estimate_repository(),
                $this->project_repository(),
                $this->client_project_service()
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
