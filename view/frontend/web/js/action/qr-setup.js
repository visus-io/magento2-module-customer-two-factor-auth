define([
    'jquery',
    'Magento_Ui/js/model/messageList',
    'mage/translate',
    'mage/url'
], function ($, messageContainer, $t, urlBuilder) {
    'use strict';

    let callbacks = [],
        /**
         * @param {String} cookieName
         */
        action = function (cookieName) {
            if (!$.mage.cookies.get(cookieName)) {
                messageContainer.addErrorMessage({'message': $t('Invalid nonce validation token. Please refresh the page.')});
                return $.when($.Deferred().reject());
            }

            let url = urlBuilder.build('visus_tfa/setup/setup');

            return $.get(url).done(function (response) {
                if (!response.success) {
                    messageContainer.addErrorMessage({'message': $t('QR code setup has failed. Please refresh the page.')})
                }

                callbacks.forEach(function (callback) {
                    callback(response)
                });
            }).fail(function () {
                messageContainer.addErrorMessage({'message': $t('Invalid or expired nonce. Please refresh the page.')});
            }).always(function (response) {
                if (response.message) {
                    messageContainer.addErrorMessage({'message': response.message});
                }
            });
        };

    action.registerCallback = function (callback) {
        callbacks.push(callback);
    }

    return action;
});
