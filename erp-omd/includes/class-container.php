<?php

class ERP_OMD_Container
{
    private $instances = [];

    public function hr_module()
    {
        return $this->get('hr_module', function () {
            return new ERP_OMD_HR_Module();
        });
    }

    public function role_repository()
    {
        return $this->hr_module()->role_repository();
    }

    public function employee_repository()
    {
        return $this->hr_module()->employee_repository();
    }

    public function salary_repository()
    {
        return $this->hr_module()->salary_repository();
    }

    public function client_project_module()
    {
        return $this->get('client_project_module', function () {
            return new ERP_OMD_Client_Project_Module($this);
        });
    }

    public function client_repository()
    {
        return $this->client_project_module()->client_repository();
    }

    public function client_rate_repository()
    {
        return $this->client_project_module()->client_rate_repository();
    }

    public function project_repository()
    {
        return $this->client_project_module()->project_repository();
    }

    public function project_request_repository()
    {
        return $this->client_project_module()->project_request_repository();
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

    public function project_note_repository()
    {
        return $this->client_project_module()->project_note_repository();
    }

    public function project_rate_repository()
    {
        return $this->client_project_module()->project_rate_repository();
    }

    public function finance_module()
    {
        return $this->get('finance_module', function () {
            return new ERP_OMD_Finance_Module($this);
        });
    }

    public function project_cost_repository()
    {
        return $this->finance_module()->project_cost_repository();
    }

    public function project_revenue_repository()
    {
        return $this->finance_module()->project_revenue_repository();
    }

    public function project_financial_repository()
    {
        return $this->finance_module()->project_financial_repository();
    }

    public function time_entry_repository()
    {
        return $this->get('time_entry_repository', function () {
            return new ERP_OMD_Time_Entry_Repository();
        });
    }

    public function attachment_repository()
    {
        return $this->client_project_module()->attachment_repository();
    }

    public function project_calendar_sync_repository()
    {
        return $this->get('project_calendar_sync_repository', function () {
            return new ERP_OMD_Project_Calendar_Sync_Repository();
        });
    }

    public function project_attachment_service()
    {
        return $this->client_project_module()->project_attachment_service();
    }

    public function google_calendar_sync_service()
    {
        return $this->get('google_calendar_sync_service', function () {
            return new ERP_OMD_Google_Calendar_Sync_Service(
                $this->project_repository(),
                $this->project_calendar_sync_repository()
            );
        });
    }

    public function monthly_hours_service()
    {
        return $this->hr_module()->monthly_hours_service();
    }

    public function employee_service()
    {
        return $this->hr_module()->employee_service();
    }

    public function estimate_service()
    {
        return $this->get('estimate_service', function () {
            return new ERP_OMD_Estimate_Service(
                $this->estimate_repository(),
                $this->estimate_item_repository(),
                $this->client_repository(),
                $this->project_repository(),
                $this->project_cost_repository(),
                $this->estimate_audit_repository(),
                $this->project_request_repository(),
                $this->project_revenue_repository()
            );
        });
    }

    public function time_entry_service()
    {
        return $this->get('time_entry_service', function () {
            return new ERP_OMD_Time_Entry_Service(
                $this->time_entry_repository(),
                $this->employee_repository(),
                $this->project_repository(),
                $this->role_repository(),
                $this->client_rate_repository(),
                $this->project_rate_repository(),
                $this->salary_repository()
            );
        });
    }

    public function project_financial_service()
    {
        return $this->finance_module()->project_financial_service();
    }

    public function reporting_service()
    {
        return $this->finance_module()->reporting_service();
    }

    public function alert_service()
    {
        return $this->finance_module()->alert_service();
    }

    public function client_project_service()
    {
        return $this->client_project_module()->client_project_service();
    }

    public function project_request_service()
    {
        return $this->client_project_module()->project_request_service();
    }

    public function admin()
    {
        return $this->get('admin', function () {
            return new ERP_OMD_Admin(
                $this->role_repository(),
                $this->employee_repository(),
                $this->salary_repository(),
                $this->employee_service(),
                $this->monthly_hours_service(),
                $this->client_repository(),
                $this->client_rate_repository(),
                $this->project_repository(),
                $this->project_request_repository(),
                $this->estimate_repository(),
                $this->estimate_item_repository(),
                $this->project_note_repository(),
                $this->client_project_service(),
                $this->project_request_service(),
                $this->estimate_service(),
                $this->project_rate_repository(),
                $this->project_cost_repository(),
                $this->project_revenue_repository(),
                $this->project_financial_repository(),
                $this->time_entry_repository(),
                $this->attachment_repository(),
                $this->time_entry_service(),
                $this->project_financial_service(),
                $this->reporting_service(),
                $this->alert_service()
            );
        });
    }

    public function frontend()
    {
        return $this->get('frontend', function () {
            return new ERP_OMD_Frontend(
                $this->employee_repository(),
                $this->client_repository(),
                $this->project_repository(),
                $this->role_repository(),
                $this->time_entry_repository(),
                $this->project_request_repository(),
                $this->estimate_repository(),
                $this->estimate_item_repository(),
                $this->project_cost_repository(),
                $this->project_revenue_repository(),
                $this->time_entry_service(),
                $this->client_project_service(),
                $this->project_request_service(),
                $this->estimate_service(),
                $this->project_financial_service(),
                $this->reporting_service(),
                $this->alert_service()
            );
        });
    }

    public function rest_api()
    {
        return $this->get('rest_api', function () {
            return new ERP_OMD_REST_API(
                $this->role_repository(),
                $this->employee_repository(),
                $this->salary_repository(),
                $this->employee_service(),
                $this->monthly_hours_service(),
                $this->client_repository(),
                $this->client_rate_repository(),
                $this->project_repository(),
                $this->estimate_repository(),
                $this->estimate_item_repository(),
                $this->project_note_repository(),
                $this->client_project_service(),
                $this->estimate_service(),
                $this->project_rate_repository(),
                $this->project_cost_repository(),
                $this->project_financial_repository(),
                $this->time_entry_repository(),
                $this->attachment_repository(),
                $this->time_entry_service(),
                $this->project_financial_service(),
                $this->reporting_service(),
                $this->alert_service()
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
