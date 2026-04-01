<?php

class ERP_OMD_Role_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_roles';
    }

    public function all()
    {
        global $wpdb;

        return $wpdb->get_results("SELECT * FROM {$this->table_name()} ORDER BY name ASC", ARRAY_A);
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
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'],
                'status' => $data['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s']
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
                'slug' => $data['slug'],
                'description' => $data['description'],
                'status' => $data['status'],
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );
    }

    public function delete($id)
    {
        global $wpdb;

        return $wpdb->delete($this->table_name(), ['id' => $id], ['%d']);
    }

    public function slug_exists($slug, $exclude_id = null)
    {
        global $wpdb;

        if ($exclude_id) {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name()} WHERE slug = %s AND id != %d", $slug, $exclude_id));
        } else {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name()} WHERE slug = %s", $slug));
        }

        return (int) $count > 0;
    }
}
