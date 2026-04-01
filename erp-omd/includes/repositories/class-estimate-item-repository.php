<?php

class ERP_OMD_Estimate_Item_Repository
{
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

    public function create(array $data)
    {
        global $wpdb;

        $now = current_time('mysql');
        $wpdb->insert(
            $this->table_name(),
            [
                'estimate_id' => $data['estimate_id'],
                'name' => $data['name'],
                'qty' => $data['qty'],
                'price' => $data['price'],
                'cost_internal' => $data['cost_internal'],
                'comment' => $data['comment'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%s', '%f', '%f', '%f', '%s', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public function update($id, array $data)
    {
        global $wpdb;

        return $wpdb->update(
            $this->table_name(),
            [
                'name' => $data['name'],
                'qty' => $data['qty'],
                'price' => $data['price'],
                'cost_internal' => $data['cost_internal'],
                'comment' => $data['comment'],
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%s', '%f', '%f', '%f', '%s', '%s'],
            ['%d']
        );
    }

    public function delete($id)
    {
        global $wpdb;

        return $wpdb->delete($this->table_name(), ['id' => $id], ['%d']);
    }
}
