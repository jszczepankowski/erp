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

    window.erpOmdFrontManager = window.erpOmdFrontManager || {};
    window.erpOmdFrontManager.setupTabs = setupManagerTabs;
    window.erpOmdFrontManager.setupTableEnhancements = setupTableEnhancements;
}(window, document));
