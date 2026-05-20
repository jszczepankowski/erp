# Sprint 3 — Async zapis (Faza A/B/C)

Status: przygotowane do wdrożenia etapowego.

## Faza A (fundament)

- Ujednolicony kontrakt odpowiedzi async:
  - `ok` (bool),
  - `message` (string),
  - `data` (mixed),
  - `errors` (array),
  - `meta` (object).
- Centralny parser odpowiedzi i domyślne nagłówki REST w `assets/js/admin.js` (`window.erpOmdAsync`).

## Faza B (zakres modułów)

Zakres uzgodniony:
- Lista zadań,
- Klienci,
- Czas pracy,
- Kosztorysy,
- Projekty,
- Wnioski,
- Faktury / KSeF.

## Faza C (testy i rollout)

- Testy endpointów (status + shape kontraktu),
- Testy UI smoke dla kluczowych flow,
- etapowe uruchamianie per moduł (flagą lub sekwencyjnie).

## Docelowy kontrakt (przykład)

```json
{
  "ok": true,
  "message": "Zapisano.",
  "data": {},
  "errors": [],
  "meta": { "entity": "client", "action": "update" }
}
```
