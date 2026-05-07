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
13. ✅ **P1-04 hardening** — regresje okresów domknięte o walidację zakresu miesiąca (`YYYY-MM`, odrzucenie `00/13`).
14. ✅ **P1-04 API hardening** — endpointy `/periods` i `/periods/.../transition` odrzucają miesiące spoza zakresu (`YYYY-MM`, np. `2026-13`) kodem 422.
17. ✅ **P2 UX bridge** — dodany szybki link „Podgląd kontraktu dashboard-v1 (JSON)” z raportów admina (podgląd payloadu dla bieżącego miesiąca/mode).
18. ✅ **P2 UX bridge+** — podgląd dashboard-v1 z raportów ma osobne linki dla `scope=project` i `scope=client`.
19. ✅ **P2 UX helper** — w raporcie dodany inline „Szybki smoke test (UX)” (checklista klików do lokalnej weryfikacji paginacji/eksportu/dashboard links).
21. ✅ **P2 dashboard UX controls** — filtry raportów mają teraz sterowanie parametrami podglądu `dashboard-v1` (`scope`, limity), plus szybki link do `system/status`.
36. ✅ **P3/P5 reports UX cleanup (36-a)** — filtry raportów pokazują poprawne statusy zależnie od typu raportu, wrócił jawny wybór wersji `Podstawowa/Szczegółowa`, a panel został odchudzony z diagnostycznych elementów technicznych.
37. ✅ **P3 status cleanup archiwum (37-a)** — UI projektów używa `archiwum` (zamiast legacy `inactive`) w formularzu, filtrach i akcjach masowych.
38. ✅ **P3/P5 month switcher + cleanup (38-a)** — dedykowany wybór miesiąca działa w panelu admina `ERP OMD — Dashboard`, usunięto box switchera z dashboardu managera, a eksport CSV rozróżnia wersję podstawową i szczegółową.
44. ✅ **P5 status naming consistency + smoke (44-a)** — etykiety UI używają formy `DO ROZLICZENIA` (bez underscore), a w raporcie jest szybka checklista smoke do lokalnej weryfikacji.
45. ✅ **P5 shared status-label helper contract (45-a)** — dashboard-v1 zwraca `period_status_label` i `status_actions[].to_status_label`, a frontend używa tych pól jako źródła etykiet (fallback tylko awaryjnie).
46. ✅ **P5 status-label contract regression test (46-a)** — dodany dedykowany test regresji UI/API, który pilnuje pól `period_status_label` / `to_status_label` oraz smoke renderingu `DO ROZLICZENIA`.
47. ✅ **P5 status-label fallback cleanup (47-a)** — frontend dashboard-v1 używa już bezpośrednio `period_status_label` / `to_status_label` i usunięto legacy fallback formatter underscore.
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
- `docs/archiwum/UAT_EXECUTION_SHEET_2026-04-07.md` (operacyjna checklista manualna UAT ekranów 1–6)
- `docs/REPORTING_BENCHMARK_BASELINE_2026-04-07.md` (baseline wydajności pod ETAP 2.1)

## 6) Audyt RAPORTY_NEW (2026-04-07) — braki i duplikaty

### Co znalezione
- **Duplikacja semantyczna:** punkty **13** i **14** dotyczą tego samego obszaru (`WB-P1-04` hardening walidacji miesiąca), tylko rozdzielają warstwę domenową i API. W praktyce to jeden pakiet prac i warto traktować je jako jeden krok z podpunktami.
- **Niespójność nagłówka sekcji 3:** tytuł „Co robimy dalej” sugeruje backlog, ale punkty 1–51 są już zamknięte (`✅`). To utrudnia szybkie wznowienie pracy i może powodować ponowne „przerabianie” domkniętych tematów.
- **Potencjalny brak operacyjny:** punkt **52** ma opis kolejnego etapu utrzymaniowego, ale bez jawnej checklisty wejścia/wyjścia (Definition of Done) i bez numerowanych ID zadań utrzymaniowych.

### Decyzja porządkowa (od teraz)
- Traktujemy etapy 1–51 jako **historię zamkniętych prac**.
- Etap 52 traktujemy jako **aktywny strumień utrzymaniowy** i prowadzimy go jako osobne zadania `WB-MNT-*`.

### Proponowane zadania `WB-MNT-*` (najbliższa kolejka)
3. **WB-MNT-03 — Reports perf spot-check**
4. **WB-MNT-04 — Cleanup dokumentacji operacyjnej**

### Definition of Done dla etapu utrzymaniowego
- Każde zadanie `WB-MNT-*` ma: wynik, datę, właściciela i link do artefaktu (log/test/notatka).
- `docs/RAPORTY_NEW.md` aktualizowany po każdym zamkniętym `WB-MNT-*`.
- Brak otwartych alertów krytycznych dot. raportów przez uzgodnione okno obserwacji.
