<?php

class ERP_OMD_Supplier_Repository
{
    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'erp_omd_suppliers';
    }

    public function all_active()
    {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$this->table_name()} WHERE status = 'active' ORDER BY name ASC",
            ARRAY_A
        );
    }

    public function find($id)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE id = %d", $id),
            ARRAY_A
        );
    }

    public function find_by_nip($nip)
    {
        global $wpdb;

        $nip = preg_replace('/[^0-9]/', '', (string) $nip);
        if (! is_string($nip) || $nip === '') {
            return [];
        }

        return (array) $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name()} WHERE REPLACE(REPLACE(REPLACE(nip, '-', ''), ' ', ''), '.', '') = %s ORDER BY id ASC",
                $nip
            ),
            ARRAY_A
        );
    }

    public function create(array $data)
    {
        global $wpdb;

        $now = current_time('mysql');
        $wpdb->insert(
            $this->table_name(),
            [
                'name' => (string) ($data['name'] ?? ''),
                'company' => (string) ($data['company'] ?? ''),
                'nip' => (string) ($data['nip'] ?? ''),
                'email' => (string) ($data['email'] ?? ''),
                'phone' => (string) ($data['phone'] ?? ''),
                'contact_person_name' => (string) ($data['contact_person_name'] ?? ''),
                'contact_person_email' => (string) ($data['contact_person_email'] ?? ''),
                'contact_person_phone' => (string) ($data['contact_person_phone'] ?? ''),
                'category' => (string) ($data['category'] ?? ''),
                'supplier_description' => (string) ($data['supplier_description'] ?? ''),
                'city' => (string) ($data['city'] ?? ''),
                'street' => (string) ($data['street'] ?? ''),
                'apartment_number' => (string) ($data['apartment_number'] ?? ''),
                'postal_code' => (string) ($data['postal_code'] ?? ''),
                'country' => (string) ($data['country'] ?? 'PL'),
                'status' => (string) ($data['status'] ?? 'active'),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public function update($id, array $data)
    {
        global $wpdb;

        return $wpdb->update(
            $this->table_name(),
            [
                'name' => (string) ($data['name'] ?? ''),
                'company' => (string) ($data['company'] ?? ''),
                'nip' => (string) ($data['nip'] ?? ''),
                'email' => (string) ($data['email'] ?? ''),
                'phone' => (string) ($data['phone'] ?? ''),
                'contact_person_name' => (string) ($data['contact_person_name'] ?? ''),
                'contact_person_email' => (string) ($data['contact_person_email'] ?? ''),
                'contact_person_phone' => (string) ($data['contact_person_phone'] ?? ''),
                'category' => (string) ($data['category'] ?? ''),
                'supplier_description' => (string) ($data['supplier_description'] ?? ''),
                'city' => (string) ($data['city'] ?? ''),
                'street' => (string) ($data['street'] ?? ''),
                'apartment_number' => (string) ($data['apartment_number'] ?? ''),
                'postal_code' => (string) ($data['postal_code'] ?? ''),
                'country' => (string) ($data['country'] ?? 'PL'),
                'status' => (string) ($data['status'] ?? 'active'),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => (int) $id],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );
    }

    public function delete($id)
    {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name(),
            ['id' => (int) $id],
            ['%d']
        );
    }
}
