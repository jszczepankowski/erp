# ERP_OMD V2 — Sprint 8 Checklist

## Zakres Sprintu 8
- Hardening produkcyjny i finalizacja API REST.
- Endpointy końcowe dla alertów, załączników, meta i statusu systemu.
- UX/admin polish dla release candidate.
- Finalna paczka `dist/erp-omd-sprint-8-rc.zip`.
- Regresja skryptów build/test i naprawa uprawnień wykonywania dla skryptów Sprintu 7/8.

## Paczka release candidate
- ZIP wynikowy: `dist/erp-omd-sprint-8-rc.zip`
- Generator ZIP: `./scripts/build-sprint-8-rc.sh`

## Automatyczne sanity checki
1. Uruchom `./scripts/test-sprint-8.sh`.
2. Potwierdź:
   - lint wszystkich plików PHP,
   - testy time trackingu,
   - test agregatora finansowego,
   - test modułu kosztorysów,
   - test modułu raportowania,
   - test modułu alertów,
   - test endpointów REST / hardening,
   - budowę ZIP Sprintu 8 RC.

## Kroki odbioru
1. Zbuduj `dist/erp-omd-sprint-8-rc.zip`.
2. Zainstaluj lub zaktualizuj plugin w WordPress.
3. Zweryfikuj dashboard i ustawienia — powinny pokazywać status Sprintu 8 RC.
4. Sprawdź REST API:
   - `GET /wp-json/erp-omd/v1/meta`
   - `GET /wp-json/erp-omd/v1/system`
   - `GET /wp-json/erp-omd/v1/alerts`
   - `GET /wp-json/erp-omd/v1/attachments?entity_type=project&entity_id={id}`
5. Dodaj i usuń załącznik przez admin UI oraz REST API.
6. Potwierdź brak regresji na ekranach: Pracownicy, Klienci, Projekty, Kosztorysy, Czas pracy, Raporty, Alerty, Ustawienia.
7. Potwierdź, że skrypty `build/test` Sprintu 7 i 8 uruchamiają się bez błędów uprawnień.
