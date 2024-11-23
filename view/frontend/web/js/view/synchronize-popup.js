define([
    'underscore',
    'jquery',
    'ko',
    'Magento_Ui/js/form/form',
    'mage/url',
    'Visus_CustomerTfa/js/model/synchronize-popup',
    'Visus_CustomerTfa/js/action/synchronize',
], function (
    _,
    $,
    ko,
    Component,
    url,
    synchronizePopUp,
    synchronizeAction
) {
   'use strict';

   return Component.extend({
       autocomplete: 'off',
       modalWindow: null,
       isLoading: ko.observable(false),
       defaults: {
           'template': 'Visus_CustomerTfa/view/synchronize-popup'
       },
       settings: {
           qrCode: synchronizePopUp.settings.qrCode,
           secret: synchronizePopUp.settings.secret,
       },

       initialize: function () {
           let self = this;
           this._super();

           url.setBaseUrl(window.authenticationPopup.baseUrl);

           synchronizeAction.onExecuted(function () {
               self.isLoading(false);
           });
       },

       /**
        * @param {HTMLElement} element
        */
       setModalElement: function (element) {
           if (synchronizePopUp.modalWindow === null) {
               synchronizePopUp.createPopUp(element);
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
               synchronizeAction(verifyData);
           }

           return false;
       }
   });
});
