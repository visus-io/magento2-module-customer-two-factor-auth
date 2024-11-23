<?php
declare(strict_types=1);

namespace Unit\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Helper\View;
use Magento\Customer\Model\Address\Mapper;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\DataObjectFactory as ObjectFactory;
use Magento\Framework\Math\Random;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\App\Response\Redirect;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Visus\CustomerTfa\Api\Service\CustomerTfaServiceInterface;
use Visus\CustomerTfa\Controller\Adminhtml\Customer\Reset;

class ResetTest extends TestCase
{
    /**
     * @var Reset
     */
    private Reset $controller;

    /**
     * @var CustomerTfaServiceInterface|MockObject
     */
    private CustomerTfaServiceInterface|MockObject $customerTfaServiceMock;

    /**
     * @var \Magento\Backend\Model\View\Result\RedirectFactory|MockObject
     */
    private \Magento\Backend\Model\View\Result\RedirectFactory|MockObject $resultRedirectFactoryMock;

    /**
     * @var RequestInterface|MockObject
     */
    private RequestInterface|MockObject $requestMock;

    /**
     * @var ManagerInterface|MockObject
     */
    private ManagerInterface|MockObject $messageManagerMock;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->messageManagerMock = $this->createMock(ManagerInterface::class);
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->resultRedirectFactoryMock = $this->createMock(RedirectFactory::class);

        $contextMock = $this->createPartialMock(
            Context::class,
            [
                'getMessageManager',
                'getRequest',
                'getResultRedirectFactory'
            ]
        );

        $contextMock->expects(self::any())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);

        $contextMock->expects(self::any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $contextMock->expects(self::any())
            ->method('getResultRedirectFactory')
            ->willReturn($this->resultRedirectFactoryMock);

        $registryMock = $this->createMock(Registry::class);
        $fileFactoryMock = $this->createMock(FileFactory::class);
        $customerFactoryMock = $this->createMock(CustomerFactory::class);
        $addressFactoryMock = $this->createMock(AddressFactory::class);
        $formFactoryMock = $this->createMock(FormFactory::class);
        $subscriberFactoryMock = $this->createMock(SubscriberFactory::class);
        $viewHelperMock = $this->createMock(View::class);
        $randomMock = $this->createMock(Random::class);

        $customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $extensibleDataObjectConverterMock = $this->createMock(ExtensibleDataObjectConverter::class);

        $addressMapperMock = $this->createMock(Mapper::class);

        $customerAccountManagementMock = $this->getMockBuilder(AccountManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $addressRepositoryMock = $this->getMockBuilder(AddressRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $customerDataFactoryMock = $this->getMockBuilder(CustomerInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $addressDataFactoryMock = $this->getMockBuilder(AddressInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $customerMapperMock = $this->createMock(\Magento\Customer\Model\Customer\Mapper::class);
        $dataObjectProcessorMock = $this->createMock(DataObjectProcessor::class);
        $dataObjectHelperMock = $this->createMock(DataObjectHelper::class);
        $objectFactoryMock = $this->createMock(ObjectFactory::class);
        $layoutFactoryMock = $this->createMock(LayoutFactory::class);
        $resultLayoutFactoryMock = $this->createMock(\Magento\Framework\View\Result\LayoutFactory::class);
        $resultPageFactoryMock = $this->createMock(PageFactory::class);
        $resultForwardFactoryMock = $this->createMock(ForwardFactory::class);
        $resultJsonFactoryMock = $this->createMock(JsonFactory::class);

        $this->customerTfaServiceMock = $this->getMockBuilder(CustomerTfaServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->controller = new Reset(
            $contextMock,
            $registryMock,
            $fileFactoryMock,
            $customerFactoryMock,
            $addressFactoryMock,
            $formFactoryMock,
            $subscriberFactoryMock,
            $viewHelperMock,
            $randomMock,
            $customerRepositoryMock,
            $extensibleDataObjectConverterMock,
            $addressMapperMock,
            $customerAccountManagementMock,
            $addressRepositoryMock,
            $customerDataFactoryMock,
            $addressDataFactoryMock,
            $customerMapperMock,
            $dataObjectProcessorMock,
            $dataObjectHelperMock,
            $objectFactoryMock,
            $layoutFactoryMock,
            $resultLayoutFactoryMock,
            $resultPageFactoryMock,
            $resultForwardFactoryMock,
            $resultJsonFactoryMock,
            $this->customerTfaServiceMock,
        );
    }

    public function testExecuteSuccess(): void
    {
        $resultRedirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->addMethods(['setPath'])
            ->getMock();

        $this->resultRedirectFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($resultRedirectMock);

        $this->requestMock->expects(self::once())
            ->method('getParam')
            ->with('id')
            ->willReturn(11);

        $this->customerTfaServiceMock->expects(self::once())
            ->method('reset')
            ->with(11)
            ->willReturn(true);

        $this->messageManagerMock->expects(self::once())
            ->method('addSuccessMessage')
            ->with(__('You have revoked the 2FA token.'));

        $resultRedirectMock->expects(self::once())
            ->method('setPath')
            ->with('customer/index/edit', ['id' => 11, '_current' => true])
            ->willReturnSelf();

        $this->assertSame($resultRedirectMock, $this->controller->execute());
    }

    public function testExecuteFailure(): void
    {
        $resultRedirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->addMethods(['setPath'])
            ->getMock();

        $this->resultRedirectFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($resultRedirectMock);

        $this->requestMock->expects(self::once())
            ->method('getParam')
            ->with('id')
            ->willReturn(11);

        $this->customerTfaServiceMock->expects(self::once())
            ->method('reset')
            ->with(11)
            ->willReturn(false);

        $this->messageManagerMock->expects(self::once())
            ->method('addErrorMessage')
            ->with(__('There was an error revoking the 2FA token.'));

        $resultRedirectMock->expects(self::once())
            ->method('setPath')
            ->with('customer/index/edit', ['id' => 11, '_current' => true])
            ->willReturnSelf();

        $this->assertSame($resultRedirectMock, $this->controller->execute());
    }

    public function testExecuteCustomerNotFound(): void
    {
        $resultRedirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->addMethods(['setPath'])
            ->getMock();

        $this->resultRedirectFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($resultRedirectMock);

        $this->requestMock->expects(self::once())
            ->method('getParam')
            ->with('id')
            ->willReturn(0);

        $this->messageManagerMock->expects(self::once())
            ->method('addErrorMessage')
            ->with(__('We can\'t find a customer to revoke.'));

        $resultRedirectMock->expects(self::once())
            ->method('setPath')
            ->with('customer/index/index')
            ->willReturnSelf();

        $this->assertSame($resultRedirectMock, $this->controller->execute());
    }
}
