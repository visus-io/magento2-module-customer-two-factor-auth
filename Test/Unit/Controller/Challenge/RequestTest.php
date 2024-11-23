<?php
declare(strict_types=1);

namespace Unit\Controller\Challenge;

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
use Visus\CustomerTfa\Controller\Challenge\Request;
use Visus\CustomerTfa\Controller\ResponseFactory;

class RequestTest extends TestCase
{
    /**
     * @var Request
     */
    private Request $controller;

    /**
     * @var Context|MockObject
     */
    private Context|MockObject $contextMock;

    /**
     * @var CustomerNonceServiceInterface|MockObject
     */
    private CustomerNonceServiceInterface|MockObject $customerNonceServiceMock;

    /**
     * @var Customer|MockObject
     */
    private Customer|MockObject $customerMock;

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

        $this->customerSessionMock = $this->createPartialMock(Session::class, ['isLoggedIn', 'getCustomer']);

        $this->customerTfaChallengeServiceMock = $this->getMockBuilder(CustomerTfaChallengeServiceInterface::class)
            ->onlyMethods(['sendEmail'])
            ->getMockForAbstractClass();

        $this->resultMock = $this->getMockForAbstractClass(ResultInterface::class);

        $this->responseFactoryMock = $this->createPartialMock(ResponseFactory::class, ['create']);

        $validator = $this->createMock(Validator::class);

        $this->controller = new Request(
            $this->contextMock,
            $this->customerNonceServiceMock,
            $this->customerSessionMock,
            $this->customerTfaChallengeServiceMock,
            $this->responseFactoryMock,
            $validator
        );

        parent::setUp();
    }

    public function testExecute()
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

        $this->customerSessionMock->expects(self::exactly(2))
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerTfaChallengeServiceMock->expects(self::once())
            ->method('sendEmail')
            ->with($this->customerMock)
            ->willReturn(true);

        $this->responseFactoryMock->expects(self::once())
            ->method('create')
            ->with(['success' => true])
            ->willReturn($this->resultMock);

        $this->controller->execute();
    }

    public function testExecuteWithBadRequest()
    {
        $this->customerTfaChallengeServiceMock->expects($this->never())->method('sendEmail');
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
            ])
            ->willReturn($this->resultMock);

        $this->controller->execute();
    }

    public function testExecuteWithEmailFailed(): void
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
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerTfaChallengeServiceMock->expects(self::once())
            ->method('sendEmail')
            ->with($this->customerMock)
            ->willReturn(false);

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
