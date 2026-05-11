<?php

class ERP_OMD_Estimate_Item_Repository
{
    private $column_cache = [];
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_estimate_items';
    }

    public function for_estimate($estimate_id)
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name()} WHERE estimate_id = %d ORDER BY id ASC",
                $estimate_id
            ),
            ARRAY_A
        );
    }

    public function find($id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE id = %d", $id), ARRAY_A);
    }


    private function has_column($column)
    {
        if (isset($this->column_cache[$column])) {
            return $this->column_cache[$column];
        }

        global $wpdb;

        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW COLUMNS FROM {$this->table_name()} LIKE %s",
                $column
            )
        );

        $this->column_cache[$column] = (bool) $result;

        return $this->column_cache[$column];
    }

    public function create(array $data)
    {
        global $wpdb;

        $now = current_time('mysql');
        $insert_data = [
            'estimate_id' => $data['estimate_id'],
            'name' => $data['name'],
            'qty' => $data['qty'],
            'price' => $data['price'],
            'cost_internal' => $data['cost_internal'],
            'comment' => $data['comment'],
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $insert_format = ['%d', '%s', '%f', '%f', '%f', '%s', '%s', '%s'];

        if ($this->has_column('margin_percent')) {
            $insert_data['margin_percent'] = isset($data['margin_percent']) ? (float) $data['margin_percent'] : 0.0;
            $insert_format[] = '%f';
        }

        if ($this->has_column('price_source')) {
            $insert_data['price_source'] = isset($data['price_source']) && in_array((string) $data['price_source'], ['manual', 'suggested'], true) ? (string) $data['price_source'] : 'manual';
            $insert_format[] = '%s';
        }

        $wpdb->insert($this->table_name(), $insert_data, $insert_format);

        return (int) $wpdb->insert_id;
    }

    public function update($id, array $data)
    {
        global $wpdb;

        $update_data = [
            'name' => $data['name'],
            'qty' => $data['qty'],
            'price' => $data['price'],
            'cost_internal' => $data['cost_internal'],
            'comment' => $data['comment'],
            'updated_at' => current_time('mysql'),
        ];
        $update_format = ['%s', '%f', '%f', '%f', '%s', '%s'];

        if ($this->has_column('margin_percent')) {
            $update_data['margin_percent'] = isset($data['margin_percent']) ? (float) $data['margin_percent'] : 0.0;
            $update_format[] = '%f';
        }

        if ($this->has_column('price_source')) {
            $update_data['price_source'] = isset($data['price_source']) && in_array((string) $data['price_source'], ['manual', 'suggested'], true) ? (string) $data['price_source'] : 'manual';
            $update_format[] = '%s';
        }

        return $wpdb->update(
            $this->table_name(),
            $update_data,
            ['id' => $id],
            $update_format,
            ['%d']
        );
    }

    public function delete($id)
    {
        global $wpdb;

        return $wpdb->delete($this->table_name(), ['id' => $id], ['%d']);
    }
}
