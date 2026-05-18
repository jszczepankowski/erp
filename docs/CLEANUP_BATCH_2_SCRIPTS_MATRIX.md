# Cleanup Batch 2 — macierz decyzji dla `scripts/*`

Data: 2026-05-13
Zakres: skrypty sprintowe build/test.

| Plik | Typ | Decyzja | Uzasadnienie |
|---|---|---|---|
| scripts/build-sprint-1-zip.sh | build | usunąć | historyczny artefakt pierwszych wersji systemu |
| scripts/build-sprint-2-zip.sh | build | usunąć | jw. |
| scripts/build-sprint-3-zip.sh | build | usunąć | jw. |
| scripts/build-sprint-4-zip.sh | build | usunąć | jw. |
| scripts/build-sprint-5-zip.sh | build | usunąć | jw. |
| scripts/build-sprint-6-zip.sh | build | usunąć | jw. |
| scripts/build-sprint-7-zip.sh | build | usunąć | jw. |
| scripts/build-sprint-8-rc.sh | build | usunąć | jw. |
| scripts/build-sprint-9.sh | build | usunąć | jw. |
| scripts/build-sprint-10.sh | build | usunąć | jw. |
| scripts/test-sprint-3.sh | test | usunąć | skrypt jednorazowy dla konkretnego sprintu |
| scripts/test-sprint-4.sh | test | usunąć | jw. |
| scripts/test-sprint-5.sh | test | usunąć | jw. |
| scripts/test-sprint-6.sh | test | usunąć | jw. |
| scripts/test-sprint-7.sh | test | usunąć | jw. |
| scripts/test-sprint-8.sh | test | usunąć | jw. |
| scripts/test-sprint-9.sh | test | usunąć | jw. |
| scripts/test-sprint-10.sh | test | usunąć | jw. |

## Podsumowanie decyzji
- **usunąć:** 18
- **przenieść:** 0
- **zostawić:** 0

## Plan wykonania cleanupu
- Krok 1: usunąć skrypty historyczne `scripts/*sprint*.sh`.
- Krok 2: zaktualizować dokumentację, jeśli wskazuje stare ścieżki.
