<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Controller\Setup;

use Laminas\Http\Response;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Visus\CustomerTfa\Api\Service\CustomerNonceServiceInterface;
use Visus\CustomerTfa\Api\Service\CustomerTfaServiceInterface;
use Visus\CustomerTfa\Controller\AbstractController;
use Visus\CustomerTfa\Controller\ResponseFactory;

class Recovery extends AbstractController implements HttpGetActionInterface
{
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
        if (!$this->isSecureAjaxRequest() || !$this->isCustomerNonceValid()) {
            return $this->responseFactory->create([
                'success' => false,
                'message' => null
            ], Response::STATUS_CODE_400);
        }

        $recoveryCodes = $this->customerTfaService->generateRecoveryCodes((int)$this->customerSession->getCustomerId());
        if (empty($recoveryCodes)) {
            return $this->responseFactory->create([
                'success' => false,
                'message' => null
            ]);
        }

        return $this->responseFactory->create([
            'success' => true,
            'data' => $recoveryCodes
        ]);
    }
}
