<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\ViewModel\Customer;

use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Visus\CustomerTfa\Api\Service\CustomerTfaServiceInterface;
use Visus\CustomerTfa\Model\Config;

class TfaViewModel implements ArgumentInterface
{
    /**
     * @var CustomerTfaServiceInterface
     */
    private readonly CustomerTfaServiceInterface $customerTfaService;

    /**
     * @var Session
     */
    private readonly Session $customerSession;

    /**
     * Constructor
     *
     * @param CustomerTfaServiceInterface $customerTfaService
     * @param Session $customerSession
     */
    public function __construct(
        CustomerTfaServiceInterface $customerTfaService,
        Session $customerSession
    ) {
        $this->customerTfaService = $customerTfaService;
        $this->customerSession = $customerSession;
    }

    /**
     * Checks if customer is enrolled in TFA
     *
     * @return bool
     */
    public function isEnrolled(): bool
    {
        return $this->customerTfaService->isEnrolled((int)$this->customerSession->getCustomerId());
    }

    /**
     * Returns the name of the nonce validation cookie
     *
     * @return string
     */
    public function getNonceValidationCookieName(): string
    {
        return Config::COOKIE_NAME;
    }
}
