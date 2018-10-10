define([
    'underscore',
    'Magento_Ui/js/grid/columns/select'
], function (_, SelectColumn) {
    'use strict';

    return SelectColumn.extend({
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
