<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Api;

interface CustomerTfaSessionInterface
{
    /**
     * Passed TFA Key Name
     */
    public const KEY_NAME = 'visus_customer_tfa_passed';

    /**
     * Set TFA session as passed
     *
     * @return void
     */
    public function grantAccess(): void;

    /**
     * Return true if TFA session has been passed
     *
     * @return bool
     */
    public function isGranted(): bool;

    /**
     * Set TFA session as revoked
     *
     * @return void
     */
    public function revokeAccess(): void;
}
