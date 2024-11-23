<?php
declare(strict_types=1);

namespace Unit\Service;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Math\Random;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Visus\CustomerTfa\Model\Config;
use Visus\CustomerTfa\Service\CustomerNonceService;

class CustomerNonceServiceTest extends TestCase
{
    private const TEST_CUSTOMER_ID = 11;

    private const TEST_DEFAULT_TIMEOUT = 900;

    private const TEST_NONCE_SECRET = 'F2gw6elV+QObawXigMHjNGfxR+QLVnXI';

    private const TEST_NONCE_SECRET_ENCRYPTED = '0:3:ZQm+0/O3nqA=';

    /**
     * @var CustomerNonceService
     */
    private CustomerNonceService $service;

    /**
     * @var Customer|MockObject
     */
    private Customer|MockObject $customerMock;

    /**
     * @var CookieManagerInterface|MockObject
     */
    private CookieManagerInterface|MockObject $cookieManagerMock;

    /**
     * @var CookieMetadataFactory|MockObject
     */
    private CookieMetadataFactory|MockObject $cookieMetadataFactoryMock;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private CustomerRepositoryInterface|MockObject $customerRepositoryMock;

    /**
     * @var EncryptorInterface|MockObject
     */
    private EncryptorInterface|MockObject $encryptorMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $loggerMock;

    /**
     * @var Random|MockObject
     */
    private Random|MockObject $randomMock;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private StoreManagerInterface|MockObject $storeManagerMock;

    protected function setUp(): void
    {
        $this->cookieManagerMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->onlyMethods(['getCookie', 'setPublicCookie'])
            ->getMockForAbstractClass();

        $this->cookieMetadataFactoryMock = $this->getMockBuilder(CookieMetadataFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createPublicCookieMetadata'])
            ->getMock();

        $this->customerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->addMethods(['getStoreId'])
            ->onlyMethods(['getId', 'getData','getDataModel'])
            ->getMock();

        $this->customerMock->expects(self::once())
            ->method('getStoreId')
            ->willReturn(1);

        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->onlyMethods(['save'])
            ->getMockForAbstractClass();

        $this->encryptorMock = $this->getMockBuilder(EncryptorInterface::class)
            ->onlyMethods(['encrypt','decrypt'])
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->onlyMethods(['critical'])
            ->getMockForAbstractClass();

        $this->randomMock = $this->createMock(Random::class);

        $this->serializer = new Serialize();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->service = new CustomerNonceService(
            $this->cookieManagerMock,
            $this->cookieMetadataFactoryMock,
            $this->customerRepositoryMock,
            $this->encryptorMock,
            $this->loggerMock,
            $this->randomMock,
            $this->serializer,
            $this->storeManagerMock
        );
    }

    public function testGenerate(): void
    {
        $cookieMetadataMock = $this->createMock(PublicCookieMetadata::class);

        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getBaseUrl'])
            ->getMockForAbstractClass();

        $storeMock->expects(self::once())
            ->method('getBaseUrl')
            ->willReturn('https://app.example.net');

        $this->customerMock->expects(self::exactly(2))
            ->method('getId')
            ->willReturn(self::TEST_CUSTOMER_ID);

        $this->customerMock->expects(self::once())
            ->method('getData')
            ->with(Config::CUSTOMER_NONCE_SECRET_ATTRIBUTE_CODE)
            ->willReturn(self::TEST_NONCE_SECRET_ENCRYPTED);

        $this->encryptorMock->expects(self::once())
            ->method('decrypt')
            ->with(self::TEST_NONCE_SECRET_ENCRYPTED)
            ->willReturn(self::TEST_NONCE_SECRET);

        $this->storeManagerMock->expects(self::once())
            ->method('getStore')
            ->willReturn($storeMock);

        $this->cookieMetadataFactoryMock->expects(self::once())
            ->method('createPublicCookieMetadata')
            ->willReturn($cookieMetadataMock);

        $cookieMetadataMock->expects(self::once())
            ->method('setDomain')
            ->with('app.example.net')
            ->willReturnSelf();

        $cookieMetadataMock->expects(self::once())
            ->method('setDuration')
            ->with(self::TEST_DEFAULT_TIMEOUT)
            ->willReturnSelf();

        $cookieMetadataMock->expects(self::once())
            ->method('setPath')
            ->with('/')
            ->willReturnSelf();

        $cookieMetadataMock->expects(self::once())
            ->method('setSameSite')
            ->with('Strict')
            ->willReturnSelf();

        $cookieMetadataMock->expects(self::once())
            ->method('setSecure')
            ->with(true)
            ->willReturnSelf();

        $this->assertTrue($this->service->generate($this->customerMock));
    }

    public function testSetCustomAttribute(): void
    {
        $customerExtensionsMock = $this->getMockForAbstractClass(CustomerExtensionInterface::class);
        $customerExtensionsMock->expects(self::once())
            ->method('setVisusNonceSecret')
            ->willReturn(self::TEST_NONCE_SECRET_ENCRYPTED);

        $customerDataMock = $this->getMockForAbstractClass(CustomerInterface::class);
        $customerDataMock->expects(self::once())
            ->method('getExtensionAttributes')
            ->willReturn($customerExtensionsMock);

        $customerDataMock->expects(self::once())
            ->method('setExtensionAttributes')
            ->with($customerExtensionsMock)
            ->willReturnSelf();

        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getBaseUrl'])
            ->getMockForAbstractClass();

        $storeMock->expects(self::once())
            ->method('getBaseUrl')
            ->willReturn('https://app.example.net');

        $this->cookieManagerMock->expects(self::once())
            ->method('getCookie')
            ->with(Config::COOKIE_NAME)
            ->willReturn($this->generateNonce());

        $this->customerMock->expects(self::exactly(2))
            ->method('getId')
            ->willReturn(self::TEST_CUSTOMER_ID);

        $this->customerMock->expects(self::once())
            ->method('getData')
            ->with(Config::CUSTOMER_NONCE_SECRET_ATTRIBUTE_CODE)
            ->willReturn(null);

        $this->customerMock->expects(self::once())
            ->method('getDataModel')
            ->willReturn($customerDataMock);

        $this->storeManagerMock->expects(self::once())
            ->method('getStore')
            ->willReturn($storeMock);

        $this->encryptorMock->expects(self::once())
            ->method('encrypt')
            ->willReturn(self::TEST_NONCE_SECRET_ENCRYPTED);

        $this->randomMock->expects(self::once())
            ->method('getRandomBytes')
            ->willReturn(self::TEST_NONCE_SECRET);

        $this->customerRepositoryMock->expects(self::once())
            ->method('save')
            ->with($customerDataMock);

        $this->assertTrue($this->service->validate($this->customerMock));
    }

    public function testValidate(): void
    {
        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getBaseUrl'])
            ->getMockForAbstractClass();

        $storeMock->expects(self::once())
            ->method('getBaseUrl')
            ->willReturn('https://app.example.net');

        $this->cookieManagerMock->expects(self::once())
            ->method('getCookie')
            ->with(Config::COOKIE_NAME)
            ->willReturn($this->generateNonce());

        $this->customerMock->expects($this->atMost(2))
            ->method('getId')
            ->willReturn(self::TEST_CUSTOMER_ID);

        $this->customerMock->expects(self::once())
            ->method('getData')
            ->with(Config::CUSTOMER_NONCE_SECRET_ATTRIBUTE_CODE)
            ->willReturn(self::TEST_NONCE_SECRET_ENCRYPTED);

        $this->encryptorMock->expects(self::once())
            ->method('decrypt')
            ->with(self::TEST_NONCE_SECRET_ENCRYPTED)
            ->willReturn(self::TEST_NONCE_SECRET);

        $this->storeManagerMock->expects(self::once())
            ->method('getStore')
            ->willReturn($storeMock);

        $this->loggerMock->expects($this->never())
            ->method('critical');

        $this->assertTrue($this->service->validate($this->customerMock));
    }

    private function generateNonce(): string
    {
        $random = new Random();

        $expires = time() + self::TEST_DEFAULT_TIMEOUT;
        $salt = $random->getRandomString(10);

        $data = [
            'customer_id' => self::TEST_CUSTOMER_ID,
            'domain' => 'app.example.net',
            'expires' => $expires,
            'salt' => $salt
        ];

        $hash = hash_hmac('sha3-512', $this->serializer->serialize($data), self::TEST_NONCE_SECRET);

        return $salt . '~' . $expires . '~' . $hash;
    }
}
