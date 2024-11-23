<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Model;

use Exception;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Visus\CustomerTfa\Api\CustomerTfaChallengeRepositoryInterface;
use Visus\CustomerTfa\Api\Data\CustomerTfaChallengeInterface;
use Visus\CustomerTfa\Api\Data\CustomerTfaChallengeInterfaceFactory;
use Visus\CustomerTfa\Api\Data\CustomerTfaChallengeSearchResultInterface;
use Visus\CustomerTfa\Api\Data\CustomerTfaChallengeSearchResultInterfaceFactory;
use Visus\CustomerTfa\Model\ResourceModel\CustomerTfaChallenge as CustomerTfaChallengeResource;
use Visus\CustomerTfa\Model\ResourceModel\CustomerTfaChallenge\CollectionFactory as CustomerTfaChallengeCollectionFactory;

class CustomerTfaChallengeRepository implements CustomerTfaChallengeRepositoryInterface
{
    /**
     * @var CustomerTfaChallengeCollectionFactory
     */
    private readonly CustomerTfaChallengeCollectionFactory $collectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private readonly CollectionProcessorInterface $collectionProcessor;

    /**
     * @var CustomerTfaChallengeInterfaceFactory
     */
    private readonly CustomerTfaChallengeInterfaceFactory $factory;

    /**
     * @var CustomerTfaChallengeSearchResultInterfaceFactory
     */
    private readonly CustomerTfaChallengeSearchResultInterfaceFactory $searchResultFactory;

    /**
     * @var CustomerTfaChallengeResource
     */
    private readonly CustomerTfaChallengeResource $resource;

    /**
     * Constructor
     *
     * @param CustomerTfaChallengeCollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param CustomerTfaChallengeInterfaceFactory $factory
     * @param CustomerTfaChallengeResource $resource
     * @param CustomerTfaChallengeSearchResultInterfaceFactory $searchResultFactory
     */
    public function __construct(
        CustomerTfaChallengeCollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        CustomerTfaChallengeInterfaceFactory $factory,
        CustomerTfaChallengeResource $resource,
        CustomerTfaChallengeSearchResultInterfaceFactory $searchResultFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->factory = $factory;
        $this->resource = $resource;
        $this->searchResultFactory = $searchResultFactory;
    }

    /**
     * @inheritdoc
     */
    public function delete(CustomerTfaChallengeInterface $record): bool
    {
        try {
            $this->resource->delete($record);
            return true;
        } catch (Exception $e) {
            throw new CouldNotDeleteException(__($e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $customerId): bool
    {
        return $this->resource->deleteById($customerId);
    }

    /**
     * @inheritdoc
     */
    public function getById(int $customerId): CustomerTfaChallengeInterface
    {
        $model = $this->factory->create();
        $this->resource->load($model, $customerId);

        if (!$model || $model->getId() != $customerId) {
            throw NoSuchEntityException::singleField(CustomerTfaChallengeInterface::CUSTOMER_ID, $customerId);
        }

        return $model;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): CustomerTfaChallengeSearchResultInterface
    {
        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);
        $searchResults = $this->searchResultFactory->create();

        /** @var CustomerTfaChallengeInterface[] $items */
        $items = $collection->getItems();

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setSearchCriteria($searchCriteria);

        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function save(CustomerTfaChallengeInterface $record): CustomerTfaChallengeInterface
    {
        try {
            $this->resource->save($record);
        } catch (Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $record;
    }
}
