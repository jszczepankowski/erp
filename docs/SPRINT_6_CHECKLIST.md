# ERP_OMD V2 — Sprint 6 Checklist

## Zakres Sprintu 6
- Wszystko ze Sprintów 1–5 bez regresji.
- Centrum raportów dla projektów, klientów, pozycji do faktury i raportu miesięcznego.
- Eksport CSV dla raportów z poziomu admina i REST API.
- Kalendarz miesięczny z agregacją godzin per dzień.
- Filtry po kliencie, projekcie, pracowniku, miesiącu i statusie.
- REST API dla:
  - `reports`
  - `reports/export`
  - `calendar`

## Paczka sprintu
- ZIP wynikowy: `dist/erp-omd-sprint-6.zip`
- Generator ZIP: `./scripts/build-sprint-6-zip.sh`

## Automatyczne sanity checki
1. Uruchom `./scripts/test-sprint-6.sh`.
2. Potwierdź:
   - lint wszystkich plików PHP,
   - testy time trackingu,
   - test agregatora finansowego,
   - test modułu kosztorysów,
   - test modułu raportowania,
   - budowę ZIP Sprintu 6.

## Kroki odbioru
1. Zbuduj `dist/erp-omd-sprint-6.zip`.
2. Zainstaluj lub zaktualizuj plugin w WordPress.
3. Otwórz menu `ERP OMD -> Raporty`.
4. Sprawdź raport projektów, klientów, do faktury i miesięczny.
5. Zmień filtry po kliencie, projekcie, pracowniku, miesiącu i statusie.
6. Wyeksportuj wybrany raport do CSV.
7. Przełącz zakładkę `Kalendarz` i sprawdź sumy godzin per dzień.
8. Zweryfikuj endpointy `GET /reports`, `GET /reports/export` i `GET /calendar`.
