define([
    'jquery',
    'ko',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'Visus_CustomerTfa/js/action/recovery'
], function (
    $,
    ko,
    modal,
    $t,
    recoveryAction
) {
   'use strict';

   let callbacks = [];

   return {
       modalWindow: null,
       nonceValidationCookieName: window.vsCustomerTfa.nonceValidationCookieName,
       settings: {
           recoveryCodes: ko.observableArray([])
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
               title: $t('Save Recovery Codes'),
               buttons: [{
                   text: $t('Submit'),
                   class: 'action primary',
                   click: function () {
                       this.closeModal();
                   }
               }],
               opened: function () {
                   $('#input-checkbox-confirmed-saved-recovery-codes').change(function () {
                       $('.modal-footer button').prop('disabled', !this.checked);
                   });

                   $('.modal-footer button').prop('disabled', true);
               },
               closed: function () {
                   callbacks.forEach(function (callback) {
                       callback($('#input-checkbox-confirmed-saved-recovery-codes').is(':checked'));
                   });
               }
           };

           this.modalWindow = element;
           modal(options, $(this.modalWindow));
       },

       /**
        * @param {Function} callback
        */
       onClosed: function (callback) {
           callbacks.push(callback);
       },

       showModal: function () {
           let self = this;

           $('body').trigger('processStart');

           self.settings.recoveryCodes.removeAll();

           recoveryAction(this.nonceValidationCookieName).done(function (response) {
               $('body').trigger('processStop');

               if (response.data !== null && Array.isArray(response.data)) {
                   $.each(response.data, function (i, item) {
                       self.settings.recoveryCodes.push(item);
                   });
               }

               $(this.modalWindow).modal('openModal').trigger('contentUpdated');
               $('#input-checkbox-confirmed-saved-recovery-codes').prop('checked', false);
           }.bind(this)).fail(function () {
               $('body').trigger('processStop');
           });
       }
   }
});
