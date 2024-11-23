define([
    'underscore',
    'jquery',
    'ko',
    'Magento_Ui/js/form/form',
    'mage/url',
    'Visus_CustomerTfa/js/model/challenge-popup',
    'Visus_CustomerTfa/js/action/validate-challenge'
], function (
    _
    ,
    $,
    ko,
    Component,
    url,
    challengePopUp,
    validateChallengeAction
) {
    'use strict';

    return Component.extend({
        modalWindow: null,
        isLoading: ko.observable(false),
        modalIdentifier: ko.observable([]),
        defaults: {
            'template': 'Visus_CustomerTfa/view/challenge-popup'
        },

        initialize: function () {
            let self = this;
            this._super();

            url.setBaseUrl(window.authenticationPopup.baseUrl);

            validateChallengeAction.onExecuted(function () {
                self.isLoading(false);
            });
        },

        /**
         * @param {HTMLElement} element
         */
        setModalElement: function (element) {
            if (challengePopUp.modalWindow === null) {
                challengePopUp.createPopUp(element);
            }
        },

        /**
         * Provide action
         *
         * @param {HTMLElement} formUiElement
         * @param {Event} event
         *
         * @return {Boolean}
         */
        onSubmit: function (formUiElement, event) {
            let verifyData = {},
                formElement = $(event.currentTarget),
                formDataArray = formElement.serializeArray();

            event.stopPropagation();

            formDataArray.forEach(function (item) {
                verifyData[item.name] = item.value;
            });

            if (formElement.validation() && formElement.validation('isValid')) {
                this.isLoading(true);
                validateChallengeAction(verifyData);
            }

            return false;
        }

    });
});
