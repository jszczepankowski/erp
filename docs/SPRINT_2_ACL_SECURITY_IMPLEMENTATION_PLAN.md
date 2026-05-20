# Sprint 2 — ACL, bezpieczeństwo i widoczność ekranów (plan wdrożenia)

Data: 2026-05-19  
Status: gotowy do realizacji

## Cel sprintu
Wdrożyć granularny model dostępu **per użytkownik** z audytem zmian oraz twardą walidacją backendową.  
Dodatkowo: dodać możliwość konfiguracji **widoczności ekranów i pozycji menu** dla konkretnego użytkownika.

---

## Założenia architektoniczne

1. **Backend jest źródłem prawdy**
   - Widoczność menu to warstwa UX.
   - Autoryzacja akcji i endpointów musi być egzekwowana capability checkami.

2. **Model dostępu**
   - rola bazowa (WordPress role/caps),
   - wyjątki per user (allow/deny),
   - reguła rozstrzygania: `deny > allow > inherited`.

3. **Rozdział odpowiedzialności**
   - ACL capability = możliwość wykonania operacji,
   - menu/page visibility = możliwość zobaczenia wejścia do ekranu.

---

## Etap 1 — Silnik ACL per user (bez UI)

### Zakres
- Dodać serwis `ERP_OMD_Acl_Service` (lub analogiczny), który zwraca efektywne decyzje:
  - `can_user($user_id, $capability, array $context = []): bool`
  - `can_view_menu_page($user_id, $page_slug): bool`
- Dodać magazyn override per użytkownik:
  - `user_capability_overrides` (allow/deny),
  - `user_menu_visibility_overrides` (allow/deny).

### Kryteria akceptacji
- Serwis potrafi policzyć finalną decyzję na podstawie roli + override.
- Reguła `deny > allow > inherited` działa deterministycznie.

### Testy
- jednostkowe testy reguł ACL (przypadki pozytywne/negatywne).

---

## Etap 2 — Integracja z menu i ekranami admina

### Zakres
- Zastosować `can_view_menu_page()` w rejestracji menu/submenu (`class-admin-runtime.php`).
- Zachować istniejące capability requirements przy `add_submenu_page`.
- Dla bezpośredniego wejścia po URL dodać gatekeeper (brak dostępu jeśli capability lub visibility blokuje ekran).

### Kryteria akceptacji
- Użytkownik widzi tylko dozwolone pozycje menu.
- Próba wejścia bez dostępu zwraca czytelny komunikat „Brak uprawnień”.

### Testy
- testy integracyjne: widoczność menu dla 2–3 profili użytkownika,
- testy negatywne wejścia po URL.

---

## Etap 3 — UI konfiguracji uprawnień i widoczności

### Zakres
- Dodać ekran „Uprawnienia użytkownika”:
  - rola bazowa (read-only lub editable wg polityki),
  - override capability (allow/deny/reset),
  - widoczność menu/ekranów (allow/deny/reset).
- Dodać presety (opcjonalnie) dla szybkiego przypisania zestawu dostępu.

### Kryteria akceptacji
- Administrator/super-admin może zapisać override dla usera.
- Zmiana jest aktywna po odświeżeniu (bez potrzeby deployu/restartu).

### Testy
- testy formularza i walidacji danych wejściowych,
- testy ochrony CSRF (nonce).

---

## Etap 4 — Audyt zmian uprawnień

### Zakres
- Dodać dziennik zmian ACL:
  - kto zmienił (`actor_user_id`),
  - komu (`target_user_id`),
  - kiedy,
  - co (before/after),
  - typ zmiany (`capability_override`, `menu_override`, `role_change`).
- Dodać listę audytu z filtrem po użytkowniku i dacie.

### Kryteria akceptacji
- Każda zmiana ACL i visibility zostawia wpis audytowy.
- Wpisy są nieedytowalne (append-only).

### Testy
- testy tworzenia wpisu dla każdego typu zmiany,
- testy uprawnień dostępu do audytu.

---

## Etap 5 — Hardening i walidacje bezpieczeństwa

### Zakres
- Ujednolicić `require_capability` dla akcji krytycznych:
  - finansowe,
  - statusy projektów,
  - bulk actions,
  - eksporty,
  - ustawienia.
- Walidacje ochronne:
  - whitelist capability keys i page slugs,
  - blokada privilege escalation,
  - ochrona przed self-lockout (ostatni admin krytyczny).

### Kryteria akceptacji
- Co najmniej 80% ekranów admina respektuje ACL (zgodnie z ERP_v4.2).
- Krytyczne operacje nie są możliwe do wykonania przy bypassie UI.

### Testy
- testy regresji endpointów REST (`permission_callback`),
- testy negatywne dla operacji krytycznych.

---

## Zakres capability i ekranów do pierwszej iteracji

### Capability (minimum)
- `erp_omd_manage_settings`
- `erp_omd_manage_roles`
- `erp_omd_manage_employees`
- `erp_omd_manage_clients`
- `erp_omd_manage_projects`
- `erp_omd_manage_time`
- `erp_omd_approve_time`

### Ekrany/menu (minimum)
- `erp-omd` (Dashboard)
- `erp-omd-private-tasks`
- `erp-omd-employees`
- `erp-omd-roles`
- `erp-omd-clients`
- `erp-omd-projects`
- `erp-omd-time`
- `erp-omd-calendar`
- `erp-omd-cost-invoices`
- `erp-omd-reports`
- `erp-omd-alerts`
- `erp-omd-settings`

---

## Definition of Done (Sprint 2)
- ACL per user działa dla kluczowych capabilities i ekranów.
- Widoczność menu/ekranów jest konfigurowalna per użytkownik.
- Audyt zmian uprawnień i widoczności jest dostępny dla super-admin.
- Krytyczne operacje backend są chronione capability checkami i walidacjami.
- Testy jednostkowe/integracyjne ACL przechodzą.
