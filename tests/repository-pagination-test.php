<?php

declare(strict_types=1);

if (! defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

final class FakeWpdb
{
    public $prefix = 'wp_';
    public $users = 'wp_users';

    /** @var array<int, array{sql:string,params:array<int,mixed>}> */
    public $prepared = [];

    public function esc_like($text)
    {
        return addcslashes((string) $text, '%_\\');
    }

    public function prepare($sql, ...$params)
    {
        $this->prepared[] = [
            'sql' => (string) $sql,
            'params' => $params,
        ];

        return (string) $sql;
    }

    public function get_results($query, $output = ARRAY_A)
    {
        return [];
    }

    public function get_var($query)
    {
        return '0';
    }
}

$GLOBALS['wpdb'] = new FakeWpdb();

require_once __DIR__ . '/../erp-omd/includes/repositories/class-estimate-repository-v2.php';
require_once __DIR__ . '/../erp-omd/includes/repositories/class-time-entry-repository.php';

final class RepositoryPaginationTestRunner
{
    private $assertions = 0;

    public function run(): void
    {
        $this->testEstimateRepositoryUsesConsistentMonthFilter();
        $this->testTimeEntryAllDelegatesToFindPagedWithLargeLimit();

        echo "Assertions: {$this->assertions}\n";
        echo "Repository pagination tests passed.\n";
    }

    private function testEstimateRepositoryUsesConsistentMonthFilter(): void
    {
        global $wpdb;

        $repo = new ERP_OMD_Estimate_Repository();

        $wpdb->prepared = [];
        $repo->find_paged(['month' => '2026-04', 'search' => 'ACME'], 25, 50);

        $this->assertSame(1, count($wpdb->prepared), 'find_paged should execute exactly one prepared query.');
        $findPrepared = $wpdb->prepared[0];
        $this->assertContains('e.created_at LIKE %s', $findPrepared['sql'], 'find_paged should apply month filter to created_at.');
        $this->assertContains('LIMIT %d OFFSET %d', $findPrepared['sql'], 'find_paged should include SQL LIMIT/OFFSET placeholders.');
        $this->assertSame('2026-04%', $findPrepared['params'][3] ?? null, 'find_paged month parameter should use YYYY-MM% pattern.');
        $this->assertSame(25, $findPrepared['params'][4] ?? null, 'find_paged should pass sanitized limit parameter.');
        $this->assertSame(50, $findPrepared['params'][5] ?? null, 'find_paged should pass sanitized offset parameter.');

        $wpdb->prepared = [];
        $repo->count_filtered(['month' => '2026-04', 'search' => 'ACME']);

        $this->assertSame(1, count($wpdb->prepared), 'count_filtered should execute prepared query when filters are provided.');
        $countPrepared = $wpdb->prepared[0];
        $this->assertContains('e.created_at LIKE %s', $countPrepared['sql'], 'count_filtered should apply same month filter as find_paged.');
        $this->assertSame('2026-04%', $countPrepared['params'][3] ?? null, 'count_filtered month parameter should use YYYY-MM% pattern.');
    }

    private function testTimeEntryAllDelegatesToFindPagedWithLargeLimit(): void
    {
        global $wpdb;

        $repo = new ERP_OMD_Time_Entry_Repository();

        $wpdb->prepared = [];
        $repo->all(['employee_id' => 7]);

        $this->assertSame(1, count($wpdb->prepared), 'all() should execute one paged query.');
        $prepared = $wpdb->prepared[0];
        $this->assertContains('LIMIT %d OFFSET %d', $prepared['sql'], 'all() via find_paged should still use SQL LIMIT/OFFSET placeholders.');

        $params = $prepared['params'];
        $this->assertSame(7, $params[0] ?? null, 'Employee filter should be forwarded by all().');
        $this->assertSame(1000000, $params[count($params) - 2] ?? null, 'all() should delegate with high compatibility limit.');
        $this->assertSame(0, $params[count($params) - 1] ?? null, 'all() should delegate with zero offset.');
    }

    private function assertSame($expected, $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new RuntimeException($message . ' Expected: ' . var_export($expected, true) . ' Actual: ' . var_export($actual, true));
        }
    }

    private function assertContains(string $needle, string $haystack, string $message): void
    {
        $this->assertions++;
        if (strpos($haystack, $needle) === false) {
            throw new RuntimeException($message . ' Missing: ' . $needle);
        }
    }
}

(new RepositoryPaginationTestRunner())->run();
