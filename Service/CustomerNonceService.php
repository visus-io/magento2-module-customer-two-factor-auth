<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Service;

use Exception;
use Laminas\Uri\UriFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Math\Random;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Visus\CustomerTfa\Api\Service\CustomerNonceServiceInterface;
use Visus\CustomerTfa\Model\Config;

/**
 * Nonce Generation and Validation Service
 *
 * @since 1.0.0
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerNonceService implements CustomerNonceServiceInterface
{
    private const DEFAULT_TIMEOUT = 900;

    /**
     * @var CookieManagerInterface
     */
    private readonly CookieManagerInterface $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    private readonly CookieMetadataFactory $cookieMetadataFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private readonly CustomerRepositoryInterface $customerRepository;

    /**
     * @var EncryptorInterface
     */
    private readonly EncryptorInterface $encryptor;

    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * @var Random
     */
    private readonly Random $mathRandom;

    /**
     * @var SerializerInterface
     */
    private readonly SerializerInterface $serializer;

    /**
     * @var StoreManagerInterface
     */
    private readonly StoreManagerInterface $storeManager;

    /**
     * Constructor
     *
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param EncryptorInterface $encryptor
     * @param LoggerInterface $logger
     * @param Random $mathRandom
     * @param SerializerInterface $serializer
     * @param StoreManagerInterface $storeManager
     *
     * @SuppressWarnings("php:S107")
     */
    public function __construct(
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        CustomerRepositoryInterface $customerRepository,
        EncryptorInterface $encryptor,
        LoggerInterface $logger,
        Random $mathRandom,
        SerializerInterface $serializer,
        StoreManagerInterface $storeManager
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->customerRepository = $customerRepository;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        $this->mathRandom = $mathRandom;
        $this->serializer = $serializer;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings("php:S1142")
     */
    public function generate(Customer $customer): bool
    {
        if (empty($customer->getId())) {
            return false;
        }

        try {
            $secret = $this->getOrCreateSecret($customer);
            if (empty($secret)) {
                return false;
            }

            $salt = $this->mathRandom->getRandomString(10);
            $store = $this->storeManager->getStore($customer->getStoreId());
            $domain = UriFactory::factory($store->getBaseUrl())->getHost();
            $expires = time() + self::DEFAULT_TIMEOUT;

            $data = [
                'customer_id' => $customer->getId(),
                'domain' => $domain,
                'expires' => $expires,
                'salt' => $salt,
            ];

            $hash = hash_hmac('sha3-512', $this->serializer->serialize($data), $secret);

            $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
                ->setDomain($domain)
                ->setDuration(self::DEFAULT_TIMEOUT)
                ->setPath('/')
                ->setSameSite('Strict')
                ->setSecure(true);

            $nonce = $salt . '~' . $expires . '~' . $hash;

            $this->cookieManager->setPublicCookie(Config::COOKIE_NAME, $nonce, $metadata);

            return true;
        } catch (Exception $e) {
            $this->logger->critical($e);
            return false;
        }
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings("php:S1142")
     */
    public function validate(Customer $customer): bool
    {
        $nonce = $this->cookieManager->getCookie(Config::COOKIE_NAME);
        if (empty($nonce) || empty($customer->getId())) {
            return false;
        }

        try {
            $secret = $this->getOrCreateSecret($customer);
            if (empty($secret)) {
                return false;
            }

            $store = $this->storeManager->getStore($customer->getStoreId());
            $domain = UriFactory::factory($store->getBaseUrl())->getHost();

            $values = explode('~', $nonce);
            if (count($values) !== 3) {
                return false;
            }

            $salt = $values[0];
            $expires = $values[1];
            $hash = $values[2];

            $data = [
                'customer_id' => $customer->getId(),
                'domain' => $domain,
                'expires' => (int)$expires,
                'salt' => $salt,
            ];

            $expected  = hash_hmac('sha3-512', $this->serializer->serialize($data), $secret);

            return hash_equals($expected, $hash) && time() < $expires;
        } catch (Exception $e) {
            $this->logger->critical($e);
            return false;
        }
    }

    /**
     * Gets or creates the nonce secret for the customer.
     *
     * @param Customer $customer
     * @return string|null
     */
    private function getOrCreateSecret(Customer $customer): ?string
    {
        try {
            $secret = $customer->getData(Config::CUSTOMER_NONCE_SECRET_ATTRIBUTE_CODE);
            if (!empty($secret)) {
                return $this->encryptor->decrypt($secret);
            }

            $secret = $this->mathRandom->getRandomBytes(24);

            $customerData = $customer->getDataModel();

            $extensionAttributes = $customerData->getExtensionAttributes();
            $extensionAttributes->setVisusNonceSecret($this->encryptor->encrypt($secret));

            $customerData->setExtensionAttributes($extensionAttributes);

            $this->customerRepository->save($customerData);

            return $secret;
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
            return null;
        }
    }
}
