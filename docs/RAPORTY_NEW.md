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
7. **P5-01** — feature flags + canary rollout (admin -> wszyscy).

## 4) Instrukcja startowa do nowego chatu

W nowym chacie wklej:

1. `Hasło: RAPORTY_NEW. Kontynuujemy Wariant B v1.`
2. `Najpierw pokaż git status + krótki diff od ostatniego commita.`
3. `Realizujemy teraz kolejny krok: P3-01 (albo następny niezamknięty z listy).`
4. `Po zmianach: testy, commit, PR summary i aktualizacja snapshotu RAPORTY_NEW.`

## 5) Source of truth
- `docs/SPECYFIKACJA_V1_WARIANT_B.txt`
- `docs/POTWIERDZENIE_USTALEŃ_V1_2026-04-01.md`
- `docs/WARIANT_B_PROGRESS.md`
- `docs/RAPORTY_NEW.md` (ten snapshot do wznowienia)
