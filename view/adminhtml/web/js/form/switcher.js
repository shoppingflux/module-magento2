define(
    [
        'underscore',
        'uiRegistry',
        'Magento_Ui/js/form/switcher'
    ],
    function (_, UiRegistry, UiSwitcher) {
        return UiSwitcher.extend({
            defaults: {},

            applyAction: function (action) {
                UiRegistry.get(action.target, function (target) {
                    var notifyData = action.callback.match(/^notify:(.+)$/);

                    if (notifyData && !!notifyData[1]) {
                        var observable = target[notifyData[1]];
                        observable.valueHasMutated();
                    } else if ((action.callback === 'suggest') && (action.params.length === 1)) {
                        var currentValue = target.value();
                        var suggestionKey = 'sfm.last_suggestion.' + action.target;
                        var lastSuggestedValue = UiRegistry.get(suggestionKey);

                        if (_.isNull(currentValue)
                            || _.isUndefined(currentValue)
                            || (String(currentValue).trim() === '')
                            || (currentValue === lastSuggestedValue)
                        ) {
                            target.value(action.params[0]);
                            UiRegistry.set(suggestionKey, action.params[0]);
                        } else {
                            // Do not override user-defined values, even when they are the same as the suggestions.
                            UiRegistry.remove(suggestionKey);
                        }
                    } else {
                        var callback = target[action.callback];
                        callback.apply(target, action.params || []);
                    }
                });
            }
        });
    }
);
