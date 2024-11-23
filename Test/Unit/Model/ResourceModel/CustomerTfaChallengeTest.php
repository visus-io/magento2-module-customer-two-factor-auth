<?php
declare(strict_types=1);

namespace Unit\Model\ResourceModel;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Model\ResourceModel\Db\TransactionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Visus\CustomerTfa\Api\Data\CustomerTfaChallengeInterface;
use Visus\CustomerTfa\Model\ResourceModel\CustomerTfaChallenge;

class CustomerTfaChallengeTest extends TestCase
{
    private const TEST_CUSTOMER_ID = 11;

    /**
     * @var CustomerTfaChallenge
     */
    private CustomerTfaChallenge $resource;

    /**
     * @var AdapterInterface|MockObject
     */
    private AdapterInterface|MockObject $connectionMock;

    /**
     * @var ResourceConnection|MockObject
     */
    private ResourceConnection|MockObject $resourceConnectionMock;

    /**
     * @var TransactionManager|MockObject
     */
    private TransactionManager|MockObject $transactionManagerMock;

    protected function setUp(): void
    {
        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceConnectionMock = $this->createPartialMock(
            ResourceConnection::class,
            [
                'getConnection',
                'getTableName'
            ]
        );

        $this->connectionMock = $this->createMock(Mysql::class);
        $this->transactionManagerMock = $this->createMock(TransactionManager::class);

        $contextMock->expects(self::once())
            ->method('getResources')
            ->willReturn($this->resourceConnectionMock);

        $contextMock->expects(self::once())
            ->method('getTransactionManager')
            ->willReturn($this->transactionManagerMock);

        $this->resource = new CustomerTfaChallenge(
            $contextMock,
        );
    }

    public function testDeleteById(): void
    {
        $this->transactionManagerMock->expects(self::once())
            ->method('start')
            ->willReturn($this->connectionMock);

        $this->resourceConnectionMock->expects(self::once())
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->resourceConnectionMock->expects(self::once())
            ->method('getTableName')
            ->with('visus_customer_tfa_challenge')
            ->willReturn('visus_customer_tfa_challenge');

        $this->connectionMock->expects(self::once())
            ->method('quoteIdentifier')
            ->with('visus_customer_tfa_challenge.' . CustomerTfaChallengeInterface::CUSTOMER_ID)
            ->willReturn('`visus_customer_tfa_challenge`.`' . CustomerTfaChallengeInterface::CUSTOMER_ID . '`');

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->connectionMock->expects(self::once())
            ->method('delete')
            ->with(
                'visus_customer_tfa_challenge',
                ['`visus_customer_tfa_challenge`.`' . CustomerTfaChallengeInterface::CUSTOMER_ID . '` = ?' => self::TEST_CUSTOMER_ID]
            )
            ->willReturn(1);
        // phpcs:enable Generic.Files.LineLength.TooLong

        $this->transactionManagerMock->expects(self::once())
            ->method('commit');

        $this->assertTrue($this->resource->deleteById(self::TEST_CUSTOMER_ID));
    }

    public function testDeleteByIdException(): void
    {
        $this->transactionManagerMock->expects(self::once())
            ->method('start')
            ->willReturn($this->connectionMock);

        $this->resourceConnectionMock->expects(self::once())
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->resourceConnectionMock->expects(self::once())
            ->method('getTableName')
            ->with('visus_customer_tfa_challenge')
            ->willReturn('visus_customer_tfa_challenge');

        $this->connectionMock->expects(self::once())
            ->method('quoteIdentifier')
            ->with('visus_customer_tfa_challenge.' . CustomerTfaChallengeInterface::CUSTOMER_ID)
            ->willReturn('`visus_customer_tfa_challenge`.`' . CustomerTfaChallengeInterface::CUSTOMER_ID . '`');

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->connectionMock->expects(self::once())
            ->method('delete')
            ->with(
                'visus_customer_tfa_challenge',
                ['`visus_customer_tfa_challenge`.`' . CustomerTfaChallengeInterface::CUSTOMER_ID . '` = ?' => self::TEST_CUSTOMER_ID]
            )
            ->willThrowException(new Exception());
        // phpcs:enable Generic.Files.LineLength.TooLong

        $this->transactionManagerMock->expects(self::once())
            ->method('rollback');

        $this->assertFalse($this->resource->deleteById(self::TEST_CUSTOMER_ID));
    }

    public function testDeleteByIdEmptyCustomerId(): void
    {
        $this->assertTrue($this->resource->deleteById(0));
    }

    public function testBeforeSave(): void
    {
        $modelMock = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->addMethods(['setExpiresAt'])
            ->getMock();

        $modelMock->expects(self::once())
            ->method('setExpiresAt')
            ->with($this->isType('string'))
            ->willReturnSelf();

        $this->resource->beforeSave($modelMock);
    }
}
