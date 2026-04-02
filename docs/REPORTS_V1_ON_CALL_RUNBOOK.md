# Reports v1 — runbook on-call

Data: 2026-04-02

## 1) Sygnały do obserwacji

Podstawowe źródło: `GET /erp-omd/v1/system/status`.

Sprawdzaj:
- `feature_flags.reports_v1_last_metrics` (ostatnia próbka),
- `feature_flags.reports_v1_metrics_log` (ostatnie próbki),
- `feature_flags.reports_v1_slo` i `feature_flags.reports_v1_slo_status`.

## 2) Kryteria alarmowe

1. `generation_ms_p95_within_target = false`.
2. Czas generowania raportów rośnie skokowo (> 2x względem mediany z ostatnich próbek).
3. Użytkownicy raportują timeouty / puste eksporty CSV.

## 3) Procedura reakcji

1. Potwierdź incydent i zapisz timestamp + zakres raportów (`report_type`).
2. Zbierz payload z `system/status` i logów PHP/WP.
3. Zweryfikuj czy problem dotyczy:
   - jednego typu raportu,
   - konkretnego miesiąca,
   - dużych wolumenów (`rows_count`).
4. Jeśli degradacja trwa > 15 min:
   - ogranicz użycie ciężkich filtrów,
   - uruchom diagnostykę SQL dla zapytań raportowych,
   - eskaluj do ownera backendu ERP OMD.
5. Po ustabilizowaniu: opisz RCA + działania korygujące.

## 4) Post-incident checklist

- [ ] wpis do changelogu operacyjnego,
- [ ] aktualizacja metryk/SLO jeśli próg był zbyt niski/wysoki,
- [ ] uzupełnienie testów regresji dla znalezionego przypadku.
