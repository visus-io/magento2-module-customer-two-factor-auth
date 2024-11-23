<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Service;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use SensitiveParameter;
use Throwable;
use Visus\CustomerTfa\Api\CustomerTfaChallengeRepositoryInterface;
use Visus\CustomerTfa\Api\Data\CustomerTfaChallengeInterfaceFactory;
use Visus\CustomerTfa\Api\Service\CustomerTfaChallengeServiceInterface;

/**
 * Challenge Generation Service
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerTfaChallengeService implements CustomerTfaChallengeServiceInterface
{

    /**
     * @var CustomerTfaChallengeInterfaceFactory
     */
    private readonly CustomerTfaChallengeInterfaceFactory $factory;

    /**
     * @var CustomerTfaChallengeRepositoryInterface
     */
    private readonly CustomerTfaChallengeRepositoryInterface $repository;

    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * @var EncryptorInterface
     */
    private readonly EncryptorInterface $encryptor;

    /**
     * @var TransportBuilder
     */
    private readonly TransportBuilder $transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    private readonly StoreManagerInterface $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private readonly ScopeConfigInterface $scopeConfig;

    /**
     * Constructor
     *
     * @param CustomerTfaChallengeInterfaceFactory $factory
     * @param CustomerTfaChallengeRepositoryInterface $repository
     * @param LoggerInterface $logger
     * @param EncryptorInterface $encryptor
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        CustomerTfaChallengeInterfaceFactory $factory,
        CustomerTfaChallengeRepositoryInterface $repository,
        LoggerInterface $logger,
        EncryptorInterface $encryptor,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->factory = $factory;
        $this->repository = $repository;
        $this->logger = $logger;
        $this->encryptor = $encryptor;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function sendEmail(Customer $customer): bool
    {
        $challenge = $this->generate((int)$customer->getId());
        if (empty($challenge)) {
            return false;
        }

        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('visus_tfa_challenge_email_template')
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => $this->storeManager->getStore()->getId()
                ])
                ->setTemplateVars([
                    'customer' => [
                        'name' => $customer->getName()
                    ],
                    'support_email' => $this->scopeConfig->getValue(
                        'trans_email/ident_support/email',
                        ScopeInterface::SCOPE_STORE
                    ),
                    'store' => $this->storeManager->getStore(),
                    'challenge' => $challenge
                ])
                ->setFromByScope('support')
                ->addTo($customer->getEmail(), $customer->getName())
                ->getTransport();

            $transport->sendMessage();
            return true;
        } catch (Throwable $e) {
            $this->logger->critical($e);
            return false;
        }
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings("php:S1142")
     */
    public function verify(int $customerId, #[SensitiveParameter] ?string $code): bool
    {
        if (empty($customerId) || empty($code)) {
            return false;
        }

        try {
            $entity = $this->repository->getById($customerId);
            $this->repository->deleteById($customerId);

            if ($this->isExpired($entity->getExpiresAt())) {
                return false;
            }

            $challenge = $this->encryptor->decrypt($entity->getChallenge());

            return !empty($challenge) && hash_equals($challenge, $code);
        } catch (Exception $e) {
            $this->logger->critical($e);
            return false;
        }
    }

    /**
     * Generates and saves a challenge code for the customer.
     *
     * @param int $customerId
     * @return string|null
     */
    private function generate(int $customerId): ?string
    {
        if (empty($customerId)) {
            return null;
        }

        try {
            $challenge = sprintf("%06d", random_int(1000, 999999));
            if (!empty($challenge)) {
                $model = $this->factory->create();
                $model->setCustomerId($customerId);
                $model->setChallenge($this->encryptor->encrypt($challenge));

                $this->repository->save($model);

                return $challenge;
            }
        } catch (Exception $e) {
            $this->logger->critical($e);
        }

        return null;
    }

    /**
     * Checks if timestamp is expired
     *
     * @param string $expiresAt
     * @return bool
     */
    private function isExpired(string $expiresAt): bool
    {
        try {
            $timestamp = new DateTime($expiresAt, new DateTimeZone('UTC'));

            $dateTime = new DateTime(timezone: new DateTimeZone('UTC'));
            $dateTime->sub(new DateInterval('PT1H'));

            return $dateTime > $timestamp;
        } catch (Exception $e) {
            $this->logger->critical($e);
            return false;
        }
    }
}
