<?php
declare(strict_types=1);

namespace Unit\Controller\Index;

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
use Visus\CustomerTfa\Api\Service\CustomerTfaChallengeServiceInterface;
use Visus\CustomerTfa\Controller\Challenge\Verify;
use Visus\CustomerTfa\Controller\ResponseFactory;

class VerifyTest extends TestCase
{
    private const TEST_CHALLENGE = '394150';

    private const TEST_CUSTOMER_ID = 11;

    /**
     * @var Context|MockObject
     */
    private Context|MockObject $contextMock;

    /**
     * @var Verify
     */
    private Verify $controller;

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
     * @var CustomerTfaChallengeServiceInterface|MockObject
     */
    private CustomerTfaChallengeServiceInterface|MockObject $customerTfaChallengeServiceMock;

    /**
     * @var ResponseFactory|MockObject
     */
    private ResponseFactory|MockObject $responseFactoryMock;

    /**
     * @var ResultInterface|MockObject
     */
    private ResultInterface|MockObject $resultMock;

    /**
     * @var RequestInterface|MockObject
     */
    private RequestInterface|MockObject $requestMock;

    /**
     * @var Validator|MockObject
     */
    private readonly Validator|MockObject $validatorMock;

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

        $this->customerSessionMock = $this->createPartialMock(
            Session::class,
            [
                'isLoggedIn',
                'getCustomer',
                'getCustomerId'
            ]
        );

        $this->customerTfaChallengeServiceMock = $this->getMockBuilder(CustomerTfaChallengeServiceInterface::class)
            ->onlyMethods(['verify'])
            ->getMockForAbstractClass();

        $this->resultMock = $this->getMockForAbstractClass(ResultInterface::class);

        $this->responseFactoryMock = $this->createPartialMock(ResponseFactory::class, ['create']);

        $this->validatorMock = $this->createPartialMock(Validator::class, ['validate']);

        $this->controller = new Verify(
            $this->contextMock,
            $this->customerNonceServiceMock,
            $this->customerSessionMock,
            $this->customerTfaChallengeServiceMock,
            $this->responseFactoryMock,
            $this->validatorMock
        );
    }

    public function testExecute(): void
    {
        $this->contextMock->expects(self::exactly(3))
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

        $this->customerSessionMock->expects(self::exactly(2))
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerNonceServiceMock->expects(self::once())
            ->method('validate')
            ->with($this->customerMock)
            ->willReturn(true);

        $this->validatorMock->expects(self::once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(true);

        $this->requestMock->expects(self::once())
            ->method('getParam')
            ->with('challenge')
            ->willReturn(self::TEST_CHALLENGE);

        $this->customerTfaChallengeServiceMock->expects(self::once())
            ->method('verify')
            ->with(self::TEST_CUSTOMER_ID, self::TEST_CHALLENGE)
            ->willReturn(true);

        $this->customerNonceServiceMock->expects(self::once())
            ->method('generate')
            ->with($this->customerMock)
            ->willReturn(true);

        $this->responseFactoryMock->expects(self::once())
            ->method('create')
            ->with(['success' => true])
            ->willReturn($this->resultMock);

        $this->controller->execute();
    }

    public function testExecuteBadRequest(): void
    {
        $this->customerNonceServiceMock->expects($this->never())->method('validate');
        $this->customerTfaChallengeServiceMock->expects($this->never())->method('verify');
        $this->customerNonceServiceMock->expects($this->never())->method('generate');

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

    public function testExecuteForbiddenRequest(): void
    {
        $this->customerNonceServiceMock->expects($this->never())->method('generate');

        $this->contextMock->expects(self::exactly(3))
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

        $this->validatorMock->expects(self::once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(true);

        $this->customerNonceServiceMock->expects(self::once())
            ->method('validate')
            ->with($this->customerMock)
            ->willReturn(true);

        $this->requestMock->expects(self::once())
            ->method('getParam')
            ->with('challenge')
            ->willReturn(self::TEST_CHALLENGE);

        $this->customerTfaChallengeServiceMock->expects(self::once())
            ->method('verify')
            ->with(self::TEST_CUSTOMER_ID, self::TEST_CHALLENGE)
            ->willReturn(false);

        $this->responseFactoryMock->expects(self::once())
            ->method('create')
            ->with([
                'success' => false,
                'message' => null
            ], Response::STATUS_CODE_403)
            ->willReturn($this->resultMock);

        $this->controller->execute();
    }

    public function testExecuteBadRequestMissingParams(): void
    {
        $this->customerTfaChallengeServiceMock->expects($this->never())->method('verify');
        $this->customerNonceServiceMock->expects($this->never())->method('generate');

        $this->contextMock->expects(self::exactly(3))
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
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->validatorMock->expects(self::once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(true);

        $this->customerNonceServiceMock->expects(self::once())
            ->method('validate')
            ->with($this->customerMock)
            ->willReturn(true);

        $this->requestMock->expects(self::once())
            ->method('getParam')
            ->with('challenge')
            ->willReturn(null);

        $this->responseFactoryMock->expects(self::once())
            ->method('create')
            ->with([
                'success' => false,
                'message' => __('The parameter \'challenge\' is required.')
            ], Response::STATUS_CODE_400)
            ->willReturn($this->resultMock);

        $this->controller->execute();
    }
}
