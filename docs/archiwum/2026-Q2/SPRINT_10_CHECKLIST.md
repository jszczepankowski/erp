# ERP_OMD V2 — Sprint 10 Checklist

## Zakres Sprintu 10
- Dodanie procesu obsługi **wniosków projektowych** (manager → admin) wraz z konwersją zaakceptowanego wniosku do projektu.
- Usprawnienie pracy administratora przez **edycję inline** na listach: pracownicy, projekty, wpisy czasu.
- Rozszerzenie workflow kosztorysów managera o:
  - wielopozycyjne pozycje kosztorysu,
  - eksport CSV,
  - lepszą widoczność klientów/projektów dla managera/admina.
- Uporządkowanie UI frontu (sekcje zwijane/rozwijane z zapamiętywaniem stanu).
- Przygotowanie paczki wdrożeniowej Sprintu 10.

## Paczka Sprintu 10
- ZIP wynikowy: `dist/erp-omd-sprint-10.zip`
- Generator ZIP: `./scripts/build-sprint-10.sh`

## Automatyczne sanity checki
1. Uruchom `./scripts/test-sprint-10.sh`.
2. Potwierdź:
   - lint wszystkich plików PHP,
   - testy time trackingu,
   - testy finansów projektu,
   - testy kosztorysów,
   - testy raportowania,
   - testy alertów,
   - testy reguł klient/projekt,
   - testy REST API,
   - testy workflow wniosków projektowych,
   - budowę ZIP Sprintu 10.

## Kroki odbioru
1. Manager tworzy nowy **wniosek projektowy** w panelu front i zapisuje go.
2. Administrator otwiera **Wnioski projektowe**, zmienia status wniosku i konwertuje zaakceptowany wniosek do projektu.
3. Na listach admina wykonaj edycję inline dla pracownika, projektu i wpisu czasu; potwierdź zapis i odświeżenie danych.
4. W panelu managera utwórz kosztorys z wieloma pozycjami oraz wyeksportuj go do CSV.
5. Zweryfikuj działanie sekcji zwijanych/rozwijanych po odświeżeniu strony (stan powinien być zapamiętany).
6. Zbuduj `dist/erp-omd-sprint-10.zip` i sprawdź instalację/aktualizację pluginu na środowisku testowym WordPress.
