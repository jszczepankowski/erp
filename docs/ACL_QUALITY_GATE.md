# ACL Quality Gate (stały krok)

Ten krok jest **obowiązkowy** przy każdym merge/release obejmującym:
- uprawnienia użytkowników,
- ACL overrides,
- endpointy `/acl-audit*`,
- gatekeeping ekranów admina.

## Minimalny zestaw komend

1. `php tests/acl-service-test.php`
2. `php tests/rest-acl-behavior-test.php`
3. `php tests/admin-acl-coverage-test.php`

## Kryteria przejścia

- wszystkie 3 komendy kończą się bez błędu,
- `tests/admin-acl-coverage-test.php` raportuje pokrycie `>= 80%`,
- zmiany ACL nie mogą obniżać dostępu do pełnego audytu dla super-admin.

## Dodatkowe kroki operacyjne (po wdrożeniu)

1. **Backfill ACL audit (legacy option -> tabela)**
   - potwierdź obecność markera `erp_omd_acl_audit_backfill_done=1`,
   - porównaj orientacyjnie liczność wpisów historycznych przed/po migracji.

2. **Kontrakt eksportu CSV ACL**
   - endpoint: `GET /erp-omd/v1/acl-audit/export`,
   - wymagany nagłówek CSV:
     `changed_at,actor_user_id,target_user_id,change_type,before_capability_overrides,after_capability_overrides,before_menu_overrides,after_menu_overrides`,
   - daty filtrów `changed_from`/`changed_to` podawaj w czasie serwera WordPress.
