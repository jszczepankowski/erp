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
- [x] UAT-D1 status miesiąca (manual) — PASS (potwierdzenie użytkownika)
- [x] UAT-D2 trend 3M (manual) — PASS (potwierdzenie użytkownika)
- [x] UAT-D3 top/bottom (manual) — PASS (potwierdzenie użytkownika)
- [ ] UAT-D4 korekty + drilldown (manual)

## Ekran 2 — Raport per klient
- [x] UAT-C1 widok prosty (manual) — PASS (potwierdzenie użytkownika)
- [x] UAT-C2 widok szczegółowy + eksport (manual) — PASS (potwierdzenie użytkownika)

## Ekran 3 — Raport per projekt
- [x] UAT-P1 widok prosty (manual) — PASS (potwierdzenie użytkownika)
- [x] UAT-P2 widok szczegółowy (manual) — PASS (potwierdzenie użytkownika)

## Ekran 4 — Raport per czas pracy
- [x] UAT-T1 widok prosty (manual) — PASS (potwierdzenie użytkownika; uwaga UX: zakres simple/detail podobny)
- [x] UAT-T2 widok szczegółowy + paginacja (manual) — PASS (potwierdzenie użytkownika po fixie paginacji i per_page)

## Ekran 5 — OMD rozliczenia
- [x] UAT-O1 definicje (manual) — PASS (potwierdzenie użytkownika)
- [x] UAT-O2 wyniki + eksport (manual) — PASS (potwierdzenie użytkownika)

## Ekran 6 — Zarządzanie miesiącem
- [ ] UAT-M1 przejścia statusów (manual) — RETEST PO WDROŻENIU UI/API
- [ ] UAT-M2 blokady uprawnień (manual) — RETEST PO WDROŻENIU UI/API
- [x] UAT-M3 korekty 72h vs emergency (manual) — PASS (potwierdzenie użytkownika, 2026-04-08)

## Ekran 7 — Audit log korekt
- [x] UAT-A1 rejestr i filtrowanie (automatyczny smoke + implementacja UI)
- [x] UAT-A1 eksport CSV audytu (automatyczny smoke + implementacja backend)
- [x] UAT-A1 widok `przed/po` (old/new) dostępny w tabeli i eksporcie CSV
- [ ] UAT-A1 walidacja biznesowa użytkownika końcowego (manual)

## 4) Wykonane dziś dowody techniczne

- `php -l erp-omd/includes/class-admin.php`
- `php -l erp-omd/templates/admin/reports.php`
- `php tests/rest-api-test.php`

## 5) Co dalej (następna iteracja)

1. Wykonać manualny retest Ekranu 6 po wdrożeniu UI/API przejść statusów.
2. Domknąć UAT-M1 i UAT-M2 (przejścia statusów + blokady uprawnień).
3. Zaktualizować wpis końcowy w `docs/WDROZENIE_V1_DZIENNIK.md` wynikiem PASS/PASS warunkowy/FAIL dla Kroku 6.
