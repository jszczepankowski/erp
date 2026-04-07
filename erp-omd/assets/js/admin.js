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

window.erpOmdInitDashboardV1Preview =
  window.erpOmdInitDashboardV1Preview ||
  function () {
    const previewNode = document.querySelector('[data-dashboard-v1-preview="1"]');
    if (!(previewNode instanceof HTMLElement)) {
      return;
    }

    const statusNode = previewNode.querySelector('[data-dashboard-v1-status="1"]');
    const gridNode = previewNode.querySelector('[data-dashboard-v1-grid="1"]');
    const monthNode = previewNode.querySelector('[data-dashboard-v1-month="1"]');
    const modeNode = previewNode.querySelector('[data-dashboard-v1-mode="1"]');
    const scopeNode = previewNode.querySelector('[data-dashboard-v1-scope="1"]');
    const refreshNode = previewNode.querySelector('[data-dashboard-v1-refresh="1"]');
    const clearCacheNode = previewNode.querySelector(
      '[data-dashboard-v1-clear-cache="1"]'
    );
    const monthStatusNode = previewNode.querySelector(
      '[data-dashboard-v1-month-status="1"]'
    );
    const actionsNode = previewNode.querySelector('[data-dashboard-v1-actions="1"]');
    const checklistNode = previewNode.querySelector(
      '[data-dashboard-v1-checklist="1"]'
    );
    const adjustmentsNode = previewNode.querySelector(
      '[data-dashboard-v1-adjustments="1"]'
    );
    const updatedAtNode = previewNode.querySelector(
      '[data-dashboard-v1-updated-at="1"]'
    );
    const sourceNode = previewNode.querySelector('[data-dashboard-v1-source="1"]');
    const countersNode = previewNode.querySelector('[data-dashboard-v1-counters="1"]');
    const debugNode = previewNode.querySelector('[data-dashboard-v1-debug="1"]');

  if (
    !(statusNode instanceof HTMLElement) ||
    !(gridNode instanceof HTMLElement) ||
    !(monthNode instanceof HTMLInputElement) ||
    !(modeNode instanceof HTMLSelectElement) ||
    !(scopeNode instanceof HTMLSelectElement) ||
    !(refreshNode instanceof HTMLButtonElement) ||
    !(clearCacheNode instanceof HTMLButtonElement) ||
    !(monthStatusNode instanceof HTMLElement) ||
    !(actionsNode instanceof HTMLElement) ||
    !(checklistNode instanceof HTMLElement) ||
    !(adjustmentsNode instanceof HTMLElement) ||
    !(updatedAtNode instanceof HTMLElement) ||
    !(sourceNode instanceof HTMLElement) ||
    !(countersNode instanceof HTMLElement) ||
    !(debugNode instanceof HTMLElement)
  ) {
    return;
  }

  if (
    typeof erpOmdAdminData === 'undefined' ||
    !erpOmdAdminData ||
    !erpOmdAdminData.restUrl
  ) {
    setStatusState(
      'Nie udało się załadować dashboard-v1 (brak konfiguracji REST).',
      'error',
      true
    );
    return;
  }

  const fallbackMonth = String(previewNode.dataset.month || '').trim();
  const headers = {};
  if (erpOmdAdminData.restNonce) {
    headers['X-WP-Nonce'] = String(erpOmdAdminData.restNonce);
  }

  const renderEmptyList = (listNode, message) => {
    listNode.innerHTML = '';
    const item = document.createElement('li');
    item.textContent = message;
    listNode.appendChild(item);
  };
  const setSourceState = (label, state) => {
    sourceNode.textContent = label;
    sourceNode.classList.remove(
      'erp-omd-dashboard-v1-source-badge-live',
      'erp-omd-dashboard-v1-source-badge-cache',
      'erp-omd-dashboard-v1-source-badge-empty'
    );
    sourceNode.classList.add(`erp-omd-dashboard-v1-source-badge-${state}`);
  };
  const setStatusState = (message, state, visible) => {
    statusNode.textContent = message;
    statusNode.hidden = !visible;
    statusNode.classList.remove(
      'erp-omd-dashboard-v1-preview-status-loading',
      'erp-omd-dashboard-v1-preview-status-success',
      'erp-omd-dashboard-v1-preview-status-warning',
      'erp-omd-dashboard-v1-preview-status-error',
      'erp-omd-dashboard-v1-preview-status-info'
    );
    statusNode.classList.add(`erp-omd-dashboard-v1-preview-status-${state}`);
  };
  const formatCountersLabel = (counters) => {
    const safeCounters = counters || {};
    return [
      `Trend: ${safeCounters.trend_rows || 0}`,
      `Projekty: ${safeCounters.project_rows || 0}`,
      `Klienci: ${safeCounters.client_rows || 0}`,
      `Kolejka: ${safeCounters.queue_rows || 0}`,
      `Korekty: ${safeCounters.adjustment_rows || 0}`,
      `Relewantne projekty: ${safeCounters.relevant_projects || 0}`,
    ].join(' | ');
  };
  const checklistLabelMap = {
    time_entries_finalized: 'Wpisy czasu sfinalizowane',
    project_costs_verified: 'Koszty projektowe zweryfikowane',
    project_client_completeness: 'Kompletność klient/projekt',
    critical_settlement_locks: 'Brak krytycznych blokad rozliczenia',
  };
  const adminReportsBaseUrl =
    typeof erpOmdAdminData !== 'undefined' &&
    erpOmdAdminData &&
    erpOmdAdminData.adminReportsUrl
      ? String(erpOmdAdminData.adminReportsUrl)
      : '';
  const buildProjectDrilldownUrl = (projectId) => {
    if (!adminReportsBaseUrl || !projectId) {
      return '';
    }
    return `${adminReportsBaseUrl}&report_type=projects&month=${encodeURIComponent(
      monthNode.value || fallbackMonth
    )}&project_id=${encodeURIComponent(String(projectId))}`;
  };
  const buildChecklistReason = (key, meta) => {
    const safeMeta = isObject(meta) ? meta : {};
    if (key === 'project_costs_verified') {
      const invalidCostRows = Number(safeMeta.invalid_cost_rows || 0);
      const withoutCostRows = Number(safeMeta.relevant_projects_without_cost_rows || 0);
      const invalidCostProjectIds = Array.isArray(safeMeta.invalid_cost_project_ids)
        ? safeMeta.invalid_cost_project_ids.map((value) => Number(value)).filter((value) => value > 0)
        : [];
      const withoutCostProjectIds = Array.isArray(safeMeta.relevant_projects_without_cost_project_ids)
        ? safeMeta.relevant_projects_without_cost_project_ids.map((value) => Number(value)).filter((value) => value > 0)
        : [];
      const reasonParts = [];
      if (invalidCostRows > 0) {
        reasonParts.push(
          `błędne wiersze kosztów: ${invalidCostRows}${
            invalidCostProjectIds.length ? ` (projekty: #${invalidCostProjectIds.join(', #')})` : ''
          }`
        );
      }
      if (withoutCostRows > 0) {
        reasonParts.push(
          `projekty bez kosztów: ${withoutCostRows}${
            withoutCostProjectIds.length ? ` (projekty: #${withoutCostProjectIds.join(', #')})` : ''
          }`
        );
      }
      return reasonParts.join(', ');
    }
    if (key === 'time_entries_finalized') {
      const submittedOrRejected = Number(safeMeta.submitted_or_rejected_entries || 0);
      return submittedOrRejected > 0 ? `wpisy submitted/rejected: ${submittedOrRejected}` : '';
    }
    if (key === 'project_client_completeness') {
      const incompleteProjects = Number(safeMeta.incomplete_relevant_projects || 0);
      return incompleteProjects > 0 ? `niekompletne projekty: ${incompleteProjects}` : '';
    }
    if (key === 'critical_settlement_locks') {
      const criticalAlerts = Number(safeMeta.critical_alerts || 0);
      return criticalAlerts > 0 ? `krytyczne alerty: ${criticalAlerts}` : '';
    }
    return '';
  };
  const collectProjectIdsForCostVerification = (meta) => {
    const safeMeta = isObject(meta) ? meta : {};
    const ids = [];
    const addIds = (source) => {
      if (!Array.isArray(source)) {
        return;
      }
      source.forEach((value) => {
        const numericValue = Number(value);
        if (numericValue > 0 && ids.indexOf(numericValue) === -1) {
          ids.push(numericValue);
        }
      });
    };
    addIds(safeMeta.invalid_cost_project_ids);
    addIds(safeMeta.relevant_projects_without_cost_project_ids);
    return ids;
  };
  const collectInvalidCostRowDetails = (meta) => {
    const safeMeta = isObject(meta) ? meta : {};
    if (!Array.isArray(safeMeta.invalid_cost_rows_details)) {
      return [];
    }
    return safeMeta.invalid_cost_rows_details
      .map((row) => (isObject(row) ? row : null))
      .filter((row) => row && Number(row.project_id || 0) > 0)
      .slice(0, 5);
  };
  const formatInvalidCostReason = (reasonCode) => {
    if (reasonCode === 'amount_non_positive') {
      return 'kwota <= 0';
    }
    if (reasonCode === 'description_empty') {
      return 'pusty opis';
    }
    return 'niepoprawny rekord kosztu';
  };
  const safeGet = (value, fallback) => (typeof value === 'undefined' || value === null ? fallback : value);
  const isObject = (value) => Boolean(value) && typeof value === 'object';

  let activeController = null;
  let activeRequestId = 0;
  const supportsAbortController = typeof AbortController === 'function';
  const submitCostCorrection = (detail, nextAmountRaw, nextDescription, adjustmentReason) => {
    const costId = Number(detail && detail.cost_id ? detail.cost_id : 0);
    const costDate = String(detail && detail.cost_date ? detail.cost_date : '');
    if (costId <= 0 || !costDate) {
      setStatusState('Brak danych rekordu kosztu do korekty.', 'error', true);
      return Promise.resolve(false);
    }
    if (!adjustmentReason || adjustmentReason.trim() === '') {
      setStatusState('Powód korekty jest wymagany.', 'error', true);
      return Promise.resolve(false);
    }

    const endpoint = `${String(erpOmdAdminData.restUrl).replace(/\/$/, '')}/project-costs/${encodeURIComponent(
      String(costId)
    )}`;
    const requestBody = new URLSearchParams();
    requestBody.set('amount', String(nextAmountRaw));
    requestBody.set('description', String(nextDescription));
    requestBody.set('cost_date', costDate);
    requestBody.set('adjustment_reason', String(adjustmentReason));

    setStatusState('Zapisywanie korekty kosztu…', 'loading', true);
    return fetch(endpoint, {
      method: 'POST',
      headers: Object.assign({ 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, headers),
      body: requestBody.toString(),
    })
      .then((response) =>
        response
          .json()
          .catch(() => ({}))
          .then((payload) => ({ ok: response.ok, payload }))
      )
      .then(({ ok, payload }) => {
        if (!ok) {
          throw new Error(String((payload && payload.message) || 'Korekta kosztu nie powiodła się.'));
        }
        setStatusState('Korekta kosztu zapisana. Odświeżam podgląd…', 'success', true);
        fetchPreview();
        return true;
      })
      .catch((error) => {
        setStatusState(`Nie udało się zapisać korekty: ${String(error.message || 'błąd')}`, 'error', true);
        return false;
      });
  };

  const fetchPreview = () => {
    if (supportsAbortController && activeController instanceof AbortController) {
      activeController.abort();
    }
    activeController = supportsAbortController ? new AbortController() : null;
    activeRequestId += 1;
    const requestId = activeRequestId;
    const cacheKey = `erp_omd_dashboard_v1_preview_${monthNode.value || fallbackMonth}_${modeNode.value}_${scopeNode.value}`;

    const endpoint = `${String(erpOmdAdminData.restUrl).replace(
      /\/$/,
      ''
    )}/dashboard-v1?month=${encodeURIComponent(
      monthNode.value || fallbackMonth
    )}&mode=${encodeURIComponent(
      modeNode.value
    )}&profitability_scope=${encodeURIComponent(scopeNode.value)}`;

    previewNode.setAttribute('aria-busy', 'true');
    setStatusState('Ładowanie podglądu dashboard-v1…', 'loading', true);
    gridNode.hidden = true;
    refreshNode.disabled = true;
    debugNode.textContent = 'DEBUG: init start';

    const timeoutHandle = window.setTimeout(() => {
      if (supportsAbortController && activeController instanceof AbortController) {
        activeController.abort();
      }
    }, 12000);

    const fetchOptions = { headers };
    if (supportsAbortController && activeController instanceof AbortController) {
      fetchOptions.signal = activeController.signal;
    }

    fetch(endpoint, fetchOptions)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
      })
      .then((payload) => {
      if (requestId !== activeRequestId) {
        return;
      }
      try {
        localStorage.setItem(cacheKey, JSON.stringify(payload));
      } catch (_) {
        // ignore storage quota / privacy mode issues
      }
      const safePayload = isObject(payload) ? payload : {};
      const dataHealth = isObject(safePayload.data_health) ? safePayload.data_health : {};
      const readinessChecklist = isObject(safePayload.readiness_checklist)
        ? safePayload.readiness_checklist
        : {};
      const adjustments = isObject(safePayload.adjustments) ? safePayload.adjustments : {};
      gridNode.hidden = false;
      debugNode.textContent = '';
      setStatusState('Dane LIVE zostały odświeżone.', 'success', false);

      monthStatusNode.textContent = `${safeGet(safePayload.period_status, '—')} (${safeGet(safePayload.month, monthNode.value || fallbackMonth)})`;
      updatedAtNode.textContent = `Ostatnia aktualizacja: ${safeGet(safePayload.generated_at, '—')}`;
      setSourceState('LIVE', 'live');
      const hasOperationalData = Boolean(dataHealth.has_operational_data);
      if (!hasOperationalData) {
        const counters = isObject(dataHealth.counters) ? dataHealth.counters : {};
        setStatusState(
          safeGet(dataHealth.hint, 'Brak danych operacyjnych dla wybranego miesiąca.'),
          'warning',
          true
        );
        countersNode.textContent = formatCountersLabel(counters);
      } else {
        setStatusState('Dane LIVE zostały odświeżone.', 'success', false);
        countersNode.textContent = '';
      }
      actionsNode.innerHTML = '';
      const statusActions = Array.isArray(safePayload.status_actions)
        ? safePayload.status_actions
        : [];
      if (statusActions.length === 0) {
        renderEmptyList(actionsNode, 'Brak dostępnych akcji statusu.');
      } else {
        statusActions.forEach((action) => {
          const safeAction = isObject(action) ? action : {};
          const item = document.createElement('li');
          const label = safeGet(safeAction.label, safeGet(safeAction.to_status, '—'));
          const state = safeAction.enabled ? 'aktywna' : 'zablokowana';
          item.textContent = `${label} (${state})`;
          actionsNode.appendChild(item);
        });
      }

      checklistNode.innerHTML = '';
      const checks = isObject(readinessChecklist.checks) ? readinessChecklist.checks : {};
      const checklistMeta = isObject(readinessChecklist.meta) ? readinessChecklist.meta : {};
      const checkEntries = Object.entries(checks);
      if (checkEntries.length === 0) {
        renderEmptyList(checklistNode, 'Brak danych checklisty.');
      } else {
        checkEntries.forEach(([key, passed]) => {
          const item = document.createElement('li');
          const label = safeGet(checklistLabelMap[key], key);
          const reason = !passed ? buildChecklistReason(key, checklistMeta) : '';
          item.textContent = `${passed ? '✅' : '⛔'} ${label}${reason ? ` — ${reason}` : ''}`;
          if (!passed && key === 'project_costs_verified') {
            const projectIds = collectProjectIdsForCostVerification(checklistMeta);
            if (projectIds.length > 0) {
              const linksWrapper = document.createElement('div');
              linksWrapper.className = 'erp-omd-dashboard-v1-checklist-links';
              linksWrapper.appendChild(document.createTextNode('Przejdź do projektu: '));
              projectIds.forEach((projectId, index) => {
                const drilldownUrl = buildProjectDrilldownUrl(projectId);
                if (drilldownUrl) {
                  const link = document.createElement('a');
                  link.href = drilldownUrl;
                  link.textContent = `#${projectId}`;
                  link.target = '_self';
                  linksWrapper.appendChild(link);
                } else {
                  linksWrapper.appendChild(document.createTextNode(`#${projectId}`));
                }
                if (index < projectIds.length - 1) {
                  linksWrapper.appendChild(document.createTextNode(', '));
                }
              });
              item.appendChild(document.createElement('br'));
              item.appendChild(linksWrapper);
            }
            const invalidCostDetails = collectInvalidCostRowDetails(checklistMeta);
            if (invalidCostDetails.length > 0) {
              const detailsWrapper = document.createElement('div');
              detailsWrapper.className = 'erp-omd-dashboard-v1-checklist-links';
              detailsWrapper.appendChild(document.createTextNode('Szczegóły błędnych kosztów: '));
              invalidCostDetails.forEach((row, index) => {
                const projectId = Number(row.project_id || 0);
                const costId = Number(row.cost_id || 0);
                const detailText = `#${projectId}${costId > 0 ? `/koszt:${costId}` : ''} (${formatInvalidCostReason(row.reason)})`;
                detailsWrapper.appendChild(document.createTextNode(detailText));
                if (costId > 0) {
                  detailsWrapper.appendChild(document.createTextNode(' '));
                  const fixButton = document.createElement('button');
                  fixButton.type = 'button';
                  fixButton.className = 'button button-small';
                  fixButton.textContent = 'Skoryguj';
                  fixButton.addEventListener('click', () => {
                    const existingForm = detailsWrapper.querySelector('.erp-omd-dashboard-v1-correction-form');
                    if (existingForm instanceof HTMLElement) {
                      existingForm.remove();
                      return;
                    }
                    const formNode = document.createElement('div');
                    formNode.className = 'erp-omd-dashboard-v1-correction-form';

                    const amountInput = document.createElement('input');
                    amountInput.type = 'number';
                    amountInput.step = '0.01';
                    amountInput.value = String(typeof row.amount !== 'undefined' ? row.amount : '');
                    amountInput.placeholder = 'Kwota';

                    const descriptionInput = document.createElement('input');
                    descriptionInput.type = 'text';
                    descriptionInput.value = String(row.description || '');
                    descriptionInput.placeholder = 'Opis kosztu';

                    const reasonInput = document.createElement('input');
                    reasonInput.type = 'text';
                    reasonInput.value = 'Korekta z dashboard-v1';
                    reasonInput.placeholder = 'Powód korekty';

                    const saveButton = document.createElement('button');
                    saveButton.type = 'button';
                    saveButton.className = 'button button-primary button-small';
                    saveButton.textContent = 'Zapisz';
                    saveButton.addEventListener('click', () => {
                      const amountValue = Number(amountInput.value);
                      if (!(amountValue > 0)) {
                        setStatusState('Kwota kosztu musi być większa od zera.', 'error', true);
                        return;
                      }
                      if (String(descriptionInput.value || '').trim() === '') {
                        setStatusState('Opis kosztu nie może być pusty.', 'error', true);
                        return;
                      }
                      if (String(reasonInput.value || '').trim() === '') {
                        setStatusState('Powód korekty jest wymagany.', 'error', true);
                        return;
                      }
                      saveButton.disabled = true;
                      submitCostCorrection(
                        row,
                        String(amountValue),
                        descriptionInput.value,
                        reasonInput.value
                      ).then((saved) => {
                        if (saved) {
                          formNode.remove();
                        }
                      }).finally(() => {
                        saveButton.disabled = false;
                      });
                    });

                    const cancelButton = document.createElement('button');
                    cancelButton.type = 'button';
                    cancelButton.className = 'button button-small';
                    cancelButton.textContent = 'Anuluj';
                    cancelButton.addEventListener('click', () => formNode.remove());

                    formNode.appendChild(amountInput);
                    formNode.appendChild(descriptionInput);
                    formNode.appendChild(reasonInput);
                    formNode.appendChild(saveButton);
                    formNode.appendChild(cancelButton);
                    detailsWrapper.appendChild(formNode);
                  });
                  detailsWrapper.appendChild(fixButton);
                }
                if (index < invalidCostDetails.length - 1) {
                  detailsWrapper.appendChild(document.createTextNode('; '));
                }
              });
              item.appendChild(document.createElement('br'));
              item.appendChild(detailsWrapper);
            }
          }
          checklistNode.appendChild(item);
        });
      }

      adjustmentsNode.innerHTML = '';
      const adjustmentItems = Array.isArray(adjustments.items)
        ? adjustments.items
        : [];
      if (adjustmentItems.length === 0) {
        renderEmptyList(adjustmentsNode, 'Brak korekt dla wybranego miesiąca.');
      } else {
        adjustmentItems.slice(0, 5).forEach((row) => {
          const safeRow = isObject(row) ? row : {};
          const item = document.createElement('li');
          const entity = safeGet(safeRow.entity_type, '—');
          const reason = safeGet(safeRow.reason, '—');
          item.textContent = `${entity}: ${reason}`;
          adjustmentsNode.appendChild(item);
        });
      }
      })
      .catch((error) => {
        const safeError = isObject(error) ? error : {};
        if (requestId !== activeRequestId || safeError.name === 'AbortError') {
          return;
        }
        let restored = false;
        try {
          const cached = localStorage.getItem(cacheKey);
          if (cached) {
            const payload = JSON.parse(cached);
            const safePayload = isObject(payload) ? payload : {};
            const dataHealth = isObject(safePayload.data_health) ? safePayload.data_health : {};
            monthStatusNode.textContent = `${safeGet(safePayload.period_status, '—')} (${safeGet(safePayload.month, monthNode.value || fallbackMonth)})`;
            updatedAtNode.textContent = `Tryb offline (cache): ${safeGet(safePayload.generated_at, '—')}`;
            setSourceState('CACHE', 'cache');
            const counters = isObject(dataHealth.counters) ? dataHealth.counters : {};
            countersNode.textContent = formatCountersLabel(counters);
            restored = true;
          }
        } catch (_) {
          restored = false;
        }
        if (restored) {
          setStatusState(
            'Nie udało się odświeżyć danych live — pokazuję ostatni zapisany snapshot.',
            'warning',
            true
          );
          debugNode.textContent = `DEBUG: ${String(safeGet(safeError.message, 'REST fetch failed'))}`;
          gridNode.hidden = false;
          return;
        }
        setStatusState(
          'Nie udało się pobrać podglądu dashboard-v1. Sprawdź uprawnienia i konfigurację REST API.',
          'error',
          true
        );
        setSourceState('BRAK DANYCH', 'empty');
        debugNode.textContent = `DEBUG: ${String(safeGet(safeError.message, 'REST fetch failed'))}`;
        gridNode.hidden = true;
      })
      .finally(() => {
        window.clearTimeout(timeoutHandle);
        if (requestId !== activeRequestId) {
          return;
        }
        previewNode.setAttribute('aria-busy', 'false');
        refreshNode.disabled = false;
      });
  };

  refreshNode.addEventListener('click', fetchPreview);
  monthNode.addEventListener('change', fetchPreview);
  modeNode.addEventListener('change', fetchPreview);
  scopeNode.addEventListener('change', fetchPreview);
  clearCacheNode.addEventListener('click', () => {
    try {
      Object.keys(localStorage).forEach((key) => {
        if (key.indexOf('erp_omd_dashboard_v1_preview_') === 0) {
          localStorage.removeItem(key);
        }
      });
      setStatusState('Wyczyszczono lokalny cache snapshotów dashboard-v1.', 'info', true);
      updatedAtNode.textContent = '';
      setSourceState('LIVE', 'live');
      countersNode.textContent = '';
      debugNode.textContent = '';
    } catch (_) {
      setStatusState('Nie udało się wyczyścić lokalnego cache.', 'error', true);
      debugNode.textContent = 'DEBUG: localStorage clear failed';
    }
  });
    fetchPreview();
  };

window.erpOmdInitAdminInteractions =
  window.erpOmdInitAdminInteractions ||
  ((currentPage) => {

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

document.addEventListener('DOMContentLoaded', () => {
  const rootNode = document.body;
  if (rootNode instanceof HTMLElement) {
    if (rootNode.dataset.erpOmdAdminInitDone === '1') {
      return;
    }
    rootNode.dataset.erpOmdAdminInitDone = '1';
  }

  const currentPage = new URLSearchParams(window.location.search).get('page') || '';
  initTableTools();
  initFixedCosts();
  initInlineAutoSave();
  if (typeof window.erpOmdInitDashboardV1Preview === 'function') {
    window.erpOmdInitDashboardV1Preview();
  }
  if (typeof window.erpOmdInitAdminInteractions === 'function') {
    window.erpOmdInitAdminInteractions(currentPage);
  }
});
