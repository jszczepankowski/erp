# ERP_OMD V2 — Sprint 9 Checklist

## Zakres Sprintu 9
- Usprawnienie pracy po edycji przez czystsze przekierowania i odświeżenie kontekstu ekranu.
- Duplikowanie projektów z zachowaniem klienta, typu rozliczenia oraz budżetu / opłaty ryczałtowej.
- Porządkowanie tabeli projektów pod potrzeby operacyjne.
- Dodanie wyboru klienta na ekranie czasu pracy wraz z filtrowaniem projektów klienta.
- Podbicie wersji pluginu do `0.9.0`.

## Paczka Sprintu 9
- ZIP wynikowy: `dist/erp-omd-sprint-9.zip`
- Generator ZIP: `./scripts/build-sprint-9.sh`

## Automatyczne sanity checki
1. Uruchom `./scripts/test-sprint-9.sh`.
2. Potwierdź:
   - lint plików PHP,
   - testy time trackingu,
   - testy finansów projektu,
   - testy kosztorysów,
   - testy raportowania,
   - testy alertów,
   - testy reguł klient/projekt,
   - testy REST API,
   - budowę ZIP Sprintu 9.

## Kroki odbioru
1. Otwórz ekran **Projekty** i potwierdź kolejność kolumn: `ID / Klient / Nazwa / Typ / Manager / Koszt / Przychód / Zysk / Marża % / Status / Akcje`.
2. Zduplikuj projekt z listy i sprawdź, że nowy rekord zachowuje klienta, typ rozliczenia oraz właściwy budżet lub opłatę miesięczną.
3. Zedytuj klienta / kosztorys / wpis czasu i potwierdź, że po zapisie ekran wraca do odświeżonego widoku bez pozostawania w trybie edycji.
4. Na ekranie **Czas pracy** wybierz klienta i sprawdź, że lista projektów zawęża się do projektów tego klienta.
5. Zbuduj `dist/erp-omd-sprint-9.zip` i zweryfikuj instalację / aktualizację pluginu w WordPress.
