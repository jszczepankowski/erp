# ETAP 2 / KROK 2.2 — raport testów bezpieczeństwa endpointów krytycznych

Data: 2026-04-08  
Status: **PASS**

## Zakres

- `/periods/{month}/transition`
- `/adjustments` (GET/POST)
- krytyczne operacje raportowe wymagające autoryzacji

## Zestaw testów (AuthN/AuthZ + walidacja)

- S-01 brak logowania -> `POST /periods/{month}/transition` — PASS
- S-02 non-admin ERP -> `POST /periods/{month}/transition` — PASS
- S-03 admin -> `POST /periods/{month}/transition` (poprawny payload) — PASS
- S-04 admin -> `POST /periods/{month}/transition` (invalid month) — PASS
- S-05 admin -> `POST /periods/{month}/transition` (invalid to_status) — PASS
- S-06 brak logowania -> `GET /adjustments` — PASS
- S-07 non-admin ERP -> `GET /adjustments` — PASS
- S-08 admin -> `GET /adjustments?month=YYYY-MM` — PASS
- S-09 non-admin ERP -> `POST /adjustments` — PASS
- S-10 admin -> `POST /adjustments` bez wymaganych pól — PASS

## Wynik

Brak krytycznych findings P0/P1 dla testowanego zakresu endpointów krytycznych.
