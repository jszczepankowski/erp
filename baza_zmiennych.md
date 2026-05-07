# Baza zmiennych — spec dokument (ERP OMD)

## Cel dokumentu
Ten dokument jest bazą referencyjną do przebudowy i utrzymania raportów. Zawiera mapę:

`pole_wynikowe -> źródło (tabela/pole) -> wzór -> warunki -> raporty`

Możesz dopisywać kolejne sekcje dla nowych modułów systemu.

---

## A. Zakres raportów (admin.php?page=erp-omd-reports)

### A1. Typy raportów
- projects
- clients
- invoice
- time_entries
- monthly
- omd_rozliczenia

### A2. Filtry wspólne
| Pole filtra | Znaczenie | Uwaga |
|---|---|---|
| month | miesiąc raportowy (YYYY-MM) | wejście z date, normalizowane |
| report_type | typ raportu | walidacja do whitelisty |
| tab | reports / calendar | wpływa na filtrowanie statusów czasu |
| status | status projektu lub wpisu czasu | zależne od kontekstu |
| mode | LIVE / DO_ROZLICZENIA / ZAMKNIETY | filtrowanie statusów projektu |
| detail | simple / detail | time_entries wymusza simple |
| client_id/project_id/employee_id | zawężenie encji | opcjonalne |

---

## B. Mapa danych — słownik pól i wzorów

### B1. Time entry (poziom rekordu)
| Pole | Źródło | Wzór / interpretacja | Raporty |
|---|---|---|---|
| entry_date | time_entries.entry_date | data wpisu czasu | time_entries, projects(detail), monthly, calendar |
| status | time_entries.status | submitted/approved/rejected | wszystkie raporty oparte o czas |
| hours | time_entries.hours | godziny pracy | wszystkie raporty oparte o czas |
| rate_snapshot | time_entries.rate_snapshot | stawka klientowska/h zamrożona na dzień wpisu | time_entries, projects, monthly, invoice |
| cost_snapshot | time_entries.cost_snapshot | koszt wewnętrzny/h zamrożony na dzień wpisu | projects, monthly, omd_rozliczenia |
| amount | wyliczane | hours * rate_snapshot | time_entries, projects(detail), invoice |
| entry_cost | wyliczane | hours * cost_snapshot | projects(detail) |
| entry_profit | wyliczane | amount - entry_cost | projects(detail) |

### B2. Agregaty projektu/klienta
| Pole | Źródło | Wzór / interpretacja | Raporty |
|---|---|---|---|
| reported_hours | agregacja time entries | Σ hours | projects, clients |
| entries_count | agregacja time entries | liczba wpisów | projects, monthly |
| filtered_time_revenue | agregacja time entries | Σ(hours * rate_snapshot) | projects, clients |
| filtered_time_cost | agregacja time entries | Σ(hours * cost_snapshot) | projects, clients |
| filtered_direct_cost | project_costs | suma kosztów bezpośrednich wg zakresu | projects, clients |
| margin (klient) | wyliczane | if revenue>0: (profit/revenue)*100 else 0 | clients |

### B3. Finanse projektu (serwis finansowy)
| Pole | Źródło | Wzór / interpretacja | Raporty |
|---|---|---|---|
| revenue | project_financial_service | zależne od billing_type | projects, clients |
| cost | project_financial_service | time_cost + direct_cost | projects, clients |
| profit | project_financial_service | revenue - cost | projects, clients, monthly |
| margin | project_financial_service | if revenue>0: (profit/revenue)*100 else 0 | projects, clients |
| budget_usage | project_financial_service | if budget>0: (cost/budget)*100 else 0 | projects |
| time_revenue | project_financial_service | Σ(hours * rate_snapshot), approved | projects, monthly, omd_rozliczenia |
| time_cost | project_financial_service | Σ(hours * cost_snapshot), approved | projects, monthly, omd_rozliczenia |
| direct_cost | project_financial_service | Σ(project_cost.amount) | projects, monthly, omd_rozliczenia |

#### Reguły revenue wg billing_type
- fixed_price: budget + extra_revenue
- retainer: retainer_revenue + extra_revenue
- mixed: budget + time_revenue + extra_revenue
- time_material: time_revenue + extra_revenue

retainer_revenue = month_count * retainer_monthly_fee

---

## C. Mapa per raport

### C1. projects
- Rekord per projekt.
- Pobiera: finansy projektu + agregaty time entries + direct cost miesiąca.
- Drilldown do time_entries per project.

### C2. clients
- Agregacja C1 po kliencie.
- Sumuje godziny, przychody, koszty, zysk, direct cost.

### C3. invoice
- Wariant projects ze statusem do_faktury.
- invoice_items:
  - estymata qty*price (fixed/mixed)
  - lub grupowanie czasu po (rola, stawka)
  - fallback retainer

### C4. time_entries
- Raport liniowy wpisów czasu.
- amount = hours * rate_snapshot.

### C5. monthly
- Agregacja per miesiąc.
- profit = time_revenue - time_cost - direct_cost + project_budget_profit.

### C6. omd_rozliczenia
- 12-miesięczne okno controllingowe.
- hourly_profit = time_revenue - time_cost
- operational_result = (active_project_budgets + hourly_profit) - project_direct_cost
- controlling_overhead = salary_cost + fixed_cost
- controlling_result = operational_result - controlling_overhead

---

## D. Zasady filtrowania, które wpływają na spójność raportów
- W większości raportów czas bazuje na statusie approved.
- time_entries/calendar mają tryb rozszerzony, ale bez jawnego statusu i tak potrafią zawężać do approved.
- Filtr `mode` zmienia zbiór projektów po statusach (LIVE/DO_ROZLICZENIA/ZAMKNIETY).

---

## E. Szablon do dopisywania nowych zmiennych (cały system)
Skopiuj tabelę i uzupełniaj:

| Moduł | Pole | Źródło DB / serwis | Wzór | Filtry / warunki | Ekrany / API |
|---|---|---|---|---|---|
| np. sprzedaż | net_amount | invoices.net_amount | brutto - VAT | status=approved | raport X, endpoint Y |

