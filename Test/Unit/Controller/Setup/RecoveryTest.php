<?php
declare(strict_types=1);

namespace Unit\Controller\Setup;

use Laminas\Http\Response;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Visus\CustomerTfa\Api\Service\CustomerNonceServiceInterface;
use Visus\CustomerTfa\Api\Service\CustomerTfaServiceInterface;
use Visus\CustomerTfa\Controller\ResponseFactory;
use Visus\CustomerTfa\Controller\Setup\Recovery;

class RecoveryTest extends TestCase
{
    private const TEST_CUSTOMER_ID = 11;

    /**
     * @var Recovery
     */
    private Recovery $controller;

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
     * @var CustomerTfaServiceInterface|MockObject
     */
    private CustomerTfaServiceInterface|MockObject $customerTfaServiceMock;

    /**
     * @var Session|MockObject
     */
    private Session|MockObject $customerSessionMock;

    /**
     * @var ResultInterface|MockObject
     */
    private ResultInterface|MockObject $resultMock;

    /**
     * @var ResponseFactory|MockObject
     */
    private ResponseFactory|MockObject $responseFactoryMock;

    /**
     * @var RequestInterface|MockObject
     */
    private RequestInterface|MockObject $requestMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['isXmlHttpRequest'])
            ->getMockForAbstractClass();

        $this->contextMock = $this->createPartialMock(Context::class, ['getRequest']);

        $this->customerMock = $this->createMock(Customer::class);

        $this->customerNonceServiceMock = $this->getMockBuilder(CustomerNonceServiceInterface::class)
            ->onlyMethods(['generate'])
            ->getMockForAbstractClass();

        $this->customerTfaServiceMock = $this->getMockBuilder(CustomerTfaServiceInterface::class)
            ->onlyMethods(['verify'])
            ->getMockForAbstractClass();

        $this->customerSessionMock = $this->createPartialMock(
            Session::class,
            [
                'isLoggedIn',
                'getCustomer',
                'getCustomerId'
            ]
        );

        $this->resultMock = $this->getMockForAbstractClass(ResultInterface::class);

        $this->responseFactoryMock = $this->createPartialMock(ResponseFactory::class, ['create']);

        $validator = $this->createMock(Validator::class);

        $this->controller = new Recovery(
            $this->contextMock,
            $this->customerNonceServiceMock,
            $this->customerSessionMock,
            $this->customerTfaServiceMock,
            $this->responseFactoryMock,
            $validator
        );
    }

    public function testExecute(): void
    {
        $recoveryCodes = [
            '461818-eb32ea',
            '96e32b-6d4771',
            '151f80-3b7072',
            '04e7b4-cb9ec6',
            '0bd0b0-05bd1e',
            '085532-a943fa',
            '094a30-1602c0',
            '188bc1-ea3158',
            'c3d5e5-dc75ec'
        ];

        $this->contextMock->expects(self::once())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->requestMock->expects(self::once())
            ->method('isSecure')
            ->willReturn(true);

        $this->requestMock->expects(self::once())
            ->method('isXmlHttpRequest')
            ->willReturn(true);

        $this->customerSessionMock->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->customerSessionMock->expects(self::once())
            ->method('getCustomerId')
            ->willReturn(self::TEST_CUSTOMER_ID);

        $this->customerSessionMock->expects(self::once())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerNonceServiceMock->expects(self::once())
            ->method('validate')
            ->with($this->customerMock)
            ->willReturn(true);

        $this->customerTfaServiceMock->expects(self::once())
            ->method('generateRecoveryCodes')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn($recoveryCodes);

        $this->responseFactoryMock->expects(self::once())
            ->method('create')
            ->with([
                'success' => true,
                'data' => $recoveryCodes
            ])
            ->willReturn($this->resultMock);

        $this->controller->execute();
    }

    public function testExecuteWithBadRequest(): void
    {
        $this->customerNonceServiceMock->expects($this->never())->method('validate');
        $this->customerTfaServiceMock->expects($this->never())->method('generateRecoveryCodes');

        $this->contextMock->expects(self::once())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->requestMock->expects(self::once())
            ->method('isSecure')
            ->willReturn(true);

        $this->requestMock->expects(self::once())
            ->method('isXmlHttpRequest')
            ->willReturn(false);

        $this->responseFactoryMock->expects(self::once())
            ->method('create')
            ->with([
                'success' => false,
                'message' => null
            ], Response::STATUS_CODE_400)
            ->willReturn($this->resultMock);

        $this->controller->execute();
    }

    public function testExecuteWithNoRecoveryCodes(): void
    {
        $this->contextMock->expects(self::once())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->requestMock->expects(self::once())
            ->method('isSecure')
            ->willReturn(true);

        $this->requestMock->expects(self::once())
            ->method('isXmlHttpRequest')
            ->willReturn(true);

        $this->customerSessionMock->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->customerSessionMock->expects(self::once())
            ->method('getCustomerId')
            ->willReturn(self::TEST_CUSTOMER_ID);

        $this->customerSessionMock->expects($this->atMost(2))
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerNonceServiceMock->expects(self::once())
            ->method('validate')
            ->with($this->customerMock)
            ->willReturn(true);

        $this->customerTfaServiceMock->expects(self::once())
            ->method('generateRecoveryCodes')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn([]);

        $this->responseFactoryMock->expects(self::once())
            ->method('create')
            ->with([
                'success' => false,
                'message' => null
            ])
            ->willReturn($this->resultMock);

        $this->controller->execute();
    }
}
