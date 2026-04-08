# ETAP 4 / KROK 4.1 — checklista ujednolicenia dokumentacji końcowej

Data: 2026-04-08  
Status: **IN PROGRESS**

## 1) Cel kroku

Ujednolicić dokumentację końcową tak, aby:
- była jednoznaczna dla utrzymania i on-call,
- nie zawierała sprzecznych statusów etapów,
- miała jeden spójny „source of truth”.

## 2) Zakres dokumentów do synchronizacji

- `docs/WDROZENIE_V1_DZIENNIK.md`
- `docs/UAT_MASTER_PASS_V1_2026-04-07.md`
- `docs/UAT_EXECUTION_SHEET_2026-04-07.md`
- `docs/RAPORTY_NEW.md`
- `docs/PLAN_PELNE_WDROZENIE_WARIANT_B_V1.md`
- raporty operacyjne:
  - `docs/REPORTING_BENCHMARK_BASELINE_2026-04-07.md`
  - `docs/SECURITY_ENDPOINTS_TEST_REPORT_2026-04-08.md`
  - `docs/STAGING_MIGRATION_REGRESSION_REPORT_2026-04-08.md`
  - `docs/ROLLBACK_DRILL_REPORT_2026-04-08.md`
  - `docs/SLO_REVIEW_REPORT_2026-04-08.md`
  - `docs/OPERACYJNA_MATRYCA_REAKCJI_2026-04-08.md`

## 3) Checklista „na żywo” (PASS/FAIL)

### C4.1 — Spójność statusów etapów
- [ ] Etapy/kroki w `WDROZENIE_V1_DZIENNIK.md` są zgodne z faktycznie domkniętymi raportami.
- [ ] Nie ma sprzeczności między „Status globalny”, checklistą etapów i logiem aktualizacji.

### C4.2 — Spójność UAT
- [ ] `UAT_MASTER_PASS` i `UAT_EXECUTION_SHEET` mają zgodne wyniki PASS/PASS WARUNKOWY/FAIL.
- [ ] Wszystkie ręczne potwierdzenia użytkownika są odnotowane i datowane.

### C4.3 — Spójność planu vs wykonania
- [ ] `PLAN_PELNE_WDROZENIE_WARIANT_B_V1.md` jest zgodny z aktualnym stanem ETAP 1–3.
- [ ] `RAPORTY_NEW.md` ma spójny status strumienia utrzymaniowego (WB-MNT) względem dziennika.

### C4.4 — Spójność artefaktów operacyjnych
- [ ] Benchmark/security/staging/rollback/SLO/matryca mają status i datę.
- [ ] Każdy artefakt ma jasny wniosek operacyjny.

### C4.5 — Konsolidacja końcowa
- [ ] Przygotowana lista dokumentów „Source of truth” dla ETAP 4.2.
- [ ] Wpis do dziennika z wynikiem kroku 4.1 (`PASS/PASS WARUNKOWY/FAIL`).

## 4) Kryterium wyniku

- **PASS**: wszystkie punkty C4.1–C4.5 odhaczone.
- **PASS WARUNKOWY**: brak sprzeczności krytycznych, otwarte tylko porządki redakcyjne.
- **FAIL**: wykryte sprzeczne statusy lub brakujące artefakty uniemożliwiające operacyjne wznowienie.
