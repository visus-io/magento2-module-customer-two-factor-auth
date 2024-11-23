define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'Visus_CustomerTfa/js/action/request-challenge'
], function ($, modal, $t, requestChallengeAction) {
    'use strict';

    return {
        modalWindow: null,
        context: null,

        /**
         * @param {HTMLElement} element
         */
        createPopUp: function (element) {
            let options = {
                type: 'popup',
                modalClass: 'vs-tfa__modal',
                focus: '[name=challenge]',
                responsive: true,
                innerScroll: true,
                title: $t('Account Access Verification'),
                buttons: [{
                    text: $t('Verify Code'),
                    class: 'action primary',
                    click: function () {
                        let form = element.querySelector('#verify-challenge-form');
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

            requestChallengeAction().done(function () {
                $('body').trigger('processStop');

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
        },

        getContext: function () {
            return this.context;
        },

        setContext: function (context) {
            this.context = context;
        }
    };
});
