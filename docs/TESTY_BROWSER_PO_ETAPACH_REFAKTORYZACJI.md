# Testy przeglądarkowe po etapach refaktoryzacji ERP OMD

Ten dokument służy jako ręczna checklista smoke/regression po każdym etapie rozbijania dużych klas na mniejsze elementy. Celem jest potwierdzenie, że refaktoryzacja nie zmieniła zachowania systemu z perspektywy użytkownika.

## Etap 1 — kontener zależności i odchudzenie `ERP_OMD_Plugin`

Zakres techniczny: przeniesienie tworzenia repozytoriów, serwisów oraz punktów wejścia admin/front/REST z `ERP_OMD_Plugin` do `ERP_OMD_Container`.

### Admin WordPress
- [x] Zaloguj się jako administrator WordPress.
- [x] Wejdź w główne menu ERP OMD i sprawdź, czy dashboard admina ładuje się bez błędów PHP/white screen.
- [x] Otwórz kolejno zakładki: Pracownicy, Klienci, Projekty, Czas pracy, Kosztorysy, Raporty, Ustawienia.
- [x] Na stronie Ustawienia zapisz dowolną neutralną zmianę, np. bez zmiany wartości kliknij „Zapisz” na bieżącej zakładce i sprawdź komunikat sukcesu.

### FRONT
- [x] Otwórz `/erp-front/login/` i zaloguj się kontem pracownika.
- [x] Sprawdź, czy `/erp-front/worker/` pokazuje formularz wpisu czasu i listę wpisów.
- [x] Zaloguj się kontem managera i sprawdź `/erp-front/manager/` — lista projektów oraz kolejka akceptacji powinny się ładować.
- [x] Zaloguj się kontem klienta i sprawdź `/erp-front/client/` — widok projektów/wniosków klienta powinien być dostępny.

### REST API
- [x] W panelu admina otwórz narzędzia deweloperskie przeglądarki i przejdź do zakładki Network.
- [x] Odśwież stronę ERP OMD, która korzysta z REST API, i sprawdź, czy requesty `erp-omd/v1/*` nie zwracają 500.
- [x] Jako niezalogowany użytkownik spróbuj otworzyć endpoint REST ERP OMD w nowej karcie — powinien być zablokowany przez uprawnienia, nie przez błąd serwera.

### Integracje hooków
- [x] Utwórz lub zapisz projekt i sprawdź, czy zapis kończy się sukcesem.
- [x] Jeżeli Google Calendar jest skonfigurowany: sprawdź, czy zapis projektu nie powoduje błędu synchronizacji w UI/logach.
- [x] Wyloguj i zaloguj użytkownika ponownie; profil użytkownika powinien nadal działać, a logowanie nie powinno zwracać błędów.

## Etap 2 — moduły domenowe

Planowany zakres: wydzielenie modułów np. HR, Klienci/Projekty, Finanse, Kosztorysy, KSeF, Calendar. Po każdym wydzielonym module wykonaj tylko testy obszaru, który został ruszony, plus smoke z Etapu 1.

**Status:** ✅ etapy 2A–2F wdrożone i przetestowane w przeglądarce.

### Etap 2A — HR / pracownicy / role / wynagrodzenia ✅ gotowe i przetestowane

Zakres techniczny: wydzielenie `ERP_OMD_HR_Module`, który zarządza repozytoriami ról, pracowników, historii wynagrodzeń oraz serwisami pracowników i miesięcznych godzin.

- [x] Dodaj lub edytuj pracownika testowego.
- [x] Zmień role/uprawnienia pracownika i zapisz.
- [x] Dodaj historię wynagrodzenia lub miesięczne godziny, jeśli moduł był modyfikowany.
- [x] Sprawdź, czy pracownik może zalogować się do FRONT zgodnie z przypisanymi uprawnieniami.

### Etap 2B — Klienci / projekty / wnioski projektowe ✅ gotowe i przetestowane

Zakres techniczny: wydzielenie `ERP_OMD_Client_Project_Module`, który zarządza repozytoriami klientów, stawek klientów, projektów, wniosków projektowych, notatek projektu, stawek projektowych, załączników oraz serwisami klient/projekt i wniosków projektowych.

- [x] Dodaj klienta testowego i zapisz dane kontaktowe.
- [x] Dodaj projekt dla klienta oraz zmień jego status.
- [x] Utwórz wniosek projektowy z FRONT jako klient/pracownik.
- [x] Jako manager/admin zaakceptuj lub skonwertuj wniosek do projektu.

### Etap 2C — Finanse / raporty / alerty ✅ gotowe i przetestowane

Zakres techniczny: wydzielenie `ERP_OMD_Finance_Module`, który zarządza repozytoriami kosztów, przychodów i finansów projektu oraz serwisami finansów projektu, raportów i alertów.

- [x] Dodaj koszt projektu i przychód projektu.
- [x] Sprawdź, czy podsumowanie finansowe projektu przelicza marżę/budżet.
- [x] Otwórz raporty i sprawdź, czy filtry miesiąca/klienta/projektu działają.
- [x] Sprawdź, czy alerty niskiej marży lub braków danych nadal pojawiają się w oczekiwanych miejscach.

### Etap 2D — Kosztorysy ✅ gotowe i przetestowane

Zakres techniczny: wydzielenie `ERP_OMD_Estimate_Module`, który zarządza repozytoriami kosztorysów, pozycjami, audytem kosztorysów oraz `ERP_OMD_Estimate_Service`.

- [x] Utwórz kosztorys z pozycjami.
- [x] Wyślij/udostępnij kosztorys klientowi, jeśli środowisko na to pozwala.
- [x] Jako klient zaakceptuj lub odrzuć kosztorys z komentarzem.
- [x] Sprawdź, czy status kosztorysu i dane akceptacji są widoczne w adminie.

### Etap 2E — KSeF / faktury kosztowe ✅ gotowe i przetestowane

Zakres techniczny: wydzielenie `ERP_OMD_KSeF_Module`, który zarządza dostawcami, repozytoriami faktur kosztowych, workflow faktur kosztowych oraz `ERP_OMD_KSeF_Import_Service`.

- [x] Zaimportuj testowy XML sprzedażowy lub kosztowy.
- [x] Sprawdź kolejkę moderacji KSeF.
- [x] Podepnij fakturę kosztową do projektu.
- [x] Sprawdź, czy koszt projektu i relacja faktury są widoczne po odświeżeniu strony.

### Etap 2F — Calendar / Google Calendar ✅ gotowe i przetestowane

Zakres techniczny: wydzielenie `ERP_OMD_Calendar_Module`, który zarządza repozytorium synchronizacji projektów z kalendarzem oraz `ERP_OMD_Google_Calendar_Sync_Service`.

- [x] Zapisz projekt z datą startu/końca/deadline.
- [x] Jeżeli integracja jest skonfigurowana: sprawdź, czy event pojawia się lub aktualizuje w Google Calendar.
- [x] Zmień projekt na archiwalny i sprawdź, czy synchronizacja usuwa/oznacza eventy zgodnie z dotychczasowym zachowaniem.

## Etap 3 — kontrolery REST per domena

Zakres techniczny: rozbicie dużego REST API na kontrolery domenowe.

### Etap 3A — fundament kontrolerów REST / HR

Zakres techniczny: dodanie bazowego `ERP_OMD_REST_Controller` oraz pierwszego kontrolera domenowego `ERP_OMD_REST_HR_Controller`, który rejestruje endpointy pracowników, wynagrodzeń, miesięcznych godzin i ACL bez zmiany publicznych URL-i.

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
