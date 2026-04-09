# Code Review ERP_OMD 3.1.0 (2026-04-09)

## Zakres przeglądu
- Analiza statyczna kodu backend (PHP) + architektura pluginu WordPress.
- Uruchomienie testów regresyjnych Sprintu 10 i testów dedykowanych pod kolizje metod.
- Ocena: duplikaty, niedokończone wątki/funkcje, konflikty kontraktów, elementy zbędne.

---

## 1) Duplikaty i nakładające się odpowiedzialności

### 1.1. Podwójny punkt wejścia do `ERP_OMD_Reporting_Service`
W repozytorium funkcjonują jednocześnie:
- wrapper `includes/services/class-reporting-service.php` (proxy do V2),
- implementacja `includes/services/class-reporting-service-v2.php`.

Autoloader mapuje klasę bezpośrednio na V2, ale bootstrap pluginu dodatkowo wpisuje wrapper do listy plików invalidowanych przez OPcache. To zwiększa złożoność operacyjną (2 ścieżki ładowania tej samej klasy) i utrudnia utrzymanie. 

**Rekomendacja:** po stabilizacji wyciąć wrapper i zostawić jeden, kanoniczny plik klasy, plus aktualizację invalidacji OPcache.

### 1.2. Podwójny punkt wejścia do `ERP_OMD_Estimate_Repository`
Analogiczny wzorzec występuje dla `class-estimate-repository.php` (proxy) i `class-estimate-repository-v2.php` (implementacja). Technicznie działa poprawnie, ale utrzymuje dług technologiczny i ryzyko „cichego” rozjazdu plików.

**Rekomendacja:** zunifikować do jednej implementacji (bez suffixu `-v2`) albo wdrożyć oficjalny mechanizm wersjonowania klas (np. namespace + alias).

---

## 2) Niedokończone wątki / niespójne kontrakty

### 2.1. Regresja paginacji raportu `time_entries`
W `build_report(..., report_type = 'time_entries')` paginacja jest nadpisywana na stałe (`total_pages = 1`, `page_num = 1`, `per_page = total_items`), mimo że filtry przyjmują `page_num` i `per_page`.

Skutkiem jest niespójność kontraktu API/UI oraz niezgodność z testem oczekującym wielostronicowej paginacji.

**Wpływ:**
- błędny UX na liście wpisów czasu,
- eksport i widok mogą rozjeżdżać się semantycznie względem oczekiwań użytkownika,
- nieprzewidywalność przy większym wolumenie danych.

**Rekomendacja:** wdrożyć realne `array_slice`/paginację SQL dla `time_entries` i spójnie zwracać metadane `total_pages`, `page_num`, `per_page`.

### 2.2. Niespójność testów `Cron Manager` vs implementacja
Test kontraktowy oczekuje obecności metod `restore_backup_bundle_from_zip()` i `import_sql_dump()` w `ERP_OMD_Cron_Manager`, natomiast aktualna implementacja tych metod nie zawiera (są w `Backup_Manager`).

To wygląda na niedokończoną refaktoryzację: kod został przeniesiony, ale kontrakt testowy nie został formalnie zaktualizowany.

**Rekomendacja:** podjąć decyzję architektoniczną i ją domknąć:
1. albo przywrócić kompatybilny adapter w `Cron_Manager`,
2. albo poprawić testy i dokumentację, wskazując `Backup_Manager` jako jedyne źródło prawdy.

---

## 3) Ocena jakości kodu

### 3.1. Plusy
- Dobra separacja warstw: repozytoria, serwisy, klasy wejściowe pluginu.
- Obecny zestaw testów domenowych (time/finance/reporting/backup/REST) daje sensowną siatkę regresji.
- Widoczne hardeningowe decyzje (sanity checki, walidacje, sanitizacja wejścia).

### 3.2. Ryzyka jakościowe
- Duże klasy typu „god object”:
  - `class-admin.php` (~3000 linii),
  - `class-frontend.php` (~2100 linii),
  - `class-rest-api.php` (~1800 linii),
  - `class-reporting-service-v2.php` (~1400 linii).

To podnosi koszt zmian i ryzyko regresji przy każdej modyfikacji.

**Rekomendacja:** etapowa dekompozycja na handlery per moduł/endpoint/ekran.

---

## 4) Konflikty i elementy potencjalnie zbędne

1. **Konflikt kontraktowy paginacji** (kod vs testy) — wymaga pilnej decyzji produktowej i technicznej.
2. **Konflikt kontraktowy cron/backup** (testy vs implementacja) — wymaga domknięcia refaktoryzacji.
3. **Pliki proxy `*-v2`** — obecnie działają jako warstwa kompatybilności, ale długoterminowo są zbędnym mnożeniem punktów wejścia.

---

## 5) Priorytety naprawcze (proponowana kolejność)

### P0 (krytyczne)
1. Naprawa paginacji `time_entries` + aktualizacja testów raportowych.
2. Ujednolicenie kontraktu `Cron_Manager` i `Backup_Manager` (kod + test + docs).

### P1 (wysokie)
3. Rozbicie `class-admin.php` i `class-rest-api.php` na moduły.
4. Rozbicie `class-reporting-service-v2.php` na buildery raportów per typ.

### P2 (średnie)
5. Redukcja warstw proxy (`class-reporting-service.php`, `class-estimate-repository.php`) po potwierdzeniu braku zależności legacy.

---

## 6) Wnioski końcowe
Kod jest funkcjonalnie dojrzały i posiada bazę testową, ale przed dalszym skalowaniem wymagane jest domknięcie dwóch regresji kontraktowych oraz uporządkowanie wersjonowanych punktów wejścia (`v2` + wrappery). Największe ryzyko na dziś to niespójność zachowania raportowania i backup/cron względem testów oraz dokumentacji.
