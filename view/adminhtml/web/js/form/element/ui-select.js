define([
    'underscore',
    'jquery',
    'ko',
    'mage/translate',
    'Magento_Ui/js/form/element/ui-select'
], function (_, $, ko, $t, UiSelect) {
    'use strict';

    return UiSelect.extend({
        defaults: {
            selectionBaseValues: [],
            isSelectionBaseExcluding: true,
            selectionOptionClassName: '_in-selection',
            nonSelectionOptionClassName: '_off-selection',
            selectionOptionSelectedNote: null,
            nonSelectionOptionSelectedNote: null,
            clearBtn: false,
            clearBtnLabel: $t('Clear'),
            resetBtn: false,
            resetBtnLabel: $t('Reset'),
            actionsContainerSelector: '.admin__action-multiselect-actions-wrap'
        },

        initialize: function () {
            this._super();

            if (this.clearBtn || this.resetBtn) {
                this.__closeBtn = this.closeBtn;
                this.closeBtn = true;
                this.hasInitializedActionsContainer = false;
                $.async(this.actionTargetSelector, this, this.initSelectionOption.bind(this));
                $.async(this.actionsContainerSelector, this, this.initActionsContainer.bind(this));
            }

            return this;
        },

        initObservable: function () {
            this._super();
            this.value.subscribe(this.onOptionsChange.bind(this));

            this.selectionOptionSelected = false;
            this.nonSelectionOptionSelected = false;

            this.observe([
                'selectionOptionSelected',
                'selectionOptionSelectedNote',
                'nonSelectionOptionSelected',
                'nonSelectionOptionSelectedNote'
            ]);

            this.hasSelectionOptionSelectedNote = ko.pureComputed(function () {
                return this.selectionOptionSelected() && this.selectionOptionSelectedNote();
            }.bind(this));

            this.hasNonSelectionOptionSelectedNote = ko.pureComputed(function () {
                return this.nonSelectionOptionSelected() && this.nonSelectionOptionSelectedNote();
            }.bind(this));

            return this;
        },

        initActionsContainer: function (actionsContainer) {
            if (!this.hasInitializedActionsContainer) {
                this.hasInitializedActionsContainer = true;

                ko.renderTemplate(
                    'ShoppingFeed_Manager/form/element/ui-select/buttons',
                    {
                        clearButton: this.clearBtn,
                        clearLabel: this.clearBtnLabel,
                        onClear: this.onClearButtonClick.bind(this),
                        resetButton: this.resetBtn,
                        resetLabel: this.resetBtnLabel,
                        onReset: this.onResetButtonClick.bind(this),
                        closeButton: this.__closeBtn,
                        closeLabel: this.closeBtnLabel,
                        onClose: this.outerClick.bind(this)
                    },
                    {},
                    actionsContainer
                );
            }
        },

        initSelectionOption: function (option) {
            if (this.isValueInSelection(ko.dataFor(option).value)) {
                $(option).addClass(this.selectionOptionClassName);
            } else {
                $(option).addClass(this.nonSelectionOptionClassName);
            }
        },

        setInitialValue: function () {
            this._super();

            if (!_.has(this, 'resetValue')) {
                this.resetValue = _.clone(ko.toJS(this.initialValue));
            }

            return this;
        },

        isValueInSelection: function (value) {
            var isInSelectionBase = _.contains(this.selectionBaseValues, value);
            return this.isSelectionBaseExcluding ? !isInSelectionBase : isInSelectionBase;
        },

        extractListSelectionValues: function (values) {
            return this.isSelectionBaseExcluding
                ? _.difference(values, this.selectionBaseValues)
                : _.intersection(values, this.selectionBaseValues);
        },

        onClearButtonClick: function () {
            this.value([]);
        },

        onResetButtonClick: function () {
            this.value(_.clone(this.resetValue));
        },

        onOptionsChange: function (optionValues) {
            if (this.multiple) {
                var selectionValues = this.extractListSelectionValues(optionValues);
                this.selectionOptionSelected(selectionValues.length > 0);
                this.nonSelectionOptionSelected(optionValues.length > selectionValues.length);
            } else {
                this.selectionOptionSelected(false);
                this.nonSelectionOptionSelected(false);

                if ((_.isArray(optionValues) && !_.isEmpty(optionValues)) || (null !== optionValues)) {
                    var optionValue = _.first(_.flatten([optionValues]));

                    if (this.isValueInSelection(optionValue)) {
                        this.selectionOptionSelected(true);
                    } else {
                        this.nonSelectionOptionSelected(true);
                    }
                }
            }
        }
    });
});
