<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Api\Data;

/**
 * Customer TFA record interface for API handling.
 *
 * @api
 * @since 1.0.0
 */
interface CustomerTfaInterface
{
    public const CUSTOMER_ID = 'customer_id';

    public const SECRET = 'secret';

    public const RECOVERY_CODES = 'recovery_codes';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    /**
     * Get allowed URLs
     *
     * @return array<string>
     */
    public function getAllowedUrls(): array;

    /**
     * Get Created At
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set Created At
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): static;

    /**
     * Get Customer ID
     *
     * @return int|null
     */
    public function getCustomerId(): ?int;

    /**
     * Set Customer ID
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId): static;

    /**
     * Get Recovery Codes
     *
     * @return string|null
     */
    public function getRecoveryCodes(): ?string;

    /**
     * Set Recovery Codes
     *
     * @param string $codes
     * @return $this
     */
    public function setRecoveryCodes(string $codes): static;

    /**
     * Get OTP Secret
     *
     * @return string
     */
    public function getSecret(): string;

    /**
     * Set OTP Secret
     *
     * @param string $secret
     * @return $this
     */
    public function setSecret(string $secret): static;

    /**
     * Get Updated At
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set Updated At
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): static;
}
