<?php
declare(strict_types=1);
namespace Visus\CustomerTfa\Controller\Authenticate;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Visus\CustomerTfa\Api\Service\CustomerNonceServiceInterface;

/**
 * Controller to request one-time-password after login
 *
 * @since 1.0.0
 */
class Index implements HttpGetActionInterface
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
     * @var PageFactory
     */
    private readonly PageFactory $resultPageFactory;

    /**
     * Constructor
     *
     * @param CustomerNonceServiceInterface $customerNonceService
     * @param Session $customerSession
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        CustomerNonceServiceInterface $customerNonceService,
        Session $customerSession,
        PageFactory $resultPageFactory
    ) {
        $this->customerNonceService = $customerNonceService;
        $this->customerSession = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->customerNonceService->generate($this->customerSession->getCustomer());

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Two-Factor Authentication'));

        return $resultPage;
    }
}
