<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Model;

use Magento\Framework\Model\AbstractModel;
use Visus\CustomerTfa\Api\Data\CustomerTfaChallengeInterface;

class CustomerTfaChallenge extends AbstractModel implements CustomerTfaChallengeInterface
{
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
    public function getChallenge(): ?string
    {
        return $this->getData(CustomerTfaChallengeInterface::CHALLENGE);
    }

    /**
     * @inheritdoc
     */
    public function setChallenge(string $challenge): static
    {
        $this->setData(CustomerTfaChallengeInterface::CHALLENGE, $challenge);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCustomerId(): ?int
    {
        return $this->getData(CustomerTfaChallengeInterface::CUSTOMER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerId(int $customerId): static
    {
        $this->setData(CustomerTfaChallengeInterface::CUSTOMER_ID, $customerId);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getExpiresAt(): ?string
    {
        return $this->getData(CustomerTfaChallengeInterface::EXPIRES_AT);
    }

    /**
     * @inheritdoc
     */
    public function setExpiresAt(string $expiresAt): static
    {
        $this->setData(CustomerTfaChallengeInterface::EXPIRES_AT, $expiresAt);
        return $this;
    }

}
