<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Controller\Challenge;

use Laminas\Http\Response;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Visus\CustomerTfa\Api\Service\CustomerNonceServiceInterface;
use Visus\CustomerTfa\Api\Service\CustomerTfaChallengeServiceInterface;
use Visus\CustomerTfa\Controller\AbstractController;
use Visus\CustomerTfa\Controller\ResponseFactory;

/**
 * Controller to verify one-time email challenges
 *
 * @since 1.0.0
 */
class Verify extends AbstractController implements HttpPostActionInterface
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
     * @var CustomerTfaChallengeServiceInterface
     */
    private readonly CustomerTfaChallengeServiceInterface $customerTfaChallengeService;

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
     * @param CustomerTfaChallengeServiceInterface $customerTfaChallengeService
     * @param ResponseFactory $responseFactory
     * @param Validator $validator
     */
    public function __construct(
        Context $context,
        CustomerNonceServiceInterface $customerNonceService,
        Session $customerSession,
        CustomerTfaChallengeServiceInterface $customerTfaChallengeService,
        ResponseFactory $responseFactory,
        Validator $validator
    ) {
        $this->customerNonceService = $customerNonceService;
        $this->customerSession = $customerSession;
        $this->customerTfaChallengeService = $customerTfaChallengeService;
        $this->responseFactory = $responseFactory;

        parent::__construct($context, $customerNonceService, $customerSession, $validator);
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings("php:S1142")
     */
    public function execute()
    {
        if (!$this->isSecureAjaxRequest() || !$this->isCustomerNonceValid() || !$this->isValidFormRequest()) {
            return $this->responseFactory->create([
                'success' => false,
                'message' => null
            ], Response::STATUS_CODE_400);
        }

        $challenge = $this->getRequest()->getParam('challenge');
        if (empty($challenge)) {
            return $this->responseFactory->create([
                'success' => false,
                'message' =>  __('The parameter \'challenge\' is required.')
            ], Response::STATUS_CODE_400);
        }

        if ($this->customerTfaChallengeService->verify((int)$this->customerSession->getCustomerId(), $challenge)) {
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
