<?php
declare(strict_types=1);

namespace Unit\Controller\Authenticate;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Visus\CustomerTfa\Api\CustomerTfaSessionInterface;
use Visus\CustomerTfa\Api\Service\CustomerNonceServiceInterface;
use Visus\CustomerTfa\Api\Service\CustomerTfaServiceInterface;
use Visus\CustomerTfa\Controller\Authenticate\Validate;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ValidateTest extends TestCase
{
    private const TEST_CUSTOMER_ID = 11;

    private const TEST_OTP = '223442';

    /**
     * @var Validate
     */
    private Validate $controller;

    /**
     * @var Context|MockObject
     */
    private Context|MockObject $contextMock;

    /**
     * @var Customer|MockObject
     */
    private Customer|MockObject $customerMock;

    /**
     * @var CustomerNonceServiceInterface|MockObject
     */
    private CustomerNonceServiceInterface|MockObject $customerNonceServiceMock;

    /**
     * @var Session|MockObject
     */
    private Session|MockObject $customerSessionMock;

    /**
     * @var CustomerTfaSessionInterface|MockObject
     */
    private CustomerTfaSessionInterface|MockObject $customerTfaSessionMock;

    /**
     * @var CustomerTfaServiceInterface|MockObject
     */
    private CustomerTfaServiceInterface|MockObject $customerTfaServiceMock;

    /**
     * @var ManagerInterface|MockObject
     */
    private ManagerInterface|MockObject $messageManagerMock;

    /**
     * @var Redirect|MockObject
     */
    private Redirect|MockObject $redirectMock;

    /**
     * @var RequestInterface|MockObject
     */
    private RequestInterface|MockObject $requestMock;

    /**
     * @var ResultFactory|MockObject
     */
    private ResultFactory|MockObject $resultFactoryMock;

    /**
     * @var UrlInterface|MockObject
     */
    private UrlInterface|MockObject $urlBuilderMock;

    /**
     * @var Validator|MockObject
     */
    private Validator|MockObject $validatorMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);

        $this->customerMock = $this->createMock(Customer::class);

        $this->customerNonceServiceMock = $this->getMockBuilder(CustomerNonceServiceInterface::class)
            ->onlyMethods(['validate'])
            ->getMockForAbstractClass();

        $this->customerSessionMock = $this->createPartialMock(
            Session::class,
            [
                'getCustomer',
                'getCustomerId',
                'isLoggedIn'
            ]
        );

        $this->customerTfaServiceMock = $this->getMockBuilder(CustomerTfaServiceInterface::class)
            ->onlyMethods(['verify'])
            ->getMockForAbstractClass();

        $this->customerTfaSessionMock = $this->getMockForAbstractClass(CustomerTfaSessionInterface::class);

        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->onlyMethods(['addErrorMessage'])
            ->getMockForAbstractClass();

        $this->redirectMock = $this->createPartialMock(Redirect::class, ['setUrl']);

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->onlyMethods(['getParam'])
            ->getMockForAbstractClass();

        $this->resultFactoryMock = $this->createPartialMock(ResultFactory::class, ['create']);

        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->onlyMethods(['getUrl'])
            ->getMockForAbstractClass();

        $this->validatorMock = $this->createPartialMock(Validator::class, ['validate']);

        $this->controller = new Validate(
            $this->contextMock,
            $this->customerNonceServiceMock,
            $this->customerSessionMock,
            $this->customerTfaServiceMock,
            $this->customerTfaSessionMock,
            $this->messageManagerMock,
            $this->resultFactoryMock,
            $this->urlBuilderMock,
            $this->validatorMock
        );
    }

    public function testExecute(): void
    {
        $this->customerTfaSessionMock->expects(self::never())->method('revokeAccess');
        $this->messageManagerMock->expects(self::never())->method('addErrorMessage');

        $this->contextMock->expects(self::exactly(2))
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->validatorMock->expects(self::once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(true);

        $this->customerSessionMock->expects(self::once())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerSessionMock->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->customerSessionMock->expects(self::once())
            ->method('getCustomerId')
            ->willReturn(self::TEST_CUSTOMER_ID);

        $this->customerNonceServiceMock->expects(self::once())
            ->method('validate')
            ->with($this->customerMock)
            ->willReturn(true);

        $this->requestMock->expects(self::once())
            ->method('getParam')
            ->with('one-time-password')
            ->willReturn(self::TEST_OTP);

        $this->customerTfaServiceMock->expects(self::once())
            ->method('verify')
            ->with(self::TEST_CUSTOMER_ID, self::TEST_OTP)
            ->willReturn(true);

        $this->customerTfaSessionMock->expects(self::once())
            ->method('grantAccess');

        $this->urlBuilderMock->expects(self::once())
            ->method('getUrl')
            ->with('customer/account')
            ->willReturn('customer/account');

        $this->resultFactoryMock->expects(self::once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->redirectMock);

        $this->redirectMock->expects(self::once())
            ->method('setUrl')
            ->with('customer/account')
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteInvalidOtp(): void
    {
        $this->customerTfaSessionMock->expects(self::never())->method('grantAccess');

        $this->contextMock->expects(self::exactly(2))
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->validatorMock->expects(self::once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(true);

        $this->customerSessionMock->expects(self::once())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerSessionMock->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->customerSessionMock->expects(self::once())
            ->method('getCustomerId')
            ->willReturn(self::TEST_CUSTOMER_ID);

        $this->customerNonceServiceMock->expects(self::once())
            ->method('validate')
            ->with($this->customerMock)
            ->willReturn(true);

        $this->requestMock->expects(self::once())
            ->method('getParam')
            ->with('one-time-password')
            ->willReturn(self::TEST_OTP);

        $this->customerTfaServiceMock->expects(self::once())
            ->method('verify')
            ->with(self::TEST_CUSTOMER_ID, self::TEST_OTP)
            ->willReturn(false);

        $this->customerTfaSessionMock->expects(self::once())
            ->method('revokeAccess');

        $this->messageManagerMock->expects(self::once())
            ->method('addErrorMessage')
            ->with(__('Invalid or expired one-time password'));

        $this->urlBuilderMock->expects(self::once())
            ->method('getUrl')
            ->with('*/*/*')
            ->willReturn('*/*/*');

        $this->resultFactoryMock->expects(self::once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->redirectMock);

        $this->redirectMock->expects(self::once())
            ->method('setUrl')
            ->with('*/*/*')
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteMissingParams(): void
    {
        $this->customerTfaServiceMock->expects(self::never())->method('verify');
        $this->customerTfaSessionMock->expects(self::never())->method('grantAccess');

        $this->contextMock->expects(self::exactly(2))
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->validatorMock->expects(self::once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(true);

        $this->customerSessionMock->expects(self::once())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerSessionMock->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->customerNonceServiceMock->expects(self::once())
            ->method('validate')
            ->with($this->customerMock)
            ->willReturn(true);

        $this->requestMock->expects(self::once())
            ->method('getParam')
            ->with('one-time-password')
            ->willReturn(null);

        $this->customerTfaSessionMock->expects(self::once())->method('revokeAccess');

        $this->messageManagerMock->expects(self::once())
            ->method('addErrorMessage')
            ->with(__('Invalid or expired one-time password'));

        $this->urlBuilderMock->expects(self::once())
            ->method('getUrl')
            ->with('*/*/*')
            ->willReturn('*/*/*');

        $this->resultFactoryMock->expects(self::once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->redirectMock);

        $this->redirectMock->expects(self::once())
            ->method('setUrl')
            ->with('*/*/*')
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteNonceFail(): void
    {
        $this->contextMock->expects(self::once())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->validatorMock->expects(self::once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(false);

        $this->customerTfaSessionMock->expects(self::once())
            ->method('revokeAccess');

        $this->messageManagerMock->expects(self::once())
            ->method('addErrorMessage')
            ->with(__('Invalid Form Key or Nonce. Please try again.'));

        $this->urlBuilderMock->expects(self::once())
            ->method('getUrl')
            ->with('customer/account/logout')
            ->willReturn('customer/account/logout');

        $this->resultFactoryMock->expects(self::once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->redirectMock);

        $this->redirectMock->expects(self::once())
            ->method('setUrl')
            ->with('customer/account/logout')
            ->willReturnSelf();

        $this->controller->execute();
    }
}
