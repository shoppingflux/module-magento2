require(
    [
        'underscore',
        'Magento_Ui/js/lib/validation/validator'
    ],
    function (_, uiValidator) {
        uiValidator.addRule(
            'sfm-validate-magento-cron-expression',
            function (value) {
                var tokens = _.reject(String(value).split(/\s+/), _.isEmpty);

                if ((tokens.length < 5) && (tokens.length > 6)) {
                    return false;
                }

                return _.every(tokens, checkExpressionToken);
            },
            'Please enter a valid cron expression.'
        );

        function checkExpressionToken(token) {
            // See \Magento\Cron\Model\Schedule::matchCronExpression()

            if ('*' === token) {
                return true;
            }

            var subTokens;

            if (token.indexOf(',') >= 0) {
                subTokens = token.split(',');
                return _.every(subTokens, checkExpressionToken);
            }

            if (token.indexOf('/') >= 0) {
                subTokens = token.split('/');

                if ((2 !== subTokens.length) || isNaN(Number(subTokens[1]))) {
                    return false;
                }

                token = subTokens[0];
            }

            if (token === '*') {
                return true;
            } else if (token.indexOf('-') >= 0) {
                subTokens = token.split('-');

                if (2 !== subTokens.length) {
                    return false;
                }

                return _.isNumber(parseNumericToken(subTokens[0])) && _.isNumber(parseNumericToken(subTokens[1]));
            } else {
                return _.isNumber(parseNumericToken(token));
            }
        }

        function parseNumericToken(token) {
            var namedMap = {
                'jan': 1,
                'feb': 2,
                'mar': 3,
                'apr': 4,
                'may': 5,
                'jun': 6,
                'jul': 7,
                'aug': 8,
                'sep': 9,
                'oct': 10,
                'nov': 11,
                'dec': 12,
                'sun': 0,
                'mon': 1,
                'tue': 2,
                'wed': 3,
                'thu': 4,
                'fri': 5,
                'sat': 6
            };

            var number = Number(token);

            if (!isNaN(number)) {
                return number;
            }

            token = String(token).substr(0, 3).toLowerCase();
            return namedMap[token] ? namedMap[token] : false;
        }
    }
);
