<?php
declare(strict_types=1);

namespace Unit\Service;

use Magento\Customer\Model\Customer;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Visus\CustomerTfa\Api\CustomerTfaChallengeRepositoryInterface;
use Visus\CustomerTfa\Api\Data\CustomerTfaChallengeInterface;
use Visus\CustomerTfa\Api\Data\CustomerTfaChallengeInterfaceFactory;
use Visus\CustomerTfa\Service\CustomerTfaChallengeService;

class CustomerTfaChallengeServiceTest extends TestCase
{
    private const TEST_CHALLENGE_CODE = '260147';

    private const TEST_CUSTOMER_ID = 11;

    private const TEST_CUSTOMER_NAME = 'John Doe';

    private const TEST_CUSTOMER_EMAIL = 'john.doe@example.com';

    private const TEST_STORE_ID = 1;

    private const TEST_IDENT_SUPPORT_EMAIL = 'support@app.example.net';

    /**
     * @var CustomerTfaChallengeService
     */
    private CustomerTfaChallengeService $service;

    /**
     * @var CustomerTfaChallengeInterfaceFactory|MockObject
     */
    private CustomerTfaChallengeInterfaceFactory|MockObject $customerTfaChallengeFactoryMock;

    /**
     * @var CustomerTfaChallengeRepositoryInterface|MockObject
     */
    private CustomerTfaChallengeRepositoryInterface|MockObject $customerTfaChallengeRepositoryMock;

    /**
     * @var EncryptorInterface|MockObject
     */
    private EncryptorInterface|MockObject $encryptorMock;

    /**
     * @var TransportBuilder|MockObject
     */
    private TransportBuilder|MockObject $transportBuilderMock;

    /**
     * @var StoreInterface|MockObject
     */
    private StoreInterface|MockObject $storeMock;

    protected function setUp(): void
    {
        $this->customerTfaChallengeFactoryMock =
            $this->getMockBuilder(CustomerTfaChallengeInterfaceFactory::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['create'])
                ->getMockForAbstractClass();

        $this->customerTfaChallengeRepositoryMock =
            $this->getMockBuilder(CustomerTfaChallengeRepositoryInterface::class)
                ->onlyMethods(['deleteById','getById'])
                ->getMockForAbstractClass();

        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->encryptorMock = $this->getMockForAbstractClass(EncryptorInterface::class);

        $this->transportBuilderMock = $this->getMockBuilder(TransportBuilder::class)
            ->disableOriginalConstructor()
            ->addMethods(['sendMessage'])
            ->onlyMethods([
                'addTo',
                'getTransport',
                'setTemplateIdentifier',
                'setTemplateOptions',
                'setTemplateVars',
                'setFromByScope'
            ])
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->onlyMethods(['getId'])
            ->getMockForAbstractClass();

        $this->storeMock ->expects(self::any())
            ->method('getId')
            ->willReturn(self::TEST_STORE_ID);

        $storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->onlyMethods(['getStore'])
            ->getMockForAbstractClass();

        $storeManagerMock->expects(self::any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->onlyMethods(['getValue'])
            ->getMockForAbstractClass();

        $scopeConfigMock->expects(self::any())
            ->method('getValue')
            ->with('trans_email/ident_support/email', ScopeInterface::SCOPE_STORE)
            ->willReturn(self::TEST_IDENT_SUPPORT_EMAIL);

        $this->service = new CustomerTfaChallengeService(
            $this->customerTfaChallengeFactoryMock,
            $this->customerTfaChallengeRepositoryMock,
            $loggerMock,
            $this->encryptorMock,
            $this->transportBuilderMock,
            $storeManagerMock,
            $scopeConfigMock
        );
    }

    public function testSendEmail(): void
    {
        $challengeMock = $this->getMockForAbstractClass(CustomerTfaChallengeInterface::class);

        $this->encryptorMock->expects(self::once())
            ->method('encrypt')
            ->willReturn('some value');

        $this->customerTfaChallengeFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($challengeMock);

        $customerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->addMethods(['getEmail'])
            ->onlyMethods(['getId','getName'])
            ->getMock();

        $customerMock->expects(self::once())
            ->method('getId')
            ->willReturn(self::TEST_CUSTOMER_ID);

        $customerMock->expects(self::exactly(2))
            ->method('getName')
            ->willReturn(self::TEST_CUSTOMER_NAME);

        $customerMock->expects(self::once())
            ->method('getEmail')
            ->willReturn(self::TEST_CUSTOMER_EMAIL);

        $this->transportBuilderMock->expects(self::once())
            ->method('setTemplateIdentifier')
            ->with('visus_tfa_challenge_email_template')
            ->willReturnSelf();

        $this->transportBuilderMock->expects(self::once())
            ->method('setTemplateOptions')
            ->with([
                'area' => Area::AREA_FRONTEND,
                'store' => self::TEST_STORE_ID
            ])
            ->willReturnSelf();

        $this->transportBuilderMock->expects(self::once())
            ->method('setTemplateVars')
            ->with($this->callback(function (array $array): bool {
                $this->assertIsArray($array);
                $this->assertIsArray($array['customer']);

                $this->assertEquals(self::TEST_CUSTOMER_NAME, $array['customer']['name']);
                $this->assertEquals(self::TEST_IDENT_SUPPORT_EMAIL, $array['support_email']);
                $this->assertEquals($this->storeMock, $array['store']);

                $this->assertMatchesRegularExpression("#^\d{6}$#", $array['challenge']);

                return true;
            }))
            ->willReturnSelf();

        $this->transportBuilderMock->expects(self::once())
            ->method('setFromByScope')
            ->with('support')
            ->willReturnSelf();

        $this->transportBuilderMock->expects(self::once())
            ->method('addTo')
            ->with(self::TEST_CUSTOMER_EMAIL, self::TEST_CUSTOMER_NAME)
            ->willReturnSelf();

        $this->transportBuilderMock->expects(self::once())
            ->method('getTransport')
            ->willReturnSelf();

        $this->transportBuilderMock->expects(self::once())
            ->method('sendMessage');

        $this->service->sendEmail($customerMock);
    }

    public function testVerify(): void
    {
        $challengeMock = $this->getMockForAbstractClass(CustomerTfaChallengeInterface::class);
        $challengeMock->expects(self::once())
            ->method('getExpiresAt')
            ->willReturn('2199-01-01 00:00:00');

        $challengeMock->expects(self::once())
            ->method('getChallenge')
            ->willReturn('0:3:NDAwNTMx');

        $this->customerTfaChallengeRepositoryMock->expects(self::once())
            ->method('getById')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn($challengeMock);

        $this->customerTfaChallengeRepositoryMock->expects(self::once())
            ->method('deleteById')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn(true);

        $this->encryptorMock->expects(self::once())
            ->method('decrypt')
            ->with($this->isType('string'))
            ->willReturn(self::TEST_CHALLENGE_CODE);

        $this->assertTrue(
            $this->service->verify(self::TEST_CUSTOMER_ID, self::TEST_CHALLENGE_CODE)
        );
    }

    public function testVerifyIsExpired(): void
    {
        $challengeMock = $this->getMockForAbstractClass(CustomerTfaChallengeInterface::class);
        $challengeMock->expects(self::once())
            ->method('getExpiresAt')
            ->willReturn('2000-01-01 00:00:00');

        $this->customerTfaChallengeRepositoryMock->expects(self::once())
            ->method('getById')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn($challengeMock);

        $this->customerTfaChallengeRepositoryMock->expects(self::once())
            ->method('deleteById')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn(true);

        $this->assertFalse(
            $this->service->verify(self::TEST_CUSTOMER_ID, self::TEST_CHALLENGE_CODE)
        );
    }
}
