# ERP OMD — Pełna dokumentacja techniczna systemu

Data: 2026-05-13
Status: dokument bazowy (living document)

---

## 1. Cel systemu
ERP OMD to system operacyjno-rozliczeniowy do obsługi:
- klientów i pracowników,
- projektów i ich lifecycle,
- czasu pracy,
- kosztorysów i decyzji klienta,
- kosztów/przychodów,
- raportowania i audytu,
- modułów pomocniczych (wnioski projektowe, załączniki, alerty, kalendarz).

System działa jako plugin WordPress (`erp-omd`) i wykorzystuje role/capabilities WP oraz własne repozytoria danych.

---

## 2. Architektura (wysoki poziom)

### 2.1 Warstwy
1. **UI (Admin + Front)**
   - `erp-omd/templates/admin/*`
   - `erp-omd/templates/front/*`
2. **Runtime / Kontrolery akcji**
   - `erp-omd/includes/class-admin-runtime.php`
   - `erp-omd/includes/class-frontend-runtime.php`
   - `erp-omd/includes/class-rest-api.php`
3. **Logika biznesowa (Services)**
   - `erp-omd/includes/services/*`
4. **Dostęp do danych (Repositories)**
   - `erp-omd/includes/repositories/*`
5. **Infrastruktura / bootstrap**
   - installer, autoloader, capabilities, cron, backup

### 2.2 Wzorzec działania
- UI wysyła formularz (`POST`) lub request REST.
- Runtime waliduje nonce/capabilities.
- Service egzekwuje reguły biznesowe.
- Repository wykonuje operacje CRUD.
- Runtime zwraca redirect/notice lub JSON.

---

## 3. Moduły funkcjonalne

## 3.1 Użytkownicy, role, uprawnienia
- Oparte o role i capability WordPress + role projektowe.
- Dostęp do sekcji i akcji jest ograniczany capability checkami.
- Kierunek rozwoju: granularne ACL per użytkownik (planowane).

Główne obiekty:
- użytkownik WP,
- pracownik (`employees`),
- role projektowe (`roles`),
- mapowanie user↔employee↔roles.

## 3.2 Klienci
- CRUD klientów,
- status aktywności,
- dane kontaktowe i adresowe,
- powiązania z projektami/kosztorysami.

## 3.3 Projekty
- CRUD projektu, managerowie, status lifecycle,
- modele rozliczeń (godzinowy/ryczałt/retainer/hybryda),
- budżety, daty, deadline, brief,
- widok szczegółów 360 i metryki finansowe.

Dodatkowo:
- akcje masowe (w tym merge),
- merge preview + merge execute,
- markowanie deadline jako wykonany.

## 3.4 Czas pracy
- wpisy czasu pracowników,
- statusy (submitted/approved/rejected),
- akceptacja manager/admin,
- korekty i powiązanie z projektami.

## 3.5 Kosztorysy
- tworzenie/edycja kosztorysu,
- pozycje kosztorysu (qty, cena, koszt wewnętrzny, marża),
- statusy: `wstepny`, `do_akceptacji`, `odrzucony`, `zaakceptowany`,
- eksporty (wariant klient/agencja),
- wysyłka linku decyzji do klienta,
- akceptacja/odrzucenie przez klienta,
- duplikacja kosztorysu (powielanie pozycji).

Reguły kluczowe:
- odrzucenie wymaga komentarza,
- zaakceptowany kosztorys jest częściowo lockowany,
- marża może być wyliczana informacyjnie z ceny i kosztu.

## 3.6 Wnioski projektowe
- tworzenie wniosków przez front,
- moderacja i konwersja do projektu,
- ścieżka akceptacji/odrzucenia.

## 3.7 Finanse projektu
- koszty, przychody, budżety, korekty,
- zestawienia marży i rentowności,
- audyt zmian wartości finansowych.

## 3.8 Raporty
- raporty projektowe/operacyjne,
- filtry czasu/statusu/zakresu,
- eksport CSV,
- baseline wydajności raportowania.

## 3.9 Załączniki i dokumenty
- upload i powiązania z obiektami,
- operacje usuwania i walidacji uprawnień.

## 3.10 Integracje i narzędzia operacyjne
- Google Calendar sync,
- KSeF (obsługa importu/kolejek/wiązania faktur),
- backup/restore,
- cron i zadania okresowe.

---

## 4. Model domenowy (uproszczony)

Główne encje i relacje:
- **Client** 1..* **Project**
- **Client** 1..* **Estimate**
- **Estimate** 1..* **EstimateItem**
- **Project** 1..* **TimeEntry**
- **Project** 1..* **ProjectCost**
- **Project** 1..* **ProjectRevenue**
- **Project** 1..* **Attachment**
- **User/Employee** 1..* **TimeEntry**

Powiązanie krytyczne:
- `Estimate (zaakceptowany)` -> może inicjować/powiązać `Project`.

---

## 5. Przepływy biznesowe (end-to-end)

## 5.1 Kosztorys -> decyzja klienta
1. Manager/Admin tworzy kosztorys i pozycje.
2. Ustawia status `do_akceptacji`.
3. System wysyła link decyzji klienta.
4. Klient:
   - akceptuje (opcjonalne metadane dostawy/faktury), albo
   - odrzuca (komentarz wymagany).
5. System aktualizuje status i zapisuje ślad decyzji.

## 5.2 Projekt -> czas pracy -> rozliczenie
1. Projekt w statusie aktywnym.
2. Pracownicy logują czas.
3. Manager/admin akceptuje wpisy.
4. Raporty i finanse aktualizowane wg danych operacyjnych.

## 5.3 Merge projektów
1. Zaznaczenie min. 2 projektów w akcjach masowych.
2. Wejście w panel merge preview.
3. Walidacja źródeł i metryk.
4. Potwierdzenie merge i utworzenie nowego kontekstu docelowego.

---

## 6. Słownik pojęć

- **Kosztorys** — oferta kosztowa dla klienta.
- **Pozycja kosztorysu** — linia oferty (ilość/cena/koszt/marża).
- **Marża** — relacja ceny sprzedaży do kosztu wewnętrznego.
- **Projekt** — realizacja usług/produktu powiązana z klientem.
- **Wpis czasu** — jednostka ewidencji pracy pracownika.
- **Lifecycle projektu** — status operacyjny projektu.
- **Merge projektów** — konsolidacja wielu projektów do jednego docelowego.
- **UAT** — testy akceptacyjne użytkownika.
- **DoE / Evidence** — dowody wykonania testów i gotowości release.
- **KSeF** — integracja z Krajowym Systemem e-Faktur.

---

## 7. Zależności techniczne

## 7.1 Platforma
- WordPress (plugin runtime),
- PHP + MySQL/MariaDB,
- mechanizmy nonce/capabilities/cron/options.

## 7.2 Zależności wewnętrzne
- runtime zależy od services i repositories,
- templates zależą od runtime/context,
- raporty zależą od poprawności danych wejściowych z czasu, kosztów i przychodów.

## 7.3 Krytyczne kontrakty
- spójne nazwy statusów i mapowanie etykiet,
- niezmienność danych zaakceptowanego kosztorysu (w określonym zakresie),
- wymagane pola bezpieczeństwa (nonce/capability).

---

## 8. Bezpieczeństwo i zgodność

- Każda akcja mutująca: nonce + capability check.
- Walidacja i sanityzacja danych wejściowych.
- Ograniczenie dostępu do danych klienta/projektu wg roli.
- Audit trail dla operacji finansowych i korekt.
- Backup/restore i procedura rollback.

---

## 9. Wydajność i niezawodność

## 9.1 Obszary ryzyka
- ciężkie raporty i agregacje,
- listy bez paginacji,
- operacje masowe (merge/importy).

## 9.2 Zalecenia
- indeksy DB dla kluczy filtrujących,
- paginacja i limity,
- cache wyników raportowych,
- profilowanie zapytań SQL i endpointów.

---

## 10. Testy i jakość

## 10.1 Poziomy testów
- testy domenowe service/repository,
- testy REST/API i walidacji,
- smoke testy admin/front po cleanup batchach,
- UAT dla kluczowych flow (kosztorys/projekt/raporty).

## 10.2 Standard Evidence
- każdy PR/release notes zawiera dowody testów (komendy + wyniki),
- brak deklaracji bez artefaktu,
- preferowane dowody: log terminala / CI job link / raport testowy.

---

## 11. Operacje i utrzymanie

- Procedury backup/restore,
- runbook dla błędów synchronizacji,
- cykliczny audit cleanupu docs/skryptów,
- kontrola regresji po batch cleanup.

---

## 12. Roadmapa rozwoju (skrót)

1. ACL per użytkownik (granularne uprawnienia),
2. zapisy async bez reload,
3. mini-taski projektowe,
4. dalsza optymalizacja wydajności,
5. iteracyjne porządki repo i dokumentacji.

---

## 13. Załączniki / dokumenty referencyjne

- `docs/README.md`
- `docs/ERP_OMD_SYSTEM_OVERVIEW.md`
- `docs/RELEASE_NOTES_TEST_EVIDENCE_POLICY.md`
- `docs/REPO_CLEANUP_INVENTORY.md`
- `ERP_v4.2`

