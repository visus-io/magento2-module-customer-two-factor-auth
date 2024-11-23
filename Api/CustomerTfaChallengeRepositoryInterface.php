<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Api;

use Exception;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Visus\CustomerTfa\Api\Data\CustomerTfaChallengeInterface;
use Visus\CustomerTfa\Api\Data\CustomerTfaChallengeSearchResultInterface;

/**
 * Customer TFA Challenge CRUD Interface
 *
 * @api
 * @since 1.0.0
 */
interface CustomerTfaChallengeRepositoryInterface
{
    /**
     * Delete Customer TFA Challenge record.
     *
     * @param CustomerTfaChallengeInterface $record
     * @return bool
     * @throws CouldNotDeleteException Unable to delete Challenge
     */
    public function delete(CustomerTfaChallengeInterface $record): bool;

    /**
     * Delete Customer TFA Challenge by ID
     *
     * @param int $customerId
     * @return bool
     */
    public function deleteById(int $customerId): bool;

    /**
     * Get Customer TFA Challenge record by ID
     *
     * @param int $customerId
     * @return CustomerTfaChallengeInterface
     * @throws NoSuchEntityException If TFA Challenge record with the specified Customer ID does not exist
     */
    public function getById(int $customerId): CustomerTfaChallengeInterface;

    /**
     * Retrieve Customer TFA Challenge records which match a specified criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return CustomerTfaChallengeSearchResultInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria): CustomerTfaChallengeSearchResultInterface;

    /**
     * Create or update a Customer TFA Challenge record
     *
     * @param CustomerTfaChallengeInterface $record
     * @return CustomerTfaChallengeInterface
     * @throws CouldNotSaveException
     */
    public function save(CustomerTfaChallengeInterface $record): CustomerTfaChallengeInterface;
}
