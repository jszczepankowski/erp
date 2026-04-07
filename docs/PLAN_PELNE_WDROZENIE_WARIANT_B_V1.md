# Plan pełnego wdrożenia ustaleń v1 (Wariant B)

Data: 2026-04-07  
Wejście: `docs/SPECYFIKACJA_V1_WARIANT_B.txt` + aktualny stan kodu i snapshotów.

## 1) Cel

Domknąć wdrożenie **100% MUST** ze specyfikacji v1 oraz utrwalić tryb utrzymaniowy (steady-state), tak aby:
- proces miesiąca LIVE -> DO ROZLICZENIA -> ZAMKNIĘTY działał bez wyjątków,
- korekty admina miały pełny, audytowalny i wygodny operacyjnie flow,
- raporty operacyjne i OMD były spójne definicyjnie oraz stabilne wydajnościowo,
- UAT/release/rollback były odtwarzalne na checklistach.

## 2) Stan obecny vs SPEC (co mamy / czego nie mamy)

Legenda: ✅ gotowe, 🟡 częściowo, ❌ brak.

### A. Fundament okresów i przejść (SPEC rozdz. 2, 11, 13)
- ✅ Statusy miesiąca i przejścia (LIVE/DO_ROZLICZENIA/ZAMKNIETY) + blokada niedozwolonego powrotu przez `can_transition`.  
- ✅ Walidacja formatu miesiąca `YYYY-MM` + odrzucanie out-of-range (422) na API.  
- ✅ Endpointy `/periods`, `/periods/{month}`, `/periods/{month}/transition` działają.
- 🟡 Checklista gotowości istnieje i ma meta-sygnały, ale wymaga pełnego domknięcia biznesowego i UAT „ekran po ekranie”.

### B. Korekty i audyt admina (SPEC rozdz. 5, 11.3, 13)
- ✅ Tabela audytu korekt i zapis pełnych rekordów (kto/kiedy/co/przed/po/powód/typ).  
- ✅ Rozróżnienie `STANDARD` vs `EMERGENCY_ADJUSTMENT` (logika 72h).  
- ✅ Endpointy `GET|POST /adjustments` z filtrami.
- 🟡 Brakuje dedykowanego, wygodnego operacyjnie ekranu „Audit log korekt” z pełnym workflow operatorskim i eksportem CSV per audyt.

### C. Reguły księgowania i statusy domenowe (SPEC rozdz. 3, 4, 12)
- ✅ Approved-only dla czasu w raportowaniu i finansach.
- ✅ `operational_close_month` obecny i używany w logice raportowej.
- ✅ Migracja/ujednolicenie `inactive -> archiwum` zrobione w UI i raportach.
- 🟡 Należy dopiąć finalny pakiet testów regresji „wielomiesięczne + backfill historyczny” na danych stagingowych.

### D. Raporty operacyjne (SPEC rozdz. 7.1, 9)
- ✅ Raport klient/projekt/czas (simple/detail), drilldown oraz paginacja działają.
- ✅ Eksport CSV 1:1 z aktywnymi filtrami jest wdrożony.
- ✅ Tryby `mode` (LIVE/DO_ROZLICZENIA/ZAMKNIETY) są spięte z raportowaniem.
- 🟡 Brakuje formalnego „UAT pass” udokumentowanego w jednym artefakcie per ekran i per scenariusz graniczny.

### E. Dashboard v1 i OMD (SPEC rozdz. 7.2, 8)
- ✅ Dashboard v1 ma status miesiąca, checklistę, trend 3M, top/bottom, kolejkę i korekty.
- ✅ OMD jako warstwa controllingowa jest utrzymany i opisany.
- ✅ Kontrakt status-label i linki drilldown są domknięte.
- 🟡 Potrzebne domknięcie operacyjne: runbook-driven ćwiczenia rollback + potwierdzenie ergonomii on-call na realnych incydentach/symulacjach.

### F. Bezpieczeństwo / wydajność / obserwowalność (SPEC EPIC E7)
- ✅ Monitoring raportów, SLO p95, freshness, error-rate i sygnały drift działają (`system/status` + banery UX).
- ✅ Tryb steady-state i telemetryka drift ratio są wdrożone.
- 🟡 Brakuje cyklicznego benchmarku wydajności na większym wolumenie danych oraz formalnego raportu z testów penetracyjnych endpointów krytycznych.

## 3) Jak ma działać całość po domknięciu (target operating model)

1. **Miesiąc startuje w LIVE** i zbiera dane operacyjne.
2. **Admin inicjuje przejście LIVE -> DO ROZLICZENIA**, a system wymusza pozytywną checklistę gotowości.
3. Po wejściu w **DO ROZLICZENIA** zwykli użytkownicy nie edytują danych miesiąca.
4. **Admin zamyka miesiąc do ZAMKNIĘTY**; od tego momentu korekty idą tylko ścieżką admina.
5. Przez **72h** działa korekta standardowa; po oknie tylko tryb awaryjny (`EMERGENCY_ADJUSTMENT`) z obowiązkowym powodem i pełnym audytem.
6. Raporty klient/projekt/czas oraz OMD są spójne metrycznie, a dashboard v1 pokazuje stan, trend i priorytety operatorskie.
7. On-call opiera decyzje o rollback/tuning o `system/status`, runbook i próbki telemetryczne steady-state.

## 4) Plan domknięcia pełnego wdrożenia (4 fale)

## Fala 1 (P0) — domknięcie braków MUST operacyjnych
1. **Audit Log UX + CSV**
   - dedykowany ekran admina dla rejestru korekt,
   - filtrowanie po miesiącu/typie/encji/użytkowniku,
   - eksport CSV rejestru korekt.
2. **UAT Master Pass (formalny)**
   - pełny przebieg checklist UAT ze specyfikacji,
   - podpisany artefakt: co przeszło, co wymaga poprawki, kto zatwierdził.

Wyjście z Fali 1:
- brak otwartych punktów MUST bez właściciela,
- opublikowany raport UAT (PASS lub PASS z warunkami).

## Fala 2 (P1) — twarda jakość i bezpieczeństwo
1. **Benchmark wydajności raportów** (duże `detail`, wysokie `per_page`, miesiące o dużym wolumenie).
2. **Testy bezpieczeństwa endpointów krytycznych** (`/periods/.../transition`, `/adjustments`, raporty).
3. **Regresja migracyjna staging** na kopii danych (inactive->archiwum, backfill `operational_close_month`, zgodność eksportów).

Wyjście z Fali 2:
- raport wydajności z rekomendacjami indeksów/tuningu,
- checklista security bez krytycznych findings.

## Fala 3 (P1) — operacyjność steady-state
1. **Rollback drill** wg runbooka (ćwiczenie na sucho + czasy reakcji).
2. **Kalibracja i przegląd progów SLO** na podstawie realnych próbek.
3. **Operacyjna matryca alertów** (co jest `ok/warn/alert`, kto reaguje, w jakim SLA).

Wyjście z Fali 3:
- zatwierdzony playbook on-call,
- potwierdzone czasy MTTR/rollback.

## Fala 4 (P2) — cleanup i zamknięcie programu
1. Ujednolicenie dokumentacji (`RAPORTY_NEW`, `WARIANT_B_PROGRESS`, checklisty go-live/runbook).
2. Zamknięcie otwartych „legacy mentions” i dopisanie finalnej noty „v1 fully implemented”.
3. Ustalenie backlogu „v1.1” (już poza MUST baseline).

Wyjście z Fali 4:
- jeden spójny „source of truth” dla utrzymania,
- formalne zamknięcie wdrożenia v1.

## 5) Konkret: co robimy teraz (najbliższe 2 tygodnie)

Sprint A:
- Audit Log UX + CSV,
- UAT ekranów 6 i 7 (zarządzanie miesiącem + audyt korekt),
- benchmark raportów `projects detail` i `time_entries detail`.

Sprint B:
- testy bezpieczeństwa endpointów,
- rollback drill + aktualizacja runbooka,
- finalny raport „MUST coverage 100%”.

## 6) Definicja sukcesu (DoD programu)

Wdrożenie uznajemy za pełne, gdy jednocześnie:
1. wszystkie punkty MUST ze specyfikacji mają status „wdrożone + zweryfikowane UAT”,
2. brak krytycznych odchyleń w security/performance,
3. rollback jest przećwiczony i odtwarzalny,
4. dokumentacja operacyjna jest spójna i aktualna.

## 7) Potwierdzenie kompletności planu względem SPEC (MUST coverage)

Tak — ten plan jest **kompletny względem MUST baseline** ze `SPECYFIKACJA_V1_WARIANT_B`, bo obejmuje wszystkie obszary wymagane do uzyskania efektu końcowego:
- model okresów + przejścia + blokady,
- korekty admina 72h/awaryjne + pełny audyt,
- reguły księgowania (`approved-only`, `operational_close_month`, `archiwum`),
- raporty operacyjne klient/projekt/czas (simple/detail + eksport),
- dashboard v1 i warstwę controllingową OMD,
- UAT, rollout, rollback i operacyjność utrzymaniową.

Dla pełnej jednoznaczności mapujemy finalizację MUST do fal:
- **Fala 1:** domknięcie MUST operacyjnych (audit UX + formalny UAT).
- **Fala 2:** bezpieczeństwo/wydajność/migracje produkcyjnie bezpieczne.
- **Fala 3:** gotowość operacyjna on-call i rollback drill.
- **Fala 4:** finalny cleanup dokumentacji i formalne zamknięcie programu v1.

## 8) Sposób prowadzenia prac „na żywo” (cross-chat)

Od teraz postęp prowadzimy w jednym dzienniku: `docs/WDROZENIE_V1_DZIENNIK.md`.

Zasada aktualizacji po każdym kroku:
1. aktualny etap i krok (`ETAP`, `KROK`),
2. status (`TODO` / `W TRAKCIE` / `DONE`),
3. krótki wynik i co dalej,
4. data + commit/hash.

Dzięki temu można wznowić temat w dowolnym chacie bez utraty kontekstu.

## 9) Efekt „po ludzku” — jak to będzie działać po wdrożeniu

Po wdrożeniu praca zespołu będzie prostsza i przewidywalna:
- **Zamykanie miesiąca:** admin przechodzi przez jasną checklistę i jednym procesem zamyka miesiąc; po zamknięciu nikt „przypadkiem” nie zmieni danych z raportów.
- **Korekty:** jeśli trzeba coś poprawić po zamknięciu, admin robi to legalną ścieżką z obowiązkowym uzasadnieniem; zawsze wiadomo kto, co i kiedy zmienił.
- **Raporty:** raport klienta/projektu/czasu pokazują spójne liczby i można je rozwinąć do szczegółu bez ręcznego składania danych z kilku miejsc.
- **Dashboard:** w jednym widoku widać stan miesiąca, trend i miejsca wymagające reakcji — bez biegania po kilku ekranach.
- **Utrzymanie:** gdy coś „zaczyna pływać”, zespół ma gotowy playbook: jak to rozpoznać, jak szybko ograniczyć wpływ i jak bezpiecznie wrócić do stabilnego stanu.
