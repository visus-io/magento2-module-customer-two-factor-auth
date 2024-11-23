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
use Visus\CustomerTfa\Controller\Setup\Setup;

class SetupTest extends TestCase
{
    private const TEST_CUSTOMER_ID = 11;

    private const TEST_TFA_SECRET = 'Y3F6Z5KT3ZNVMM3W6SHZS67DUA';

    /**
     * remarks: not a real qr code
     */
    private const TEST_QR_CODE = 'xSpPDzYMO0BQ/W4YaBI2vOxcDon7nndmqeMdGG/facQ=';

    /**
     * @var Setup
     */
    private Setup $controller;

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
            ->disableOriginalConstructor()
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

        $this->controller = new Setup(
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
            ->method('getSecret')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn(self::TEST_TFA_SECRET);

        $qrCodeMock = $this->getMockBuilder(\Endroid\QrCode\Writer\Result\ResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $qrCodeMock->expects(self::once())
            ->method('getDataUri')
            ->willReturn(sprintf("data:image/png;base64,%s", self::TEST_QR_CODE));

        $this->customerTfaServiceMock->expects(self::once())
            ->method('generateQrCode')
            ->with($this->customerMock)
            ->willReturn($qrCodeMock);

        $this->responseFactoryMock->expects(self::once())
            ->method('create')
            ->with([
                'success' => true,
                'data' => [
                    'qrCode' => sprintf("data:image/png;base64,%s", self::TEST_QR_CODE),
                    'secret' => self::TEST_TFA_SECRET
                ]
            ])
            ->willReturn($this->resultMock);

        $this->controller->execute();
    }

    public function testExecuteWithBadRequest(): void
    {
        $this->customerNonceServiceMock->expects($this->never())->method('validate');
        $this->customerTfaServiceMock->expects($this->never())->method('getSecret');
        $this->customerTfaServiceMock->expects($this->never())->method('generateQrCode');

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

    public function testExecuteWithNoQrCode(): void
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

        $this->customerSessionMock->expects(self::exactly(2))
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerNonceServiceMock->expects(self::once())
            ->method('validate')
            ->with($this->customerMock)
            ->willReturn(true);

        $this->customerTfaServiceMock->expects(self::once())
            ->method('getSecret')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn(self::TEST_TFA_SECRET);

        $this->customerTfaServiceMock->expects(self::once())
            ->method('generateQrCode')
            ->with($this->customerMock)
            ->willReturn(null);

        $this->responseFactoryMock->expects(self::once())
            ->method('create')
            ->with([
                'success' => false,
                'message' => __('Unable to retrieve QR code.')
            ])
            ->willReturn($this->resultMock);

        $this->controller->execute();
    }

    public function testExecuteWithNoSecret(): void
    {
        $this->customerTfaServiceMock->expects($this->never())->method('generateQrCode');

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
            ->method('getSecret')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn(null);

        $this->responseFactoryMock->expects(self::once())
            ->method('create')
            ->with([
                'success' => false,
                'message' => __('Unable to retrieve 2FA secret.')
            ])
            ->willReturn($this->resultMock);

        $this->controller->execute();
    }
}
