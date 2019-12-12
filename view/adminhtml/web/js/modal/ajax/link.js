define([
    'require',
    'jquery',
    'mage/translate',
    'jquery/ui',
    'Magento_Ui/js/modal/modal'
], function (require, $, $t) {
    'use strict';

    return function (config, element) {
        var $contentWrapper = null;

        function getContentWrapper() {
            if (null === $contentWrapper) {
                $contentWrapper = $('<div />');
                $contentWrapper.hide();
                $contentWrapper.uniqueId();
                $contentWrapper.appendTo(document.body);
                $contentWrapper.modal($.extend(config, { autoOpen: false }));
            }

            return $contentWrapper;
        }

        $(element).click(function (e) {
            var $element = $(this);
            var $wrapper = getContentWrapper();
            $wrapper.text('');
            $wrapper.modal('openModal');

            $.ajax({
                showLoader: true,
                type: 'get',
                url: $element.attr('href')
            }).done(function (content) {
                $wrapper.html(content);
            }).fail(function () {
                $wrapper.text($t('An error has occurred.'));
            });

            e.preventDefault();
            e.stopPropagation();
        });
    };
});
