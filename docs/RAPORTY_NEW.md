# RAPORTY_NEW — snapshot do wznowienia prac

Data snapshotu: 2026-04-01
Gałąź: `work`
Hasło wznowienia: `RAPORTY_NEW`

## 1) Co już mamy zrobione

### Fundament domeny i API
- Wdrożony model okresów (`LIVE` / `DO_ROZLICZENIA` / `ZAMKNIETY`) wraz z repo i serwisem okresów.
- Wdrożony audyt korekt admina (`erp_omd_adjustment_audit`) + endpointy `GET|POST /adjustments`.
- Dodane endpointy okresów (`/periods`, `/periods/{YYYY-MM}`, `/periods/{YYYY-MM}/transition`).
- Dodany kontrakt backendowy `GET /dashboard-v1` (status miesiąca, checklista gotowości, trend, rentowność, kolejka, korekty, drilldown links).

### Reguły biznesowe i raportowanie
- Blokady edycji w miesiącach zamykanych/zamkniętych dla non-admin.
- Wymóg `reason` i logowanie korekt admina w okresach zablokowanych.
- Reporting działa z `mode` (`LIVE` / `DO_ROZLICZENIA` / `ZAMKNIETY`).
- Projekty i trend OMD uwzględniają `operational_close_month`.
- Status domenowy `inactive` został zastąpiony przez `archiwum`.

### Migracje i testy
- Migracje DB dla nowych tabel i kolumn + helpery bezpiecznych zmian schematu.
- Zaktualizowane wersje pluginu/DB.
- Testy serwisowe i REST dla nowych endpointów/kontraktu zostały dopięte i wcześniej przechodziły.

## 2) Gdzie aktualnie jesteśmy

Jesteśmy po dużym wdrożeniu backendu Wariantu B v1 (okresy, korekty, dashboard contract, migracje).  
Aktualny fokus przesunięty jest na **raporty operacyjne i domknięcie UAT flow** tak, aby przejścia simple/detail i drilldown były spójne oraz gotowe do dalszej integracji UI.

## 3) Co robimy dalej (kolejność)

1. ✅ **P3-01** — raport klient simple/detail + drilldown klient -> projekt -> pozycje.
2. ✅ **P3-02** — raport projekt simple/detail (direct cost, budget usage, mix billing), w tym szczegóły: wpisy czasu + koszty projektowe + billing mix breakdown.
3. ✅ **P3-03** — raport czasu pracy line-by-line + paginacja (filtr `per_page`, numer strony, metadane i nawigacja stron w admin view).
4. ✅ **P3-04** — eksport CSV/XLS 1:1 zgodny z aktywnymi filtrami i widokiem (uwzględnia `mode`, `detail`, `page_num`, `per_page`).
5. ✅ **P4-01** — finalne agregacje controllingowe vs operacyjne dopięte w raporcie OMD (operational_result vs controlling_overhead/controlling_result).
6. ✅ **P4-02** — UI OMD z legendą definicji i pełnym eksportem (kolumny 1:1 z eksportem, w tym przychód/koszt czasu).
7. ✅ **P5-01** — feature flags + canary rollout (reports v1: `off` / `admins` / `all`).
8. ✅ **P5-02** — monitoring wydajności (czas generowania/rekordy/rollout) + plan rollback przez flagę `erp_omd_reports_v1_rollout`.
9. ✅ **P5-03** — raport powdrożeniowy i plan cleanup legacy (`docs/WB_P5_03_POST_DEPLOY_REPORT.md`).
10. ✅ **P5-04** — wygaszenie canary/legacy: reports v1 aktywny globalnie (`all`), usunięte przełączniki UI rollout.
11. ✅ **P5-05** — docelowe SLO metryk raportowych utrwalone i wystawione w `system/status`.
12. ✅ **P5-06** — dokumentacja release/on-call skonsolidowana (`GO_LIVE` + runbook on-call).
13. ✅ **P1-04 hardening** — regresje okresów domknięte o walidację zakresu miesiąca (`YYYY-MM`, odrzucenie `00/13`).
14. ✅ **P1-04 API hardening** — endpointy `/periods` i `/periods/.../transition` odrzucają miesiące spoza zakresu (`YYYY-MM`, np. `2026-13`) kodem 422.
15. ✅ **P5-monitoring hardening** — `system/status` zwraca freshness metryk Reports v1 (wiek próbki, próg, status świeżości) + próg konfigurowalny z panelu ustawień.
16. ✅ **P5-monitoring UX** — baner monitoringu w raportach pokazuje wiek poprzedniej próbki metryk i status fresh/stale wg progu z ustawień.
17. ✅ **P2 UX bridge** — dodany szybki link „Podgląd kontraktu dashboard-v1 (JSON)” z raportów admina (podgląd payloadu dla bieżącego miesiąca/mode).
18. ✅ **P2 UX bridge+** — podgląd dashboard-v1 z raportów ma osobne linki dla `scope=project` i `scope=client`.
19. ✅ **P2 UX helper** — w raporcie dodany inline „Szybki smoke test (UX)” (checklista klików do lokalnej weryfikacji paginacji/eksportu/dashboard links).
20. ✅ **P5-monitoring actionable status** — `system/status` zwraca operacyjny status health (`ok/warn/alert`) z powodami i rekomendowanymi akcjami (p95/freshness/missing signals).
21. ✅ **P2 dashboard UX controls** — filtry raportów mają teraz sterowanie parametrami podglądu `dashboard-v1` (`scope`, limity), plus szybki link do `system/status`.
22. ✅ **P5 SLO tuning controls** — próg `generation_ms_p95_max` dla Reports v1 jest konfigurowalny z panelu ustawień i odczytywany przez `system/status`.
23. ✅ **P5 docs consistency (post-canary)** — checklista `GO_LIVE_REPORTS_V1_CHECKLIST` zaktualizowana do trybu steady-state (bez legacy przełączania `admins -> all`).
24. ✅ **P5 SLO calibration signals (24-a)** — `system/status` zwraca `sample_target_min`, `calibration_state` i rekomendowany próg `generation_ms_p95_recommended_max`.
25. **Kolejny etap** — obserwacja produkcyjna i decyzja kalibracyjna na bazie min. 20 próbek (`calibration_state=ready`).

## 4) Instrukcja startowa do nowego chatu

W nowym chacie wklej:

1. `Hasło: RAPORTY_NEW. Kontynuujemy Wariant B v1.`
2. `Najpierw pokaż git status + krótki diff od ostatniego commita.`
3. `Realizujemy teraz kolejny etap utrzymaniowy: monitoring/rollback/cleanup (albo następny niezamknięty z listy).`
4. `Po zmianach: testy, commit, PR summary i aktualizacja snapshotu RAPORTY_NEW.`

## 5) Source of truth
- `docs/SPECYFIKACJA_V1_WARIANT_B.txt`
- `docs/POTWIERDZENIE_USTALEŃ_V1_2026-04-01.md`
- `docs/WARIANT_B_PROGRESS.md`
- `docs/RAPORTY_NEW.md` (ten snapshot do wznowienia)
