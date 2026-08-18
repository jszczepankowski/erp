<?php

class ERP_OMD_Calendar_Module
{
    private $container;
    private $instances = [];

    public function __construct(ERP_OMD_Container $container)
    {
        $this->container = $container;
    }

    public function project_calendar_sync_repository()
    {
        return $this->get('project_calendar_sync_repository', function () {
            return new ERP_OMD_Project_Calendar_Sync_Repository();
        });
    }

    public function google_calendar_sync_service()
    {
        return $this->get('google_calendar_sync_service', function () {
            return new ERP_OMD_Google_Calendar_Sync_Service(
                $this->container->project_repository(),
                $this->project_calendar_sync_repository()
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
