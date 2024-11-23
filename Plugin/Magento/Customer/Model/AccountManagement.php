<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Plugin\Magento\Customer\Model;

use Exception;
use Magento\Customer\Api\Data\CustomerExtensionFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Math\Random;
use Psr\Log\LoggerInterface;

/**
 * @since 1.0.0
 */
class AccountManagement
{
    /**
     * @var CustomerExtensionFactory
     */
    private readonly CustomerExtensionFactory $customerExtensionFactory;

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
     * Constructor
     *
     * @param CustomerExtensionFactory $customerExtensionFactory
     * @param EncryptorInterface $encryptor
     * @param LoggerInterface $logger
     * @param Random $mathRandom
     */
    public function __construct(
        CustomerExtensionFactory $customerExtensionFactory,
        EncryptorInterface $encryptor,
        LoggerInterface $logger,
        Random $mathRandom
    ) {
        $this->customerExtensionFactory = $customerExtensionFactory;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        $this->mathRandom = $mathRandom;
    }

    /**
     * Generates a nonce secret before account is created in the system.
     *
     * @param \Magento\Customer\Model\AccountManagement $subject
     * @param CustomerInterface $customer
     * @param string|null $password
     * @param string|null $redirectUrl
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameters)
     * @SuppressWarnings("php:S1172")
     */
    public function beforeCreateAccount(
        \Magento\Customer\Model\AccountManagement $subject,
        CustomerInterface $customer,
        ?string $password = null,
        ?string $redirectUrl = ''
    ): array {
        try {
            $extensionAttributes = $customer->getExtensionAttributes() ?? $this->customerExtensionFactory->create();
            $extensionAttributes->setVisusNonceSecret($this->generateSecretKey());

            $customer->setExtensionAttributes($extensionAttributes);
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return [$customer, $password, $redirectUrl];
    }

    /**
     * Generates a secret key used for nonce generation
     *
     * @return string
     * @throws Exception
     */
    private function generateSecretKey(): string
    {
        return $this->encryptor->encrypt($this->mathRandom->getRandomBytes(24));
    }
}
