<?php

class ERP_OMD_Installer
{
    public static function activate()
    {
        self::migrate();
        ERP_OMD_Capabilities::activate();
        ERP_OMD_Cron_Manager::activate();
        ERP_OMD_Frontend::register_rewrite_rules();
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
        self::maybe_allow_nullable_project_request_requester_employee_id();
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
        $project_revenues_table = $wpdb->prefix . 'erp_omd_project_revenues';
        $project_financials_table = $wpdb->prefix . 'erp_omd_project_financials';
        $time_entries_table = $wpdb->prefix . 'erp_omd_time_entries';
        $project_requests_table = $wpdb->prefix . 'erp_omd_project_requests';
        $attachments_table = $wpdb->prefix . 'erp_omd_attachments';
        $suppliers_table = $wpdb->prefix . 'erp_omd_suppliers';
        $cost_invoices_table = $wpdb->prefix . 'erp_omd_cost_invoices';
        $cost_invoice_items_table = $wpdb->prefix . 'erp_omd_cost_invoice_items';
        $cost_invoice_audit_table = $wpdb->prefix . 'erp_omd_cost_invoice_audit';
        $project_calendar_sync_table = $wpdb->prefix . 'erp_omd_project_calendar_sync';
        $estimate_audit_table = $wpdb->prefix . 'erp_omd_estimate_audit';
        $periods_table = $wpdb->prefix . 'erp_omd_periods';
        $adjustment_audit_table = $wpdb->prefix . 'erp_omd_adjustment_audit';

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
                sent_to_client_at DATETIME NULL,
                accepted_by_user_id BIGINT UNSIGNED NULL,
                accepted_at DATETIME NULL,
                client_decision_note TEXT NULL,
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
                deadline_date DATE NULL,
                deadline_completed_at DATETIME NULL,
                deadline_completed_by BIGINT UNSIGNED NULL,
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
            "CREATE TABLE {$project_revenues_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                project_id BIGINT UNSIGNED NOT NULL,
                amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                description TEXT NULL,
                revenue_date DATE NOT NULL,
                created_by_user_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY project_id (project_id),
                KEY revenue_date (revenue_date),
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
                requester_employee_id BIGINT UNSIGNED NULL,
                client_id BIGINT UNSIGNED NOT NULL,
                project_name VARCHAR(191) NOT NULL,
                billing_type VARCHAR(32) NOT NULL DEFAULT 'time_material',
                budget DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                preferred_manager_id BIGINT UNSIGNED NULL,
                estimate_id BIGINT UNSIGNED NULL,
                brief LONGTEXT NULL,
                start_date DATE NULL,
                end_date DATE NULL,
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
        self::add_column_if_missing($project_requests_table, 'budget', "ALTER TABLE {$project_requests_table} ADD COLUMN budget DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER billing_type");

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
            "CREATE TABLE {$suppliers_table} (
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
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY name (name),
                KEY status (status)
            ) ENGINE=InnoDB {$charset_collate};"
        );
        self::add_column_if_missing($suppliers_table, 'category', "ALTER TABLE {$suppliers_table} ADD COLUMN category VARCHAR(191) NOT NULL DEFAULT '' AFTER contact_person_phone");
        self::add_column_if_missing($suppliers_table, 'supplier_description', "ALTER TABLE {$suppliers_table} ADD COLUMN supplier_description TEXT NULL AFTER category");

        dbDelta(
            "CREATE TABLE {$cost_invoices_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                supplier_id BIGINT UNSIGNED NOT NULL,
                project_id BIGINT UNSIGNED NOT NULL,
                invoice_number VARCHAR(191) NOT NULL,
                issue_date DATE NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'zaimportowana',
                net_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                vat_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                gross_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                source VARCHAR(20) NOT NULL DEFAULT 'manual',
                ksef_reference_number VARCHAR(191) NOT NULL DEFAULT '',
                description TEXT NULL,
                created_by_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
                updated_by_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY supplier_invoice_number (supplier_id, invoice_number),
                KEY project_id (project_id),
                KEY status (status),
                KEY source (source)
            ) ENGINE=InnoDB {$charset_collate};"
        );
        self::add_column_if_missing($cost_invoices_table, 'description', "ALTER TABLE {$cost_invoices_table} ADD COLUMN description TEXT NULL AFTER ksef_reference_number");

        dbDelta(
            "CREATE TABLE {$cost_invoice_items_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                invoice_id BIGINT UNSIGNED NOT NULL,
                line_no INT UNSIGNED NOT NULL DEFAULT 1,
                item_name VARCHAR(255) NOT NULL DEFAULT '',
                qty DECIMAL(12,3) NOT NULL DEFAULT 0.000,
                unit VARCHAR(32) NOT NULL DEFAULT '',
                unit_net_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                net_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                vat_rate DECIMAL(7,2) NOT NULL DEFAULT 0.00,
                vat_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                gross_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                source_payload_json LONGTEXT NULL,
                PRIMARY KEY  (id),
                KEY invoice_id (invoice_id),
                KEY line_no (line_no)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$cost_invoice_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                invoice_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                before_value LONGTEXT NULL,
                after_value LONGTEXT NULL,
                changed_by_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY invoice_id (invoice_id),
                KEY changed_by_user_id (changed_by_user_id),
                KEY changed_at (changed_at)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$project_calendar_sync_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                project_id BIGINT UNSIGNED NOT NULL,
                range_event_id VARCHAR(191) NOT NULL DEFAULT '',
                deadline_event_id VARCHAR(191) NOT NULL DEFAULT '',
                sync_status VARCHAR(20) NOT NULL DEFAULT 'pending',
                last_error TEXT NULL,
                last_synced_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY project_id (project_id),
                KEY sync_status (sync_status)
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

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

        dbDelta(
            "CREATE TABLE {$periods_table} (
                month CHAR(7) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'LIVE',
                closed_at DATETIME NULL,
                correction_window_until DATETIME NULL,
                updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (month),
                KEY status (status),
                KEY correction_window_until (correction_window_until)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$adjustment_audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                month CHAR(7) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                field_name VARCHAR(128) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                reason TEXT NOT NULL,
                adjustment_type VARCHAR(32) NOT NULL DEFAULT 'STANDARD',
                changed_by BIGINT UNSIGNED NOT NULL,
                changed_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY month (month),
                KEY entity_lookup (entity_type, entity_id),
                KEY adjustment_type (adjustment_type),
                KEY changed_by (changed_by)
            ) ENGINE=InnoDB {$charset_collate};"
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$projects_table}
                SET status = %s
                WHERE status = %s",
                'archiwum',
                'inactive'
            )
        );

