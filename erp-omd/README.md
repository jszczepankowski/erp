# ERP OMD

ERP_OMD V2 — wersja 0.10.6 przygotowana pod wdrożenie Sprintu 10.

## Utrzymanie repozytorium
- Przed publikacją zmian uruchom przynajmniej test sanity odpowiadający aktywnemu sprintowi.

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
- Sprint 1 (historyczny): `./scripts/archiwum/2026-Q2/build-sprint-1-zip.sh`
- Sprint 2 (historyczny): `./scripts/archiwum/2026-Q2/build-sprint-2-zip.sh`
- Sprint 3 (historyczny): `./scripts/archiwum/2026-Q2/build-sprint-3-zip.sh`
- Sprint 4 (historyczny): `./scripts/archiwum/2026-Q2/build-sprint-4-zip.sh`
- Sprint 5 (historyczny): `./scripts/archiwum/2026-Q2/build-sprint-5-zip.sh`
- Sprint 6 (historyczny): `./scripts/archiwum/2026-Q2/build-sprint-6-zip.sh`
- Sprint 7 (historyczny): `./scripts/archiwum/2026-Q2/build-sprint-7-zip.sh`
- Sprint 8 RC (historyczny): `./scripts/archiwum/2026-Q2/build-sprint-8-rc.sh`
- Sprint 9 (historyczny): `./scripts/archiwum/2026-Q2/build-sprint-9.sh`
- Sprint 10 (aktualny): `./scripts/archiwum/2026-Q2/build-sprint-10.sh`

## Testy Sprintu 3
- Automatyczne sanity checki: `./scripts/archiwum/2026-Q2/test-sprint-3.sh`
- Test domenowy time trackingu: `php tests/time-entry-service-test.php`
- Plan odbioru ręcznego i bramka do Sprintu 4: `docs/SPRINT_3_ACCEPTANCE_PLAN.md`

## Testy Sprintu 4
- Automatyczne sanity checki: `./scripts/archiwum/2026-Q2/test-sprint-4.sh`
- Test domenowy finansów projektu: `php tests/project-financial-service-test.php`
- Checklista odbiorowa Sprintu 4: `docs/archiwum/2026-Q2/SPRINT_4_CHECKLIST.md`

## Testy Sprintu 5
- Automatyczne sanity checki: `./scripts/archiwum/2026-Q2/test-sprint-5.sh`
- Test domenowy kosztorysów: `php tests/estimate-service-test.php`
- Checklista odbiorowa Sprintu 5: `docs/archiwum/2026-Q2/SPRINT_5_CHECKLIST.md`

## Testy Sprintu 6
- Automatyczne sanity checki: `./scripts/archiwum/2026-Q2/test-sprint-6.sh`
- Test domenowy raportów: `php tests/reporting-service-test.php`
- Checklista odbiorowa Sprintu 6: `docs/archiwum/2026-Q2/SPRINT_6_CHECKLIST.md`

## Testy Sprintu 7
- Automatyczne sanity checki: `./scripts/archiwum/2026-Q2/test-sprint-7.sh`
- Test domenowy alertów: `php tests/alert-service-test.php`
- Checklista odbiorowa Sprintu 7: `docs/archiwum/2026-Q2/SPRINT_7_CHECKLIST.md`

## Testy Sprintu 8 RC
- Automatyczne sanity checki: `./scripts/archiwum/2026-Q2/test-sprint-8.sh`
- Test REST API / hardening: `php tests/rest-api-test.php`
- Checklista odbiorowa Sprintu 8: `docs/archiwum/2026-Q2/SPRINT_8_CHECKLIST.md`

## Testy Sprintu 9
- Automatyczne sanity checki: `./scripts/archiwum/2026-Q2/test-sprint-9.sh`
- Test walidacji klient/projekt: `php tests/client-project-service-test.php`
- Test REST API / hardening: `php tests/rest-api-test.php`
- Checklista odbiorowa Sprintu 9: `docs/archiwum/2026-Q2/SPRINT_9_CHECKLIST.md`

## Testy Sprintu 10
- Automatyczne sanity checki: `./scripts/archiwum/2026-Q2/test-sprint-10.sh`
- Test workflow wniosków projektowych: `php tests/project-request-service-test.php`
- Checklista odbiorowa Sprintu 10: `docs/archiwum/2026-Q2/SPRINT_10_CHECKLIST.md`
