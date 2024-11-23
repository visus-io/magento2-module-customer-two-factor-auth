define([
    'jquery',
    'Magento_Ui/js/model/messageList',
    'mage/translate',
    'mage/url'
], function ($, messageContainer, $t, urlBuilder) {
    'use strict';

    return function () {
        if (!$.mage.cookies.get('form_key')) {
            $.mage.formKey();
        }

        if (!$.mage.cookies.get('form_key')) {
            messageContainer.addErrorMessage({'message': $t('Invalid Form Key. Please refresh the page.')});
            return $.when($.Deferred().reject());
        }

        let data = {
            form_key: $.mage.cookies.get('form_key'),
        }

        let url = urlBuilder.build('visus_tfa/challenge/request');

        return $.post(url, data).done(function (response) {
            if (response.success) {
                messageContainer.addSuccessMessage({
                    'message': $t('One-time password has been sent to your email. Please check your inbox and enter the value.')
                });
            } else {
                messageContainer.addErrorMessage({
                    'message': $t('An error was encountered when sending the one-time password email.')
                });
            }
        });
    };
});
