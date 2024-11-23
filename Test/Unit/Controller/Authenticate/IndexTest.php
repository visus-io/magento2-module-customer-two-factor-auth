<?php
declare(strict_types=1);

namespace Unit\Controller\Authenticate;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Visus\CustomerTfa\Api\Service\CustomerNonceServiceInterface;
use Visus\CustomerTfa\Controller\Authenticate\Index;

class IndexTest extends TestCase
{
    /**
     * @var Index
     */
    private Index $controller;

    /**
     * @var CustomerNonceServiceInterface|MockObject
     */
    private CustomerNonceServiceInterface|MockObject $customerNonceServiceMock;

    /**
     * @var Session|MockObject
     */
    private Session|MockObject $customerSessionMock;

    /**
     * @var Page|MockObject
     */
    private Page|MockObject $resultPageMock;

    protected function setUp(): void
    {
        $this->customerNonceServiceMock = $this->getMockBuilder(CustomerNonceServiceInterface::class)
            ->onlyMethods(['generate'])
            ->getMockForAbstractClass();

        $this->customerSessionMock = $this->createPartialMock(Session::class, ['getCustomer']);

        $resultPageFactory = $this->initResultPage();

        $this->controller = new Index(
            $this->customerNonceServiceMock,
            $this->customerSessionMock,
            $resultPageFactory
        );
    }

    /**
     * @return PageFactory|MockObject
     */
    protected function initResultPage(): PageFactory|MockObject
    {
        $this->resultPageMock = $this->createPartialMock(
            \Magento\Backend\Model\View\Result\Page::class,
            ['getConfig']
        );

        $resultPageFactoryMock = $this->createPartialMock(PageFactory::class, ['create']);

        $resultPageFactoryMock->expects(self::any())
            ->method('create')
            ->willReturn($this->resultPageMock);

        return $resultPageFactoryMock;
    }

    public function testExecute(): void
    {
        $customerMock = $this->createMock(Customer::class);

        $this->customerSessionMock->expects(self::once())
            ->method('getCustomer')
            ->willReturn($customerMock);

        $this->customerNonceServiceMock->expects(self::once())
            ->method('generate')
            ->with();

        $pageTitleMock = $this->createPartialMock(Title::class, ['set']);

        $pageTitleMock->expects(self::once())
            ->method('set')
            ->with(__('Two-Factor Authentication'))
            ->willReturnSelf();

        $pageConfigMock = $this->createPartialMock(Config::class, ['getTitle']);

        $this->resultPageMock->expects(self::once())
            ->method('getConfig')
            ->willReturn($pageConfigMock);

        $pageConfigMock->expects(self::once())
            ->method('getTitle')
            ->willReturn($pageTitleMock);

        $this->controller->execute();
    }
}
