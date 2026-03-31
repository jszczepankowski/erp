# ERP OMD — raporty i dashboard (zasady + przykłady)

Dokument tłumaczy **jak liczą się raporty** i **karty dashboardu** na prostych liczbach.

## 1. Wspólna logika filtrowania

Każdy raport przechodzi przez te same filtry wejściowe:
- `month` (format `YYYY-MM`),
- `client_id`,
- `project_id`,
- `employee_id`,
- `status`,
- `report_type`.

Następnie:
- projekty są filtrowane po `project_id`, `client_id` i statusie projektu,
- wpisy czasu są filtrowane po projektach, pracowniku, projekcie, statusie wpisu i miesiącu.

To oznacza, że raporty zwykle pracują na tym samym „wycinku danych”, tylko inaczej agregują wynik.

---

## 2. Jak budowany jest każdy raport (na przykładach)

### 2.1 Raport projektów (`projects`)

Dla każdego projektu system zbiera:
1. Metryki czasu (godziny, liczba wpisów, przychód czasu, koszt czasu),
2. Koszt bezpośredni projektu (z tabeli kosztów projektu),
3. Finanse projektu (przychód, koszt, zysk, marża, wykorzystanie budżetu).

### Przykład

Projekt A (time_material):
- wpisy czasu: 10h po 200 zł/h i koszcie 120 zł/h,
- koszt bezpośredni: 300 zł.

Wyliczenie:
- `time_revenue = 10 * 200 = 2000`,
- `time_cost = 10 * 120 = 1200`,
- `cost = 1200 + 300 = 1500`,
- `revenue = 2000` (bo time_material),
- `profit = 2000 - 1500 = 500`,
- `margin = 500 / 2000 = 25%`.

---

### 2.2 Raport klientów (`clients`)

To suma raportu projektów pogrupowana po kliencie.

### Przykład

Klient X ma dwa projekty:
- Projekt A: przychód 2000, koszt 1500, zysk 500,
- Projekt B: przychód 3000, koszt 2100, zysk 900.

Raport klienta:
- `revenue = 5000`,
- `cost = 3600`,
- `profit = 1400`,
- `margin = 1400/5000 = 28%`.

---

### 2.3 Raport do faktury (`invoice`)

To raport projektów z wymuszonym statusem `do_faktury` + pozycje faktury:
- fixed_price z kosztorysem: pozycje z kosztorysu (`qty * price`),
- pozostałe: agregacja czasu po (rola + stawka),
- retainer bez pozycji: jedna pozycja ryczałtowa.

### Przykład (time + rola)

Dla projektu C:
- Developer 8h po 250,
- QA 4h po 180.

Pozycje faktury:
- `Developer: 8 * 250 = 2000`,
- `QA: 4 * 180 = 720`,
- suma: `2720`.

---

### 2.4 Raport czasu pracy (`time_entries`)

Raport pokazuje szczegółowo każdy wpis i pole `amount` liczy jako:
- `amount = hours * rate_snapshot`.

### Przykład

Wpis: 5.5h, stawka 210 zł/h:
- `amount = 5.5 * 210 = 1155`.

---

### 2.5 Raport miesięczny (`monthly`)

Agreguje dane **miesiąc po miesiącu**:
- liczba wpisów,
- liczba projektów i klientów,
- godziny,
- `time_revenue`,
- `time_cost`,
- `direct_cost` (koszty projektowe z miesiąca),
- `project_budget_profit` (budżety projektów ze statusem `do_faktury`/`zakonczony`, przypisane do miesiąca zakończenia projektu),
- `profit = time_revenue - time_cost - direct_cost + project_budget_profit`.

### Przykład

Marzec:
- `time_revenue = 20 000`,
- `time_cost = 12 000`,
- `direct_cost = 3 000`,
- `project_budget_profit = 8 000`.

Wynik:
- `profit = 20 000 - 12 000 - 3 000 + 8 000 = 13 000`.

---

### 2.6 Raport OMD rozliczenia (`omd_rozliczenia`)

Buduje 12-miesięczne zestawienie (od `month-11` do `month`) i dla każdego miesiąca liczy:
- `salary_cost` (pensje z aktywnych wpisów historii wynagrodzeń),
- `project_direct_cost`,
- `active_project_budgets` (suma budżetów projektów `do_faktury` + `zakonczony`),
- `time_revenue`,
- `time_cost`,
- `hourly_profit = time_revenue - time_cost`,
- `fixed_cost` (z pozycji kosztów stałych; fallback do legacy),
- `operating_result = (active_project_budgets + hourly_profit) - (salary_cost + fixed_cost + direct_cost)`.

### Przykład (marzec)

Załóżmy:
- `time_revenue = 40 000`,
- `active_project_budgets = 25 000`,
- `salary_cost = 18 000`,
- `fixed_cost = 4 000`,
- `direct_cost = 7 000`,
- `time_cost = 22 000`.

Wyniki:
- `hourly_profit = 40 000 - 22 000 = 18 000`,
- `operating_result = (25 000 + 18 000) - (18 000 + 4 000 + 7 000) = 14 000`.

---

## 3. Dashboard — jak liczone są pozycje

Dashboard bierze dane z dwóch źródeł:
1. `build_monthly_performance_metrics(reporting_month)`,
2. bieżący miesiąc z `reporting_service->build_report('omd_rozliczenia', ...)`.

### 3.1 Kafelek „Koszt · MM.RRRR”

To `monthly_totals['hourly_cost_total']` z miesięcznych metryk.

W praktyce:
- bierze **tylko zatwierdzone (`approved`) wpisy czasu** z bieżącego miesiąca,
- dla każdego wpisu liczy `hours * cost_snapshot`,
- sumuje.

### Przykład

- 6h * 120 = 720,
- 4h * 150 = 600,

`Koszt = 1320`.

---

### 3.2 Kafelek „Zysk · MM.RRRR”

To `monthly_totals['employee_profit']`.

W praktyce:
- dla zatwierdzonych wpisów z miesiąca,
- `profit_entry = hours*rate_snapshot - hours*cost_snapshot`,
- suma tych profitów.

### Przykład

- wpis 1: 6h, rate 220, cost 120 → `(6*220) - (6*120) = 600`,
- wpis 2: 4h, rate 250, cost 150 → `(4*250) - (4*150) = 400`,

`Zysk = 1000`.

---

### 3.3 Wykres rentowności miesiąca

Najpierw:
- `cost_total = hourly_cost_total`,
- `profit_total = employee_profit`,
- `revenue_total = cost_total + profit_total`.

Potem udział procentowy paska:
- `cost_share = cost_total / revenue_total * 100`,
- `profit_share = 100 - cost_share`,
- oba segmenty mają minimalnie 2% (żeby były widoczne).

### Przykład

- `cost_total = 12 000`,
- `profit_total = 8 000`,
- `revenue_total = 20 000`.

Udziały:
- `cost_share = 60%`,
- `profit_share = 40%`.

---

### 3.4 Miesięczny bilans operacyjny

To 4 słupki:
1. `Koszty miesięczne projektów = project_direct_cost`,
2. `Koszty pracy pracowników = time_cost`,
3. `Zysk z pracy pracowników = hourly_profit`,
4. `Wynik operacyjny = operating_result`.

Szerokość słupków jest normalizowana do największej wartości bezwzględnej z tej czwórki (min. 4%).

### Przykład

Załóżmy:
- `project_direct_cost = 7 000`,
- `time_cost = 22 000`,
- `hourly_profit = 18 000`,
- `operating_result = 14 000`.

Słupki porównują więc: 7k, 22k, 18k, 14k.

---

## 4. Co najczęściej myli użytkowników

1. **„Koszt” w kafelku dashboardu** to koszt czasu pracy (approved wpisy), a nie wszystkie koszty firmy.
2. **Raport miesięczny** i **OMD rozliczenia** mają inne definicje wyniku:
   - miesięczny: `time_revenue - time_cost - direct_cost + project_budget_profit`,
   - OMD wynik operacyjny: `(active_project_budgets + hourly_profit) - (salary_cost + fixed_cost + direct_cost)`.
3. **Raport projektów** bierze finansy projektu z serwisu finansowego projektu (zależne od typu rozliczenia), a nie wyłącznie z filtrowanych wpisów czasu.
