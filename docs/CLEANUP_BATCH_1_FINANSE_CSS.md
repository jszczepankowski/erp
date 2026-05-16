# Cleanup Batch 1 — Finanse + przeniesienie inline style do CSS

Data: 2026-05-13

## Zakres wykonany
1. Usunięcie zakładki/ekranu Finanse z panelu admina.
2. Przeniesienie inline style z widoków kosztorysów do pliku CSS.
3. Pierwszy krok procesowy: uszczelnienie standardu Evidence dla PR + porządkowanie dokumentacji sprintowej.

## Artefakty zmian (już wdrożone)
- `erp-omd/includes/class-admin-runtime.php`
  - usunięta rejestracja submenu `Finanse`.
- `erp-omd/templates/admin/finances.php`
  - plik usunięty.
- `erp-omd/templates/admin/estimates.php`
  - inline style zastąpione klasami CSS.
- `erp-omd/assets/css/admin.css`
  - dodane klasy:
    - `.erp-omd-estimate-summary-card`
    - `.erp-omd-estimate-summary-card-spaced`
    - `.erp-omd-estimate-basics-grid`
    - `.erp-omd-estimate-decision-url`

## Pierwszy procesowy + cleanup
- `.github/pull_request_template.md`
  - dodana obowiązkowa sekcja "Testing Evidence".
- `docs/RELEASE_NOTES_TEST_EVIDENCE_POLICY.md`
  - polityka dowodów testowych.
- `docs/archiwum/2026-Q2/`
  - przeniesione historyczne checklisty sprintowe.

## Status
Zakończone i gotowe jako baza do kolejnych batchy cleanupu.
