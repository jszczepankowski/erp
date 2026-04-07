# RAPORTY_NEW — snapshot do wznowienia prac

Data snapshotu: 2026-04-01
Gałąź: `work`
Hasło wznowienia: `RAPORTY_NEW`

## 1) Co już mamy zrobione

### Fundament domeny i API
- Wdrożony model okresów (`LIVE` / `DO ROZLICZENIA` / `ZAMKNIETY`) wraz z repo i serwisem okresów.
- Wdrożony audyt korekt admina (`erp_omd_adjustment_audit`) + endpointy `GET|POST /adjustments`.
- Dodane endpointy okresów (`/periods`, `/periods/{YYYY-MM}`, `/periods/{YYYY-MM}/transition`).
- Dodany kontrakt backendowy `GET /dashboard-v1` (status miesiąca, checklista gotowości, trend, rentowność, kolejka, korekty, drilldown links).

### Reguły biznesowe i raportowanie
- Blokady edycji w miesiącach zamykanych/zamkniętych dla non-admin.
- Wymóg `reason` i logowanie korekt admina w okresach zablokowanych.
- Reporting działa z `mode` (`LIVE` / `DO ROZLICZENIA` / `ZAMKNIETY`).
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
25. ✅ **P5 SLO calibration progress (25-a)** — `system/status` zwraca `samples_missing_to_calibration`, żeby operacyjnie śledzić brakujące próbki do decyzji.
26. ✅ **P5 SLO calibration UX (26-a)** — baner monitoringu raportów pokazuje postęp kalibracji (`próbki/target` + `brakujące`), aby łatwiej domknąć etap obserwacji.
27. ✅ **P5 SLO decision signal (27-a)** — `system/status` zwraca `calibration_decision_ready`, więc moment decyzji kalibracyjnej jest jednoznaczny API-owo.
28. ✅ **P5 SLO workflow hint (28-a)** — `system/status` zwraca `calibration_next_action`, więc on-call ma jednoznaczną podpowiedź „co dalej” w kalibracji.
29. ✅ **P5 SLO tuning delta (29-a)** — `system/status` zwraca `generation_ms_p95_threshold_delta` + `generation_ms_p95_tuning_direction` (increase/decrease/keep).
30. ✅ **P5 SLO apply helper (30-a)** — w ustawieniach widoczny jest rekomendowany próg p95 + opcja zastosowania go przy zapisie.
31. ✅ **P5 SLO decision UX (31-a)** — baner raportów pokazuje status decyzji kalibracyjnej (`ready/pending`) oraz następną akcję operatorską.
32. ✅ **P5 SLO decision UX in settings (32-a)** — panel Ustawień pokazuje status kalibracji, brakujące próbki i następną akcję przy konfiguracji progu p95.
33. ✅ **P5 SLO decision audit (33-a)** — możliwe potwierdzenie finalnej decyzji progu p95 z zapisem audytowym (`decided_at`, `user_id`, próg, rekomendacja, sample_count) + ekspozycja w `system/status`.
34. ✅ **P5 formal closure (34-a)** — formalne zamknięcie kalibracji SLO po potwierdzonej decyzji (zapis closure audit + ekspozycja statusu closure w `system/status` i panelu ustawień).
35. ✅ **P5 monitoring telemetry hardening (35-a)** — `system/status` liczy `error_rate_percent` z logu metryk Reports v1 (`has_error`) i nie oznacza już tego sygnału jako brakującego.
36. ✅ **P3/P5 reports UX cleanup (36-a)** — filtry raportów pokazują poprawne statusy zależnie od typu raportu, wrócił jawny wybór wersji `Podstawowa/Szczegółowa`, a panel został odchudzony z diagnostycznych elementów technicznych.
37. ✅ **P3 status cleanup archiwum (37-a)** — UI projektów używa `archiwum` (zamiast legacy `inactive`) w formularzu, filtrach i akcjach masowych.
38. ✅ **P3/P5 month switcher + cleanup (38-a)** — dedykowany wybór miesiąca działa w panelu admina `ERP OMD — Dashboard`, usunięto box switchera z dashboardu managera, a eksport CSV rozróżnia wersję podstawową i szczegółową.
39. ✅ **P5 steady-state drift guard (39-a)** — `system/status` (po formalnym zamknięciu kalibracji SLO) wykrywa trwały dryf metryk (`sustained_drift_detected`) na oknie ostatnich próbek i rekomenduje rollback/tuning tylko dla utrzymującego się dryfu.
40. ✅ **P5 steady-state UX banner (40-a)** — raporty admina pokazują baner steady-state (status drift + akcje operatorskie) oraz szybki link do runbooka/ustawień SLO.
41. ✅ **P5 steady-state drift history (41-a)** — baner raportów pokazuje mini-historię ostatnich próbek drift (timestamp, report_type, ms, err, przekroczenie progu), żeby operator widział trend bez odpytywania API.
42. ✅ **P5 steady-state drift-only toggle (42-a)** — baner raportów ma przełącznik „pokaż tylko próbki z dryfem / pokaż wszystkie”, żeby skrócić diagnostykę przy dużym ruchu.
43. ✅ **P5 steady-state drift counter (43-a)** — quick view w banerze pokazuje licznik próbek dryfowych (`x/y`), żeby szybciej ocenić skalę problemu.
44. ✅ **P5 status naming consistency + smoke (44-a)** — etykiety UI używają formy `DO ROZLICZENIA` (bez underscore), a w raporcie jest szybka checklista smoke do lokalnej weryfikacji.
45. ✅ **P5 shared status-label helper contract (45-a)** — dashboard-v1 zwraca `period_status_label` i `status_actions[].to_status_label`, a frontend używa tych pól jako źródła etykiet (fallback tylko awaryjnie).
46. ✅ **P5 status-label contract regression test (46-a)** — dodany dedykowany test regresji UI/API, który pilnuje pól `period_status_label` / `to_status_label` oraz smoke renderingu `DO ROZLICZENIA`.
47. ✅ **P5 status-label fallback cleanup (47-a)** — frontend dashboard-v1 używa już bezpośrednio `period_status_label` / `to_status_label` i usunięto legacy fallback formatter underscore.
48. ✅ **P5 final closure note + handover (48-a)** — dodany release note steady-state + checklista handover on-call dla monitoringu i status-label contract (`docs/REPORTS_V1_STEADY_STATE_RELEASE_NOTE.md`).
49. ✅ **P5 maintenance telemetry expansion (49-a)** — `system/status` rozszerzony o telemetryczny udział dryfu w oknie steady-state (`sustained_drift_positive_samples`, `sustained_drift_positive_ratio_percent`, `sustained_drift_last_sample_at`), aby on-call szybciej ocenił skalę ryzyka bez ręcznego liczenia.
50. ✅ **P5 maintenance UX drift ratio (50-a)** — baner steady-state pokazuje teraz udział dryfu jako `x/y (z%)` oraz timestamp ostatniej próbki monitoringu, co skraca triage on-call.
51. ✅ **P5 maintenance banner contract guard (51-a)** — dodany test kontraktu bannera steady-state (ratio `%` + timestamp ostatniej próbki), aby uniknąć cichych regresji UX.
52. **Kolejny etap** — utrzymanie: tylko bugfixy/telemetria po obserwacji produkcyjnej.

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
- `docs/PLAN_PELNE_WDROZENIE_WARIANT_B_V1.md` (plan domknięcia 100% MUST ze specyfikacji)
- `docs/WDROZENIE_V1_DZIENNIK.md` (bieżący tracker etapów/kroków do wznowienia cross-chat)
- `docs/UAT_MASTER_PASS_V1_2026-04-07.md` (artefakt przebiegu UAT Master Pass v1)

## 6) Audyt RAPORTY_NEW (2026-04-07) — braki i duplikaty

### Co znalezione
- **Duplikacja semantyczna:** punkty **13** i **14** dotyczą tego samego obszaru (`WB-P1-04` hardening walidacji miesiąca), tylko rozdzielają warstwę domenową i API. W praktyce to jeden pakiet prac i warto traktować je jako jeden krok z podpunktami.
- **Niespójność nagłówka sekcji 3:** tytuł „Co robimy dalej” sugeruje backlog, ale punkty 1–51 są już zamknięte (`✅`). To utrudnia szybkie wznowienie pracy i może powodować ponowne „przerabianie” domkniętych tematów.
- **Potencjalny brak operacyjny:** punkt **52** ma opis kolejnego etapu utrzymaniowego, ale bez jawnej checklisty wejścia/wyjścia (Definition of Done) i bez numerowanych ID zadań utrzymaniowych.

### Decyzja porządkowa (od teraz)
- Traktujemy etapy 1–51 jako **historię zamkniętych prac**.
- Etap 52 traktujemy jako **aktywny strumień utrzymaniowy** i prowadzimy go jako osobne zadania `WB-MNT-*`.

### Proponowane zadania `WB-MNT-*` (najbliższa kolejka)
1. **WB-MNT-01 — Monitoring drift triage**
   - Zebrać min. 7 dni próbek steady-state i potwierdzić, czy `sustained_drift_detected` generuje alerty tylko dla realnych anomalii.
2. **WB-MNT-02 — Rollback drill**
   - Przejść na sucho runbook rollback (flagi, komunikacja, check po rollbacku) i zapisać czasy wykonania kroków.
3. **WB-MNT-03 — Reports perf spot-check**
   - Zweryfikować p95 i error-rate dla najcięższych scenariuszy (`projects detail`, duże `per_page`) oraz porównać z progiem SLO.
4. **WB-MNT-04 — Cleanup dokumentacji operacyjnej**
   - Ujednolicić nazewnictwo „utrzymanie/stabilizacja” między `RAPORTY_NEW`, `WARIANT_B_PROGRESS`, `GO_LIVE_REPORTS_V1_CHECKLIST`.

### Definition of Done dla etapu utrzymaniowego
- Każde zadanie `WB-MNT-*` ma: wynik, datę, właściciela i link do artefaktu (log/test/notatka).
- `docs/RAPORTY_NEW.md` aktualizowany po każdym zamkniętym `WB-MNT-*`.
- Brak otwartych alertów krytycznych dot. raportów przez uzgodnione okno obserwacji.
