# Go-live checklist — Reports v1 (admins -> all)

Data: 2026-04-02

## 1) Warunki wejścia

- rollout ustawiony na `admins` przez min. 24h,
- brak krytycznych błędów w logach PHP/WP związanych z raportami v1,
- poprawne generowanie eksportów CSV na danych produkcyjnych,
- potwierdzona poprawność metryk OMD (operacyjne vs controllingowe) przez właściciela biznesowego.

## 2) Kroki przełączenia

1. Ustaw w panelu: **Ustawienia -> Reports v1 rollout = all**.
2. Wykonaj smoke test:
   - raport `time_entries` (filtrowanie + paginacja),
   - raport `projects` (detail + billing mix),
   - raport `omd_rozliczenia` (kolumny controllingowe + legenda),
   - eksport CSV dla wszystkich ww. raportów.
3. Sprawdź endpoint statusowy:
   - `GET /erp-omd/v1/system/status`
   - oczekiwane: `feature_flags.reports_v1_rollout = all`.

## 3) Monitoring po przełączeniu (T+0 ... T+48h)

- obserwuj `generation_ms` i `rows_count` dla typów raportów,
- monitoruj wzrost error-rate w logach i timeouty eksportu,
- porównuj wybrane miesiące raportów z danymi referencyjnymi.

## 4) Szybki rollback

Jeśli pojawią się regresje:
1. Ustaw rollout na `off`.
2. Zweryfikuj ukrycie widoku reports v1.
3. Zgłoś incydent i uruchom checklistę analizy przyczyny.
