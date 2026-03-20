<?php

class ERP_OMD_Project_Rate_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_project_rates';
    }

    public function for_project($project_id)
    {
        global $wpdb;

        $roles_table = $wpdb->prefix . 'erp_omd_roles';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT pr.*, r.name AS role_name
                FROM {$this->table_name()} pr
                INNER JOIN {$roles_table} r ON r.id = pr.role_id
                WHERE pr.project_id = %d
                ORDER BY r.name ASC",
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

    public function find_by_project_role($project_id, $role_id)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name()} WHERE project_id = %d AND role_id = %d",
                $project_id,
                $role_id
            ),
            ARRAY_A
        );
    }

    public function upsert($project_id, $role_id, $rate)
    {
        global $wpdb;

        $existing = $this->find_by_project_role($project_id, $role_id);
        if ($existing) {
            $wpdb->update(
                $this->table_name(),
                ['rate' => $rate, 'updated_at' => current_time('mysql')],
                ['id' => $existing['id']],
                ['%f', '%s'],
                ['%d']
            );

            return (int) $existing['id'];
        }

        $wpdb->insert(
            $this->table_name(),
            [
                'project_id' => $project_id,
                'role_id' => $role_id,
                'rate' => $rate,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%f', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public function delete($id)
    {
        global $wpdb;

        return $wpdb->delete($this->table_name(), ['id' => $id], ['%d']);
    }
}
