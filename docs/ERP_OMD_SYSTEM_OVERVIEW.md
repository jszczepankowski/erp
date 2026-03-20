# ERP OMD — opis systemu po wszystkich sprintach i propozycje rozwoju

## 1. Czym jest ERP OMD

ERP OMD to plugin WordPress pełniący rolę lekkiego systemu ERP/operations dla agencji lub zespołu usługowego. Łączy w jednym miejscu:

- warstwę organizacyjną zespołu,
- prosty CRM operacyjny klientów,
- delivery projektowe,
- ewidencję czasu pracy,
- kosztorysowanie,
- raportowanie zarządcze,
- alertowanie o ryzykach,
- oraz warstwę REST API do integracji i automatyzacji.

System jest projektowany iteracyjnie. Każdy sprint rozszerza wcześniejsze moduły bez utraty kompatybilności funkcjonalnej. W praktyce daje to jeden spójny backoffice do prowadzenia pracy operacyjnej od momentu konfiguracji ról i pracowników, przez klienta i projekt, aż po kontrolę czasu, rentowności oraz statusów biznesowych.

## 2. Główna architektura biznesowa systemu

System można podzielić na osiem warstw funkcjonalnych:

1. **Administracja i uprawnienia** — użytkownicy WordPress, role ERP, capability, ustawienia i lifecycle danych.
2. **Zespół i kompetencje** — role projektowe, pracownicy, role przypisane pracownikom, historia wynagrodzeń.
3. **Klienci i relacje handlowe** — kartoteki klientów, dane kontaktowe, opiekun klienta, stawki klienta.
4. **Projekty i delivery** — projekty, statusy, manager projektu, brief, stawki projektowe, uwagi klienta.
5. **Kosztorysy** — kosztorysy, pozycje kosztorysowe, akceptacja i powiązanie z projektem.
6. **Time tracking i workflow wykonawczy** — wpisy czasu, statusy wpisów, approval flow, snapshoty stawek i kosztów.
7. **Finanse i raportowanie** — koszty projektowe, agregaty finansowe, raporty, eksporty, kalendarz miesięczny.
8. **Monitoring i utrzymanie** — alerty, załączniki, meta/system endpoints, uninstall i kontrole operacyjne.

## 3. Funkcjonalności po sprintach

### Sprint 1 — fundament systemu kadrowego i technicznego

Sprint 1 zbudował bazę całego systemu:

- rejestr ról projektowych,
- rejestr pracowników powiązanych z użytkownikami WordPress,
- możliwość przypisania roli domyślnej i wielu ról dodatkowych do pracownika,
- salary history z okresami obowiązywania,
- podstawowy REST API dla struktur bazowych,
- uninstall i mechanizmy bezpiecznego usuwania danych.

Efekt biznesowy: system zna strukturę zespołu, odpowiedzialności oraz koszt pracy w czasie.

### Sprint 2 — klienci i projekty

Sprint 2 dodał pełną podstawę operacyjną pracy z klientem:

- CRUD klientów,
- stawki klienta per rola,
- podstawowy CRUD projektów,
- przypisanie projektu do istniejącego klienta,
- historia uwag klienta w projekcie,
- ekran administracyjny dla klientów i projektów,
- endpointy REST dla klientów, stawek klienta i projektów.

Efekt biznesowy: można przejść od samego zespołu do realnej obsługi klientów i przypisanych im projektów.

### Sprint 3 — time tracking i logika stawek

Sprint 3 rozwinął warstwę delivery i rozliczeń:

- stawki projektowe jako override dla stawek klienta,
- rejestracja czasu pracy z przypisaniem do projektu, roli i pracownika,
- snapshoty stawki sprzedażowej i kosztu we wpisie czasu,
- approval flow wpisów czasu (`submitted`, `approved`, `rejected`),
- logika rozstrzygania stawki z priorytetem project rate nad client rate.

Efekt biznesowy: system potrafi już nie tylko opisać projekt, ale również mierzyć faktycznie zrealizowaną pracę i jej wartość.

### Sprint 4 — finanse projektu

Sprint 4 dołożył kontrolę finansową projektów:

- koszty projektowe niezależne od czasu pracy,
- agregację przychodów, kosztów i marży,
- materializację finansów projektu,
- ekran administracyjny wspierający przegląd wyników projektowych.

Efekt biznesowy: rentowność projektu nie opiera się wyłącznie na czasie pracy, ale uwzględnia również pozostałe koszty delivery.

### Sprint 5 — kosztorysy

Sprint 5 rozszerzył system o warstwę presales i planowania:

- CRUD kosztorysów,
- pozycje kosztorysowe z ilością, ceną, kosztem wewnętrznym i komentarzem,
- wyliczanie wartości netto, VAT, brutto i kosztu wewnętrznego,
- akceptację kosztorysu,
- możliwość utworzenia lub powiązania projektu na podstawie zaakceptowanego kosztorysu,
- eksport danych kosztorysu dla klienta i agencji.

Efekt biznesowy: handlowiec i delivery mogą pracować na tym samym źródle danych od oferty do realizacji.

### Sprint 6 — raportowanie i widok kalendarzowy

Sprint 6 dodał centrum raportowe:

- raport projektów,
- raport klientów,
- raport pozycji do faktury,
- raport miesięczny,
- kalendarz miesięczny dla wpisów czasu,
- eksporty CSV raportów.

Efekt biznesowy: dane z delivery są gotowe do analizy zarządczej i rozliczeniowej bez konieczności ręcznego składania raportów w arkuszach.

### Sprint 7 — alerty, załączniki i lifecycle

Sprint 7 zajął się kontrolą operacyjną i utrzymaniem danych:

- alerty systemowe dla niskiej marży, przekroczenia budżetu i braków w stawkach,
- centrum alertów,
- załączniki powiązane z projektami i kosztorysami,
- soft delete / aktywacja-dezaktywacja klientów, projektów i pracowników,
- dopracowanie cyklu życia rekordów.

Efekt biznesowy: operatorzy systemu szybciej wykrywają ryzyka i mogą pracować na uporządkowanym lifecycle danych.

### Sprint 8 — hardening, meta i status systemu

Sprint 8 domknął warstwę techniczną oraz administracyjną:

- finalizacja REST API dla alertów, załączników, meta i statusu systemu,
- poprawki hardeningowe,
- dodatkowe sanity checki,
- dopracowanie paneli administracyjnych i ustawień.

Efekt biznesowy: system jest łatwiejszy do utrzymania, testowania i integracji z zewnętrznymi procesami.

## 4. Obecne moduły systemu z perspektywy użytkownika

### 4.1 Dashboard

Dashboard pokazuje skrócone informacje o zakresie systemu i podstawowe metryki operacyjne: liczbę pracowników, ról, klientów, projektów i aktywnych alertów.

### 4.2 Role

Administrator może definiować role projektowe wykorzystywane później w:

- przypisaniach kompetencyjnych pracowników,
- stawkach klienta,
- stawkach projektowych,
- wpisach czasu,
- raportach i snapshotach rozliczeniowych.

### 4.3 Pracownicy

Moduł pracowników pozwala:

- powiązać rekord ERP z kontem WordPress,
- wskazać rolę domyślną,
- przypisać wiele ról projektowych,
- ustawić typ konta,
- aktywować/dezaktywować pracownika,
- prowadzić salary history z zakresem obowiązywania.

To jest fundament dla dalszego liczenia kosztów i autoryzacji działań.

### 4.4 Klienci

Karta klienta zawiera:

- nazwę i firmę,
- identyfikatory oraz dane kontaktowe,
- adres,
- opiekuna klienta,
- status aktywności.

Dodatkowo klient ma własne stawki per rola, które mogą być wykorzystane do wyceny i rozliczenia czasu pracy.

### 4.5 Projekty

Projekt obejmuje:

- powiązanie z klientem,
- nazwę,
- typ rozliczenia,
- status,
- daty,
- managera,
- budżet lub retainer,
- brief,
- uwagi klienta,
- stawki projektowe,
- koszty projektowe,
- dane finansowe i alerty.

Projekt jest główną jednostką delivery i raportowania.

### 4.6 Kosztorysy

Kosztorysy pozwalają przygotować ofertę dla klienta i kontrolować jej opłacalność jeszcze przed startem projektu. System przechowuje:

- dane klienta,
- nazwę kosztorysu,
- status kosztorysu,
- listę pozycji kosztorysowych,
- wartości handlowe i kosztowe,
- informację o akceptacji.

### 4.7 Czas pracy

Time tracking umożliwia:

- dodanie wpisu czasu do projektu,
- przypisanie roli i wykonawcy,
- wskazanie daty i opisu,
- przejście przez approval flow,
- zapis snapshotów stawki i kosztu.

Dzięki snapshotom raporty nie są zniekształcane późniejszymi zmianami konfiguracji stawek.

### 4.8 Raporty

System raportowy agreguje dane do czterech perspektyw:

- projekty,
- klienci,
- do faktury,
- miesięcznie.

Raporty mogą być filtrowane i eksportowane. Kalendarz miesięczny pomaga analizować obciążenie i status wpisów czasu w układzie dni.

### 4.9 Alerty

Alerty są automatycznym mechanizmem wczesnego ostrzegania. Obecnie obejmują co najmniej:

- przekroczenie budżetu,
- niską marżę,
- brak skonfigurowanych stawek.

To narzędzie wspiera managerów projektów i właścicieli operacji w szybszym reagowaniu.

### 4.10 Załączniki

Załączniki pozwalają przypinać pliki do:

- projektów,
- kosztorysów.

To upraszcza przechowywanie dokumentów roboczych, briefów, PDF-ów ofertowych lub materiałów referencyjnych.

### 4.11 REST API i status systemu

REST API obejmuje obszary:

- role,
- pracowników,
- klientów,
- stawki klienta,
- projekty,
- kosztorysy,
- pozycje kosztorysowe,
- wpisy czasu,
- koszty projektowe,
- raporty,
- alerty,
- załączniki,
- meta,
- system.

Endpointy meta i system porządkują integracje, bo zwracają konfiguracje referencyjne oraz stan środowiska.

## 5. Główne procesy end-to-end, które system już wspiera

### Proces 1 — onboarding organizacji

1. Administrator definiuje role projektowe.
2. Dodaje pracowników i przypisuje im role.
3. Uzupełnia salary history.

Rezultat: organizacja ma gotową strukturę do kalkulacji kosztów i pracy operacyjnej.

### Proces 2 — uruchomienie klienta

1. Administrator zakłada klienta.
2. Wskazuje opiekuna klienta.
3. Uzupełnia dane kontaktowe i adresowe.
4. Konfiguruje stawki klienta per rola.

Rezultat: klient jest gotowy do kosztorysowania i projektów.

### Proces 3 — przejście od oferty do delivery

1. Tworzony jest kosztorys.
2. Dodawane są pozycje kosztorysowe.
3. Kosztorys zostaje zaakceptowany.
4. Na jego podstawie powstaje projekt lub następuje jego powiązanie z projektem.

Rezultat: oferta i realizacja pozostają spięte jednym modelem danych.

### Proces 4 — realizacja projektu

1. Projekt otrzymuje managera, status i model rozliczenia.
2. Dodawane są stawki projektowe i koszty dodatkowe.
3. Zespół raportuje czas pracy.
4. Wpisy przechodzą approval flow.
5. Finanse projektu aktualizują przychody, koszty i marżę.

Rezultat: projekt ma aktualny, policzalny obraz delivery i rentowności.

### Proces 5 — kontrola operacyjna i raportowanie

1. Managerzy śledzą alerty.
2. Raporty pokazują wyniki projektów i klientów.
3. Dane są eksportowane do dalszego rozliczenia lub analizy.

Rezultat: organizacja ma bieżący monitoring operacyjny.

## 6. Propozycje usprawnień

Poniższe propozycje są podzielone na trzy grupy: logika biznesowa, nowe funkcje i UX.

### 6.1 Usprawnienia logiki biznesowej

1. **Wersjonowanie stawek i kosztów w czasie**  
   Obecnie snapshoty chronią wpisy czasu, ale przydałaby się pełna wersjonowalność stawek klienta i projektu z zakresem obowiązywania.

2. **Twardsze reguły lifecycle projektów**  
   Warto wymusić warunki przejścia statusów, np. projekt nie może przejść do `do_faktury`, jeśli ma niezatwierdzone wpisy czasu albo aktywne alerty krytyczne.

3. **Polityka walidacji budżetu i retainera**  
   Dobrze rozdzielić walidacje dla `fixed_price`, `time_material` i `retainer`, tak aby każda ścieżka miała dedykowane wymagane pola.

4. **Lepsze reguły dla kosztorysów zaakceptowanych**  
   Po akceptacji można mocniej blokować edycję kluczowych pól i utrzymywać jawny audit trail zmian administracyjnych.

5. **Automatyczne progi alertów per projekt lub klient**  
   Zamiast jednego globalnego progu marży warto dopuścić override na poziomie klienta lub projektu.

6. **Normalizacja danych adresowych i kontaktowych**  
   Można rozważyć rozdzielenie pól adresu na bardziej strukturalne oraz dodanie walidacji numerów telefonów i NIP.

### 6.2 Dodatkowe funkcje

1. **Fakturowanie i status rozliczenia**  
   Naturalnym kolejnym krokiem byłby moduł faktur lub przynajmniej rejestr dokumentów sprzedażowych i płatności.

2. **Planowanie zasobów i capacity planning**  
   Kalendarz można rozwinąć o planowane obciążenie pracowników, dostępność i forecast wykorzystania.

3. **Powiadomienia e-mail / in-app**  
   Alerty, akceptacje wpisów czasu i zmiany statusów projektów mogłyby wysyłać automatyczne notyfikacje.

4. **Portal klienta**  
   Klient mógłby otrzymać ograniczony dostęp do kosztorysów, statusów projektu, załączników i akceptacji.

5. **Workflow akceptacji wieloetapowej**  
   Dla większych organizacji przydatne byłoby np. dwuetapowe zatwierdzanie czasu lub kosztów.

6. **Import/eksport danych referencyjnych**  
   Przydałby się import klientów, pracowników i stawek z CSV lub zewnętrznego systemu.

7. **Automatyzacje REST / webhooki**  
   Webhooki po utworzeniu kosztorysu, akceptacji, zmianie statusu projektu czy pojawieniu się alertu ułatwiłyby integracje.

### 6.3 Usprawnienia UX i panelu administracyjnego

1. **Wspólne filtry i zapisane widoki**  
   Użytkownik powinien móc zapisać własne konfiguracje filtrów dla raportów, projektów i czasu pracy.

2. **Lepsze wyszukiwanie i bulk actions**  
   Listy klientów, projektów i kosztorysów warto wzbogacić o wyszukiwarkę pełnotekstową i akcje masowe.

3. **Sekcjonowanie długich formularzy**  
   Formularze klientów, projektów i kosztorysów można podzielić na sekcje lub zakładki: podstawy, kontakt, finanse, lifecycle.

4. **Widok szczegółowy klienta i projektu**  
   Obecnie duża część pracy odbywa się formularzowo. Dedykowany widok 360° poprawiłby czytelność danych i historii zmian.

5. **Lepsze stany puste i podpowiedzi kontekstowe**  
   UI może wyraźniej tłumaczyć, co zrobić dalej, np. kiedy klient nie ma stawek, projekt nie ma managera albo kosztorys nie ma pozycji.

6. **Kolorystyczne oznaczenia ryzyka i statusów**  
   Raporty, alerty i listy projektów byłyby czytelniejsze z badge’ami statusów i poziomów ryzyka.

7. **Skróty na dashboardzie**  
   Dashboard może zyskać szybkie akcje typu „Dodaj klienta”, „Dodaj projekt”, „Dodaj wpis czasu”, „Przejdź do raportu miesięcznego”.

## 7. Rekomendowana kolejność dalszego rozwoju

Jeżeli system ma przejść z fazy operacyjnego MVP do wersji produkcyjnie skalowalnej, rekomendowana kolejność jest następująca:

1. **Usprawnienia lifecycle i walidacji biznesowej** — bo wpływają na jakość danych.
2. **Powiadomienia i automatyzacje** — bo skracają czas reakcji operacyjnej.
3. **Fakturowanie lub integracja billingowa** — bo domyka proces finansowy.
4. **Capacity planning i forecast** — bo zwiększa wartość zarządczą systemu.
5. **Portal klienta i bardziej zaawansowany UX** — bo poprawia komunikację zewnętrzną i adopcję.

## 8. Podsumowanie

Po wszystkich sprintach ERP OMD jest spójnym systemem operacyjnym do prowadzenia klientów, projektów, czasu pracy, kosztorysów, finansów i monitoringu ryzyk w środowisku WordPress. Największą siłą rozwiązania jest to, że łączy proces handlowy, delivery i raportowanie w jednym modelu danych. Największym potencjałem rozwoju są teraz: dopracowanie logiki biznesowej, automatyzacja powiadomień, warstwa billingowa oraz dalsze usprawnienia UX.
