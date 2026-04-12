<?php

class ERP_OMD_Google_Calendar_Sync_Service
{
    private $projects;
    private $calendar_sync_repository;

    public function __construct(
        ERP_OMD_Project_Repository $projects,
        ERP_OMD_Project_Calendar_Sync_Repository $calendar_sync_repository
    ) {
        $this->projects = $projects;
        $this->calendar_sync_repository = $calendar_sync_repository;
    }

    public function sync_all_projects()
    {
        $projects = $this->projects->all();

        foreach ($projects as $project) {
            $status = (string) ($project['status'] ?? '');
            if ($status === 'archiwum') {
                $this->delete_project_events((int) ($project['id'] ?? 0));
                continue;
            }

            $this->sync_project_events($project);
        }
    }

    public function sync_project_events(array $project)
    {
        $project_id = (int) ($project['id'] ?? 0);
        if ($project_id <= 0) {
            return;
        }

        try {
            $existing = $this->calendar_sync_repository->find_by_project_id($project_id) ?: [];
            $range_event_id = (string) ($existing['range_event_id'] ?? '');
            $deadline_event_id = (string) ($existing['deadline_event_id'] ?? '');

            if ((string) ($project['start_date'] ?? '') !== '' && (string) ($project['end_date'] ?? '') !== '') {
                $range_event_id = $this->upsert_remote_event($range_event_id, 'range', $project);
            }
            if ((string) ($project['deadline_date'] ?? '') !== '') {
                $deadline_event_id = $this->upsert_remote_event($deadline_event_id, 'deadline', $project);
            }

            $this->calendar_sync_repository->upsert([
                'project_id' => $project_id,
                'range_event_id' => $range_event_id,
                'deadline_event_id' => $deadline_event_id,
                'sync_status' => 'synced',
                'last_error' => '',
                'last_synced_at' => current_time('mysql'),
            ]);
        } catch (Throwable $exception) {
            $this->calendar_sync_repository->upsert([
                'project_id' => $project_id,
                'range_event_id' => '',
                'deadline_event_id' => '',
                'sync_status' => 'error',
                'last_error' => $exception->getMessage(),
                'last_synced_at' => '',
            ]);
            $this->notify_admin_about_sync_error($project_id, $exception->getMessage());
        }
    }

    public function delete_project_events($project_id)
    {
        $project_id = (int) $project_id;
        if ($project_id <= 0) {
            return;
        }

        $existing = $this->calendar_sync_repository->find_by_project_id($project_id);
        if (! $existing) {
            return;
        }

        try {
            $this->delete_remote_event((string) ($existing['range_event_id'] ?? ''));
            $this->delete_remote_event((string) ($existing['deadline_event_id'] ?? ''));
            $this->calendar_sync_repository->delete_by_project_id($project_id);
        } catch (Throwable $exception) {
            $this->calendar_sync_repository->upsert([
                'project_id' => $project_id,
                'range_event_id' => (string) ($existing['range_event_id'] ?? ''),
                'deadline_event_id' => (string) ($existing['deadline_event_id'] ?? ''),
                'sync_status' => 'error',
                'last_error' => $exception->getMessage(),
                'last_synced_at' => '',
            ]);
            $this->notify_admin_about_sync_error($project_id, $exception->getMessage());
        }
    }

    private function upsert_remote_event($existing_event_id, $event_type, array $project)
    {
        $project_id = (int) ($project['id'] ?? 0);
        $event_payload = [
            'project_id' => $project_id,
            'event_type' => $event_type,
            'title' => sprintf('ERP OMD: %s', (string) ($project['name'] ?? ('#' . $project_id))),
            'start_date' => (string) ($event_type === 'range' ? ($project['start_date'] ?? '') : ($project['deadline_date'] ?? '')),
            'end_date' => (string) ($event_type === 'range' ? ($project['end_date'] ?? '') : ($project['deadline_date'] ?? '')),
        ];

        // TODO(EPIC D): replace filter-based adapter with native Google Calendar API client + OAuth token refresh.
        $remote_event_id = apply_filters('erp_omd_google_calendar_sync_upsert_event', $existing_event_id, $event_payload);
        if (! is_string($remote_event_id) || $remote_event_id === '') {
            return sprintf('todo-%s-%d', $event_type, $project_id);
        }

        return $remote_event_id;
    }

    private function delete_remote_event($event_id)
    {
        if ($event_id === '') {
            return;
        }

        // TODO(EPIC D): wire remove call to Google Calendar API transport layer.
        do_action('erp_omd_google_calendar_sync_delete_event', $event_id);
    }

    private function notify_admin_about_sync_error($project_id, $error_message)
    {
        $last_notified_at = (string) get_option('erp_omd_calendar_sync_last_error_notified_at', '');
        if ($last_notified_at !== '' && strtotime($last_notified_at) > time() - HOUR_IN_SECONDS) {
            return;
        }

        $admin_users = get_users(['role' => 'administrator', 'fields' => ['user_email']]);
        $recipients = [];
        foreach ($admin_users as $admin_user) {
            $email = sanitize_email((string) ($admin_user->user_email ?? ''));
            if (is_email($email)) {
                $recipients[] = $email;
            }
        }

        $recipients = array_values(array_unique($recipients));
        if ($recipients === []) {
            return;
        }

        $subject = sprintf('[ERP OMD] Google Calendar sync error (project #%d)', (int) $project_id);
        $body = sprintf(
            'Wystąpił błąd synchronizacji Google Calendar dla projektu #%1$d. Szczegóły: %2$s',
            (int) $project_id,
            (string) $error_message
        );

        foreach ($recipients as $recipient) {
            wp_mail($recipient, $subject, $body);
        }

        update_option('erp_omd_calendar_sync_last_error_notified_at', current_time('mysql'));
    }
}
