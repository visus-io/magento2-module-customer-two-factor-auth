<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Model\ResourceModel\CustomerTfaChallenge;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Visus\CustomerTfa\Api\Data\CustomerTfaChallengeInterface;
use Visus\CustomerTfa\Model\CustomerTfaChallenge;

class Collection extends AbstractCollection
{
    /**
     * @var string
     * @SuppressWarnings("php:S116")
     */
    protected $_idFieldName = CustomerTfaChallengeInterface::CUSTOMER_ID;

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(CustomerTfaChallenge::class, \Visus\CustomerTfa\Model\ResourceModel\CustomerTfaChallenge::class);
    }
}
