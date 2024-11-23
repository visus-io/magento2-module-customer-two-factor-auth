define([
    'jquery',
    'Magento_Ui/js/model/messageList',
    'mage/translate',
    'mage/url'
], function ($, messageContainer, $t, urlBuilder) {
    'use strict';

    let callbacks = [],

        /**
         * @param {Object} data
         */
        action = function (data) {
            if (!$.mage.cookies.get('form_key')) {
                $.mage.formKey();
            }

            if (!$.mage.cookies.get('form_key')) {
                messageContainer.addErrorMessage({'message': $t('Invalid Form Key. Please refresh the page.')});

                callbacks.forEach(function (callback) {
                    callback({'error': true});
                });
            } else {
                data['form_key'] = $.mage.cookies.get('form_key');
            }

            let url = urlBuilder.build('visus_tfa/challenge/verify');

            return $.post(url, data).done(function (response) {
                callbacks.forEach(function (callback) {
                    callback(response)
                });
            }).fail(function () {
                messageContainer.addErrorMessage({'message': $t('Invalid or expired verification code')});

                callbacks.forEach(function (callback) {
                    callback({'success': false});
                })
            }).always(function (response) {
                if (response.message) {
                    messageContainer.addErrorMessage({'message': response.message});
                }
            });
        };

    /**
     * @param {Function} callback
     */
    action.onExecuted = function (callback) {
        callbacks.push(callback);
    };

    return action;
});
