# ERP_OMD — Sprint 6 (V2) — Tickets + Definition of Done

Data bazowa: 2026-04-15  
Owner biznesowy: **Admin**  
Priorytet: **balans** (time-to-value + jakość operacyjna)  
SLA: **target 4h**, **hard 24h**, retry automatyczny do **90 min** + manual fallback.

Dokument operacyjny do odbioru: `docs/SPRINT_6_DEFINITION_OF_EVIDENCE.md`.

---

## 0) Założenia i kontekst (uzgodnione)

1. Rozróżnienie dokumentu KSeF:
   - kosztowa: NIP naszej firmy po stronie **Nabywcy**,
   - sprzedażowa: NIP naszej firmy po stronie **Sprzedawcy**.
2. Idempotencja:
   - primary key: `ksef_reference_number`,
   - fallback: `supplier_id + invoice_number`.
3. Konflikty/duplikaty:
   - blokada automatyki + kolejka moderacji manualnej.
4. Kosztowe:
   - dostawca auto-match po NIP (single match),
   - projekt może być pusty przy imporcie,
   - przypięcie projektu manualne.
5. Sprzedażowe:
   - mapowanie po NIP do klienta na liście,
   - przypięcie projektu tylko manualnie.
6. Do projektu może być przypięta więcej niż jedna faktura.
7. Mechanizm zamykania projektu:
   - **reuse istniejącej logiki**; bez budowy równoległego silnika statusów.

---

## 1) Sprint 6 — konkretne tickety

> Skala estymacji: S (<=0.5d), M (1d), L (2d), XL (3-4d)

### EPIC A — Import i klasyfikacja KSeF

### S6-01 (M): Klasyfikacja dokumentu KSeF (kosztowa/sprzedażowa)
**Zakres**
- Implementacja klasyfikatora po roli NIP naszej firmy (`Nabywca` vs `Sprzedawca`).
- Obsługa wersji struktury FA(2)/FA(3) na poziomie parsera mapowania.

**DoD**
- [ ] Dla payloadów testowych kosztowych klasyfikator zwraca `cost`.
- [ ] Dla payloadów testowych sprzedażowych klasyfikator zwraca `sales`.
- [ ] Brak rozpoznania = status `manual_required` + czytelny błąd.
- [ ] Testy jednostkowe parsera i klasyfikacji przechodzą.

---

### S6-02 (L): Idempotencja importu (C: reference + fallback)
**Zakres**
- Deduplikacja po `ksef_reference_number`.
- Fallback deduplikacji po `supplier_id + invoice_number`.
- Obsługa konfliktu jako kolejka manualna.

**DoD**
- [ ] Duplikat po `ksef_reference_number` nie tworzy nowego rekordu.
- [ ] Duplikat fallbackowy wykrywany i oznaczany jako konflikt manualny.
- [ ] W logu/audycie zapisany powód konfliktu.
- [ ] Testy regresyjne idempotencji przechodzą.

---

### S6-03 (M): Retry pipeline KSeF (90 min + manual)
**Zakres**
- Retry scheduler dla błędów pobrania/mapowania.
- Strategia: auto retry do 90 min, potem `manual_required`.

**DoD**
- [ ] Błąd importu uruchamia retry zgodnie z harmonogramem.
- [ ] Po 90 min bez sukcesu rekord ma status `manual_required`.
- [ ] Widoczne są: liczba prób, ostatni błąd, timestamp.
- [ ] Testy zachowania retry przechodzą.

---

### EPIC B — Matchowanie i moderacja kosztowych

### S6-04 (M): Matchowanie dostawcy po NIP
**Zakres**
- Normalizacja NIP.
- Single match => auto-przypisanie dostawcy.
- Multi/no match => blokada i manual.

**DoD**
- [ ] Single match poprawnie ustawia `supplier_id`.
- [ ] Multi-match ustawia konflikt i nie przypisuje automatycznie.
- [ ] No-match ustawia status do ręcznej moderacji.
- [ ] Testy scenariuszy NIP match przechodzą.

---

### S6-05 (L): UI/REST kolejki moderacji KSeF
**Zakres**
- Widok listy importów: `new`, `conflict`, `manual_required`, `ready`.
- Akcje manualne: przypisz dostawcę, przypisz projekt, zatwierdź, odrzuć.
- Filtrowanie i podstawowe bulk-actions.

**DoD**
- [ ] Operator widzi wszystkie rekordy wymagające akcji.
- [ ] Każda akcja manualna odkłada ślad auditowy.
- [ ] Endpointy REST wspierają filtrowanie i akcje moderacji.
- [ ] Testy kontraktowe endpointów i fragmentów UI przechodzą.

---

### EPIC C — Strumień sprzedażowy i final invoice

### S6-06 (L): Import faktur sprzedażowych z KSeF
**Zakres**
- Rejestracja dokumentów `sales`.
- Mapowanie klienta po NIP na liście.
- Brak auto-przypięcia projektu.

**DoD**
- [x] Sprzedażowe trafiają do dedykowanego widoku/listy.
- [x] Klient jest mapowany automatycznie po NIP (jeśli jednoznaczny).
- [x] Projekt pozostaje pusty do manualnego przypięcia.
- [x] Testy E2E importu sprzedażowych przechodzą.

---

### S6-07 (M): Manualne podpinanie faktury sprzedażowej do projektu
**Zakres**
- Akcja manualnego powiązania invoice ↔ project.
- Oznaczenie faktury jako „końcowa” dla projektu.

**DoD**
- [ ] Operator może przypiąć sprzedażową do projektu z UI/API.
- [ ] Audit zawiera user/time/before-after.
- [ ] Możliwe jest przypięcie więcej niż jednej faktury do projektu.
- [ ] Testy walidacji i uprawnień przechodzą.

---

### S6-08 (M): Reuse mechanizmu zamykania projektu + warunek final invoice
**Zakres**
- Rozszerzenie istniejącej walidacji przejścia `do_faktury -> zamknięty`.
- Warunek: co najmniej jedna podpięta faktura końcowa.

**DoD**
- [ ] Bez podpiętej końcowej przejście jest blokowane.
- [ ] Z podpiętą końcową przejście działa.
- [ ] Istniejąca logika statusów pozostaje pojedynczym źródłem prawdy.
- [ ] Testy regresyjne statusów projektu przechodzą.

---

### EPIC D — Operacyjka, metryki i release

### S6-09 (M): Monitoring i alerty SLA (4h/24h)
**Zakres**
- Metryki pipeline: imported/failed/manual_required/conflict.
- Alert przy przekroczeniu target SLA 4h.
- Raport dzienny naruszeń hard SLA 24h.

**DoD**
- [ ] Metryki są dostępne w logu/raporcie admin.
- [ ] Alerty dla przekroczenia 4h działają.
- [ ] Zestawienie przypadków >24h jest generowane.

---

### S6-10 (S): UAT, release notes i closure sprintu
**Zakres**
- Checklista UAT Sprintu 6.
- Notatka release + closure doc.

**DoD**
- [ ] UAT checklist wykonana i podpisana przez ownera.
- [ ] Release notes + closure dodane do `docs/`.
- [ ] Brak blockerów krytycznych P1/P2.

---

## 2) Kolejność realizacji (rekomendowana)

1. S6-01  
2. S6-02  
3. S6-03  
4. S6-04  
5. S6-05  
6. S6-06  
7. S6-07  
8. S6-08  
9. S6-09  
10. S6-10

---

## 3) Minimalny pakiet testów na gate merge

1. Unit:
- klasyfikacja koszt/sprzedaż,
- idempotencja (primary + fallback),
- NIP match.

2. Integracyjne:
- import kosztowych + moderacja + przypięcie projektu,
- import sprzedażowych + mapowanie klienta + manualne przypięcie projektu.

3. Regresja:
- workflow faktur kosztowych (Sprint 5),
- statusy projektu i warunek przejścia do `zamknięty`,
- REST route contracts.

---

## 4) Kryterium zakończenia Sprintu 6

Sprint 6 uznajemy za zakończony, gdy:
- F1/F2 działają produkcyjnie dla strumienia kosztowego i sprzedażowego,
- mechanizm zamykania projektu wykorzystuje istniejącą logikę i nowy warunek final invoice,
- SLA i observability są wdrożone,
- UAT zakończony i podpisany przez ownera (Admin).
