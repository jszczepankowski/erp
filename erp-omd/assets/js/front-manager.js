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

    window.erpOmdFrontManager = window.erpOmdFrontManager || {};
    window.erpOmdFrontManager.setupTabs = setupManagerTabs;
}(window, document));
