(function (window, document) {
    'use strict';

    var setupManagerTabs = function () {
        var storageKey = 'erp_omd_front_manager_active_tab';
        var allowedTabs = ['dodaj-wpis', 'wpisy-godzin', 'projekty', 'kosztorysy', 'akceptacje', 'wnioski'];
        var params = new URLSearchParams(window.location.search);
        var urlTab = params.get('manager_tab');
        var storedTab = localStorage.getItem(storageKey);
        var activeTab = allowedTabs.indexOf(urlTab) !== -1 ? urlTab : (allowedTabs.indexOf(storedTab) !== -1 ? storedTab : 'projekty');

        localStorage.setItem(storageKey, activeTab);
        if (allowedTabs.indexOf(urlTab) === -1) {
            params.set('manager_tab', activeTab);
            history.replaceState({}, '', window.location.pathname + '?' + params.toString() + window.location.hash);
        }

        document.querySelectorAll('[data-manager-tab-pane]').forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-manager-tab-pane') !== activeTab;
        });

        document.querySelectorAll('[data-manager-tab-button]').forEach(function (button) {
            var isActive = button.getAttribute('data-manager-tab-button') === activeTab;
            button.classList.toggle('erp-omd-front-button-primary', isActive);
            button.classList.toggle('erp-omd-front-button-ghost', !isActive);
            button.setAttribute('aria-current', isActive ? 'page' : 'false');
            button.addEventListener('click', function () {
                var nextTab = button.getAttribute('data-manager-tab-button');
                if (allowedTabs.indexOf(nextTab) === -1) {
                    return;
                }
                localStorage.setItem(storageKey, nextTab);
                params.set('manager_tab', nextTab);
                history.replaceState({}, '', window.location.pathname + '?' + params.toString() + window.location.hash);
                document.querySelectorAll('[data-manager-tab-pane]').forEach(function (panel) {
                    panel.hidden = panel.getAttribute('data-manager-tab-pane') !== nextTab;
                });
                document.querySelectorAll('[data-manager-tab-button]').forEach(function (candidate) {
                    var candidateActive = candidate.getAttribute('data-manager-tab-button') === nextTab;
                    candidate.classList.toggle('erp-omd-front-button-primary', candidateActive);
                    candidate.classList.toggle('erp-omd-front-button-ghost', !candidateActive);
                    candidate.setAttribute('aria-current', candidateActive ? 'page' : 'false');
                });
            });
        });
    };

    var setupTableEnhancements = function (options) {
        var settings = options || {};
        var searchLabel = settings.searchLabel || 'Szukaj:';
        var searchPlaceholder = settings.searchPlaceholder || 'np. klient / projekt / status';
        var viewLabel = settings.viewLabel || 'Widok:';
        var visibleRowsLabel = settings.visibleRowsLabel || 'Widoczne wiersze:';
        var actionHeaders = settings.actionHeaders || ['Akcja', 'Akcje'];

        var parseSortableValue = function (value) {
            var normalized = (value || '').replace(/\s+/g, ' ').trim();
            var numeric = normalized.replace(',', '.').replace(/[^\d.-]/g, '');
            return numeric !== '' && !Number.isNaN(Number(numeric)) ? Number(numeric) : normalized.toLowerCase();
        };

        document.querySelectorAll('table[data-table-enhanced="1"]').forEach(function (table) {
            var tbody = table.querySelector('tbody');
            if (!tbody) {
                return;
            }

            var allRows = Array.from(tbody.querySelectorAll('tr'));
            if (allRows.length === 0) {
                return;
            }

            var wrap = table.closest('.erp-omd-front-table-wrap');
            var controls = document.createElement('div');
            controls.className = 'erp-omd-front-table-tools';
            controls.innerHTML =
                '<label class="erp-omd-front-table-search">' +
                    '<span>' + searchLabel + '</span>' +
                    '<input type="search" class="erp-omd-front-table-search-input" placeholder="' + searchPlaceholder + '">' +
                '</label>' +
                '<label class="erp-omd-front-table-size">' +
                    '<span>' + viewLabel + '</span>' +
                    '<select class="erp-omd-front-table-size-select">' +
                        '<option value="25">25</option>' +
                        '<option value="50">50</option>' +
                        '<option value="100" selected>100</option>' +
                        '<option value="200">200</option>' +
                    '</select>' +
                '</label>' +
                '<div class="erp-omd-front-table-pagination">' +
                    '<button type="button" class="erp-omd-front-button erp-omd-front-button-small erp-omd-front-table-prev">←</button>' +
                    '<span class="erp-omd-front-table-page-meta">1/1</span>' +
                    '<button type="button" class="erp-omd-front-button erp-omd-front-button-small erp-omd-front-table-next">→</button>' +
                '</div>' +
                '<span class="erp-omd-front-table-results"></span>';
            if (wrap) {
                wrap.parentNode.insertBefore(controls, wrap);
            }

            var searchInput = controls.querySelector('.erp-omd-front-table-search-input');
            var resultsNode = controls.querySelector('.erp-omd-front-table-results');
            var pageSizeSelect = controls.querySelector('.erp-omd-front-table-size-select');
            var paginationMeta = controls.querySelector('.erp-omd-front-table-page-meta');
            var paginationPrev = controls.querySelector('.erp-omd-front-table-prev');
            var paginationNext = controls.querySelector('.erp-omd-front-table-next');
            var activeSort = { index: -1, dir: 'asc' };
            var currentPage = 1;
            var pageSize = 100;

            var applyView = function () {
                var query = ((searchInput && searchInput.value) || '').toLowerCase().trim();
                var visibleRows = [];

                allRows.forEach(function (row) {
                    var matches = query === '' || row.textContent.toLowerCase().indexOf(query) !== -1;
                    row.hidden = !matches;
                    if (matches) {
                        visibleRows.push(row);
                    }
                });

                if (activeSort.index >= 0) {
                    visibleRows.sort(function (rowA, rowB) {
                        var cellA = rowA.children[activeSort.index];
                        var cellB = rowB.children[activeSort.index];
                        var valueA = parseSortableValue(cellA ? cellA.textContent : '');
                        var valueB = parseSortableValue(cellB ? cellB.textContent : '');
                        if (valueA === valueB) {
                            return 0;
                        }
                        var comparison = valueA > valueB ? 1 : -1;
                        return activeSort.dir === 'asc' ? comparison : -comparison;
                    });
                    visibleRows.forEach(function (row) {
                        tbody.appendChild(row);
                    });
                }

                var pagesCount = Math.max(1, Math.ceil(visibleRows.length / pageSize));
                currentPage = Math.min(currentPage, pagesCount);
                currentPage = Math.max(1, currentPage);
                var start = (currentPage - 1) * pageSize;
                var end = start + pageSize;

                visibleRows.forEach(function (row, index) {
                    row.hidden = index < start || index >= end;
                });

                if (paginationMeta) {
                    paginationMeta.textContent = currentPage + '/' + pagesCount;
                }
                if (paginationPrev) {
                    paginationPrev.disabled = currentPage <= 1;
                }
                if (paginationNext) {
                    paginationNext.disabled = currentPage >= pagesCount;
                }
                if (resultsNode) {
                    resultsNode.textContent = visibleRowsLabel + ' ' + visibleRows.length + '/' + allRows.length;
                }
            };

            table.querySelectorAll('thead th').forEach(function (header, index) {
                if (actionHeaders.indexOf(header.textContent.trim()) !== -1) {
                    return;
                }
                header.classList.add('erp-omd-front-table-th-sortable');
                header.addEventListener('click', function () {
                    if (activeSort.index === index) {
                        activeSort.dir = activeSort.dir === 'asc' ? 'desc' : 'asc';
                    } else {
                        activeSort.index = index;
                        activeSort.dir = 'asc';
                    }
                    table.querySelectorAll('thead th').forEach(function (th) {
                        th.removeAttribute('data-sort-dir');
                    });
                    header.setAttribute('data-sort-dir', activeSort.dir);
                    applyView();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    currentPage = 1;
                    applyView();
                });
            }
            if (pageSizeSelect) {
                pageSizeSelect.addEventListener('change', function () {
                    pageSize = Number(pageSizeSelect.value) || 100;
                    currentPage = 1;
                    applyView();
                });
            }
            if (paginationPrev) {
                paginationPrev.addEventListener('click', function () {
                    currentPage -= 1;
                    applyView();
                });
            }
            if (paginationNext) {
                paginationNext.addEventListener('click', function () {
                    currentPage += 1;
                    applyView();
                });
            }
            applyView();
        });
    };

    var setupProjectsTableFilters = function (options) {
        var settings = options || {};
        var actionHeader = settings.actionHeader || 'Akcja';
        var table = document.querySelector('table[data-projects-table="1"]');
        if (!table) {
            return;
        }

        var tbody = table.querySelector('tbody');
        if (!tbody) {
            return;
        }

        var rows = Array.from(tbody.querySelectorAll('tr'));
        var form = document.querySelector('[data-project-table-filters="1"]');
        if (!form || rows.length === 0) {
            return;
        }

        var clientFilter = form.querySelector('[data-project-filter="client"]');
        var statusFilter = form.querySelector('[data-project-filter="status"]');
        var billingTypeFilter = form.querySelector('[data-project-filter="billing-type"]');
        var sortState = { index: -1, dir: 'asc' };

        var applyFiltersAndSort = function () {
            var selectedClient = clientFilter ? clientFilter.value : '';
            var selectedStatus = statusFilter ? statusFilter.value : '';
            var selectedBillingType = billingTypeFilter ? billingTypeFilter.value : '';
            var visibleRows = [];

            rows.forEach(function (row) {
                var matchesClient = selectedClient === '' || row.getAttribute('data-client') === selectedClient;
                var matchesStatus = selectedStatus === '' || row.getAttribute('data-status') === selectedStatus;
                var matchesBillingType = selectedBillingType === '' || row.getAttribute('data-billing-type') === selectedBillingType;
                var isVisible = matchesClient && matchesStatus && matchesBillingType;
                row.hidden = !isVisible;
                if (isVisible) {
                    visibleRows.push(row);
                }
            });

            if (sortState.index >= 0) {
                visibleRows.sort(function (rowA, rowB) {
                    var cellA = rowA.children[sortState.index];
                    var cellB = rowB.children[sortState.index];
                    var valueA = (cellA ? cellA.textContent : '').trim().toLowerCase();
                    var valueB = (cellB ? cellB.textContent : '').trim().toLowerCase();
                    var numericA = Number(valueA.replace(',', '.').replace(/[^\d.-]/g, ''));
                    var numericB = Number(valueB.replace(',', '.').replace(/[^\d.-]/g, ''));
                    var comparableA = Number.isNaN(numericA) ? valueA : numericA;
                    var comparableB = Number.isNaN(numericB) ? valueB : numericB;
                    if (comparableA === comparableB) {
                        return 0;
                    }
                    var comparison = comparableA > comparableB ? 1 : -1;
                    return sortState.dir === 'asc' ? comparison : -comparison;
                });
                visibleRows.forEach(function (row) {
                    tbody.appendChild(row);
                });
            }
        };

        table.querySelectorAll('thead th').forEach(function (header, index) {
            if (header.textContent.trim() === actionHeader) {
                return;
            }
            header.classList.add('erp-omd-front-table-th-sortable');
            header.addEventListener('click', function () {
                if (sortState.index === index) {
                    sortState.dir = sortState.dir === 'asc' ? 'desc' : 'asc';
                } else {
                    sortState.index = index;
                    sortState.dir = 'asc';
                }
                table.querySelectorAll('thead th').forEach(function (th) {
                    th.removeAttribute('data-sort-dir');
                });
                header.setAttribute('data-sort-dir', sortState.dir);
                applyFiltersAndSort();
            });
        });

        [clientFilter, statusFilter, billingTypeFilter].forEach(function (filterField) {
            if (!filterField) {
                return;
            }
            filterField.addEventListener('change', applyFiltersAndSort);
        });

        applyFiltersAndSort();
    };

    var setupManagerTimeEntryForm = function () {
        var clientInput = document.getElementById('erp-omd-manager-time-client');
        var projectInput = document.getElementById('erp-omd-manager-time-project');
        if (!clientInput || !projectInput) {
            return;
        }

        var syncProjectOptions = function () {
            var selectedClientId = clientInput.value;
            var hasVisibleSelectedOption = false;

            Array.prototype.forEach.call(projectInput.options, function (option) {
                if (option.value === '') {
                    option.hidden = false;
                    return;
                }

                var optionClientId = option.getAttribute('data-client-id') || '';
                var visible = selectedClientId !== '' && optionClientId === selectedClientId;
                option.hidden = !visible;

                if (visible && option.selected) {
                    hasVisibleSelectedOption = true;
                }
            });

            if (!hasVisibleSelectedOption) {
                projectInput.value = '';
            }
        };

        clientInput.addEventListener('change', syncProjectOptions);
        syncProjectOptions();
    };

    var setupApprovalQueueFilters = function (options) {
        var settings = options || {};
        var actionsHeader = settings.actionsHeader || 'Akcje';
        var table = document.querySelector('table[data-approval-queue-table="1"]');
        var form = document.querySelector('[data-approval-queue-filters="1"]');
        if (!table || !form) {
            return;
        }

        var tbody = table.querySelector('tbody');
        if (!tbody) {
            return;
        }

        var rows = Array.from(tbody.querySelectorAll('tr'));
        if (rows.length === 0) {
            return;
        }

        var employeeFilter = form.querySelector('[data-queue-filter="employee"]');
        var projectFilter = form.querySelector('[data-queue-filter="project"]');
        var roleFilter = form.querySelector('[data-queue-filter="role"]');
        var sortState = { index: -1, dir: 'asc' };

        var parseComparableValue = function (value) {
            var normalized = (value || '').replace(/\s+/g, ' ').trim().toLowerCase();
            var numeric = normalized.replace(',', '.').replace(/[^\d.-]/g, '');
            if (numeric !== '' && !Number.isNaN(Number(numeric))) {
                return Number(numeric);
            }
            return normalized;
        };

        var applyFiltersAndSort = function () {
            var selectedEmployee = employeeFilter ? employeeFilter.value : '';
            var selectedProject = projectFilter ? projectFilter.value : '';
            var selectedRole = roleFilter ? roleFilter.value : '';
            var visibleRows = [];

            rows.forEach(function (row) {
                var matchesEmployee = selectedEmployee === '' || row.getAttribute('data-queue-employee') === selectedEmployee;
                var matchesProject = selectedProject === '' || row.getAttribute('data-queue-project') === selectedProject;
                var matchesRole = selectedRole === '' || row.getAttribute('data-queue-role') === selectedRole;
                var isVisible = matchesEmployee && matchesProject && matchesRole;
                row.hidden = !isVisible;
                if (isVisible) {
                    visibleRows.push(row);
                }
            });

            if (sortState.index >= 0) {
                visibleRows.sort(function (rowA, rowB) {
                    var cellA = rowA.children[sortState.index];
                    var cellB = rowB.children[sortState.index];
                    var valueA = parseComparableValue(cellA ? cellA.textContent : '');
                    var valueB = parseComparableValue(cellB ? cellB.textContent : '');
                    if (valueA === valueB) {
                        return 0;
                    }
                    var comparison = valueA > valueB ? 1 : -1;
                    return sortState.dir === 'asc' ? comparison : -comparison;
                });
                visibleRows.forEach(function (row) {
                    tbody.appendChild(row);
                });
            }
        };

        table.querySelectorAll('thead th').forEach(function (header, index) {
            if (header.textContent.trim() === actionsHeader) {
                return;
            }
            header.classList.add('erp-omd-front-table-th-sortable');
            header.addEventListener('click', function () {
                if (sortState.index === index) {
                    sortState.dir = sortState.dir === 'asc' ? 'desc' : 'asc';
                } else {
                    sortState.index = index;
                    sortState.dir = 'asc';
                }
                table.querySelectorAll('thead th').forEach(function (th) {
                    th.removeAttribute('data-sort-dir');
                });
                header.setAttribute('data-sort-dir', sortState.dir);
                applyFiltersAndSort();
            });
        });

        [employeeFilter, projectFilter, roleFilter].forEach(function (filterField) {
            if (!filterField) {
                return;
            }
            filterField.addEventListener('change', applyFiltersAndSort);
        });

        applyFiltersAndSort();
    };

    var setupEstimateItemsForm = function (options) {
        var settings = options || {};
        var removeItemLabel = settings.removeItemLabel || 'Usuń pozycję';
        var itemsContainer = document.getElementById('erp-omd-front-estimate-items');
        var addButton = document.getElementById('erp-omd-front-add-item');
        var netNode = document.getElementById('erp-omd-front-estimate-net');
        var taxNode = document.getElementById('erp-omd-front-estimate-tax');
        var grossNode = document.getElementById('erp-omd-front-estimate-gross');

        if (!itemsContainer || !addButton || !netNode || !taxNode || !grossNode) {
            return;
        }

        var formatAmount = function (value) { return Number(value || 0).toFixed(2); };
        var updateTotals = function () {
            var net = 0;
            itemsContainer.querySelectorAll('.erp-omd-front-estimate-item-row').forEach(function (row) {
                var qtyInput = row.querySelector('input[name="item_qty[]"]');
                var priceInput = row.querySelector('input[name="item_price[]"]');
                var qty = parseFloat(qtyInput ? qtyInput.value : '0');
                var price = parseFloat(priceInput ? priceInput.value : '0');
                net += qty * price;
            });
            var tax = net * 0.23;
            var gross = net + tax;
            netNode.textContent = formatAmount(net);
            taxNode.textContent = formatAmount(tax);
            grossNode.textContent = formatAmount(gross);
        };

        var bindRow = function (row) {
            row.querySelectorAll('input').forEach(function (input) {
                input.addEventListener('input', updateTotals);
            });
            var removeButton = row.querySelector('.erp-omd-front-remove-item');
            if (removeButton) {
                removeButton.addEventListener('click', function () {
                    if (itemsContainer.querySelectorAll('.erp-omd-front-estimate-item-row').length <= 1) {
                        return;
                    }
                    row.remove();
                    updateTotals();
                });
            }
        };

        var firstRow = itemsContainer.querySelector('.erp-omd-front-estimate-item-row');
        if (firstRow) {
            bindRow(firstRow);
        }

        addButton.addEventListener('click', function () {
            var row = itemsContainer.querySelector('.erp-omd-front-estimate-item-row');
            if (!row) {
                return;
            }
            var clone = row.cloneNode(true);
            clone.querySelectorAll('input[type="text"], textarea').forEach(function (node) { node.value = ''; });
            clone.querySelectorAll('input[type="number"]').forEach(function (node) {
                node.value = node.name === 'item_qty[]' ? '1' : '0';
            });
            if (!clone.querySelector('.erp-omd-front-remove-item')) {
                var removeWrap = document.createElement('div');
                removeWrap.className = 'erp-omd-front-inline-actions';
                removeWrap.innerHTML = '<button type="button" class="erp-omd-front-button erp-omd-front-button-ghost erp-omd-front-remove-item">' + removeItemLabel + '</button>';
                clone.appendChild(removeWrap);
            }
            itemsContainer.appendChild(clone);
            bindRow(clone);
            updateTotals();
        });
        updateTotals();
    };

    window.erpOmdFrontManager = window.erpOmdFrontManager || {};
    window.erpOmdFrontManager.setupTabs = setupManagerTabs;
    window.erpOmdFrontManager.setupTableEnhancements = setupTableEnhancements;
    window.erpOmdFrontManager.setupProjectsTableFilters = setupProjectsTableFilters;
    window.erpOmdFrontManager.setupManagerTimeEntryForm = setupManagerTimeEntryForm;
    window.erpOmdFrontManager.setupApprovalQueueFilters = setupApprovalQueueFilters;
    window.erpOmdFrontManager.setupEstimateItemsForm = setupEstimateItemsForm;
}(window, document));
