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

Zalecenie operacyjne:
- utrzymać rollout `admins` przez 24–48h,
- przy braku błędów przełączyć na `all`,
- monitorować logi błędów i regresje wydajności.

## 3) Plan rollback (bez downtime)

Szybki rollback reports v1:
1. Ustawić opcję `erp_omd_reports_v1_rollout=off`.
   - można to zrobić w panelu: **Ustawienia -> Reports v1 rollout**.
2. Zweryfikować ukrycie widoku v1.
3. Pozostawić nowe tabele i logikę pasywnie (bez rollbacku schematu DB).

## 4) Cleanup legacy — zakres po stabilizacji

Po okresie stabilizacji:
- usunąć nieużywane ścieżki legacy raportów,
- usunąć martwe helpery i tymczasowe obejścia rolloutowe,
- ujednolicić filtry statusów projektów do `archiwum` (usunąć legacy `inactive` z UI),
- skonsolidować dokumentację release + checklistę on-call,
- utrwalić finalny zestaw metryk SLO dla raportów.

## 5) Decyzja końcowa

WB-P5-03 uznany za zakończony: raport powdrożeniowy i plan cleanup legacy przygotowane.
