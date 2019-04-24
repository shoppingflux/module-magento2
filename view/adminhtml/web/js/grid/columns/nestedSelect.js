define([
    'underscore',
    'Magento_Ui/js/grid/columns/column'
], function (_, Column) {
    'use strict';

    return Column.extend({
        getLabel: function () {
            var options = this.options || [],
                values = this._super(),
                label = [];

            if (_.isString(values)) {
                values = values.split(',');
            }

            if (!_.isArray(values)) {
                values = [values];
            }

            values = values.map(function (value) {
                return value + '';
            });

            options = this.flatOptions(options);

            options.forEach(function (item) {
                if (_.contains(values, item.value + '')) {
                    label.push(item.label);
                }
            });

            return label.join(', ');
        },

        flatOptions: function (options) {
            var self = this;

            if (!_.isArray(options)) {
                options = _.values(options);
            }

            return options.reduce(function (flatOptions, option) {
                if (_.isArray(option.value)) {
                    flatOptions = flatOptions.concat(
                        _.map(
                            self.flatOptions(option.value),
                            function (subOption) {
                                return {
                                    value: subOption.value,
                                    label: option.label + ' - ' + subOption.label
                                };
                            }
                        )
                    );
                } else {
                    flatOptions.push(option);
                }

                return flatOptions;
            }, []);
        }
    });
});
