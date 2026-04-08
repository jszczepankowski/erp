# WDROZENIE_V1_DZIENNIK — tracker postępu cross-chat

Data startu dziennika: 2026-04-07
Plan bazowy: `docs/PLAN_PELNE_WDROZENIE_WARIANT_B_V1.md`
Spec referencyjna: `docs/SPECYFIKACJA_V1_WARIANT_B.txt`

## 1) Status globalny

- Aktualny etap: **ETAP 1 — MUST operacyjne (Fala 1)**
- Aktualny krok: **KROK 1.3 — UAT Master Pass (wykonanie i raport)**
- Status: **W TRAKCIE**
- Następny krok: **KROK 1.3 — retest Ekranu 6 po wdrożeniu UI/API status transitions + domknięcie raportu UAT**

## 2) Etapy i kroki (kanoniczna lista)

### ETAP 0 — Planowanie (zakończony)
- [x] **0.1** Audyt `RAPORTY_NEW` (braki/duplikaty).
- [x] **0.2** Opracowanie pełnego planu wdrożenia v1 (4 fale).
- [x] **0.3** Potwierdzenie kompletności planu względem SPEC + ustalenie trackingu cross-chat.

### ETAP 1 — MUST operacyjne (Fala 1)
- [x] **1.1** Audit Log UX + CSV — projekt techniczny i zakres implementacji.
- [x] **1.2** Audit Log UX + CSV — implementacja backend/UI/eksport.
- [ ] **1.3** UAT Master Pass — wykonanie i raport.

### ETAP 2 — Jakość i bezpieczeństwo (Fala 2)
- [ ] **2.1** Benchmark wydajności raportów (scenariusze ciężkie).
- [ ] **2.2** Testy bezpieczeństwa endpointów krytycznych.
- [ ] **2.3** Regresja migracyjna na staging (kopie danych/historyczne miesiące).

### ETAP 3 — Operacyjność steady-state (Fala 3)
- [ ] **3.1** Rollback drill (runbook, czasy wykonania).
- [ ] **3.2** Przegląd i utrwalenie progów operacyjnych.
- [ ] **3.3** Matryca reakcji operacyjnej (kto/co/kiedy).

### ETAP 4 — Cleanup i formalne zamknięcie (Fala 4)
- [ ] **4.1** Ujednolicenie dokumentacji końcowej.
- [ ] **4.2** Finalna nota „v1 fully implemented”.
- [ ] **4.3** Backlog v1.1 (poza MUST).

## 3) Log aktualizacji (append-only)

## 2026-04-07
- **DONE:** ETAP 0 / KROK 0.3 — potwierdzono kompletność planu `PLAN_PELNE_WDROZENIE_WARIANT_B_V1.md` względem MUST specyfikacji; zdefiniowano standard raportowania postępu cross-chat.
- Commit referencyjny: `TBD (uzupełniany po merge)`.
- Kolejny focus: ETAP 1 / KROK 1.1.

- **DONE:** ETAP 1 / KROK 1.1 — domknięto zakres projektowy dla Audit Log UX + CSV oraz wydzielono warstwę techniczną do zakładki `Monitoring techniczny`.
- **DONE:** ETAP 1 / KROK 1.2 — wdrożono filtrację i tabelę audit log korekt w `Monitoring techniczny` + eksport CSV audytu.
- Commit referencyjny: `TBD (uzupełniany po merge)`.
- Kolejny focus: ETAP 1 / KROK 1.3 (UAT Master Pass).

- **IN PROGRESS:** ETAP 1 / KROK 1.3 — utworzono artefakt `docs/UAT_MASTER_PASS_V1_2026-04-07.md` z checklistą UAT per ekran i bieżącymi dowodami technicznymi.
- **IN PROGRESS:** ETAP 1 / KROK 1.3 — rozszerzono Audit log korekt o widok/eksport wartości `przed/po` (old/new), aby ułatwić finalne testy UAT z biznesem.
- **IN PROGRESS:** ETAP 1 / KROK 1.3 — hotfix runtime: dodano brakującą metodę `is_valid_month_string()` w `ERP_OMD_Admin` (usuniecie fatal error na ekranie Raporty/Monitoring).
- **IN PROGRESS:** ETAP 1 / KROK 1.3 — hotfix redeclare: obsługę `export_adjustments_audit` przeniesiono inline do `handle_forms()` (bez dedykowanej metody), aby wyeliminować kolizje nazw metod po merge/cherry-pick.
- **IN PROGRESS:** ETAP 1 / KROK 1.3 — dodano twardy test `tests/admin-class-test.php` pilnujący, że legacy `handle_adjustments_audit_export()` i `handle_adjustments_audit_export_csv()` nie wracają jako metody klasy.
- **IN PROGRESS:** ETAP 1 / KROK 1.3 — hotfix runtime: dodano inicjalizację `$adjustment_audit` w konstruktorze `ERP_OMD_Admin` + guardy, gdy repozytorium audytu nie jest dostępne.
- **IN PROGRESS:** ETAP 1 / KROK 1.3 — przygotowano `docs/UAT_EXECUTION_SHEET_2026-04-07.md` (operacyjna checklista manualna ekranów 1–6 z kryterium PASS/PASS warunkowy/FAIL).
- **IN PROGRESS:** ETAP 1 / KROK 1.3 — doprecyzowano przebieg „krok po kroku” z linkami per ekran i znacznikami **[WYMAGA POTWIERDZENIA UŻYTKOWNIKA]**.
- **IN PROGRESS:** ETAP 1 / KROK 1.3 — użytkownik potwierdził `PASS` dla kroków 1–3 (Dashboard v1, Raport klient, Raport projekt); w toku pozostają kroki 4–6.
- **IN PROGRESS:** ETAP 1 / KROK 1.3 — po feedbacku dla kroku 4 wdrożono fixy: przełącznik `Podstawowa/Szczegółowa` także dla `time_entries`, górna paginacja tabeli czasu oraz `per_page` jako „Wierszy na stronę” (retest wymagany).
- **IN PROGRESS:** ETAP 1 / KROK 1.3 — użytkownik potwierdził `PASS` dla kroku 4 (Raport czasu); pozostają kroki 5–6.
- **IN PROGRESS:** ETAP 1 / KROK 1.3 — użytkownik potwierdził `PASS` dla kroku 5 (OMD rozliczenia); pozostał krok 6.
- **IN PROGRESS:** ETAP 1 / KROK 1.3 — wdrożono UI/API dla przejść statusu miesiąca (LIVE → DO_ROZLICZENIA → ZAMKNIETY) w Monitoring + Settings; wymagany retest manualny Kroku 6 na koncie admin.
- **PARALLEL (ETAP 2 / KROK 2.1):** przygotowano baseline wydajności raportów (`docs/REPORTING_BENCHMARK_BASELINE_2026-04-07.md`) komendą `php tests/reporting-benchmark-12m.php`.
- Commit referencyjny: `TBD (uzupełniany po merge)`.
- Kolejny focus: przeprowadzić retest Kroku 6 (status transitions, blokady po zamknięciu, korekty z reason + audit trail) i domknąć ETAP 1 / KROK 1.3.

## 2026-04-08
- **IN PROGRESS:** ETAP 1 / KROK 1.3 — potwierdzono pozytywnie UAT-M3 (Ekran 6.3): korekta po zamknięciu miesiąca działa ścieżką admina z `reason` i wpisem w Audit log.
- **IN PROGRESS:** ETAP 1 / KROK 1.3 — dopracowano UX Monitoringu technicznego (miesiąc dla szybkiej korekty, podpowiedź ID kosztów, czytelny widok `Encja` + `Przed/Po`).
- **IN PROGRESS:** ETAP 1 / KROK 1.3 — uruchomiono auto-smoke dla punktów M6.1/M6.2: `php tests/omd-period-service-test.php` (OK) + `php tests/rest-api-test.php` (PASS). Potwierdzają poprawność logiki przejść i blokad API; nadal wymagane potwierdzenie manualne UI/uprawnień.
- **IN PROGRESS:** ETAP 1 / KROK 1.3 — potwierdzono manualnie UAT-M1 i UAT-M2 jako PASS (retest 2026-04-08), oraz UAT-A1 walidację biznesową jako PASS.
- Kolejny focus: domknąć ostatni punkt manualny UAT-D4 (korekty + drilldown na dashboardzie), następnie oznaczyć KROK 1.3 jako DONE/PASS.

## 4) Instrukcja wznowienia w nowym chacie

Wklej na start:
1. `Kontynuujemy wdrożenie v1 wg docs/PLAN_PELNE_WDROZENIE_WARIANT_B_V1.md i docs/WDROZENIE_V1_DZIENNIK.md.`
2. `Pokaż aktualny ETAP/KROK i przejdź do kolejnego kroku TODO.`
3. `Po zmianach zaktualizuj dziennik (status + log aktualizacji + commit).`
