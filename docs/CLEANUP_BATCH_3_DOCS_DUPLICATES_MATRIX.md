# Cleanup Batch 3 — przegląd `docs/*` pod duplikaty i mapa zastępowalności 1:1

Data: 2026-05-13
Cel: wskazać które pliki **zostawić / przenieść / usunąć** oraz podać mapę zastępowalności 1:1.

## A) Mapa 1:1 — duplikaty i następcy

| Plik źródłowy | Decyzja | Następca 1:1 | Uzasadnienie |
|---|---|---|---|
| `docs/RAPORTY_DASHBOARD_PRZYKLADY.md` | usunąć | `docs/RAPORTY_NEW.md` | nowszy, bardziej aktualny opis raportów |
| `docs/SPECYFIKACJA_V1_WARIANT_B.txt` | usunąć | `docs/SPEC_V3_1_FUNKCJONALNOSC_LOGIKA_ZALEZNOSCI.md` | stara spec v1 zastąpiona spec v3.1 |
| `docs/PLAN_PELNE_WDROZENIE_WARIANT_B_V1.md` | przenieść | `docs/WARIANT_B_PROGRESS.md` | plan historyczny, bieżący status w progress |
| `docs/ERP_OMD_V2_ROADMAP.md` | przenieść | `ERP_v4.2` | roadmapa v2 historyczna, obowiązująca v4.2 |
| `docs/SPRINT_3_ACCEPTANCE_PLAN.md` | przenieść | `docs/UAT_MASTER_PASS_V1_2026-04-07.md` | plan zastąpiony raportem wykonania |
| `docs/UAT_D4_MINI_SCENARIUSZ_CHECKLISTA_2026-04-08.md` | usunąć | `docs/archiwum/UAT_D4_MINI_SCENARIUSZ_CHECKLISTA_2026-04-08.md` | duplikat istnieje już w archiwum |
| `docs/CHECKLISTA_UJEDNOLICENIA_DOKUMENTACJI_2026-04-08.md` | usunąć | `docs/archiwum/CHECKLISTA_UJEDNOLICENIA_DOKUMENTACJI_2026-04-08.md` | duplikat istnieje już w archiwum |

## B) Zostawić (aktywnie operacyjne)

- `docs/ERP_OMD_SYSTEM_OVERVIEW.md`
- `docs/RUNBOOK_GOOGLE_CALENDAR_SYNC_ERROR.md`
- `docs/RELEASE_NOTES_TEST_EVIDENCE_POLICY.md`
- `docs/REPO_CLEANUP_INVENTORY.md`
- `docs/ODTWARZANIE_BACKUPU_ERP.md`
- `docs/KSEF_API_INTEGRATION_IMPLEMENTATION_PLAN_2026-04-25.md`
- `docs/KSEF_REDESIGN_PLAN_2026-04-17.md`
- `docs/SECURITY_ENDPOINTS_TEST_REPORT_2026-04-08.md`
- `docs/REPORTING_BENCHMARK_BASELINE_2026-04-07.md`

## C) Przenieść do `docs/archiwum/2026-Q2/`

- `docs/BACKLOG_V1_1_2026-04-08.md`
- `docs/ERP_4_0_BACKLOG_MASTER.md`
- `docs/FRONT_PLAN.md`
- `docs/POTWIERDZENIE_USTALEŃ_V1_2026-04-01.md`
- `docs/PROMPT_SPRINT_6_EXECUTION.md`
- `docs/RELEASE_CLOSURE_SPRINT_5_2026-04-15.md`
- `docs/RELEASE_CLOSURE_SPRINT_7_2026-04-23.md`
- `docs/SPRINT_6_TICKETS_DOD.md`
- `docs/SPRINT_7_CLIENT_PANEL_TICKETS_DOD.md`
- `docs/STAGING_MIGRATION_REGRESSION_REPORT_2026-04-08.md`
- `docs/UAT_CHECKLIST_SPRINT_7_CLIENT_PANEL_2026-04-23.md`
- `docs/V1_FULLY_IMPLEMENTED_NOTE_2026-04-08.md`
- `docs/WDROZENIE_V1_DZIENNIK.md`

## D) Usunąć (po potwierdzeniu)

- `docs/RAPORTY_DASHBOARD_PRZYKLADY.md` (zastąpiony przez `docs/RAPORTY_NEW.md`)
- `docs/SPECYFIKACJA_V1_WARIANT_B.txt` (zastąpiony przez `docs/SPEC_V3_1_FUNKCJONALNOSC_LOGIKA_ZALEZNOSCI.md`)
- `docs/UAT_D4_MINI_SCENARIUSZ_CHECKLISTA_2026-04-08.md` (duplikat w archiwum)
- `docs/CHECKLISTA_UJEDNOLICENIA_DOKUMENTACJI_2026-04-08.md` (duplikat w archiwum)

## E) Podsumowanie batch 3

- **zostawić:** 9
- **przenieść:** 13
- **usunąć:** 4
- **duplikaty 1:1 z mapą następcy:** 7

