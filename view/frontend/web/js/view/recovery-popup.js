define([
    'underscore',
    'jquery',
    'ko',
    'Magento_Ui/js/form/form',
    'Magento_Ui/js/model/messageList',
    'mage/translate',
    'mage/url',
    'Visus_CustomerTfa/js/model/recovery-popup',
    'Visus_CustomerTfa/js/action/recovery'
], function (
    _,
    $,
    ko,
    Component,
    messageContainer,
    $t,
    url,
    recoveryPopUp,
    recoveryAction
) {
    'use strict';

    return Component.extend({
        autocomplete: 'off',
        modalWindow: null,
        isLoading: ko.observable(false),
        defaults: {
            'template': 'Visus_CustomerTfa/view/recovery-popup'
        },
        settings: {
            recoveryCodes: recoveryPopUp.settings.recoveryCodes
        },

        initialize: function () {
            let self = this;
            this._super();

            url.setBaseUrl(window.authenticationPopup.baseUrl);

            recoveryAction.registerCallback(function () {
                self.isLoading(false);
            });
        },

        copyCodes: async function () {
            let self = this,
                recoveryCodes = self.settings.recoveryCodes.map(function (item) {
                return item;
            }).join("\n");

            await navigator.clipboard.writeText(recoveryCodes);

            messageContainer.addSuccessMessage({'message': $t('Copied')});
        },

        downloadCodes: function () {
            let self = this,
                recoveryCodes = self.settings.recoveryCodes.map(function (item) {
                    return item;
                }).join("\n"),
                blob = new Blob([recoveryCodes], {type: 'text/plain'}),
                element = document.createElement('a');

            let url = new URL(window.BASE_URL);

            element.download = url.hostname.replaceAll('.', '-') + '-recovery-codes.txt';
            element.href = URL.createObjectURL(blob);
            element.dataset.downloadurl = ['text/plain', element.download, element.href].join(';');
            element.style.display = 'none';

            document.body.appendChild(element);
            element.click();
            document.body.removeChild(element);

            messageContainer.addSuccessMessage({'message': $t('Downloaded')});
        },

        printCodes: function () {
            let self = this,
                recoveryCodes = self.settings.recoveryCodes.map(function (item) {
                    return '<li>' + item + '</li>';
                }).join(''),
                printWindow = window.open();

            let url = new URL(window.BASE_URL);

            let header = $t('Recovery Codes');
            let footer = $t('%1 two-factor authentication recovery codes.').replace('%1', url.hostname);
            let body = `<!DOCTYPE html>
<html lang="en">
    <head>
        <title></title>
    </head>
    <body>
        <h1>${header}</h1>
        <ul>${recoveryCodes}</ul>
        <p>${footer}</p>
    </body>
</html>
`
            printWindow.document.open('text/html');
            printWindow.document.write(body);
            printWindow.document.close();

            printWindow.focus();
            printWindow.print();
            printWindow.close();
        },

        /**
         * @param {HTMLElement} element
         */
        setModalElement: function (element) {
            if (recoveryPopUp.modalWindow === null) {
                recoveryPopUp.createPopUp(element);
            }
        },
    });
});
