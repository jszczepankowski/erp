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

  document.querySelectorAll('.erp-omd-quick-hours-button').forEach((button) => {
    button.addEventListener('click', () => {
      const selector = button.dataset.target;
      const hours = button.dataset.hours;

      if (!selector || !hours) {
        return;
      }

      const input = document.querySelector(selector);
      if (!(input instanceof HTMLInputElement)) {
        return;
      }

      input.value = hours;
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
      input.focus();
    });
  });


  const syncProjectOptions = (clientSelect) => {
    if (!(clientSelect instanceof HTMLSelectElement)) {
      return;
    }

    const targetSelector = clientSelect.dataset.projectTarget;
    if (!targetSelector) {
      return;
    }

    const projectSelect = document.querySelector(targetSelector);
    if (!(projectSelect instanceof HTMLSelectElement)) {
      return;
    }

    const selectedClientId = clientSelect.value;
    let hasVisibleSelectedOption = false;

    Array.from(projectSelect.options).forEach((option) => {
      if (option.value === '') {
        option.hidden = false;
        return;
      }

      const optionClientId = option.dataset.clientId || '';
      const visible = selectedClientId === '' || selectedClientId === '0' || optionClientId === selectedClientId;
      option.hidden = !visible;

      if (visible && option.selected) {
        hasVisibleSelectedOption = true;
      }
    });

    if (!hasVisibleSelectedOption) {
      projectSelect.value = '';
      const firstVisibleOption = Array.from(projectSelect.options).find((option) => !option.hidden && option.value !== '');
      if (projectSelect.required && firstVisibleOption) {
        firstVisibleOption.selected = true;
      }
    }
  };

  document.querySelectorAll('select[data-project-target]').forEach((clientSelect) => {
    syncProjectOptions(clientSelect);
    clientSelect.addEventListener('change', () => syncProjectOptions(clientSelect));
  });

  document.querySelectorAll('.erp-omd-attachment-form').forEach((form) => {
    const button = form.querySelector('.erp-omd-media-button');
    const input = form.querySelector('.erp-omd-media-id');
    const nameNode = form.querySelector('.erp-omd-media-name');

    if (!button || !(input instanceof HTMLInputElement) || !nameNode || typeof wp === 'undefined' || !wp.media) {
      return;
    }

    button.addEventListener('click', () => {
      const frame = wp.media({
        title: 'Wybierz załącznik',
        button: { text: 'Użyj załącznika' },
        multiple: false,
      });

      frame.on('select', () => {
        const selection = frame.state().get('selection').first();
        if (!selection) {
          return;
        }

        const attachment = selection.toJSON();
        input.value = String(attachment.id || '');
        nameNode.textContent = attachment.filename || attachment.title || `#${attachment.id}`;
      });

      frame.open();
    });
  });

  document.querySelectorAll('.erp-omd-list-actions').forEach((detailsNode) => {
    detailsNode.addEventListener('toggle', () => {
      if (!detailsNode.open) {
        return;
      }

      document.querySelectorAll('.erp-omd-list-actions[open]').forEach((otherNode) => {
        if (otherNode !== detailsNode) {
          otherNode.open = false;
        }
      });
    });
  });

  document.addEventListener('click', (event) => {
    document.querySelectorAll('.erp-omd-list-actions[open]').forEach((detailsNode) => {
      if (!detailsNode.contains(event.target)) {
        detailsNode.open = false;
      }
    });
  });
});
