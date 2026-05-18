# ERP_4.0 — Release Closure (Sprint 5)

Data zamknięcia: **2026-04-15**

## Zakres domknięcia

Sprint 5 (ERP_4.0) obejmuje:
- E1: moduł dostawców,
- E2: workflow faktur kosztowych,
- E3: relacje projekt ↔ dostawca ↔ faktura,
- E4: unikalność numeru faktury w obrębie dostawcy,
- E5: audit log krytycznych zmian.

## Dowody wykonania

### 1) Artefakt wydania
- Paczka sprintu obecna: `dist/erp-omd-sprint-5.zip`.

### 2) Sanity / testy domenowe
Uruchomione komendy:

```bash
./scripts/test-sprint-5.sh
php tests/estimate-service-test.php
php tests/project-financial-service-test.php
php tests/time-entry-service-test.php
```

Wynik:
- sanity check Sprintu 5 zakończony powodzeniem,
- testy domenowe zakończone powodzeniem (`Assertions` OK),
- ZIP sprintowy zbudowany i dostępny.

### 3) Odbiór funkcjonalny
- Potwierdzony przepływ kosztorys → akceptacja → projekt.
- Potwierdzone działanie read-only po akceptacji.
- Potwierdzone podstawowe scenariusze UI/REST dla modułu sprintu.

## Decyzja release

**Sprint 5: CLOSED / DONE**  
Brak otwartych blockerów w obszarze Sprintu 5.

## Przekazanie do Sprintu 6

Start realizacji Sprintu 6 (Integracja KSeF: F1/F2) zgodnie z roadmapą ERP_4.0.
