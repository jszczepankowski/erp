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

    window.erpOmdFrontWorker = window.erpOmdFrontWorker || {};
    window.erpOmdFrontWorker.setupTabs = setupWorkerTabs;
}(window, document));
