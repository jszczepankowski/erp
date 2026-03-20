# ERP_OMD V2 — Sprint 7 Checklist

## Zakres Sprintu 7
- Wszystko ze Sprintów 1–6 bez regresji.
- Alerty dla przekroczenia budżetu, niskiej marży, brakujących stawek i 3 dni bez wpisu czasu.
- Załączniki dla projektów i kosztorysów z wykorzystaniem biblioteki mediów WordPress.
- Soft delete / aktywacja dla klientów, projektów i pracowników.
- Dodatkowe polish admina: data ostatniego logowania pracownika i odświeżone dashboard/listy.

## Paczka sprintu
- ZIP wynikowy: `dist/erp-omd-sprint-7.zip`
- Generator ZIP: `./scripts/build-sprint-7-zip.sh`

## Automatyczne sanity checki
1. Uruchom `./scripts/test-sprint-7.sh`.
2. Potwierdź:
   - lint wszystkich plików PHP,
   - testy time trackingu,
   - test agregatora finansowego,
   - test modułu kosztorysów,
   - test modułu raportowania,
   - test modułu alertów,
   - budowę ZIP Sprintu 7.

## Kroki odbioru
1. Zbuduj `dist/erp-omd-sprint-7.zip`.
2. Zainstaluj lub zaktualizuj plugin w WordPress.
3. Otwórz menu `ERP OMD -> Alerty` i sprawdź listę alertów.
4. Zweryfikuj oznaczenia alertów na dashboardzie, projektach i pracownikach.
5. Dodaj załącznik do projektu i kosztorysu z biblioteki mediów.
6. Potwierdź aktywację/dezaktywację klienta, projektu i pracownika.
7. Sprawdź tabelę pracowników: brak emaila, obecna data logowania.
