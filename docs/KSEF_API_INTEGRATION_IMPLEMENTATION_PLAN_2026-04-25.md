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

## 8. Etap 1 — pełna rozpiska wykonawcza (checklista implementacyjna)

Poniżej jest rozpisany **dokładny plan realizacji Etapu 1** tak, aby można było wejść od razu w development i review.

### 8.1 Cel Etapu 1

Dostarczyć stabilną, testowalną warstwę auth KSeF, która zapewnia:
1. `challenge`,
2. rozpoczęcie autoryzacji (token KSeF / alternatywnie XAdES),
3. polling statusu autoryzacji,
4. wymianę tokenów (`redeem`),
5. odświeżanie tokenu (`refresh`),
6. bezpieczne przechowywanie sekretów i tokenów per środowisko.

### 8.2 Zakres kodu (MVP Etapu 1)

**Nowe klasy/pliki:**
1. `erp-omd/includes/services/class-ksef-auth-service.php`
2. `erp-omd/includes/services/class-ksef-auth-storage.php`
3. `erp-omd/includes/services/class-ksef-public-key-service.php`
4. `erp-omd/includes/contracts/interface-ksef-auth-provider.php`
5. `tests/ksef-auth-service-test.php`
6. `tests/ksef-auth-storage-test.php`

**Modyfikacje istniejących plików:**
1. `erp-omd/includes/class-autoloader.php` (rejestracja nowych klas)
2. `erp-omd/includes/services/class-ksef-connector.php` (opcjonalnie: jawna obsługa metod HTTP i nagłówków)
3. `erp-omd/includes/class-admin.php` + `erp-omd/templates/admin/settings.php` (minimalny panel diagnostyczny Etapu 1)

### 8.3 Kolejność prac (task-by-task)

#### Krok 1: Kontrakty i DTO
1. Dodać `interface-ksef-auth-provider.php` z metodami:
   - `get_challenge($environment)`
   - `authenticate_with_ksef_token($environment, $ksef_token, $context_identifier)`
   - `get_auth_status($environment, $reference_number, $authentication_token)`
   - `redeem_token($environment, $authentication_token)`
   - `refresh_access_token($environment, $refresh_token)`
2. Zdefiniować spójny format odpowiedzi tablicowych:
   - `ok`, `code`, `data`, `error_code`, `error_message`, `retry_after`.

#### Krok 2: Storage tokenów i sekretów
1. Wydzielić `class-ksef-auth-storage.php`.
2. Wprowadzić klucze opcji per env, np.:
   - `erp_omd_ksef_auth_test`,
   - `erp_omd_ksef_auth_demo`,
   - `erp_omd_ksef_auth_prod`.
3. Zapisywać minimum:
   - `access_token`, `refresh_token`,
   - `access_expires_at`, `refresh_expires_at`,
   - `updated_at`, `token_type`.
4. Dodać helpery:
   - `save_tokens($env, array $tokens)`
   - `get_tokens($env)`
   - `clear_tokens($env)`.

#### Krok 3: Public key + encrypted token
1. Dodać `class-ksef-public-key-service.php`:
   - pobranie certyfikatu/klucza dla środowiska,
   - cache z TTL,
   - kontrola fingerprint.
2. Dodać helper szyfrowania tokenu KSeF zgodny ze spec:
   - budowa plaintext `token|timestamp_ms`,
   - RSA-OAEP SHA-256 + MGF1,
   - Base64 output.
3. Dodać walidację błędów kryptograficznych z czytelnymi kodami błędów.

#### Krok 4: Implementacja `class-ksef-auth-service.php`
1. Wstrzykiwane zależności:
   - `ERP_OMD_KSeF_Connector`,
   - `ERP_OMD_KSeF_Auth_Storage`,
   - `ERP_OMD_KSeF_Public_Key_Service`.
2. Implementacja metod:
   - `get_challenge()` -> request do `/auth/challenge`, walidacja TTL,
   - `authenticate_with_ksef_token()` -> generacja encryptedToken i request auth,
   - `get_auth_status()` -> polling statusu,
   - `redeem_token()` -> zapis `access/refresh` do storage,
   - `refresh_access_token()` -> update storage + fallback na re-auth, gdy refresh wygasł.
3. Dodać metodę orchestration:
   - `ensure_access_token($env)`
   - logika: jeśli access ważny -> zwróć; jeśli wygasł -> refresh; jeśli refresh nie działa -> pełny auth.

#### Krok 5: Hardening błędów i limitów
1. Ujednolicić mapowanie błędów HTTP:
   - 400/401/403 -> auth failure,
   - 404/405 -> kontrakt endpointu,
   - 429 -> rate limited (`retry_after`),
   - 5xx -> transient upstream error.
2. Dodać retry tylko dla transient/rate-limit (nie dla 4xx biznesowych).
3. Dodać correlation fields do logów:
   - `environment`, `referenceNumber`, `request_id`, `phase`.

#### Krok 6: Integracja z panelem admina (minimalna)
1. W `settings.php` dodać sekcję „KSeF Auth Diagnostics”:
   - aktywne środowisko,
   - status tokenu (valid/expired/missing),
   - timestamp ostatniego odświeżenia.
2. W `class-admin.php` dodać akcję ręczną:
   - „Sprawdź połączenie auth (dry-run)”.
3. Pokazać bezpieczny komunikat (bez wycieku tokenów).

#### Krok 7: Testy (must-have)
1. `tests/ksef-auth-service-test.php`:
   - challenge success/fail,
   - auth + redeem success,
   - refresh success,
   - refresh fail -> fallback re-auth,
   - 429 -> retry_after,
   - 5xx -> retryable error.
2. `tests/ksef-auth-storage-test.php`:
   - separacja danych per env,
   - save/get/clear,
   - brak wycieku między TEST/DEMO/PRD.
3. Test regresji autoloadera po dodaniu klas.

### 8.4 Definition of Done dla Etapu 1

Etap 1 uznajemy za zamknięty, gdy:
1. wszystkie nowe testy Etapu 1 przechodzą lokalnie,
2. `ensure_access_token($env)` działa stabilnie dla min. 20 kolejnych wywołań,
3. panel diagnostyczny pokazuje poprawny status tokenów,
4. logi zawierają wystarczające informacje do supportu (bez sekretów),
5. nie ma regresji w istniejącym manualnym imporcie XML.

### 8.5 Plan wykonania dzień po dniu

**Dzień 1:** kontrakty + storage + autoloader.  
**Dzień 2:** public key + encrypted token + auth methods.  
**Dzień 3:** refresh/orchestration + błędy/limity + logowanie.  
**Dzień 4:** UI diagnostyczne + testy + poprawki po review.

### 8.6 Następny krok po Etapie 1

Po zamknięciu Etapu 1 od razu przechodzimy do Etapu 2 (stan synchronizacji i harmonogram), bo bez tego nie da się uruchomić bezpiecznego pipeline'u eksportowego opartego o HWM.

## 9. Nazwa funkcjonalności i nazwy działań wdrożeniowych

Żeby łatwiej prowadzić development, testy i komunikację (ticketing/release notes), proponuję spójne nazewnictwo.

### 9.1 Nazwa główna funkcjonalności

**KSeF Sync Hub**

Krótki opis produktu: moduł odpowiedzialny za bezpieczne uwierzytelnianie do KSeF, przyrostowe pobieranie dokumentów oraz kontrolowany import do ERP OMD.

**Alias techniczny (prefix):** `ksef_sync_hub`

Przykładowe użycie aliasu:
- feature flag: `erp_omd_ksef_sync_hub_enabled`
- tryb pracy: `erp_omd_ksef_sync_hub_mode`
- log channel: `ksef_sync_hub`

### 9.2 Nazwy strumieni działań (workstreams)

1. **WS1 – KSeF Sync Hub / Auth Core**
   - zakres: challenge, auth, redeem, refresh, storage tokenów.
2. **WS2 – KSeF Sync Hub / State & Scheduler**
   - zakres: sync state, cron, lock, retry policy.
3. **WS3 – KSeF Sync Hub / Export Engine**
   - zakres: `/invoices/exports`, polling, HWM, batch processing.
4. **WS4 – KSeF Sync Hub / ERP Mapping**
   - zakres: mapowanie danych i integracja z `ERP_OMD_KSeF_Import_Service`.
5. **WS5 – KSeF Sync Hub / Ops Console**
   - zakres: UI diagnostyczne, observability, metryki, alerting.

### 9.3 Nazwy działań wdrożeniowych (milestones)

1. **M1: Auth Ready**
   - ukończony Etap 1 (stabilne logowanie i odświeżanie tokenów).
2. **M2: Sync Ready**
   - ukończony Etap 2 (stan synchronizacji + harmonogram + lock).
3. **M3: Export Ready**
   - ukończony Etap 3 (eksport paczek + HWM).
4. **M4: Import Ready**
   - ukończony Etap 4 (pełne spięcie z workflow ERP).
5. **M5: Go-Live Ready**
   - ukończony Etap 5 (UI operacyjne + cutover).

### 9.4 Nazwy ticketów (proponowany standard)

Format:
`[KSeF Sync Hub][WSx][Mx] Krótki opis`

Przykłady:
1. `[KSeF Sync Hub][WS1][M1] Implementacja class-ksef-auth-service.php`
2. `[KSeF Sync Hub][WS1][M1] Dodanie tests/ksef-auth-service-test.php`
3. `[KSeF Sync Hub][WS3][M3] Obsługa LastPermanentStorageDate i HWM`
4. `[KSeF Sync Hub][WS5][M5] Dashboard diagnostyczny KSeF w settings.php`

### 9.5 Nazwy release’ów (proponowany standard)

- `KSH-Alpha` – zakończony M1
- `KSH-Beta` – zakończone M1–M3
- `KSH-RC` – zakończone M1–M5, start walidacji przedprodukcyjnej
- `KSH-GA` – produkcyjne uruchomienie

Dzięki temu nazewnictwu wszystkie prace będą łatwe do śledzenia end-to-end: od ticketów, przez commity i PR, po checklisty wdrożeniowe i runbook operacyjny.
