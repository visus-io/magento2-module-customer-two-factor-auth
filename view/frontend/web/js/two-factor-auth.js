define([
    'uiComponent',
    'Visus_CustomerTfa/js/tfa-default',
    'Visus_CustomerTfa/js/tfa-disabled',
    'Visus_CustomerTfa/js/tfa-enabled'
], function (Component, tfaDefault, tfaDisabled, tfaEnabled) {
    'use strict';

    return Component.extend({
        /**
         * @param {Array} params
         * @param {Element} element
         */
       initialize: function (params, element) {
           let isEnabled = params.enabled;
           if (isEnabled) {
               tfaEnabled.init();
           } else {
               tfaDisabled.init();
           }

           tfaDefault.init();

           this._super();
       }
    });
});
