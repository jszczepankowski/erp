# Testy przeglądarkowe po etapach refaktoryzacji ERP OMD

Ten dokument służy jako ręczna checklista smoke/regression po każdym etapie rozbijania dużych klas na mniejsze elementy. Celem jest potwierdzenie, że refaktoryzacja nie zmieniła zachowania systemu z perspektywy użytkownika.

## Etap 1 — kontener zależności i odchudzenie `ERP_OMD_Plugin`

Zakres techniczny: przeniesienie tworzenia repozytoriów, serwisów oraz punktów wejścia admin/front/REST z `ERP_OMD_Plugin` do `ERP_OMD_Container`.

### Admin WordPress
- [ ] Zaloguj się jako administrator WordPress.
- [ ] Wejdź w główne menu ERP OMD i sprawdź, czy dashboard admina ładuje się bez błędów PHP/white screen.
- [ ] Otwórz kolejno zakładki: Pracownicy, Klienci, Projekty, Czas pracy, Kosztorysy, Raporty, Ustawienia.
- [ ] Na stronie Ustawienia zapisz dowolną neutralną zmianę, np. bez zmiany wartości kliknij „Zapisz” na bieżącej zakładce i sprawdź komunikat sukcesu.

### FRONT
- [ ] Otwórz `/erp-front/login/` i zaloguj się kontem pracownika.
- [ ] Sprawdź, czy `/erp-front/worker/` pokazuje formularz wpisu czasu i listę wpisów.
- [ ] Zaloguj się kontem managera i sprawdź `/erp-front/manager/` — lista projektów oraz kolejka akceptacji powinny się ładować.
- [ ] Zaloguj się kontem klienta i sprawdź `/erp-front/client/` — widok projektów/wniosków klienta powinien być dostępny.

### REST API
- [ ] W panelu admina otwórz narzędzia deweloperskie przeglądarki i przejdź do zakładki Network.
- [ ] Odśwież stronę ERP OMD, która korzysta z REST API, i sprawdź, czy requesty `erp-omd/v1/*` nie zwracają 500.
- [ ] Jako niezalogowany użytkownik spróbuj otworzyć endpoint REST ERP OMD w nowej karcie — powinien być zablokowany przez uprawnienia, nie przez błąd serwera.

### Integracje hooków
- [ ] Utwórz lub zapisz projekt i sprawdź, czy zapis kończy się sukcesem.
- [ ] Jeżeli Google Calendar jest skonfigurowany: sprawdź, czy zapis projektu nie powoduje błędu synchronizacji w UI/logach.
- [ ] Wyloguj i zaloguj użytkownika ponownie; profil użytkownika powinien nadal działać, a logowanie nie powinno zwracać błędów.

## Etap 2 — moduły domenowe

Planowany zakres: wydzielenie modułów np. HR, Klienci/Projekty, Finanse, Kosztorysy, KSeF, Calendar. Po każdym wydzielonym module wykonaj tylko testy obszaru, który został ruszony, plus smoke z Etapu 1.

### Etap 2A — HR / pracownicy / role / wynagrodzenia

Zakres techniczny: wydzielenie `ERP_OMD_HR_Module`, który zarządza repozytoriami ról, pracowników, historii wynagrodzeń oraz serwisami pracowników i miesięcznych godzin.

- [ ] Dodaj lub edytuj pracownika testowego.
- [ ] Zmień role/uprawnienia pracownika i zapisz.
- [ ] Dodaj historię wynagrodzenia lub miesięczne godziny, jeśli moduł był modyfikowany.
- [ ] Sprawdź, czy pracownik może zalogować się do FRONT zgodnie z przypisanymi uprawnieniami.

### Etap 2B — Klienci / projekty / wnioski projektowe

Zakres techniczny: wydzielenie `ERP_OMD_Client_Project_Module`, który zarządza repozytoriami klientów, stawek klientów, projektów, wniosków projektowych, notatek projektu, stawek projektowych, załączników oraz serwisami klient/projekt i wniosków projektowych.

- [ ] Dodaj klienta testowego i zapisz dane kontaktowe.
- [ ] Dodaj projekt dla klienta oraz zmień jego status.
- [ ] Utwórz wniosek projektowy z FRONT jako klient/pracownik.
- [ ] Jako manager/admin zaakceptuj lub skonwertuj wniosek do projektu.

### Finanse / raporty / alerty
- [ ] Dodaj koszt projektu i przychód projektu.
- [ ] Sprawdź, czy podsumowanie finansowe projektu przelicza marżę/budżet.
- [ ] Otwórz raporty i sprawdź, czy filtry miesiąca/klienta/projektu działają.
- [ ] Sprawdź, czy alerty niskiej marży lub braków danych nadal pojawiają się w oczekiwanych miejscach.

### Kosztorysy
- [ ] Utwórz kosztorys z pozycjami.
- [ ] Wyślij/udostępnij kosztorys klientowi, jeśli środowisko na to pozwala.
- [ ] Jako klient zaakceptuj lub odrzuć kosztorys z komentarzem.
- [ ] Sprawdź, czy status kosztorysu i dane akceptacji są widoczne w adminie.

### KSeF / faktury kosztowe
- [ ] Zaimportuj testowy XML sprzedażowy lub kosztowy.
- [ ] Sprawdź kolejkę moderacji KSeF.
- [ ] Podepnij fakturę kosztową do projektu.
- [ ] Sprawdź, czy koszt projektu i relacja faktury są widoczne po odświeżeniu strony.

### Calendar / Google Calendar
- [ ] Zapisz projekt z datą startu/końca/deadline.
- [ ] Jeżeli integracja jest skonfigurowana: sprawdź, czy event pojawia się lub aktualizuje w Google Calendar.
- [ ] Zmień projekt na archiwalny i sprawdź, czy synchronizacja usuwa/oznacza eventy zgodnie z dotychczasowym zachowaniem.

## Etap 3 — kontrolery REST per domena

Zakres techniczny: rozbicie dużego REST API na kontrolery domenowe.

- [ ] W adminie wykonaj operacje CRUD dla roli, pracownika, klienta, projektu i wpisu czasu.
- [ ] W Network sprawdź, czy endpointy `erp-omd/v1/*` zwracają statusy 200/201/204 dla poprawnych akcji.
- [ ] Spróbuj wykonać akcję użytkownikiem bez uprawnień — UI powinien pokazać odmowę, a REST powinien zwrócić 401/403.
- [ ] Sprawdź paginację/listy: pracownicy, klienci, projekty, wpisy czasu, raporty.
- [ ] Sprawdź eksport CSV/raportów, jeśli dotyczył ruszanego kontrolera.

## Etap 4 — kontrolery admin akcji i widoków

Zakres techniczny: rozbicie `class-admin-runtime.php` na mniejsze kontrolery ustawień, projektów, KSeF, backupu itd.

- [ ] Dla każdej ruszanej strony admina otwórz widok, zapisz formularz i sprawdź komunikat sukcesu/błędu.
- [ ] Sprawdź akcje masowe na listach, które były refaktoryzowane.
- [ ] Sprawdź nonce: odśwież stronę, wyślij formularz poprawnie, a potem spróbuj ponowić starą kartę po wylogowaniu — system powinien blokować akcję.
- [ ] Jeżeli ruszany był backup: wykonaj ręczny backup i sprawdź komunikat sukcesu; restore testuj wyłącznie na środowisku testowym.
- [ ] Jeżeli ruszane były ustawienia: zapisz każdą zakładkę ustawień i sprawdź po odświeżeniu, czy wartości się zachowały.

## Stały smoke test po każdym etapie

- [ ] Brak białego ekranu po aktywacji wtyczki.
- [ ] Brak błędów 500 w Network dla stron ERP OMD.
- [ ] Admin może otworzyć dashboard ERP OMD.
- [ ] Pracownik, manager i klient mogą zalogować się do właściwych paneli FRONT.
- [ ] Wylogowanie z FRONT działa i wraca do `/erp-front/login/`.
- [ ] Podstawowy zapis formularza admina oraz formularza FRONT działa po odświeżeniu strony.
