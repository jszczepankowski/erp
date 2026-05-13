# Ustalenia raportów

## Obowiązujące założenia

- Czas pracy (time entries) pozostaje bez zmian.
- Narzut controllingowy pozostaje bez zmian.
- Raporty korzystają z filtrów/REST/export/widoków jak dotychczas.

## Raport projektów

Wszystkie mapowania odnoszą się do sekcji **Finanse projektu**.

1. **Przychód projektu** = pole `Przychód`.
2. **Przychód czasu (referencyjnie)** = pole `Przychód z czasu pracy`, tylko dla typów: `Godzinowy` i `Mix`.
3. **Przychód łącznie**:
   - `Godzinowy` / `Mix`: `Przychód + Przychód czasu`.
   - `Abonament` / `Ryczałt`: `Przychód`.
4. **Koszt czasu (referencyjnie)** = pole `Koszt czasu pracy`.
5. **Koszt bezpośredni projektu** = pole `Koszt bezpośredni`.
6. **Koszt łącznie**:
   - `Godzinowy` / `Mix`: `Koszt bezpośredni + Koszt czasu pracy`.
   - `Abonament` / `Ryczałt`: `Koszt bezpośredni`.
7. **Zysk projektu** = pole `Zysk`.
8. **Marża %** = pole `Marża %`.
9. **Budżet %** = pole `Wykorzystanie budżetu %` tylko tam, gdzie budżet ma sens (`Ryczałt`/`Mix`).

Doprecyzowanie:
- Dla `Godzinowy` i `Mix` pole `Przychód` oznacza dodatkowe pozycje przychodowe (jeśli są).

## Raport operacyjny OMD (miesięczny)

1. **Przychód projektów** = suma pól `Przychód` z finansów projektów.
2. **Zysk godzinowy (info)** = kolumna informacyjna pomocnicza.
3. **Koszt bezpośredni projektów** = suma pól `Koszt bezpośredni`.
4. **Koszt pensji** = bez zmian.
5. **Stałe koszty** = bez zmian.
6. **Wynik operacyjny** = `Przychód projektów - Koszt bezpośredni projektów`.
7. **Narzut controllingowy** = bez zmian.
8. **Wynik controllingowy** = `Wynik operacyjny - Narzut controllingowy`.

## Dashboard

- Metryki dashboardu pozostają, ale dane do nich pochodzą z nowej, uzgodnionej logiki raportowej.
