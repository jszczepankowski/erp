# ERP_OMD V2 — Plan odbioru i zamknięcia Sprintu 3

## Cel dokumentu

Ten dokument służy do **formalnego domknięcia Sprintu 3** przed rozpoczęciem Sprintu 4.

Zasada przejścia dalej:
- **najpierw testy automatyczne,**
- potem **odbiór ręczny w WordPress,**
- potem **akceptacja sprintu,**
- dopiero na końcu **start Sprintu 4**.

---

## 1. Warunki wejścia do odbioru Sprintu 3

Przed rozpoczęciem odbioru upewnij się, że:

1. branch zawiera aktualną wersję Sprintu 3,
2. paczka ZIP buduje się poprawnie,
3. automatyczne sanity checki przechodzą bez błędów,
4. dostępne jest środowisko WordPress do testów manualnych,
5. masz przygotowane co najmniej trzy konta WP:
   - `administrator`,
   - `manager`,
   - `worker`.

---

## 2. Bramka techniczna — testy automatyczne

### Komenda główna

```bash
./scripts/test-sprint-3.sh
```

### Oczekiwany wynik

Komenda ma zakończyć się sukcesem i potwierdzić:
- lint wszystkich plików PHP pluginu,
- test domenowy `tests/time-entry-service-test.php`,
- poprawne zbudowanie paczki `dist/erp-omd-sprint-3.zip`.

### Dowód wykonania

Do akceptacji sprintu zapisz:
- datę uruchomienia,
- commit hash,
- wynik komendy,
- ścieżkę do wygenerowanego ZIP.

---

## 3. Środowisko testowe WordPress

## Minimalne dane testowe do przygotowania

Przed właściwym odbiorem przygotuj:

### Role projektowe
- `Developer`
- `Designer`

### Pracownicy
- pracownik typu `manager`, powiązany z kontem WP managera,
- pracownik typu `worker`, powiązany z kontem WP workera,
- opcjonalnie drugi worker do testów widoczności i filtrów.

### Salary history
- co najmniej jeden aktywny wpis salary dla workera:
  - `monthly_salary`,
  - `monthly_hours`,
  - `valid_from`.

### Klient
- aktywny klient testowy.

### Stawki klienta
- stawka klienta dla roli `Developer`,
- opcjonalnie dodatkowa stawka dla `Designer`.

### Projekt
- projekt przypisany do klienta,
- manager projektu ustawiony na pracownika typu `manager`,
- status projektu zmieniany w testach między:
  - `do_rozpoczecia`,
  - `w_realizacji`.

---

## 4. Plan odbioru ręcznego — UI admina

## Etap A — regresja Sprintów 1–2

1. Zainstaluj ZIP `dist/erp-omd-sprint-3.zip`.
2. Aktywuj plugin.
3. Wejdź do menu `ERP OMD`.
4. Potwierdź, że działają ekrany:
   - pracownicy,
   - role,
   - klienci,
   - projekty,
   - ustawienia.
5. Dodaj / edytuj:
   - rolę projektową,
   - pracownika,
   - wpis salary history,
   - klienta,
   - projekt.
6. Potwierdź brak regresji względem Sprintów 1–2.

## Etap B — stawki projektowe

1. Otwórz istniejący projekt.
2. Dodaj projektową stawkę override dla roli `Developer`.
3. Potwierdź, że stawka pojawia się na liście stawek projektowych.
4. Upewnij się, że projektowa stawka różni się od stawki klienta, aby dało się zweryfikować priorytet.

## Etap C — time tracking

1. Ustaw projekt na `w_realizacji`.
2. Zaloguj się jako worker.
3. Dodaj wpis czasu dla projektu:
   - poprawny pracownik,
   - poprawna rola,
   - dodatnia liczba godzin,
   - poprawna data.
4. Potwierdź:
   - zapis wpisu,
   - status `submitted`,
   - zapis `rate_snapshot`,
   - zapis `cost_snapshot`.
5. Zmień stawkę klienta albo projektu.
6. Potwierdź, że istniejący wpis czasu zachowuje historyczny snapshot.

## Etap D — walidacje

1. Spróbuj dodać wpis czasu dla projektu w statusie innym niż `w_realizacji`.
2. Potwierdź, że zapis jest zablokowany.
3. Spróbuj dodać duplikat według reguły:
   - `employee_id + project_id + role_id + hours`.
4. Potwierdź blokadę duplikatu.

## Etap E — approval flow i uprawnienia

1. Zaloguj się jako manager przypisany do projektu.
2. Otwórz listę wpisów czasu.
3. Potwierdź, że manager widzi:
   - własne wpisy,
   - wpisy dla projektów, którymi zarządza,
   - nie widzi obcych wpisów spoza swoich projektów.
4. Zatwierdź wpis czasu.
5. Potwierdź zmianę statusu na `approved`.
6. Odrzuć inny wpis czasu.
7. Potwierdź zmianę statusu na `rejected`.
8. Zaloguj się jako inny manager, nieprzypisany do projektu.
9. Potwierdź brak możliwości approval dla obcego projektu.
10. Zaloguj się jako worker.
11. Potwierdź brak możliwości:
    - approval,
    - usuwania wpisów czasu,
    - podglądu obcych wpisów.
12. Zaloguj się jako administrator.
13. Potwierdź możliwość:
    - edycji wpisu czasu,
    - usunięcia wpisu czasu,
    - podglądu wszystkich wpisów.

---

## 5. Plan odbioru REST API

Wykonaj poniższe wywołania na środowisku testowym:

### Stawki projektowe
- `GET /wp-json/erp-omd/v1/projects/{id}/rates`
- `POST /wp-json/erp-omd/v1/projects/{id}/rates`

### Wpisy czasu
- `GET /wp-json/erp-omd/v1/time`
- `GET /wp-json/erp-omd/v1/time/{id}`
- `POST /wp-json/erp-omd/v1/time`
- `PATCH /wp-json/erp-omd/v1/time/{id}`
- `PATCH /wp-json/erp-omd/v1/time/{id}/status`
- `DELETE /wp-json/erp-omd/v1/time/{id}`

### Co potwierdzić

1. Worker widzi tylko swoje wpisy.
2. Manager projektu nie może approve wpisu dla projektu, którego nie prowadzi.
3. Administrator ma pełny dostęp.
4. `DELETE` działa wyłącznie dla administratora.
5. Nieprawidłowy status approval kończy się błędem walidacji.

---

## 6. Kryteria akceptacji Sprintu 3

Sprint 3 można uznać za **zamknięty i zaakceptowany**, jeżeli spełnione są wszystkie warunki:

- `./scripts/test-sprint-3.sh` przechodzi,
- paczka ZIP instaluje się w WordPress,
- regresja Sprintów 1–2 nie występuje,
- stawki projektowe działają jako override,
- snapshoty są historyczne i stabilne,
- time tracking działa tylko dla `w_realizacji`,
- approval działa tylko dla przypisanego managera projektu lub administratora,
- worker nie ma dostępu do obcych wpisów,
- usuwanie wpisów czasu działa tylko dla administratora,
- REST API zachowuje te same ograniczenia co UI,
- wynik odbioru został zapisany jako:
  - `PASS`,
  - `PASS WITH MINOR ISSUES`,
  - albo `BLOCKED`.

---

## 7. Bramka wejścia do Sprintu 4

Do Sprintu 4 przechodzimy dopiero wtedy, gdy:

1. Sprint 3 ma status **zaakceptowany**.
2. Wszystkie testy automatyczne są zielone.
3. Manualny odbiór WordPress został wykonany i zapisany.
4. Nie ma otwartych blockerów w obszarach:
   - time tracking,
   - approval,
   - snapshoty,
   - uprawnienia,
   - REST API.
5. Jest zbudowana i zweryfikowana paczka `dist/erp-omd-sprint-3.zip`.

Jeśli którykolwiek z powyższych punktów nie jest spełniony, pozostajemy w Sprincie 3.

---

## 8. Co przygotować bezpośrednio przed Sprintem 4

Po zaakceptowaniu Sprintu 3 można rozpocząć planowanie Sprintu 4 w następującej kolejności:

1. potwierdzić finalny zakres tabel:
   - `project_costs`,
   - `project_financials`,
2. ustalić kontrakt agregatora real-time,
3. zdefiniować reguły przychodów dla:
   - `time_material`,
   - `fixed_price`,
   - `retainer`,
4. opisać źródła kosztów:
   - `time_entries.cost_snapshot`,
   - koszty bezpośrednie projektu,
5. zdecydować, jakie minimum UI i REST API ma wejść do pierwszego przyrostu Sprintu 4.
