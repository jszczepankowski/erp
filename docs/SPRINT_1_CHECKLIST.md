# ERP_OMD V2 — Sprint 1 Checklist

## Zakres Sprintu 1
- Bootstrap pluginu WordPress i autoloading.
- Migracje DB z wersjonowaniem dla tabel `roles`, `employees`, `employee_roles`, `salary_history`.
- Role systemowe i capability map dla admin / manager / pracownik.
- CRUD ról projektowych.
- CRUD pracowników powiązanych z kontami WordPress.
- Historia wynagrodzeń z walidacją okresów oraz wyliczaniem `hourly_cost`.
- Ustawienie uninstall: usuń dane / zachowaj dane.
- REST API dla `roles CRUD`, `employees CRUD + salary`.

## Paczka sprintu
- ZIP wynikowy: `dist/erp-omd-sprint-1.zip`
- Generator ZIP: `./scripts/build-sprint-1-zip.sh`
- Katalog pluginu: `erp-omd/`

## Kroki odbioru
1. Uruchom `./scripts/build-sprint-1-zip.sh`.
2. Zainstaluj wygenerowany ZIP `dist/erp-omd-sprint-1.zip` w WordPress jako plugin.
3. Aktywuj plugin `ERP OMD`.
4. Wejdź w menu `ERP OMD -> Role`.
5. Dodaj rolę projektową, np. `Project Manager`, slug `project-manager`, status `active`.
6. Potwierdź, że rola pojawia się na liście i można ją edytować.
7. Wejdź w `ERP OMD -> Pracownicy`.
8. Dodaj pracownika powiązanego z istniejącym kontem WordPress.
9. Ustaw typ konta (`admin`, `manager` lub `pracownik`), przypisz role projektowe i wybierz domyślną rolę.
10. Potwierdź, że rekord zapisuje się bez błędów i jest widoczny na liście.
11. Na ekranie edycji pracownika dodaj wpis salary history:
    - `monthly_salary`
    - `monthly_hours`
    - `valid_from`
    - opcjonalnie `valid_to`
12. Potwierdź, że system zapisuje rekord i pokazuje wyliczone `hourly_cost = salary / monthly_hours`.
13. Spróbuj dodać nakładający się okres salary history dla tego samego pracownika.
14. Potwierdź, że system blokuje zapis walidacją.
15. Wejdź w `ERP OMD -> Ustawienia`.
16. Zmień checkbox uninstall i zapisz ustawienia.
17. Potwierdź, że ustawienie zapisuje się bez błędów.

## REST API — minimalne sprawdzenie
1. Wywołaj `GET /wp-json/erp-omd/v1/roles` jako użytkownik z odpowiednimi uprawnieniami.
2. Wywołaj `POST /wp-json/erp-omd/v1/roles` z payloadem roli.
3. Wywołaj `GET /wp-json/erp-omd/v1/employees`.
4. Wywołaj `POST /wp-json/erp-omd/v1/employees`.
5. Wywołaj `GET /wp-json/erp-omd/v1/employees/{id}/salary`.
6. Wywołaj `POST /wp-json/erp-omd/v1/employees/{id}/salary`.
7. Wywołaj `GET /wp-json/erp-omd/v1/monthly-hours/{YYYY-MM}` i potwierdź, że system zwraca podpowiedź godzin.

## Oczekiwany rezultat
- Plugin aktywuje się bez błędu fatal.
- Tabele tworzą się tylko raz i nie dublują migracji.
- Admin UI umożliwia ręczny test wszystkich funkcji sprintu.
- Salary history waliduje okresy i wylicza hourly cost.
- REST API zwraca dane oraz respektuje capability checks.
- Ustawienie uninstall jest zapisane i odczytywane przez plugin.
