<?php
declare(strict_types=1);

namespace Unit\Plugin\Magento\Customer\Model;

use Magento\Customer\Api\Data\CustomerExtensionFactory;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Math\Random;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Visus\CustomerTfa\Plugin\Magento\Customer\Model\AccountManagement;

class AccountManagementTest extends TestCase
{

    private const TEST_NONCE_SECRET_ENCRYPTED = '0:3:ZQm+0/O3nqA=';

    /**
     * @var AccountManagement
     */
    private AccountManagement $plugin;

    /**
     * @var EncryptorInterface|MockObject
     */
    private EncryptorInterface|MockObject $encryptorMock;

    /**
     * @var Random
     */
    private readonly Random $mathRandom;

    protected function setUp(): void
    {
        $customerExtensionFactoryMock = $this->createMock(CustomerExtensionFactory::class);

        $this->encryptorMock = $this->createMock(EncryptorInterface::class);

        $loggerMock = $this->createMock(LoggerInterface::class);

        $this->mathRandom = new Random();

        $this->plugin = new AccountManagement(
            $customerExtensionFactoryMock,
            $this->encryptorMock,
            $loggerMock,
            $this->mathRandom
        );
    }

    public function testBeforeCreateAccount(): void
    {
        $accountManagementMock = $this->createMock(\Magento\Customer\Model\AccountManagement::class);

        $customerExtensionsMock = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->onlyMethods(['setVisusNonceSecret'])
            ->getMockForAbstractClass();

        $customerExtensionsMock->expects(self::once())
            ->method('setVisusNonceSecret')
            ->willReturn(self::TEST_NONCE_SECRET_ENCRYPTED);

        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->onlyMethods(['getExtensionAttributes'])
            ->getMockForAbstractClass();

        $customerMock->expects(self::once())
            ->method('getExtensionAttributes')
            ->willReturn($customerExtensionsMock);

        $this->encryptorMock->expects(self::once())
            ->method('encrypt')
            ->willReturn(self::TEST_NONCE_SECRET_ENCRYPTED);

        $this->plugin->beforeCreateAccount($accountManagementMock, $customerMock);
    }
}
