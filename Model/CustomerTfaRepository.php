<?php
declare(strict_types=1);
namespace Visus\CustomerTfa\Model;

use Exception;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Visus\CustomerTfa\Api\CustomerTfaRepositoryInterface;
use Visus\CustomerTfa\Api\Data\CustomerTfaInterface;
use Visus\CustomerTfa\Api\Data\CustomerTfaInterfaceFactory;
use Visus\CustomerTfa\Api\Data\CustomerTfaSearchResultInterface;
use Visus\CustomerTfa\Api\Data\CustomerTfaSearchResultInterfaceFactory;
use Visus\CustomerTfa\Model\ResourceModel\CustomerTfa as CustomerTfaResource;
use Visus\CustomerTfa\Model\ResourceModel\CustomerTfa\CollectionFactory as CustomerTfaCollectionFactory;

class CustomerTfaRepository implements CustomerTfaRepositoryInterface
{
    /**
     * @var CustomerTfaCollectionFactory
     */
    private readonly CustomerTfaCollectionFactory $collectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private readonly CollectionProcessorInterface $collectionProcessor;

    /**
     * @var CustomerTfaInterfaceFactory
     */
    private readonly CustomerTfaInterfaceFactory $factory;

    /**
     * @var CustomerTfaSearchResultInterfaceFactory
     */
    private readonly CustomerTfaSearchResultInterfaceFactory $searchResultFactory;

    /**
     * @var CustomerTfaResource
     */
    private readonly CustomerTfaResource $resource;

    /**
     * Constructor
     *
     * @param CustomerTfaCollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param CustomerTfaInterfaceFactory $factory
     * @param CustomerTfaResource $resource
     * @param CustomerTfaSearchResultInterfaceFactory $searchResultFactory
     */
    public function __construct(
        CustomerTfaCollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        CustomerTfaInterfaceFactory $factory,
        CustomerTfaResource $resource,
        CustomerTfaSearchResultInterfaceFactory $searchResultFactory
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
    public function delete(CustomerTfaInterface $tfa): bool
    {
        try {
            $this->resource->delete($tfa);
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
    public function getById(int $customerId): CustomerTfaInterface
    {
        $model = $this->factory->create();
        $this->resource->load($model, $customerId, CustomerTfaInterface::CUSTOMER_ID);

        if (!$model || $model->getCustomerId() != $customerId) {
            throw NoSuchEntityException::singleField(CustomerTfaInterface::CUSTOMER_ID, $customerId);
        }

        return $model;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): CustomerTfaSearchResultInterface
    {
        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);
        $searchResults = $this->searchResultFactory->create();

        /** @var CustomerTfaInterface[] $items */
        $items = $collection->getItems();

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setSearchCriteria($searchCriteria);

        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function isEnrolled(int $customerId): bool
    {
        if (empty($customerId)) {
            return false;
        }

        return $this->resource->isEnrolled($customerId);
    }

    /**
     * @inheritdoc
     */
    public function reset(int $customerId): bool
    {
        return $this->resource->deleteById($customerId);
    }

    /**
     * @inheritdoc
     */
    public function save(CustomerTfaInterface $tfa): CustomerTfaInterface
    {
        try {
            $this->resource->save($tfa);
        } catch (Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $tfa;
    }
}
