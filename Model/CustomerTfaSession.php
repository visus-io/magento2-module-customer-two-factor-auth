<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Model;

use Magento\Framework\Session\SessionManager;
use Visus\CustomerTfa\Api\CustomerTfaSessionInterface;

class CustomerTfaSession extends SessionManager implements CustomerTfaSessionInterface
{
    /**
     * @inheritdoc
     */
    public function grantAccess(): void
    {
        $this->storage->setData(CustomerTfaSessionInterface::KEY_NAME, true);
    }

    /**
     * @inheritdoc
     */
    public function isGranted(): bool
    {
        return (bool)$this->storage->getData(CustomerTfaSessionInterface::KEY_NAME);
    }

    /**
     * @inheritDoc
     */
    public function revokeAccess(): void
    {
        $this->storage->setData(CustomerTfaSessionInterface::KEY_NAME, false);
    }
}
