<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Model\ResourceModel;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Visus\CustomerTfa\Api\Data\CustomerTfaChallengeInterface;

class CustomerTfaChallenge extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init('visus_customer_tfa_challenge', CustomerTfaChallengeInterface::CUSTOMER_ID);
        $this->_isPkAutoIncrement = false;
    }

    /**
     * @inheritDoc
     */
    protected function _beforeSave(AbstractModel $object): AbstractDb
    {
        $date = new DateTime(timezone: new DateTimeZone('UTC'));
        $date->add(new DateInterval('PT1H'));

        $dateTime = $date->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);

        /** @var \Visus\CustomerTfa\Model\CustomerTfaChallenge $object */
        $object->setExpiresAt($dateTime);

        return parent::_beforeSave($object);
    }

    /**
     * Delete record by ID
     *
     * @param int $customerId
     * @return bool
     */
    public function deleteById(int $customerId): bool
    {
        if (empty($customerId)) {
            return true;
        }

        $table = $this->getTable('visus_customer_tfa_challenge');
        $connection = $this->transactionManager->start($this->getConnection());

        try {
            // phpcs:disable Generic.Files.LineLength.TooLong
            $result = $connection->delete(
                $table,
                [$connection->quoteIdentifier($table . '.' . CustomerTfaChallengeInterface::CUSTOMER_ID) . ' = ?' => $customerId]
            ) > 0;
            // phpcs:enable Generic.Files.LineLength.TooLong

            $this->transactionManager->commit();
            return $result;
        } catch (Exception) {
            $this->transactionManager->rollBack();
            return false;
        }
    }
}
