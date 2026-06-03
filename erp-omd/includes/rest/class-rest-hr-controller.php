<?php

class ERP_OMD_REST_HR_Controller extends ERP_OMD_REST_Controller
{
    private $api;

    public function __construct(ERP_OMD_REST_API $api)
    {
        $this->api = $api;
    }

    public function register_routes()
    {
        $this->register_route('/employees', [
            $this->readable([$this->api, 'list_employees'], [$this->api, 'can_manage_employees']),
            $this->creatable([$this->api, 'create_employee'], [$this->api, 'can_manage_employees']),
        ]);
        $this->register_route('/employees/(?P<id>\d+)', [
            $this->readable([$this->api, 'get_employee'], [$this->api, 'can_manage_employees']),
            $this->editable([$this->api, 'update_employee'], [$this->api, 'can_manage_employees']),
            $this->deletable([$this->api, 'delete_employee'], [$this->api, 'can_manage_employees']),
        ]);
        $this->register_route('/employees/(?P<id>\d+)/acl', [
            $this->readable([$this->api, 'get_employee_acl'], [$this->api, 'can_manage_employees']),
            $this->editable([$this->api, 'update_employee_acl'], [$this->api, 'can_manage_employees']),
            $this->deletable([$this->api, 'reset_employee_acl'], [$this->api, 'can_manage_employees']),
        ]);
        $this->register_route('/acl-audit', [
            $this->readable([$this->api, 'list_acl_audit'], [$this->api, 'can_access_acl_audit']),
        ]);
        $this->register_route('/acl-audit/export', [
            $this->readable([$this->api, 'export_acl_audit_csv'], [$this->api, 'can_access_acl_audit']),
        ]);
        $this->register_route('/acl-config', [
            $this->readable([$this->api, 'get_acl_config'], [$this->api, 'can_manage_employees']),
        ]);
        $this->register_route('/employees/(?P<id>\d+)/salary', [
            $this->readable([$this->api, 'list_salary_history'], [$this->api, 'can_manage_salary']),
            $this->creatable([$this->api, 'create_salary_history'], [$this->api, 'can_manage_salary']),
        ]);
        $this->register_route('/salary/(?P<id>\d+)', [
            $this->readable([$this->api, 'get_salary_history'], [$this->api, 'can_manage_salary']),
            $this->editable([$this->api, 'update_salary_history'], [$this->api, 'can_manage_salary']),
            $this->deletable([$this->api, 'delete_salary_history'], [$this->api, 'can_manage_salary']),
        ]);
        $this->register_route('/monthly-hours/(?P<year_month>\d{4}-\d{2})', [
            $this->readable([$this->api, 'get_monthly_hours_suggestion'], [$this->api, 'can_manage_salary']),
        ]);
    }
}
