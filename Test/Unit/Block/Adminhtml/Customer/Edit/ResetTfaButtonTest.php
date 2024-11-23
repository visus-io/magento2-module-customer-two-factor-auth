<?php
declare(strict_types=1);

namespace Unit\Block\Adminhtml\Customer\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Visus\CustomerTfa\Api\CustomerTfaRepositoryInterface;
use Visus\CustomerTfa\Block\Adminhtml\Customer\Edit\ResetTfaButton;

class ResetTfaButtonTest extends TestCase
{
    private const TEST_CUSTOMER_ID = 11;

    /**
     * @var ResetTfaButton
     */
    private ResetTfaButton $button;

    /**
     * @var CustomerTfaRepositoryInterface|MockObject
     */
    private CustomerTfaRepositoryInterface|MockObject $customerTfaRepositoryMock;

    /**
     * @var Registry|MockObject
     */
    private Registry|MockObject $registryMock;

    /**
     * @var UrlInterface|MockObject
     */
    private UrlInterface|MockObject $urlBuilderMock;


    protected function setUp(): void
    {
        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->onlyMethods(['getUrl'])
            ->getMockForAbstractClass();

        $contextMock = $this->createPartialMock(Context::class, ['getUrlBuilder']);
        $contextMock->expects(self::once())
            ->method('getUrlBuilder')
            ->willReturn($this->urlBuilderMock);

        $this->registryMock = $this->createPartialMock(Registry::class, ['registry']);

        $this->customerTfaRepositoryMock = $this->getMockBuilder(CustomerTfaRepositoryInterface::class)
            ->onlyMethods(['isEnrolled'])
            ->getMockForAbstractClass();

        $this->button = new ResetTfaButton(
            $contextMock,
            $this->registryMock,
            $this->customerTfaRepositoryMock
        );
    }

    public function testGetButtonData(): void
    {
        $url = 'https://app.example.net/backend/visus_tfa/customer/reset/id/' . self::TEST_CUSTOMER_ID;

        $expected = [
            'label' => __('Reset 2FA'),
            'class' => 'reset',
            'on_click' => sprintf("location.href = '%s'", $url),
            'sort_order' => 60,
            'aclResource' => 'Visus_CustomerTfa::reset'
        ];

        $this->urlBuilderMock->expects(self::once())
            ->method('getUrl')
            ->with('visus_tfa/customer/reset', ['id' => self::TEST_CUSTOMER_ID])
            ->willReturn($url);

        $this->registryMock->expects($this->exactly(2))
            ->method('registry')
            ->with(RegistryConstants::CURRENT_CUSTOMER_ID)
            ->willReturn(self::TEST_CUSTOMER_ID);

        $this->customerTfaRepositoryMock->expects(self::once())
            ->method('isEnrolled')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn(true);

        $this->assertEquals($expected, $this->button->getButtonData());
    }
}
