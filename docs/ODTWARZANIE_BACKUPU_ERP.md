# Odtwarzanie backupu ERP OMD (T3.1)

Dokument operacyjny dla backupów generowanych przez `ERP_OMD_Cron_Manager`.

## Zakres backupu
- backup obejmuje tylko tabele ERP OMD o prefiksie: `${wpdb->prefix}erp_omd_`,
- backup nie obejmuje tabel WordPress core (`posts`, `options`, `users`) ani tabel innych pluginów.

## Format backupu
- archiwum ZIP: `uploads/erp-omd-backups/erp-omd-db-YYYYMMDD-HHMMSS.zip`,
- wewnątrz plik SQL z:
  - `DROP TABLE IF EXISTS ...`,
  - `CREATE TABLE ...`,
  - `INSERT INTO ...`.

## Procedura odtworzenia (manualna)
1. Pobierz ostatni backup z katalogu:
   - `wp-content/uploads/erp-omd-backups/`
2. Rozpakuj archiwum ZIP.
3. Zweryfikuj, że SQL zawiera wyłącznie tabele `*_erp_omd_*`.
4. Wykonaj restore SQL na docelowej bazie:
   - `mysql -u <user> -p <database> < erp-omd-db-YYYYMMDD-HHMMSS.sql`
5. Weryfikacja po odtworzeniu:
   - czy istnieją kluczowe tabele ERP (`erp_omd_projects`, `erp_omd_time_entries`, `erp_omd_salary_history`),
   - czy rekordy raportowe i wpisy czasu są widoczne w panelu ERP.

## Walidacja testowa w repo
- test referencyjny zakresu tabel backupu:
  - `php tests/cron-backup-table-filter-test.php`

