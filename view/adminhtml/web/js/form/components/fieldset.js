define([
    'underscore',
    'Magento_Ui/js/form/components/fieldset'
], function (_, Fieldset) {
    'use strict';

    return Fieldset.extend({
        hide: function () {
            this.visible(false);
        },

        show: function () {
            this.visible(true);
        },

        disable: function () {
            var elements = this.elems();

            _.each(elements, function (element) {
                if (_.isFunction(element.disable)) {
                    element.disable();
                }
            });
        },

        enable: function () {
            var elements = this.elems();

            _.each(elements, function (element) {
                if (_.isFunction(element.enable)) {
                    element.enable();
                }
            });
        }
    });
});
