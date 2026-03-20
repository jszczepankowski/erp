# ERP_OMD V2 — Sprint 3 Checklist

## Zakres Sprintu 3
- Wszystko ze Sprintów 1–2 bez regresji.
- Migracje DB dla `project_rates` i `time_entries`.
- Stawki projektowe per rola jako override względem stawek klienta.
- Time tracking tylko dla projektów w statusie `w_realizacji`.
- Snapshoty `rate_snapshot` i `cost_snapshot` na wpisie czasu.
- Approval flow: `submitted`, `approved`, `rejected`.
- Usuwanie wpisów czasu tylko przez administratora.
- UI admina dla stawek projektowych i czasu pracy.
- REST API dla `projects/{id}/rates`, `project-rates`, `time CRUD + approval`.

## Paczka sprintu
- ZIP wynikowy: `dist/erp-omd-sprint-3.zip`
- Generator ZIP: `./scripts/build-sprint-3-zip.sh`
- Katalog pluginu: `erp-omd/`

## Automatyczne sanity checki przed zamknięciem sprintu
1. Uruchom `./scripts/test-sprint-3.sh`.
2. Potwierdź, że przechodzą:
   - lint wszystkich plików PHP pluginu,
   - testy domenowe dla `ERP_OMD_Time_Entry_Service`,
   - budowanie ZIP paczki Sprintu 3.
3. Otwórz `docs/SPRINT_3_ACCEPTANCE_PLAN.md` i wykonaj pełny plan odbioru ręcznego.
4. Dopiero po przejściu powyższych checków przejdź do formalnego zamknięcia sprintu.

## Kroki odbioru
1. Uruchom `./scripts/build-sprint-3-zip.sh`.
2. Zainstaluj wygenerowany ZIP `dist/erp-omd-sprint-3.zip` w WordPress jako plugin.
3. Aktywuj plugin `ERP OMD`.
4. Potwierdź, że funkcje Sprintów 1–2 nadal działają.
5. Wejdź w `ERP OMD -> Projekty` i otwórz istniejący projekt.
6. Ustaw status projektu na `w_realizacji` i zapisz.
7. Dodaj stawkę projektową dla wybranej roli.
8. Potwierdź, że stawka pojawia się na liście stawek projektowych.
9. Wejdź w `ERP OMD -> Czas pracy`.
10. Dodaj wpis czasu dla projektu `w_realizacji`, pracownika, roli i liczby godzin.
11. Potwierdź, że wpis zapisuje się ze statusem `submitted` i pokazuje snapshot `rate_snapshot / cost_snapshot`.
12. Spróbuj dodać wpis czasu dla projektu w statusie innym niż `w_realizacji`.
13. Potwierdź, że system blokuje zapis.
14. Spróbuj dodać duplikat według reguły `employee_id + project_id + role_id + hours`.
15. Potwierdź, że system blokuje zapis.
16. Zalogowany manager zatwierdza lub odrzuca wpis czasu.
17. Potwierdź, że status zmienia się na `approved` albo `rejected`.
18. Spróbuj usunąć wpis czasu jako nie-admin.
19. Potwierdź, że usunięcie jest zablokowane.
20. Usuń wpis czasu jako administrator.
21. Potwierdź, że wpis znika z listy.

## REST API — minimalne sprawdzenie
1. Wywołaj `GET /wp-json/erp-omd/v1/projects/{id}/rates`.
2. Wywołaj `POST /wp-json/erp-omd/v1/projects/{id}/rates`.
3. Wywołaj `GET /wp-json/erp-omd/v1/time`.
4. Wywołaj `POST /wp-json/erp-omd/v1/time`.
5. Wywołaj `POST/PATCH /wp-json/erp-omd/v1/time/{id}/status`.
6. Wywołaj `DELETE /wp-json/erp-omd/v1/time/{id}` jako administrator.

## Oczekiwany rezultat
- Sprinty 1–2 działają bez regresji.
- Stawki projektowe nadpisują stawki klienta na potrzeby snapshotu sprzedażowego.
- Time tracking działa tylko dla projektów `w_realizacji`.
- Snapshot kosztu opiera się na salary history obowiązującym w dniu wpisu.
- Approval flow działa poprawnie dla managera.
- Usuwanie wpisów czasu jest ograniczone do administratora.
