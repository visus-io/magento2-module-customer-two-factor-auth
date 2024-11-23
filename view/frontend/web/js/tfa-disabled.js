define([
    'jquery',
    'Visus_CustomerTfa/js/model/challenge-popup'
], function ($, challengeModel) {
   'use strict';

   return {
       init: function () {
           $('#enable-tfa').on('click', function (e) {
               e.preventDefault();
               challengeModel.setContext('enable');
               challengeModel.showModal();
           });
       }
   };
});
