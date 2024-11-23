<?php
declare(strict_types=1);

namespace Unit\Model;

use Exception;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Visus\CustomerTfa\Api\Data\CustomerTfaInterfaceFactory;
use Visus\CustomerTfa\Api\Data\CustomerTfaSearchResultInterfaceFactory;
use Visus\CustomerTfa\Model\CustomerTfa;
use Visus\CustomerTfa\Model\CustomerTfaRepository;
use Visus\CustomerTfa\Model\ResourceModel\CustomerTfa as CustomerTfaResource;
use Visus\CustomerTfa\Model\ResourceModel\CustomerTfa\CollectionFactory as CustomerTfaCollectionFactory;

class CustomerTfaRepositoryTest extends TestCase
{
    private const TEST_CUSTOMER_ID = 1;

    /**
     * @var CustomerTfaRepository
     */
    private CustomerTfaRepository $repository;

    /**
     * @var CustomerTfaCollectionFactory|MockObject
     */
    private CustomerTfaCollectionFactory|MockObject $collectionFactoryMock;

    /**
     * @var CollectionProcessorInterface|MockObject
     */
    private CollectionProcessorInterface|MockObject $collectionProcessorMock;

    /**
     * @var CustomerTfaInterfaceFactory|MockObject
     */
    private CustomerTfaInterfaceFactory|MockObject $factoryMock;

    /**
     * @var CustomerTfaSearchResultInterfaceFactory|MockObject
     */
    private CustomerTfaSearchResultInterfaceFactory|MockObject $searchResultFactoryMock;

    /**
     * @var CustomerTfaResource|MockObject
     */
    private CustomerTfaResource|MockObject $resourceMock;

    protected function setUp(): void
    {
        $this->collectionFactoryMock = $this->createPartialMock(
            CustomerTfaCollectionFactory::class,
            ['create']
        );

        $this->collectionProcessorMock = $this->getMockForAbstractClass(CollectionProcessorInterface::class);

        $this->factoryMock = $this->createPartialMock(CustomerTfaInterfaceFactory::class, ['create']);

        $this->searchResultFactoryMock = $this->createPartialMock(
            CustomerTfaSearchResultInterfaceFactory::class,
            ['create']
        );

        $this->resourceMock = $this->createPartialMock(
            CustomerTfaResource::class,
            [
                'delete',
                'deleteById',
                'isEnrolled',
                'load',
                'save'
            ]
        );

        $this->repository = new CustomerTfaRepository(
            $this->collectionFactoryMock,
            $this->collectionProcessorMock,
            $this->factoryMock,
            $this->resourceMock,
            $this->searchResultFactoryMock
        );
    }

    public function testDelete(): void
    {
        $entityMock = $this->createPartialMock(CustomerTfa::class, ['getId']);

        $this->resourceMock->expects(self::once())
            ->method('delete')
            ->with($entityMock)
            ->willReturnSelf();

        $this->assertTrue($this->repository->delete($entityMock));
    }

    public function testDeleteOnException(): void
    {
        $entityMock = $this->createPartialMock(CustomerTfa::class, ['getId']);

        $this->resourceMock->expects(self::once())
            ->method('delete')
            ->with($entityMock)
            ->willThrowException(new Exception());

        $this->expectException(CouldNotDeleteException::class);
        $this->assertFalse($this->repository->delete($entityMock));
    }

    public function testDeleteById(): void
    {
        $this->resourceMock->expects(self::once())
            ->method('deleteById')
            ->with(1)
            ->willReturn(true);

        $this->repository->deleteById(1);
    }

    public function testGetById(): void
    {
        $entityMock = $this->createPartialMock(CustomerTfa::class, ['getCustomerId']);
        $entityMock->expects(self::once())
            ->method('getCustomerId')
            ->willReturn(self::TEST_CUSTOMER_ID);

        $this->factoryMock->expects(self::once())
            ->method('create')
            ->willReturn($entityMock);

        $this->resourceMock->expects(self::once())
            ->method('load')
            ->with($entityMock, self::TEST_CUSTOMER_ID)
            ->willReturnSelf();

        $this->assertSame($entityMock, $this->repository->getById(self::TEST_CUSTOMER_ID));
    }

    public function testGetByIdOnException(): void
    {
        $entityMock = $this->createPartialMock(CustomerTfa::class, ['getCustomerId']);

        $this->factoryMock->expects(self::once())
            ->method('create')
            ->willReturn($entityMock);

        $this->resourceMock->expects(self::once())
            ->method('load')
            ->with($entityMock, self::TEST_CUSTOMER_ID)
            ->willReturn(null);

        $this->expectException(NoSuchEntityException::class);
        $this->repository->getById(self::TEST_CUSTOMER_ID);
    }

    public function testIsEnrolled(): void
    {
        $this->resourceMock->expects(self::once())
            ->method('isEnrolled')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn(true);

        $this->assertTrue($this->repository->isEnrolled(self::TEST_CUSTOMER_ID));
    }

    public function testIsEnrolledOnEmpty(): void
    {
        $this->assertFalse($this->repository->isEnrolled(0));
    }

    public function testReset(): void
    {
        $this->resourceMock->expects(self::once())
            ->method('deleteById')
            ->with(self::TEST_CUSTOMER_ID)
            ->willReturn(true);

        $this->assertTrue($this->repository->reset(self::TEST_CUSTOMER_ID));
    }

    public function testSave(): void
    {
        $entityMock = $this->createPartialMock(CustomerTfa::class, ['getCustomerId']);

        $this->resourceMock->expects(self::once())
            ->method('save')
            ->with($entityMock)
            ->willReturnSelf();

        $this->assertSame($entityMock, $this->repository->save($entityMock));
    }

    public function testSaveOnException(): void
    {
        $entityMock = $this->createPartialMock(CustomerTfa::class, ['getCustomerId']);

        $this->resourceMock->expects(self::once())
            ->method('save')
            ->with($entityMock)
            ->willThrowException(new Exception());

        $this->expectException(CouldNotSaveException::class);
        $this->assertSame($entityMock, $this->repository->save($entityMock));
    }
}
