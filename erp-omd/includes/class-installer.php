<?php

class ERP_OMD_Installer
{
    public static function activate()
    {
        self::migrate();
        ERP_OMD_Capabilities::activate();
        ERP_OMD_Frontend::register_rewrite_rules();
        ERP_OMD_Cron_Manager::activate();
        flush_rewrite_rules();
    }

    public static function deactivate()
    {
        ERP_OMD_Capabilities::deactivate();
        ERP_OMD_Cron_Manager::deactivate();
        flush_rewrite_rules();
    }

    public static function maybe_upgrade()
    {
        $installed_version = get_option('erp_omd_db_version');
        if ($installed_version !== ERP_OMD_DB_VERSION) {
            self::migrate();
            return;
        }

        self::maybe_cleanup_legacy_time_entry_indexes();
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
        $client_rate_history_table = $wpdb->prefix . 'erp_omd_client_rate_history';
        $estimates_table = $wpdb->prefix . 'erp_omd_estimates';
        $estimate_items_table = $wpdb->prefix . 'erp_omd_estimate_items';
        $projects_table = $wpdb->prefix . 'erp_omd_projects';
        $project_managers_table = $wpdb->prefix . 'erp_omd_project_managers';
        $project_notes_table = $wpdb->prefix . 'erp_omd_project_notes';
        $project_rates_table = $wpdb->prefix . 'erp_omd_project_rates';
        $project_rate_history_table = $wpdb->prefix . 'erp_omd_project_rate_history';
        $project_costs_table = $wpdb->prefix . 'erp_omd_project_costs';
        $project_financials_table = $wpdb->prefix . 'erp_omd_project_financials';
        $time_entries_table = $wpdb->prefix . 'erp_omd_time_entries';
        $project_requests_table = $wpdb->prefix . 'erp_omd_project_requests';
        $attachments_table = $wpdb->prefix . 'erp_omd_attachments';
        $estimate_audit_table = $wpdb->prefix . 'erp_omd_estimate_audit';

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
                street VARCHAR(191) NOT NULL DEFAULT '',
                apartment_number VARCHAR(64) NOT NULL DEFAULT '',
                postal_code VARCHAR(16) NOT NULL DEFAULT '',
                country VARCHAR(2) NOT NULL DEFAULT 'PL',
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                alert_margin_threshold DECIMAL(8,2) NULL,
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
            "CREATE TABLE {$client_rate_history_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                client_id BIGINT UNSIGNED NOT NULL,
                role_id BIGINT UNSIGNED NOT NULL,
                rate DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                valid_from DATE NOT NULL,
                valid_to DATE NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY client_role_date (client_id, role_id, valid_from),
                KEY valid_to (valid_to)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$estimates_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                client_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(191) NOT NULL DEFAULT '',
                status VARCHAR(32) NOT NULL DEFAULT 'wstepny',
                accepted_by_user_id BIGINT UNSIGNED NULL,
                accepted_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY client_id (client_id),
                KEY name (name),
                KEY status (status),
                KEY accepted_by_user_id (accepted_by_user_id)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$estimate_items_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                estimate_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(191) NOT NULL,
                qty DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                cost_internal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                comment TEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY estimate_id (estimate_id)
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
                alert_margin_threshold DECIMAL(8,2) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY client_id (client_id),
                KEY estimate_id (estimate_id),
                KEY manager_id (manager_id),
                KEY status (status),
                KEY billing_type (billing_type)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$project_managers_table} (
                project_id BIGINT UNSIGNED NOT NULL,
                employee_id BIGINT UNSIGNED NOT NULL,
                assigned_at DATETIME NOT NULL,
                PRIMARY KEY  (project_id, employee_id),
                KEY employee_id (employee_id)
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

        dbDelta(
            "CREATE TABLE {$project_rates_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                project_id BIGINT UNSIGNED NOT NULL,
                role_id BIGINT UNSIGNED NOT NULL,
                rate DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY project_role (project_id, role_id),
                KEY role_id (role_id)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$project_rate_history_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                project_id BIGINT UNSIGNED NOT NULL,
                role_id BIGINT UNSIGNED NOT NULL,
                rate DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                valid_from DATE NOT NULL,
                valid_to DATE NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY project_role_date (project_id, role_id, valid_from),
                KEY valid_to (valid_to)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$project_costs_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                project_id BIGINT UNSIGNED NOT NULL,
                amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                description TEXT NULL,
                cost_date DATE NOT NULL,
                created_by_user_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY project_id (project_id),
                KEY cost_date (cost_date),
                KEY created_by_user_id (created_by_user_id)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$project_financials_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                project_id BIGINT UNSIGNED NOT NULL,
                revenue DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                profit DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                margin DECIMAL(8,2) NOT NULL DEFAULT 0.00,
                budget_usage DECIMAL(8,2) NOT NULL DEFAULT 0.00,
                time_revenue DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                time_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                direct_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                last_recalculated_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY project_id (project_id)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$time_entries_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                employee_id BIGINT UNSIGNED NOT NULL,
                project_id BIGINT UNSIGNED NOT NULL,
                role_id BIGINT UNSIGNED NOT NULL,
                hours DECIMAL(8,2) NOT NULL DEFAULT 0.00,
                entry_date DATE NOT NULL,
                description TEXT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'submitted',
                rate_snapshot DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                cost_snapshot DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                created_by_user_id BIGINT UNSIGNED NOT NULL,
                approved_by_user_id BIGINT UNSIGNED NULL,
                approved_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY employee_id (employee_id),
                KEY project_id (project_id),
                KEY role_id (role_id),
                KEY status (status),
                KEY entry_date (entry_date),
                KEY created_by_user_id (created_by_user_id),
                KEY approved_by_user_id (approved_by_user_id)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$project_requests_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                requester_user_id BIGINT UNSIGNED NOT NULL,
                requester_employee_id BIGINT UNSIGNED NOT NULL,
                client_id BIGINT UNSIGNED NOT NULL,
                project_name VARCHAR(191) NOT NULL,
                billing_type VARCHAR(32) NOT NULL DEFAULT 'time_material',
                preferred_manager_id BIGINT UNSIGNED NULL,
                estimate_id BIGINT UNSIGNED NULL,
                brief LONGTEXT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'new',
                reviewed_by_user_id BIGINT UNSIGNED NULL,
                reviewed_at DATETIME NULL,
                converted_project_id BIGINT UNSIGNED NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY requester_user_id (requester_user_id),
                KEY requester_employee_id (requester_employee_id),
                KEY client_id (client_id),
                KEY preferred_manager_id (preferred_manager_id),
                KEY estimate_id (estimate_id),
                KEY status (status),
                KEY converted_project_id (converted_project_id)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$attachments_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                entity_type VARCHAR(32) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                attachment_id BIGINT UNSIGNED NOT NULL,
                label VARCHAR(191) NOT NULL DEFAULT '',
                created_by_user_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY entity_lookup (entity_type, entity_id),
                KEY attachment_id (attachment_id),
                KEY created_by_user_id (created_by_user_id)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$estimate_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                estimate_id BIGINT UNSIGNED NOT NULL,
                action VARCHAR(64) NOT NULL,
                changed_by_user_id BIGINT UNSIGNED NULL,
                details LONGTEXT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY estimate_id (estimate_id),
                KEY action (action),
                KEY changed_by_user_id (changed_by_user_id)
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
        self::add_foreign_key_if_missing($clients_table, 'fk_erp_omd_client_rate_history_client', "ALTER TABLE {$client_rate_history_table} ADD CONSTRAINT fk_erp_omd_client_rate_history_client FOREIGN KEY (client_id) REFERENCES {$clients_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($roles_table, 'fk_erp_omd_client_rate_history_role', "ALTER TABLE {$client_rate_history_table} ADD CONSTRAINT fk_erp_omd_client_rate_history_role FOREIGN KEY (role_id) REFERENCES {$roles_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($clients_table, 'fk_erp_omd_estimates_client', "ALTER TABLE {$estimates_table} ADD CONSTRAINT fk_erp_omd_estimates_client FOREIGN KEY (client_id) REFERENCES {$clients_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($users_table, 'fk_erp_omd_estimates_accepted_by', "ALTER TABLE {$estimates_table} ADD CONSTRAINT fk_erp_omd_estimates_accepted_by FOREIGN KEY (accepted_by_user_id) REFERENCES {$users_table}(ID) ON DELETE SET NULL");
        self::add_foreign_key_if_missing($estimates_table, 'fk_erp_omd_estimate_items_estimate', "ALTER TABLE {$estimate_items_table} ADD CONSTRAINT fk_erp_omd_estimate_items_estimate FOREIGN KEY (estimate_id) REFERENCES {$estimates_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($clients_table, 'fk_erp_omd_projects_client', "ALTER TABLE {$projects_table} ADD CONSTRAINT fk_erp_omd_projects_client FOREIGN KEY (client_id) REFERENCES {$clients_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($projects_table, 'fk_erp_omd_project_managers_project', "ALTER TABLE {$project_managers_table} ADD CONSTRAINT fk_erp_omd_project_managers_project FOREIGN KEY (project_id) REFERENCES {$projects_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($employees_table, 'fk_erp_omd_project_managers_employee', "ALTER TABLE {$project_managers_table} ADD CONSTRAINT fk_erp_omd_project_managers_employee FOREIGN KEY (employee_id) REFERENCES {$employees_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($employees_table, 'fk_erp_omd_projects_manager', "ALTER TABLE {$projects_table} ADD CONSTRAINT fk_erp_omd_projects_manager FOREIGN KEY (manager_id) REFERENCES {$employees_table}(id) ON DELETE SET NULL");
        self::add_foreign_key_if_missing($estimates_table, 'fk_erp_omd_projects_estimate', "ALTER TABLE {$projects_table} ADD CONSTRAINT fk_erp_omd_projects_estimate FOREIGN KEY (estimate_id) REFERENCES {$estimates_table}(id) ON DELETE SET NULL");
        self::add_foreign_key_if_missing($projects_table, 'fk_erp_omd_project_notes_project', "ALTER TABLE {$project_notes_table} ADD CONSTRAINT fk_erp_omd_project_notes_project FOREIGN KEY (project_id) REFERENCES {$projects_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($users_table, 'fk_erp_omd_project_notes_author', "ALTER TABLE {$project_notes_table} ADD CONSTRAINT fk_erp_omd_project_notes_author FOREIGN KEY (author_user_id) REFERENCES {$users_table}(ID) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($projects_table, 'fk_erp_omd_project_rates_project', "ALTER TABLE {$project_rates_table} ADD CONSTRAINT fk_erp_omd_project_rates_project FOREIGN KEY (project_id) REFERENCES {$projects_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($roles_table, 'fk_erp_omd_project_rates_role', "ALTER TABLE {$project_rates_table} ADD CONSTRAINT fk_erp_omd_project_rates_role FOREIGN KEY (role_id) REFERENCES {$roles_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($projects_table, 'fk_erp_omd_project_rate_history_project', "ALTER TABLE {$project_rate_history_table} ADD CONSTRAINT fk_erp_omd_project_rate_history_project FOREIGN KEY (project_id) REFERENCES {$projects_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($roles_table, 'fk_erp_omd_project_rate_history_role', "ALTER TABLE {$project_rate_history_table} ADD CONSTRAINT fk_erp_omd_project_rate_history_role FOREIGN KEY (role_id) REFERENCES {$roles_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($projects_table, 'fk_erp_omd_project_costs_project', "ALTER TABLE {$project_costs_table} ADD CONSTRAINT fk_erp_omd_project_costs_project FOREIGN KEY (project_id) REFERENCES {$projects_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($users_table, 'fk_erp_omd_project_costs_created_by', "ALTER TABLE {$project_costs_table} ADD CONSTRAINT fk_erp_omd_project_costs_created_by FOREIGN KEY (created_by_user_id) REFERENCES {$users_table}(ID) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($projects_table, 'fk_erp_omd_project_financials_project', "ALTER TABLE {$project_financials_table} ADD CONSTRAINT fk_erp_omd_project_financials_project FOREIGN KEY (project_id) REFERENCES {$projects_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($employees_table, 'fk_erp_omd_time_entries_employee', "ALTER TABLE {$time_entries_table} ADD CONSTRAINT fk_erp_omd_time_entries_employee FOREIGN KEY (employee_id) REFERENCES {$employees_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($projects_table, 'fk_erp_omd_time_entries_project', "ALTER TABLE {$time_entries_table} ADD CONSTRAINT fk_erp_omd_time_entries_project FOREIGN KEY (project_id) REFERENCES {$projects_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($roles_table, 'fk_erp_omd_time_entries_role', "ALTER TABLE {$time_entries_table} ADD CONSTRAINT fk_erp_omd_time_entries_role FOREIGN KEY (role_id) REFERENCES {$roles_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($users_table, 'fk_erp_omd_time_entries_created_by', "ALTER TABLE {$time_entries_table} ADD CONSTRAINT fk_erp_omd_time_entries_created_by FOREIGN KEY (created_by_user_id) REFERENCES {$users_table}(ID) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($users_table, 'fk_erp_omd_time_entries_approved_by', "ALTER TABLE {$time_entries_table} ADD CONSTRAINT fk_erp_omd_time_entries_approved_by FOREIGN KEY (approved_by_user_id) REFERENCES {$users_table}(ID) ON DELETE SET NULL");
        self::add_foreign_key_if_missing($users_table, 'fk_erp_omd_project_requests_requester_user', "ALTER TABLE {$project_requests_table} ADD CONSTRAINT fk_erp_omd_project_requests_requester_user FOREIGN KEY (requester_user_id) REFERENCES {$users_table}(ID) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($employees_table, 'fk_erp_omd_project_requests_requester_employee', "ALTER TABLE {$project_requests_table} ADD CONSTRAINT fk_erp_omd_project_requests_requester_employee FOREIGN KEY (requester_employee_id) REFERENCES {$employees_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($clients_table, 'fk_erp_omd_project_requests_client', "ALTER TABLE {$project_requests_table} ADD CONSTRAINT fk_erp_omd_project_requests_client FOREIGN KEY (client_id) REFERENCES {$clients_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($employees_table, 'fk_erp_omd_project_requests_preferred_manager', "ALTER TABLE {$project_requests_table} ADD CONSTRAINT fk_erp_omd_project_requests_preferred_manager FOREIGN KEY (preferred_manager_id) REFERENCES {$employees_table}(id) ON DELETE SET NULL");
        self::add_foreign_key_if_missing($estimates_table, 'fk_erp_omd_project_requests_estimate', "ALTER TABLE {$project_requests_table} ADD CONSTRAINT fk_erp_omd_project_requests_estimate FOREIGN KEY (estimate_id) REFERENCES {$estimates_table}(id) ON DELETE SET NULL");
        self::add_foreign_key_if_missing($users_table, 'fk_erp_omd_project_requests_reviewed_by', "ALTER TABLE {$project_requests_table} ADD CONSTRAINT fk_erp_omd_project_requests_reviewed_by FOREIGN KEY (reviewed_by_user_id) REFERENCES {$users_table}(ID) ON DELETE SET NULL");
        self::add_foreign_key_if_missing($projects_table, 'fk_erp_omd_project_requests_converted_project', "ALTER TABLE {$project_requests_table} ADD CONSTRAINT fk_erp_omd_project_requests_converted_project FOREIGN KEY (converted_project_id) REFERENCES {$projects_table}(id) ON DELETE SET NULL");
        self::add_foreign_key_if_missing($users_table, 'fk_erp_omd_attachments_created_by', "ALTER TABLE {$attachments_table} ADD CONSTRAINT fk_erp_omd_attachments_created_by FOREIGN KEY (created_by_user_id) REFERENCES {$users_table}(ID) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($estimates_table, 'fk_erp_omd_estimate_audit_estimate', "ALTER TABLE {$estimate_audit_table} ADD CONSTRAINT fk_erp_omd_estimate_audit_estimate FOREIGN KEY (estimate_id) REFERENCES {$estimates_table}(id) ON DELETE CASCADE");
        self::add_foreign_key_if_missing($users_table, 'fk_erp_omd_estimate_audit_user', "ALTER TABLE {$estimate_audit_table} ADD CONSTRAINT fk_erp_omd_estimate_audit_user FOREIGN KEY (changed_by_user_id) REFERENCES {$users_table}(ID) ON DELETE SET NULL");

        $wpdb->query(
            "INSERT IGNORE INTO {$project_managers_table} (project_id, employee_id, assigned_at)
            SELECT id, manager_id, updated_at
            FROM {$projects_table}
            WHERE manager_id IS NOT NULL"
        );

        update_option('erp_omd_db_version', ERP_OMD_DB_VERSION);
        add_option('erp_omd_delete_data_on_uninstall', false);
        add_option('erp_omd_alert_margin_threshold', 10);
        add_option('erp_omd_front_login_logo_id', 0);
        add_option('erp_omd_front_login_cover_id', 0);
        add_option('erp_omd_notification_sender_email', '');
        add_option('erp_omd_fixed_monthly_cost', 0);
        add_option('erp_omd_fixed_monthly_cost_items', []);
        self::maybe_cleanup_legacy_time_entry_indexes();
    }

    private static function maybe_cleanup_legacy_time_entry_indexes()
    {
        if (get_option('erp_omd_time_entries_index_cleanup_done') === '1') {
            return;
        }

        global $wpdb;
        $time_entries_table = $wpdb->prefix . 'erp_omd_time_entries';
        self::drop_legacy_time_entry_unique_indexes($time_entries_table);
        update_option('erp_omd_time_entries_index_cleanup_done', '1');
    }

    private static function drop_legacy_time_entry_unique_indexes($time_entries_table)
    {
        global $wpdb;

        $indexes = $wpdb->get_results("SHOW INDEX FROM {$time_entries_table}", ARRAY_A);
        if (! is_array($indexes) || $indexes === []) {
            return;
        }

        $unique_indexes = [];
        foreach ($indexes as $index_row) {
            $index_name = (string) ($index_row['Key_name'] ?? '');
            if ($index_name === '' || $index_name === 'PRIMARY') {
                continue;
            }
            if ((int) ($index_row['Non_unique'] ?? 1) !== 0) {
                continue;
            }

            $sequence = (int) ($index_row['Seq_in_index'] ?? 0);
            $column_name = (string) ($index_row['Column_name'] ?? '');
            if ($sequence <= 0 || $column_name === '') {
                continue;
            }

            if (! isset($unique_indexes[$index_name])) {
                $unique_indexes[$index_name] = [];
            }

            $unique_indexes[$index_name][$sequence] = $column_name;
        }

        foreach ($unique_indexes as $index_name => $index_columns_by_seq) {
            ksort($index_columns_by_seq);
            $index_columns = array_values($index_columns_by_seq);
            $contains_legacy_columns = count(array_intersect($index_columns, ['employee_id', 'project_id', 'role_id', 'hours'])) === 4;
            $contains_entry_date = in_array('entry_date', $index_columns, true);

            if (! $contains_legacy_columns || $contains_entry_date) {
                continue;
            }

            $safe_index_name = str_replace('`', '``', $index_name);
            $wpdb->query("ALTER TABLE {$time_entries_table} DROP INDEX `{$safe_index_name}`");
        }
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
