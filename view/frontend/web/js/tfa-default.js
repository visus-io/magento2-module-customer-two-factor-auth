define([
    'Visus_CustomerTfa/js/action/synchronize',
    'Visus_CustomerTfa/js/action/validate-challenge',
    'Visus_CustomerTfa/js/model/challenge-popup',
    'Visus_CustomerTfa/js/model/synchronize-popup',
    'Visus_CustomerTfa/js/model/recovery-popup'
], function (
    synchronizeAction,
    validateChallengeAction,
    challengeModel,
    synchronizeModel,
    recoveryCodesModel
) {
    'use strict';

    return {
        init: function () {
            validateChallengeAction.onExecuted(function (data) {
                if (challengeModel.getContext() === 'enable' && data?.success) {
                    challengeModel.closeModal();
                    synchronizeModel.showModal();
                }

                if (challengeModel.getContext() === 'disable' && data?.success) {
                    challengeModel.closeModal();

                    // TODO: Reset MFA

                    window.location.reload();
                }

                return this;
            }.bind(this));

            synchronizeAction.onExecuted(function (data) {
                if (data?.success) {
                    synchronizeModel.closeModal();
                    recoveryCodesModel.showModal();
                }
            });

            recoveryCodesModel.onClosed(function () {
                window.location.reload();
            });
        }
    };
});
