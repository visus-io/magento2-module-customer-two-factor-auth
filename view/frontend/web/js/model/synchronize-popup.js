define([
    'jquery',
    'ko',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'Visus_CustomerTfa/js/action/synchronize',
    'Visus_CustomerTfa/js/action/qr-setup'
], function (
    $,
    ko,
    modal,
    $t,
    synchronizeAction,
    qrSetupAction
) {
    'use strict';

    return {
        modalWindow: null,
        nonceValidationCookieName: window.vsCustomerTfa.nonceValidationCookieName,
        settings: {
            qrCode: ko.observable(null),
            secret: ko.observable(null)
        },

        /**
         * @param {HTMLElement} element
         */
        createPopUp: function (element) {
            let options = {
                type: 'popup',
                modalClass: 'vs-tfa__modal',
                focus: '',
                responsive: true,
                innerScroll: true,
                title: $t('Setup Two-Factor Authentication'),
                buttons: [{
                    text: $t('Verify Code'),
                    class: 'action primary',
                    click: function () {
                        let form = element.querySelector('#verify-code-form');
                        if ($(form).length) {
                            $(form).submit();
                        }
                    }
                }]
            };

            this.modalWindow = element;
            modal(options, $(this.modalWindow));
        },

        showModal: function () {
            $('body').trigger('processStart');

            qrSetupAction(this.nonceValidationCookieName).done(function (response) {
                $('body').trigger('processStop');

                if (typeof response.data === 'object' && !Array.isArray(response.data) && response.data !== null) {
                    this.settings.qrCode(response.data.qrCode);
                    this.settings.secret(response.data.secret);
                }

                $(this.modalWindow).modal('openModal').trigger('contentUpdated');
                $(this.modalWindow).find('form').each(function () {
                    this.reset();
                });
            }.bind(this)).fail(function () {
                $('body').trigger('processStop');
            });
        },

        closeModal: function () {
            $(this.modalWindow).modal('closeModal');
        }
    }
});
