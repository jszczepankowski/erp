# ERP_OMD V2 — Sprint 5 Checklist

## Zakres Sprintu 5
- Wszystko ze Sprintów 1–4 bez regresji.
- Migracje DB dla `estimates` i `estimate_items`.
- CRUD kosztorysów i pozycji kosztorysu.
- Kalkulacja netto / VAT 23% / brutto.
- Akceptacja kosztorysu tworząca projekt z powiązaniem `estimate_id`.
- Blokada edycji zaakceptowanego kosztorysu.
- REST API dla:
  - `estimates`
  - `estimates/{id}`
  - `estimates/{id}/items`
  - `estimate-items/{id}`
  - `estimates/{id}/accept`

## Paczka sprintu
- ZIP wynikowy: `dist/erp-omd-sprint-5.zip`
- Generator ZIP: `./scripts/build-sprint-5-zip.sh`

## Automatyczne sanity checki
1. Uruchom `./scripts/test-sprint-5.sh`.
2. Potwierdź:
   - lint wszystkich plików PHP,
   - test time trackingu,
   - test agregatora finansowego,
   - test modułu kosztorysów,
   - budowę ZIP Sprintu 5.

## Kroki odbioru
1. Zbuduj `dist/erp-omd-sprint-5.zip`.
2. Zainstaluj lub zaktualizuj plugin w WordPress.
3. Otwórz menu `ERP OMD -> Kosztorysy`.
4. Dodaj kosztorys dla istniejącego klienta.
5. Dodaj minimum dwie pozycje kosztorysu.
6. Sprawdź wyliczenie netto / VAT / brutto.
7. Przełącz kosztorys do `do_akceptacji`.
8. Użyj akcji akceptacji kosztorysu.
9. Potwierdź utworzenie projektu powiązanego z `estimate_id`.
10. Potwierdź, że zaakceptowany kosztorys jest tylko do odczytu.
11. Sprawdź endpointy `GET/POST/PATCH/DELETE` kosztorysów i pozycji oraz `POST /accept`.

## Oczekiwany rezultat
- Można utworzyć kosztorys z pozycjami.
- System poprawnie liczy netto, VAT i brutto.
- Akceptacja tworzy projekt typu `fixed_price`.
- Zaakceptowany kosztorys jest zablokowany do edycji.
