const initTableTools = () => {
  const currentPage = new URLSearchParams(window.location.search).get('page') || '';
  const paginatedPages = new Set([
    'erp-omd-clients',
    'erp-omd-estimates',
    'erp-omd-projects',
    'erp-omd-requests',
    'erp-omd-time',
    'erp-omd-reports',
  ]);
  const tables = document.querySelectorAll('.erp-omd-admin table.widefat');

  tables.forEach((table, tableIndex) => {
    if (table.dataset.disableTableTools === '1') {
      return;
    }
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

    const shouldPaginate =
      paginatedPages.has(currentPage) &&
      !table.classList.contains('erp-omd-calendar-table');

    let pageSize = 100;
    let currentPaginationPage = 1;
    let pageSizeSelect = null;
    let paginationMeta = null;
    let paginationPrev = null;
    let paginationNext = null;

    if (shouldPaginate) {
      pageSizeSelect = document.createElement('select');
      pageSizeSelect.className = 'erp-omd-table-page-size';
      pageSizeSelect.setAttribute('aria-label', 'Liczba wierszy na stronę');
      [25, 50, 100, 200].forEach((size) => {
        const option = document.createElement('option');
        option.value = String(size);
        option.textContent = `${size} / strona`;
        if (size === 100) {
          option.selected = true;
        }
        pageSizeSelect.appendChild(option);
      });
      controls.appendChild(pageSizeSelect);

      const pager = document.createElement('div');
      pager.className = 'erp-omd-table-pagination';

      paginationPrev = document.createElement('button');
      paginationPrev.type = 'button';
      paginationPrev.className = 'button button-secondary';
      paginationPrev.textContent = '←';
      pager.appendChild(paginationPrev);

      paginationMeta = document.createElement('span');
      paginationMeta.className = 'erp-omd-table-pagination-meta';
      pager.appendChild(paginationMeta);

      paginationNext = document.createElement('button');
      paginationNext.type = 'button';
      paginationNext.className = 'button button-secondary';
      paginationNext.textContent = '→';
      pager.appendChild(paginationNext);

      controls.appendChild(pager);
    }

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

    const applyTableView = () => {
      const phrase = search.value.trim().toLowerCase();
      const filteredRows = Array.from(body.rows).filter((row) => {
        const haystack = row.textContent.toLowerCase();
        return phrase === '' || haystack.includes(phrase);
      });

      if (!shouldPaginate) {
        Array.from(body.rows).forEach((row) => {
          row.hidden = !filteredRows.includes(row);
        });
        return;
      }

      const pagesCount = Math.max(1, Math.ceil(filteredRows.length / pageSize));
      currentPaginationPage = Math.min(currentPaginationPage, pagesCount);
      currentPaginationPage = Math.max(1, currentPaginationPage);
      const start = (currentPaginationPage - 1) * pageSize;
      const end = start + pageSize;

      filteredRows.forEach((row, index) => {
        row.hidden = index < start || index >= end;
      });
      Array.from(body.rows).forEach((row) => {
        if (!filteredRows.includes(row)) {
          row.hidden = true;
        }
      });

      if (paginationMeta) {
        paginationMeta.textContent = `${currentPaginationPage}/${pagesCount} · ${filteredRows.length}`;
      }
      if (paginationPrev) {
        paginationPrev.disabled = currentPaginationPage <= 1;
      }
      if (paginationNext) {
        paginationNext.disabled = currentPaginationPage >= pagesCount;
      }
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
        currentPaginationPage = 1;
        applyTableView();
      });
    });

    search.addEventListener('input', () => {
      currentPaginationPage = 1;
      applyTableView();
    });

    if (shouldPaginate && pageSizeSelect && paginationPrev && paginationNext) {
      pageSizeSelect.addEventListener('change', () => {
        pageSize = Number.parseInt(pageSizeSelect.value, 10) || 100;
        currentPaginationPage = 1;
        applyTableView();
      });
      paginationPrev.addEventListener('click', () => {
        currentPaginationPage -= 1;
        applyTableView();
      });
      paginationNext.addEventListener('click', () => {
        currentPaginationPage += 1;
        applyTableView();
      });
    }

    table.dataset.tableIndex = String(tableIndex);
    applyTableView();
  });
};

const initFixedCosts = () => {
  const fixedCostBody = document.querySelector('tbody[data-fixed-cost-body="1"]');
  const addFixedCostButton = document.getElementById('erp-omd-add-fixed-cost-row');

  const buildFixedCostRow = (index) => {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td><input type="text" name="fixed_cost_items[${index}][name]" value="" /></td>
      <td><input type="number" min="0" step="0.01" name="fixed_cost_items[${index}][amount]" value="0.00" /></td>
      <td><input type="date" name="fixed_cost_items[${index}][valid_from]" value="" /></td>
      <td><input type="date" name="fixed_cost_items[${index}][valid_to]" value="" /></td>
      <td>
        <label>
          <input type="checkbox" name="fixed_cost_items[${index}][active]" value="1" checked />
          Tak
        </label>
      </td>
      <td>
        <button type="button" class="button button-secondary erp-omd-remove-fixed-cost-row">Usuń</button>
      </td>
    `;
    return row;
  };

  const appendFixedCostRow = () => {
    if (!(fixedCostBody instanceof HTMLTableSectionElement)) {
      return;
    }

    const nextIndex = Number.parseInt(fixedCostBody.dataset.nextIndex || '0', 10) || 0;
    fixedCostBody.dataset.nextIndex = String(nextIndex + 1);
    fixedCostBody.appendChild(buildFixedCostRow(nextIndex));
  };

  if (addFixedCostButton && fixedCostBody instanceof HTMLTableSectionElement) {
    addFixedCostButton.addEventListener('click', appendFixedCostRow);

    fixedCostBody.addEventListener('click', (event) => {
      const target = event.target;
      if (!(target instanceof HTMLElement) || !target.classList.contains('erp-omd-remove-fixed-cost-row')) {
        return;
      }

      const row = target.closest('tr');
      if (row) {
        row.remove();
      }

      if (fixedCostBody.rows.length === 0) {
        appendFixedCostRow();
      }
    });
  }
};

const initInlineAutoSave = () => {
  const inlineAutoSaveConfig = {
    debounceMs: 700,
    inputSelectors:
      'input[type="text"], input[type="number"], input[type="date"], textarea',
    immediateSelectors:
      'select, input[type="checkbox"], input[type="radio"]',
  };

  const inlineAutoSaveForms = Array.from(
    document.querySelectorAll('form[id^="erp-omd-inline-"]')
  );

  inlineAutoSaveForms.forEach((form) => {
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    const actionInput = form.querySelector('input[name="erp_omd_action"]');
    const allowedActions = new Set([
      'inline_update_employee',
      'inline_update_project',
      'inline_update_time_entry',
    ]);

    if (
      !(actionInput instanceof HTMLInputElement) ||
      !allowedActions.has(actionInput.value)
    ) {
      return;
    }

    let debounceTimer = null;
    let ajaxInFlight = false;

    const setInlineState = (stateClass) => {
      form.classList.remove(
        'erp-omd-inline-pending',
        'erp-omd-inline-success',
        'erp-omd-inline-error'
      );
      if (stateClass) {
        form.classList.add(stateClass);
      }
    };

    const submitInlineProjectViaAjax = () => {
      if (
        typeof erpOmdAdminData === 'undefined' ||
        !erpOmdAdminData ||
        !erpOmdAdminData.ajaxUrl ||
        ajaxInFlight
      ) {
        return false;
      }

      const idInput = form.querySelector('input[name="id"]');
      if (!(idInput instanceof HTMLInputElement) || idInput.value === '') {
        return false;
      }

      ajaxInFlight = true;
      setInlineState('erp-omd-inline-pending');

      const payload = new FormData(form);
      payload.set('action', 'erp_omd_inline_project_update');
      payload.set(
        '_ajax_nonce',
        String(erpOmdAdminData.inlineProjectNonce || '')
      );

      fetch(erpOmdAdminData.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: payload,
      })
        .then((response) => response.json())
        .then((json) => {
          if (!json || json.success !== true) {
            throw new Error(
              (json && json.data && json.data.message) ||
                'Błąd zapisu inline projektu.'
            );
          }
          setInlineState('erp-omd-inline-success');
          setTimeout(() => setInlineState(''), 1200);
        })
        .catch((error) => {
          setInlineState('erp-omd-inline-error');
          window.console &&
            console.warn &&
            console.warn('[ERP OMD] Inline save error:', error);
        })
        .finally(() => {
          ajaxInFlight = false;
        });

      return true;
    };

    const submitInlineForm = () => {
      if (actionInput.value === 'inline_update_project') {
        const usedAjax = submitInlineProjectViaAjax();
        if (usedAjax) {
          return;
        }
      }

      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
        return;
      }

      form.submit();
    };

    const scheduleSubmit = () => {
      if (debounceTimer) {
        clearTimeout(debounceTimer);
      }
      debounceTimer = setTimeout(() => {
        submitInlineForm();
      }, inlineAutoSaveConfig.debounceMs);
    };

    const linkedElements = Array.from(
      document.querySelectorAll(`[form="${form.id}"]`)
    );

    linkedElements.forEach((element) => {
      if (
        !(element instanceof HTMLInputElement) &&
        !(element instanceof HTMLSelectElement) &&
        !(element instanceof HTMLTextAreaElement)
      ) {
        return;
      }

      if (element.matches(inlineAutoSaveConfig.immediateSelectors)) {
        element.addEventListener('change', submitInlineForm);
        return;
      }

      if (element.matches(inlineAutoSaveConfig.inputSelectors)) {
        element.addEventListener('change', scheduleSubmit);
        element.addEventListener('blur', scheduleSubmit);
      }
    });
  });
};

document.addEventListener('DOMContentLoaded', () => {
  const currentPage = new URLSearchParams(window.location.search).get('page') || '';
  initTableTools();
  initFixedCosts();
  initInlineAutoSave();

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

  document.querySelectorAll('.erp-omd-project-monthly-dates').forEach((button) => {
    button.addEventListener('click', () => {
      const startSelector = button.dataset.startTarget || '';
      const endSelector = button.dataset.endTarget || '';

      if (!startSelector || !endSelector) {
        return;
      }

      const startInput = document.querySelector(startSelector);
      const endInput = document.querySelector(endSelector);

      if (!(startInput instanceof HTMLInputElement) || !(endInput instanceof HTMLInputElement)) {
        return;
      }

      const now = new Date();
      const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);
      const monthEnd = new Date(now.getFullYear(), now.getMonth() + 1, 0);
      const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      };

      startInput.value = formatDate(monthStart);
      endInput.value = formatDate(monthEnd);
      startInput.dispatchEvent(new Event('change', { bubbles: true }));
      endInput.dispatchEvent(new Event('change', { bubbles: true }));
    });
  });


  const syncRoleAvailability = (projectSelect) => {
    if (!(projectSelect instanceof HTMLSelectElement)) {
      return;
    }

    const roleSelector = projectSelect.dataset.roleTarget;
    if (!roleSelector) {
      return;
    }

    const roleSelect = document.querySelector(roleSelector);
    if (!(roleSelect instanceof HTMLSelectElement)) {
      return;
    }

    const hasProject = projectSelect.value !== '' && projectSelect.value !== '0';
    roleSelect.disabled = !hasProject;

    if (!hasProject) {
      roleSelect.value = '';
    }
  };

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
    const requiresClient = clientSelect.dataset.projectRequiresClient === '1';
    const hasClient = selectedClientId !== '' && selectedClientId !== '0';
    let hasVisibleSelectedOption = false;

    if (requiresClient) {
      projectSelect.disabled = !hasClient;
    }

    Array.from(projectSelect.options).forEach((option) => {
      if (option.value === '') {
        option.hidden = false;
        return;
      }

      const optionClientId = option.dataset.clientId || '';
      const visible = requiresClient
        ? hasClient && optionClientId === selectedClientId
        : selectedClientId === '' || selectedClientId === '0' || optionClientId === selectedClientId;
      option.hidden = !visible;

      if (visible && option.selected) {
        hasVisibleSelectedOption = true;
      }
    });

    if (!hasVisibleSelectedOption) {
      projectSelect.value = '';
      const firstVisibleOption = Array.from(projectSelect.options).find((option) => !option.hidden && option.value !== '');
      if (!requiresClient && projectSelect.required && firstVisibleOption) {
        firstVisibleOption.selected = true;
      }
    }

    syncRoleAvailability(projectSelect);
  };

  document.querySelectorAll('select[data-project-target]').forEach((clientSelect) => {
    syncProjectOptions(clientSelect);
    clientSelect.addEventListener('change', () => syncProjectOptions(clientSelect));
  });

  document.querySelectorAll('select[data-role-target]').forEach((projectSelect) => {
    syncRoleAvailability(projectSelect);
    projectSelect.addEventListener('change', () => syncRoleAvailability(projectSelect));
  });

  document.querySelectorAll('.erp-omd-attachment-form').forEach((form) => {
    const button = form.querySelector('.erp-omd-media-button');
    const input = form.querySelector('.erp-omd-media-id');
    const nameNode = form.querySelector('.erp-omd-media-name');
    const previewNode = form.querySelector('.erp-omd-media-preview img');

    if (!button || !(input instanceof HTMLInputElement) || !nameNode || typeof wp === 'undefined' || !wp.media) {
      return;
    }

    button.addEventListener('click', () => {
      const frame = wp.media({
        title: button.dataset.mediaTitle || 'Wybierz załącznik',
        button: { text: button.dataset.mediaButton || 'Użyj załącznika' },
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
        if (previewNode instanceof HTMLImageElement && attachment.url) {
          previewNode.src = attachment.url;
          previewNode.hidden = false;
        }
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

  if (currentPage === 'erp-omd-dashboard') {
    return;
  }

  const collapsibleBoxes = document.querySelectorAll(
    '.erp-omd-admin .erp-omd-card, .erp-omd-admin .erp-omd-form-section, .erp-omd-admin .erp-omd-detail-card'
  );

  collapsibleBoxes.forEach((box, index) => {
    const titleNode = box.querySelector(':scope > h1, :scope > h2, :scope > h3, :scope > h4, :scope > .erp-omd-section-header, :scope > .erp-omd-form-section-header');
    if (!titleNode) {
      return;
    }

    const controls = document.createElement('div');
    controls.className = 'erp-omd-collapse-controls';

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'erp-omd-collapse-toggle';
    toggle.dataset.collapseTarget = `erp-omd-box-${index}`;
    const pageKey = currentPage || 'global';
    const headingKey = (titleNode.textContent || '').trim().toLowerCase().replace(/\s+/g, '-').slice(0, 80);
    const storageKey = `erp_omd_admin_box_${pageKey}_${index}_${headingKey}`;

    const renderState = () => {
      const isCollapsed = box.classList.contains('erp-omd-is-collapsed');
      toggle.textContent = isCollapsed ? 'Rozwiń' : 'Zwiń';
      toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
    };

    toggle.addEventListener('click', () => {
      box.classList.toggle('erp-omd-is-collapsed');
      localStorage.setItem(storageKey, box.classList.contains('erp-omd-is-collapsed') ? '1' : '0');
      renderState();
    });

    controls.appendChild(toggle);
    box.insertBefore(controls, box.firstChild);
    box.classList.add('erp-omd-collapsible-box');
    if (localStorage.getItem(storageKey) === '1') {
      box.classList.add('erp-omd-is-collapsed');
    }
    renderState();
  });
});
