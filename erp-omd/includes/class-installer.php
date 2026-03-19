<?php

class ERP_OMD_Installer
{
    public static function activate()
    {
        self::migrate();
        ERP_OMD_Capabilities::activate();
    }

    public static function deactivate()
    {
        ERP_OMD_Capabilities::deactivate();
    }

    public static function maybe_upgrade()
    {
        $installed_version = get_option('erp_omd_db_version');
        if ($installed_version !== ERP_OMD_DB_VERSION) {
            self::migrate();
        }
    }

    public static function migrate()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $users_table = $wpdb->users;

        $roles_table = $wpdb->prefix . 'erp_omd_roles';
        $employees_table = $wpdb->prefix . 'erp_omd_employees';
        $employee_roles_table = $wpdb->prefix . 'erp_omd_employee_roles';
        $salary_table = $wpdb->prefix . 'erp_omd_salary_history';
        $clients_table = $wpdb->prefix . 'erp_omd_clients';
        $client_rates_table = $wpdb->prefix . 'erp_omd_client_rates';
        $projects_table = $wpdb->prefix . 'erp_omd_projects';
        $project_notes_table = $wpdb->prefix . 'erp_omd_project_notes';

        dbDelta(
            "CREATE TABLE {$roles_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(191) NOT NULL,
                slug VARCHAR(191) NOT NULL,
                description TEXT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY slug (slug),
                KEY status (status)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$employees_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                default_role_id BIGINT UNSIGNED NULL,
                account_type VARCHAR(20) NOT NULL DEFAULT 'worker',
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY user_id (user_id),
                KEY default_role_id (default_role_id),
                KEY account_type (account_type),
                KEY status (status)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$employee_roles_table} (
                employee_id BIGINT UNSIGNED NOT NULL,
                role_id BIGINT UNSIGNED NOT NULL,
                assigned_at DATETIME NOT NULL,
                PRIMARY KEY  (employee_id, role_id),
                KEY role_id (role_id)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$salary_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                employee_id BIGINT UNSIGNED NOT NULL,
                monthly_salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                monthly_hours DECIMAL(8,2) NOT NULL DEFAULT 0.00,
                hourly_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                valid_from DATE NOT NULL,
                valid_to DATE NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY employee_id (employee_id),
                KEY valid_from (valid_from),
                KEY valid_to (valid_to)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$clients_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(191) NOT NULL,
                company VARCHAR(191) NOT NULL DEFAULT '',
                nip VARCHAR(32) NOT NULL DEFAULT '',
                email VARCHAR(191) NOT NULL DEFAULT '',
                phone VARCHAR(64) NOT NULL DEFAULT '',
                contact_person_name VARCHAR(191) NOT NULL DEFAULT '',
                contact_person_email VARCHAR(191) NOT NULL DEFAULT '',
                contact_person_phone VARCHAR(64) NOT NULL DEFAULT '',
                city VARCHAR(191) NOT NULL DEFAULT '',
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                account_manager_id BIGINT UNSIGNED NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY nip (nip),
                KEY status (status),
                KEY account_manager_id (account_manager_id)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$client_rates_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                client_id BIGINT UNSIGNED NOT NULL,
                role_id BIGINT UNSIGNED NOT NULL,
                rate DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY client_role (client_id, role_id),
                KEY role_id (role_id)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$projects_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                client_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(191) NOT NULL,
                billing_type VARCHAR(32) NOT NULL DEFAULT 'time_material',
                budget DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                retainer_monthly_fee DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                status VARCHAR(32) NOT NULL DEFAULT 'do_rozpoczecia',
                start_date DATE NULL,
                end_date DATE NULL,
                manager_id BIGINT UNSIGNED NULL,
                estimate_id BIGINT UNSIGNED NULL,
                brief LONGTEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY client_id (client_id),
                KEY manager_id (manager_id),
                KEY status (status),
                KEY billing_type (billing_type)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$project_notes_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                project_id BIGINT UNSIGNED NOT NULL,
                author_user_id BIGINT UNSIGNED NOT NULL,
                note LONGTEXT NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY project_id (project_id),
                KEY author_user_id (author_user_id)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        self::add_foreign_key_if_missing($roles_table, 'fk_erp_omd_employees_default_role', "ALTER TABLE {$employees_table} ADD CONSTRAINT fk_erp_omd_employees_default_role FOREIGN KEY (default_role_id) REFERENCES {$roles_table}(id) ON DELETE SET NULL");
        self::add_foreign_key_if_missing($users_table, 'fk_erp_omd_employees_user', "ALTER TABLE {$employees_table} ADD CONSTRAINT fk_erp_omd_employees_user FOREIGN KEY (user_id) REFERENCES {$users_table}(ID) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($employees_table, 'fk_erp_omd_employee_roles_employee', "ALTER TABLE {$employee_roles_table} ADD CONSTRAINT fk_erp_omd_employee_roles_employee FOREIGN KEY (employee_id) REFERENCES {$employees_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($roles_table, 'fk_erp_omd_employee_roles_role', "ALTER TABLE {$employee_roles_table} ADD CONSTRAINT fk_erp_omd_employee_roles_role FOREIGN KEY (role_id) REFERENCES {$roles_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($employees_table, 'fk_erp_omd_salary_history_employee', "ALTER TABLE {$salary_table} ADD CONSTRAINT fk_erp_omd_salary_history_employee FOREIGN KEY (employee_id) REFERENCES {$employees_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($employees_table, 'fk_erp_omd_clients_account_manager', "ALTER TABLE {$clients_table} ADD CONSTRAINT fk_erp_omd_clients_account_manager FOREIGN KEY (account_manager_id) REFERENCES {$employees_table}(id) ON DELETE SET NULL");
        self::add_foreign_key_if_missing($clients_table, 'fk_erp_omd_client_rates_client', "ALTER TABLE {$client_rates_table} ADD CONSTRAINT fk_erp_omd_client_rates_client FOREIGN KEY (client_id) REFERENCES {$clients_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($roles_table, 'fk_erp_omd_client_rates_role', "ALTER TABLE {$client_rates_table} ADD CONSTRAINT fk_erp_omd_client_rates_role FOREIGN KEY (role_id) REFERENCES {$roles_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($clients_table, 'fk_erp_omd_projects_client', "ALTER TABLE {$projects_table} ADD CONSTRAINT fk_erp_omd_projects_client FOREIGN KEY (client_id) REFERENCES {$clients_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($employees_table, 'fk_erp_omd_projects_manager', "ALTER TABLE {$projects_table} ADD CONSTRAINT fk_erp_omd_projects_manager FOREIGN KEY (manager_id) REFERENCES {$employees_table}(id) ON DELETE SET NULL");
        self::add_foreign_key_if_missing($projects_table, 'fk_erp_omd_project_notes_project', "ALTER TABLE {$project_notes_table} ADD CONSTRAINT fk_erp_omd_project_notes_project FOREIGN KEY (project_id) REFERENCES {$projects_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($users_table, 'fk_erp_omd_project_notes_author', "ALTER TABLE {$project_notes_table} ADD CONSTRAINT fk_erp_omd_project_notes_author FOREIGN KEY (author_user_id) REFERENCES {$users_table}(ID) ON DELETE CASCADE");

        update_option('erp_omd_db_version', ERP_OMD_DB_VERSION);
        add_option('erp_omd_delete_data_on_uninstall', false);
    }

    private static function add_foreign_key_if_missing($referenced_table, $constraint_name, $sql)
    {
        global $wpdb;

        $existing = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = %s AND REFERENCED_TABLE_NAME = %s AND CONSTRAINT_NAME = %s LIMIT 1',
                DB_NAME,
                $referenced_table,
                $constraint_name
            )
        );

        if (! $existing) {
            $wpdb->query($sql);
        }
    }
}
