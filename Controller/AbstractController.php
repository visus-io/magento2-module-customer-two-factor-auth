<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Controller;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Webapi\Request;
use Visus\CustomerTfa\Api\Service\CustomerNonceServiceInterface;

/**
 * Abstract controller for handling AJAX requests
 *
 * @since 1.0.0
 */
abstract class AbstractController
{
    /**
     * @var Context
     */
    private readonly Context $context;

    /**
     * @var CustomerNonceServiceInterface
     */
    private readonly CustomerNonceServiceInterface $customerNonceService;

    /**
     * @var Session
     */
    private readonly Session $customerSession;

    /**
     * @var Validator
     */
    private readonly Validator $validator;

    /**
     * Constructor
     *
     * @param Context $context
     * @param CustomerNonceServiceInterface $customerNonceService
     * @param Session $customerSession
     * @param Validator $validator
     */
    public function __construct(
        Context $context,
        CustomerNonceServiceInterface $customerNonceService,
        Session $customerSession,
        Validator $validator
    ) {
        $this->context = $context;
        $this->customerNonceService = $customerNonceService;
        $this->customerSession = $customerSession;
        $this->validator = $validator;
    }

    /**
     * Get the current request
     *
     * @return RequestInterface
     */
    protected function getRequest(): RequestInterface
    {
        return $this->context->getRequest();
    }

    /**
     * Verifies if the request has a valid nonce
     *
     * @return bool
     */
    protected function isCustomerNonceValid(): bool
    {
        return $this->customerSession->isLoggedIn()
            && $this->customerNonceService->validate($this->customerSession->getCustomer());
    }

    /**
     * Verifies if the request was made through AJAX and HTTPS
     *
     * @return bool
     */
    protected function isSecureAjaxRequest(): bool
    {
        /** @var Request $request */
        $request = $this->context->getRequest();

        return $request->isSecure() && $request->isXmlHttpRequest();
    }

    /**
     * Verifies if the form request is valid
     *
     * @return bool
     */
    protected function isValidFormRequest(): bool
    {
        return $this->validator->validate($this->context->getRequest());
    }
}
