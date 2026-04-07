# Reports v1 — release note (steady-state monitoring + status-label contract)

Data: 2026-04-07

## Co zostało domknięte

1. Monitoring steady-state Reports v1:
   - sygnały `sustained_drift_*` aktywne po formalnym zamknięciu kalibracji SLO,
   - rekomendacje rollback/tuning wyświetlane tylko przy trwałym dryfie.
2. Kontrakt status-label:
   - API dostarcza `period_status_label` i `status_actions[].to_status_label`,
   - frontend używa tych pól jako źródła etykiet (bez fallbacku underscore).
3. UX admin:
   - baner steady-state z poziomem alertu i akcjami operatorskimi,
   - quick-view historii dryfu (lista próbek, toggle drift-only, licznik `x/y`),
   - etykiety widoczne dla użytkownika bez underscore (np. `DO ROZLICZENIA`).

## Co to daje operacyjnie

- szybsze rozpoznanie, czy problem jest trwały, czy chwilowy,
- mniej przedwczesnych rollbacków,
- spójny sposób prezentacji statusów między API i UI,
- czytelny handover dla on-call bez „wiedzy ukrytej”.

## Handover checklist (on-call)

1. Wejdź w `ERP OMD → Raporty` i sprawdź baner steady-state:
   - czy poziom (`info/success/warning`) odpowiada stanowi metryk,
   - czy link do ustawień SLO (`#reports-v1-slo-monitoring`) działa.
2. Zweryfikuj quick-view historii:
   - `Pokaż wszystkie próbki` ↔ `Pokaż tylko próbki z dryfem`,
   - licznik `x/y` zgadza się z listą próbek.
3. Zweryfikuj kontrakt labeli statusu:
   - UI pokazuje `DO ROZLICZENIA` (bez underscore),
   - endpoint `dashboard-v1` zwraca `period_status_label` i `to_status_label`.
4. Przy `sustained_drift_detected = true`:
   - wykonaj runbook rollback/tuning,
   - zanotuj decyzję operatorską (kto, kiedy, próg, akcja).

## Minimalny smoke po wdrożeniu

- `php tests/rest-api-test.php`
- `php tests/status-label-contract-test.php`
- `node --check erp-omd/assets/js/admin.js`
