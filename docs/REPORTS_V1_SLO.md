# Reports v1 — docelowe SLO/SLI

Data: 2026-04-02

## Cele SLO

1. **P95 czasu generowania raportu** (`generation_ms_p95`) <= **2500 ms**.
2. **Error rate endpointów raportowych** <= **2.0%** (sygnał do integracji z logami/app monitoringiem).
3. **Wolumen ostrzegawczy** (`rows_count_warn_threshold`) = **5000** rekordów.

## Źródło sygnałów

- `GET /erp-omd/v1/system/status`
  - `feature_flags.reports_v1_metrics_log` (ostatnie próbki czasu/liczności),
  - `feature_flags.reports_v1_slo` (progi),
  - `feature_flags.reports_v1_slo_status` (stan wyliczony z próbek).

## Notatka operacyjna

`reports_v1_slo_status.missing_signals` jawnie wskazuje brakujące sygnały
(np. `error_rate_percent`) dopóki nie zostaną podłączone metryki błędów z runtime/logów.
