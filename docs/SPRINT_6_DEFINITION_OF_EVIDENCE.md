# ERP_OMD — Sprint 6 (V2) — Definition of Evidence (DoE)

Cel: jedna tabela dowodowa do formalnego zamknięcia Sprintu 6 (S6-01..S6-10) w procesie odbiorowym.

Statusy sugerowane:
- `TODO` — brak dowodów,
- `IN_REVIEW` — dowody zebrane, czekają na walidację Ownera,
- `DONE` — formalnie potwierdzone (tech + biznes).

---

## Tabela DoE per ticket

| Ticket | Zakres odbioru | Evidence techniczne (must-have) | Evidence biznesowe / manualne | Gate (kto potwierdza) | Status |
|---|---|---|---|---|---|
| **S6-01** Klasyfikacja cost/sales | Klasyfikator NIP (Nabywca/Sprzedawca), `manual_required` dla nierozpoznanych | Log/artefakt z testów klasyfikacji + parsera (`cost`, `sales`, `manual_required`) | Próbka 3 XML (cost/sales/unknown) potwierdzona przez operatora | Dev Lead + QA | IN_REVIEW *(potwierdzone w trybie manual XML; brak aktywnej integracji API KSeF: token/session/fetch)* |
| **S6-02** Idempotencja | Deduplikacja po `ksef_reference_number` + fallback `supplier_id+invoice_number` | Dowód z testów: brak duplikatu, wykrycie konfliktu fallback, audit reason | Manualny retest na tych samych danych importowych 2x | Dev Lead + QA | DONE |
| **S6-03** Retry pipeline | Retry do 90 min, potem `manual_required`, metadane prób/błędów | Dowód z testów retry + cron: próby, `last_error`, `retry_attempts`, status końcowy | Podgląd kolejki w adminie i potwierdzenie czytelności pól operacyjnych | DevOps + QA | DONE |
| **S6-04** Match dostawcy po NIP | Single/multi/no-match | Dowód z testów scenariuszy NIP match | Manualny retest na danych rzeczywistych (min. 1 przypadek każdego typu) | QA + Owner operacyjny | TODO |
| **S6-05** Moderacja UI/REST | Lista + akcje manualne + bulk + ślad auditowy | Kontrakty REST + testy fragmentów UI + test audit trail | Przejście operatora przez pełny flow (filter → action → wynik) | QA + Owner operacyjny | TODO |
| **S6-06** Import sprzedażowych | Rejestracja `sales`, mapowanie klienta po NIP, brak auto-project | E2E sales import + testy mapowania klienta | Manualny odbiór listy sprzedażowych dokumentów KSeF | QA + Owner biznesowy | DONE |
| **S6-07** Podpinanie sprzedażowej do projektu | Manual attach invoice↔project, oznaczenie końcowej, wiele faktur/projekt | Dowód z testów: attach, `is_final`, audit before/after, uprawnienia API/UI | Operator podpina 2 faktury do 1 projektu, jedna oznaczona jako końcowa | QA + Owner operacyjny | TODO |
| **S6-08** Zamykanie projektu z final invoice | Warunek `do_faktury -> zakonczony`: min. 1 końcowa | Testy regresji statusów: blokada bez finalnej, sukces z finalną | Manualny retest status transition w panelu projektów | QA + Owner biznesowy | TODO |
| **S6-09** SLA/monitoring 4h/24h | Metryki, alert 4h, raport >24h | Artefakty metryk + reguły alertów + raport dzienny naruszeń | Potwierdzenie operacyjne: kto reaguje i w jakim czasie | DevOps + Owner operacyjny | IN_REVIEW *(zakres ograniczony do importu manual XML; blocker: brak integracji API KSeF dla automatycznego fetch/sync)* |
| **S6-10** UAT + release closure | UAT checklist + release notes + closure | Linki do checklisty, release notes i closure doc w `docs/` | Podpis Ownera biznesowego (PASS / PASS warunkowy) | Owner biznesowy + PM | IN_REVIEW *(warunkowo: UAT bez pełnego E2E z KSeF API; wymagane domknięcie modułu token/session/fetch)* |

---

## Minimalny pakiet artefaktów do jednego „closure PR”

1. Aktualizacja `docs/SPRINT_6_TICKETS_DOD.md` (odhaczenie DoD po ticketach).  
2. Uzupełniona tabela DoE (ten dokument) ze statusem `DONE` oraz linkami do dowodów.  
3. `docs/RELEASE_CLOSURE_SPRINT_6_<YYYY-MM-DD>.md` z:
   - listą wdrożonych ticketów,
   - checklistą regresji,
   - listą otwartych ryzyk (jeśli są),
   - decyzją Go/No-Go.  
4. Wynik UAT podpisany przez Ownera (`PASS` / `PASS WARUNKOWY` / `FAIL`) z datą.

---

## Sugerowany workflow domknięcia (1 przebieg)

1. **Tech freeze Sprint 6** (brak nowych feature’ów poza fixami blockerów).  
2. **Run test gate** (unit/integracyjne/regresyjne).  
3. **Uzupełnienie DoE** ticket po tickecie (evidence links + status).  
4. **Manual UAT** wg checklisty i podpis Ownera.  
5. **Closure PR** (DoD + DoE + release closure).  
6. **Tag/release** po akceptacji.

---

## Dziennik potwierdzeń (Owner/Operator)

- 2026-04-16: Potwierdzono `S6-02 Idempotencja` = **OK**.
- 2026-04-16: Potwierdzono `S6-03 Retry pipeline` = **OK**.
- 2026-04-16: Zidentyfikowano blocker dla domknięcia E2E: brak aktywnej integracji API KSeF (`token/session/fetch`), dostępny jedynie tryb manual XML.
