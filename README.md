# ERP OMD

ERP_OMD V2 — wersja 3.2.0 przygotowana pod wdrożenie Sprintu 10.

> Uwaga dot. numeracji: zakres „kalendarz projektów + Google Calendar” odpowiada Sprintowi 4 w planie ERP_4.0 (`docs/ERP_4_0_BACKLOG_MASTER.md`), mimo że w linii historycznej V2 występuje jako część pakietu Sprintu 10.

## Dokumentacja systemu
- Pełny opis systemu i propozycje usprawnień: `../docs/ERP_OMD_SYSTEM_OVERVIEW.md`
- Plan rozwoju frontendu dla pracowników i managerów (`FRONT`): `../docs/FRONT_PLAN.md`

## FRONT
- Frontowe logowanie: `/erp-front/login/`
- Routing użytkownika po logowaniu:
  - pracownik: `/erp-front/worker/`
  - manager: `/erp-front/manager/`
- Wylogowanie: `/erp-front/logout/`
- FRONT-2 (pracownik):
  - własny formularz wpisu czasu,
  - lista własnych wpisów,
  - filtrowanie po dacie / projekcie / statusie,
  - edycja i usuwanie własnych wpisów `submitted`,
  - skróty zakresów: dziś / tydzień / miesiąc / wszystko,
  - kalendarz własnych godzin w wybranym miesiącu,
  - wybór dnia z kalendarza i panel szczegółów dla wskazanej daty,
  - szybkie szablony na bazie ostatnich wpisów pracownika

## Build ZIP
- Sprint 1 (historyczny): `./scripts/build-sprint-1-zip.sh`
- Sprint 2 (historyczny): `./scripts/build-sprint-2-zip.sh`
- Sprint 3 (historyczny): `./scripts/build-sprint-3-zip.sh`
- Sprint 4 (historyczny): `./scripts/build-sprint-4-zip.sh`
- Sprint 5 (historyczny): `./scripts/build-sprint-5-zip.sh`
- Sprint 6 (historyczny): `./scripts/build-sprint-6-zip.sh`
- Sprint 7 (historyczny): `./scripts/build-sprint-7-zip.sh`
- Sprint 8 RC (historyczny): `./scripts/build-sprint-8-rc.sh`
- Sprint 9 (historyczny): `./scripts/build-sprint-9.sh`
- Sprint 10 (aktualny): `./scripts/build-sprint-10.sh`

## Testy Sprintu 3
- Automatyczne sanity checki: `./scripts/test-sprint-3.sh`
- Test domenowy time trackingu: `php tests/time-entry-service-test.php`
- Plan odbioru ręcznego i bramka do Sprintu 4: `docs/SPRINT_3_ACCEPTANCE_PLAN.md`

## Testy Sprintu 4
- Automatyczne sanity checki: `./scripts/test-sprint-4.sh`
- Test domenowy finansów projektu: `php tests/project-financial-service-test.php`
- Checklista odbiorowa Sprintu 4: `docs/SPRINT_4_CHECKLIST.md`

## Testy Sprintu 5
- Automatyczne sanity checki: `./scripts/test-sprint-5.sh`
- Test domenowy kosztorysów: `php tests/estimate-service-test.php`
- Checklista odbiorowa Sprintu 5: `docs/SPRINT_5_CHECKLIST.md`
- Release Closure Sprintu 5: `docs/RELEASE_CLOSURE_SPRINT_5_2026-04-15.md`

## Testy Sprintu 6
- Automatyczne sanity checki: `./scripts/test-sprint-6.sh`
- Test domenowy raportów: `php tests/reporting-service-test.php`
- Checklista odbiorowa Sprintu 6: `docs/SPRINT_6_CHECKLIST.md`
- Tickety + Definition of Done Sprintu 6: `docs/SPRINT_6_TICKETS_DOD.md`
- Prompt startowy realizacji Sprintu 6: `docs/PROMPT_SPRINT_6_EXECUTION.md`

## Testy Sprintu 7
- Automatyczne sanity checki: `./scripts/test-sprint-7.sh`
- Test domenowy alertów: `php tests/alert-service-test.php`
- Checklista odbiorowa Sprintu 7: `docs/SPRINT_7_CHECKLIST.md`

## Testy Sprintu 8 RC
- Automatyczne sanity checki: `./scripts/test-sprint-8.sh`
- Test REST API / hardening: `php tests/rest-api-test.php`
- Checklista odbiorowa Sprintu 8: `docs/SPRINT_8_CHECKLIST.md`

## Testy Sprintu 9
- Automatyczne sanity checki: `./scripts/test-sprint-9.sh`
- Test walidacji klient/projekt: `php tests/client-project-service-test.php`
- Test REST API / hardening: `php tests/rest-api-test.php`
- Checklista odbiorowa Sprintu 9: `docs/SPRINT_9_CHECKLIST.md`

## Testy Sprintu 10
- Automatyczne sanity checki: `./scripts/test-sprint-10.sh`
- Test workflow wniosków projektowych: `php tests/project-request-service-test.php`
- Checklista odbiorowa Sprintu 10: `docs/SPRINT_10_CHECKLIST.md`
