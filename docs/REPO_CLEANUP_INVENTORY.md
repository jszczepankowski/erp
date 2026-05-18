# Kontrolowany cleanup repo — lista „usunąć / przenieść / zostawić”

Data audytu: 2026-05-13
Status: propozycja do realizacji iteracyjnej (bez masowego kasowania w jednym kroku).

## ZOSTAWIĆ (aktywnie używane)
- `erp-omd/**` — kod pluginu/runtime/szablony/CSS.
- `erp-omd/README.md` — dokumentacja modułu.
- `docs/ERP_OMD_SYSTEM_OVERVIEW.md` — aktualny przegląd systemu.
- `docs/RUNBOOK_GOOGLE_CALENDAR_SYNC_ERROR.md` — runbook operacyjny.

## PRZENIEŚĆ (archiwum / historyczne)
Do `docs/archiwum/2026-Q2/`:
- checklisty sprintowe historyczne (`docs/SPRINT_*_CHECKLIST.md`),
- historyczne raporty UAT/closure/backlog snapshoty,
- materiały promptowe wdrożeń i stare plany (jeśli nie są już referencją bieżącą).

Cel: zostawić w `docs/` tylko dokumenty operacyjne i aktualne specy.

## USUNĄĆ (po potwierdzeniu braku użycia)
1. Stare jednorazowe skrypty build/test sprintów:
   - `scripts/build-sprint-*.sh`
   - `scripts/test-sprint-*.sh`

Warunek usunięcia:
- brak referencji w CI i runbookach,
- brak użycia przez zespół operacyjny.

2. Duplikaty dokumentów (po porównaniu treści):
- pliki o tej samej tematyce z różnymi datami, gdzie nowszy plik całkowicie zastępuje starszy.

## Zasady bezpieczeństwa cleanupu
1. Najpierw **przenieś**, potem **usuń** (w osobnym kroku).
2. Każda paczka cleanupu max 20–30 plików.
3. Każdy commit cleanupu z listą plików i uzasadnieniem.
4. Po każdej paczce: szybki smoke test panelu admin/front.

## Etapy realizacji
Etap A (bezpieczny):
- porządkowanie `docs/` i przenoszenie archiwów.

Etap B (weryfikacja skryptów):
- potwierdzenie czy `scripts/*sprint*` są nieużywane.

Etap C (kasowanie):
- usunięcie zweryfikowanych, nieużywanych plików.

## Kryterium DONE
- w root i `docs/` brak plików tymczasowych/niejasnych,
- każdy pozostawiony plik ma właściciela i cel,
- cleanup nie wprowadził regresji runtime.
