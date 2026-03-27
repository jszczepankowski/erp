# [OPTYMALIZACJA] — backlog techniczny (Etap 1 i Etap 2)

Dokument roboczy ustaleń do realizacji i wdrożenia.

## Cel
- Przyspieszenie działania pluginu przy dużych wolumenach danych.
- Uproszczenie utrzymania kodu (mniej duplikacji, mniejsza złożoność UI/JS).
- Ograniczenie ryzyka regresji przy kolejnych wdrożeniach.

---

## ETAP 1 — Wydajność danych i skalowanie

### EPIC 1.1 — SQL pagination + count API dla list

#### T1.1.1 TimeEntryRepository: paginacja SQL
**Zakres**
- dodać metody:
  - `find_paged(array $filters, int $limit, int $offset): array`
  - `count_filtered(array $filters): int`
- pozostawić `all()` dla kompatybilności (oznaczyć jako legacy/deprecated).

**DoD**
- LIMIT/OFFSET i count działają dla tych samych filtrów,
- brak regresji w miejscach korzystających z `all()`,
- min. 1 test repozytorium dla paginacji i count.

**Priorytet**: P0

---

#### T1.1.2 Paginacja SQL dla pozostałych list admin/front
**Zakres**
- analogiczny wzorzec dla: klienci, projekty, kosztorysy, wnioski.
- backendowe liczenie wszystkich rekordów (`count_filtered`).

**DoD**
- listy pobierają tylko bieżącą stronę danych,
- całkowita liczba rekordów jest spójna z filtrami,
- paginacja UI działa z backendowym źródłem danych.

**Priorytet**: P0

---

#### T1.1.3 Podpięcie paginacji backendowej do ekranów
**Zakres**
- Admin: klienci, kosztorysy, projekty, wnioski, czas pracy, raporty.
- Front: projekty, kosztorysy, czas pracy.

**DoD**
- nawigacja stron nie wymaga ładowania pełnej tabeli do DOM,
- zachowane opcje 25/50/100/200,
- brak regresji filtrów/sortowania.

**Priorytet**: P0

---

### EPIC 1.2 — Optymalizacja raportowania

#### T1.2.1 Cache wyników raportów po filtrach
**Zakres**
- cache (transient/object cache) po kluczu: `report_type + month + filters + scope`.
- invalidacja cache po zmianie danych źródłowych:
  - wpisy czasu,
  - koszty projektowe,
  - historia wynagrodzeń,
  - koszty stałe.

**DoD**
- kolejne odczyty raportu są szybsze,
- po zmianie danych cache nie zwraca starych wartości.

**Priorytet**: P0

---

#### T1.2.2 Ograniczenie złożoności `omd_rozliczenia`
**Zakres**
- prefetch/index danych do wyliczeń miesięcznych (zamiast wielokrotnych pętli),
- przygotowanie pod snapshoty miesięczne (jeśli potrzebne).

**DoD**
- ta sama logika biznesowa,
- zauważalny spadek czasu budowania raportu 12M.

**Priorytet**: P1

---

### EPIC 1.3 — Monitoring wydajności

#### T1.3.1 Lekki profiler (tryb dev)
**Zakres**
- liczba zapytań + czas renderu kluczowych ekranów.
- aktywacja tylko w debug/dev.

**DoD**
- możliwość porównania przed/po dla optymalizacji.

**Priorytet**: P2

---

## ETAP 2 — Utrzymanie, porządek kodu i mniej regresji

### EPIC 2.1 — Front JS refactor (inline → assety)

#### T2.1.1 Manager dashboard: przeniesienie JS do `assets/js/front-manager.js`
**Zakres**
- wyciągnięcie skryptów inline do pliku asset,
- zachowanie 1:1 funkcjonalności (tabs, filtry, tabele, paginacja).

**DoD**
- minimalny inline JS w template,
- brak regresji zachowania manager front.

**Priorytet**: P1

---

#### T2.1.2 Worker dashboard: przeniesienie JS do `assets/js/front-worker.js`
**Zakres**
- analogiczna refaktoryzacja dla worker front.

**DoD**
- 1:1 funkcjonalność,
- prostszy i krótszy template worker.

**Priorytet**: P1

---

### EPIC 2.2 — Modularizacja admin JS

#### T2.2.1 Podział `admin.js` na moduły
**Proponowane moduły**
- `admin-table-tools.js`
- `admin-fixed-costs.js`
- `admin-collapsible.js`
- `admin-form-sync.js`

**DoD**
- entrypoint inicjuje moduły,
- mniejsza odpowiedzialność pojedynczych plików,
- testy manualne bez regresji.

**Priorytet**: P1

---

#### T2.2.2 Wspólny silnik tabel (front/admin)
**Zakres**
- wspólny utility dla: sort/search/pagination,
- adaptery dla selektorów CSS front i admin.

**DoD**
- jedna logika tabel utrzymywana w jednym miejscu,
- uproszczone poprawki błędów.

**Priorytet**: P2

---

### EPIC 2.3 — Porządek menu i konfiguracji UI

#### T2.3.1 Dopracowanie separatorów submenu
**Zakres**
- utrzymać obecny podział sekcji,
- dopracować semantykę/a11y separatorów i stylowanie.

**DoD**
- separatory nie są traktowane jak normalne akcje,
- czytelność menu pozostaje wysoka.

**Priorytet**: P3

---

## Kolejność realizacji
1. **Etap 1 (P0):** T1.1.1 → T1.1.2 → T1.1.3 → T1.2.1
2. **Etap 1 (P1/P2):** T1.2.2 → T1.3.1
3. **Etap 2 (P1):** T2.1.1 → T2.1.2 → T2.2.1
4. **Etap 2 (P2/P3):** T2.2.2 → T2.3.1

---

## Status
- **Dokument zatwierdzony do realizacji:** TAK
- **Tryb pracy:** iteracyjny (wdrożenia etapowe)
- **Następny krok:** rozpoczęcie T1.1.1
