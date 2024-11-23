<?php
declare(strict_types=1);

namespace Unit\Observer;

use Controller;
use Magento\Cms\Controller\Index\Index;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Visus\CustomerTfa\Api\CustomerTfaSessionInterface;
use Visus\CustomerTfa\Api\Data\CustomerTfaInterface;
use Visus\CustomerTfa\Api\Service\CustomerTfaServiceInterface;
use Visus\CustomerTfa\Observer\ControllerActionPredispatch;

class ControllerActionPredispatchTest extends TestCase
{
    /**
     * @var ControllerActionPredispatch
     */
    private ControllerActionPredispatch $controllerActionPredispatchObserver;

    /**
     * @var ActionFlag|MockObject
     */
    private ActionFlag|MockObject $actionFlagMock;

    /**
     * @var Session|MockObject
     */
    private Session|MockObject $customerSessionMock;

    /**
     * @var CustomerTfaInterface|MockObject
     */
    private CustomerTfaInterface|MockObject $customerTfaMock;

    /**
     * @var CustomerTfaSessionInterface|MockObject
     */
    private CustomerTfaSessionInterface|MockObject $customerTfaSessionMock;

    /**
     * @var CustomerTfaServiceInterface|MockObject
     */
    private CustomerTfaServiceInterface|MockObject $customerTfaServiceMock;

    /**
     * @var Event|MockObject
     */
    private Event|MockObject $eventMock;

    /**
     * @var Http|MockObject
     */
    private Http|MockObject $httpRequestMock;

    /**
     * @var Observer|MockObject
     */
    private Observer|MockObject $observer;

    /**
     * @var UrlInterface|MockObject
     */
    private UrlInterface|MockObject $urlBuilderMock;

    protected function setUp(): void
    {
        $this->actionFlagMock = $this->createPartialMock(ActionFlag::class, ['set']);

        $this->customerSessionMock = $this->createPartialMock(Session::class, ['isLoggedIn', 'getCustomer']);

        $this->customerTfaMock = $this->getMockForAbstractClass(CustomerTfaInterface::class);

        $this->customerTfaSessionMock = $this->getMockBuilder(CustomerTfaSessionInterface::class)
            ->onlyMethods(['isGranted'])
            ->getMockForAbstractClass();

        $this->customerTfaServiceMock = $this->getMockBuilder(CustomerTfaServiceInterface::class)
            ->onlyMethods(['isEnrolled'])
            ->getMockForAbstractClass();

        $this->httpRequestMock = $this->createPartialMock(Http::class, ['getFullActionName']);

        $this->eventMock = $this->createPartialMock(Event::class, ['getData']);

        $this->observer = $this->createPartialMock(Observer::class, ['getEvent']);

        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->onlyMethods(['getUrl'])
            ->getMockForAbstractClass();

        $this->controllerActionPredispatchObserver = new ControllerActionPredispatch(
            $this->actionFlagMock,
            $this->customerSessionMock,
            $this->customerTfaMock,
            $this->customerTfaSessionMock,
            $this->customerTfaServiceMock,
            $this->urlBuilderMock
        );
    }

    public function testExecuteNeedsGrant(): void
    {
        $httpResponseMock = $this->createMock(\Magento\Framework\App\Response\Http::class);
        $httpResponseMock->expects(self::once())
            ->method('setRedirect')
            ->with('https://app.example.net/visus_tfa/authenticate/index');

        $controllerMock = $this->createMock(Index::class);
        $controllerMock->expects(self::once())
            ->method('getResponse')
            ->willReturn($httpResponseMock);

        $customerMock = $this->createMock(Customer::class);

        $this->httpRequestMock->expects(self::once())
            ->method('getFullActionName')
            ->willReturn('cms_index_index');

        $this->eventMock->expects(self::exactly(2))
            ->method('getData')
            ->will(
                $this->returnValueMap([
                    ['controller_action', null, $controllerMock],
                    ['request', null, $this->httpRequestMock]
                ])
            );

        $this->observer->expects(self::any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->customerSessionMock->expects(self::once())
            ->method('getCustomer')
            ->willReturn($customerMock);

        $this->customerSessionMock->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->customerTfaSessionMock->expects(self::once())
            ->method('isGranted')
            ->willReturn(false);

        $this->customerTfaServiceMock->expects(self::once())
            ->method('isEnrolled')
            ->willReturn(true);

        $this->actionFlagMock->expects(self::once())
            ->method('set')
            ->with('', 'no-dispatch', true)
            ->willReturnSelf();

        $this->urlBuilderMock->expects(self::once())
            ->method('getUrl')
            ->with('visus_tfa/authenticate/index')
            ->willReturn('https://app.example.net/visus_tfa/authenticate/index');

        $this->controllerActionPredispatchObserver->execute($this->observer);
    }

    public function testExecuteIsGranted(): void
    {
        $this->httpRequestMock->expects(self::once())
            ->method('getFullActionName')
            ->willReturn('index_index');

        $this->eventMock->expects(self::exactly(2))
            ->method('getData')
            ->will(
                $this->returnValueMap([
                    ['controller_action', null, []],
                    ['request', null, $this->httpRequestMock]
                ])
            );

        $this->observer->expects(self::any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->customerSessionMock->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->customerTfaSessionMock->expects(self::once())
            ->method('isGranted')
            ->willReturn(true);

        $this->customerSessionMock->expects($this->never())
            ->method('getCustomer');

        $this->controllerActionPredispatchObserver->execute($this->observer);
    }

    public function testExecutionIsNotEnrolled(): void
    {
        $httpResponseMock = $this->createMock(\Magento\Framework\App\Response\Http::class);
        $httpResponseMock->expects($this->never())
            ->method('setRedirect');

        $controllerMock = $this->createMock(Index::class);
        $controllerMock->expects($this->never())
            ->method('getResponse');

        $customerMock = $this->createMock(Customer::class);

        $this->httpRequestMock->expects(self::once())
            ->method('getFullActionName')
            ->willReturn('cms_index_index');

        $this->eventMock->expects(self::exactly(2))
            ->method('getData')
            ->will(
                $this->returnValueMap([
                    ['controller_action', null, $controllerMock],
                    ['request', null, $this->httpRequestMock]
                ])
            );

        $this->observer->expects(self::any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->customerSessionMock->expects(self::once())
            ->method('getCustomer')
            ->willReturn($customerMock);

        $this->customerSessionMock->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->customerTfaSessionMock->expects(self::once())
            ->method('isGranted')
            ->willReturn(false);

        $this->customerTfaServiceMock->expects(self::once())
            ->method('isEnrolled')
            ->willReturn(false);

        $this->actionFlagMock->expects($this->never())
            ->method('set')
            ->with('', 'no-dispatch', true);

        $this->urlBuilderMock->expects($this->never())
            ->method('getUrl')
            ->with('visus_tfa/authenticate/index');

        $this->controllerActionPredispatchObserver->execute($this->observer);
    }
}
