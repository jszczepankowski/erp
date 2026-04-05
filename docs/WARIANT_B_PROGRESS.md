# Wariant B v1 — status wdrożenia (snapshot)

Data snapshotu: 2026-04-01

Cel: szybkie wznowienie prac w kolejnych chatowych sesjach bez utraty kontekstu.

Aktualny punkt pracy: **Opcja B / pakiet P5 (WB-P5-03)** — raport powdrożeniowy i plan cleanup legacy przygotowane.
Następny krok wg planu: **utrzymanie/stabilizacja** (monitoring operacyjny + decyzje cleanup po okresie obserwacji).

## 1) Co już zrobione

### Backend fundament okresów i blokad
- [x] Dodany model okresu miesięcznego (`erp_omd_periods`) z polami:
  - `month`, `status`, `closed_at`, `correction_window_until`, `updated_by`.
- [x] Dodany serwis okresów (`ERP_OMD_Period_Service`):
  - statusy LIVE / DO_ROZLICZENIA / ZAMKNIETY,
  - walidacja przejść,
  - checklista gotowości,
  - wyliczanie okna korekty +72h,
  - detekcja trybu EMERGENCY_ADJUSTMENT.
- [x] Dodane blokady zapisu danych (time entries / project costs) w okresach zablokowanych dla non-admin.

### Korekty i audyt
- [x] Dodana tabela audytu (`erp_omd_adjustment_audit`).
- [x] Dodane repo audytu (`ERP_OMD_Adjustment_Audit_Repository`).
- [x] Wprowadzony wymóg `reason` dla korekt admina w miesiącach zablokowanych.
- [x] Logowanie korekt admina w CRUD time entries i project costs.
- [x] Dodane API korekt:
  - `GET /erp-omd/v1/adjustments`
  - `POST /erp-omd/v1/adjustments`

### API okresów i dashboard
- [x] Dodane API okresów:
  - `GET /erp-omd/v1/periods`
  - `GET /erp-omd/v1/periods/{YYYY-MM}`
  - `POST /erp-omd/v1/periods/{YYYY-MM}/transition`
- [x] Dodane API dashboardu v1:
  - `GET /erp-omd/v1/dashboard-v1`
  - payload zawiera: status miesiąca, trend 3M, top/bottom rentowności (client/project), kolejkę rozliczeń i korekty.
- [x] Dashboard v1 backend contract rozszerzony o:
  - `readiness_checklist`, `readiness_meta`, `status_actions`,
  - `metric_definitions` (tooltip/legend keys),
  - `drilldown_links`,
  - `profitability_by_scope` (project/client top+bottom bez dodatkowego requestu),
  - `adjustments.items` + entity-aware drilldown,
  - limity payloadu (`adjustments_limit`, `queue_limit`, `profitability_limit`) i `applied_limits`.

### Reporting i statusy domenowe
- [x] Reporting: dodany `mode` (`LIVE`, `DO_ROZLICZENIA`, `ZAMKNIETY`).
- [x] Reporting: domyślnie approved-only dla time entries.
- [x] Reporting: obsługa `archiwum`.
- [x] Reporting/eksport projektów: kolumna `operational_close_month` (Miesiąc zamk. oper.).
- [x] Domain status: migracja i logika `inactive -> archiwum`.

### Migracje i wersjonowanie
- [x] Migracje DB rozszerzone o nowe tabele/kolumny/indeksy i bezpieczne helpery (`add_column_if_missing`, `add_index_if_missing`).
- [x] Wersja pluginu podniesiona do `2.8.3`.
- [x] Wersja DB ustawiona na `6.5.1`.
- [x] Dodany test regresji rozpoznania budżetu wg `operational_close_month` w trendzie OMD.

## 2) Co jest częściowo / techniczny dług

- [~] Checklista gotowości LIVE -> DO_ROZLICZENIA jest obecnie uproszczona (część sygnałów domyślna), wymaga dopięcia pełnych walidatorów biznesowych.
- [~] Mechanika korekt działa, ale pełna ścieżka UI Admin (dedykowany ekran/flow) i filtrowane widoki audytu są do dopracowania.
- [~] `dashboard-v1` działa backendowo (contract pod P2-01..P2-04 gotowy), ale frontendowe komponenty/wykresy i UX states wymagają dokończenia.

## 3) Co dalej (priorytet kolejnych kroków)

### P1 — domknięcie zgodności reguł księgowania v1
1. [x] Dopięcie pełnych walidatorów checklisty gotowości (submitted/rejected, koszty niezweryfikowane, kompletność danych, blokady krytyczne).
2. [x] Twarde użycie `operational_close_month` w logice budżetowej i agregacjach finansowych.
3. [x] Ujednolicenie approved-only we wszystkich raportach/eksportach/endpointach finansowych.

### P2 — operacyjny dashboard v1 (frontend + contract)
1. [x] Ekran statusu miesiąca + CTA admin (backend: `status_actions`).
2. [x] Wykres trendu 3M i tooltipy definicji metryk (backend: `metric_definitions`).
3. [x] Top/Bottom z przełącznikiem client/project (backend: `profitability_by_scope`).
4. [x] Sekcja kolejki rozliczeń i korekt z deep-linkami do drilldown (backend: `drilldown_links`).

### P3 — raporty operacyjne simple/detail (pełny UAT)
1. [x] Raport klient (simple/detail + drilldown).
2. [x] Raport projekt (direct cost, budget usage, detail mix).
3. [x] Raport czas pracy (line-by-line + paginacja).
4. [x] Eksport CSV/XLS zgodny 1:1 z widokiem.

### P4 — controlling OMD
1. [x] Dopięcie finalnych agregacji controllingowych vs operacyjnych.
2. [x] Odświeżenie widoku OMD z legendą definicji i eksportem.

## 4) Checklist do wznowienia pracy w nowym chacie

Wklej na start:
1. "Kontynuujemy Wariant B v1, pracujemy na `docs/WARIANT_B_PROGRESS.md` i `docs/SPECYFIKACJA_V1_WARIANT_B.txt`."
2. "Najpierw zrób status git + krótki diff od ostatniego commita."
3. "Następny cel: [wstaw z sekcji P1/P2/P3/P4]."
4. "Po zmianach: testy + commit + PR summary + aktualizacja tego pliku progress."

## 5) Source of truth
- Spec: `docs/SPECYFIKACJA_V1_WARIANT_B.txt`
- Potwierdzenie: `docs/POTWIERDZENIE_USTALEŃ_V1_2026-04-01.md`
- Ten snapshot roboczy: `docs/WARIANT_B_PROGRESS.md`

---

## 6) Numerowana lista kroków wdrożenia (ID do odwołań w kolejnych chat)

Format ID: `WB-<obszar>-<nr>` (np. `WB-P1-02`).

### P1 — Reguły księgowania i zamknięcie miesiąca
- `WB-P1-01` — [x] Dopięcie pełnej checklisty gotowości LIVE -> DO_ROZLICZENIA (wszystkie walidatory biznesowe).
- `WB-P1-02` — [x] Użycie `operational_close_month` w pełnej logice budżetowej i controllingowej.
- `WB-P1-03` — [x] Ujednolicenie approved-only we wszystkich raportach/eksportach/API finansowym.
- `WB-P1-04` — [x] Testy regresji: przejścia statusów, locki, edge-case 72h + emergency + walidacja zakresu miesiąca (`YYYY-MM`) także na endpointach okresów (422 dla out-of-range).

### P2 — Dashboard v1 (frontend + contract)
- `WB-P2-01` — [x] Karta statusu miesiąca + akcje admin (backend contract).
- `WB-P2-02` — [x] Wizualizacja trendu 3M (backend definitions/tooltip keys).
- `WB-P2-03` — [x] Ranking Top/Bottom rentowności z przełącznikiem client/project (backend contract).
- `WB-P2-04` — [x] Kolejka rozliczeń + sekcja korekt z linkami do drilldown (backend contract).

### P3 — Raporty operacyjne (UAT ready)
- `WB-P3-01` — [x] Raport klient simple/detail + drilldown klient -> projekt -> pozycje.
- `WB-P3-02` — [x] Raport projekt simple/detail (direct cost, budget usage, mix billing).
- `WB-P3-03` — [x] Raport czas pracy simple/detail (line-by-line + paginacja).
- `WB-P3-04` — [x] Eksport CSV/XLS zgodny 1:1 z widokiem i filtrami.

### P4 — Controlling OMD
- `WB-P4-01` — [x] Finalne agregacje controllingowe vs operacyjne (spis i implementacja).
- `WB-P4-02` — [x] UI OMD z legendą definicji i pełnym eksportem.

### P5 — Stabilizacja rollout i operacje
- `WB-P5-01` — [x] Feature flags + canary rollout (admin -> wszyscy).
- `WB-P5-02` — [x] Monitoring błędów/wydajności + plan rollback przez flagi.
- `WB-P5-03` — [x] Raport powdrożeniowy i cleanup legacy po stabilizacji.
- `WB-P5-04` — [x] Utrwalony monitoring API (`system/status`) z freshness metryk i konfigurowalnym progiem świeżości.

## 7) Jak odwoływać się do kroków
- W nowym chacie podaj po prostu: „Robimy `WB-P2-03`”.
- Dla większych kawałków: „Robimy `WB-P3-01` + `WB-P3-04`”.
- Dla hotfixu: „Priorytetowo wracamy do `WB-P1-04`”.

## 8) Zwiększanie zakresu — ocena ryzyka (praktycznie)

### Opcja A: małe kroki (niższe ryzyko)
- 1–2 ID na iterację (np. `WB-P1-02` + `WB-P1-04`).
- Plusy: łatwiejszy rollback, szybsza diagnoza regresji.
- Minus: więcej iteracji / więcej merge.

### Opcja B: średni pakiet (umiarkowane ryzyko) — REKOMENDOWANE
- 3–4 spójne ID na iterację, ale tylko z jednego obszaru.
- Przykład: cały `P1` (WB-P1-01..04) albo cały `P2` (WB-P2-01..04).
- Warunek: testy + feature flag + checklista release.

### Opcja C: duży pakiet cross-obszar (wyższe ryzyko)
- Łączenie P1 + P2 + P3 w jednym strzale.
- Ryzyka:
  - większe prawdopodobieństwo regresji,
  - trudniejszy root-cause po wdrożeniu,
  - dłuższy czas stabilizacji.
- Wymagane minimum:
  - canary rollout,
  - metryki wydajności + error-rate,
  - gotowy rollback przez flagi.

### Nasza rekomendacja na teraz
- Trzymać **Opcję B**:
  1. domknąć `P1`,
  2. potem pełny `P2`,
  3. potem `P3`,
  4. na końcu `P4/P5`.

## 9) Podsumowanie kroku (dla kolejnych chatów)
- **Bieżący krok:** `WB-P5-HF-09` (stabilizacja/hotfix) — obsługa cache snapshotów w podglądzie `dashboard-v1` rozszerzona o ręczne czyszczenie cache z poziomu UI.
- **Szacowana liczba kroków do domknięcia (orientacyjnie): ~0**
  1. Etap stabilizacji Wariantu B v1 uznajemy za domknięty; kolejne kroki to utrzymanie/iteracyjne usprawnienia.
