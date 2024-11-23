<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Controller\Setup;

use Laminas\Http\Response;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Visus\CustomerTfa\Api\Service\CustomerNonceServiceInterface;
use Visus\CustomerTfa\Api\Service\CustomerTfaServiceInterface;
use Visus\CustomerTfa\Controller\AbstractController;
use Visus\CustomerTfa\Controller\ResponseFactory;

/**
 * Controller for confirming 2FA QR Code (Setup)
 *
 * @since 1.0.0
 */
class Confirm extends AbstractController implements HttpPostActionInterface
{
    /**
     * @var CustomerNonceServiceInterface
     */
    private readonly CustomerNonceServiceInterface $customerNonceService;

    /**
     * @var Session
     */
    private readonly Session $customerSession;

    /**
     * @var CustomerTfaServiceInterface
     */
    private readonly CustomerTfaServiceInterface $customerTfaService;

    /**
     * @var ResponseFactory
     */
    private readonly ResponseFactory $responseFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param CustomerNonceServiceInterface $customerNonceService
     * @param Session $customerSession
     * @param CustomerTfaServiceInterface $customerTfaService
     * @param ResponseFactory $responseFactory
     * @param Validator $validator
     */
    public function __construct(
        Context $context,
        CustomerNonceServiceInterface $customerNonceService,
        Session $customerSession,
        CustomerTfaServiceInterface $customerTfaService,
        ResponseFactory $responseFactory,
        Validator $validator
    ) {
        $this->customerNonceService = $customerNonceService;
        $this->customerSession = $customerSession;
        $this->customerTfaService = $customerTfaService;
        $this->responseFactory = $responseFactory;

        parent::__construct($context, $customerNonceService, $customerSession, $validator);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->isSecureAjaxRequest() || !$this->isCustomerNonceValid() || !$this->isValidFormRequest()) {
            return $this->responseFactory->create([
                'success' => false,
                'message' => null
            ], Response::STATUS_CODE_400);
        }

        $otp = $this->getRequest()->getParam('one-time-password');
        if (empty($otp)) {
            return $this->responseFactory->create([
                'success' => false,
                'message' => __('The parameter \'one-time-password\' is required.')
            ], Response::STATUS_CODE_400);
        }

        if ($this->customerTfaService->verify((int)$this->customerSession->getCustomerId(), $otp)) {
            $this->customerNonceService->generate($this->customerSession->getCustomer());

            return $this->responseFactory->create(['success' => true]);
        } else {
            return $this->responseFactory->create([
                'success' => false,
                'message' => null
            ], Response::STATUS_CODE_403);
        }
    }
}
