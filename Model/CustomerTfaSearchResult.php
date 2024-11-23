<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Model;

use Magento\Framework\Api\SearchResults;
use Visus\CustomerTfa\Api\Data\CustomerTfaSearchResultInterface;

class CustomerTfaSearchResult extends SearchResults implements CustomerTfaSearchResultInterface
{
}
