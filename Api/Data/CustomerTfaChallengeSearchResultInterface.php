<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Customer TFA Challenge record search result interface for API handling
 *
 * @api
 * @since 1.0.0
 */
interface CustomerTfaChallengeSearchResultInterface extends SearchResultsInterface
{
    /**
     * Retrieves Customer TFA Challenge records
     *
     * @return CustomerTfaChallengeInterface[]
     */
    public function getItems();

    /**
     * Sets Customer TFA Challenge records
     *
     * @param CustomerTfaChallengeInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
