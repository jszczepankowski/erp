# Plan wdrożenia integracji API KSeF (v2.x) dla wtyczki ERP OMD

Data: 2026-04-25
Status: plan wykonawczy (powrót do funkcjonalności KSeF API sync)

## 1. Zakres analizy i źródła

Przegląd wykonano na podstawie:
- repozytorium specyfikacji `CIRFMF/ksef-docs` (aktualny przewodnik i changelog),
- obecnego kodu wtyczki ERP OMD (moduły KSeF i REST),
- istniejącego dokumentu `docs/KSEF_REDESIGN_PLAN_2026-04-17.md`.

Najważniejsze obserwacje z dokumentacji KSeF:
1. Uwierzytelnianie jest obowiązkowe i startuje od `POST /auth/challenge` (challenge żyje 10 minut), a finalnie kończy się wymianą na `accessToken` i `refreshToken`.
2. Rekomendowany tryb pobierania faktur do systemów zewnętrznych to asynchroniczny eksport paczek przez `POST /invoices/exports` + mechanizm HWM.
3. Trzeba obsłużyć limity API, w tym `429 Too Many Requests` i nagłówek `Retry-After`.
4. Integracja musi być rozdzielona środowiskowo (`TEST`, `DEMO`, `PRD`) i nie wolno mieszać sekretów/tokenu/kluczy między środowiskami.

## 2. Aktualny stan pluginu (as-is)

### 2.1 Co już mamy
- `ERP_OMD_KSeF_Connector` (warstwa HTTP z fallback prefixów `/v2`, `/api/v2`, ``).
- `ERP_OMD_KSeF_Import_Service` (import dokumentów KSeF do modelu kosztowego/sprzedażowego, retry queue, moderacja, deduplikacja).
- REST endpointy i UI pod **manualny import XML** i moderację.

### 2.2 Czego realnie brakuje do pełnego KSeF API sync
1. Brak kompletnego, aktywnego pipeline'u KSeF API sync (w praktyce funkcjonalność jest wygaszona i operujemy głównie trybem manualnym).
2. Brak stabilnego serwisu auth KSeF (challenge -> auth token -> status -> redeem -> refresh) jako pojedynczego, testowalnego modułu.
3. Brak trwałego stanu synchronizacji per środowisko i `SubjectType` (HWM checkpointy).
4. Brak harmonogramu eksportu paczek faktur z polityką limitów i backoff.
5. Brak „operacyjnej” obserwowalności pod KSeF (metryki sync, telemetry IDs, kody błędów z jednoznaczną klasyfikacją).

## 3. Model docelowy (to-be)

## 3.1 Zasady architektoniczne
- Rozdzielić integrację na niezależne komponenty serwisowe (Auth, Export, SyncState, ImportMapper, Observability).
- Zachować istniejącą logikę biznesową importu (`ERP_OMD_KSeF_Import_Service`) jako warstwę docelową zapisu do ERP.
- Wprowadzić przełączniki feature-flag (soft launch):
  - `ksef_api_enabled`,
  - `ksef_api_mode = dry_run|active`,
  - `ksef_api_env = TEST|DEMO|PRD`.

## 3.2 Proponowane komponenty
1. `ERP_OMD_KSeF_Environment_Resolver`
   - mapowanie hostów i walidacja zgodności env.
2. `ERP_OMD_KSeF_Auth_Service`
   - challenge,
   - auth tokenem KSeF / XAdES,
   - polling statusu autoryzacji,
   - redeem/refresh,
   - bezpieczne składowanie tokenów.
3. `ERP_OMD_KSeF_Public_Key_Service`
   - pobranie i cache klucza/certyfikatu do szyfrowania tokenu KSeF,
   - walidacja fingerprint i TTL.
4. `ERP_OMD_KSeF_Export_Service`
   - inicjacja `POST /invoices/exports`,
   - polling statusu,
   - pobieranie części paczek.
5. `ERP_OMD_KSeF_Incremental_Sync_Service`
   - okna czasowe,
   - HWM per `SubjectType`,
   - retry/backoff dla 429/5xx,
   - idempotencja.
6. `ERP_OMD_KSeF_Observability_Service`
   - structured logs,
   - korelacja po `referenceNumber` / operation id,
   - metryki i alarmy.

## 4. Plan wdrożenia krok po kroku

## Etap 0 — Discovery + kontrakt techniczny (1–2 dni)
1. Potwierdzić finalny kontrakt endpointów i auth flow wg aktualnego `open-api.json` i changeloga KSeF.
2. Przygotować tabelę zgodności endpointów: „spec -> implementacja wtyczki”.
3. Zdefiniować politykę środowisk (`TEST/DEMO/PRD`) i strategy storage (oddzielne tokeny/sekrety per env).

**Artefakt:** `docs/KSEF_API_ENDPOINT_CONTRACT_MATRIX_2026-04-25.md`

## Etap 1 — Fundament auth (2–4 dni)
1. Dodać `ERP_OMD_KSeF_Auth_Service` i interfejs `ERP_OMD_KSeF_Auth_Provider_Interface`.
2. Zaimplementować:
   - `get_challenge()`,
   - `authenticate_with_ksef_token(...)`,
   - `get_auth_status(referenceNumber, authenticationToken)`,
   - `redeem_token(authenticationToken)`,
   - `refresh_access_token(refreshToken)`.
3. Dodać bezpieczne przechowywanie tokenów (szyfrowanie w at-rest + minimalna ekspozycja w panelu admina).
4. Dodać testy jednostkowe dla pełnego flow i edge-case'ów wygasania challenge.

**Pliki (proponowane):**
- `erp-omd/includes/services/class-ksef-auth-service.php` (new),
- `erp-omd/includes/class-autoloader.php` (rejestracja klasy),
- `tests/ksef-auth-service-test.php` (new).

## Etap 2 — Sync state + harmonogram (2–3 dni)
1. Dodać trwałą tabelę `erp_omd_ksef_sync_state` (installer + migracja).
2. Dodać cron hook `erp_omd_ksef_incremental_sync`.
3. Dodać lock współbieżności (aby uniknąć podwójnego uruchomienia sync).
4. Dodać retry policy: exponential backoff + respektowanie `Retry-After`.

**Pliki (proponowane):**
- `erp-omd/includes/class-installer.php`,
- `erp-omd/includes/class-cron-manager.php`,
- `erp-omd/includes/services/class-ksef-incremental-sync-service.php` (new),
- `tests/ksef-incremental-sync-test.php` (new).

## Etap 3 — Eksport paczek + HWM (3–5 dni)
1. Implementować pipeline:
   - inicjacja exportu,
   - polling statusu,
   - pobranie wszystkich części,
   - odczyt `_metadata.json`,
   - aktualizacja checkpointów HWM.
2. Iterować oddzielnie po rolach (`SubjectType`) dla kompletności danych.
3. Dodać deduplikację na `ksef_reference_number` + hash treści.

**Pliki (proponowane):**
- `erp-omd/includes/services/class-ksef-export-service.php` (new),
- `erp-omd/includes/services/class-ksef-incremental-sync-service.php`,
- `erp-omd/includes/repositories/class-cost-invoice-repository.php` (uzupełnienie indeksów/metod),
- `tests/ksef-export-service-test.php` (new).

## Etap 4 — Integracja z istniejącym importem ERP (2–4 dni)
1. Zmapować dane z paczek/XML do wejścia `ERP_OMD_KSeF_Import_Service::attempt_import_document(...)`.
2. Zachować obecną moderację i retry queue jako downstream business workflow.
3. Dodać statusy diagnostyczne „api_sync_source” w audycie.

**Pliki (proponowane):**
- `erp-omd/includes/services/class-ksef-import-service.php` (rozszerzenie wejścia o źródło API),
- `erp-omd/includes/class-rest-api.php` (endpointy diagnostyczne i ręczny trigger dry-run),
- `tests/ksef-api-sync-service-test.php` (reaktywacja i nowy kontrakt).

## Etap 5 — UI operacyjne + cutover (2–3 dni)
1. W zakładce ustawień KSeF dodać:
   - wybór środowiska,
   - status auth tokenów,
   - ostatni checkpoint HWM per `SubjectType`,
   - licznik błędów 429/5xx,
   - przycisk „Dry-run connector check”.
2. Uruchomić tryb równoległy (manual XML + API dry-run) i porównać wyniki 1:1 przez minimum 7 dni.
3. Po walidacji przełączyć na `active`.

**Pliki (proponowane):**
- `erp-omd/templates/admin/settings.php`,
- `erp-omd/includes/class-admin.php`,
- `erp-omd/assets/js/admin.js`,
- `tests/settings-ksef-api-test.php` (new).

## 5. Kryteria Done / Acceptance Criteria

1. **Auth reliability:** 20 kolejnych udanych pełnych logowań (challenge -> redeem) na tym samym środowisku.
2. **Token lifecycle:** automatyczny refresh tokenu bez ręcznej interwencji.
3. **Data completeness:** brak luk w synchronizacji przy porównaniu zakresów czasu i liczności dokumentów.
4. **Idempotencja:** ponowny sync tego samego okna nie tworzy duplikatów.
5. **Rate-limit resilience:** 429/Retry-After skutkuje opóźnieniem, ale bez utraty danych.
6. **Observability:** dla każdego eksportu/faktury możliwy jest audyt po `referenceNumber` i statusie importu.

## 6. Ryzyka i zabezpieczenia

1. **Ryzyko błędnej interpretacji kontraktu API (zmiany RC):**
   - Mitigacja: testy kontraktowe na podstawie `open-api.json` + review changelog przy każdym release.
2. **Ryzyko mieszania środowisk i sekretów:**
   - Mitigacja: hard-separation storage per env i walidacja hosta.
3. **Ryzyko limitów i blokad:**
   - Mitigacja: centralny limiter, backoff, jitter, kolejka retry.
4. **Ryzyko niekompletności danych:**
   - Mitigacja: HWM, iteracja po `SubjectType`, deduplikacja po metadata i identyfikatorach KSeF.

## 7. Proponowana kolejność wykonania w tym repo

1. Najpierw Etap 1 + Etap 2 (fundament auth i stan sync).
2. Następnie Etap 3 (eksport + HWM).
3. Potem Etap 4 (spięcie z istniejącą logiką ERP).
4. Na końcu Etap 5 (UI + cutover).

Dzięki takiej kolejności minimalizujemy ryzyko regresji manualnego importu XML i unikamy „big bang” przełączenia.
