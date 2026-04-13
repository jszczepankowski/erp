<?php

class ERP_OMD_Google_Calendar_Sync_Service
{
    const OPTION_CLIENT_ID = 'erp_omd_google_calendar_client_id';
    const OPTION_CLIENT_SECRET_ENC = 'erp_omd_google_calendar_client_secret_enc';
    const OPTION_SCOPE = 'erp_omd_google_calendar_scope';
    const OPTION_CALENDAR_ID = 'erp_omd_google_calendar_calendar_id';
    const OPTION_TECHNICAL_EMAIL = 'erp_omd_google_calendar_technical_account_email';
    const OPTION_ACCESS_TOKEN_ENC = 'erp_omd_google_calendar_access_token_enc';
    const OPTION_REFRESH_TOKEN_ENC = 'erp_omd_google_calendar_refresh_token_enc';
    const OPTION_EXPIRES_AT = 'erp_omd_google_calendar_access_token_expires_at';
    const OPTION_LAST_SYNC_AT = 'erp_omd_google_calendar_last_sync_at';
    const OPTION_LAST_ERROR = 'erp_omd_google_calendar_last_error';

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
            update_option(self::OPTION_LAST_ERROR, $exception->getMessage());
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
            update_option(self::OPTION_LAST_ERROR, $exception->getMessage());
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
        $event_payload = $this->build_event_payload($event_type, $project);
        $event_payload['project_id'] = $project_id;
        $event_payload['event_type'] = $event_type;

        if ($this->is_google_api_enabled()) {
            return $this->upsert_google_event($existing_event_id, $event_payload);
        }

        $legacy_payload = [
            'project_id' => $project_id,
            'event_type' => $event_type,
            'title' => sprintf('ERP OMD: %s', (string) ($project['name'] ?? ('#' . $project_id))),
            'start_date' => (string) ($event_type === 'range' ? ($project['start_date'] ?? '') : ($project['deadline_date'] ?? '')),
            'end_date' => (string) ($event_type === 'range' ? ($project['end_date'] ?? '') : ($project['deadline_date'] ?? '')),
        ];

        // TODO(EPIC D): replace filter-based adapter with native Google Calendar API client + OAuth token refresh.
        $remote_event_id = apply_filters('erp_omd_google_calendar_sync_upsert_event', $existing_event_id, $legacy_payload);
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

        if ($this->is_google_api_enabled()) {
            $this->delete_google_event($event_id);
            return;
        }

        // TODO(EPIC D): wire remove call to Google Calendar API transport layer.
        do_action('erp_omd_google_calendar_sync_delete_event', $event_id);
    }

    private function build_event_payload($event_type, array $project)
    {
        $project_name = (string) ($project['name'] ?? ('#' . (int) ($project['id'] ?? 0)));
        $title = sprintf('ERP OMD: %s (%s)', $project_name, $event_type === 'range' ? __('Zakres projektu', 'erp-omd') : __('Deadline projektu', 'erp-omd'));
        $description = sprintf(
            "Projekt: %s\nID projektu: %d\nTyp eventu: %s",
            $project_name,
            (int) ($project['id'] ?? 0),
            $event_type
        );

        if ($event_type === 'range') {
            $start_date = (string) ($project['start_date'] ?? '');
            $end_date = (string) ($project['end_date'] ?? '');
            if ($start_date === '' || $end_date === '') {
                throw new RuntimeException(__('Brak dat start/end dla eventu zakresu projektu.', 'erp-omd'));
            }

            $end_date_exclusive = DateTimeImmutable::createFromFormat('Y-m-d', $end_date);
            if ($end_date_exclusive instanceof DateTimeImmutable) {
                $end_date = $end_date_exclusive->modify('+1 day')->format('Y-m-d');
            }

            return [
                'summary' => $title,
                'description' => $description,
                'start' => ['date' => $start_date],
                'end' => ['date' => $end_date],
            ];
        }

        $deadline_date = (string) ($project['deadline_date'] ?? '');
        if ($deadline_date === '') {
            throw new RuntimeException(__('Brak daty deadline dla eventu deadline projektu.', 'erp-omd'));
        }
        $deadline_date_exclusive = DateTimeImmutable::createFromFormat('Y-m-d', $deadline_date);
        $deadline_end = $deadline_date_exclusive instanceof DateTimeImmutable ? $deadline_date_exclusive->modify('+1 day')->format('Y-m-d') : $deadline_date;

        return [
            'summary' => $title,
            'description' => $description,
            'start' => ['date' => $deadline_date],
            'end' => ['date' => $deadline_end],
        ];
    }

    private function is_google_api_enabled()
    {
        $client_id = trim((string) get_option(self::OPTION_CLIENT_ID, ''));
        $calendar_id = trim((string) get_option(self::OPTION_CALENDAR_ID, ''));
        $refresh_token = trim((string) $this->decrypt_option(self::OPTION_REFRESH_TOKEN_ENC));

        return $client_id !== '' && $calendar_id !== '' && $refresh_token !== '';
    }

    private function upsert_google_event($existing_event_id, array $event_payload)
    {
        $token = $this->ensure_access_token();
        if ($token === '') {
            throw new RuntimeException(__('Brak aktywnego tokenu dostępu Google Calendar.', 'erp-omd'));
        }

        $calendar_id = rawurlencode((string) get_option(self::OPTION_CALENDAR_ID, 'primary'));
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json; charset=utf-8',
        ];
        $body = wp_json_encode($event_payload);

        if ((string) $existing_event_id !== '' && strpos((string) $existing_event_id, 'todo-') !== 0) {
            $response = wp_remote_request(
                sprintf('https://www.googleapis.com/calendar/v3/calendars/%s/events/%s', $calendar_id, rawurlencode((string) $existing_event_id)),
                [
                    'method' => 'PATCH',
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 20,
                ]
            );
        } else {
            $response = wp_remote_post(
                sprintf('https://www.googleapis.com/calendar/v3/calendars/%s/events', $calendar_id),
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 20,
                ]
            );
        }

        return $this->extract_google_event_id($response);
    }

    private function delete_google_event($event_id)
    {
        $token = $this->ensure_access_token();
        if ($token === '') {
            throw new RuntimeException(__('Brak aktywnego tokenu dostępu Google Calendar dla usuwania eventu.', 'erp-omd'));
        }

        $calendar_id = rawurlencode((string) get_option(self::OPTION_CALENDAR_ID, 'primary'));
        $response = wp_remote_request(
            sprintf('https://www.googleapis.com/calendar/v3/calendars/%s/events/%s', $calendar_id, rawurlencode((string) $event_id)),
            [
                'method' => 'DELETE',
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'timeout' => 20,
            ]
        );

        if (is_wp_error($response)) {
            throw new RuntimeException($response->get_error_message());
        }
    }

    private function ensure_access_token()
    {
        $access_token = trim((string) $this->decrypt_option(self::OPTION_ACCESS_TOKEN_ENC));
        $expires_at = (int) get_option(self::OPTION_EXPIRES_AT, 0);
        if ($access_token !== '' && $expires_at > (time() + 60)) {
            return $access_token;
        }

        $client_id = trim((string) get_option(self::OPTION_CLIENT_ID, ''));
        $client_secret = trim((string) $this->decrypt_option(self::OPTION_CLIENT_SECRET_ENC));
        $refresh_token = trim((string) $this->decrypt_option(self::OPTION_REFRESH_TOKEN_ENC));
        if ($client_id === '' || $client_secret === '' || $refresh_token === '') {
            return '';
        }

        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'timeout' => 20,
            'body' => [
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $refresh_token,
                'grant_type' => 'refresh_token',
            ],
        ]);
        if (is_wp_error($response)) {
            throw new RuntimeException($response->get_error_message());
        }

        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        $new_token = (string) ($payload['access_token'] ?? '');
        $expires_in = max(60, (int) ($payload['expires_in'] ?? 3600));
        if ($new_token === '') {
            throw new RuntimeException(__('Google OAuth refresh token nie zwrócił access_token.', 'erp-omd'));
        }

        update_option(self::OPTION_ACCESS_TOKEN_ENC, $this->encrypt_value($new_token));
        update_option(self::OPTION_EXPIRES_AT, time() + $expires_in);

        return $new_token;
    }

    private function extract_google_event_id($response)
    {
        if (is_wp_error($response)) {
            throw new RuntimeException($response->get_error_message());
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($status >= 400) {
            $message = (string) ($payload['error']['message'] ?? __('Błąd Google Calendar API.', 'erp-omd'));
            throw new RuntimeException($message);
        }

        $event_id = (string) ($payload['id'] ?? '');
        if ($event_id === '') {
            throw new RuntimeException(__('Google Calendar API nie zwrócił identyfikatora eventu.', 'erp-omd'));
        }

        update_option(self::OPTION_LAST_SYNC_AT, current_time('mysql'));
        update_option(self::OPTION_LAST_ERROR, '');

        return $event_id;
    }

    private function encrypt_value($raw_value)
    {
        $raw_value = (string) $raw_value;
        if ($raw_value === '' || ! function_exists('openssl_encrypt')) {
            return $raw_value;
        }

        $key = hash('sha256', (string) wp_salt('auth'), true);
        $iv = substr(hash('sha256', (string) wp_salt('secure_auth')), 0, 16);
        $encrypted = openssl_encrypt($raw_value, 'AES-256-CBC', $key, 0, $iv);

        return is_string($encrypted) ? $encrypted : $raw_value;
    }

    private function decrypt_option($option_name)
    {
        $encrypted = (string) get_option($option_name, '');
        if ($encrypted === '' || ! function_exists('openssl_decrypt')) {
            return $encrypted;
        }

        $key = hash('sha256', (string) wp_salt('auth'), true);
        $iv = substr(hash('sha256', (string) wp_salt('secure_auth')), 0, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);

        return is_string($decrypted) ? $decrypted : $encrypted;
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
