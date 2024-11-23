<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Controller\Authenticate;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Visus\CustomerTfa\Api\CustomerTfaSessionInterface;
use Visus\CustomerTfa\Api\Service\CustomerNonceServiceInterface;
use Visus\CustomerTfa\Api\Service\CustomerTfaServiceInterface;

/**
 * Controller to validate one-time-password
 *
 * @since 1.0.0
 */
class Validate implements HttpPostActionInterface
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
     * @var CustomerTfaSessionInterface
     */
    private readonly CustomerTfaSessionInterface $customerTfaSession;

    /**
     * @var CustomerTfaServiceInterface
     */
    private readonly CustomerTfaServiceInterface $customerTfaService;

    /**
     * @var ManagerInterface
     */
    private readonly ManagerInterface $messageManager;

    /**
     * @var ResultFactory
     */
    private readonly ResultFactory $resultFactory;

    /**
     * @var UrlInterface
     */
    private readonly UrlInterface $urlBuilder;

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
     * @param CustomerTfaServiceInterface $customerTfaService
     * @param CustomerTfaSessionInterface $customerTfaSession
     * @param ManagerInterface $messageManager
     * @param ResultFactory $resultFactory
     * @param UrlInterface $urlBuilder
     * @param Validator $validator
     *
     * @SuppressWarnings("php:S107")
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        CustomerNonceServiceInterface $customerNonceService,
        Session $customerSession,
        CustomerTfaServiceInterface $customerTfaService,
        CustomerTfaSessionInterface $customerTfaSession,
        ManagerInterface $messageManager,
        ResultFactory $resultFactory,
        UrlInterface $urlBuilder,
        Validator $validator
    ) {
        $this->context = $context;
        $this->customerNonceService = $customerNonceService;
        $this->customerSession = $customerSession;
        $this->customerTfaService = $customerTfaService;
        $this->customerTfaSession = $customerTfaSession;
        $this->messageManager = $messageManager;
        $this->resultFactory = $resultFactory;
        $this->urlBuilder = $urlBuilder;
        $this->validator = $validator;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings("php:S1142")
     */
    public function execute()
    {
        if (!$this->validator->validate($this->context->getRequest()) ||
            !$this->customerSession->isLoggedIn() ||
            !$this->customerNonceService->validate($this->customerSession->getCustomer())) {
            $this->customerTfaSession->revokeAccess();
            $this->messageManager->addErrorMessage(__('Invalid Form Key or Nonce. Please try again.'));

            $redirectUrl = $this->urlBuilder->getUrl('customer/account/logout');

            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $redirect->setUrl($redirectUrl);

            return $redirect;
        }

        $otp = $this->context->getRequest()->getParam('one-time-password');
        if (empty($otp)) {
            $this->customerTfaSession->revokeAccess();
            $this->messageManager->addErrorMessage(__('Invalid or expired one-time password'));

            return $this->redirect('*/*/*');
        }

        if ($this->customerTfaService->verify((int)$this->customerSession->getCustomerId(), $otp)) {
            $this->customerTfaSession->grantAccess();

            return $this->redirect('customer/account');
        }

        $this->customerTfaSession->revokeAccess();
        $this->messageManager->addErrorMessage(__('Invalid or expired one-time password'));

        return $this->redirect('*/*/*');
    }

    /**
     * Redirect to the given path
     *
     * @param string $url
     *
     * @return ResultInterface|Redirect
     */
    private function redirect(string $url): ResultInterface|Redirect
    {
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setUrl($this->urlBuilder->getUrl($url));

        return $redirect;
    }
}
