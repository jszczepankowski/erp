<?php

class ERP_OMD_Project_Cost_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_project_costs';
    }

    public function for_project($project_id)
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$this->table_name()}
                WHERE project_id = %d
                ORDER BY cost_date DESC, id DESC",
                $project_id
            ),
            ARRAY_A
        );
    }

    public function find($id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE id = %d", $id), ARRAY_A);
    }

    public function create(array $data)
    {
        global $wpdb;

        $now = current_time('mysql');
        $wpdb->insert(
            $this->table_name(),
            [
                'project_id' => $data['project_id'],
                'amount' => $data['amount'],
                'description' => $data['description'],
                'cost_date' => $data['cost_date'],
                'created_by_user_id' => $data['created_by_user_id'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%f', '%s', '%s', '%d', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public function update($id, array $data)
    {
        global $wpdb;

        return $wpdb->update(
            $this->table_name(),
            [
                'amount' => $data['amount'],
                'description' => $data['description'],
                'cost_date' => $data['cost_date'],
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%f', '%s', '%s', '%s'],
            ['%d']
        );
    }

    public function delete($id)
    {
        global $wpdb;

        return $wpdb->delete($this->table_name(), ['id' => $id], ['%d']);
    }
}
