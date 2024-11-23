<?php
declare(strict_types=1);

namespace Unit\Service;

use Magento\Customer\Model\Customer;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use OTPHP\TOTP;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Visus\CustomerTfa\Api\CustomerTfaRepositoryInterface;
use Visus\CustomerTfa\Api\Data\CustomerTfaInterface;
use Visus\CustomerTfa\Api\Data\CustomerTfaInterfaceFactory;
use Visus\CustomerTfa\Model\Config;
use Visus\CustomerTfa\Service\CustomerTfaService;

class CustomerTfaServiceTest extends TestCase
{
    private const TEST_CUSTOMER_ID = 11;


    private const TEST_TFA_SECRET = 'Y3F6Z5KT3ZNVMM3W6SHZS67DUA';

    /**
     * @var CustomerTfaService
     */
    private CustomerTfaService $service;

    /**
     * @var CustomerTfaInterface|MockObject
     */
    private CustomerTfaInterface|MockObject $customerTfaMock;

    /**
     * @var CustomerTfaRepositoryInterface|MockObject
     */
    private CustomerTfaRepositoryInterface|MockObject $customerTfaRepositoryMock;

    /**
     * @var EncryptorInterface|MockObject
     */
    private EncryptorInterface|MockObject $encryptorMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private SerializerInterface|MockObject $serializerMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private StoreManagerInterface|MockObject $storeManagerMock;

    protected function setUp(): void
    {
        $configMock = $this->createMock(Config::class);

        $this->customerTfaMock = $this->getMockForAbstractClass(CustomerTfaInterface::class);

        $customerTfaFactoryMock = $this->getMockBuilder(CustomerTfaInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->addMethods(['getSecret','setSecret'])
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();

        $customerTfaFactoryMock->expects(self::any())
            ->method('create')
            ->willReturn($this->customerTfaMock);

        $this->customerTfaRepositoryMock = $this->getMockForAbstractClass(CustomerTfaRepositoryInterface::class);

        $this->encryptorMock = $this->getMockForAbstractClass(EncryptorInterface::class);

        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->serializerMock = $this->getMockForAbstractClass(SerializerInterface::class);

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->onlyMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);

        $this->service = new CustomerTfaService(
            $configMock,
            $customerTfaFactoryMock,
            $this->customerTfaRepositoryMock,
            $this->encryptorMock,
            $loggerMock,
            $this->serializerMock,
            $this->storeManagerMock
        );
    }

    public function testGenerateQrCode(): void
    {
        // SHA-512 hash of the QR Code PNG
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = '493ec6ab8cd9a5e802684aa994587c3bcb66fd3af545f7a4ef0ebe62705ee3abf6639b1264f51e24b0c62959447436aef1ff5053d6af1258f144818f9385fcc5';
        // phpcs:enable Generic.Files.LineLength.TooLong

        $customerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->addMethods(['getEmail'])
            ->onlyMethods(['getId'])
            ->getMock();

        $customerMock->expects(self::exactly(2))
            ->method('getId')
            ->willReturn(11);

        $customerMock->expects(self::once())
            ->method('getEmail')
            ->willReturn('john.doe@example.com');

        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getBaseUrl'])
            ->getMockForAbstractClass();

        $storeMock->expects(self::once())
            ->method('getBaseUrl')
            ->willReturn('https://app.example.net');

        $this->storeManagerMock->expects(self::once())
            ->method('getStore')
            ->willReturn($storeMock);

        $this->customerTfaRepositoryMock->expects(self::once())
            ->method('getById')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn($this->customerTfaMock);

        $this->encryptorMock->expects(self::once())
            ->method('decrypt')
            ->willReturn(self::TEST_TFA_SECRET);

        $result = hash('sha512', $this->service->generateQrCode($customerMock)->getDataUri());

        $this->assertEquals($expected, $result);
    }

    public function testGenerateRecoveryCodes(): void
    {
        $this->customerTfaRepositoryMock->expects(self::once())
            ->method('getById')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn($this->customerTfaMock);

        $this->serializerMock->expects(self::once())
            ->method('serialize')
            ->willReturn('a:1:{i:0;s:11:"12346-abcde";}');

        $this->encryptorMock->expects(self::once())
            ->method('encrypt')
            ->willReturn('0:3:YToxOntpOjA7czoxMToiMTIzNDYtYWJjZGUiO30');

        $this->customerTfaRepositoryMock->expects(self::once())
            ->method('save')
            ->with($this->customerTfaMock);

        $results = $this->service->generateRecoveryCodes(self::TEST_CUSTOMER_ID);

        $this->assertIsArray($results);
    }

    public function testGetRecoveryCodes(): void
    {
        $this->customerTfaRepositoryMock->expects(self::once())
            ->method('getById')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn($this->customerTfaMock);

        $this->customerTfaMock->expects(self::once())
            ->method('getSecret')
            ->willReturn(self::TEST_TFA_SECRET);

        $this->customerTfaMock->expects(self::once())
            ->method('getRecoveryCodes')
            ->willReturn('a:1:{i:0;s:11:"12346-abcde";}');

        $this->serializerMock->expects(self::once())
            ->method('unserialize')
            ->willreturn(['12346-abcde']);

        $this->assertIsArray($this->service->getRecoveryCodes(self::TEST_CUSTOMER_ID));
    }

    public function testGetSecret(): void
    {
        $this->customerTfaRepositoryMock->expects(self::once())
            ->method('getById')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn($this->customerTfaMock);

        $this->encryptorMock->expects(self::once())
            ->method('decrypt')
            ->willReturn(self::TEST_TFA_SECRET);

        $this->assertEquals(
            self::TEST_TFA_SECRET,
            $this->service->getSecret(self::TEST_CUSTOMER_ID)
        );
    }

    public function testGetSecretException(): void
    {
        $this->customerTfaRepositoryMock->expects(self::exactly(2))
            ->method('getById')
            ->with(self::TEST_CUSTOMER_ID)
            ->willThrowException(new NoSuchEntityException());

        $this->customerTfaMock->expects(self::once())
            ->method('setCustomerId')
            ->with(self::TEST_CUSTOMER_ID);

        $this->encryptorMock->expects(self::once())
            ->method('encrypt')
            ->willReturn(self::TEST_TFA_SECRET);

        $this->assertNotEmpty($this->service->getSecret(self::TEST_CUSTOMER_ID));
    }

    public function testGetSecretGenerate(): void
    {
        $this->customerTfaRepositoryMock->expects(self::exactly(2))
            ->method('getById')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn($this->customerTfaMock);

        $this->encryptorMock->expects(self::once())
            ->method('decrypt')
            ->willReturn(null);

        $this->encryptorMock->expects(self::once())
            ->method('encrypt')
            ->willReturn(self::TEST_TFA_SECRET);

        $this->assertNotEmpty($this->service->getSecret(self::TEST_CUSTOMER_ID));
    }

    public function testIsEnrolled(): void
    {
        $this->customerTfaRepositoryMock->expects(self::once())
            ->method('isEnrolled')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn(true);

        $this->service->isEnrolled(self::TEST_CUSTOMER_ID);
    }

    public function testReset(): void
    {
        $this->customerTfaRepositoryMock->expects(self::once())
            ->method('reset')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn(true);

        $this->service->reset(self::TEST_CUSTOMER_ID);
    }

    public function testVerify(): void
    {
        $this->customerTfaRepositoryMock->expects(self::once())
            ->method('getById')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn($this->customerTfaMock);

        $this->encryptorMock->expects(self::once())
            ->method('decrypt')
            ->willReturn(self::TEST_TFA_SECRET);

        $otp = TOTP::createFromSecret(self::TEST_TFA_SECRET);

        $code = $otp->now();

        $this->assertTrue($this->service->verify(self::TEST_CUSTOMER_ID, $code));
    }
}
