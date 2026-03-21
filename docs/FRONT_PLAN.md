# FRONT — plan rozwoju frontendu pracowniczego i managerskiego

> Ten dokument jest „kotwicą” do dalszej pracy.  
> Jeżeli w kolejnej rozmowie padnie hasło **FRONT**, wracamy do tego planu jako uzgodnionego backlogu i punktu startowego.

## 1. Cel etapu FRONT

Celem etapu **FRONT** jest dobudowanie do istniejącego systemu ERP OMD osobnej warstwy frontendowej dla:

- pracowników,
- managerów,
- oraz workflow wniosków projektowych.

Frontend ma uprościć codzienną pracę operacyjną i odciążyć użytkowników od konieczności korzystania z `wp-admin`, ale bez duplikowania logiki biznesowej, która już działa w warstwie admin/REST.

## 2. Uzgodnione założenia

### 2.1 Logowanie pracowników

Potwierdzone decyzje:

- WordPress pozostaje źródłem logowania i autoryzacji,
- powstaje osobna strona logowania / formularz UI dla pracowników,
- po zalogowaniu użytkownik jest przekierowywany według roli / capability.

### 2.2 Panel pracownika

Potwierdzony zakres:

- dodaj wpis czasu,
- moja lista wpisów,
- filtrowanie po:
  - dacie,
  - projekcie,
  - statusie,
- szybkie akcje godzinowe:
  - 15 min,
  - 30 min,
  - 45 min,
- widok kalendarza własnych godzin, analogiczny do podejścia znanego z raportów.

Potwierdzone zasady edycji:

- pracownik może edytować i usuwać tylko **własne wpisy `submitted`**,
- pracownik nie może edytować ani usuwać wpisów `approved` i `rejected`,
- pracownik nie powinien móc zmieniać statusów akceptacyjnych ręcznie poza zakresem dopuszczonym przez workflow.

### 2.3 Panel managera

Potwierdzony zakres MVP:

- lista projektów, którymi manager zarządza,
- podgląd podstawowych danych projektu:
  - klient,
  - status,
  - model rozliczenia,
  - budżet / retainer,
  - marża / alerty,
- lista kosztorysów powiązanych z jego obszarem,
- lista wpisów czasu do akceptacji,
- szybkie akcje:
  - zaakceptuj,
  - odrzuć,
  - przejdź do szczegółów projektu.

Potwierdzona zasada rozwoju:

- najpierw robimy **widok + akceptacje + ograniczone akcje**,
- dopiero później ewentualne **tworzenie / edycję**.

### 2.4 Wniosek o nowy projekt

Potwierdzona decyzja:

- nie tworzymy bezpośrednio projektu z frontendu,
- użytkownik wysyła **wniosek o projekt**,
- wniosek przechodzi osobny workflow i nie omija lifecycle głównego modułu projektowego.

## 3. Zasady architektoniczne FRONT

### 3.1 Bez duplikacji logiki biznesowej

Frontend ma korzystać z tej samej logiki, która już istnieje w systemie:

- walidacji,
- reguł dostępu,
- approval flow,
- resolverów stawek i snapshotów,
- istniejących usług i endpointów REST.

To oznacza, że frontend powinien być przede wszystkim nową warstwą prezentacji i ergonomii, a nie osobnym równoległym systemem.

### 3.2 Osobne capability frontendowe dla managera

Ustalona decyzja:

- należy doprecyzować model uprawnień managera,
- do frontendu warto wprowadzić osobne capability frontendowe,
- dzięki temu nie mieszamy uprawnień backoffice z uprawnieniami do lekkiego panelu operacyjnego.

### 3.3 Frontend czasu pracy ma być prostszy niż backend admina

Uzgodniony kierunek UX:

- mój tydzień / miesiąc,
- moje projekty,
- szybkie dodawanie wpisu,
- historia i statusy wpisów,
- kalendarz godzin.

Frontend nie powinien kopiować 1:1 rozbudowanego widoku adminowego, tylko dawać skróconą i wygodniejszą ścieżkę codziennej pracy.

### 3.4 Workflow projektu nie może omijać lifecycle

Uzgodniona zasada:

- nie dajemy użytkownikowi akcji „utwórz projekt”,
- dajemy akcję „wyślij wniosek o projekt”,
- dopiero zaakceptowany wniosek może być konwertowany do projektu przez uprawnioną osobę.

## 4. Zakres funkcjonalny etapu FRONT

## FRONT-1 — logowanie i routing użytkownika

### Cel

Oddzielić doświadczenie pracownika i managera od surowego `wp-admin`.

### Zakres

- osobna strona logowania frontendowego,
- własny formularz UI oparty o WordPress auth,
- redirect po loginie zależny od roli / capability,
- ochrona tras frontendowych przed niezalogowanym użytkownikiem,
- wyjście / logout z poprawnym redirectem.

### Rezultat

Użytkownik wchodzi do systemu przez dedykowany punkt wejścia i trafia od razu do właściwego panelu.

## FRONT-2 — panel pracownika

### Cel

Dać pracownikowi prosty self-service do raportowania czasu.

### Zakres MVP

- dashboard pracownika,
- formularz nowego wpisu czasu,
- lista własnych wpisów,
- filtry:
  - data,
  - projekt,
  - status,
- szybkie przyciski godzinowe,
- widok kalendarza własnych godzin,
- edycja własnych wpisów `submitted`,
- usuwanie własnych wpisów `submitted`,
- brak możliwości edycji/usuwania wpisów `approved` i `rejected`.

### Dodatkowe uwagi

- warto dodać domyślny widok tygodnia lub miesiąca,
- warto pokazać skrót: dziś / ten tydzień / ten miesiąc,
- warto uprościć formularz do najczęściej używanej ścieżki.

## FRONT-3 — panel managera

### Cel

Dać managerowi wygodny widok operacyjny bez konieczności ciągłego korzystania z panelu admina.

### Zakres MVP

- lista projektów managera,
- karta projektu z podstawowymi danymi biznesowymi,
- widok marży / alertów,
- lista powiązanych kosztorysów,
- kolejka wpisów czasu do akceptacji,
- szybkie akcje akceptacji i odrzucenia,
- link do przejścia do szczegółów projektu.

### Ograniczenia MVP

- bez pełnego CRUD projektów z frontendu,
- bez pełnej edycji kosztorysów z frontendu,
- nacisk na przegląd, zatwierdzanie i szybkie decyzje operacyjne.

## FRONT-4 — workflow wniosku o projekt

### Cel

Umożliwić inicjowanie nowych projektów bez rozszczelniania głównego lifecycle projektowego.

### Proponowana encja

`project_request`

### Przykładowe pola

- requester_user_id,
- requester_employee_id,
- client_id,
- project_name,
- billing_type,
- preferred_manager_id,
- estimate_id (opcjonalnie),
- brief / uzasadnienie,
- status:
  - `new`,
  - `under_review`,
  - `approved`,
  - `rejected`,
  - `converted`.

### Workflow

1. użytkownik wysyła wniosek,
2. administrator lub uprawniona osoba dostaje powiadomienie,
3. wniosek jest przeglądany,
4. po akceptacji można go skonwertować do projektu,
5. system zapisuje relację między wnioskiem a utworzonym projektem.

## 5. Proponowana kolejność wdrożenia

### Priorytet wdrożeniowy

1. **FRONT-1** — logowanie i routing,
2. **FRONT-2** — panel pracownika czasu pracy,
3. **FRONT-3** — panel managera,
4. **FRONT-4** — workflow wniosku projektowego.

### Dlaczego taka kolejność

- logowanie jest warstwą bazową dla reszty,
- panel pracownika daje najszybszy efekt biznesowy,
- panel managera wykorzysta już gotową warstwę dostępu i nawigacji,
- workflow projektowy jest najbezpieczniej wdrażać po ustabilizowaniu ról frontendowych.

## 6. Co trzeba doprecyzować przy starcie implementacji

Przed rozpoczęciem właściwego developmentu FRONT trzeba jeszcze domknąć kilka decyzji wykonawczych:

1. Czy frontend będzie oparty o:
   - shortcode,
   - własne template pages,
   - czy osobny mini-moduł routingowy?
2. Jak nazwać i rozdzielić nowe capability frontendowe?
3. Czy panel pracownika ma pokazywać:
   - tylko wpisy czasu,
   - czy też skrót projektów przypisanych do użytkownika?
4. Czy manager ma widzieć:
   - tylko projekty, gdzie jest `manager_id`,
   - czy również obszary wynikające z innych przypisań?
5. Czy wniosek projektowy ma być dostępny:
   - tylko managerom,
   - czy też wybranym pracownikom?

## 7. Definicja gotowości etapu FRONT

Etap FRONT uznamy za gotowy, gdy:

- użytkownik loguje się przez dedykowaną stronę frontendową,
- pracownik może wygodnie prowadzić własny time tracking z kalendarzem,
- manager ma lekki operacyjny widok projektów, kosztorysów i akceptacji czasu,
- nowy projekt może być inicjowany przez wniosek, a nie bezpośrednie obejście lifecycle,
- całość korzysta z istniejącej logiki biznesowej i nie duplikuje reguł backendowych.

## 8. Hasło do wznowienia tematu

Hasło do wznowienia tego obszaru w kolejnych rozmowach:

**FRONT**

Po użyciu tego hasła wracamy do:

- planu etapów,
- uzgodnionych decyzji,
- zakresu MVP,
- zasad architektonicznych,
- i backlogu dalszej pracy opisanego w tym pliku.
