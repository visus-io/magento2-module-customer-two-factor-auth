<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Model;

use Magento\Store\Model\StoreManagerInterface;

class Config
{
    public const CUSTOMER_NONCE_SECRET_ATTRIBUTE_CODE = 'visus_nonce_secret';

    public const COOKIE_NAME = '__VISUS_TFA_NONCE';

    /**
     * @var StoreManagerInterface
     */
    private readonly StoreManagerInterface $storeManager;

    /**
     * Constructor
     *
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * Return if single store mode
     *
     * @return bool
     */
    public function isSingleStoreMode(): bool
    {
        return $this->storeManager->isSingleStoreMode();
    }
}
