# UAT Execution Sheet v1 — do ręcznego przebiegu

Data: 2026-04-07
Cel: szybkie, operacyjne wykonanie UAT ekranów 1–6 przed domknięciem ETAP 1.3.

## 1) Dane wejściowe (minimum)

- min. 1 miesiąc z danymi: wpisy czasu approved + koszty projektowe,
- min. 1 projekt ze statusem `do_faktury` i `operational_close_month`,
- min. 1 korekta admina z wpisem audytowym.

## 2) Linki robocze (wp-admin)

- Raporty (biznes): `admin.php?page=erp-omd-reports&tab=reports`
- Monitoring techniczny: `admin.php?page=erp-omd-reports&tab=monitoring`
- Ustawienia SLO: `admin.php?page=erp-omd-settings#reports-v1-slo-monitoring`

## 2a) Zasada komunikacji testów (USTALENIE)

- Każdy punkt poniżej ma status do wpisania: `PASS` / `PASS WARUNKOWY` / `FAIL`.
- Jeśli potrzebne jest Twoje potwierdzenie, oznaczam to jawnie jako: **[WYMAGA POTWIERDZENIA UŻYTKOWNIKA]**.
- Po każdym bloku ekranu wpisujemy wynik od razu do sekcji „Wyniki przebiegu”.

## 3) Scenariusze UAT do odhaczenia

## Ekran 1 — Dashboard v1
- [ ] D1.1 Status miesiąca renderuje się poprawnie (LIVE/DO ROZLICZENIA/ZAMKNIĘTY).
- [ ] D1.2 Akcje statusowe (admin) zgodne z checklistą gotowości.
- [ ] D1.3 Trend 3M i top/bottom odświeżają się po zmianie miesiąca/trybu.

## Ekran 2 — Raport klient
- [ ] C2.1 Widok podstawowy: sumy i marże spójne.
- [ ] C2.2 Widok szczegółowy: drilldown klient -> projekt -> pozycje.
- [ ] C2.3 Eksport CSV zgodny z widokiem i filtrami.

## Ekran 3 — Raport projekt
- [ ] P3.1 Widok podstawowy zawiera direct cost i budget usage.
- [ ] P3.2 Widok szczegółowy pokazuje wpisy czasu + koszty + billing mix.
- [ ] P3.3 Eksport CSV zgodny z widokiem i filtrami.

## Ekran 4 — Raport czasu pracy
- [ ] T4.1 Agregacje simple poprawne (pracownik/rola/projekt).
- [ ] T4.2 Detail line-by-line + paginacja działają.
- [ ] T4.3 Eksport CSV zgodny z filtrem i stroną.

## Ekran 5 — OMD rozliczenia
- [ ] O5.1 Definicje controllingowe są czytelne i zgodne ze spec.
- [ ] O5.2 Wyniki OMD i eksport bez regresji.

## Ekran 6 — Zarządzanie miesiącem
- [ ] M6.1 LIVE -> DO ROZLICZENIA blokowane, gdy checklista ma blokery.
- [ ] M6.2 DO ROZLICZENIA -> ZAMKNIĘTY działa dla admina.
- [ ] M6.3 Po zamknięciu: tylko admin i tylko ścieżką korekt.

## 4) Kryterium wyjścia ETAP 1.3

- PASS: wszystkie punkty [x] i brak blockerów P0.
- PASS warunkowy: brak blockerów P0, otwarte tylko poprawki UX/P2.
- FAIL: jakikolwiek blocker P0 w danych/rozliczeniu/uprawnieniach.

## 5) Przebieg krok po kroku (z linkami i miejscem na wynik)

## Krok 1 — Dashboard v1
- Link: `admin.php?page=erp-omd-reports&tab=monitoring` (sekcja „Dashboard v1 — podgląd operacyjny”).
- Sprawdź: status miesiąca, checklistę gotowości, top/bottom po zmianie miesiąca i trybu.
- **[WYMAGA POTWIERDZENIA UŻYTKOWNIKA]** Wynik: `PASS (potwierdzone przez użytkownika)`

## Krok 2 — Raport klient
- Link: `admin.php?page=erp-omd-reports&tab=reports&report_type=clients`.
- Sprawdź: widok podstawowy, drilldown w szczegółach, eksport CSV.
- **[WYMAGA POTWIERDZENIA UŻYTKOWNIKA]** Wynik: `PASS (potwierdzone przez użytkownika)`

## Krok 3 — Raport projekt
- Link: `admin.php?page=erp-omd-reports&tab=reports&report_type=projects`.
- Sprawdź: direct cost, budget usage, szczegóły wpisów/kosztów/mix, eksport CSV.
- **[WYMAGA POTWIERDZENIA UŻYTKOWNIKA]** Wynik: `PASS (potwierdzone przez użytkownika)`

## Krok 4 — Raport czasu pracy
- Link: `admin.php?page=erp-omd-reports&tab=reports&report_type=time_entries`.
- Sprawdź: simple + detail, paginacja, eksport CSV.
- **[WYMAGA POTWIERDZENIA UŻYTKOWNIKA]** Wynik: `PASS (potwierdzone przez użytkownika; uwaga UX: simple/detail obecnie mają bardzo zbliżoną zawartość)`

## Krok 5 — OMD rozliczenia
- Link: `admin.php?page=erp-omd-reports&tab=reports&report_type=omd_rozliczenia`.
- Sprawdź: definicje controllingowe, zgodność wyników i eksportu.
- **[WYMAGA POTWIERDZENIA UŻYTKOWNIKA]** Wynik: `PASS (potwierdzone przez użytkownika)`

## Krok 6 — Zarządzanie miesiącem
- Link: `admin.php?page=erp-omd-reports&tab=monitoring` + `admin.php?page=erp-omd-settings#reports-v1-slo-monitoring`.
- Sprawdź: przejścia statusów, blokady po zamknięciu, zachowanie ścieżki korekt.
- Jeśli nie widzisz akcji zmiany statusu, to test wymaga konta z uprawnieniem `erp_omd_manage_settings` lub `administrator`.
- Alternatywa techniczna: potwierdzenie przez admina na endpointach `/erp-omd/v1/periods/{month}/transition` i próbie zapisu wpisu czasu bez/ze `reason`.
- **[WYMAGA POTWIERDZENIA UŻYTKOWNIKA]** Wynik: `BLOKADA UPRAWNIEŃ (oczekuje na test admina)`

## 6) Wyniki przebiegu (do bieżącego uzupełniania)

- Krok 1 (Dashboard v1): `PASS`
- Krok 2 (Raport klient): `PASS`
- Krok 3 (Raport projekt): `PASS`
- Krok 4 (Raport czasu): `PASS (z uwagą UX o podobnym zakresie simple/detail)`
- Krok 5 (OMD): `PASS`
- Krok 6 (Zarządzanie miesiącem): `BLOKADA UPRAWNIEŃ -> test admina wymagany`

## 7) Notatka końcowa

Po zakończeniu przebiegu wpisz wynik do:
- `docs/UAT_MASTER_PASS_V1_2026-04-07.md`
- `docs/WDROZENIE_V1_DZIENNIK.md` (status ETAP 1 / KROK 1.3)
