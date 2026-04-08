# Reporting benchmark baseline — 2026-04-07

Cel: przygotować bazę odniesienia pod ETAP 2 / KROK 2.1 (wydajność raportów).

## Komenda

`php tests/reporting-benchmark-12m.php`

## Wynik

```json
{
  "rows": 12,
  "elapsed_ms": 19.15,
  "projects_all_calls": 1,
  "salary_for_employee_calls": 0,
  "salary_for_employees_calls": 1,
  "project_cost_for_project_calls": 0,
  "project_cost_sum_by_project_and_month_calls": 1,
  "time_entries_all_calls": 1
}
```

## Wnioski (baseline)

- Test syntetyczny 12M zwrócił 12 wierszy i wykonał się ~19 ms w środowisku testowym.
- Wykorzystana jest ścieżka z agregacją batch (`for_employees`, `sum_by_project_and_month_in_date_range`) bez regresji do per-row zapytań.
- Ten wynik traktujemy jako **baseline porównawczy**, nie jako wynik produkcyjny.

## Następny krok

- Po manualnym UAT (ETAP 1.3) powtarzamy benchmark po każdej większej zmianie raportowej i porównujemy odchylenie względem tego baseline.
