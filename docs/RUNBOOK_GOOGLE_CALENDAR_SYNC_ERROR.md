# Runbook — `sync_status=error` dla Google Calendar

Data aktualizacji: 2026-04-14

## Objaw
- Projekt ma `sync_status=error` w tabeli mapowań kalendarza.
- W UI kalendarza widoczny `last_error`.
- Opcjonalnie wysłany alert mailowy do administratora.

## Diagnostyka (kolejność)
1. Sprawdź konfigurację:
   - Client ID,
   - Client Secret,
   - Scope,
   - Calendar ID.
2. Sprawdź tokeny:
   - refresh token istnieje,
   - access token nie jest przeterminowany (lub odświeża się poprawnie).
3. Sprawdź odpowiedź Google API:
   - kod HTTP,
   - payload błędu (`error.message`).
4. Sprawdź poprawność danych projektu:
   - `start_date` + `end_date` dla eventu range,
   - `deadline_date` dla eventu deadline.

## Retry (procedura operacyjna)
1. Napraw przyczynę błędu:
   - re-autoryzuj OAuth (Połącz z Google),
   - popraw Calendar ID / scope / secret,
   - popraw daty projektu.
2. Uruchom sync ręczny (`Synchronizuj teraz`).
3. Zweryfikuj:
   - `sync_status=synced`,
   - `last_error` puste,
   - `last_synced_at` ustawione.

## Kiedy eskalować
- 3 kolejne próby retry zakończone błędem.
- Błędy 401/403 utrzymują się po re-autoryzacji.
- Błędy 5xx Google API utrzymują się > 30 min.

## Działania po incydencie
- Zapisz przyczynę i czas usunięcia.
- Dodaj notkę do dziennika wdrożeniowego.
- Jeżeli błąd wynikał z konfiguracji, zaktualizuj checklistę E2E.
