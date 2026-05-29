# ERP OMD — szczegółowa specyfikacja techniczna całej wtyczki

Data przeglądu: 2026-05-28  
Zakres: cały kod produkcyjny w `erp-omd/` — bootstrap pluginu, runtime admin/front, REST API, serwisy, repozytoria, szablony, JS, CSS, uninstall.  
Charakter dokumentu: specyfikacja techniczno-biznesowa i mapa zależności systemu po code review.

---

## 1. Streszczenie systemu

ERP OMD jest wtyczką WordPress pełniącą rolę wewnętrznego systemu ERP/operations dla agencji usługowej. System obsługuje cały cykl operacyjny:

1. konfiguracja ról projektowych, pracowników i uprawnień,
2. kartoteki klientów, dostawców oraz stawek,
3. presales i kosztorysy,
4. projekty, wnioski projektowe, notatki, załączniki i deadline'y,
5. ewidencja czasu pracy i akceptacja wpisów,
6. koszty, przychody, faktury kosztowe, import KSeF,
7. controlling, raporty, kalendarz i alerty,
8. front dla pracownika, managera i klienta,
9. zadania cron, backup/restore i integracje Google Calendar.

Wtyczka używa WordPressa jako platformy użytkowników, sesji, capabilities, menu, REST API, crona i mailingu, a dane domenowe przechowuje we własnych tabelach SQL prefiksowanych `erp_omd_*`.

---

## 2. Bootstrap, lifecycle i zależności startowe

### 2.1 Punkt wejścia

Plik `erp-omd/erp-omd.php` definiuje metadane pluginu, wersję aplikacji `4.0_dev`, wersję bazy `6.6.3`, ścieżki `ERP_OMD_PATH/URL/FILE`, ładuje autoloader, rejestruje hooki aktywacji/dezaktywacji oraz uruchamia singleton `ERP_OMD_Plugin` na `plugins_loaded`.

Funkcje globalne:

| Funkcja | Cel techniczny | Cel biznesowy / workflow |
|---|---|---|
| `erp_omd()` | Tworzy i zwraca jedną instancję kontenera `ERP_OMD_Plugin`. | Zapewnia, że admin, front i API pracują na tych samych repozytoriach i serwisach. |
| `erp_omd_reports_cache_bump_version()` | Inkrementuje wersję cache raportów w opcji WP. | Każda zmiana danych finansowych/operacyjnych może wymusić świeże raporty. |
| `erp_omd_invalidate_plugin_opcache()` | Unieważnia OPcache dla głównych plików pluginu i repozytoriów. | Zmniejsza ryzyko pracy na starym kodzie po wdrożeniu aktualizacji. |

### 2.2 Kontener aplikacji `ERP_OMD_Plugin`

`ERP_OMD_Plugin` ręcznie buduje graf zależności:

- repozytoria: role, pracownicy, salary, klienci, stawki, projekty, wnioski, kosztorysy, pozycje, audyty, koszty, przychody, finanse, time entries, załączniki,
- serwisy: miesięczne godziny, pracownicy, klient/projekt, wnioski, kosztorysy, czas pracy, finanse, raporty, alerty, załączniki, Google Calendar,
- runtime'y: `ERP_OMD_Admin`, `ERP_OMD_Frontend`, `ERP_OMD_REST_API`.

Workflow bootowania:

1. `ERP_OMD_Installer::maybe_upgrade()` porównuje wersję DB i wykonuje migracje/naprawy.
2. `ERP_OMD_Capabilities::register_hooks()` dodaje role/capabilities.
3. Admin, frontend i REST API rejestrują własne hooki.
4. `ERP_OMD_Cron_Manager` rejestruje harmonogramy.
5. Hooki domenowe synchronizują projekty z Google Calendar po zapisie/usunięciu.
6. Login użytkownika aktualizuje `erp_omd_last_login_at`.
7. Nadawca maili jest ustawiany z opcji `erp_omd_mail_from`.

---

## 3. Architektura warstwowa

| Warstwa | Pliki | Odpowiedzialność | Nie powinna robić |
|---|---|---|---|
| Bootstrap/infrastruktura | `erp-omd.php`, `class-plugin.php`, `class-autoloader.php`, `class-installer.php`, `class-capabilities.php`, `class-cron-manager.php`, `class-backup-manager.php`, `uninstall.php` | start systemu, migracje, role WP, zadania cron, backup, cleanup | logiki formularzy i widoków |
| Runtime admin | `includes/class-admin-runtime.php`, `templates/admin/*` | menu wp-admin, formularze, redirecty, nonces, notices, agregowanie danych dla szablonów | bezpośrednich reguł walidacyjnych bez serwisów, gdy istnieje serwis domenowy |
| Runtime front | `includes/class-frontend-runtime.php`, `templates/front/*` | routing `/erp-omd/...`, login, panele worker/manager/client, obsługa formularzy frontowych | adminowych capability gates bez mapowania na front |
| REST API | `includes/class-rest-api.php` | endpointy `erp-omd/v1`, sanitizacja payloadów, permission callbacks, JSON | renderowania HTML |
| Services | `includes/services/*` | reguły biznesowe, workflow, obliczenia, integracje | składania SQL |
| Repositories | `includes/repositories/*` | CRUD i zapytania SQL na tabelach domenowych | decyzji biznesowych i HTML |
| UI assets | `assets/js/*`, `assets/css/*` | interaktywność formularzy, autosave, filtry, wygląd | dostępu do danych poza REST/formularzami |

Zasada przepływu: **UI/formularz/REST → runtime/API → service → repository → baza/opcje WP → odpowiedź/redirect/JSON**.

---

## 4. Model danych i cel biznesowy tabel

| Tabela | Encja | Cel biznesowy | Najważniejsze powiązania |
|---|---|---|---|
| `erp_omd_roles` | Rola projektowa/kompetencja | Słownik ról używanych w stawkach, pracownikach i czasie pracy. | role pracownika, stawki klienta/projektu, time entries |
| `erp_omd_employees` | Pracownik ERP | Powiązanie użytkownika WP z kontem operacyjnym i typem konta. | WP users, role, salary, time entries, manager projektu |
| `erp_omd_employee_roles` | Pracownik–rola | Wielokrotne kompetencje pracownika. | employees, roles |
| `erp_omd_salary_history` | Historia wynagrodzeń | Koszt godzinowy w czasie, potrzebny do marży i rentowności. | employees, time entries |
| `erp_omd_clients` | Klient | CRM operacyjny, dane kontaktowe, próg marży, opiekun. | projects, estimates, rates |
| `erp_omd_client_rates` / `erp_omd_client_rate_history` | Stawki klienta | Domyślne ceny sprzedażowe per rola i historia zmian. | clients, roles, time entries |
| `erp_omd_estimates` / `erp_omd_estimate_items` | Kosztorys i pozycje | Oferta/presales, akceptacja klienta, źródło projektu i przychodów. | clients, projects, project_requests, estimate_audit |
| `erp_omd_projects` | Projekt | Główna jednostka delivery, rozliczeń, raportów i kalendarza. | clients, managers, notes, rates, costs, revenues, time, attachments |
| `erp_omd_project_managers` | Managerowie projektu | Współdzielona odpowiedzialność i approval flow. | projects, employees |
| `erp_omd_project_notes` | Notatki | Historia komunikacji, w tym notatki klienta i automatyczne wpisy. | projects, WP users |
| `erp_omd_project_rates` / `erp_omd_project_rate_history` | Stawki projektowe | Override stawek klienta dla konkretnego projektu. | projects, roles, time entries |
| `erp_omd_project_costs` | Koszty bezpośrednie projektu | Koszty zewnętrzne i ręczne pozycje kosztowe. | projects, cost invoices, financials |
| `erp_omd_project_revenues` | Przychody projektu | Przychody dodatkowe/pozycje do raportowania. | projects, financials, reporting |
| `erp_omd_project_financials` | Materializowane finanse | Cache rentowności: revenue, cost, profit, margin, budget usage. | projects, costs, revenues, time entries |
| `erp_omd_time_entries` | Wpis czasu | Produkcja zespołu, approval, snapshot stawki i kosztu. | employees, projects, roles |
| `erp_omd_project_requests` | Wniosek projektowy | Intake pracy od managera/pracownika/klienta i konwersja do projektu. | clients, employees, estimates, projects |
| `erp_omd_attachments` | Załączniki | Dokumenty i pliki powiązane z projektami/kosztorysami. | dowolna encja przez `entity_type/entity_id` |
| `erp_omd_suppliers` | Dostawca | Słownik kontrahentów kosztowych i dopasowanie KSeF. | cost invoices |
| `erp_omd_cost_invoices` / `erp_omd_cost_invoice_items` | Faktury kosztowe | Moderowane koszty, pozycje faktury, powiązanie z projektem. | suppliers, projects, project_costs |
| `erp_omd_cost_invoice_audit` | Audyt faktury | Ślad zmian/moderacji pól faktury. | cost invoices, WP users |
| `erp_omd_project_calendar_sync` | Synchronizacja kalendarza | Mapowanie projektu na eventy Google Calendar i status synchronizacji. | projects |
| `erp_omd_estimate_audit` | Audyt kosztorysu | Rejestr akcji na kosztorysie: wysłanie, decyzja, akceptacja. | estimates, WP users |
| `erp_omd_adjustment_audit` | Audyt korekt raportowych | Wymuszenie uzasadnienia korekt danych w zamkniętych/raportowych okresach. | miesiące, encje finansowe |
| `erp_omd_acl_audit` | Audyt ACL | Ślad zmian override'ów uprawnień użytkownika. | WP users |

---

## 5. Uprawnienia, role i ACL

### 5.1 Capabilities WordPress

System rejestruje capabilities:

- `erp_omd_access`,
- `erp_omd_manage_settings`,
- `erp_omd_manage_roles`,
- `erp_omd_manage_employees`,
- `erp_omd_manage_salary`,
- `erp_omd_manage_account_types`,
- `erp_omd_manage_clients`,
- `erp_omd_manage_projects`,
- `erp_omd_manage_time`,
- `erp_omd_approve_time`,
- `erp_omd_front_worker`,
- `erp_omd_front_manager`,
- `erp_omd_front_client`.

Role WP:

| Rola WP | Przeznaczenie | Dostęp |
|---|---|---|
| `administrator` | pełna administracja | wszystkie capabilities ERP |
| `erp_omd_manager` | manager operacyjny | admin ERP, projekty, klienci, pracownicy, salary, time approval, front manager/worker |
| `erp_omd_worker` | pracownik | front worker, time tracking |
| `erp_omd_client` | klient | front client |

### 5.2 ACL per użytkownik

`ERP_OMD_Acl_Service` rozstrzyga prawo `can_user()` i widoczność menu `can_view_menu_page()` przez:

1. natywne capabilities WP,
2. indywidualne override'y zapisane przy pracowniku,
3. audyt zmian przez `ERP_OMD_Acl_Audit_Repository`.

Cel biznesowy: administrator może nadać lub odebrać dostęp konkretnemu użytkownikowi bez zmiany całej roli WP, a zmiany są rozliczalne audytowo.

---

## 6. Moduły biznesowe i workflow

### 6.1 Pracownicy, role, salary

Workflow:

1. Admin tworzy role projektowe.
2. Admin tworzy pracownika powiązanego z użytkownikiem WP, typem konta i statusem.
3. Pracownik otrzymuje rolę domyślną i role dodatkowe.
4. Admin zapisuje historię wynagrodzeń z `valid_from/valid_to`, miesięczną pensją, godzinami i kosztem godzinowym.
5. Wpis czasu pobiera snapshot kosztu z obowiązującej historii salary.

Reguły:

- `Employee_Service::validate_employee()` pilnuje poprawności pracownika.
- `validate_salary()` i `Salary_History_Repository::overlaps()` zapobiegają błędnym okresom salary.
- `Monthly_Hours_Service` sugeruje miesięczne godziny i koszt godzinowy.

### 6.2 Klienci i stawki klienta

Workflow:

1. Admin tworzy klienta z danymi firmy, kontaktem, adresem, NIP, statusem i progiem marży.
2. Opcjonalnie przypisuje opiekuna klienta.
3. Dla klienta definiuje stawki per rola.
4. Zmiany stawek są wersjonowane w historii.
5. Projekty i kosztorysy wybierają klienta jako właściciela relacji biznesowej.

Reguły:

- walidacja NIP, telefonu, kodu pocztowego, kraju i dat jest w `Client_Project_Service`,
- stawka projektowa ma pierwszeństwo przed stawką klienta,
- próg marży klienta może nadpisać ustawienie globalne alertów.

### 6.3 Projekty

Workflow podstawowy:

1. Projekt powstaje ręcznie, z kosztorysu, przez konwersję wniosku albo przez merge/duplikację.
2. Admin/manager ustawia klienta, nazwę, typ rozliczenia, budżet/retainer, status, daty, deadline, managerów, brief i linki.
3. Projekt otrzymuje notatki, stawki, koszty, przychody i załączniki.
4. Wpisy czasu i faktury kosztowe zasilają finanse projektu.
5. Alerty i raporty wykrywają przekroczenia budżetu, niską marżę i braki danych.
6. Zapis/usunięcie projektu może synchronizować eventy Google Calendar.

Statusy i typy rozliczeń są etykietowane w runtime'ach (`project_status_label()`, `billing_type_label()`), a reguły przejść waliduje `Client_Project_Service::validate_status_transition()`.

### 6.4 Wnioski projektowe

Workflow:

1. Worker, manager lub klient tworzy wniosek z opisem potrzeby.
2. Runtime zapisuje request przez `Project_Request_Service::prepare()` i `validate()`.
3. Manager/admin może zmienić status, odrzucić, anulować lub zaakceptować.
4. Przy konwersji `build_project_payload()` buduje dane projektu.
5. Repozytorium oznacza wniosek jako skonwertowany i wiąże go z projektem.

Cel biznesowy: każda nowa inicjatywa może trafić do kolejki operacyjnej bez omijania kontroli managera.

### 6.5 Kosztorysy i decyzja klienta

Workflow:

1. Admin/manager tworzy kosztorys klienta.
2. Dodaje pozycje: nazwa, ilość, cena, koszt wewnętrzny, źródło ceny, komentarz.
3. `Estimate_Service::calculate_totals()` liczy wartości i marże.
4. Link decyzyjny klienta jest generowany przez token i wysyłany mailem.
5. Klient akceptuje lub odrzuca w `Front_Estimate_Decision_Screen` / REST `estimate-decision`.
6. Akceptacja wywołuje `Estimate_Service::accept()` — oznacza kosztorys jako zaakceptowany, może utworzyć/powiązać projekt oraz przenieść przychody/koszty do projektu.
7. System wysyła podziękowanie klientowi i powiadomienie wewnętrzne, zapisuje audyt i notatkę projektu.

Reguły:

- odrzucenie wymaga komentarza,
- link decyzyjny ma token i może zostać unieważniony,
- przyjęte kosztorysy mają ślad w `erp_omd_estimate_audit`,
- pozycje mogą być eksportowane CSV.

### 6.6 Time tracking i approval

Workflow worker:

1. Pracownik loguje się przez front.
2. Wybiera projekt, rolę, datę, liczbę godzin i opis.
3. `Time_Entry_Service::prepare()` wylicza `rate_snapshot` i `cost_snapshot`.
4. Wpis trafia zwykle jako `submitted`.
5. Worker może edytować/usunąć własne wpisy w granicach reguł `can_edit_entry()` i `can_delete_entry()`.

Workflow manager/admin:

1. Manager widzi kolejkę wpisów projektów, którymi zarządza.
2. `can_approve_entry()` dopuszcza akceptację/odrzucenie dla admina lub managera projektu.
3. Zmiana statusu aktualizuje `approved_by_user_id/approved_at`.
4. Zatwierdzone wpisy zasilają raporty i finanse.

Cel biznesowy: snapshoty stawek i kosztów zabezpieczają historyczną rentowność przed zmianami cenników i pensji.

### 6.7 Finanse projektu, koszty i przychody

Workflow:

1. Time entries dają przychód z pracy (`hours * rate_snapshot`) i koszt pracy (`hours * cost_snapshot`).
2. `project_costs` dodają koszty bezpośrednie.
3. `project_revenues` dodają przychody dodatkowe.
4. `Project_Financial_Service::calculate()` liczy revenue, cost, profit, margin, budget usage, time revenue/cost i direct cost.
5. `recalculate()`/`recalculate_all()` zapisują materializowany wynik w `project_financials`.
6. Raporty i alerty korzystają z tych agregatów.

Reguły biznesowe:

- budżet i retainer wpływają na interpretację rentowności,
- koszty faktur kosztowych mogą synchronizować się z kosztami projektu,
- edycje wartości finansowych mogą wymagać audytu korekty, zwłaszcza dla zamkniętych okresów.

### 6.8 Dostawcy, faktury kosztowe i KSeF

Workflow faktury kosztowej:

1. Admin tworzy/edytuje dostawcę.
2. Admin importuje XML KSeF lub ręcznie dodaje fakturę.
3. `KSeF_Import_Service` parsuje dokument, klasyfikuje typ, dopasowuje dostawcę/klienta i wykrywa duplikaty.
4. Niepewne importy trafiają do kolejki moderacji.
5. `Cost_Invoice_Workflow_Service` waliduje statusy, zapisuje audit zmian i opcjonalnie mapuje fakturę na projekt.
6. Pozycje faktury trafiają do `cost_invoice_items`, a powiązanie z projektem może tworzyć/aktualizować `project_costs`.

Workflow faktury sprzedażowej:

1. XML sprzedażowy trafia do inboxa KSeF.
2. Dokument można powiązać z projektem.
3. System sprawdza, czy projekt ma finalną fakturę sprzedażową, co wpływa na lifecycle i walidacje.

Cel biznesowy: koszty i dokumenty sprzedażowe są kontrolowane w jednym miejscu, z kolejką błędów i audytem.

### 6.9 Raporty, kalendarz i controlling

`ERP_OMD_Reporting_Service` buduje raporty:

- project report,
- client report,
- invoice report,
- monthly report,
- OMD settlement report,
- calendar,
- definicje eksportu.

Workflow:

1. Filtry są sanityzowane (`sanitize_filters()`).
2. `build_report()` deleguje do właściwego wariantu raportu.
3. Dane są pobierane batchowo z repozytoriów.
4. Serwis tworzy indeksy metryk per miesiąc/projekt/employee.
5. Wynik trafia do admin templates albo REST JSON.
6. Cache raportów jest kluczowany filtrem i wersją danych; mutacje bumpują wersję.

Cel biznesowy: zarząd i operations widzą rentowność klientów/projektów, aktywność miesięczną, readiness do fakturowania i koszty stałe.

### 6.10 Alerty

`Alert_Service::all_alerts()` agreguje alerty z:

- autoryzacji Google Calendar,
- projektów z niską marżą,
- przekroczonego budżetu,
- brakujących stawek,
- brakujących wpisów czasu.

Cel biznesowy: zamiast ręcznie analizować projekty, użytkownik dostaje listę ryzyk operacyjnych i finansowych.

### 6.11 Front worker/manager/client

Front używa własnych rewrite rules i query vars, żeby pracownicy/managerowie/klienci nie musieli pracować w `wp-admin`.

Ścieżki logiczne:

- login/logout frontowy,
- panel workera: wpis czasu, wniosek projektowy, taski prywatne, kalendarz/rytm pracy,
- panel managera: projekty, koszty/przychody, kosztorysy, approval queue, wnioski,
- panel klienta: projekty klienta, notatki, załączniki, wnioski,
- ekran decyzji kosztorysu po tokenie.

Reguły:

- nieaktywny pracownik jest blokowany,
- użytkownik bez capability frontowej jest przekierowany,
- klient widzi tylko własne projekty i finanse przygotowane przez `Client_Portal_Service`.

### 6.12 Google Calendar

Workflow:

1. Admin konfiguruje OAuth, client ID/secret, calendar ID i environment.
2. Po zapisie projektu hook `erp_omd_project_saved` wywołuje synchronizację.
3. Serwis buduje event zakresu projektu i event deadline'u.
4. Eventy są tworzone/aktualizowane przez Google API.
5. Identyfikatory eventów i status trafiają do `project_calendar_sync`.
6. Błędy zapisują status i mogą generować powiadomienie admina.

### 6.13 Backup, restore, uninstall i cron

Cron obsługuje:

- tygodniowy backup,
- powiadomienia o brakujących godzinach,
- powiadomienia o deadline'ach,
- synchronizację Google Calendar,
- retry pipeline KSeF.

`Backup_Manager` tworzy paczkę z dumpem tabel ERP i ustawieniami, przywraca ZIP, importuje SQL, filtruje tabele ERP i usuwa stare backupy. `uninstall.php` odpowiada za cleanup przy usunięciu wtyczki.

---

## 7. REST API

Namespace: `erp-omd/v1`.

| Grupa | Endpointy | Cel |
|---|---|---|
| ACL i korekty | `/adjustments`, `/acl-audit`, `/acl-audit/export`, `/acl-config`, `/employees/{id}/acl` | audyt zmian, override'y uprawnień, korekty raportowe |
| Role/pracownicy/salary | `/roles`, `/employees`, `/employees/{id}/salary`, `/salary/{id}`, `/monthly-hours/{yyyy-mm}` | struktura zespołu i koszt pracy |
| Klienci i stawki | `/clients`, `/clients/{id}/rates`, `/client-rates/{id}` | CRM i cenniki |
| Dostawcy/faktury/KSeF | `/suppliers`, `/cost-invoices`, `/cost-invoices/{id}/moderate`, `/ksef/*` | koszty, importy, moderacja, sales inbox |
| Kosztorysy | `/estimates`, `/estimates/{id}/items`, `/estimate-items/{id}`, `/estimates/{id}/accept`, `/estimate-decision` | presales i decyzja klienta |
| Projekty | `/projects`, `/projects/{id}/notes`, `/projects/{id}/rates`, `/projects/{id}/costs`, `/projects/{id}/finance` | delivery i finanse |
| Czas | `/time`, `/time/{id}`, `/time/{id}/status` | time tracking i approval |
| Raporty/kalendarz | `/reports`, `/reports/export`, `/calendar` | controlling i eksporty |
| Portal klienta | `/client-portal/projects/{id}/finance` | bezpieczny widok finansów klienta |
| Operacyjne | `/alerts`, `/attachments`, `/meta`, `/system` | monitoring, uploady, słowniki, health check |

Każdy endpoint ma permission callback mapujący się na capability/ACL, sanitizer payloadu i warstwę repozytorium/serwisu.

---

## 8. Szablony i assets

### 8.1 Admin templates

| Plik | Widok | Workflow |
|---|---|---|
| `alerts.php` | Centrum alertów | Lista ryzyk i linki do encji. |
| `calendar.php` | Kalendarz agencji | Miesięczny widok wpisów/projektów i nawigacja. |
| `clients.php` | Klienci | CRUD klienta, stawki, widok 360, projekty i kosztorysy klienta. |
| `cost-invoices.php` | Dostawcy i koszty | Dostawcy, faktury kosztowe, KSeF moderation, inbox sprzedażowy, relacje projekt-dostawca, audit. |
| `dashboard.php` | Dashboard | Puls operacyjny, controlling miesiąca, skróty, top ryzyka. |
| `employees.php` | Pracownicy | CRUD pracownika, role, status, salary, ACL. |
| `estimates.php` | Kosztorysy | CRUD kosztorysu/pozycji, eksport, wysyłka linku, akceptacja. |
| `private-tasks.php` | Taski prywatne | Adminowy widok prywatnych tasków użytkowników. |
| `project-requests.php` | Wnioski | Moderacja, bulk actions, konwersja do projektu. |
| `projects.php` | Projekty | CRUD, merge, deadline, notatki, stawki, koszty, przychody, załączniki. |
| `reports.php` | Raporty | Filtry, warianty raportów, eksporty. |
| `roles.php` | Role | CRUD ról projektowych. |
| `settings.php` | Ustawienia | Mail, koszty stałe, powiadomienia, Google Calendar, KSeF. |
| `time-entries.php` | Czas pracy | Lista, filtry, inline update, bulk, statusy. |

### 8.2 Front templates

| Plik | Widok | Workflow |
|---|---|---|
| `login.php` | Login frontowy | Logowanie bez wejścia do wp-admin. |
| `worker-dashboard.php` | Panel pracownika | Dodanie/edycja czasu, wnioski, taski, własny rytm pracy. |
| `dashboard.php` / `front-manager.php` | Panel managera | Projekty, koszty, przychody, kosztorysy, approval, wnioski. |
| `client-dashboard.php` | Panel klienta | Podgląd projektów, notatki, załączniki, wnioski klienta. |
| `estimate-decision.php` | Decyzja kosztorysu | Akceptacja/odrzucenie oferty po tokenie. |

### 8.3 JavaScript

| Plik | Funkcje | Cel |
|---|---|---|
| `assets/js/admin.js` | narzędzia tabel, widoczność wariantów raportów, fixed costs, inline autosave, AJAX projektu, korekty kosztów, toasty | Przyspieszenie pracy admina i ograniczenie błędów formularzy. |
| `assets/js/front-manager.js` | sortowanie/porządkowanie widoków managera, inicjalizacja frontu | Lepsza obsługa panelu managera. |
| `assets/js/front-worker.js` | inicjalizacja panelu workera | Frontowa interaktywność pracownika. |
| `assets/js/front-shared.js` | współdzielony punkt na kod frontowy | Miejsce na wspólne zachowania frontu. |

### 8.4 CSS

`assets/css/admin.css` i `assets/css/front.css` definiują layout kart, formularzy, tabel, badge'y statusów, widoki raportów, dashboard i responsywność.

---

## 9. Zależności między modułami — reguły działania

1. **Employee + Salary → Time Entry → Finance**  
   Pracownik i historia salary są warunkiem policzenia kosztu wpisu czasu. Time entry utrwala snapshot, a finanse projektu używają snapshotu zamiast aktualnej pensji.

2. **Client Rate / Project Rate → Time Entry → Revenue**  
   Stawka projektowa nadpisuje stawkę klienta. Jeżeli jej brak, wpis czasu próbuje użyć stawki klienta. Brak stawek może generować alert.

3. **Estimate → Project → Revenue/Cost**  
   Zaakceptowany kosztorys może utworzyć lub powiązać projekt i przenieść pozycje do przychodów/kosztów projektu. Audit kosztorysu zapewnia historię decyzji.

4. **Project Request → Project**  
   Wniosek jest stagingiem pracy. Dopiero zaakceptowany i skonwertowany wniosek tworzy projekt, co pozwala kontrolować intake.

5. **Cost Invoice/KSeF → Project Cost → Financials**  
   Faktura kosztowa może stworzyć koszt projektu. Zmiana faktury musi zsynchronizować koszt i zapisać audyt.

6. **Project → Google Calendar**  
   Daty projektu i deadline są źródłem eventów. Sync table przechowuje event IDs i status, żeby aktualizacje były idempotentne.

7. **ACL/Capabilities → Admin/REST/Front**  
   Każdy kanał dostępu ma bramki: admin menu, REST permission callbacks, front guard. ACL override może doprecyzować dostęp bez zmiany roli WP.

8. **Reports Cache → Mutacje danych**  
   Zmiany encji operacyjnych powinny bumpować wersję cache, bo raporty zależą od projektów, czasu, kosztów, przychodów, salary i stawek.

9. **Alerts → Projects/Financials/Time/Settings**  
   Alerty nie są osobną encją stałą; są dynamicznie liczone z danych domenowych i ustawień.

---

## 10. Inwarianty i zasady bezpieczeństwa

- Dostęp do admina wymaga capabilities/ACL.
- Formularze wp-admin/front używają nonce i redirectów z notice.
- REST sanitizuje payloady per encja.
- Usunięcia krytycznych encji są ograniczane przez relacje lub wykonywane z efektami ubocznymi kontrolowanymi w runtime/service.
- Dane historyczne stawek i salary są niezbędne dla poprawnych raportów historycznych.
- Edycje finansowe powinny mieć audyt, szczególnie gdy wpływają na zamknięty miesiąc.
- Importy KSeF nie powinny automatycznie akceptować niepewnych dopasowań — trafiają do moderacji/retry.
- Nieaktywny pracownik nie powinien korzystać z frontu.
- Klient frontowy musi widzieć wyłącznie swoje projekty/dane.

---

## 11. Katalog funkcji i metod — pełna lista z odpowiedzialnością

Opis w tej sekcji obejmuje wszystkie funkcje/metody wykryte w plikach PHP oraz funkcje JS. Dla metod pomocniczych użyto zwięzłych opisów wynikających z nazwy, klasy i kontekstu modułu; szczegółowe reguły biznesowe znajdują się w sekcjach 5–10.


### `erp-omd/erp-omd.php` — `(funkcje globalne)`

Odpowiedzialność pliku: funkcje globalne lub ekran proceduralny.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `erp_omd()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `erp_omd_reports_cache_bump_version()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `erp_omd_invalidate_plugin_opcache()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |

### `erp-omd/includes/class-admin-runtime.php` — `ERP_OMD_Admin`

Odpowiedzialność pliku: komponent `ERP_OMD_Admin` w warstwie `erp-omd/includes`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `__construct()` | Inicjalizuje zależności klasy i przygotowuje obiekt do obsługi workflow. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `register_hooks()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `register_menu()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `register_acl_submenu_page()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `can_view_admin_page()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `enforce_admin_page_acl_gatekeeper()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `first_accessible_erp_admin_slug()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `enqueue_assets()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `add_submenu_separator()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `with_kolko_menu_badge()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `get_kolko_notifications_summary()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `count_unhandled_projects_for_kolko()` | Zlicza rekordy/metyki używane w dashboardach, filtrach lub paginacji. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `build_project_kolko_signature()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `latest_client_note_id_for_project()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `acknowledge_project_kolko_notification()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `get_project_kolko_acknowledgements()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `is_client_user()` | Sprawdza warunek logiczny/status/typ danych. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `can_view_kolko_notifications()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_forms()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `render_dashboard()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `render_private_tasks()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `normalize_admin_private_tasks_filter()` | Normalizuje format wartości do postaci używanej wewnętrznie przez system. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `get_admin_private_tasks()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_admin_private_task_save()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_admin_private_task_toggle()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_admin_private_task_delete()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_admin_private_task_update()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_admin_private_tasks_bulk_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `resolve_dashboard_controlling_result()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `count_dashboard_active_projects_for_month()` | Zlicza rekordy/metyki używane w dashboardach, filtrach lub paginacji. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `count_dashboard_projects_by_status_for_month()` | Zlicza rekordy/metyki używane w dashboardach, filtrach lub paginacji. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `render_roles()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `render_employees()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `render_clients()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `render_estimates()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `render_projects()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `render_time_entries()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `is_query_filter()` | Sprawdza warunek logiczny/status/typ danych. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `render_settings()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `render_alerts()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `render_calendar()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `render_reports()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `render_project_requests()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `is_client_project_request()` | Sprawdza warunek logiczny/status/typ danych. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `render_cost_invoices()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_role_save()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_role_delete()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_employee_save()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_employee_acl_overrides_save()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_project_request_status_update_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_project_request_conversion_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_project_request_delete_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_project_requests_bulk_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_inline_employee_update_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_employee_active_toggle()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_employee_password_change()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_salary_save()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_salary_delete()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_client_save()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_client_active_toggle()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_client_delete()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_client_rate_save()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_client_rate_delete()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_supplier_save()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_supplier_delete()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_cost_invoice_save()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_cost_invoice_delete()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_cost_invoice_bulk_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `normalize_certificate_to_pem()` | Normalizuje format wartości do postaci używanej wewnętrznie przez system. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `select_ksef_public_certificate_for_environment()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `normalize_ksef_subject_type_for_api()` | Normalizuje format wartości do postaci używanej wewnętrznie przez system. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `append_ksef_export_stage_hint()` | Dopisuje wpis audytu, notatkę, hint lub fragment danych. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_ksef_queue_moderation_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_ksef_queue_bulk_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_import_ksef_sales_xml_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `read_ksef_xml_from_request()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_import_ksef_cost_xml_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `read_ksef_xml_batch_from_request()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_attach_ksef_sales_invoice_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `collect_cost_invoice_items_from_post()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `delete_cost_invoice_with_side_effects()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `redirect_cost_invoice_page()` | Wykonuje kontrolowany redirect z parametrami/notice. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_estimate_save()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `collect_initial_estimate_items()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_estimate_delete()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_estimate_duplicate()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_estimate_item_save()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_estimate_item_delete()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_estimate_accept()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_send_estimate_client_decision_link()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_estimate_export()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_report_export()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_project_save()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_inline_project_update_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_project_merge_preview_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_project_merge_execute_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_mark_project_deadline_completed_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_inline_project_update_ajax()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_project_duplicate()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `duplicate_project_and_rebuild()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_project_active_toggle()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_project_delete()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_project_note_add()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_project_note_delete()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_project_rate_save()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_project_rate_delete()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_project_cost_save()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_attach_sales_invoice_to_project()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_map_project_cost_to_invoice()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_attach_cost_invoice_to_project()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `normalize_supplier_categories()` | Normalizuje format wartości do postaci używanej wewnętrznie przez system. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `validate_supplier_contact_fields()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `sync_attached_cost_invoice_to_project_cost()` | Synchronizuje role, powiązania, dane zewnętrzne lub tabele zależne. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_project_revenues_bulk_delete()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_project_costs_bulk_delete()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_project_cost_delete()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_time_entry_save()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_inline_time_entry_update_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_time_status_change()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_time_entry_delete()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_time_entries_bulk_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_clients_bulk_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_projects_bulk_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_estimates_bulk_action()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_settings_save()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_google_calendar_connect()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_google_calendar_disconnect()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_google_calendar_sync_now()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_google_calendar_fetch_calendars()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_google_calendar_oauth_callback()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `google_calendar_redirect_uri()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `normalize_google_calendar_redirect_uri_v2()` | Normalizuje format wartości do postaci używanej wewnętrznie przez system. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `encrypt_option_value()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `decrypt_option_value()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `masked_secret()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `normalize_fixed_monthly_cost_items()` | Normalizuje format wartości do postaci używanej wewnętrznie przez system. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `sum_fixed_monthly_cost_for_date_range()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_attachment_add()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `handle_attachment_delete()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `require_capability()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `sanitize_acl_override_map()` | Czyści i normalizuje dane wejściowe przed zapisem lub dalszym przetwarzaniem. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `resolve_current_salary_row()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `build_monthly_performance_metrics()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `build_client_profit_totals()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `index_alerts_by_entity()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `account_type_label()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `active_status_label()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `project_status_label()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `billing_type_label()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `time_status_label()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `resolve_project_deadline_status()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `project_deadline_status_label()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `status_badge_class()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `is_project_cost_locked_by_status()` | Sprawdza warunek logiczny/status/typ danych. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `render_alert_icons()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `missing_hours_notification_defaults()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `estimate_client_mail_defaults()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `estimate_client_thank_you_mail_defaults()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `estimate_internal_accept_mail_defaults()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `replace_mail_tokens()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `ensure_estimate_decision_token()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `estimate_client_link_state()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `build_project_cost_description_for_invoice()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `build_estimate_summary_table_html()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `is_valid_month_string()` | Sprawdza warunek logiczny/status/typ danych. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `redirect_with_notice()` | Wykonuje kontrolowany redirect z parametrami/notice. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `render_notice()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `sync_wp_role()` | Synchronizuje role, powiązania, dane zewnętrzne lub tabele zależne. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |
| `sync_client_front_user_assignment()` | Synchronizuje role, powiązania, dane zewnętrzne lub tabele zależne. | Umożliwia operatorom wykonanie procesu w wp-admin z kontrolą uprawnień i komunikatami. |

### `erp-omd/includes/class-autoloader.php` — `ERP_OMD_Autoloader`

Odpowiedzialność pliku: komponent `ERP_OMD_Autoloader` w warstwie `erp-omd/includes`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `register()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `autoload()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |

### `erp-omd/includes/class-backup-manager.php` — `ERP_OMD_Backup_Manager`

Odpowiedzialność pliku: komponent `ERP_OMD_Backup_Manager` w warstwie `erp-omd/includes`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `run_backup_bundle()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `restore_backup_bundle_from_zip()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `build_database_dump()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `filter_erp_tables()` | Filtruje dane według uprawnień, zakresu lub reguł biznesowych. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `build_settings_export_payload()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `import_settings_payload()` | Importuje dane z pliku/zewnętrznego źródła. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `import_sql_dump()` | Importuje dane z pliku/zewnętrznego źródła. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `find_zip_file_by_extension()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `prune_old_backups()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `database_prefix()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |

### `erp-omd/includes/class-capabilities.php` — `ERP_OMD_Capabilities`

Odpowiedzialność pliku: komponent `ERP_OMD_Capabilities` w warstwie `erp-omd/includes`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `register_hooks()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `get_capabilities()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `activate()` | Obsługuje lifecycle pluginu: aktywację, dezaktywację, migrację lub start modułów. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `deactivate()` | Obsługuje lifecycle pluginu: aktywację, dezaktywację, migrację lub start modułów. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `register_roles()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |

### `erp-omd/includes/class-cron-manager.php` — `ERP_OMD_Cron_Manager`

Odpowiedzialność pliku: komponent `ERP_OMD_Cron_Manager` w warstwie `erp-omd/includes`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `register_hooks()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `activate()` | Obsługuje lifecycle pluginu: aktywację, dezaktywację, migrację lub start modułów. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `deactivate()` | Obsługuje lifecycle pluginu: aktywację, dezaktywację, migrację lub start modułów. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `register_weekly_schedule()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `schedule_events()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `run_weekly_backup()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `run_missing_hours_notifications()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `run_project_deadline_notifications()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `run_google_calendar_sync()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `run_ksef_retry_pipeline()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `notification_settings()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `is_employee_notifications_active()` | Sprawdza warunek logiczny/status/typ danych. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `is_notification_due()` | Sprawdza warunek logiczny/status/typ danych. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `restore_backup_bundle_from_zip()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `import_sql_dump()` | Importuje dane z pliku/zewnętrznego źródła. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `render_template()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |

### `erp-omd/includes/class-front-estimate-decision-screen.php` — `(funkcje globalne)`

Odpowiedzialność pliku: funkcje globalne lub ekran proceduralny.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `handle_request()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `finalize_acceptance()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `send_thank_you_mail()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `send_acceptance_notification_to_agency()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `append_accept_note_to_project_history()` | Dopisuje wpis audytu, notatkę, hint lub fragment danych. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `resolve_note_author_user_id()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `build_summary_table_html()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `resolve_state()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `invalidate_token()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |

### `erp-omd/includes/class-frontend-runtime.php` — `ERP_OMD_Frontend`

Odpowiedzialność pliku: komponent `ERP_OMD_Frontend` w warstwie `erp-omd/includes`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `__construct()` | Inicjalizuje zależności klasy i przygotowuje obiekt do obsługi workflow. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `register_rewrite_rules()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `register_hooks()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `block_inactive_employee_login()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `enforce_active_employee_session()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `redirect_front_users_from_admin()` | Wykonuje kontrolowany redirect z parametrami/notice. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `filter_login_redirect()` | Filtruje dane według uprawnień, zakresu lub reguł biznesowych. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `register_query_vars()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `handle_front_request()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `front_url()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `handle_login_screen()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `process_login()` | Przetwarza wieloetapowy request lub kolejkę workflow. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `handle_logout()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `redirect_to_login()` | Wykonuje kontrolowany redirect z parametrami/notice. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `guard_dashboard_access()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `should_hide_admin_for_user()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `resolve_dashboard_url_for_user()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `user_has_front_capability()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `handle_client_screen()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `is_front_employee_inactive()` | Sprawdza warunek logiczny/status/typ danych. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `render_login_screen()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `handle_worker_screen()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `handle_manager_screen()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `process_worker_request()` | Przetwarza wieloetapowy request lub kolejkę workflow. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `process_client_request()` | Przetwarza wieloetapowy request lub kolejkę workflow. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `process_manager_request()` | Przetwarza wieloetapowy request lub kolejkę workflow. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `save_worker_time_entry()` | Zapisuje dane formularza, opcję lub stan kolejki. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `delete_worker_time_entry()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `render_worker_dashboard()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `build_recent_worker_templates()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `resolve_worker_focus_date_range()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `filter_worker_entries_by_focus()` | Filtruje dane według uprawnień, zakresu lub reguł biznesowych. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `get_calendar_navigation()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `resolve_selected_day()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `load_selected_day_entries()` | Ładuje kolekcję danych potrzebnych do widoku lub procesu. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `summarize_selected_day_entries()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `render_manager_dashboard()` | Renderuje widok lub fragment UI na podstawie danych przygotowanych przez runtime. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `change_manager_time_entry_status()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `create_manager_estimate()` | Tworzy nowy rekord lub obiekt workflow. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `update_manager_estimate_status()` | Aktualizuje istniejący rekord lub status. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `update_manager_estimate_item_inline()` | Aktualizuje istniejący rekord lub status. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `save_manager_estimate_items()` | Zapisuje dane formularza, opcję lub stan kolejki. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `update_manager_project_status()` | Aktualizuje istniejący rekord lub status. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `add_manager_project_cost()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `add_manager_project_revenue()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `accept_manager_estimate()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `create_manager_project_request()` | Tworzy nowy rekord lub obiekt workflow. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `create_worker_project_request()` | Tworzy nowy rekord lub obiekt workflow. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `collect_manager_estimate_line_items()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `validate_manager_estimate_line_items()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `export_manager_estimate_csv()` | Przygotowuje eksport danych. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `process_project_request_action()` | Przetwarza wieloetapowy request lub kolejkę workflow. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `get_worker_roles()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `redirect_worker_with_notice()` | Wykonuje kontrolowany redirect z parametrami/notice. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `redirect_client_with_notice()` | Wykonuje kontrolowany redirect z parametrami/notice. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `redirect_manager_with_notice()` | Wykonuje kontrolowany redirect z parametrami/notice. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `load_managed_projects()` | Ładuje kolekcję danych potrzebnych do widoku lub procesu. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `load_estimates_for_projects()` | Ładuje kolekcję danych potrzebnych do widoku lub procesu. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `get_manager_available_clients()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `load_visible_manager_estimates()` | Ładuje kolekcję danych potrzebnych do widoku lub procesu. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `find_estimate_in_collection()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `load_manager_approval_queue()` | Ładuje kolekcję danych potrzebnych do widoku lub procesu. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `summarize_queue_entries()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `find_project_in_collection()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `load_visible_project_requests()` | Ładuje kolekcję danych potrzebnych do widoku lub procesu. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `can_review_project_request()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `project_status_label()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `resolve_default_admin_manager_employee_id()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `billing_type_label()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `create_client_project_note()` | Tworzy nowy rekord lub obiekt workflow. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `create_client_project_request()` | Tworzy nowy rekord lub obiekt workflow. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `delete_client_project_attachment()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `handle_client_project_attachment_upload()` | Obsługuje akcję formularza/requestu, waliduje kontekst, wywołuje serwisy/repozytoria i zwraca redirect/odpowiedź. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `collect_client_dashboard_args()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `get_private_tasks_for_user()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `save_worker_private_task()` | Zapisuje dane formularza, opcję lub stan kolejki. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `toggle_worker_private_task_completed()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `find_request_in_collection()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `send_front_headers()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |
| `is_front_url()` | Sprawdza warunek logiczny/status/typ danych. | Umożliwia pracownikom, managerom lub klientom wykonanie procesu poza wp-admin. |

### `erp-omd/includes/class-installer.php` — `ERP_OMD_Installer`

Odpowiedzialność pliku: komponent `ERP_OMD_Installer` w warstwie `erp-omd/includes`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `activate()` | Obsługuje lifecycle pluginu: aktywację, dezaktywację, migrację lub start modułów. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `deactivate()` | Obsługuje lifecycle pluginu: aktywację, dezaktywację, migrację lub start modułów. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `maybe_upgrade()` | Obsługuje lifecycle pluginu: aktywację, dezaktywację, migrację lub start modułów. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `migrate()` | Obsługuje lifecycle pluginu: aktywację, dezaktywację, migrację lub start modułów. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `maybe_cleanup_legacy_time_entry_indexes()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `maybe_allow_nullable_project_request_requester_employee_id()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `maybe_backfill_acl_audit_option_to_table()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `drop_legacy_time_entry_unique_indexes()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `add_foreign_key_if_missing()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `add_column_if_missing()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `add_index_if_missing()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |

### `erp-omd/includes/class-plugin.php` — `ERP_OMD_Plugin`

Odpowiedzialność pliku: komponent `ERP_OMD_Plugin` w warstwie `erp-omd/includes`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `__construct()` | Inicjalizuje zależności klasy i przygotowuje obiekt do obsługi workflow. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `boot()` | Obsługuje lifecycle pluginu: aktywację, dezaktywację, migrację lub start modułów. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `sync_project_calendar_after_save()` | Synchronizuje role, powiązania, dane zewnętrzne lub tabele zależne. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `sync_project_calendar_after_delete()` | Synchronizuje role, powiązania, dane zewnętrzne lub tabele zależne. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `track_user_login()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `filter_wp_mail_from()` | Filtruje dane według uprawnień, zakresu lub reguł biznesowych. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |

### `erp-omd/includes/class-rest-api.php` — `ERP_OMD_REST_API`

Odpowiedzialność pliku: komponent `ERP_OMD_REST_API` w warstwie `erp-omd/includes`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `__construct()` | Inicjalizuje zależności klasy i przygotowuje obiekt do obsługi workflow. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `register_hooks()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `register_routes()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `register_employee_routes()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `register_client_routes()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `register_supplier_routes()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `register_estimate_routes()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `register_project_routes()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `register_time_routes()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `register_report_routes()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `register_hardening_routes()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `can_manage_roles()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `can_manage_employees()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `can_manage_salary()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `can_manage_clients()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `can_manage_projects()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `can_manage_time()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `can_approve_time()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `can_access_reports()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `can_manage_settings()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `can_access_acl_audit()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_roles()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_role()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `create_role()` | Tworzy nowy rekord lub obiekt workflow. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `update_role()` | Aktualizuje istniejący rekord lub status. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `delete_role()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_employees()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_employee()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `create_employee()` | Tworzy nowy rekord lub obiekt workflow. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `update_employee()` | Aktualizuje istniejący rekord lub status. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `delete_employee()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_salary_history()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_employee_acl()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `update_employee_acl()` | Aktualizuje istniejący rekord lub status. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `reset_employee_acl()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_acl_audit()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_acl_config()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `export_acl_audit_csv()` | Przygotowuje eksport danych. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `create_salary_history()` | Tworzy nowy rekord lub obiekt workflow. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_salary_history()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `update_salary_history()` | Aktualizuje istniejący rekord lub status. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `delete_salary_history()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_monthly_hours_suggestion()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_clients()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_suppliers()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_supplier()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `create_supplier()` | Tworzy nowy rekord lub obiekt workflow. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `update_supplier()` | Aktualizuje istniejący rekord lub status. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `delete_supplier()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_cost_invoices()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_cost_invoice()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `create_cost_invoice()` | Tworzy nowy rekord lub obiekt workflow. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `update_cost_invoice()` | Aktualizuje istniejący rekord lub status. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `delete_cost_invoice()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `moderate_cost_invoice()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_cost_invoice_audit()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `import_ksef_documents()` | Importuje dane z pliku/zewnętrznego źródła. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_ksef_moderation_queue()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `moderate_ksef_queue_entry()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `bulk_moderate_ksef_queue_entries()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_ksef_sales_documents()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `attach_ksef_sales_document()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `import_ksef_sales_xml()` | Importuje dane z pliku/zewnętrznego źródła. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `import_ksef_cost_xml()` | Importuje dane z pliku/zewnętrznego źródła. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_client()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `create_client()` | Tworzy nowy rekord lub obiekt workflow. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `update_client()` | Aktualizuje istniejący rekord lub status. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `delete_client()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_client_rates()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `create_client_rate()` | Tworzy nowy rekord lub obiekt workflow. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_client_rate()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `update_client_rate()` | Aktualizuje istniejący rekord lub status. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `delete_client_rate()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_estimates()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_estimate()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `create_estimate()` | Tworzy nowy rekord lub obiekt workflow. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `update_estimate()` | Aktualizuje istniejący rekord lub status. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `delete_estimate()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_estimate_items()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `create_estimate_item()` | Tworzy nowy rekord lub obiekt workflow. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_estimate_item()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `update_estimate_item()` | Aktualizuje istniejący rekord lub status. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `delete_estimate_item()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `accept_estimate()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_estimate_client_decision_state()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `submit_estimate_client_decision()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_projects()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_project()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `create_project()` | Tworzy nowy rekord lub obiekt workflow. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `update_project()` | Aktualizuje istniejący rekord lub status. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `delete_project()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_project_notes()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `create_project_note()` | Tworzy nowy rekord lub obiekt workflow. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_project_rates()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `create_project_rate()` | Tworzy nowy rekord lub obiekt workflow. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_project_rate()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `update_project_rate()` | Aktualizuje istniejący rekord lub status. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `delete_project_rate()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_project_costs()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `create_project_cost()` | Tworzy nowy rekord lub obiekt workflow. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_project_cost()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `update_project_cost()` | Aktualizuje istniejący rekord lub status. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `delete_project_cost()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_project_finance()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_time_entries()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_time_entry()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `create_time_entry()` | Tworzy nowy rekord lub obiekt workflow. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `update_time_entry()` | Aktualizuje istniejący rekord lub status. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `delete_time_entry()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `change_time_entry_status()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_reports()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `export_report_definition()` | Przygotowuje eksport danych. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_calendar()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_client_portal_project_finance()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_alerts()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `list_attachments()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_attachment()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `create_attachment()` | Tworzy nowy rekord lub obiekt workflow. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `delete_attachment()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_meta()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_system_status()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `find_or_error()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `sanitize_role_payload()` | Czyści i normalizuje dane wejściowe przed zapisem lub dalszym przetwarzaniem. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `sanitize_employee_payload()` | Czyści i normalizuje dane wejściowe przed zapisem lub dalszym przetwarzaniem. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `sanitize_salary_payload()` | Czyści i normalizuje dane wejściowe przed zapisem lub dalszym przetwarzaniem. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `sanitize_client_payload()` | Czyści i normalizuje dane wejściowe przed zapisem lub dalszym przetwarzaniem. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `sanitize_supplier_payload()` | Czyści i normalizuje dane wejściowe przed zapisem lub dalszym przetwarzaniem. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `sanitize_cost_invoice_payload()` | Czyści i normalizuje dane wejściowe przed zapisem lub dalszym przetwarzaniem. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `sanitize_ksef_document_payload()` | Czyści i normalizuje dane wejściowe przed zapisem lub dalszym przetwarzaniem. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `sanitize_estimate_payload()` | Czyści i normalizuje dane wejściowe przed zapisem lub dalszym przetwarzaniem. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `sanitize_estimate_item_payload()` | Czyści i normalizuje dane wejściowe przed zapisem lub dalszym przetwarzaniem. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `sanitize_project_payload()` | Czyści i normalizuje dane wejściowe przed zapisem lub dalszym przetwarzaniem. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `sanitize_project_cost_payload()` | Czyści i normalizuje dane wejściowe przed zapisem lub dalszym przetwarzaniem. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `sanitize_time_entry_payload()` | Czyści i normalizuje dane wejściowe przed zapisem lub dalszym przetwarzaniem. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `sanitize_acl_override_map()` | Czyści i normalizuje dane wejściowe przed zapisem lub dalszym przetwarzaniem. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `current_user_can_acl()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `validate_acl_update_guardrails()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `sanitize_cost_invoice_items()` | Czyści i normalizuje dane wejściowe przed zapisem lub dalszym przetwarzaniem. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `attach_items_to_cost_invoice()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `csv_escape()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `resolve_estimate_by_token()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `invalidate_estimate_token()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `is_supplier_category_allowed()` | Sprawdza warunek logiczny/status/typ danych. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_supplier_category_dictionary()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `validate_supplier_contact_fields()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `current_employee_id()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `is_query_filter()` | Sprawdza warunek logiczny/status/typ danych. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `request_param_or_default()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `resolve_pagination()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `paginated_response()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `build_reports_cache_key()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `get_reports_cache()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `set_reports_cache()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `is_project_cost_locked_for_non_admin()` | Sprawdza warunek logiczny/status/typ danych. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `is_month_locked_for_current_user()` | Sprawdza warunek logiczny/status/typ danych. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `is_month_locked_for_admin()` | Sprawdza warunek logiczny/status/typ danych. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `readiness_signals_for_month()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `is_project_relevant_for_month()` | Sprawdza warunek logiczny/status/typ danych. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `project_overlaps_month()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `sanitize_adjustment_reason()` | Czyści i normalizuje dane wejściowe przed zapisem lub dalszym przetwarzaniem. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `resolve_adjustment_type()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `log_adjustment_audit()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `sync_wp_role()` | Synchronizuje role, powiązania, dane zewnętrzne lub tabele zależne. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `entity_exists()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |
| `erp_omd_month_from_date()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Udostępnia funkcję systemu przez API dla integracji lub aplikacji zewnętrznych. |

### `erp-omd/includes/contracts/interface-ksef-auth-provider.php` — `ERP_OMD_KSeF_Auth_Provider_Interface`

Odpowiedzialność pliku: komponent `ERP_OMD_KSeF_Auth_Provider_Interface` w warstwie `erp-omd/includes/contracts`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `get_challenge()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `authenticate_with_ksef_token()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `get_auth_status()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `redeem_token()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `refresh_access_token()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |
| `ensure_access_token()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Wspiera spójność procesu ERP przez kontrolowany odczyt/zapis, walidację, widok lub integrację w module. |

### `erp-omd/includes/repositories/class-acl-audit-repository.php` — `ERP_OMD_Acl_Audit_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Acl_Audit_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `insert()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `all()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-attachment-repository.php` — `ERP_OMD_Attachment_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Attachment_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `for_entity()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `create()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `delete()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `count_links_for_attachment()` | Zlicza rekordy/metyki używane w dashboardach, filtrach lub paginacji. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `count_for_entity_label()` | Zlicza rekordy/metyki używane w dashboardach, filtrach lub paginacji. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-client-rate-repository.php` — `ERP_OMD_Client_Rate_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Client_Rate_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `history_table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `for_client()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find_effective_rate()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `upsert()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `update()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `delete()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `record_history_version()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-client-repository.php` — `ERP_OMD_Client_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Client_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `all()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find_paged()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `count_filtered()` | Zlicza rekordy/metyki używane w dashboardach, filtrach lub paginacji. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find_by_nip()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `create()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `update()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `delete()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `deactivate()` | Obsługuje lifecycle pluginu: aktywację, dezaktywację, migrację lub start modułów. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `set_status()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `nip_exists()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-cost-invoice-audit-repository.php` — `ERP_OMD_Cost_Invoice_Audit_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Cost_Invoice_Audit_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `insert_many()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `for_invoice()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-cost-invoice-item-repository.php` — `ERP_OMD_Cost_Invoice_Item_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Cost_Invoice_Item_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `for_invoice()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `replace_for_invoice()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `delete_for_invoice()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-cost-invoice-repository.php` — `ERP_OMD_Cost_Invoice_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Cost_Invoice_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `list()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `project_supplier_pairs()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `for_supplier()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find_by_ksef_reference()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find_by_supplier_and_invoice_number()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `create()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `update()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `delete()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-employee-repository.php` — `ERP_OMD_Employee_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Employee_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `pivot_table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `all()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find_by_user_id()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `create()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `update()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `deactivate()` | Obsługuje lifecycle pluginu: aktywację, dezaktywację, migrację lub start modułów. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `set_status()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `user_exists()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `role_ids()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `sync_roles()` | Synchronizuje role, powiązania, dane zewnętrzne lub tabele zależne. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-estimate-audit-repository.php` — `ERP_OMD_Estimate_Audit_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Estimate_Audit_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `for_estimate()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `log()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-estimate-item-repository.php` — `ERP_OMD_Estimate_Item_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Estimate_Item_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `for_estimate()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `has_column()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `create()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `update()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `delete()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-estimate-repository-v2.php` — `ERP_OMD_Estimate_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Estimate_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `all()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find_paged()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `count_filtered()` | Zlicza rekordy/metyki używane w dashboardach, filtrach lub paginacji. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `create()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `update()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `mark_accepted()` | Oznacza encję wybranym statusem/flagą. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `mark_sent_to_client()` | Oznacza encję wybranym statusem/flagą. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `save_client_decision_note()` | Zapisuje dane formularza, opcję lub stan kolejki. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `delete()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-omd-adjustment-audit-repository.php` — `ERP_OMD_Adjustment_Audit_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Adjustment_Audit_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `create()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `all()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-project-calendar-sync-repository.php` — `ERP_OMD_Project_Calendar_Sync_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Project_Calendar_Sync_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find_by_project_id()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `upsert()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `delete_by_project_id()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `all_pending()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-project-cost-repository.php` — `ERP_OMD_Project_Cost_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Project_Cost_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `for_project()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `for_month()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `sum_by_project_and_month_in_date_range()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `create()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `update()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `delete()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-project-financial-repository.php` — `ERP_OMD_Project_Financial_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Project_Financial_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find_by_project()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find_by_projects()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `upsert()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-project-note-repository.php` — `ERP_OMD_Project_Note_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Project_Note_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `for_project()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `create()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `delete()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-project-rate-repository.php` — `ERP_OMD_Project_Rate_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Project_Rate_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `history_table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `for_project()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find_by_project_role()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find_effective_rate()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `upsert()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `delete()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `record_history_version()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-project-repository.php` — `ERP_OMD_Project_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Project_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `managers_table_name()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `all()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find_paged()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `count_filtered()` | Zlicza rekordy/metyki używane w dashboardach, filtrach lub paginacji. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `ids_managed_by_employee()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `manager_ids()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `sync_manager_ids()` | Synchronizuje role, powiązania, dane zewnętrzne lub tabele zależne. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find_by_estimate_id()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `create()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `update()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `projects_table_has_column()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `delete()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `deactivate()` | Obsługuje lifecycle pluginu: aktywację, dezaktywację, migrację lub start modułów. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `set_status()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `enrich_projects()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `enrich_project()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-project-request-repository.php` — `ERP_OMD_Project_Request_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Project_Request_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `all()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find_paged()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `count_filtered()` | Zlicza rekordy/metyki używane w dashboardach, filtrach lub paginacji. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `create()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `update()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `update_status()` | Aktualizuje istniejący rekord lub status. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `mark_converted()` | Oznacza encję wybranym statusem/flagą. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `delete()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-project-revenue-repository.php` — `ERP_OMD_Project_Revenue_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Project_Revenue_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `for_project()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `create()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `update()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `delete()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-role-repository.php` — `ERP_OMD_Role_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Role_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `all()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `create()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `update()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `delete()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `slug_exists()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-salary-history-repository.php` — `ERP_OMD_Salary_History_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Salary_History_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `for_employee()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `for_employees()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `create()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `update()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `delete()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `overlaps()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-supplier-repository.php` — `ERP_OMD_Supplier_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Supplier_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `all_active()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find_by_nip()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `create()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `update()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `delete()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/repositories/class-time-entry-repository.php` — `ERP_OMD_Time_Entry_Repository`

Odpowiedzialność pliku: komponent `ERP_OMD_Time_Entry_Repository` w warstwie `erp-omd/includes/repositories`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `table_name()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `all()` | Metoda repozytorium zwracająca nazwę tabeli lub kolekcję rekordów dla relacji domenowej. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find_paged()` | Wyszukuje rekord po identyfikatorze lub unikalnym kluczu. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `count_filtered()` | Zlicza rekordy/metyki używane w dashboardach, filtrach lub paginacji. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `find()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `create()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `update()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `delete()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `count_for_project_by_statuses()` | Zlicza rekordy/metyki używane w dashboardach, filtrach lub paginacji. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `latest_entry_dates_by_employee()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |
| `duplicate_exists()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Zapewnia trwałość danych domenowych potrzebnych przez serwisy, widoki i raporty. |

### `erp-omd/includes/services/class-acl-service.php` — `ERP_OMD_Acl_Service`

Odpowiedzialność pliku: komponent `ERP_OMD_Acl_Service` w warstwie `erp-omd/includes/services`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `can_user()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `can_view_menu_page()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `resolve_override()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `append_acl_audit_log()` | Dopisuje wpis audytu, notatkę, hint lub fragment danych. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |

### `erp-omd/includes/services/class-alert-service.php` — `ERP_OMD_Alert_Service`

Odpowiedzialność pliku: komponent `ERP_OMD_Alert_Service` w warstwie `erp-omd/includes/services`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `__construct()` | Inicjalizuje zależności klasy i przygotowuje obiekt do obsługi workflow. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `margin_threshold()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `all_alerts()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `google_calendar_auth_alerts()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `project_alerts()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `missing_time_entry_alerts()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `alerts_for_entity()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `resolve_margin_threshold()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `make_alert()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |

### `erp-omd/includes/services/class-client-portal-service.php` — `ERP_OMD_Client_Portal_Service`

Odpowiedzialność pliku: komponent `ERP_OMD_Client_Portal_Service` w warstwie `erp-omd/includes/services`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `__construct()` | Inicjalizuje zależności klasy i przygotowuje obiekt do obsługi workflow. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_project_finance_view()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |

### `erp-omd/includes/services/class-client-project-service.php` — `ERP_OMD_Client_Project_Service`

Odpowiedzialność pliku: komponent `ERP_OMD_Client_Project_Service` w warstwie `erp-omd/includes/services`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `__construct()` | Inicjalizuje zależności klasy i przygotowuje obiekt do obsługi workflow. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `prepare_client()` | Buduje payload zapisu lub strukturę danych domenowych na podstawie requestu. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `validate_client()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `validate_client_rate()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `validate_project()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `prepare_project()` | Buduje payload zapisu lub strukturę danych domenowych na podstawie requestu. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `prepare_manager_ids()` | Buduje payload zapisu lub strukturę danych domenowych na podstawie requestu. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `resolve_default_main_manager_id()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `validate_billing_policy()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `validate_status_transition()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `has_final_sales_invoice_for_project()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `validate_effective_dates()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `normalize_nip()` | Normalizuje format wartości do postaci używanej wewnętrznie przez system. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `normalize_phone()` | Normalizuje format wartości do postaci używanej wewnętrznie przez system. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `is_valid_phone()` | Sprawdza warunek logiczny/status/typ danych. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `normalize_postal_code()` | Normalizuje format wartości do postaci używanej wewnętrznie przez system. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `normalize_country()` | Normalizuje format wartości do postaci używanej wewnętrznie przez system. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `normalize_margin_threshold()` | Normalizuje format wartości do postaci używanej wewnętrznie przez system. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `valid_date()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |

### `erp-omd/includes/services/class-cost-invoice-workflow-service.php` — `ERP_OMD_Cost_Invoice_Workflow_Service`

Odpowiedzialność pliku: komponent `ERP_OMD_Cost_Invoice_Workflow_Service` w warstwie `erp-omd/includes/services`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `__construct()` | Inicjalizuje zależności klasy i przygotowuje obiekt do obsługi workflow. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `allowed_statuses()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `can_transition()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `validate_invoice_data()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_critical_audit_entries()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `create_invoice()` | Tworzy nowy rekord lub obiekt workflow. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `update_invoice()` | Aktualizuje istniejący rekord lub status. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `normalize_scalar()` | Normalizuje format wartości do postaci używanej wewnętrznie przez system. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `normalize_status()` | Normalizuje format wartości do postaci używanej wewnętrznie przez system. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `now()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `is_valid_iso_date()` | Sprawdza warunek logiczny/status/typ danych. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `is_totals_mismatch()` | Sprawdza warunek logiczny/status/typ danych. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `ksef_reference_exists_on_other_invoice()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `load_supplier_invoices()` | Ładuje kolekcję danych potrzebnych do widoku lub procesu. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `supplier_exists()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `project_exists()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `replace_invoice_items_if_supported()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |

### `erp-omd/includes/services/class-employee-service.php` — `ERP_OMD_Employee_Service`

Odpowiedzialność pliku: komponent `ERP_OMD_Employee_Service` w warstwie `erp-omd/includes/services`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `__construct()` | Inicjalizuje zależności klasy i przygotowuje obiekt do obsługi workflow. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `validate_employee()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `validate_salary()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `prepare_salary_payload()` | Buduje payload zapisu lub strukturę danych domenowych na podstawie requestu. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `valid_date()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |

### `erp-omd/includes/services/class-estimate-service.php` — `ERP_OMD_Estimate_Service`

Odpowiedzialność pliku: komponent `ERP_OMD_Estimate_Service` w warstwie `erp-omd/includes/services`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `__construct()` | Inicjalizuje zależności klasy i przygotowuje obiekt do obsługi workflow. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `validate_estimate()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `validate_item()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `calculate_totals()` | Oblicza wartości finansowe, czasowe lub pomocnicze. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `accept()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `copy_revenues_to_project()` | Kopiuje dane między modułami, np. z kosztorysu do projektu. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `copy_internal_costs_to_project()` | Kopiuje dane między modułami, np. z kosztorysu do projektu. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `resolve_actor_user_id()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `log_audit()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |

### `erp-omd/includes/services/class-google-calendar-sync-service.php` — `ERP_OMD_Google_Calendar_Sync_Service`

Odpowiedzialność pliku: komponent `ERP_OMD_Google_Calendar_Sync_Service` w warstwie `erp-omd/includes/services`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `__construct()` | Inicjalizuje zależności klasy i przygotowuje obiekt do obsługi workflow. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `sync_all_projects()` | Synchronizuje role, powiązania, dane zewnętrzne lub tabele zależne. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `list_calendars()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `sync_project_events()` | Synchronizuje role, powiązania, dane zewnętrzne lub tabele zależne. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `delete_project_events()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `upsert_remote_event()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `delete_remote_event()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_event_payload()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `upsert_google_event()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `delete_google_event()` | Usuwa rekord albo powiązanie z uwzględnieniem reguł modułu. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `ensure_access_token()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `extract_google_event_id()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `encrypt_value()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `decrypt_option()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `notify_admin_about_sync_error()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |

### `erp-omd/includes/services/class-ksef-import-service.php` — `ERP_OMD_KSeF_Import_Service`

Odpowiedzialność pliku: komponent `ERP_OMD_KSeF_Import_Service` w warstwie `erp-omd/includes/services`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `__construct()` | Inicjalizuje zależności klasy i przygotowuje obiekt do obsługi workflow. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `import_documents()` | Importuje dane z pliku/zewnętrznego źródła. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `attempt_import_document()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `process_retry_queue()` | Przetwarza wieloetapowy request lub kolejkę workflow. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `enqueue_retry()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `match_supplier_for_cost_document()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `register_sales_document()` | Rejestruje hooki, trasy, menu lub komponenty WordPress wymagane przez moduł. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `match_client_for_sales_document()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `list_sales_inbox()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `attach_sales_document_to_project()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `has_final_sales_invoice_for_project()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `import_sales_xml()` | Importuje dane z pliku/zewnętrznego źródła. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `import_cost_xml()` | Importuje dane z pliku/zewnętrznego źródła. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `parse_ksef_xml_to_document()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `parse_ksef_line_items()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `sum_item_totals()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `xpath_first_text()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `xpath_first_decimal()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `xpath_sum_decimals()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `parse_decimal()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `normalize_issue_date()` | Normalizuje format wartości do postaci używanej wewnętrznie przez system. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `list_moderation_queue()` | Zwraca listę rekordów lub wpisów spełniających filtry. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `moderate_queue_entry()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `bulk_moderate_queue_entries()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `classify_document()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `moderate_imported_invoice()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `map_ksef_document_to_invoice()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `detect_duplicate()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `record_audit_reason()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_retry_decision()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `extract_nip_value()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `normalize_nip()` | Normalizuje format wartości do postaci używanej wewnętrznie przez system. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `load_retry_queue()` | Ładuje kolekcję danych potrzebnych do widoku lub procesu. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `save_retry_queue()` | Zapisuje dane formularza, opcję lub stan kolejki. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_retry_key()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `get_our_company_nip()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `normalize_moderation_status()` | Normalizuje format wartości do postaci używanej wewnętrznie przez system. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `append_moderation_audit()` | Dopisuje wpis audytu, notatkę, hint lub fragment danych. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `append_sales_audit()` | Dopisuje wpis audytu, notatkę, hint lub fragment danych. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `load_sales_inbox()` | Ładuje kolekcję danych potrzebnych do widoku lub procesu. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `save_sales_inbox()` | Zapisuje dane formularza, opcję lub stan kolejki. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `next_sales_inbox_id()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `now()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `to_timestamp()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |

### `erp-omd/includes/services/class-monthly-hours-service.php` — `ERP_OMD_Monthly_Hours_Service`

Odpowiedzialność pliku: komponent `ERP_OMD_Monthly_Hours_Service` w warstwie `erp-omd/includes/services`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `suggested_hours()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `suggested_hours_for_date()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `calculate_hourly_cost()` | Oblicza wartości finansowe, czasowe lub pomocnicze. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |

### `erp-omd/includes/services/class-project-attachment-service.php` — `ERP_OMD_Project_Attachment_Service`

Odpowiedzialność pliku: komponent `ERP_OMD_Project_Attachment_Service` w warstwie `erp-omd/includes/services`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `__construct()` | Inicjalizuje zależności klasy i przygotowuje obiekt do obsługi workflow. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `has_valid_final_invoice_pdf()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `validate_pdf_attachment()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `is_pdf_mime()` | Sprawdza warunek logiczny/status/typ danych. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `has_pdf_integrity()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |

### `erp-omd/includes/services/class-project-financial-service.php` — `ERP_OMD_Project_Financial_Service`

Odpowiedzialność pliku: komponent `ERP_OMD_Project_Financial_Service` w warstwie `erp-omd/includes/services`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `__construct()` | Inicjalizuje zależności klasy i przygotowuje obiekt do obsługi workflow. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `validate_project_cost()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `validate_project_revenue()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `get_project_financial()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `get_project_financials()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `rebuild_for_project()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `resolve_revenue()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `retainer_revenue()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |

### `erp-omd/includes/services/class-project-merge-service.php` — `ERP_OMD_Project_Merge_Service`

Odpowiedzialność pliku: komponent `ERP_OMD_Project_Merge_Service` w warstwie `erp-omd/includes/services`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `__construct()` | Inicjalizuje zależności klasy i przygotowuje obiekt do obsługi workflow. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `validate_source_projects()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_target_project_payload()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_merge_preview()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `execute_merge()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_merge_report()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `append_merge_audit_log()` | Dopisuje wpis audytu, notatkę, hint lub fragment danych. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `create_merged_estimate()` | Tworzy nowy rekord lub obiekt workflow. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |

### `erp-omd/includes/services/class-project-request-service.php` — `ERP_OMD_Project_Request_Service`

Odpowiedzialność pliku: komponent `ERP_OMD_Project_Request_Service` w warstwie `erp-omd/includes/services`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `__construct()` | Inicjalizuje zależności klasy i przygotowuje obiekt do obsługi workflow. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `prepare()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `validate()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `can_transition_status()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_project_payload()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `validate_conversion()` | Sprawdza reguły poprawności danych wejściowych i zwraca błędy walidacji. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |

### `erp-omd/includes/services/class-reporting-service-v2.php` — `ERP_OMD_Reporting_Service`

Odpowiedzialność pliku: komponent `ERP_OMD_Reporting_Service` w warstwie `erp-omd/includes/services`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `__construct()` | Inicjalizuje zależności klasy i przygotowuje obiekt do obsługi workflow. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `sanitize_filters()` | Czyści i normalizuje dane wejściowe przed zapisem lub dalszym przetwarzaniem. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_report()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_project_report()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_client_report()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_reports_link()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_invoice_report()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_monthly_report()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_omd_settlement_report()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_calendar()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `export_definition()` | Przygotowuje eksport danych. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `get_filtered_projects()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `is_project_from_reporting_month()` | Sprawdza warunek logiczny/status/typ danych. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `get_filtered_entries()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `filter_entries_from_pool()` | Filtruje dane według uprawnień, zakresu lub reguł biznesowych. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `prefetch_entries_for_months()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_entry_metrics_index_by_month()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_active_budget_metrics_index_by_month()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `entry_matches_filters()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `get_entry_metrics_by_project()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `get_direct_cost_metrics_by_project()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `get_direct_cost_metrics_by_month()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `get_revenue_metrics_by_project()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `get_additional_revenue_metrics_by_project()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_project_revenue_index_by_month()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_project_direct_cost_index_by_month()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_direct_cost_index_by_month_and_project()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `get_project_budget_profit_by_month()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `resolve_project_close_month()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_salary_cost_index_by_month()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `get_salary_rows_by_employee()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_fixed_cost_index_by_month()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `build_month_ranges()` | Składa strukturę wynikową, raport, payload integracyjny lub dane widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `emptyEntryMetrics()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `allowedStatuses()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `isProjectStatusFilter()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `isTimeEntryStatusFilter()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `matches_omd_operational_status_group()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `billing_type_label()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |

### `erp-omd/includes/services/class-time-entry-service.php` — `ERP_OMD_Time_Entry_Service`

Odpowiedzialność pliku: komponent `ERP_OMD_Time_Entry_Service` w warstwie `erp-omd/includes/services`.

| Funkcja/metoda | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `__construct()` | Inicjalizuje zależności klasy i przygotowuje obiekt do obsługi workflow. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `validate()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `prepare()` | Metoda pomocnicza modułu; realizuje wyspecjalizowany krok workflow wskazany nazwą metody. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `resolve_rate_snapshot()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `resolve_cost_snapshot()` | Wyznacza wartość wynikową z wielu źródeł, np. użytkownika, okres, stawkę lub status. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `can_edit_entry()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `can_view_entry()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `can_delete_entry()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `can_approve_entry()` | Rozstrzyga uprawnienie użytkownika do akcji lub widoku. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `get_visible_filters_for_user()` | Pobiera dane, konfigurację, etykietę, metrykę lub obiekt pomocniczy. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `filter_visible_entries()` | Filtruje dane według uprawnień, zakresu lub reguł biznesowych. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |
| `is_project_manager_for_entry()` | Sprawdza warunek logiczny/status/typ danych. | Egzekwuje reguły biznesowe modułu i ogranicza powielanie logiki w admin/front/REST. |

## 12. Katalog funkcji JavaScript


### `erp-omd/assets/js/admin.js`

| Funkcja | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `addIds()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `appendFixedCostRow()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `applyTableView()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `applyVisibility()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `buildChecklistReason()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `buildFixedCostRow()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `buildProjectDrilldownUrl()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `collectInvalidCostRowDetails()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `collectProjectIdsForCostVerification()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `compareValues()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `convertNoticesToToasts()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `fetchPreview()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `formatCountersLabel()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `formatDate()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `formatInvalidCostReason()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `getCellValue()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `getVisibilityByReportType()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `headingKey()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `initFixedCosts()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `initInlineAutoSave()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `initReportFilterVariantVisibility()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `initTableTools()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `isObject()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `normalizeDateValue()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `normalizedMessage()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `renderEmptyList()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `renderState()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `safeGet()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `scheduleSubmit()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `setFieldVisibility()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `setInlineState()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `setSourceState()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `setStatus()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `setStatusState()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `start()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `submitCostCorrection()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `submitInlineForm()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `submitInlineProjectViaAjax()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `syncDetailModeOptions()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `syncProjectOptions()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `syncRoleAvailability()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |

### `erp-omd/assets/js/front-manager.js`

| Funkcja | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `normalized()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `query()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `start()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `valueA()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |
| `valueB()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |

### `erp-omd/assets/js/front-shared.js`

Brak nazwanych funkcji; plik pełni rolę wspólnego zasobu lub miejsca na rozszerzenia.

### `erp-omd/assets/js/front-worker.js`

| Funkcja | Odpowiedzialność | Cel biznesowy/workflow |
|---|---|---|
| `start()` | Obsługuje interakcję UI, stan formularza, sortowanie, autosave, widoczność pól albo komunikaty. | Przyspiesza pracę użytkownika i ogranicza błędy ręcznej obsługi formularzy. |

## 13. Checklista kompletności przeglądu

- Przejrzano bootstrap, runtime admin, runtime front, REST API, serwisy, repozytoria, kontrakt KSeF, szablony, JS, CSS i uninstall.
- W katalogu metod ujęto wszystkie wykryte deklaracje `function` w PHP i nazwane funkcje JS.
- Zależności biznesowe opisano osobno w sekcjach workflow, aby katalog metod nie był jedynym źródłem wiedzy.
