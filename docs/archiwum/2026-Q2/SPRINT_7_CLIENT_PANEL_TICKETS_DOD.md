# ERP_4.0 — Sprint 7 — Panel klienta front (MVP) — Tickets + Definition of Done

Data bazowa: 2026-04-21  
Owner biznesowy: **Admin**  
Priorytet: **time-to-value + bezpieczeństwo danych klienta**

Dokument źródłowy zakresu: `docs/ERP_4_0_BACKLOG_MASTER.md` (EPIC G).

---

## 0) Założenia i kontekst

1. Sprint 7 realizuje EPIC G i rozwija panel klienta front jako osobny kanał dostępu.
2. Zakres MVP obejmuje:
   - osobne konto klienta,
   - listę projektów ze statusem i deadline,
   - widok finansowy projektu (bez kosztów wewnętrznych),
   - historię zmian budżetu,
   - wątek komunikacji i załączniki z wersjonowaniem,
   - historię zleceń w podziale miesięcznym.
3. Źródłem prawdy dla priorytetu i zależności pozostaje backlog ERP_4.0.
4. Dalsze rozbudowy realizujemy po domknięciu MVP i podpisanym UAT.

---

## 1) Sprint 7 — konkretne tickety

> Skala estymacji: S (<=0.5d), M (1d), L (2d), XL (3-4d)

### EPIC G — Panel klienta front

### S7-01 (M): Konto klienta i dedykowane logowanie frontowe (G0)
**Zakres**
- Dodanie/aktywacja typu konta klienta dla dostępu frontowego.
- Dedykowany ekran logowania klienta + redirect po poprawnym logowaniu.
- Ograniczenie widoczności tylko do danych klienta (tenant isolation).

**DoD**
- [x] Klient loguje się przez dedykowany ekran login/hasło.
- [x] Klient bez poprawnych uprawnień nie widzi danych innych klientów.
- [x] Logowanie i wylogowanie mają poprawny redirect UX.
- [x] Testy uprawnień i izolacji danych przechodzą.

---

### S7-02 (L): Lista projektów klienta ze statusem i deadline (G1.1)
**Zakres**
- Lista projektów klienta na panelu front.
- Widoczne pola: nazwa projektu, status, deadline, podstawowe metadane.
- Sortowanie listy projektów (w tym po deadline) bez dodatkowego filtra miesiąca deadline.

**DoD**
- [x] Klient widzi wyłącznie swoje projekty.
- [x] Każdy rekord pokazuje status i deadline.
- [x] Sortowanie (w tym po deadline) działa poprawnie.
- [x] Testy listy i sortowania przechodzą.

---

### S7-03 (L): Widok szczegółu finansowego projektu (G1 + G2)
**Zakres**
- Widok planowanego budżetu i zwiększeń przez pozycje przychodowe.
- Widok kosztów projektu per pozycja, bez kosztów wewnętrznych.
- Chronologiczna historia zmian budżetu.

**DoD**
- [x] Klient widzi budżet planowany i zwiększenia.
- [x] Koszty wewnętrzne nie są prezentowane klientowi.
- [x] Historia zmian budżetu jest czytelna i uporządkowana czasowo.
- [x] Testy mapowania danych finansowych przechodzą.

---

### S7-04 (L): Wątek projektowy + załączniki i wersjonowanie (G3 + G4)
**Zakres**
- Wspólny wątek komunikacji klient ↔ zespół projektu.
- Upload plików: `pdf/jpg/png/zip` do 30MB.
- Wersjonowanie załączników i historia zmian pliku.

**DoD**
- [x] Dodanie wiadomości zapisuje wpis w wątku projektu.
- [x] Walidacja typu i rozmiaru pliku działa zgodnie z limitem 30MB.
- [x] Każda nowa wersja pliku jest możliwa do audytu.
- [x] Testy uploadu i wersjonowania przechodzą.

---

### S7-05 (M): Historia zleceń klienta z podziałem miesięcznym (G5)
**Zakres**
- Widok historii zleceń/projektów z grupowaniem po miesiącu (YYYY-MM).
- Agregacje miesięczne (liczba zleceń, sumaryczne wartości, statusy).
- Przejście z widoku miesiąca do szczegółów pozycji.

**DoD**
- [x] Historia jest grupowana po miesiącach.
- [x] Agregacje miesięczne są poprawne względem danych źródłowych.
- [x] Użytkownik może wejść z miesiąca do szczegółu zlecenia/projektu.
- [x] Testy poprawności agregacji przechodzą.

---

### S7-06 (S): UAT i closure Sprintu 7
**Zakres**
- Checklista UAT dla panelu klienta.
- Release notes i closure doc Sprintu 7.

**DoD**
- [x] UAT checklist wykonana i podpisana przez ownera.
- [x] Release notes + closure dodane do `docs/`.
- [x] Brak krytycznych blockerów P1/P2 dla MVP.

---

## 2) Kolejność realizacji (rekomendowana)

1. S7-01  
2. S7-02  
3. S7-03  
4. S7-04  
5. S7-05  
6. S7-06

---

## 3) Minimalny pakiet testów na gate merge

1. Uprawnienia i bezpieczeństwo:
- izolacja danych klienta,
- autoryzacja i routing dostępu frontowego.

2. Funkcjonalne:
- lista projektów + status + deadline + sortowanie,
- budżet i koszty per pozycja (bez kosztów wewnętrznych),
- historia zmian budżetu,
- komunikacja i załączniki (typ/limit/wersjonowanie),
- historia miesięczna i agregacje.

3. Regresja:
- brak regresji panelu worker/manager,
- brak regresji istniejących endpointów i usług domenowych.

---

## 4) Kryterium zakończenia Sprintu 7

Sprint 7 uznajemy za zakończony, gdy:
- klient loguje się i pracuje w dedykowanym panelu frontowym,
- klient widzi projekty, statusy i deadliny,
- klient widzi widok finansowy zgodny z zasadą „bez kosztów wewnętrznych”,
- komunikacja projektowa i załączniki (z wersjonowaniem) działają produkcyjnie,
- historia zleceń miesięcznie jest dostępna i czytelna,
- UAT jest podpisany i nie ma blockerów krytycznych.

## 5) Status zamknięcia (2026-04-23)

- ✅ Sprint 7 formalnie zamknięty (CLOSED / DONE).
- ✅ Decyzja produktowa: deadline w liście projektów jest **sortowalny**, bez osobnego filtra miesiąca deadline.
- 📄 UAT: `docs/UAT_CHECKLIST_SPRINT_7_CLIENT_PANEL_2026-04-23.md`
- 📄 Closure: `docs/RELEASE_CLOSURE_SPRINT_7_2026-04-23.md`
