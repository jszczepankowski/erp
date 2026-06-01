# ERP OMD

ERP_OMD — wersja wtyczki 4.0_dev.

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

## REST API
- Endpoint `erp-omd/v1/dashboard-v1` jest wycofany z aktualnego kontraktu API wersji 4.0_dev; dashboard korzysta z bieżących widoków i endpointów raportowych.

## Build ZIP
- Skrypty buildów sprintowych z katalogu `./scripts/archiwum/2026-Q2/` zostały zarchiwizowane poza aktualnym repozytorium.
- Dla wersji 4.0_dev paczkę wtyczki przygotuj z katalogu `erp-omd/`, z pominięciem plików testowych i dokumentacji repozytorium.

## Testy Sprintu 3
- Historyczny skrypt sanity Sprintu 3 został zarchiwizowany poza repozytorium.
- Test domenowy time trackingu: `php tests/time-entry-service-test.php`
- Plan odbioru ręcznego i bramka do Sprintu 4: `docs/SPRINT_3_ACCEPTANCE_PLAN.md`

## Testy Sprintu 4
- Historyczny skrypt sanity Sprintu 4 został zarchiwizowany poza repozytorium.
- Test domenowy finansów projektu: `php tests/project-financial-service-test.php`
- Checklista odbiorowa Sprintu 4: `docs/archiwum/2026-Q2/SPRINT_4_CHECKLIST.md`

## Testy Sprintu 5
- Historyczny skrypt sanity Sprintu 5 został zarchiwizowany poza repozytorium.
- Test domenowy kosztorysów: `php tests/estimate-service-test.php`
- Checklista odbiorowa Sprintu 5: `docs/archiwum/2026-Q2/SPRINT_5_CHECKLIST.md`

## Testy Sprintu 6
- Historyczny skrypt sanity Sprintu 6 został zarchiwizowany poza repozytorium.
- Test domenowy raportów: `php tests/reporting-service-test.php`
- Checklista odbiorowa Sprintu 6: `docs/archiwum/2026-Q2/SPRINT_6_CHECKLIST.md`

## Testy Sprintu 7
- Historyczny skrypt sanity Sprintu 7 został zarchiwizowany poza repozytorium.
- Test domenowy alertów: `php tests/alert-service-test.php`
- Checklista odbiorowa Sprintu 7: `docs/archiwum/2026-Q2/SPRINT_7_CHECKLIST.md`

## Testy Sprintu 8 RC
- Historyczny skrypt sanity Sprintu 8 został zarchiwizowany poza repozytorium.
- Test REST API / hardening: `php tests/rest-api-test.php`
- Checklista odbiorowa Sprintu 8: `docs/archiwum/2026-Q2/SPRINT_8_CHECKLIST.md`

## Testy Sprintu 9
- Historyczny skrypt sanity Sprintu 9 został zarchiwizowany poza repozytorium.
- Test walidacji klient/projekt: `php tests/client-project-service-test.php`
- Test REST API / hardening: `php tests/rest-api-test.php`
- Checklista odbiorowa Sprintu 9: `docs/archiwum/2026-Q2/SPRINT_9_CHECKLIST.md`

## Testy Sprintu 10
- Sanity check aktualnego kontraktu: `php tests/project-request-service-test.php`
- Pełny przebieg testów plikowych: `for f in tests/*-test.php; do php "$f"; done`
- Checklista odbiorowa Sprintu 10: `docs/archiwum/2026-Q2/SPRINT_10_CHECKLIST.md`
