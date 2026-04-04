(function (window, document) {
    'use strict';

    var dedupeProjectRequestDateFields = function () {
        document.querySelectorAll('form.erp-omd-front-form').forEach(function (formNode) {
            if (!formNode.querySelector('[name="brief"]')) {
                return;
            }

            ['start_date', 'end_date'].forEach(function (fieldName) {
                var fieldNodes = Array.from(formNode.querySelectorAll('input[name="' + fieldName + '"]'));
                if (fieldNodes.length <= 1) {
                    return;
                }

                fieldNodes.slice(1).forEach(function (fieldNode) {
                    var rowNode = fieldNode.closest('.erp-omd-front-form-row');
                    if (rowNode) {
                        rowNode.remove();
                        return;
                    }
                    fieldNode.remove();
                });
            });
        });
    };

    var setupCollapsibleSections = function (options) {
        var settings = options || {};
        var storagePrefix = settings.storagePrefix || 'erp_omd_front_section_';
        var collapsedLabel = settings.collapsedLabel || 'Rozwiń';
        var expandedLabel = settings.expandedLabel || 'Zwiń';

        document.querySelectorAll('[data-collapsible-section]').forEach(function (panel) {
            var sectionKey = panel.getAttribute('data-collapsible-section');
            if (!sectionKey) {
                return;
            }

            var headerNode = panel.querySelector(':scope > .erp-omd-front-section-heading');
            if (!headerNode) {
                var heading = panel.querySelector(':scope > h2, :scope > h3');
                if (!heading) {
                    return;
                }
                headerNode = document.createElement('div');
                headerNode.className = 'erp-omd-front-collapsible-header';
                heading.parentNode.insertBefore(headerNode, heading);
                headerNode.appendChild(heading);
            }

            if (headerNode.querySelector('.erp-omd-front-collapse-toggle')) {
                return;
            }

            var contentNodes = Array.from(panel.children).filter(function (child) {
                return child !== headerNode;
            });

            var toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'erp-omd-front-collapse-toggle';
            headerNode.appendChild(toggle);

            var storageKey = storagePrefix + sectionKey;
            var isCollapsed = localStorage.getItem(storageKey) === '1';
            var applyState = function () {
                contentNodes.forEach(function (node) {
                    node.hidden = isCollapsed;
                });
                toggle.textContent = isCollapsed ? collapsedLabel : expandedLabel;
                panel.classList.toggle('erp-omd-front-panel-collapsed', isCollapsed);
            };

            toggle.addEventListener('click', function () {
                isCollapsed = !isCollapsed;
                localStorage.setItem(storageKey, isCollapsed ? '1' : '0');
                applyState();
            });

            applyState();
        });
    };

    window.erpOmdFrontShared = window.erpOmdFrontShared || {};
    window.erpOmdFrontShared.dedupeProjectRequestDateFields = dedupeProjectRequestDateFields;
    window.erpOmdFrontShared.setupCollapsibleSections = setupCollapsibleSections;
}(window, document));
