# Kółko

## Cel
Powiadomienia menu ERP OMD w formie „kółka” (badge) dla nieobsłużonych rekordów.

## Widoczność
Kółko jest wyświetlane wyłącznie dla ról:
- administrator
- erp_omd_manager

## Definicja licznika
Licznik oznacza wyłącznie rekordy **nieobsłużone**.
Samo otwarcie listy/panelu nie kasuje licznika.
Licznik znika dopiero po akcji na rekordzie.

## Reguły biznesowe
### 1) Wpisy czasu pracy
- rekord liczy się od momentu utworzenia / przejścia do statusu „zgłoszony” (`submitted`)
- rekord przestaje się liczyć po zatwierdzeniu (`approved`) lub odrzuceniu (`rejected`)

### 2) Kosztorysy
- rekord liczy się po uzyskaniu statusu „zaakceptowany” (`zaakceptowany`)
- rekord przestaje się liczyć po usunięciu lub po zamianie w projekt (powiązanie kosztorysu z projektem)

### 3) Wnioski projektowe (pracownik i klient)
- rekord liczy się od momentu utworzenia (`new`)
- rekord przestaje się liczyć po otwarciu lub po zmianie statusu

## Deduplikacja
Jedno zdarzenie = jedna notyfikacja (po rekordzie źródłowym).

## Limit wyświetlania
- 0: brak badge
- 1–99: liczba
- 100+: `99+`

## Wygląd
- stały kolor akcentu: `#ddb178`
- bez priorytetów kolorystycznych
- sortowanie list (po stronie widoków) docelowo po dacie malejąco

## Zakres wdrożenia (aktualny)
W menu WordPress (ERP OMD) dodano badge przy pozycjach:
- ERP OMD (suma)
- Czas pracy
- Kosztorysy
- Wnioski (suma wniosków pracowników i klientów)
