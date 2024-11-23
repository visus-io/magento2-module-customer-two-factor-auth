<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Observer;

use Magento\Customer\Model\Session;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;
use Visus\CustomerTfa\Api\CustomerTfaSessionInterface;
use Visus\CustomerTfa\Api\Data\CustomerTfaInterface;
use Visus\CustomerTfa\Api\Service\CustomerTfaServiceInterface;

/**
 * Handle redirection to MFA page if required
 */
class ControllerActionPredispatch implements ObserverInterface
{
    /**
     * @var ActionFlag
     */
    private readonly ActionFlag $actionFlag;

    /**
     * @var Session
     */
    private readonly Session $customerSession;

    /**
     * @var CustomerTfaInterface
     */
    private readonly CustomerTfaInterface $customerTfa;

    /**
     * @var CustomerTfaSessionInterface
     */
    private readonly CustomerTfaSessionInterface $customerTfaSession;

    /**
     * @var CustomerTfaServiceInterface
     */
    private readonly CustomerTfaServiceInterface $customerTfaService;

    /**
     * @var UrlInterface
     */
    private readonly UrlInterface $urlBuilder;

    /**
     * Constructor
     *
     * @param ActionFlag $actionFlag
     * @param Session $customerSession
     * @param CustomerTfaInterface $customerTfa
     * @param CustomerTfaSessionInterface $customerTfaSession
     * @param CustomerTfaServiceInterface $customerTfaService
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        ActionFlag $actionFlag,
        Session $customerSession,
        CustomerTfaInterface $customerTfa,
        CustomerTfaSessionInterface $customerTfaSession,
        CustomerTfaServiceInterface $customerTfaService,
        UrlInterface $urlBuilder
    ) {
        $this->actionFlag = $actionFlag;
        $this->customerSession = $customerSession;
        $this->customerTfa = $customerTfa;
        $this->customerTfaSession = $customerTfaSession;
        $this->customerTfaService = $customerTfaService;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Handle the `controller_action_predispatch` event.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $controllerAction = $observer->getEvent()->getData('controller_action');
        $fullActionName = $observer->getEvent()->getData('request')->getFullActionName();

        if (!$this->customerSession->isLoggedIn() ||
            $this->customerTfaSession->isGranted() ||
            in_array($fullActionName, $this->customerTfa->getAllowedUrls(), true)) {
            return;
        }

        $customer = $this->customerSession->getCustomer();
        if (!$this->customerTfaService->isEnrolled((int)$customer->getId())) {
            return;
        }

        $this->actionFlag->set('', 'no-dispatch', true);
        $redirectUrl = $this->urlBuilder->getUrl('visus_tfa/authenticate/index');
        $controllerAction->getResponse()->setRedirect($redirectUrl);
    }
}
