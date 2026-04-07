# UAT Master Pass v1 (Wariant B) — przebieg roboczy

Data: 2026-04-07  
Zakres: ETAP 1 / KROK 1.3 z `docs/PLAN_PELNE_WDROZENIE_WARIANT_B_V1.md`

## 1) Cel

Przejść checklistę UAT ze specyfikacji i zostawić jeden artefakt: co przeszło, co wymaga ręcznej walidacji biznesowej, co jest blockerem.

## 2) Wynik bieżący

Status globalny: **IN PROGRESS**

- ✅ Przygotowano ekran monitoringu technicznego i audit log korekt (filtry + CSV).
- ✅ Testy automatyczne backend/REST przechodzą.
- 🟡 Wymagany jeszcze manualny przebieg UAT per ekran przez właściciela biznesowego/operacyjnego.

## 3) Checklista UAT (robocza)

## Ekran 1 — Dashboard główny
- [ ] UAT-D1 status miesiąca (manual)
- [ ] UAT-D2 trend 3M (manual)
- [ ] UAT-D3 top/bottom (manual)
- [ ] UAT-D4 korekty + drilldown (manual)

## Ekran 2 — Raport per klient
- [ ] UAT-C1 widok prosty (manual)
- [ ] UAT-C2 widok szczegółowy + eksport (manual)

## Ekran 3 — Raport per projekt
- [ ] UAT-P1 widok prosty (manual)
- [ ] UAT-P2 widok szczegółowy (manual)

## Ekran 4 — Raport per czas pracy
- [ ] UAT-T1 widok prosty (manual)
- [ ] UAT-T2 widok szczegółowy + paginacja (manual)

## Ekran 5 — OMD rozliczenia
- [ ] UAT-O1 definicje (manual)
- [ ] UAT-O2 wyniki + eksport (manual)

## Ekran 6 — Zarządzanie miesiącem
- [ ] UAT-M1 przejścia statusów (manual)
- [ ] UAT-M2 blokady uprawnień (manual)
- [ ] UAT-M3 korekty 72h vs emergency (manual)

## Ekran 7 — Audit log korekt
- [x] UAT-A1 rejestr i filtrowanie (automatyczny smoke + implementacja UI)
- [x] UAT-A1 eksport CSV audytu (automatyczny smoke + implementacja backend)
- [ ] UAT-A1 walidacja biznesowa użytkownika końcowego (manual)

## 4) Wykonane dziś dowody techniczne

- `php -l erp-omd/includes/class-admin.php`
- `php -l erp-omd/templates/admin/reports.php`
- `php tests/rest-api-test.php`

## 5) Co dalej (następna iteracja)

1. Wykonać manual UAT dla ekranów 1–6 na danych staging.
2. Potwierdzić checklistę z właścicielem procesu (PASS/PASS z warunkami).
3. Zamknąć ETAP 1 / KROK 1.3 wpisem końcowym w `docs/WDROZENIE_V1_DZIENNIK.md`.
