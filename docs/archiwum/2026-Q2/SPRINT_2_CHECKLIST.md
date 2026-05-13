# ERP_OMD V2 — Sprint 2 Checklist

## Zakres Sprintu 2
- Wszystko ze Sprintu 1 bez regresji.
- Migracje DB dla `clients`, `client_rates`, `projects`, `project_notes`.
- CRUD klientów.
- CRUD stawek klienta per rola.
- CRUD projektów z podstawowymi polami, statusami i typami rozliczenia.
- Historia uwag klienta jako osobna tabela z autorem i timestampem.
- UI admina dla klientów i projektów.
- REST API dla `clients CRUD`, `client rates`, `projects CRUD`, `project notes`.

## Paczka sprintu
- ZIP wynikowy: `dist/erp-omd-sprint-2.zip`
- Generator ZIP: `./scripts/build-sprint-2-zip.sh`
- Katalog pluginu: `erp-omd/`

## Kroki odbioru
1. Uruchom `./scripts/build-sprint-2-zip.sh`.
2. Zainstaluj wygenerowany ZIP `dist/erp-omd-sprint-2.zip` w WordPress jako plugin.
3. Aktywuj plugin `ERP OMD`.
4. Potwierdź, że funkcje Sprintu 1 nadal działają.
5. Wejdź w `ERP OMD -> Klienci`.
6. Dodaj klienta z danymi: nazwa, firma, NIP, email, telefon, osoba kontaktowa, miasto i account manager.
7. Potwierdź, że klient pojawia się na liście i można go edytować.
8. Na ekranie klienta dodaj stawkę klienta dla wybranej roli projektowej.
9. Potwierdź, że stawka zapisuje się i pojawia na liście stawek klienta.
10. Wejdź w `ERP OMD -> Projekty`.
11. Dodaj projekt dla istniejącego klienta z typem rozliczenia, statusem, managerem, budżetem lub retainerem oraz briefem.
12. Potwierdź, że projekt zapisuje się i jest widoczny na liście projektów.
13. Na ekranie projektu dodaj uwagę klienta.
14. Potwierdź, że uwaga zapisuje się z datą i autorem na liście historii.
15. Zmień status klienta lub projektu na `inactive` albo użyj akcji deactivate.
16. Potwierdź, że rekord nie jest usuwany fizycznie, ale przechodzi w status `inactive`.

## REST API — minimalne sprawdzenie
1. Wywołaj `GET /wp-json/erp-omd/v1/clients`.
2. Wywołaj `POST /wp-json/erp-omd/v1/clients`.
3. Wywołaj `GET /wp-json/erp-omd/v1/clients/{id}/rates`.
4. Wywołaj `POST /wp-json/erp-omd/v1/clients/{id}/rates`.
5. Wywołaj `GET /wp-json/erp-omd/v1/projects`.
6. Wywołaj `POST /wp-json/erp-omd/v1/projects`.
7. Wywołaj `GET /wp-json/erp-omd/v1/projects/{id}/notes`.
8. Wywołaj `POST /wp-json/erp-omd/v1/projects/{id}/notes`.

## Oczekiwany rezultat
- Sprint 1 działa bez regresji.
- Migracje dodają nowe tabele tylko raz.
- Klienci i projekty zapisują się poprawnie.
- Stawki klienta zapisują się per rola.
- Historia uwag klienta działa jako osobna tabela z autorem i timestampem.
- REST API zwraca dane dla modułów Sprintu 2.
