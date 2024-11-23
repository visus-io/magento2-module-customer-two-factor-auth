<?php
declare(strict_types=1);

namespace Unit\Model\ResourceModel;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Model\ResourceModel\Db\TransactionManager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Visus\CustomerTfa\Api\Data\CustomerTfaInterface;
use Visus\CustomerTfa\Model\ResourceModel\CustomerTfa;

class CustomerTfaTest extends TestCase
{
    private const TEST_CUSTOMER_ID = 11;

    /**
     * @var CustomerTfa
     */
    private CustomerTfa $resource;

    /**
     * @var AdapterInterface|MockObject
     */
    private AdapterInterface|MockObject $connectionMock;

    /**
     * @var ResourceConnection|MockObject
     */
    private ResourceConnection|MockObject $resourceConnectionMock;

    /**
     * @var Select|MockObject
     */
    private Select|MockObject $selectMock;

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
        $this->selectMock = $this->createMock(Select::class);
        $this->transactionManagerMock = $this->createMock(TransactionManager::class);

        $contextMock->expects(self::once())
            ->method('getResources')
            ->willReturn($this->resourceConnectionMock);

        $contextMock->expects(self::once())
            ->method('getTransactionManager')
            ->willReturn($this->transactionManagerMock);

        $this->resource = new CustomerTfa($contextMock);
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
            ->with('visus_customer_tfa')
            ->willReturn('visus_customer_tfa');

        $this->connectionMock->expects(self::once())
            ->method('quoteIdentifier')
            ->with('visus_customer_tfa.' . CustomerTfaInterface::CUSTOMER_ID)
            ->willReturn('`visus_customer_tfa`.`' . CustomerTfaInterface::CUSTOMER_ID . '`');

        $this->connectionMock->expects(self::once())
            ->method('delete')
            ->with(
                'visus_customer_tfa',
                ['`visus_customer_tfa`.`' . CustomerTfaInterface::CUSTOMER_ID . '` = ?' => self::TEST_CUSTOMER_ID]
            )
            ->willReturn(1);

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
            ->with('visus_customer_tfa')
            ->willReturn('visus_customer_tfa');

        $this->connectionMock->expects(self::once())
            ->method('quoteIdentifier')
            ->with('visus_customer_tfa.' . CustomerTfaInterface::CUSTOMER_ID)
            ->willReturn('`visus_customer_tfa`.`' . CustomerTfaInterface::CUSTOMER_ID . '`');

        $this->connectionMock->expects(self::once())
            ->method('delete')
            ->with(
                'visus_customer_tfa',
                ['`visus_customer_tfa`.`' . CustomerTfaInterface::CUSTOMER_ID . '` = ?' => self::TEST_CUSTOMER_ID]
            )
            ->willThrowException(new Exception());

        $this->transactionManagerMock->expects(self::once())
            ->method('rollback');

        $this->assertFalse($this->resource->deleteById(self::TEST_CUSTOMER_ID));
    }

    public function testDeleteByIdEmptyCustomerId(): void
    {
        $this->assertTrue($this->resource->deleteById(0));
    }

    public function testIsEnrolled(): void
    {
        $this->connectionMock->expects(self::once())
            ->method('select')
            ->willReturn($this->selectMock);

        $this->connectionMock->expects(self::exactly(3))
            ->method('quoteIdentifier')
            ->willReturnMap([
                [
                    'visus_customer_tfa.' . CustomerTfaInterface::CUSTOMER_ID,
                    false,
                    '`visus_customer_tfa`.`' . CustomerTfaInterface::CUSTOMER_ID . '`'
                ],
                [
                    'visus_customer_tfa.' . CustomerTfaInterface::SECRET,
                    false,
                    '`visus_customer_tfa`.`' . CustomerTfaInterface::SECRET . '`'
                ],
                [
                    'visus_customer_tfa.' . CustomerTfaInterface::RECOVERY_CODES,
                    false,
                    '`visus_customer_tfa`.`' . CustomerTfaInterface::RECOVERY_CODES . '`'
                ]
            ]);

        $this->resourceConnectionMock->expects(self::exactly(2))
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->resourceConnectionMock->expects(self::once())
            ->method('getTableName')
            ->with('visus_customer_tfa')
            ->willReturn('visus_customer_tfa');

        $this->selectMock->expects(self::once())
            ->method('from')
            ->with('visus_customer_tfa', [CustomerTfaInterface::CUSTOMER_ID], null)
            ->willReturnSelf();

        $invocations = self::exactly(3);

        $this->selectMock->expects($invocations)
            ->method('where')
            ->willReturnCallback(function ($where) use ($invocations) {
                // phpcs:disable Generic.Files.LineLength.TooLong
                $expected = [
                    ['`visus_customer_tfa`.`' . CustomerTfaInterface::CUSTOMER_ID . '` = ?'],
                    ['IFNULL(NULLIF(TRIM(`visus_customer_tfa`.`' . CustomerTfaInterface::SECRET .'`), \'\'), NULL) IS NOT NULL'],
                    ['IFNULL(NULLIF(TRIM(`visus_customer_tfa`.`' . CustomerTfaInterface::RECOVERY_CODES .'`), \'\'), NULL) IS NOT NULL']
                ];
                // phpcs:enable Generic.Files.LineLength.TooLong

                $index = $invocations->getInvocationCount();

                $this->assertSame($expected[$index - 1][0], $where);

                return $this->selectMock;
            });

        $this->connectionMock->expects(self::once())
            ->method('fetchRow')
            ->willReturn(1);

        $this->assertTrue($this->resource->isEnrolled(self::TEST_CUSTOMER_ID));
    }

    public function testIsEnrolledEmptyCustomerId(): void
    {
        $this->assertFalse($this->resource->isEnrolled(0));
    }
}
