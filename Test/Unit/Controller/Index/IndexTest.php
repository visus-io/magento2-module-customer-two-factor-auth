<?php
declare(strict_types=1);

namespace Unit\Controller\Index;

use Magento\Customer\Model\Session;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Visus\CustomerTfa\Controller\Index\Index;

class IndexTest extends TestCase
{
    /**
     * @var Index
     */
    private Index $controller;

    /**
     * @var CookieManagerInterface|MockObject
     */
    private CookieManagerInterface|MockObject $cookieManagerMock;

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
        $this->cookieManagerMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->onlyMethods(['deleteCookie'])
            ->getMockForAbstractClass();

        $this->customerSessionMock = $this->createPartialMock(
            Session::class,
            [
                'authenticate',
                'isLoggedIn',
                'setAfterAuthUrl'
            ]
        );

        $resultPageFactory = $this->initResultPage();

        $urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);

        $this->controller = new Index(
            $this->cookieManagerMock,
            $this->customerSessionMock,
            $resultPageFactory,
            $urlBuilder
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
        $this->customerSessionMock->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $pageTitle = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pageTitle->expects(self::once())
            ->method('set')
            ->with(__('Two-Factor Authentication'))
            ->willReturnSelf();

        $pageConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cookieManagerMock->expects(self::once())
            ->method('deleteCookie')
            ->with(\Visus\CustomerTfa\Model\Config::COOKIE_NAME);

        $this->resultPageMock->expects(self::once())
            ->method('getConfig')
            ->willReturn($pageConfig);

        $pageConfig->expects(self::once())
            ->method('getTitle')
            ->willReturn($pageTitle);

        $this->controller->execute();
    }

    public function testExecuteAuthenticate(): void
    {
        $this->customerSessionMock->method('isLoggedIn')->willReturn(false);

        $this->customerSessionMock->expects(self::once())
            ->method('setAfterAuthUrl')
            ->willReturnSelf();

        $this->customerSessionMock->expects(self::once())
            ->method('authenticate');

        $this->controller->execute();
    }
}
