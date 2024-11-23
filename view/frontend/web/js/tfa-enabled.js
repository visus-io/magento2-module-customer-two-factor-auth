define([
    'jquery',
    'Visus_CustomerTfa/js/model/challenge-popup'
], function ($, challengeModel) {
   'use strict';

   return {
       init: function () {
           $('#disable-tfa').on('click', function (e) {
               e.preventDefault();
               challengeModel.setContext('disable');
               challengeModel.showModal();
           });
       }
   };
});
