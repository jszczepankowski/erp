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
