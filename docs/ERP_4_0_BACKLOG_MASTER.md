# ERP_4.0 — backlog master (single source of truth)

Ten dokument agreguje wszystkie ustalenia dla inicjatywy **ERP_4.0**:
- listę zależności między story,
- kolejność sprintów,
- globalne priorytety MoSCoW,
- stories z kryteriami akceptacji Given/When/Then.

---

## 0) Założenia przekrojowe (uzgodnione)

- Wersja robocza: **ERP_4.0**.
- Podniesienie wersji pluginu do `4.0` następuje po potwierdzeniu poprawnego wdrożenia całego zakresu ERP_4.0.
- Zakres obejmuje m.in. deadline projektów, workflow kosztorysów, walidację faktury końcowej PDF, kalendarz + Google sync, moduł dostawców i faktur kosztowych, integrację KSeF oraz panel klienta front.

### Kluczowe reguły domenowe

1. Deadline projektu:
   - 1 główny deadline na projekt,
   - statusy deadline: `ok / ryzyko / po_terminie`,
   - `ryzyko` gdy do deadline <= 3 dni i brak oznaczenia realizacji,
   - deadline można oznaczyć jako zrealizowany przed terminem,
   - uprawnienia zmiany: admin + manager,
   - alerty mailowe do: manager przypisany do projektu + admin (zawsze).

2. Kalendarz + Google:
   - kalendarz pokazuje wszystkie projekty,
   - jeden wspólny kalendarz firmowy Google,
   - 2 eventy: zakres projektu + deadline,
   - synchronizacja CRON co 2h,
   - usunięcie projektu/deadline w ERP usuwa event w Google,
   - event zawiera: nazwa klienta + nazwa projektu,
   - błędy sync generują alert administracyjny.

3. Dostawcy + faktury kosztowe:
   - pola dostawcy: opiekun, email opiekuna, telefon opiekuna (poza danymi fakturowymi),
   - workflow statusów faktury: `zaimportowana -> weryfikacja -> zatwierdzona -> przypisana`,
   - 1 faktura = 1 projekt,
   - projekt może mieć wiele faktur,
   - projekt może mieć wielu dostawców,
   - jeden dostawca może wystawić wiele faktur,
   - unikalność numeru faktury w obrębie dostawcy (nie globalnie),
   - walidacja NIP i email opiekuna: NIE,
   - audit log: MVP tylko dla akcji krytycznych (zaakceptowane).

4. Panel klienta front (MVP):
   - klient widzi planowany budżet projektu + zwiększenia przez pozycje przychodowe,
   - klient widzi koszty projektu per pozycja (bez kosztów wewnętrznych),
   - historia zmian budżetu widoczna,
   - komunikacja: wątek projektowy,
   - załączniki: pdf/jpg/png/zip, limit 30MB, wersjonowanie.

5. Walidacja PDF końcowego przy `do_faktury -> zakończony`:
   - minimum 1 faktura PDF końcowa,
   - brak wyjątków,
   - dotyczy wszystkich typów projektów,
   - walidacja: max 5MB, MIME `application/pdf`, integralność,
   - możliwość dodania faktury do zamkniętych/archiwizowanych projektów opcjonalnie.

6. Akceptacja kosztorysu linkiem:
   - wysyłka wyłącznie dla statusu `do_akceptacji`,
   - link jednorazowy z ważnością 5 dni,
   - odrzucenie wymaga komentarza,
   - akceptacja zmienia status kosztorysu na `zaakceptowany` i tworzy projekt (`do_rozpoczecia`),
   - ponowne wysłanie linku unieważnia stary token,
   - treści i branding maili konfigurowalne w ustawieniach (edytor wizualny).

---

## 1) Lista zależności między story

Legenda:
- `->` blokuje (story po prawej zależy od story po lewej)
- `(soft)` zależność miękka/rekomendowana kolejność

### EPIC A — Deadline projektu
- A1 (dodanie/edycja deadline) -> A2 (status deadline)
- A1 -> A3 (oznaczenie zrealizowany)
- A1 + A2 -> A4 (mailingi deadline)

### EPIC B — Akceptacja kosztorysu linkiem
- B1 (gate statusu `do_akceptacji`) -> B2 (token 5 dni)
- B2 -> B3 (odrzucenie z komentarzem)
- B2 -> B4 (akceptacja + auto projekt)
- B2 -> B5 (wyślij ponownie unieważnia stary token)
- B1 (soft) -> B6 (szablony maili)

### EPIC C — PDF końcowy
- C2 (walidacja pliku) -> C1 (blokada przejścia bez PDF)
- C1 + C2 -> C3 (spięcie z wszystkimi typami projektów)

### EPIC D — Kalendarz ERP + Google
- D1 (kalendarz ERP) -> D2 (2 eventy/projekt)
- D2 -> D3 (sync CRON 2h)
- D3 -> D4 (delete sync)
- D3 -> D5 (alert admin przy błędzie sync)

### EPIC E — Dostawcy/faktury (bez KSeF)
- E1 (moduł dostawców) -> E3 (relacje projekt/dostawca/faktura)
- E1 + E3 -> E4 (unikalność numeru faktury per dostawca)
- E2 (workflow statusów) -> E5 (audit log krytyczny zmian)
- E3 + E2 -> E5

### EPIC F — Integracja KSeF
- E1 + E2 + E3 -> F1 (import KSeF do `zaimportowana`)
- F1 -> F2 (moderacja importu)

### EPIC G — Panel klienta front
- B4 (powstanie projektu po akceptacji) (soft) -> G1 (widok finansowy klienta)
- G1 -> G2 (historia zmian budżetu)
- G1 -> G3 (wątek projektowy)
- G3 -> G4 (załączniki + wersjonowanie)

### Zależności krytyczne między-epikowe
- A1/A2 wymagane przed D2 (deadline event w Google).
- E2 wymagane przed F2 (moderacja importów KSeF).
- B4 rekomendowane przed G1 (klient realnie widzi projekty tworzone po akceptacji).

---

## 2) Kolejność sprintów (ERP_4.0)

### Sprint 1 — Deadline projektu + alerty mailowe
- A1, A2, A3, A4

### Sprint 2 — Akceptacja kosztorysu linkiem
- B1, B2, B3, B4, B5, B6

### Sprint 3 — Wymuszenie PDF końcowego przy zamknięciu projektu
- C2, C1, C3

### Sprint 4 — Kalendarz projektów + Google Calendar
- D1, D2, D3, D4, D5

### Sprint 5 — Dostawcy i faktury kosztowe (bez KSeF)
- E1, E2, E3, E4, E5

### Sprint 6 — Integracja KSeF
- F1, F2

### Sprint 7 — Panel klienta front (MVP)
- G1, G2, G3, G4

---

## 3) Priorytety MoSCoW (globalnie)

## MUST
- A1, A2, A3, A4
- B1, B2, B3, B4, B5
- C1, C2, C3
- D1, D2, D3, D4
- E1, E2, E3, E4
- F1, F2
- G1, G3, G4

## SHOULD
- B6 (edytowalne szablony maili)
- D5 (alert admin przy błędzie sync)
- E5 (MVP audit log krytyczny)
- G2 (historia zmian budżetu)

## COULD
- Dodatkowe raporty KPI dla dostawców i jakości sync,
- Zaawansowane automatyzacje powiadomień,
- Rozszerzone eksporty panelu klienta.

---

## 4) Stories + kryteria Given/When/Then

## EPIC A — Deadline projektu

### A1 (MUST): Dodanie i edycja deadline
**Given** istnieje projekt i użytkownik ma rolę admin lub manager  
**When** użytkownik zapisuje datę deadline  
**Then** deadline zapisuje się i jest widoczny na szczegółach projektu.

### A2 (MUST): Dynamiczny status deadline
**Given** projekt ma deadline i nie jest oznaczony jako zrealizowany  
**When** system przelicza status deadline  
**Then** ustawia `ok`, `ryzyko` (<=3 dni) albo `po_terminie`.

### A3 (MUST): Oznaczenie realizacji przed terminem
**Given** projekt ma aktywny deadline  
**When** admin/manager oznacza deadline jako zrealizowany  
**Then** zapisuje się znacznik realizacji i status przechodzi na `ok`.

### A4 (MUST): Alerty mailowe deadline
**Given** projekt ma przypisanego managera i istnieją admini  
**When** CRON wykryje próg 3 dni, 1 dzień lub po terminie  
**Then** system wysyła mail do managera projektu i adminów.

---

## EPIC B — Akceptacja kosztorysu linkiem

### B1 (MUST): Wysyłka linku tylko dla `do_akceptacji`
**Given** kosztorys ma status inny niż `do_akceptacji`  
**When** użytkownik próbuje wysłać link do klienta  
**Then** system blokuje wysyłkę i pokazuje komunikat walidacji.

### B2 (MUST): Link jednorazowy 5 dni
**Given** kosztorys ma status `do_akceptacji`  
**When** użytkownik wysyła link  
**Then** system tworzy jednorazowy token ważny 5 dni.

### B3 (MUST): Odrzucenie wymaga komentarza
**Given** klient otworzył ważny link  
**When** wybiera opcję odrzucenia bez komentarza  
**Then** system nie zapisuje odrzucenia i wymaga komentarza.

### B4 (MUST): Akceptacja tworzy projekt
**Given** klient akceptuje kosztorys przez ważny token  
**When** system zapisuje akceptację  
**Then** status kosztorysu zmienia się na `zaakceptowany` i tworzony jest projekt `do_rozpoczecia`.

### B5 (MUST): Ponowna wysyłka unieważnia stary link
**Given** dla kosztorysu istnieje aktywny token  
**When** użytkownik wybiera „wyślij ponownie”  
**Then** poprzedni token zostaje unieważniony i tworzony jest nowy.

### B6 (SHOULD): Konfigurowalne szablony maili
**Given** admin edytuje szablony mailowe w ustawieniach  
**When** zapisze zmiany  
**Then** kolejne maile korzystają z nowego szablonu.

---

## EPIC C — Walidacja faktury PDF końcowej

### C1 (MUST): Blokada zamknięcia bez faktury PDF
**Given** projekt ma status `do_faktury`  
**When** użytkownik próbuje zmienić status na `zakończony` bez faktury PDF  
**Then** system blokuje zmianę statusu.

### C2 (MUST): Walidacja pliku PDF
**Given** użytkownik dodaje fakturę końcową  
**When** plik ma zły MIME, przekracza 5MB lub jest niepoprawny  
**Then** system odrzuca plik i zwraca błąd walidacji.

### C3 (MUST): Reguła dla wszystkich typów projektów
**Given** dowolny typ projektu  
**When** wykonywane jest przejście `do_faktury -> zakończony`  
**Then** obowiązuje ta sama walidacja PDF bez wyjątków.

---

## EPIC D — Kalendarz projektów + Google Calendar

### D1 (MUST): Widok kalendarza wszystkich projektów
**Given** użytkownik otwiera kalendarz projektów  
**When** widok ładuje dane  
**Then** widoczne są wszystkie projekty z datami trwania i deadline.

### D2 (MUST): 2 eventy na projekt
**Given** projekt ma daty start/end i deadline  
**When** system przygotowuje dane do Google  
**Then** tworzy event zakresu projektu oraz osobny event deadline z nazwą klienta i projektu.

### D3 (MUST): Sync CRON co 2h
**Given** w ERP zaszły zmiany projektu/deadline  
**When** uruchamia się zadanie CRON  
**Then** Google otrzymuje create/update/delete zgodnie ze stanem ERP.

### D4 (MUST): Usuwanie eventów po usunięciu w ERP
**Given** projekt lub deadline został usunięty w ERP  
**When** uruchomi się kolejny sync CRON  
**Then** odpowiadające eventy znikają z Google Calendar.

### D5 (SHOULD): Alert admin przy błędzie synchronizacji
**Given** API Google zwróci błąd synchronizacji  
**When** sync zakończy się błędem  
**Then** system zapisuje błąd i pokazuje/wysyła alert administracyjny.

---

## EPIC E — Dostawcy i faktury kosztowe (bez KSeF)

### E1 (MUST): Moduł dostawcy z polami opiekuna
**Given** admin/manager tworzy/edytuje dostawcę  
**When** zapisuje formularz  
**Then** system przechowuje dane dostawcy wraz z opiekunem, emailem i telefonem opiekuna.

### E2 (MUST): Status faktury wymagany przy każdej zmianie
**Given** faktura kosztowa istnieje  
**When** użytkownik edytuje fakturę  
**Then** status faktury musi być ustawiony i zgodny z workflow `zaimportowana -> weryfikacja -> zatwierdzona -> przypisana`.

### E3 (MUST): Relacje faktura/projekt/dostawca
**Given** istnieją projekt i dostawca  
**When** użytkownik przypina fakturę  
**Then** jedna faktura jest przypięta do jednego projektu, a projekt może mieć wiele faktur i wielu dostawców.

### E4 (MUST): Unikalność numeru faktury per dostawca
**Given** dostawca ma już fakturę o danym numerze  
**When** użytkownik próbuje dodać ten sam numer dla tego samego dostawcy  
**Then** system blokuje zapis jako duplikat.

### E5 (SHOULD): MVP audit log dla akcji krytycznych
**Given** użytkownik zmienia status faktury, przepina fakturę między projektem/dostawcą lub edytuje kwoty netto/VAT/brutto  
**When** zapisuje zmianę  
**Then** system zapisuje wpis audytowy: kto, kiedy, jakie pole i wartości przed/po.

---

## EPIC F — Integracja KSeF

### F1 (MUST): Import do statusu `zaimportowana`
**Given** aktywna konfiguracja integracji KSeF  
**When** system pobierze nową fakturę z KSeF  
**Then** tworzy rekord faktury kosztowej w statusie `zaimportowana`.

### F2 (MUST): Moderacja importu
**Given** faktura została zaimportowana z KSeF  
**When** admin/manager przeprowadzi weryfikację i przypisanie  
**Then** faktura przechodzi przez workflow i kończy jako `przypisana`.

---

## EPIC G — Panel klienta front (MVP)

### G1 (MUST): Widok budżetu i kosztów klienta
**Given** klient loguje się do frontu  
**When** otworzy szczegóły projektu  
**Then** widzi planowany budżet, jego zwiększenia przez pozycje przychodowe oraz koszty projektu per pozycja (bez kosztów wewnętrznych).

### G2 (SHOULD): Historia zmian budżetu
**Given** budżet projektu był modyfikowany  
**When** klient otwiera historię zmian  
**Then** system pokazuje chronologiczną historię zmian budżetu.

### G3 (MUST): Wątek projektowy
**Given** klient i zespół projektu mają dostęp do komunikacji  
**When** użytkownik dodaje wiadomość  
**Then** wpis pojawia się we wspólnym wątku projektu.

### G4 (MUST): Załączniki i wersjonowanie
**Given** użytkownik dodaje plik do wątku  
**When** plik jest typu pdf/jpg/png/zip i nie przekracza 30MB  
**Then** system zapisuje załącznik oraz jego wersję.

---

## 5) Definition of Done (zaakceptowane)

Element ERP_4.0 jest uznany za ukończony, gdy:
1. Spełnia kryteria Given/When/Then,
2. Przechodzi testy funkcjonalne i regresję obszaru,
3. Ma odpowiednie uprawnienia ról,
4. Ma obsłużone walidacje i ścieżki błędów,
5. Jest uwzględniony w dokumentacji operacyjnej wdrożenia.

