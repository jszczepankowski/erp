<?php

class ERP_OMD_Plugin
{
    private $role_repository;
    private $employee_repository;
    private $salary_repository;
    private $client_repository;
    private $client_rate_repository;
    private $project_repository;
    private $project_request_repository;
    private $estimate_repository;
    private $estimate_item_repository;
    private $estimate_audit_repository;
    private $project_note_repository;
    private $project_rate_repository;
    private $project_cost_repository;
    private $project_revenue_repository;
    private $project_financial_repository;
    private $time_entry_repository;
    private $attachment_repository;
    private $monthly_hours_service;
    private $employee_service;
    private $client_project_service;
    private $project_request_service;
    private $estimate_service;
    private $time_entry_service;
    private $project_financial_service;
    private $reporting_service;
    private $alert_service;
    private $admin;
    private $frontend;
    private $rest_api;

    public function __construct()
    {
        $this->role_repository = new ERP_OMD_Role_Repository();
        $this->employee_repository = new ERP_OMD_Employee_Repository();
        $this->salary_repository = new ERP_OMD_Salary_History_Repository();
        $this->client_repository = new ERP_OMD_Client_Repository();
        $this->client_rate_repository = new ERP_OMD_Client_Rate_Repository();
        $this->project_repository = new ERP_OMD_Project_Repository();
        $this->project_request_repository = new ERP_OMD_Project_Request_Repository();
        $this->estimate_repository = new ERP_OMD_Estimate_Repository();
        $this->estimate_item_repository = new ERP_OMD_Estimate_Item_Repository();
        $this->estimate_audit_repository = new ERP_OMD_Estimate_Audit_Repository();
        $this->project_note_repository = new ERP_OMD_Project_Note_Repository();
        $this->project_rate_repository = new ERP_OMD_Project_Rate_Repository();
        $this->project_cost_repository = new ERP_OMD_Project_Cost_Repository();
        $this->project_revenue_repository = new ERP_OMD_Project_Revenue_Repository();
        $this->project_financial_repository = new ERP_OMD_Project_Financial_Repository();
        $this->time_entry_repository = new ERP_OMD_Time_Entry_Repository();
        $this->attachment_repository = new ERP_OMD_Attachment_Repository();
        $this->monthly_hours_service = new ERP_OMD_Monthly_Hours_Service();
        $this->employee_service = new ERP_OMD_Employee_Service(
            $this->employee_repository,
            $this->salary_repository,
            $this->monthly_hours_service
        );
        $this->estimate_service = new ERP_OMD_Estimate_Service(
            $this->estimate_repository,
            $this->estimate_item_repository,
            $this->client_repository,
            $this->project_repository,
            $this->project_cost_repository,
            $this->estimate_audit_repository,
            $this->project_request_repository
        );
        $this->time_entry_service = new ERP_OMD_Time_Entry_Service(
            $this->time_entry_repository,
            $this->employee_repository,
            $this->project_repository,
            $this->role_repository,
            $this->client_rate_repository,
            $this->project_rate_repository,
            $this->salary_repository
        );
        $this->project_financial_service = new ERP_OMD_Project_Financial_Service(
            $this->project_repository,
            $this->project_cost_repository,
            $this->project_revenue_repository,
            $this->project_financial_repository,
            $this->time_entry_repository
        );
        $this->reporting_service = new ERP_OMD_Reporting_Service(
            $this->project_repository,
            $this->client_repository,
            $this->employee_repository,
            $this->salary_repository,
            $this->project_cost_repository,
            $this->time_entry_repository,
            $this->project_financial_service,
            $this->estimate_item_repository
        );
        $this->alert_service = new ERP_OMD_Alert_Service(
            $this->employee_repository,
            $this->client_repository,
            $this->client_rate_repository,
            $this->project_repository,
            $this->project_rate_repository,
            $this->project_financial_service,
            $this->time_entry_repository
        );
        $this->client_project_service = new ERP_OMD_Client_Project_Service(
            $this->client_repository,
            $this->employee_repository,
            $this->role_repository,
            $this->project_repository,
            $this->time_entry_repository,
            $this->alert_service
        );
        $this->project_request_service = new ERP_OMD_Project_Request_Service(
            $this->client_repository,
            $this->employee_repository,
            $this->estimate_repository,
            $this->project_repository,
            $this->client_project_service
        );
        $this->admin = new ERP_OMD_Admin(
            $this->role_repository,
            $this->employee_repository,
            $this->salary_repository,
            $this->employee_service,
            $this->monthly_hours_service,
            $this->client_repository,
            $this->client_rate_repository,
            $this->project_repository,
            $this->project_request_repository,
            $this->estimate_repository,
            $this->estimate_item_repository,
            $this->project_note_repository,
            $this->client_project_service,
            $this->project_request_service,
            $this->estimate_service,
            $this->project_rate_repository,
            $this->project_cost_repository,
            $this->project_revenue_repository,
            $this->project_financial_repository,
            $this->time_entry_repository,
            $this->attachment_repository,
            $this->time_entry_service,
            $this->project_financial_service,
            $this->reporting_service,
            $this->alert_service
        );
        $this->frontend = new ERP_OMD_Frontend(
            $this->employee_repository,
            $this->client_repository,
            $this->project_repository,
            $this->role_repository,
            $this->time_entry_repository,
            $this->project_request_repository,
            $this->estimate_repository,
            $this->estimate_item_repository,
            $this->project_cost_repository,
            $this->project_revenue_repository,
            $this->time_entry_service,
            $this->client_project_service,
            $this->project_request_service,
            $this->estimate_service,
            $this->project_financial_service,
            $this->reporting_service,
            $this->alert_service
        );
        $this->rest_api = new ERP_OMD_REST_API(
            $this->role_repository,
            $this->employee_repository,
            $this->salary_repository,
            $this->employee_service,
            $this->monthly_hours_service,
            $this->client_repository,
            $this->client_rate_repository,
            $this->project_repository,
            $this->estimate_repository,
            $this->estimate_item_repository,
            $this->project_note_repository,
            $this->client_project_service,
            $this->estimate_service,
            $this->project_rate_repository,
            $this->project_cost_repository,
            $this->project_financial_repository,
            $this->time_entry_repository,
            $this->attachment_repository,
            $this->time_entry_service,
            $this->project_financial_service,
            $this->reporting_service,
            $this->alert_service
        );
    }

    public function boot()
    {
        ERP_OMD_Installer::maybe_upgrade();
        ERP_OMD_Capabilities::register_hooks();
        $this->admin->register_hooks();
        $this->frontend->register_hooks();
        $this->rest_api->register_hooks();
        add_action('wp_login', [$this, 'track_user_login'], 10, 2);
        add_filter('wp_mail_from', [$this, 'filter_wp_mail_from']);
        ERP_OMD_Cron_Manager::register_hooks();
    }

    public function track_user_login($user_login, $user)
    {
        if (! $user instanceof WP_User) {
            return;
        }

        update_user_meta($user->ID, 'erp_omd_last_login_at', current_time('mysql'));
    }

    public function filter_wp_mail_from($from_email)
    {
        $configured_sender = sanitize_email((string) get_option('erp_omd_notification_sender_email', ''));
        if ($configured_sender !== '' && is_email($configured_sender)) {
            return $configured_sender;
        }

        return $from_email;
    }
}
