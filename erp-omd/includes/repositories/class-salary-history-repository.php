<?php

class ERP_OMD_Salary_History_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_salary_history';
    }

    public function for_employee($employee_id)
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE employee_id = %d ORDER BY valid_from DESC, id DESC", $employee_id),
            ARRAY_A
        );
    }

    public function for_employees(array $employee_ids)
    {
        global $wpdb;

        $employee_ids = array_values(array_unique(array_map('intval', $employee_ids)));
        $employee_ids = array_values(array_filter($employee_ids, static function ($employee_id) {
            return $employee_id > 0;
        }));

        if ($employee_ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($employee_ids), '%d'));
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name()} WHERE employee_id IN ($placeholders) ORDER BY employee_id ASC, valid_from DESC, id DESC",
            ...$employee_ids
        );

        return $wpdb->get_results($query, ARRAY_A);
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
                'employee_id' => $data['employee_id'],
                'monthly_salary' => $data['monthly_salary'],
                'monthly_hours' => $data['monthly_hours'],
                'hourly_cost' => $data['hourly_cost'],
                'valid_from' => $data['valid_from'],
                'valid_to' => $data['valid_to'] ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%f', '%f', '%f', '%s', '%s', '%s', '%s']
        );

        if (function_exists('erp_omd_reports_cache_bump_version')) {
            erp_omd_reports_cache_bump_version();
        }

        return (int) $wpdb->insert_id;
    }

    public function update($id, array $data)
    {
        global $wpdb;

        $updated = $wpdb->update(
            $this->table_name(),
            [
                'monthly_salary' => $data['monthly_salary'],
                'monthly_hours' => $data['monthly_hours'],
                'hourly_cost' => $data['hourly_cost'],
                'valid_from' => $data['valid_from'],
                'valid_to' => $data['valid_to'] ?: null,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%f', '%f', '%f', '%s', '%s', '%s'],
            ['%d']
        );

        if ($updated !== false && function_exists('erp_omd_reports_cache_bump_version')) {
            erp_omd_reports_cache_bump_version();
        }

        return $updated;
    }

    public function delete($id)
    {
        global $wpdb;

        $deleted = $wpdb->delete($this->table_name(), ['id' => $id], ['%d']);
        if ($deleted !== false && function_exists('erp_omd_reports_cache_bump_version')) {
            erp_omd_reports_cache_bump_version();
        }

        return $deleted;
    }

    public function overlaps($employee_id, $valid_from, $valid_to, $exclude_id = null)
    {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$this->table_name()} WHERE employee_id = %d AND valid_from <= %s AND COALESCE(valid_to, '9999-12-31') >= %s";
        $args = [$employee_id, $valid_to ?: '9999-12-31', $valid_from];

        if ($exclude_id) {
            $sql .= ' AND id != %d';
            $args[] = $exclude_id;
        }

        return (int) $wpdb->get_var($wpdb->prepare($sql, ...$args)) > 0;
    }
}
