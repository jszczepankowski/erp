# UAT-D4 — mini scenariusz i checklista

Data: 2026-04-08  
Zakres: Dashboard v1 — **korekty + drilldown** (punkt `UAT-D4`).

## 1) Cel testu

Potwierdzić, że:
1. Korekty wykonane przez admina są widoczne i czytelne operacyjnie.
2. Drilldown z dashboardu prowadzi do właściwych widoków raportowych.
3. Dane w dashboardzie i danych docelowych (raporty / audit log) są spójne.

## 2) Warunki wejścia

- Miesiąc testowy: np. `2026-03` (zamknięty lub w stanie do rozliczenia).
- Wykonana minimum 1 korekta admina z `reason`.
- Uprawnienia:
  - konto admin (wykonanie korekty + weryfikacja),
  - konto non-admin (opcjonalny smoke blokad).

## 3) Mini scenariusz (krok po kroku)

### Krok D4.1 — Przygotowanie danych
- Wejdź: `admin.php?page=erp-omd-reports&tab=monitoring`.
- Wybierz miesiąc testowy.
- W sekcji „Szybka korekta admina” wybierz koszt z listy i zapisz korektę z powodem.

Oczekiwane:
- Komunikat sukcesu zapisu.
- Wpis pojawia się w „Audit log korekt” dla właściwego miesiąca.

### Krok D4.2 — Weryfikacja widoczności korekty na dashboardzie
- W sekcji „Dashboard v1 — podgląd operacyjny” odśwież miesiąc/mode.
- Sprawdź, czy sekcja korekt/adjustments pokazuje nową korektę.

Oczekiwane:
- Korekta jest widoczna (co najmniej 1 rekord odpowiadający zapisowi).
- Widoczne są podstawowe atrybuty: typ/encja/powód/data.

### Krok D4.3 — Drilldown z dashboardu
- Kliknij link drilldown powiązany z korektą (lub sekcją adjustments/profitability/queue, zależnie od widocznego rekordu).
- Zweryfikuj, że link otwiera odpowiedni raport z poprawnym miesiącem.

Oczekiwane:
- Przejście działa bez błędu.
- W URL i filtrach zgadza się `month`.
- Docelowy ekran zawiera dane spójne z rekordem źródłowym.

### Krok D4.4 — Spójność dashboard vs audit log
- Porównaj ten sam rekord między:
  1) dashboard,
  2) tabelą „Audit log korekt”.

Oczekiwane:
- Zgodność encji, miesiąca i powodu.
- Brak rozjazdu „Przed/Po” względem zapisanej korekty.

## 4) Checklista PASS/FAIL (do odhaczania)

- [x] D4.1 Korekta zapisana i widoczna w audit log. — PASS (potwierdzenie użytkownika, 2026-04-08)
- [x] D4.2 Korekta widoczna na dashboardzie. — PASS (potwierdzenie użytkownika, 2026-04-08)
- [x] D4.3 Drilldown otwiera poprawny ekran i poprawny miesiąc. — PASS (potwierdzenie użytkownika, 2026-04-08)
- [x] D4.4 Spójność danych dashboard ↔ audit log potwierdzona. — PASS (potwierdzenie użytkownika, 2026-04-08)

## 5) Kryterium wyniku

- **PASS**: wszystkie punkty D4.1–D4.4 odhaczone.
- **PASS WARUNKOWY**: brak błędów krytycznych, otwarte tylko drobne uwagi UX.
- **FAIL**: brak widoczności korekt, niespójny drilldown lub rozjazd danych.

## 6) Miejsce na wynik

- Wynik końcowy: `PASS`
- Data i osoba testująca: `2026-04-08, użytkownik biznesowy/operacyjny`
- Notatka: `Wszystkie punkty D4.1–D4.4 potwierdzone jako PASS.`
