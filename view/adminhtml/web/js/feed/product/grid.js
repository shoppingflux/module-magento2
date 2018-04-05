define([
    'jquery',
    'underscore',
    'mage/adminhtml/grid'
], function ($, _) {
    'use strict';

    return function (config) {
        var selectedProductIds = _.isArray(config.selectedProductIds) ? config.selectedProductIds : [];
        var selectionInput = $('#' + config.selectionInputId);
        var selectionParameterName = String(config.selectionParameterName);
        var gridJsObject = window[String(config.gridJsObjectName)];

        selectionInput.val(Object.toJSON(selectedProductIds));

        function registerProductSelection(grid, element, checked) {
            var productId = Number(element.value);

            if (productId) {
                if (checked) {
                    selectedProductIds.push(productId);
                } else {
                    selectedProductIds = _.without(selectedProductIds, productId);
                }

                selectionInput.val(Object.toJSON(selectedProductIds));
                var parameterKey = selectionParameterName + '[]';
                grid.reloadParams[parameterKey] = selectedProductIds;
            }
        }

        function onProductRowClick(grid, event) {
            var target = $(event.target);
            var row = target.closest('tr');

            if (row.length) {
                var checkbox = row.find('input[type="checkbox"]').first();
                var isCheckboxClicked = target.is('input[type="checkbox"]');

                if (checkbox.length) {
                    var isCheckboxChecked = isCheckboxClicked ? checkbox.prop('checked') : !checkbox.prop('checked');
                    gridJsObject.setCheckboxChecked(checkbox[0], isCheckboxChecked);
                }
            }
        }

        gridJsObject.rowClickCallback = onProductRowClick;
        gridJsObject.checkboxCheckCallback = registerProductSelection;
    };
});
