<?php
declare(strict_types=1);

namespace Unit\Block\Customer;

use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Visus\CustomerTfa\Block\Customer\Authenticate;

class AuthenticateTest extends TestCase
{
    /**
     * @var Authenticate
     */
    private Authenticate $block;

    /**
     * @var FormKey|MockObject
     */
    private FormKey|MockObject $formKeyMock;


    protected function setUp(): void
    {
        $contextMock = $this->createPartialMock(Context::class, ['getSession']);

        $this->formKeyMock = $this->createPartialMock(FormKey::class, ['getFormKey']);

        $this->block = new Authenticate(
            $contextMock,
            $this->formKeyMock
        );
    }

    public function testGetFormKey(): void
    {
        $this->formKeyMock->expects(self::once())
            ->method('getFormKey')
            ->willReturn('formKey');

        $this->assertEquals('formKey', $this->block->getFormKey());
    }
}
