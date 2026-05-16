# Prompt startowy — realizacja Sprintu 6 (V2)

Skopiuj poniższy prompt do nowego chatu.

---

Jesteśmy w repo ERP_OMD. Realizujemy **Sprint 6 (V2)** wg uzgodnionego scope i dokumentu:
- `docs/SPRINT_6_TICKETS_DOD.md`

## Kontekst biznesowy
- Sprint 5 jest zamknięty.
- Owner akceptacji: Admin.
- Priorytet: balans (value + jakość operacyjna).
- SLA: target 4h, hard 24h.
- Retry automatyczny do 90 min + manual fallback.

## Kluczowe reguły (nie zmieniaj bez potwierdzenia)
1. Klasyfikacja dokumentu KSeF:
   - kosztowa: NIP naszej firmy po stronie Nabywcy,
   - sprzedażowa: NIP naszej firmy po stronie Sprzedawcy.
2. Idempotencja:
   - primary: `ksef_reference_number`,
   - fallback: `supplier_id + invoice_number`.
3. Duplikaty/conflicty:
   - blokada automatyki + manual moderation queue.
4. Kosztowe:
   - auto-match dostawcy po NIP (single match),
   - projekt może być pusty przy imporcie,
   - przypięcie projektu manualne.
5. Sprzedażowe:
   - mapowanie klienta po NIP na liście,
   - przypięcie projektu tylko manualnie.
6. Do projektu może być przypięta więcej niż jedna faktura.
7. Mechanizm zamykania projektu:
   - wykorzystaj istniejący mechanizm (reuse), nie buduj równoległego.
   - nowy warunek: podpięta faktura końcowa.

## Sposób pracy
1. Zrealizuj tickety zgodnie z kolejnością z `docs/SPRINT_6_TICKETS_DOD.md`.
2. Po każdym większym kroku:
   - pokaż diff,
   - uruchom testy adekwatne do zmiany,
   - krótko opisz ryzyka.
3. Jeśli musisz odstąpić od założeń, zatrzymaj się i poproś o decyzję.

## Oczekiwany format raportowania
- Sekcja „Done in this step”
- Sekcja „Tests run”
- Sekcja „Open decisions (if any)”
- Sekcja „Next step”

Zacznij od realizacji:
1) S6-01, 2) S6-02, 3) S6-03.

---
