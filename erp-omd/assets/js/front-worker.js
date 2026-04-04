(function (window, document) {
    'use strict';

    var setupWorkerTabs = function () {
        var storageKey = 'erp_omd_front_worker_active_tab';
        var allowedTabs = ['dodaj-wpis', 'wpisy', 'kalendarz', 'wnioski'];
        var defaultTab = 'wpisy';
        var params = new URLSearchParams(window.location.search);
        var urlTab = params.get('tab');
        var storedTab = localStorage.getItem(storageKey);
        var activeTab = allowedTabs.indexOf(urlTab) !== -1
            ? urlTab
            : (allowedTabs.indexOf(storedTab) !== -1 ? storedTab : defaultTab);

        localStorage.setItem(storageKey, activeTab);

        if (allowedTabs.indexOf(urlTab) === -1) {
            params.set('tab', activeTab);
            history.replaceState({}, '', window.location.pathname + '?' + params.toString());
        }

        document.querySelectorAll('[data-worker-tab-pane]').forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-worker-tab-pane') !== activeTab;
        });

        document.querySelectorAll('[data-worker-tab-button]').forEach(function (button) {
            var isActive = button.getAttribute('data-worker-tab-button') === activeTab;
            button.classList.toggle('erp-omd-front-button-primary', isActive);
            button.classList.toggle('erp-omd-front-button-ghost', !isActive);
            button.setAttribute('aria-current', isActive ? 'page' : 'false');
        });
    };

    var setupTablePagination = function (options) {
        var settings = options || {};
        var viewLabel = settings.viewLabel || 'Widok:';
        var rowsLabel = settings.rowsLabel || 'Wiersze:';

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
            if (!wrap) {
                return;
            }

            var controls = document.createElement('div');
            controls.className = 'erp-omd-front-table-tools';
            controls.innerHTML =
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
            wrap.parentNode.insertBefore(controls, wrap);

            var pageSizeSelect = controls.querySelector('.erp-omd-front-table-size-select');
            var paginationMeta = controls.querySelector('.erp-omd-front-table-page-meta');
            var paginationPrev = controls.querySelector('.erp-omd-front-table-prev');
            var paginationNext = controls.querySelector('.erp-omd-front-table-next');
            var resultsNode = controls.querySelector('.erp-omd-front-table-results');
            var currentPage = 1;
            var pageSize = 100;

            var applyPagination = function () {
                var pagesCount = Math.max(1, Math.ceil(allRows.length / pageSize));
                currentPage = Math.max(1, Math.min(currentPage, pagesCount));
                var start = (currentPage - 1) * pageSize;
                var end = start + pageSize;

                allRows.forEach(function (row, index) {
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
                    resultsNode.textContent = rowsLabel + ' ' + allRows.length;
                }
            };

            if (pageSizeSelect) {
                pageSizeSelect.addEventListener('change', function () {
                    pageSize = Number(pageSizeSelect.value) || 100;
                    currentPage = 1;
                    applyPagination();
                });
            }
            if (paginationPrev) {
                paginationPrev.addEventListener('click', function () {
                    currentPage -= 1;
                    applyPagination();
                });
            }
            if (paginationNext) {
                paginationNext.addEventListener('click', function () {
                    currentPage += 1;
                    applyPagination();
                });
            }

            applyPagination();
        });
    };

    window.erpOmdFrontWorker = window.erpOmdFrontWorker || {};
    window.erpOmdFrontWorker.setupTabs = setupWorkerTabs;
    window.erpOmdFrontWorker.setupTablePagination = setupTablePagination;
}(window, document));
