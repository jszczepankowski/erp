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

    window.erpOmdFrontShared = window.erpOmdFrontShared || {};
    window.erpOmdFrontShared.dedupeProjectRequestDateFields = dedupeProjectRequestDateFields;
}(window, document));
