<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Customer TFA records search result interface for API handling
 *
 * @api
 * @since 1.0.0
 */
interface CustomerTfaSearchResultInterface extends SearchResultsInterface
{
    /**
     * Retrieves Customer TFA Records
     *
     * @return CustomerTfaInterface[]
     */
    public function getItems();

    /**
     * Sets Customer TFA Records
     *
     * @param CustomerTfaInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
