<?php
declare(strict_types=1);

namespace Unit\ViewModel\Customer;

use Magento\Customer\Model\Session;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Visus\CustomerTfa\Api\Service\CustomerTfaServiceInterface;
use Visus\CustomerTfa\Model\Config;
use Visus\CustomerTfa\ViewModel\Customer\TfaViewModel;

class TfaViewModelTest extends TestCase
{
    /**
     * @var TfaViewModel
     */
    private TfaViewModel $viewModel;

    /**
     * @var CustomerTfaServiceInterface|MockObject
     */
    private readonly CustomerTfaServiceInterface|MockObject $customerTfaServiceMock;

    /**
     * @var Session|MockObject
     */
    private readonly Session|MockObject $customerSessionMock;

    protected function setUp(): void
    {
        $this->customerTfaServiceMock = $this->getMockBuilder(CustomerTfaServiceInterface::class)
            ->onlyMethods(['isEnrolled'])
            ->getMockForAbstractClass();

        $this->customerSessionMock = $this->createPartialMock(Session::class, ['getCustomerId']);

        $this->viewModel = new TfaViewModel(
            $this->customerTfaServiceMock,
            $this->customerSessionMock
        );
    }

    public function testIsEnrolled(): void
    {
        $this->customerSessionMock->expects(self::once())
            ->method('getCustomerId')
            ->willReturn(1);

        $this->customerTfaServiceMock->expects(self::once())
            ->method('isEnrolled')
            ->with(1)
            ->willReturn(true);

        $this->viewModel->isEnrolled();
    }

    public function testGetNonceValidationCookieName(): void
    {
        $this->assertEquals(Config::COOKIE_NAME, $this->viewModel->getNonceValidationCookieName());
    }

}
