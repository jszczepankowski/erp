# ERP_OMD 3.1.x — Specyfikacja funkcjonalności, logiki i zależności

Data: 2026-04-09  
Zakres: linia rozwojowa 3.1.x (stabilizacja + porządkowanie kontraktów + przygotowanie pod 3.2)

---

## 1. Cel wersji 3.1.x

Wersja 3.1.x ma dwa główne cele:
1. **Stabilizacja kontraktów domenowych** (raportowanie, backup/cron, API).
2. **Ujednolicenie warstw implementacyjnych** (redukcja dublowania wejść klasy `v2/proxy`).

To jest wydanie jakościowe, nie „feature-heavy”. Główny efekt biznesowy: przewidywalność raportów i bezpieczniejsze wdrożenia.

---

## 2. Zakres funkcjonalny

### 2.1. Raporty i analityka
- `time_entries` musi wspierać rzeczywistą paginację (`page_num`, `per_page`) we wszystkich kanałach:
  - widok admin,
  - eksport,
  - REST API (jeśli endpoint używa tej samej usługi).
- Metadane paginacji muszą być spójne z danymi (`total_items`, `total_pages`, `has_next`, `has_prev`).
- Jednolita semantyka sortowania (domyślnie `entry_date DESC`, tie-breaker po użytkowniku lub id).

### 2.2. Backup i odzyskiwanie
- Jednoznaczny właściciel logiki restore/import (preferowany: `Backup_Manager`).
- `Cron_Manager` odpowiada wyłącznie za harmonogram i delegację.
- Kontrakt testów ma odzwierciedlać finalną architekturę (brak „historycznych” wymagań po przeniesieniu metod).

### 2.3. Ujednolicenie punktów wejścia klas
- Utrzymujemy dokładnie jedną implementację per klasa domenowa.
- Jeśli wymagany jest kompatybilny alias, musi być jawnie opisany w docs i testach.
- Docelowo eliminujemy wrappery `*-v2` tam, gdzie nie ma realnych zależności legacy.

---

## 3. Logika biznesowa (kontrakty)

### 3.1. Kontrakt paginacji raportu czasu
Dla `report_type = time_entries`:
- wejście: `page_num >= 1`, `1 <= per_page <= 200`,
- wyjście:
  - `rows` tylko dla żądanej strony,
  - `last_report_pagination.total_items = liczba wszystkich rekordów po filtrach`,
  - `total_pages = ceil(total_items / per_page)`,
  - `page_num = min(requested_page, total_pages)` (lub pusta lista jeśli poza zakresem — decyzja implementacyjna, ale stała),
  - `has_prev`, `has_next` zgodnie z numeracją.

### 3.2. Kontrakt restore/import
- `Backup_Manager`:
  - `run_backup_bundle()`
  - `restore_backup_bundle_from_zip($zip_path)`
  - wewnętrzna obsługa importu SQL + walidacja pliku.
- `Cron_Manager`:
  - tylko rejestracja i uruchamianie hooków,
  - brak logiki parsera backupu/SQL.

### 3.3. Kontrakt kompatybilności
- Każde przeniesienie metody między klasami wymaga jednoczesnej aktualizacji:
  1. testów kontraktowych,
  2. dokumentacji technicznej,
  3. ewentualnych adapterów BC.

---

## 4. Zależności techniczne

### 4.1. WordPress / PHP
- WordPress hooks (`add_action`, `add_filter`, WP-Cron).
- Repozytoria oparte o `$wpdb` i przygotowane zapytania.
- Sanitizacja i walidacja wejść zgodna z WordPress API.

### 4.2. Moduły wewnętrzne
- `Plugin` (bootstrap) zależy od:
  - `Autoloader`,
  - `Admin`, `Frontend`, `REST_API`,
  - usług i repozytoriów.
- `Reporting_Service` zależy od:
  - repozytoriów projektów/klientów/pracowników/czasu/kosztów,
  - `Project_Financial_Service`.
- `Cron_Manager` zależy od:
  - harmonogramu WP,
  - `Backup_Manager` (delegacja backupu).

### 4.3. Testy / CI
Minimalna bramka jakości 3.1.x:
- lint PHP dla pluginu,
- testy domenowe: time/financial/estimate/reporting,
- testy kontraktowe klas (`cron-manager`, `backup-manager`, `admin`, `frontend`, `rest-api`).

---

## 5. Wymagania niefunkcjonalne

1. **Spójność kontraktu:** kod i testy nie mogą opisywać dwóch różnych architektur.
2. **Obserwowalność:** logi błędów dla backup/restore i raportów z kontekstem filtra.
3. **Wydajność:** raporty paginowane muszą skalować się liniowo do liczby rekordów strony, nie całego zbioru.
4. **Bezpieczeństwo:** wszystkie wejścia użytkownika sanitizowane; import backupu tylko dla uprawnionych ról.

---

## 6. Plan wdrożenia 3.1.x

### Etap A (hotfixy kontraktowe)
- Naprawa paginacji `time_entries`.
- Ujednolicenie kontraktu backup/cron (kod + testy).

### Etap B (porządki architektoniczne)
- Redukcja warstw proxy `v2` tam, gdzie możliwe bez regresji.
- Aktualizacja dokumentacji technicznej i checklist sprintowych.

### Etap C (stabilizacja)
- Pełny regres testów.
- Wydanie 3.1.1 (lub 3.1.2, jeśli etap B wymaga oddzielnego releasu).

---

## 7. Kryteria akceptacji

1. `reporting-service-test.php` przechodzi dla scenariuszy paginacji.
2. `cron-manager-class-test.php` i `backup-manager-class-test.php` są zgodne z finalną odpowiedzialnością klas.
3. Brak niejednoznacznych punktów wejścia dla klas kluczowych (lub jawna, udokumentowana warstwa BC).
4. Dokumentacja release zawiera listę zmian kontraktowych i wpływu na admin UI/API.

---

## 8. Ryzyka i mitigacje

- **Ryzyko:** zmiana paginacji może zmienić zachowanie eksportu.  
  **Mitigacja:** wspólny adapter paginacji + test porównujący widok i eksport.

- **Ryzyko:** usunięcie wrappera może złamać integracje legacy.  
  **Mitigacja:** tymczasowy alias BC przez 1 minor release + wpis do release notes.

- **Ryzyko:** duże klasy utrudniają bezpieczne refaktoryzacje.  
  **Mitigacja:** najpierw wydzielenie metod „pure” do osobnych helperów, potem pełny podział modułów.
