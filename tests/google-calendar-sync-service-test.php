<?php

declare(strict_types=1);

if (! function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

if (! class_exists('WP_Error')) {
    class WP_Error
    {
        private $message;

        public function __construct($code = '', $message = '')
        {
            $this->message = (string) $message;
        }

        public function get_error_message()
        {
            return $this->message;
        }
    }
}

if (! function_exists('get_option')) {
    function get_option($key, $default = '')
    {
        return $GLOBALS['erp_omd_options'][$key] ?? $default;
    }
}

if (! function_exists('get_users')) {
    function get_users($args = [])
    {
        return [];
    }
}

if (! function_exists('sanitize_email')) {
    function sanitize_email($value)
    {
        return (string) $value;
    }
}

if (! function_exists('is_email')) {
    function is_email($value)
    {
        return strpos((string) $value, '@') !== false;
    }
}

if (! function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message)
    {
        return true;
    }
}

if (! function_exists('update_option')) {
    function update_option($key, $value)
    {
        $GLOBALS['erp_omd_options'][$key] = $value;
        return true;
    }
}

if (! function_exists('current_time')) {
    function current_time($format)
    {
        return $format === 'mysql' ? '2026-04-13 10:00:00' : '2026-04-13';
    }
}

if (! function_exists('wp_salt')) {
    function wp_salt($scheme = 'auth')
    {
        return 'test-salt-' . (string) $scheme;
    }
}

if (! function_exists('is_wp_error')) {
    function is_wp_error($value)
    {
        return $value instanceof WP_Error;
    }
}

if (! function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response)
    {
        return (string) ($response['body'] ?? '');
    }
}

if (! function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response)
    {
        return (int) ($response['response']['code'] ?? 0);
    }
}

if (! function_exists('wp_remote_post')) {
    function wp_remote_post($url, $args = [])
    {
        if (strpos((string) $url, 'oauth2.googleapis.com/token') !== false) {
            return [
                'response' => ['code' => 200],
                'body' => wp_json_encode(['access_token' => 'token-xyz', 'expires_in' => 3600]),
            ];
        }

        if (strpos((string) $url, '/events') !== false) {
            $GLOBALS['erp_omd_test_actions'][] = ['upsert', $url];
            return [
                'response' => ['code' => 200],
                'body' => wp_json_encode(['id' => 'remote-created']),
            ];
        }

        return new WP_Error('http_error', 'Unexpected POST URL in test.');
    }
}

if (! function_exists('wp_remote_request')) {
    function wp_remote_request($url, $args = [])
    {
        $method = strtoupper((string) ($args['method'] ?? 'GET'));
        if ($method === 'PATCH') {
            $GLOBALS['erp_omd_test_actions'][] = ['patch', $url];
            return [
                'response' => ['code' => 200],
                'body' => wp_json_encode(['id' => 'remote-patched']),
            ];
        }
        if ($method === 'DELETE') {
            $GLOBALS['erp_omd_test_actions'][] = ['delete', $url];
            return [
                'response' => ['code' => 204],
                'body' => '',
            ];
        }

        return new WP_Error('http_error', 'Unexpected request method in test.');
    }
}

if (! function_exists('wp_json_encode')) {
    function wp_json_encode($value)
    {
        return json_encode($value);
    }
}

if (! class_exists('ERP_OMD_Project_Repository')) {
    class ERP_OMD_Project_Repository
    {
        private $projects;

        public function __construct(array $projects = [])
        {
            $this->projects = $projects;
        }

        public function all()
        {
            return $this->projects;
        }
    }
}

if (! class_exists('ERP_OMD_Project_Calendar_Sync_Repository')) {
    class ERP_OMD_Project_Calendar_Sync_Repository
    {
        public $rows = [];

        public function find_by_project_id($project_id)
        {
            return $this->rows[(int) $project_id] ?? null;
        }

        public function upsert(array $data)
        {
            $this->rows[(int) $data['project_id']] = $data;
            return (int) $data['project_id'];
        }

        public function delete_by_project_id($project_id)
        {
            unset($this->rows[(int) $project_id]);
            return true;
        }
    }
}

require_once __DIR__ . '/../erp-omd/includes/services/class-google-calendar-sync-service.php';

final class GoogleCalendarSyncServiceTestRunner
{
    private $assertions = 0;

    public function run(): void
    {
        $GLOBALS['erp_omd_options'][ERP_OMD_Google_Calendar_Sync_Service::OPTION_CLIENT_ID] = 'client-id';
        $GLOBALS['erp_omd_options'][ERP_OMD_Google_Calendar_Sync_Service::OPTION_CLIENT_SECRET_ENC] = 'client-secret';
        $GLOBALS['erp_omd_options'][ERP_OMD_Google_Calendar_Sync_Service::OPTION_REFRESH_TOKEN_ENC] = 'refresh-token';
        $GLOBALS['erp_omd_options'][ERP_OMD_Google_Calendar_Sync_Service::OPTION_CALENDAR_ID] = 'primary';
        $GLOBALS['erp_omd_options'][ERP_OMD_Google_Calendar_Sync_Service::OPTION_ACCESS_TOKEN_ENC] = '';
        $GLOBALS['erp_omd_options'][ERP_OMD_Google_Calendar_Sync_Service::OPTION_EXPIRES_AT] = 0;

        $projectRepository = new ERP_OMD_Project_Repository([
            ['id' => 10, 'name' => 'Projekt A', 'status' => 'w_realizacji', 'start_date' => '2026-04-01', 'end_date' => '2026-04-15', 'deadline_date' => '2026-04-16'],
            ['id' => 11, 'name' => 'Projekt B', 'status' => 'archiwum', 'start_date' => '2026-04-01', 'end_date' => '2026-04-05', 'deadline_date' => '2026-04-05'],
        ]);
        $syncRepository = new ERP_OMD_Project_Calendar_Sync_Repository();
        $syncRepository->rows[11] = ['project_id' => 11, 'range_event_id' => 'old-range', 'deadline_event_id' => 'old-deadline'];

        $service = new ERP_OMD_Google_Calendar_Sync_Service($projectRepository, $syncRepository);
        $service->sync_all_projects();

        $this->assertSame('synced', $syncRepository->rows[10]['sync_status'] ?? '', 'Active project should be synced.');
        $this->assertTrue(! isset($syncRepository->rows[11]), 'Archived project should have calendar mapping deleted.');
        $this->assertTrue(! empty($GLOBALS['erp_omd_test_actions']), 'Remote requests should be executed.');

        echo "Assertions: {$this->assertions}\n";
        echo "Google calendar sync service tests passed.\n";
    }

    private function assertSame($expected, $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new RuntimeException($message . ' Expected: ' . var_export($expected, true) . ' Actual: ' . var_export($actual, true));
        }
    }

    private function assertTrue($condition, string $message): void
    {
        $this->assertions++;
        if (! $condition) {
            throw new RuntimeException($message);
        }
    }
}

(new GoogleCalendarSyncServiceTestRunner())->run();
