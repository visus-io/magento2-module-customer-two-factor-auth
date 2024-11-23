<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Service;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Exception;
use Laminas\Uri\UriFactory;
use Magento\Customer\Model\Customer;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use Psr\Log\LoggerInterface;
use SensitiveParameter;
use Visus\CustomerTfa\Api\CustomerTfaRepositoryInterface;
use Visus\CustomerTfa\Api\Data\CustomerTfaInterfaceFactory;
use Visus\CustomerTfa\Api\Service\CustomerTfaServiceInterface;
use Visus\CustomerTfa\Model\Config;

/**
 * TFA Enrollment and Verification Service
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerTfaService implements CustomerTfaServiceInterface
{
    private const DEFAULT_SECRET_LENGTH = 16;

    /**
     * @var Config
     */
    private readonly Config $config;

    /**
     * @var CustomerTfaInterfaceFactory
     */
    private readonly CustomerTfaInterfaceFactory $customerTfaFactory;

    /**
     * @var CustomerTfaRepositoryInterface
     */
    private readonly CustomerTfaRepositoryInterface $customerTfaRepository;

    /**
     * @var EncryptorInterface
     */
    private readonly EncryptorInterface $encryptor;

    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

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
     * @param Config $config
     * @param CustomerTfaInterfaceFactory $customerTfaFactoryInterface
     * @param CustomerTfaRepositoryInterface $customerTfaRepository
     * @param EncryptorInterface $encryptor
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config $config,
        CustomerTfaInterfaceFactory $customerTfaFactoryInterface,
        CustomerTfaRepositoryInterface $customerTfaRepository,
        EncryptorInterface $encryptor,
        LoggerInterface $logger,
        SerializerInterface $serializer,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->customerTfaFactory = $customerTfaFactoryInterface;
        $this->customerTfaRepository = $customerTfaRepository;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function generateQrCode(Customer $customer): ?ResultInterface
    {
        if (empty($customer->getId())) {
            return null;
        }

        try {
            $store = $this->storeManager->getStore();
            $baseUrl = UriFactory::factory($store->getBaseUrl());
            $issuer = $baseUrl->getHost();

            $otp = TOTP::createFromSecret($this->getSecret((int)$customer->getId()));

            $otp->setLabel($customer->getEmail());
            $otp->setIssuer($issuer);

            $data = new QrCode(
                $otp->getProvisioningUri(),
                new Encoding('UTF-8'),
                new ErrorCorrectionLevelHigh(),
                200,
                0,
                null,
                new Color(0, 0, 0, 0),
                new Color(255, 255, 255, 0),
            );

            $writer = new PngWriter();
            return $writer->write($data);
        } catch (Exception $e) {
            $this->logger->critical($e);
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function generateRecoveryCodes(int $customerId): ?array
    {
        if (empty($customerId)) {
            return null;
        }

        try {
            $results = [];

            for ($i = 0; $i < 14; $i++) {
                $bytes = random_bytes(6);
                $value = str_split(bin2hex($bytes), 6);
                $results[] = implode('-', $value);
            }

            $this->saveRecoveryCodes($customerId, $results);
            return $results;
        } catch (Exception $e) {
            $this->logger->critical($e);
            return null;
        }
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings("php:S1142")
     */
    public function getRecoveryCodes(int $customerId): ?array
    {
        if (empty($customerId)) {
            return null;
        }

        try {
            $entity = $this->customerTfaRepository->getById($customerId);

            if (empty($entity->getSecret())) {
                return null;
            }

            return $this->serializer->unserialize($this->encryptor->decrypt($entity->getRecoveryCodes()));
        } catch (NoSuchEntityException) {
            return null;
        }
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings("php:S1142")
     */
    public function getSecret(int $customerId): ?string
    {
        if (empty($customerId)) {
            return null;
        }

        try {
            $entity = $this->customerTfaRepository->getById($customerId);

            $secret = $this->encryptor->decrypt($entity->getSecret());
            if (!empty($secret)) {
                return $secret;
            }

            return $this->generateSecret($customerId);
        } catch (NoSuchEntityException) {
            return $this->generateSecret($customerId);
        }
    }

    /**
     * @inheritdoc
     */
    public function isEnrolled(int $customerId): bool
    {
        return $this->customerTfaRepository->isEnrolled($customerId);
    }

    /**
     * @inheritdoc
     */
    public function reset(int $customerId): bool
    {
        return $this->customerTfaRepository->reset($customerId);
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings("php:S1142")
     */
    public function verify(int $customerId, #[SensitiveParameter] string $otp): bool
    {
        if (empty($customerId)) {
            return true;
        }

        if (empty($otp)) {
            return false;
        }

        try {
            $tfa = $this->customerTfaRepository->getById($customerId);
            $secret = $this->encryptor->decrypt($tfa->getSecret());
            if (empty($secret)) {
                return false;
            }

            $totp = TOTP::createFromSecret($secret);

            return $totp->verify($otp);
        } catch (NoSuchEntityException) {
            return true;
        }
    }

    /**
     * Generate OTP Secret
     *
     * @param int $customerId
     * @return string|null
     *
     * @codeCoverageIgnore
     */
    private function generateSecret(int $customerId): ?string
    {
        try {
            $secret = trim(Base32::encodeUpper(random_bytes(self::DEFAULT_SECRET_LENGTH)), '=');
            $this->saveSecret($customerId, $secret);

            return $secret;
        } catch (Exception $e) {
            $this->logger->critical($e);
            return null;
        }
    }

    /**
     * Save the recovery codes
     *
     * @param int $customerId
     * @param array $recoveryCodes
     * @return void
     */
    private function saveRecoveryCodes(int $customerId, #[SensitiveParameter] array $recoveryCodes): void
    {
        try {
            $model = $this->customerTfaRepository->getById($customerId);
            $model->setRecoveryCodes($this->encryptor->encrypt($this->serializer->serialize($recoveryCodes)));

            $this->customerTfaRepository->save($model);
        } catch (CouldNotSaveException|NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    /**
     * Save TFA secret used to generate OTP
     *
     * @param int $customerId
     * @param string $secret
     * @return void
     */
    private function saveSecret(int $customerId, #[SensitiveParameter] string $secret): void
    {
        try {
            try {
                $model = $this->customerTfaRepository->getById($customerId);
            } catch (NoSuchEntityException) {
                $model = $this->customerTfaFactory->create();
                $model->setCustomerId($customerId);
            }

            $model->setSecret($this->encryptor->encrypt($secret));

            $this->customerTfaRepository->save($model);
        } catch (CouldNotSaveException $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
