<?php

declare(strict_types=1);

if (! function_exists('apply_filters')) {
    function apply_filters($tag, $value, $payload = null)
    {
        if ($tag === 'erp_omd_google_calendar_sync_upsert_event') {
            return $value !== '' ? $value : 'remote-' . (string) ($payload['event_type'] ?? 'event') . '-' . (int) ($payload['project_id'] ?? 0);
        }

        return $value;
    }
}

if (! function_exists('do_action')) {
    function do_action($tag, $value = null)
    {
        $GLOBALS['erp_omd_test_actions'][] = [$tag, $value];
    }
}

if (! function_exists('get_option')) {
    function get_option($key, $default = '')
    {
        return $default;
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
        $this->assertTrue(! empty($GLOBALS['erp_omd_test_actions']), 'Delete action should be called for archived project events.');

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
