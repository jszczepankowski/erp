<?php

class ERP_OMD_Plugin
{
    private $container;

    public function __construct($container = null)
    {
        $this->container = $container instanceof ERP_OMD_Container ? $container : new ERP_OMD_Container();
    }

    public function boot()
    {
        ERP_OMD_Installer::maybe_upgrade();
        ERP_OMD_Capabilities::register_hooks();
        $this->container->admin()->register_hooks();
        $this->container->frontend()->register_hooks();
        $this->container->rest_api()->register_hooks();
        ERP_OMD_Cron_Manager::register_hooks();
        add_action('erp_omd_project_saved', [$this, 'sync_project_calendar_after_save'], 10, 2);
        add_action('erp_omd_project_deleted', [$this, 'sync_project_calendar_after_delete']);
        add_action('wp_login', [$this, 'track_user_login'], 10, 2);
        add_filter('wp_mail_from', [$this, 'filter_wp_mail_from']);
    }

    public function sync_project_calendar_after_save($project, $operation = 'update')
    {
        if (! is_array($project) || (int) ($project['id'] ?? 0) <= 0) {
            return;
        }

        if ((string) ($project['status'] ?? '') === 'archiwum') {
            $this->container->google_calendar_sync_service()->delete_project_events((int) $project['id']);
            return;
        }

        $this->container->google_calendar_sync_service()->sync_project_events($project);
    }

    public function sync_project_calendar_after_delete($project)
    {
        if (! is_array($project)) {
            return;
        }

        $this->container->google_calendar_sync_service()->delete_project_events((int) ($project['id'] ?? 0));
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
