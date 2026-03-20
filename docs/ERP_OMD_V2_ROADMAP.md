# ERP_OMD V2 — Roadmapa sprintów

## Założenia wykonawcze

Ta roadmapa traktuje dokument **ERP_OMD – TECH SPEC v2 FINAL (MASTER COMPLETE)** jako kontrakt bazowy dla implementacji 1:1.

Zasady planu:
- **BUILD N+1 = BUILD N + rozszerzenie** — każdy sprint zawiera komplet poprzednich funkcji oraz nowe moduły.
- Każdy sprint kończy się **instalowalną paczką ZIP pluginu WordPress**.
- Każdy sprint zawiera trzy warstwy: **DB + backend/logika + minimum UI admina do testów**.
- Brak regresji: istniejące menu, migracje, endpointy i logika nie mogą być usuwane.
- Migracje DB są wersjonowane i idempotentne; brak duplikatów `ALTER`, brak konfliktów kluczy obcych.
- Integracja finansów jest liczona **real-time** (bez cron) zgodnie z flow: `TIME ENTRY → snapshot → aggregator`, `RATE CHANGE → aggregator`, `COST → aggregator`, `ESTIMATE → PROJECT`.

## Proponowana liczba sprintów

Dla pełnej, produkcyjnej wersji systemu proponuję **8 sprintów**.

Taki podział minimalizuje ryzyko, pozwala wcześnie uruchomić plugin w WordPressie i utrzymać sensowne, kompletne przyrosty funkcjonalne bez mieszania przypadkowych elementów.

---

## Sprint 1 — Fundament pluginu, DB, role systemowe, pracownicy, role projektowe

### Cel
Zbudować stabilny szkielet pluginu WordPress oraz pierwsze moduły bazowe, od których zależy reszta systemu: **pracownicy, role, powiązania z kontami WP i historia wynagrodzeń**.

### Zakres funkcjonalny
- Struktura pluginu WordPress gotowa do dalszej rozbudowy.
- Instalator / aktywacja / deaktywacja / uninstall z opcją:
  - usuń dane,
  - zachowaj dane.
- Utworzenie podstawowych tabel DB:
  - `roles`,
  - `employees`,
  - `employee_roles`,
  - `salary_history`.
- Klucze obce i indeksy dla powyższych tabel.
- Model kont użytkowników:
  - admin,
  - manager,
  - pracownik.
- CRUD ról projektowych.
- CRUD pracowników powiązanych z `wp_users`.
- Obsługa:
  - `default_role_id`,
  - `status`,
  - relacji many-to-many w `employee_roles`.
- Historia wynagrodzeń:
  - `monthly_salary`,
  - `monthly_hours`,
  - `valid_from`,
  - `valid_to`.
- `monthly_hours` jest wartością ręczną z możliwością automatycznej podpowiedzi systemowej opartej o `8h * dni robocze` dla danego miesiąca.
- Logika kosztu godzinowego:
  - `hourly_cost = salary / monthly_hours`,
  - tylko z modułu employee/salary,
  - role nie wpływają na salary.
- Minimum UI admina:
  - menu główne ERP_OMD,
  - lista pracowników,
  - formularz pracownika,
  - lista ról,
  - formularz roli,
  - historia wynagrodzeń pracownika.

### Zadania w sprincie
1. Przygotować bootstrap pluginu, autoloading i modułową strukturę katalogów.
2. Dodać migrator schematu DB z wersjonowaniem.
3. Zaimplementować capability map i mapowanie do ról WP.
4. Dodać repozytoria / serwisy dla employee, role, salary history.
5. Zaimplementować walidacje okresów `valid_from/valid_to`.
6. Dodać automatyczną podpowiedź `monthly_hours` z możliwością ręcznego nadpisania.
7. Dodać UI admina do CRUD pracowników i ról.
8. Dodać ekran uninstall/config retention.
9. Dodać podstawowe endpointy API:
   - `employees CRUD + salary`,
   - `roles CRUD`.

### Definicja ukończenia sprintu
- Plugin instaluje się bez błędów fatal.
- Można dodać rolę projektową.
- Można dodać pracownika przypiętego do konta WP.
- Można dodać historię salary i odczytać wyliczony hourly cost.
- UI pozwala przetestować wszystkie operacje bez ręcznej edycji DB.

---

## Sprint 2 — Klienci, stawki klienta, podstawy projektów

### Cel
Dostarczyć pełny moduł klientów i bazę pod projekty wraz z modelem stawek klient → rola.

### Zakres funkcjonalny
- Tabele DB:
  - `clients`,
  - `client_rates`,
  - `projects` (wersja bazowa bez pełnej logiki finansowej).
- CRUD klientów:
  - `name`, `company`, `nip`,
  - `email`, `phone`,
  - `contact_person_name/email/phone`,
  - `city`,
  - `status`,
  - `account_manager_id`.
- CRUD stawek klienta:
  - `client_id`,
  - `role_id`,
  - `rate`.
- CRUD projektów — pola podstawowe:
  - `client_id`,
  - `name`,
  - `billing_type`,
  - `budget`,
  - `retainer_monthly_fee`,
  - `status`,
  - `start_date`, `end_date`,
  - `manager_id`,
  - `estimate_id`,
  - `brief`,
  - historia uwag klienta.
- Status workflow projektu:
  - `do_rozpoczecia`,
  - `w_realizacji`,
  - `w_akceptacji`,
  - `do_faktury`,
  - `zakonczony`,
  - `inactive`.
- Minimum UI admina:
  - lista klientów,
  - formularz klienta,
  - ekran stawek klienta,
  - lista projektów,
  - formularz projektu.

### Zadania w sprincie
1. Dodać migracje `clients`, `client_rates`, `projects`.
2. Zaimplementować walidacje unikalności i integralności danych klienta.
3. Dodać logikę account managera i managera projektu.
4. Zbudować UI dla klientów, stawek klienta i projektów.
5. Dodać historię uwag klienta w obrębie projektu.
6. Dodać endpointy API:
   - `clients CRUD`,
   - `projects CRUD`.

### Definicja ukończenia sprintu
- Można dodać klienta i stawki klienta per rola.
- Można założyć projekt przypisany do klienta.
- Można ustawić typ rozliczenia i status projektu.
- Można zapisać brief oraz historię uwag klienta.

---

## Sprint 3 — Stawki projektowe, time tracking, snapshoty i approval flow

### Cel
Uruchomić kluczowy proces operacyjny: rejestrowanie czasu pracy z pełną logiką stawek i snapshotów.

### Zakres funkcjonalny
- Tabele DB:
  - `project_rates`,
  - `time_entries`.
- Model stawek:
  - `client -> role -> rate`,
  - `project -> role -> rate (override)`.
- Snapshoty na wpisie czasu:
  - `rate_snapshot`,
  - `cost_snapshot`.
- Reguły time trackingu:
  - wpis tylko dla projektów `w_realizacji`,
  - duplikat oznacza identyczny zestaw: `employee_id + project_id + role_id + hours`,
  - brak stawki = 0,
  - admin ma pełną edycję,
  - usuwanie wpisu czasu tylko admin.
- Approval/status dla wpisów czasu: `submitted`, `approved`, `rejected`, gdzie zatwierdza manager projektu.
- Minimum UI admina:
  - lista wpisów czasu,
  - formularz wpisu,
  - filtrowanie po pracowniku/projekcie/dacie/statusie,
  - ekran stawek projektowych.

### Zadania w sprincie
1. Dodać migracje `project_rates`, `time_entries`.
2. Zaimplementować resolver stawek z priorytetem project override nad client rate.
3. Dodać obliczanie `cost_snapshot` na bazie salary history obowiązującej w dniu wpisu.
4. Zaimplementować walidację projektu w statusie `w_realizacji`.
5. Zaimplementować blokadę duplikatów na podstawie `employee_id + project_id + role_id + hours`.
6. Dodać workflow `submitted/approved/rejected` oraz akceptację przez managera projektu.
7. Dodać endpointy API:
   - `time CRUD + approval`,
   - `projects CRUD + stawki`.

### Definicja ukończenia sprintu
- Pracownik/manager/admin może dodać wpis czasu zgodnie z uprawnieniami.
- System zapisuje snapshot stawki sprzedażowej i kosztowej.
- Zmiana stawek po zapisaniu wpisu nie zmienia historycznych snapshotów.
- Brak stawki nie blokuje wpisu, ale daje wartość 0.

---

## Sprint 4 — Koszty projektu, agregator finansowy real-time, cache finansów

### Cel
Dostarczyć pełny silnik finansowy projektu oparty o wpisy czasu i koszty bezpośrednie.

### Zakres funkcjonalny
- Tabele DB:
  - `project_costs`,
  - `project_financials`.
- CRUD kosztów projektu:
  - `project_id`,
  - `amount`,
  - `description`.
- Agregator finansowy real-time bez cron.
- `project_financials` jako cache/liczona projekcja.
- Logika przychodów:
  - T&M → godziny,
  - fixed price → pełny budżet po przejściu projektu do ustalonego statusu rozliczeniowego,
  - retainer → miesięczna opłata liczona za każdy miesiąc aktywności projektu.
- Logika kosztów:
  - czas pracy,
  - koszty projektu.
- Wyliczenia:
  - revenue,
  - cost,
  - profit,
  - margin,
  - budget_usage.
- Trigger flow:
  - `TIME ENTRY → snapshot → aggregator`,
  - `RATE CHANGE → aggregator`,
  - `COST → aggregator`.
- Minimum UI admina:
  - ekran kosztów projektu,
  - karta finansowa projektu,
  - sekcja metryk finansowych na liście/profilu projektu.

### Zadania w sprincie
1. Dodać migracje `project_costs`, `project_financials`.
2. Zaimplementować serwis agregujący finanse projektu.
3. Zaimplementować przeliczenia dla każdego `billing_type`.
4. Dodać mechanizm aktualizacji cache po zmianie czasu, kosztu lub stawki.
5. Zbudować UI kosztów i finansów projektu.
6. Dodać endpoint API `finance GET` oraz rozszerzenie `projects CRUD + koszty`.

### Definicja ukończenia sprintu
- Dodanie kosztu projektu aktualizuje finanse natychmiast.
- Dodanie/edycja wpisu czasu aktualizuje finanse natychmiast.
- Widok projektu pokazuje aktualny revenue, cost, profit, margin i budget usage.

---

## Sprint 5 — Kosztorysy, akceptacja i automatyczne tworzenie projektów

### Cel
Dostarczyć pełny moduł estimate → acceptance → project z powiązaniem read-only.

### Zakres funkcjonalny
- Tabele DB:
  - `estimates`,
  - `estimate_items`.
- CRUD kosztorysów:
  - `client_id`,
  - `status`: `wstępny`, `do akceptacji`, `zaakceptowany`.
- CRUD pozycji kosztorysu:
  - `name`,
  - `qty`,
  - `price`,
  - `cost_internal`,
  - `comment`.
- VAT 23% w kalkulacji.
- Akceptacja kosztorysu:
  - tworzy projekt,
  - tworzy powiązanie `estimate_id` ↔ `project_id`,
  - powiązanie przechodzi w tryb read-only zgodnie ze specyfikacją.
- Minimum UI admina:
  - lista kosztorysów,
  - formularz kosztorysu,
  - edycja pozycji,
  - akcja akceptacji.

### Zadania w sprincie
1. Dodać migracje `estimates`, `estimate_items`.
2. Zaimplementować kalkulacje netto/brutto/VAT 23%.
3. Dodać status workflow kosztorysu.
4. Zaimplementować transakcyjne tworzenie projektu przy akceptacji.
5. Zablokować niedozwoloną edycję po zaakceptowaniu zgodnie z read-only binding.
6. Dodać endpointy API `estimates CRUD + accept`.

### Definicja ukończenia sprintu
- Można przygotować kosztorys z pozycjami.
- Można przełączyć go do akceptacji.
- Akceptacja tworzy projekt i zapisuje trwałe powiązanie z kosztorysem.

---

## Sprint 6 — Raporty, CSV, kalendarz i widoki analityczne

### Cel
Udostępnić warstwę raportową i kontrolną do codziennego użycia przez managerów i administrację.

### Zakres funkcjonalny
- Raporty:
  - projekty,
  - klienci,
  - do_faktury (wyłącznie projekty w statusie `do_faktury`),
  - miesięczne.
- Eksport CSV:
  - dane klienta,
  - lista prac,
  - wartości.
- Kalendarz:
  - widok miesięczny,
  - suma godzin per dzień,
  - filtrowanie,
  - tylko podgląd.
- Minimum UI admina:
  - centrum raportów,
  - filtry,
  - akcje eksportu,
  - ekran kalendarza.

### Zadania w sprincie
1. Zaimplementować warstwę zapytań raportowych.
2. Dodać eksporty CSV z odpowiednim mapowaniem pól.
3. Zbudować kalendarz miesięczny z agregacją per dzień.
4. Dodać filtry po kliencie, projekcie, pracowniku, miesiącu i statusie.
5. Dodać endpointy API:
   - `reports + CSV`,
   - `calendar GET`.

### Definicja ukończenia sprintu
- Raporty pokazują spójne dane z projektów, klientów, czasu i finansów.
- CSV eksportuje wymagane informacje.
- Kalendarz umożliwia przegląd godzin dziennych bez edycji.

---

## Sprint 7 — Alerty, załączniki, soft delete i twarde domknięcie lifecycle

### Cel
Domknąć procesy operacyjne i administracyjne wymagane do wersji produkcyjnej.

### Zakres funkcjonalny
- Alerty:
  - przekroczenie budżetu,
  - niska marża (próg konfigurowalny globalnie, domyślnie 10%),
  - brak stawek.
- Załączniki przez WordPress Media dla:
  - projektów,
  - kosztorysów,
  - z własną tabelą relacyjną i metadanymi powiązań.
- Soft delete przez `status = inactive` dla klientów, projektów i pracowników.
- Dokończenie polityk usuwania:
  - time entry usuwa tylko admin,
  - uninstall z zachowaniem/usunięciem danych.
- Minimum UI admina:
  - centrum alertów / znaczniki ostrzeżeń w listach,
  - sekcje załączników w projekcie i koszorysie,
  - akcje deactivate/inactive.

### Zadania w sprincie
1. Zaimplementować silnik reguł alertów oparty o dane finansowe i brak konfiguracji stawek.
2. Dodać integrację z WP Media Library oraz własną tabelę relacyjną załączników.
3. Dodać UI załączników dla projektów i kosztorysów.
4. Zaimplementować soft delete dla klientów, projektów i pracowników.
5. Dodać oznaczenia ostrzegawcze do dashboardów/list z obsługą progu marży 10%.

### Definicja ukończenia sprintu
- System pokazuje alert przy przekroczeniu budżetu.
- System pokazuje alert przy niskiej marży.
- System pokazuje alert przy brakujących stawkach.
- Do projektu i kosztorysu można dodać załączniki z biblioteki WP.

---

## Sprint 8 — Hardening produkcyjny, API finalne, UX/admin polish, paczka release candidate

### Cel
Domknąć system do pełnej wersji produkcyjnej zgodnej ze specyfikacją końcową.

### Zakres funkcjonalny
- Finalizacja wszystkich endpointów API z punktu 17 specyfikacji.
- Przegląd uprawnień i security hardening.
- Walidacje przekrojowe, sanity checks, obsługa edge cases.
- Finalne uporządkowanie UX i warstwy wizualnej admina.
- Testy regresji instalacji / aktualizacji / uninstall.
- Przygotowanie release candidate pluginu.

### Zadania w sprincie
1. Spiąć i ujednolicić wszystkie endpointy API.
2. Uzupełnić brakujące walidacje domenowe i integralność FK.
3. Zrobić przegląd uprawnień admin/manager/pracownik na każdym ekranie i endpointcie.
4. Dopracować nawigację, komunikaty systemowe, stany pustych list, błędy walidacji.
5. Zrobić pełny test regresji wszystkich sprintów.
6. Wygenerować finalną paczkę ZIP release candidate.

### Definicja ukończenia sprintu
- Plugin obejmuje całą specyfikację MASTER FINAL.
- Wszystkie moduły działają po świeżej instalacji i po aktualizacji z poprzedniego sprintu.
- System jest gotowy do wdrożenia testowego/UAT.

---

## Standard paczki po każdym sprincie

Każdy sprint musi dostarczać:
1. **ZIP pluginu** gotowy do instalacji w WordPress.
2. Pełną strukturę pluginu, nie tylko diff/fragment.
3. Migracje DB bez konfliktów i bez regresji.
4. Menu admina oraz widoki umożliwiające test funkcji ze sprintu.
5. Zachowanie wszystkich funkcji z poprzednich sprintów.
6. Checklistę odbioru sprintu krok po kroku.

## Standard checklisty po każdym sprincie

Każda checklista odbiorowa powinna zawierać:
- instalację pluginu,
- aktywację i ewentualne wykonanie migracji,
- ścieżkę klików po menu admina,
- dane testowe do wpisania,
- oczekiwany rezultat po każdym kroku,
- listę endpointów/API objętych sprintem,
- listę ograniczeń/uprawnień do sprawdzenia,
- potwierdzenie braku regresji względem poprzedniego sprintu.

---

## Ustalone decyzje domenowe przed implementacją

Po doprecyzowaniu wymagań przyjmujemy następujące reguły obowiązujące dla dalszej implementacji 1:1:

1. **Duplikaty time entries**
   - Duplikat oznacza identyczny zestaw: `employee_id + project_id + role_id + hours`.

2. **Statusy wpisów czasu (approval flow)**
   - Statusy: `submitted`, `approved`, `rejected`.
   - Zatwierdza: manager projektu.

3. **`monthly_hours` w salary history**
   - Wartość jest wpisywana ręcznie, ale system ma dawać automatyczną podpowiedź wyliczoną na podstawie `8h * dni robocze` dla danego miesiąca.

4. **Revenue w project_financials**
   - `fixed_price`: revenue rozpoznawane po przejściu projektu do ustalonego statusu rozliczeniowego.
   - `retainer`: revenue liczone za każdy miesiąc aktywności projektu.

5. **Uwagi klienta w projekcie**
   - Historia uwag ma być przechowywana w osobnej tabeli z autorem i timestampem.

6. **Załączniki**
   - Wymagana jest własna tabela relacyjna oparta o WordPress Media Library oraz metadane powiązań.

7. **API**
   - API budujemy jako WordPress REST API jako główny interfejs oraz pomocnicze ajax/admin actions tam, gdzie uprości to UI admina.

8. **Alert niskiej marży**
   - Próg alertu jest konfigurowalny globalnie; domyślna wartość to 10%.

9. **Raport `do_faktury`**
   - Raport pokazuje wyłącznie projekty ze statusem `do_faktury`.

10. **Soft delete**
    - `inactive` dotyczy klientów, projektów i pracowników.

## Rekomendowana kolejność startu implementacji

Plan jest doprecyzowany na poziomie wystarczającym do rozpoczęcia **Sprintu 1** bez cofania logiki domenowej w kolejnych etapach.
