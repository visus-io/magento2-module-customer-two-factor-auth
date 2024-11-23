<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Controller\Index;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\SessionException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Visus\CustomerTfa\Model\Config;

/**
 * Default Controller for TFA Setup Page
 *
 * @since 1.0.0
 */
class Index implements HttpGetActionInterface
{
    /**
     * @var CookieManagerInterface
     */
    private readonly CookieManagerInterface $cookieManager;

    /**
     * @var Session
     */
    private readonly Session $customerSession;

    /**
     * @var PageFactory
     */
    private readonly PageFactory $resultPageFactory;

    /**
     * @var UrlInterface
     */
    private readonly UrlInterface $urlBuilder;

    /**
     * Constructor
     *
     * @param CookieManagerInterface $cookieManager
     * @param Session $customerSession
     * @param PageFactory $resultPageFactory
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        CookieManagerInterface $cookieManager,
        Session $customerSession,
        PageFactory $resultPageFactory,
        UrlInterface $urlBuilder
    ) {
        $this->cookieManager = $cookieManager;
        $this->customerSession = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Two-Factor Authentication
     *
     * @return ResponseInterface|ResultInterface|Page
     * @throws InputException
     * @throws FailureToSendException
     * @throws SessionException
     */
    public function execute()
    {
        $this->cookieManager->deleteCookie(Config::COOKIE_NAME);

        if (!$this->customerSession->isLoggedIn()) {
            $this->customerSession->setAfterAuthUrl($this->urlBuilder->getUrl('visus_tfa/index/index'));
            $this->customerSession->authenticate();

            return null;
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Two-Factor Authentication'));

        return $resultPage;
    }
}
