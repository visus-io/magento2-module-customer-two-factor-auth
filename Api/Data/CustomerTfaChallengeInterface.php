<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Api\Data;

/**
 * Customer TFA Challenge Record interface for API handling.
 *
 * @api
 * @since 1.0.0
 */
interface CustomerTfaChallengeInterface
{
    public const CUSTOMER_ID = 'customer_id';

    public const CHALLENGE = 'challenge';

    public const EXPIRES_AT = 'expires_at';

    /**
     * Get Challenge
     *
     * @return string|null
     */
    public function getChallenge(): ?string;

    /**
     * Set Challenge
     *
     * @param string $challenge
     * @return $this
     */
    public function setChallenge(string $challenge): static;

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
     * Get Expires At
     *
     * @return string|null
     */
    public function getExpiresAt(): ?string;

    /**
     * Set Expires At
     *
     * @param string $expiresAt
     * @return $this
     */
    public function setExpiresAt(string $expiresAt): static;
}
