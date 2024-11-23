<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Api\Service;

use Magento\Customer\Model\Customer;
use SensitiveParameter;

/**
 * Challenge Generation Service Interface
 *
 * @since 1.0.0
 * @api
 */
interface CustomerTfaChallengeServiceInterface
{

    /**
     * Sends transactional email to customer containing their challenge code.
     *
     * @param Customer $customer
     * @return bool
     */
    public function sendEmail(Customer $customer): bool;

    /**
     * Verifies validity of challenge code.
     *
     * @param int $customerId
     * @param string|null $code
     * @return bool
     */
    public function verify(int $customerId, #[SensitiveParameter] ?string $code): bool;
}
