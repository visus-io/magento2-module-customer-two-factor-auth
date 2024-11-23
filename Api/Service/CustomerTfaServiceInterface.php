<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Api\Service;

use Endroid\QrCode\Writer\Result\ResultInterface;
use Magento\Customer\Model\Customer;
use SensitiveParameter;

/**
 * TFA Enrollment and Verification Service
 *
 * @since 1.0.0
 * @api
 */
interface CustomerTfaServiceInterface
{
    /**
     * Creates a QR Code Image from TFA Secret
     *
     * @param Customer $customer
     * @return ResultInterface|null
     */
    public function generateQrCode(Customer $customer): ?ResultInterface;

    /**
     * Generate Recovery Codes
     *
     * @param int $customerId
     * @return array<string>|null
     */
    public function generateRecoveryCodes(int $customerId): ?array;

    /**
     * Gets set of recovery codes
     *
     * @param int $customerId
     * @return array<string>|null
     */
    public function getRecoveryCodes(int $customerId): ?array;

    /**
     * Gets TFA Secret
     *
     * @param int $customerId
     * @return string|null
     */
    public function getSecret(int $customerId): ?string;

    /**
     * Checks if fully enrolled with TFA
     *
     * @param int $customerId
     * @return bool
     */
    public function isEnrolled(int $customerId): bool;

    /**
     * Resets TFA Status
     *
     * @param int $customerId
     * @return bool
     */
    public function reset(int $customerId): bool;

    /**
     * Verifies one-time password against TFA secret
     *
     * @param int $customerId
     * @param string $otp
     * @return bool
     */
    public function verify(int $customerId, #[SensitiveParameter] string $otp): bool;
}
