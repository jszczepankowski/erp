<?php

class ERP_OMD_Project_Merge_Service
{
    /** @var ERP_OMD_Project_Repository */
    private $projects;

    public function __construct(ERP_OMD_Project_Repository $projects)
    {
        $this->projects = $projects;
    }

    /**
     * @param int[] $source_project_ids
     * @return array{ok:bool,errors:string[],projects:array<int,array<string,mixed>>}
     */
    public function validate_source_projects(array $source_project_ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $source_project_ids), static function ($id) {
            return $id > 0;
        })));

        if (count($ids) < 2) {
            return ['ok' => false, 'errors' => [__('Wskaż co najmniej dwa projekty źródłowe do scalenia.', 'erp-omd')], 'projects' => []];
        }

        $errors = [];
        $projects = [];
        foreach ($ids as $id) {
            $project = $this->projects->find($id);
            if (! $project) {
                $errors[] = sprintf(__('Nie znaleziono projektu źródłowego #%d.', 'erp-omd'), $id);
                continue;
            }
            $status = (string) ($project['status'] ?? '');
            if (! in_array($status, ['w_realizacji', 'do_faktury'], true)) {
                $errors[] = sprintf(__('Projekt #%1$d ma status %2$s i nie może zostać scalony.', 'erp-omd'), $id, $status !== '' ? $status : '—');
            }
            $projects[] = $project;
        }

        return ['ok' => $errors === [], 'errors' => $errors, 'projects' => $projects];
    }

    /**
     * Projekt docelowy nigdy nie otrzymuje statusu "merged".
     *
     * @param array<int,array<string,mixed>> $source_projects
     * @param int $target_client_id
     * @param string $target_project_name
     * @return array{status:string,client_id:int,name:string,source_ids_to_mark_merged:int[]}
     */
    public function build_target_project_payload(array $source_projects, $target_client_id, $target_project_name)
    {
        $source_ids = array_values(array_map(static function ($project) {
            return (int) ($project['id'] ?? 0);
        }, $source_projects));

        return [
            'status' => 'do_rozpoczecia',
            'client_id' => max(0, (int) $target_client_id),
            'name' => trim((string) $target_project_name),
            'source_ids_to_mark_merged' => array_values(array_filter($source_ids, static function ($id) {
                return $id > 0;
            })),
        ];
    }

    /**
     * @param int[] $source_project_ids
     * @param int $target_client_id
     * @return array<string,mixed>
     */
    public function build_merge_preview(array $source_project_ids, $target_client_id)
    {
        global $wpdb;

        $ids = array_values(array_unique(array_filter(array_map('intval', $source_project_ids), static function ($id) {
            return $id > 0;
        })));
        if ($ids === []) {
            return [
                'time_entries_count' => 0,
                'time_entries_hours_sum' => 0.0,
                'project_costs_count' => 0,
                'project_costs_amount_sum' => 0.0,
                'project_revenues_count' => 0,
                'project_revenues_amount_sum' => 0.0,
                'project_budgets_count' => 0,
                'project_budgets_amount_sum' => 0.0,
                'source_projects' => [],
                'target_client_id' => max(0, (int) $target_client_id),
                'target_client_name' => '',
            ];
        }

        $in = implode(',', array_map('intval', $ids));
        $time_entries_table = $wpdb->prefix . 'erp_omd_time_entries';
        $project_costs_table = $wpdb->prefix . 'erp_omd_project_costs';
        $project_revenues_table = $wpdb->prefix . 'erp_omd_project_revenues';
        $projects_table = $wpdb->prefix . 'erp_omd_projects';
        $clients_table = $wpdb->prefix . 'erp_omd_clients';

        $time_stats = (array) $wpdb->get_row("SELECT COUNT(*) AS c, COALESCE(SUM(hours),0) AS s FROM {$time_entries_table} WHERE project_id IN ({$in})", ARRAY_A);
        $cost_stats = (array) $wpdb->get_row("SELECT COUNT(*) AS c, COALESCE(SUM(amount),0) AS s FROM {$project_costs_table} WHERE project_id IN ({$in})", ARRAY_A);
        $revenue_stats = (array) $wpdb->get_row("SELECT COUNT(*) AS c, COALESCE(SUM(amount),0) AS s FROM {$project_revenues_table} WHERE project_id IN ({$in})", ARRAY_A);
        $budget_stats = (array) $wpdb->get_row("SELECT COUNT(*) AS c, COALESCE(SUM(budget),0) AS s FROM {$projects_table} WHERE id IN ({$in})", ARRAY_A);

        $source_projects = (array) $wpdb->get_results("SELECT id, name, client_id, status, budget FROM {$projects_table} WHERE id IN ({$in}) ORDER BY id ASC", ARRAY_A);
        $target_client_name = '';
        if ((int) $target_client_id > 0) {
            $target_client_name = (string) $wpdb->get_var(
                $wpdb->prepare("SELECT name FROM {$clients_table} WHERE id = %d LIMIT 1", (int) $target_client_id)
            );
        }

        return [
            'time_entries_count' => (int) ($time_stats['c'] ?? 0),
            'time_entries_hours_sum' => (float) ($time_stats['s'] ?? 0),
            'project_costs_count' => (int) ($cost_stats['c'] ?? 0),
            'project_costs_amount_sum' => (float) ($cost_stats['s'] ?? 0),
            'project_revenues_count' => (int) ($revenue_stats['c'] ?? 0),
            'project_revenues_amount_sum' => (float) ($revenue_stats['s'] ?? 0),
            'project_budgets_count' => (int) ($budget_stats['c'] ?? 0),
            'project_budgets_amount_sum' => (float) ($budget_stats['s'] ?? 0),
            'source_projects' => $source_projects,
            'target_client_id' => max(0, (int) $target_client_id),
            'target_client_name' => $target_client_name,
        ];
    }

    /**
     * @param int[] $source_project_ids
     * @param int $target_client_id
     * @param string $target_project_name
     * @param bool $confirmed
     * @param bool $delete_sources_permanently
     * @return array{ok:bool,errors:string[],target_project_id:int,preview:array<string,mixed>,merge_report:array<string,mixed>,audit_log_id:string}
     */
    public function execute_merge(array $source_project_ids, $target_client_id, $target_project_name, $confirmed, $delete_sources_permanently = false)
    {
        global $wpdb;

        if (! $confirmed) {
            return ['ok' => false, 'errors' => [__('Scalenie wymaga potwierdzenia użytkownika.', 'erp-omd')], 'target_project_id' => 0, 'preview' => [], 'merge_report' => [], 'audit_log_id' => ''];
        }

        $validation = $this->validate_source_projects($source_project_ids);
        if (! $validation['ok']) {
            return ['ok' => false, 'errors' => (array) ($validation['errors'] ?? []), 'target_project_id' => 0, 'preview' => [], 'merge_report' => [], 'audit_log_id' => ''];
        }

        $source_projects = (array) ($validation['projects'] ?? []);
        if ($source_projects === []) {
            return ['ok' => false, 'errors' => [__('Brak projektów źródłowych do scalenia.', 'erp-omd')], 'target_project_id' => 0, 'preview' => [], 'merge_report' => [], 'audit_log_id' => ''];
        }

        $preview = $this->build_merge_preview($source_project_ids, $target_client_id);
        $base = (array) $source_projects[0];
        $target_payload = $this->build_target_project_payload($source_projects, $target_client_id, $target_project_name);

        $create_payload = [
            'client_id' => (int) $target_payload['client_id'],
            'name' => (string) $target_payload['name'],
            'billing_type' => (string) ($base['billing_type'] ?? 'time_material'),
            'budget' => (float) ($preview['project_budgets_amount_sum'] ?? 0),
            'retainer_monthly_fee' => 0,
            'status' => (string) $target_payload['status'],
            'start_date' => '',
            'end_date' => '',
            'deadline_date' => '',
            'deadline_completed_at' => '',
            'deadline_completed_by' => 0,
            'manager_id' => 0,
            'manager_ids' => [],
            'estimate_id' => 0,
            'brief' => __('Projekt utworzony automatycznie przez scalenie.', 'erp-omd'),
            'alert_margin_threshold' => null,
        ];

        $source_ids = (array) ($target_payload['source_ids_to_mark_merged'] ?? []);
        $in = implode(',', array_map('intval', $source_ids));
        if ($in === '') {
            return ['ok' => false, 'errors' => [__('Brak poprawnych ID projektów źródłowych.', 'erp-omd')], 'target_project_id' => 0, 'preview' => [], 'merge_report' => [], 'audit_log_id' => ''];
        }

        $time_entries_table = $wpdb->prefix . 'erp_omd_time_entries';
        $project_costs_table = $wpdb->prefix . 'erp_omd_project_costs';
        $project_revenues_table = $wpdb->prefix . 'erp_omd_project_revenues';
        $cost_invoices_table = $wpdb->prefix . 'erp_omd_cost_invoices';

        $wpdb->query('START TRANSACTION');
        try {
            $target_project_id = (int) $this->projects->create($create_payload);
            if ($target_project_id <= 0) {
                throw new RuntimeException(__('Nie udało się utworzyć projektu docelowego.', 'erp-omd'));
            }

            $wpdb->query("UPDATE {$time_entries_table} SET project_id = {$target_project_id} WHERE project_id IN ({$in})");
            $wpdb->query("UPDATE {$project_costs_table} SET project_id = {$target_project_id} WHERE project_id IN ({$in})");
            $wpdb->query("UPDATE {$project_revenues_table} SET project_id = {$target_project_id} WHERE project_id IN ({$in})");
            $wpdb->query("UPDATE {$cost_invoices_table} SET project_id = {$target_project_id} WHERE project_id IN ({$in})");

            foreach ($source_ids as $source_id) {
                $source_id = (int) $source_id;
                $this->projects->set_status($source_id, 'merged');
                if ($delete_sources_permanently) {
                    $this->projects->delete($source_id);
                }
            }

            $wpdb->query('COMMIT');
            $merge_report = $this->build_merge_report($preview, $target_project_id, $delete_sources_permanently);
            $audit_log_id = $this->append_merge_audit_log($merge_report);
            return ['ok' => true, 'errors' => [], 'target_project_id' => $target_project_id, 'preview' => $preview, 'merge_report' => $merge_report, 'audit_log_id' => $audit_log_id];
        } catch (Throwable $e) {
            $wpdb->query('ROLLBACK');
            return ['ok' => false, 'errors' => [$e->getMessage()], 'target_project_id' => 0, 'preview' => $preview, 'merge_report' => [], 'audit_log_id' => ''];
        }
    }

    private function build_merge_report(array $preview, $target_project_id, $delete_sources_permanently)
    {
        return [
            'executed_at' => current_time('mysql'),
            'target_project_id' => (int) $target_project_id,
            'target_client_id' => (int) ($preview['target_client_id'] ?? 0),
            'target_client_name' => (string) ($preview['target_client_name'] ?? ''),
            'source_projects' => (array) ($preview['source_projects'] ?? []),
            'time_entries_count' => (int) ($preview['time_entries_count'] ?? 0),
            'time_entries_hours_sum' => (float) ($preview['time_entries_hours_sum'] ?? 0),
            'project_costs_count' => (int) ($preview['project_costs_count'] ?? 0),
            'project_costs_amount_sum' => (float) ($preview['project_costs_amount_sum'] ?? 0),
            'project_revenues_count' => (int) ($preview['project_revenues_count'] ?? 0),
            'project_revenues_amount_sum' => (float) ($preview['project_revenues_amount_sum'] ?? 0),
            'project_budgets_count' => (int) ($preview['project_budgets_count'] ?? 0),
            'project_budgets_amount_sum' => (float) ($preview['project_budgets_amount_sum'] ?? 0),
            'delete_sources_permanently' => (bool) $delete_sources_permanently,
        ];
    }

    private function append_merge_audit_log(array $merge_report)
    {
        $log_id = 'merge_' . wp_generate_uuid4();
        $item = $merge_report;
        $item['id'] = $log_id;
        $item['user_id'] = (int) get_current_user_id();

        $option_key = 'erp_omd_project_merge_audit_log';
        $entries = get_option($option_key, []);
        if (! is_array($entries)) {
            $entries = [];
        }
        $entries[] = $item;
        if (count($entries) > 100) {
            $entries = array_slice($entries, -100);
        }
        update_option($option_key, $entries, false);

        return $log_id;
    }
}
