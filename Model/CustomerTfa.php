<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Model;

use Magento\Framework\Model\AbstractModel;
use Visus\CustomerTfa\Api\Data\CustomerTfaInterface;

class CustomerTfa extends AbstractModel implements CustomerTfaInterface
{
    /**
     * @var array<string>|null
     */
    private ?array $allowedUrls = null;

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\CustomerTfa::class);
    }

    /**
     * @inheritdoc
     */
    public function getAllowedUrls(): array
    {
        if ($this->allowedUrls === null) {
            $this->allowedUrls = [
                'customer_account_forgotpassword',
                'customer_account_login',
                'customer_account_logout',
                'visus_tfa_authenticate_index',
                'visus_tfa_authenticate_validate',
                'visus_tfa_challenge_request',
                'visus_tfa_challenge_verify',
                'visus_tfa_index_index',
                'visus_tfa_setup_confirm',
                'visus_tfa_setup_recovery',
                'visus_tfa_setup_setup'
            ];
        }

        return $this->allowedUrls;
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(string $createdAt): static
    {
        $this->setData(self::CREATED_AT, $createdAt);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCustomerId(): ?int
    {
        return (int)$this->getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerId(int $customerId): static
    {
        $this->setData(self::CUSTOMER_ID, $customerId);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRecoveryCodes(): ?string
    {
        return $this->getData(self::RECOVERY_CODES);
    }

    /**
     * @inheritdoc
     */
    public function setRecoveryCodes(string $codes): static
    {
        $this->setData(self::RECOVERY_CODES, $codes);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSecret(): string
    {
        return $this->getData(self::SECRET);
    }

    /**
     * @inheritdoc
     */
    public function setSecret(?string $secret): static
    {
        $this->setData(self::SECRET, $secret);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(string $updatedAt): static
    {
        $this->setData(self::UPDATED_AT, $updatedAt);
        return $this;
    }
}
