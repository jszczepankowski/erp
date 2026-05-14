# Release Notes vs Realne Testy — Polityka dowodów (ERP OMD)

## Cel
Zapewnić, że każda informacja w release notes o testach jest weryfikowalna i oparta o realnie wykonane polecenia.

## Zasady obowiązkowe
1. **Zakaz deklaracji bez dowodu**
   - Nie wpisujemy „uruchomiono PHPUnit/PHPStan/PHPCS”, jeśli nie ma artefaktu wykonania.
2. **Sekcja "Evidence" w każdym PR**
   - Każdy check ma: komendę, wynik (pass/fail/warn), datę, wykonawcę.
3. **Spójność nazw**
   - Nazwy testów/komend w release notes muszą dokładnie odpowiadać uruchomionym komendom.
4. **Środowisko testów**
   - Każdy raport zawiera kontekst: local/staging, wersja PHP, ewentualne ograniczenia.

## Minimalny szablon sekcji testów w PR
- ✅ `php -l <plik>`
- ✅ `vendor/bin/phpunit` (jeśli uruchomione)
- ⚠️ `<komenda>` (jeśli pominięta z powodów środowiskowych)

Dodatkowo:
- Timestamp wykonania,
- Krótki wynik liczbowy (np. `145 tests, 0 failures`).

## Gate przed merge
PR nie może być mergowany, jeśli:
- zawiera deklarację uruchomienia testu bez artefaktu,
- nie zawiera sekcji Evidence,
- ma niespójność komenda vs opis.

## Artefakty dowodowe
Dopuszczalne:
- log terminala w opisie PR,
- załączony plik `docs/test-reports/<data>-<branch>.md`,
- wynik z CI (link + numer joba).

## Checklista redakcyjna release notes
1. Czy każda deklaracja testu ma dowód?
2. Czy komendy są dokładne i kompletne?
3. Czy są oznaczenia ✅/⚠️/❌?
4. Czy opis ograniczeń środowiska jest jawny?
5. Czy wersje (PHP/app) są podane?

## Plan wdrożenia
Sprint 1:
- Wprowadzić powyższy standard dla wszystkich nowych PR.

Sprint 2:
- Dodać prosty skrypt walidujący obecność sekcji Evidence w szablonie PR.

Sprint 3:
- Spiąć walidację z CI (status check blokujący merge bez Evidence).
