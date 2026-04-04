# [OPTYMALIZACJA] — backlog techniczny + roadmapa produktowa

Dokument roboczy ustaleń do realizacji i wdrożenia.

## Cel
- Przyspieszenie działania pluginu przy dużych wolumenach danych.
- Uproszczenie utrzymania kodu (mniej duplikacji, mniejsza złożoność UI/JS).
- Ograniczenie ryzyka regresji przy kolejnych wdrożeniach.
- Przełożenie wydajności i automatyzacji na mierzalny wynik biznesowy.

---

## 1) OPTYMALIZACJA (najwyższy ROI operacyjny)

### A. SQL pagination + count API (P0)

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

**Efekt biznesowy**
- szybsze ekrany managerów,
- krótszy czas pracy na operacjach masowych.

---

#### T1.1.2 Paginacja SQL dla pozostałych list admin/front
**Zakres**
- analogiczny wzorzec dla: klienci, projekty, kosztorysy, wnioski.
- backendowe liczenie wszystkich rekordów (`count_filtered`).

**DoD**
- listy pobierają tylko bieżącą stronę danych,
- całkowita liczba rekordów jest spójna z filtrami,
- paginacja UI działa z backendowym źródłem danych.

---

#### T1.1.3 Podpięcie paginacji backendowej do ekranów
**Zakres**
- Admin: klienci, kosztorysy, projekty, wnioski, czas pracy, raporty.
- Front: projekty, kosztorysy, czas pracy.
- Integracja z *istniejącą* paginacją UI (bez duplikowania logiki):
  - backend dostarcza tylko dane strony + `total_count`,
  - aktualne kontrolki paginacji na froncie/adminie pozostają źródłem interakcji użytkownika,
  - adapter mapuje obecne parametry (`page`, `per_page`, filtry, sortowanie) na zapytanie SQL.

**DoD**
- nawigacja stron nie wymaga ładowania pełnej tabeli do DOM,
- zachowane opcje 25/50/100/200,
- brak regresji filtrów/sortowania,
- brak drugiego, równoległego mechanizmu paginacji w JS/PHP (jeden wspólny flow UI → API → SQL).

---

### B. Cache raportów po filtrach + poprawna invalidacja (P0)

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

**Efekt biznesowy**
- dashboardy i raporty działają „instant”,
- stabilne doświadczenie użytkownika przy dużych wolumenach.

---

### C. Ograniczenie złożoności `omd_rozliczenia` (P1)

#### T1.2.2 Prefetch/index danych do rozliczeń miesięcznych
**Co rozumiemy przez „ograniczenie złożoności”**
- redukcja liczby zagnieżdżonych pętli i powtarzanych zapytań w `omd_rozliczenia`,
- przeniesienie ciężaru z obliczeń „w locie” na preagregację i słowniki indeksowane po kluczach (`project_id`, `employee_id`, `month`),
- jeden przebieg danych na raport zamiast wielu przebiegów po tych samych rekordach.

**Zakres**
- prefetch/index danych do wyliczeń miesięcznych (zamiast wielokrotnych pętli),
- batchowe pobieranie danych źródłowych i mapowanie do struktur lookup,
- przygotowanie pod snapshoty miesięczne (jeśli potrzebne).

**DoD**
- ta sama logika biznesowa i te same wyniki finansowe jak przed zmianą,
- ograniczenie liczby zapytań SQL w ścieżce raportu 12M,
- zauważalny spadek czasu budowania raportu 12M,
- brak timeoutów na danych historycznych referencyjnych.

**Efekt biznesowy**
- mniej timeoutów i sporów o liczby,
- stabilna analiza za długi okres.

---

### D. Refaktor front/admin JS (P1-P2)

#### T2.1 Front JS refactor (inline → assety)
**Zakres**
- przeniesienie JS z template do `assets/js/front-manager.js` i `assets/js/front-worker.js`,
- zachowanie funkcjonalności 1:1 (tabs, filtry, tabele, paginacja).

**DoD**
- minimalny inline JS,
- brak regresji zachowania ekranów front.

---

#### T2.2 Modularizacja admin JS
**Zakres**
- podział `admin.js` na moduły (`admin-table-tools.js`, `admin-fixed-costs.js`, `admin-collapsible.js`, `admin-form-sync.js`),
- opcjonalnie wspólny silnik tabel dla front/admin.

**DoD**
- entrypoint inicjuje moduły,
- mniejsza odpowiedzialność pojedynczych plików,
- testy manualne bez regresji.

**Efekt biznesowy**
- szybsze wdrażanie zmian UX,
- mniej regresji i tańsze utrzymanie.

---

### E. Backupy: przejście na backup tylko tabel ERP

#### T3.1 Zmiana zakresu backupu
**Zakres**
- odejście od backupu „wszystkie tabele” (`SHOW TABLES`),
- backup wyłącznie tabel z prefiksami ERP OMD,
- opcjonalnie snapshot różnicowy / przyrostowy.

**DoD**
- backup obejmuje wyłącznie dane systemu ERP OMD,
- odtwarzanie jest udokumentowane i testowalne,
- mniejszy rozmiar i czas backupu.

**Priorytet**: P1

**Efekt biznesowy**
- krótsze backupy,
- niższe koszty storage,
- mniejsze ryzyko operacyjne.

---

## 2) NOWE FUNKCJE (wartość użytkowa i przychodowa) — PARKING DOKUMENTACYJNY

### A. „Control Tower” dla managera (daily cockpit)
**Zakres**
- ekran „Dziś/Tydzień” z blokami:
  - projekty z ryzykiem marży,
  - wpisy do akceptacji,
  - odchylenie godzin vs plan.
- wykorzystanie istniejących alertów, metryk i danych finansowych.

**Efekt**
- mniej ręcznego przeklikiwania,
- szybsze decyzje operacyjne.

**Priorytet**: P1

---

### B. Automatyczny „invoice draft” (z akceptacją)
**Zakres**
- rozszerzenie raportu „projekty do faktury” o generację draftu faktury,
- eksport: CSV/PDF,
- webhook API do narzędzia księgowego.

**Efekt**
- skrócenie czasu od delivery do fakturowania,
- mniejsze ryzyko pomyłek manualnych.

**Priorytet**: P1

---

### C. Plan vs wykonanie (capacity + profitability)
**Zakres**
- planowane godziny vs wykorzystanie,
- marża planowana vs rzeczywista per projekt/klient,
- wykorzystanie danych `monthly_hours` i time trackingu.

**Efekt**
- lepsze decyzje sprzedażowe i staffingowe.

**Priorytet**: P1-P2

---

### D. Powiadomienia „akcji wymagających uwagi”
**Zakres**
- rozszerzenie notyfikacji cron o:
  - projekty przekraczające próg marży/budżetu,
  - wnioski/projekty bez ownera.

**Efekt**
- mniej manualnego nadzoru,
- wcześniejsze wykrywanie problemów.

**Priorytet**: P2

---

### E. Rozszerzony workflow wniosków projektowych
**Zakres**
- SLA statusów i automatyczne assignmenty ownerów,
- wykorzystanie istniejącego request lifecycle z FRONT planu.

**Efekt**
- krótszy lead time od potrzeby do startu projektu.

**Priorytet**: P2

---

## 3) KIERUNEK ROZWOJU (produktowo-biznesowy, 12–24 mies.) — PARKING DOKUMENTACYJNY

### Kierunek 1: ERP OMD jako „Operating System” agencji
**Teza**
- rozwój z ewidencji czasu do platformy operacyjnej: staffing, rentowność, cashflow operacyjny.

### Kierunek 2: API-first + integracje
**Teza**
- wykorzystać warstwę REST, feature flags i SLO,
- budować oficjalne integracje: księgowość, BI, komunikatory, CRM.

### Kierunek 3: „Performance as a feature”
**Teza**
- przejść z ogólnego SLO raportów do SLA per ekran,
- mierzyć KPI użytkowe: czas akceptacji, czas fakturowania, utilization.

### Kierunek 4: Front operacyjny jako główne UI dzienne
**Teza**
- doprowadzić do sytuacji, gdzie ~80% codziennych akcji odbywa się poza wp-admin,
- utrzymać brak duplikacji logiki i spójność warstw.

---

## Kolejność realizacji (rekomendacja)
1. **Aktywny zakres (teraz):** cały blok **1) OPTYMALIZACJA**.
2. **Poza zakresem implementacyjnym (na teraz):** bloki **2) NOWE FUNKCJE** i **3) KIERUNEK ROZWOJU** pozostają dokumentacyjne.

---

## Status
- **Dokument zatwierdzony do realizacji:** TAK
- **Tryb pracy:** iteracyjny (wdrożenia etapowe)
- **Data aktualizacji statusu:** 2026-04-04
- **Postęp bloku 1) OPTYMALIZACJA:**
  - **T1.1.1:** ZREALIZOWANE (SQL pagination + count dla time entries wdrożone).
  - **T1.1.2:** ZREALIZOWANE (SQL pagination + count dla kluczowych list admin/front).
  - **T1.1.3:** ZREALIZOWANE (UI spięte z backend pagination + testy kontraktowe REST + testy repozytoriów paginacji).
  - **T1.2.1:** ZREALIZOWANE (cache raportów po filtrach + invalidacja przez bump wersji).
  - **T1.2.2:** W TRAKCIE (wdrożony prefetch/index kosztów stałych, historii wynagrodzeń, wpisów czasu i kosztów bezpośrednich dla trendu 12M + agregacja wpisów czasu jednym przebiegiem zamiast filtrowania per miesiąc + ujednolicenie logiki filtrowania wpisów w jednej metodzie + normalizacja wyboru historii wynagrodzeń per miesiąc przez jednoznaczny wybór rekordu o najnowszym `valid_from` + zbiorczy odczyt kosztów bezpośrednich dla zakresu 12M zamiast `for_project` per projekt).
- **Następny krok wykonawczy (najbliższe wdrożenie):**
  1. benchmark ścieżki raportowej 12M (przed/po dla kolejnych iteracji T1.2.2) — skrypt referencyjny: `php tests/reporting-benchmark-12m.php`,
  2. dalsze ograniczenie złożoności pętli/duplikacji logiki w `omd_rozliczenia` (jednolite lookupy i mniej warunków rozproszonych; kolejny kandydat: preagregacja miesięczna po stronie SQL dla direct costs i salary history),
  3. porównanie metryk po wdrożeniu (czas raportu 12M, liczba zapytań SQL, stabilność bez timeoutów).

### Realizacja kroków (T1.2.2)
- **Krok 1/3:** WYKONANY — benchmark 12M uruchamiany po każdej iteracji + doprecyzowanie zakresu dat dla batch direct costs do realnego końca miesiąca (bez stałego `-31`).
