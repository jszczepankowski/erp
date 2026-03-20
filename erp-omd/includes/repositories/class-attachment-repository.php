<?php

class ERP_OMD_Attachment_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_attachments';
    }

    public function for_entity($entity_type, $entity_id)
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name()} WHERE entity_type = %s AND entity_id = %d ORDER BY created_at DESC, id DESC",
                $entity_type,
                $entity_id
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
                'entity_type' => $data['entity_type'],
                'entity_id' => $data['entity_id'],
                'attachment_id' => $data['attachment_id'],
                'label' => $data['label'],
                'created_by_user_id' => $data['created_by_user_id'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%s', '%d', '%d', '%s', '%d', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public function delete($id)
    {
        global $wpdb;

        return $wpdb->delete($this->table_name(), ['id' => $id], ['%d']);
    }
}
