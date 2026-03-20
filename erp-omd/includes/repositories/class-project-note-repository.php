<?php

class ERP_OMD_Project_Note_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_project_notes';
    }

    public function for_project($project_id)
    {
        global $wpdb;

        $users_table = $wpdb->users;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT pn.*, u.user_login AS author_login
                FROM {$this->table_name()} pn
                LEFT JOIN {$users_table} u ON u.ID = pn.author_user_id
                WHERE pn.project_id = %d
                ORDER BY pn.created_at DESC, pn.id DESC",
                $project_id
            ),
            ARRAY_A
        );
    }

    public function create($project_id, $note, $author_user_id)
    {
        global $wpdb;

        $wpdb->insert(
            $this->table_name(),
            [
                'project_id' => $project_id,
                'note' => $note,
                'author_user_id' => $author_user_id,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%d', '%s']
        );

        return (int) $wpdb->insert_id;
    }
}
