# ETAP 3 / KROK 3.3 — operacyjna matryca reakcji (kto/co/kiedy)

Data: 2026-04-08  
Status: **DRAFT (gotowa do użycia w ćwiczeniu/operacji)**

## 1) Cel

Ustalić jednoznacznie:
- **kto** reaguje na dany poziom sygnału,
- **co** ma zrobić,
- **kiedy** (SLA reakcji i eskalacji).

## 2) Wejścia do decyzji

Źródło główne: `GET /erp-omd/v1/system/status` + sygnały runbooka on-call:
- `generation_ms_p95_within_target`,
- `error_rate_percent` / `has_error`,
- `sustained_drift_detected`,
- kontekst: `report_type`, `month`, `rows_count`.

## 3) Matryca reakcji (kto / co / kiedy)

| Poziom | Warunek wejścia (przykład) | Kto (owner) | Co robić | SLA reakcji |
|---|---|---|---|---|
| **OK** | `sustained_drift_detected = false`, brak alertów p95/error | On-call L1 | Monitorowanie cykliczne, brak zmian progów | Przegląd 1x dziennie |
| **WARN** | pojedyncze przekroczenia p95 lub krótkotrwały wzrost błędów | On-call L1 | Triage: potwierdzenie zakresu (`report_type`, miesiąc, wolumen), ograniczenie ciężkich filtrów, obserwacja trendu | do 15 min |
| **ALERT** | trwały dryf (`sustained_drift_detected = true`) lub stabilny wzrost error-rate | On-call L1 + Backend Owner (L2) | Uruchomić rollback/tuning wg runbooka, zebrać payload + logi, eskalować i prowadzić komunikację statusową | start działań do 5 min, eskalacja do 15 min |
| **CRITICAL** | brak stabilizacji po działaniach ALERT / wpływ na kluczowe raporty biznesowe | Incident Lead + Backend Owner + Product/Operations Owner | Tryb incydentowy: pełny rollback, komunikat do interesariuszy, decyzja o ograniczeniu funkcji | natychmiast, max 5 min |

## 4) RACI (odpowiedzialności)

- **On-call L1** — wykrycie, triage, pierwsza mitigacja.
- **Backend Owner (L2)** — diagnostyka techniczna, rollback/tuning, decyzje implementacyjne.
- **Product/Operations Owner** — priorytety biznesowe i komunikacja z użytkownikami.

## 5) Definition of Done dla kroku 3.3

- Matryca zatwierdzona przez ownerów (L1/L2/Operations).
- Czasy SLA przetestowane co najmniej 1x w ćwiczeniu operacyjnym.
- Wpis do `WDROZENIE_V1_DZIENNIK.md` z wynikiem `PASS/PASS WARUNKOWY/FAIL`.
