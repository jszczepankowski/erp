<?php

class ERP_OMD_Project_Financial_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_project_financials';
    }

    public function find_by_project($project_id)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE project_id = %d", $project_id),
            ARRAY_A
        );
    }

    public function upsert($project_id, array $data)
    {
        global $wpdb;

        $existing = $this->find_by_project($project_id);
        $payload = [
            'project_id' => $project_id,
            'revenue' => $data['revenue'],
            'cost' => $data['cost'],
            'profit' => $data['profit'],
            'margin' => $data['margin'],
            'budget_usage' => $data['budget_usage'],
            'time_revenue' => $data['time_revenue'],
            'time_cost' => $data['time_cost'],
            'direct_cost' => $data['direct_cost'],
            'last_recalculated_at' => $data['last_recalculated_at'],
            'updated_at' => current_time('mysql'),
        ];

        if ($existing) {
            $wpdb->update(
                $this->table_name(),
                $payload,
                ['project_id' => $project_id],
                ['%d', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%s'],
                ['%d']
            );

            if (isset($existing['id'])) {
                return (int) $existing['id'];
            }

            $refetched = $this->find_by_project($project_id);

            return (int) ($refetched['id'] ?? 0);
        }

        $payload['created_at'] = current_time('mysql');
        $wpdb->insert(
            $this->table_name(),
            $payload,
            ['%d', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }
}
