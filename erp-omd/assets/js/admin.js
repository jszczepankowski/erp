document.addEventListener('DOMContentLoaded', () => {
  const tables = document.querySelectorAll('.erp-omd-admin table.widefat');

  tables.forEach((table, tableIndex) => {
    const body = table.tBodies[0];
    const rows = Array.from(body ? body.rows : []);
    if (!body || rows.length === 0) {
      return;
    }

    const controls = document.createElement('div');
    controls.className = 'erp-omd-table-tools';

    const search = document.createElement('input');
    search.type = 'search';
    search.className = 'regular-text';
    search.placeholder = 'Filtruj tabelę…';
    search.setAttribute('aria-label', 'Filtruj tabelę');
    controls.appendChild(search);
    table.parentNode.insertBefore(controls, table);

    const headers = Array.from(table.querySelectorAll('thead th'));
    let currentSort = { index: -1, direction: 'asc' };

    const getCellValue = (row, index) => {
      const cell = row.cells[index];
      return cell ? cell.textContent.trim() : '';
    };

    const compareValues = (a, b, direction) => {
      const numberA = Number.parseFloat(a.replace(',', '.'));
      const numberB = Number.parseFloat(b.replace(',', '.'));
      const bothNumbers = !Number.isNaN(numberA) && !Number.isNaN(numberB);

      if (bothNumbers) {
        return direction === 'asc' ? numberA - numberB : numberB - numberA;
      }

      return direction === 'asc'
        ? a.localeCompare(b, 'pl', { numeric: true, sensitivity: 'base' })
        : b.localeCompare(a, 'pl', { numeric: true, sensitivity: 'base' });
    };

    headers.forEach((header, headerIndex) => {
      header.style.cursor = 'pointer';
      header.dataset.sortIndex = String(headerIndex);
      header.title = 'Kliknij, aby sortować';
      header.addEventListener('click', () => {
        const direction = currentSort.index === headerIndex && currentSort.direction === 'asc' ? 'desc' : 'asc';
        currentSort = { index: headerIndex, direction };

        headers.forEach((otherHeader) => {
          otherHeader.removeAttribute('data-sort-direction');
        });
        header.setAttribute('data-sort-direction', direction);

        const sortedRows = Array.from(body.rows).sort((rowA, rowB) => {
          return compareValues(getCellValue(rowA, headerIndex), getCellValue(rowB, headerIndex), direction);
        });

        sortedRows.forEach((row) => body.appendChild(row));
      });
    });

    search.addEventListener('input', () => {
      const phrase = search.value.trim().toLowerCase();
      Array.from(body.rows).forEach((row) => {
        const haystack = row.textContent.toLowerCase();
        row.hidden = phrase !== '' && !haystack.includes(phrase);
      });
    });

    table.dataset.tableIndex = String(tableIndex);
  });
});
