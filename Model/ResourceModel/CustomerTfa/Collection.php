<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Model\ResourceModel\CustomerTfa;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Visus\CustomerTfa\Api\Data\CustomerTfaInterface;
use Visus\CustomerTfa\Model\CustomerTfa;

class Collection extends AbstractCollection
{
    /**
     * @var string
     * @SuppressWarnings("php:S116")
     */
    protected $_idFieldName = CustomerTfaInterface::CUSTOMER_ID;

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(CustomerTfa::class, \Visus\CustomerTfa\Model\ResourceModel\CustomerTfa::class);
    }
}
