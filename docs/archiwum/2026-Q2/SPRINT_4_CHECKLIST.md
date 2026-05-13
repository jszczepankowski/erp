# ERP_OMD V2 — Sprint 4 Checklist

## Zakres Sprintu 4
- Wszystko ze Sprintów 1–3 bez regresji.
- Migracje DB dla `project_costs` i `project_financials`.
- Cache finansowy projektu liczony real-time.
- Obsługa kosztów projektu w UI admina.
- Widok finansowy projektu z metrykami:
  - `revenue`,
  - `cost`,
  - `profit`,
  - `margin`,
  - `budget_usage`.
- REST API dla:
  - `projects/{id}/costs`,
  - `project-costs/{id}`,
  - `projects/{id}/finance`.

## Paczka sprintu
- ZIP wynikowy: `dist/erp-omd-sprint-4.zip`
- Generator ZIP: `./scripts/build-sprint-4-zip.sh`

## Automatyczne sanity checki
1. Uruchom `./scripts/test-sprint-4.sh`.
2. Potwierdź:
   - lint wszystkich plików PHP,
   - test time trackingu,
   - test agregatora finansowego,
   - budowę ZIP Sprintu 4.

## Kroki odbioru
1. Zbuduj `dist/erp-omd-sprint-4.zip`.
2. Zainstaluj lub zaktualizuj plugin w WordPress.
3. Otwórz istniejący projekt i sprawdź sekcję finansową.
4. Dodaj koszt projektu.
5. Potwierdź, że metryki finansowe aktualizują się natychmiast.
6. Dodaj lub zmień wpis czasu dla projektu.
7. Potwierdź, że finanse projektu przeliczają się bez ręcznego cron/job.
8. Sprawdź endpoint `GET /wp-json/erp-omd/v1/projects/{id}/finance`.
9. Sprawdź endpointy kosztów projektu:
   - `GET /wp-json/erp-omd/v1/projects/{id}/costs`
   - `POST /wp-json/erp-omd/v1/projects/{id}/costs`
   - `PATCH /wp-json/erp-omd/v1/project-costs/{id}`
   - `DELETE /wp-json/erp-omd/v1/project-costs/{id}`

## Oczekiwany rezultat
- Koszt projektu aktualizuje finanse od razu po zapisie.
- Zmiana wpisu czasu aktualizuje finanse od razu po zapisie.
- Profil projektu pokazuje aktualne wskaźniki finansowe.
