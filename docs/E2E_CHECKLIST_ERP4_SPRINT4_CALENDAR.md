# ERP_4.0 — Sprint 4 (EPIC D) E2E checklist

Data aktualizacji: 2026-04-14

## Cel
Domknąć odbiór end-to-end dla kalendarza projektów i synchronizacji Google Calendar:
- OAuth,
- CRON (2h),
- delete path,
- status/error w repo mapowań.

## 1) OAuth — połączenie i token refresh
1. Wejdź w `Ustawienia` i uzupełnij:
   - Client ID,
   - Client Secret,
   - Scope,
   - Calendar ID.
2. Kliknij **Połącz z Google** i zakończ autoryzację.
3. Zweryfikuj, że:
   - refresh token został zapisany,
   - `last_error` jest pusty.
4. Wymuś refresh (np. wyzeruj expiry tokenu) i uruchom sync ręczny.
5. Oczekiwane:
   - nowy access token zapisany,
   - brak błędu,
   - `last_synced_at` ustawione.

## 2) CRON — synchronizacja cykliczna
1. Zweryfikuj harmonogram `erp_omd_two_hours`.
2. Zweryfikuj hook `erp_omd_google_calendar_sync`.
3. Wymuś uruchomienie CRON.
4. Oczekiwane:
   - aktywne projekty mają status `synced`,
   - mapowania eventów (`range_event_id`, `deadline_event_id`) są zapisane.

## 3) Delete path — usuwanie eventów
1. Ustaw projekt w `archiwum` lub usuń projekt.
2. Uruchom sync (manualnie lub CRON).
3. Oczekiwane:
   - eventy są usuwane z Google Calendar,
   - rekord mapowania w `erp_omd_project_calendar_sync` jest usuwany.

## 4) Obsługa błędu i retry
1. Zasymuluj błąd API (np. nieprawidłowy token / błąd połączenia).
2. Oczekiwane:
   - `sync_status=error`,
   - `last_error` uzupełnione,
   - alert administracyjny wysłany.
3. Napraw przyczynę (token/konfiguracja) i uruchom ponownie sync.
4. Oczekiwane:
   - `sync_status` wraca do `synced`,
   - `last_error` czyszczone.

## 5) Kryterium DONE (Sprint 4 / EPIC D)
- OAuth + refresh działają stabilnie.
- CRON 2h działa i nie tworzy duplikatów eventów.
- Delete path usuwa eventy i mapowania.
- Błędy są widoczne i możliwe do odzyskania retry.
