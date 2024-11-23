<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Visus\CustomerTfa\Api\Data\CustomerTfaInterface;
use Visus\CustomerTfa\Api\Data\CustomerTfaSearchResultInterface;

/**
 * Customer TFA CRUD Interface
 *
 * @api
 * @since 1.0.0
 */
interface CustomerTfaRepositoryInterface
{
    /**
     * Delete Customer TFA record.
     *
     * @param CustomerTfaInterface $tfa
     * @return bool true on success
     * @throws CouldNotDeleteException Unable to delete TFA
     */
    public function delete(CustomerTfaInterface $tfa): bool;

    /**
     * Delete Customer TFA record by ID
     *
     * @param int $customerId
     * @return bool
     */
    public function deleteById(int $customerId): bool;

    /**
     * Get Customer TFA record by ID
     *
     * @param int $customerId
     * @return CustomerTfaInterface
     * @throws NoSuchEntityException If TFA record with the specified Customer ID does not exist.
     */
    public function getById(int $customerId): CustomerTfaInterface;

    /**
     * Retrieve Customer TFA records which match a specified criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return CustomerTfaSearchResultInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria): CustomerTfaSearchResultInterface;

    /**
     * Get whether customer is enrolled in TFA or not
     *
     * @param int $customerId
     * @return bool
     */
    public function isEnrolled(int $customerId): bool;

    /**
     * Reset Customer TFA
     *
     * @param int $customerId
     * @return bool
     */
    public function reset(int $customerId): bool;

    /**
     * Create or update a Customer TFA record
     *
     * @param CustomerTfaInterface $tfa
     * @return CustomerTfaInterface
     * @throws CouldNotSaveException
     */
    public function save(CustomerTfaInterface $tfa): CustomerTfaInterface;
}
