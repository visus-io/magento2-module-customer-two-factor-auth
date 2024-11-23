<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Model\ResourceModel;

use Exception;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Visus\CustomerTfa\Api\Data\CustomerTfaInterface;

class CustomerTfa extends AbstractDb
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init('visus_customer_tfa', CustomerTfaInterface::CUSTOMER_ID);
        $this->_isPkAutoIncrement = false;
    }

    /**
     * Deletes record by ID
     *
     * @param int $customerId
     * @return bool
     */
    public function deleteById(int $customerId): bool
    {
        if (empty($customerId)) {
            return true;
        }

        $table = $this->getTable('visus_customer_tfa');
        $connection = $this->transactionManager->start($this->getConnection());

        try {
            $result = $connection->delete(
                $table,
                [$connection->quoteIdentifier($table . '.' . CustomerTfaInterface::CUSTOMER_ID) . ' = ?' => $customerId]
            ) > 0;

            $this->transactionManager->commit();
            return $result;
        } catch (Exception) {
            $this->transactionManager->rollBack();
            return false;
        }
    }

    /**
     * Checks if TFA is enabled for given Customer
     *
     * @param int $customerId
     * @return bool
     */
    public function isEnrolled(int $customerId): bool
    {
        if (empty($customerId)) {
            return false;
        }

        $table = $this->getTable('visus_customer_tfa');
        $connection = $this->getConnection();

        // phpcs:disable Generic.Files.LineLength.TooLong
        $query = $this->getConnection()->select()
            ->from($table, [CustomerTfaInterface::CUSTOMER_ID])
            ->where($connection->quoteIdentifier($table . '.' . CustomerTfaInterface::CUSTOMER_ID) . ' = ?', $customerId)
            ->where('IFNULL(NULLIF(TRIM(' . $connection->quoteIdentifier($table . '.' . CustomerTfaInterface::SECRET) . '), \'\'), NULL) IS NOT NULL')
            ->where('IFNULL(NULLIF(TRIM(' . $connection->quoteIdentifier($table . '.' . CustomerTfaInterface::RECOVERY_CODES) . '), \'\'), NULL) IS NOT NULL');
        // phpcs:enable Generic.Files.LineLength.TooLong

        return $connection->fetchRow($query) > 0;
    }
}
