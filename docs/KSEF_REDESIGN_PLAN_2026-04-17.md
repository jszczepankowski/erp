# KSeF API sync – redesign mechanizmu połączenia (analiza + plan wdrożeniowy)

Data: 2026-04-17

## 1) Co dziś najpewniej blokuje połączenie (na podstawie kodu i specyfikacji)

### A. Niespójny kontrakt URL (krytyczne)
W aktualnym serwisie endpointy są budowane z prefiksem `/api/v2/...` (np. `/api/v2/auth/challenge`, `/api/v2/auth/ksef-token`, `/api/v2/invoices/query/metadata`).

W dokumentacji KSeF 2.0 endpointy są opisywane jako `/auth/...`, `/invoices/...`, `/sessions/...` na hostach środowisk TEST/DEMO/PRD (wersja API wynika z hosta dokumentacji `/docs/v2`). To oznacza, że obecną konfigurację URL trzeba jednoznacznie zweryfikować i ujednolicić.

Ryzyko: przy złym prefiksie ścieżki całe flow auth i pobierania nie ruszy (4xx/404).

### B. Model pobierania niezgodny z rekomendacją KSeF
Obecny mechanizm opiera się na `query/metadata` i importuje głównie metadane.

Dokumentacja KSeF rekomenduje przyrostowe pobieranie oparte o asynchroniczny eksport paczek (`/invoices/exports`) i High Water Mark (HWM), z obsługą `IsTruncated`, `LastPermanentStorageDate`, `PermanentStorageHwmDate`.

Ryzyko: brak kompletności danych, problemy z deduplikacją, brak deterministycznej synchronizacji przy większym wolumenie.

### C. Mieszanie modeli auth (JWT access token vs token KSeF)
Wtyczka próbuje wspierać jednocześnie accessToken JWT, refreshToken i AP token, ale obecny stan utrudnia diagnostykę „co dokładnie jest źródłem autoryzacji”.

Dodatkowo, dla uwierzytelniania tokenem KSeF wymagane jest szyfrowanie `token|timestamp` algorytmem RSA-OAEP SHA-256 (MGF1) i użycie właściwego klucza publicznego KSeF dla danego środowiska.

Ryzyko: poprawny token, ale niezgodny szyfrogram / zły klucz / złe środowisko.

### D. Brak twardej separacji środowisk
Wtyczka ma jedno pole bazowego URL. To za mało operacyjnie, bo tokeny, klucze publiczne, certyfikaty i dane są środowiskowo zależne.

Ryzyko: token TEST na PRD (lub odwrotnie), klucz publiczny pobrany z innego środowiska, import „bez danych”.

### E. Brak pełnego pobierania XML faktur jako źródła prawdy
Samo metadata query nie wystarcza do solidnego importu księgowego/ERP (brak pełnej struktury dokumentu, pól branżowych, pełnej treści pozycji).

Ryzyko: import logicznie niekompletny, trudne uzgadnianie i audyt.

---

## 2) Fakty z dokumentacji KSeF, które determinują poprawny projekt

1. **Uwierzytelnianie jest obowiązkowe i daje `accessToken` JWT + `refreshToken`**.
2. **Dla tokena KSeF trzeba wykonać challenge i szyfrowanie `token|timestamp(ms)` RSA-OAEP SHA-256 (MGF1)**.
3. **Proces autoryzacji jest asynchroniczny** (`authenticationToken` + `referenceNumber`, polling statusu, dopiero `token/redeem`).
4. **KSeF ma osobne środowiska TEST/DEMO/PRD** i dane/sekrety nie są między nimi zamienne.
5. **Rekomendowany sync do systemów zewnętrznych to mechanizm przyrostowy przez asynchroniczny eksport + HWM**.
6. **Należy pobierać dane oddzielnie per rola podmiotu (`SubjectType`)**, inaczej można pominąć dokumenty.
7. **Należy obsługiwać limity i throttling (HTTP 429 + Retry-After)**.

---

## 3) Docelowy model integracji (proponowany, bez wdrożenia kodu)

## 3.1. Zasada architektoniczna
Wariant rekomendowany:

- WordPress plugin = panel + harmonogram + lokalny import,
- osobny moduł `KSeF Connector` (może być w tym samym repo jako izolowany komponent aplikacyjny) = cała komunikacja z KSeF.

Jeżeli zostajemy bez mikroserwisu, to i tak **logicznie** rozdzielamy warstwy i interfejsy jak poniżej.

## 3.2. Moduły

1. `EnvironmentResolver`
   - jawny wybór: TEST / DEMO / PRD,
   - mapowanie hostów API,
   - walidacja: token/klucz/certyfikat muszą pasować do środowiska.

2. `KsefAuthService`
   - challenge,
   - auth by KSeF token (encryptedToken) **albo** auth by XAdES,
   - polling statusu auth,
   - redeem access/refresh,
   - refresh access token,
   - rotacja i bezpieczne przechowywanie sekretów.

3. `KsefPublicKeyService`
   - pobieranie certyfikatów publicznych z endpointu security,
   - wybór certyfikatu `usage=KsefTokenEncryption`,
   - cache z TTL + fingerprint + środowisko,
   - twarda walidacja RSA i kompatybilności OAEP SHA-256.

4. `KsefInvoiceExportService`
   - inicjacja eksportu asynchronicznego (`/invoices/exports`),
   - polling statusu operacji,
   - pobranie paczek wynikowych,
   - weryfikacja integralności paczek.

5. `KsefInvoiceContentService`
   - parsing `_metadata.json`,
   - pobranie pełnej treści XML faktur (lub z paczki, zależnie od kontraktu endpointu),
   - normalizacja do modelu ERP.

6. `KsefIncrementalSyncService`
   - stan sync per `SubjectType`,
   - checkpointy HWM,
   - deduplikacja,
   - retry + backoff + circuit breaker.

7. `KsefObservability`
   - dziennik operacji (correlationId/referenceNumber),
   - metryki (czas, wolumen, błędy, 429),
   - jednoznaczne kody przyczyn błędu dla supportu.

---

## 4) Docelowy przepływ uwierzytelnienia (token KSeF)

1. `POST /auth/challenge` → `challenge`, `timestamp`.
2. Pobierz właściwy certyfikat publiczny KSeF (`usage=KsefTokenEncryption`) dla **tego samego środowiska**.
3. Zbuduj plaintext: `{ksefToken}|{timestampMs}`.
4. Szyfruj RSA-OAEP SHA-256 + MGF1 SHA-256, zakoduj Base64.
5. `POST /auth/ksef-token` z challenge, contextIdentifier, encryptedToken.
6. Odbierz `authenticationToken` + `referenceNumber`.
7. Polling `GET /auth/{referenceNumber}` aż do finalnego statusu.
8. `POST /auth/token/redeem` (Bearer `authenticationToken`) → `accessToken` + `refreshToken`.
9. Przy wygaśnięciu access token: `POST /auth/token/refresh` (Bearer `refreshToken`).

Uwaga operacyjna: przechowujemy oddzielne tokeny per środowisko, bez współdzielenia.

---

## 5) Docelowy przepływ pobierania faktur (przyrostowo, odpornie)

1. Dla każdego `SubjectType` osobno odczytaj checkpoint (`from`).
2. Inicjuj async export dla okna `[from, to)` z `restrictToPermanentStorageHwmDate=true`.
3. Polling statusu eksportu.
4. Po gotowości pobierz wszystkie części paczki i metadata.
5. Deduplikacja po stabilnych identyfikatorach (KSeF reference + hash treści).
6. Pobierz/wyodrębnij pełny XML każdej faktury i wykonaj mapowanie do ERP.
7. Zapisz wynik importu (ok/fail) per dokument.
8. Ustaw nowy checkpoint:
   - jeśli `IsTruncated=true` → `LastPermanentStorageDate`,
   - jeśli `IsTruncated=false` → `PermanentStorageHwmDate`.
9. Retry tylko dla błędów przejściowych (429/5xx/timeout), z backoff.

---

## 6) Minimalny kontrakt danych (stan synchronizacji)

Tabela `erp_omd_ksef_sync_state`:
- `environment` (TEST/DEMO/PRD),
- `company_nip`,
- `subject_type`,
- `last_hwm_at`,
- `last_reference_number`,
- `last_success_at`,
- `last_error_code`,
- `last_error_message`,
- `updated_at`.

Tabela `erp_omd_ksef_import_log`:
- `ksef_reference_number` (unique per env),
- `invoice_hash`,
- `source_subject_type`,
- `status` (imported/duplicate/failed),
- `error_code`,
- `error_payload`,
- `created_at`.

---

## 7) Plan wdrożenia etapami (bez ruszania obecnej logiki biznesowej)

## Etap 0 – diagnostyka i odcięcie ryzyk
- Dodać „dry-run connector check”: auth + 1 małe okno exportu bez importu.
- Twarde logowanie: środowisko, host, endpoint, referenceNumber, status.
- Flagi funkcjonalne: nowy connector obok starego.

## Etap 1 – nowy auth provider
- Ujednolicony `KsefAuthService`.
- Walidacja encryptedToken (OAEP SHA-256).
- Testy kontraktowe dla challenge/auth/redeem/refresh.

## Etap 2 – incremental export engine
- Implementacja `/invoices/exports` + polling + obsługa HWM.
- Obsługa limitów 429 i Retry-After.
- Checkpoint per `SubjectType`.

## Etap 3 – pełny import XML
- Rozszerzenie mapowania o pełne XML,
- walidacja krytycznych pól,
- idempotencja i deduplikacja.

## Etap 4 – przełączenie produkcyjne
- Równoległe uruchomienie (stary + nowy tylko do porównania),
- walidacja zgodności wyników,
- odcięcie starego sync.

---

## 8) Odpowiedzi na Twoje 4 punkty kontrolne

1. **Niespójność środowiska (test/prod)**
   - Tak, to jeden z najbardziej prawdopodobnych root-cause.
   - Rozwiązanie: wymusić selektor środowiska + oddzielne sekrety/klucze/checkpointy per env.

2. **Szyfrowanie encryptedToken (OAEP SHA-256)**
   - Tak, to krytyczny element.
   - Rozwiązanie: jeden kanoniczny algorytm + test wektorów + jawne logowanie parametrów kryptograficznych (bez sekretów).

3. **Token AP niepasujący do środowiska/certyfikatu**
   - Tak, bardzo częsty błąd integracyjny.
   - Rozwiązanie: walidator zgodności env (token + klucz publiczny + endpointy).

4. **Brak pełnego pobierania XML**
   - Tak, metadata to za mało dla niezawodnego importu ERP.
   - Rozwiązanie: import oparty o pełną treść XML + metadane jako indeks pomocniczy.

---

## 9) Kryteria akceptacji „to już działa poprawnie”

1. Auth tokenem KSeF przechodzi 20/20 prób na wybranym środowisku.
2. Refresh działa automatycznie bez ręcznej interwencji.
3. Sync 24h nie gubi dokumentów (porównanie liczności i identyfikatorów z KSeF).
4. Idempotencja: ponowne uruchomienie nie duplikuje importu.
5. Obsługa 429/5xx: brak utraty danych, tylko opóźnienie.
6. Możliwość pełnego audytu: dla każdej faktury mamy `referenceNumber`, źródło, status i czas.

---

## 10) Rekomendacja wykonawcza

**Tak – warto porzucić obecny mechanizm KSeF API sync i wdrożyć go od nowa**, ale warstwowo i etapami, zaczynając od:
1) nowego auth provider,
2) przyrostowego eksportu z HWM,
3) pełnego importu XML,
4) dopiero potem finalnego cutover.

To podejście minimalizuje ryzyko operacyjne i daje największą szansę na stabilny, produkcyjny import dokumentów kosztowych i sprzedażowych.
