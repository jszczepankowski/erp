# WB-P5-03 — raport powdrożeniowy i plan cleanup legacy

Data: 2026-04-02  
Zakres: Wariant B v1 (P1–P5)

## 1) Status wdrożenia

- ✅ P1: reguły okresów / blokad / korekt (fundament backendowy).
- ✅ P2: dashboard v1 contract.
- ✅ P3: raporty operacyjne simple/detail + paginacja + eksport 1:1.
- ✅ P4: controlling OMD (operacyjne vs controllingowe agregaty + UI legenda).
- ✅ P5: rollout reports v1 przez feature flagi + monitoring i rollback.

Wniosek: pakiet Wariant B v1 jest domknięty funkcjonalnie po stronie backend/admin UI.

## 2) Monitoring po wdrożeniu (T+1 ... T+7)

Monitorowane sygnały:
- czas generowania raportu (`generation_ms`),
- liczba rekordów (`rows_count`),
- tryb rollout (`off` / `admins` / `all`),
- stabilność endpointów i logów PHP/WP.
- flagi rollout dostępne także przez `GET /erp-omd/v1/system/status` (`feature_flags`).
- ostatnie metryki reports v1 utrwalane w opcji `erp_omd_reports_v1_last_metrics` i eksponowane przez `system/status`.
- próbka ostatnich metryk (log) utrwalana w `erp_omd_reports_v1_metrics_log` (do 20 wpisów; API zwraca ostatnie 5).

Zalecenie operacyjne:
- utrzymać rollout `admins` przez 24–48h,
- przy braku błędów przełączyć na `all`,
- monitorować logi błędów i regresje wydajności.

## 3) Plan awaryjny (bez downtime)

Po wygaszeniu canary rollout:
1. Ograniczyć ciężkie zapytania raportowe (szczególnie `detail` + duże `per_page`), aby ustabilizować `generation_ms`.
2. Uruchomić runbook on-call i eskalację do backend ownera.
3. Wdrożyć poprawkę aplikacyjną (hotfix) bez rollbacku schematu DB.

## 4) Cleanup legacy — zakres po stabilizacji

Po okresie stabilizacji:
- [x] usunąć nieużywane ścieżki legacy raportów,
- [x] usunąć martwe helpery i tymczasowe obejścia rolloutowe (reports v1 rollout wymuszony na `all`),
- [x] ujednolicić filtry statusów projektów do `archiwum` (usunąć legacy `inactive` z UI),
- [x] skonsolidować dokumentację release + checklistę on-call (`docs/GO_LIVE_REPORTS_V1_CHECKLIST.md`, `docs/REPORTS_V1_ON_CALL_RUNBOOK.md`),
- [x] utrwalić finalny zestaw metryk SLO dla raportów (`docs/REPORTS_V1_SLO.md` + status przez `system/status`).

## 5) Decyzja końcowa

WB-P5-03 uznany za zakończony: raport powdrożeniowy i plan cleanup legacy przygotowane.

Powiązany dokument operacyjny:
- `docs/GO_LIVE_REPORTS_V1_CHECKLIST.md`
