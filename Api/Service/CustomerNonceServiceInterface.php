<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Api\Service;

use Magento\Customer\Model\Customer;

/**
 * Nonce Generation and Validation Service Interface
 *
 * @since 1.0.0
 * @api
 */
interface CustomerNonceServiceInterface
{
    /**
     * Generate nonce for the customer
     *
     * @param Customer $customer
     * @return bool
     */
    public function generate(Customer $customer): bool;

    /**
     * Validate customer nonce
     *
     * @param Customer $customer
     * @return bool
     */
    public function validate(Customer $customer): bool;
}
