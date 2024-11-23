<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Visus\CustomerTfa\Model\Config;

/**
 * Create the 'visus_nonce_secret' Customer Attribute
 *
 * @since 1.0.0
 */
class CreateCustomerNonceSecretAttribute implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var CustomerSetupFactory
     */
    private readonly CustomerSetupFactory $customerSetupFactory;

    /**
     * @var ModuleDataSetupInterface
     */
    private readonly ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var SetFactory
     */
    private readonly SetFactory $setFactory;

    /**
     * Constructor
     *
     * @param CustomerSetupFactory $customerSetupFactory
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param SetFactory $setFactory
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        ModuleDataSetupInterface $moduleDataSetup,
        SetFactory $setFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->setFactory = $setFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        $attributeSet = $this->setFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $attributeId = $customerSetup->getAttributeId(
            Customer::ENTITY,
            Config::CUSTOMER_NONCE_SECRET_ATTRIBUTE_CODE
        );

        if (empty($attributeId)) {
            $customerSetup->addAttribute(
                Customer::ENTITY,
                Config::CUSTOMER_NONCE_SECRET_ATTRIBUTE_CODE,
                [
                    'label' => '',
                    'input' => 'text',
                    'type' => 'text',
                    'source' => '',
                    'required' => false,
                    'position' => 1000,
                    'visible' => false,
                    'system' => true,
                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'is_searchable_in_grid' => false,
                    'backend' => ''
                ]
            );
        }

        $attributeId = $customerSetup->getAttributeId(
            Customer::ENTITY,
            Config::CUSTOMER_NONCE_SECRET_ATTRIBUTE_CODE
        );

        if (!empty($attributeId)) {
            $customerSetup->addAttributeToSet(
                Customer::ENTITY,
                $attributeSetId,
                $attributeGroupId,
                $attributeId
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function revert(): void
    {
        $this->moduleDataSetup->startSetup();

        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $customerSetup->removeAttribute(Customer::ENTITY, Config::CUSTOMER_NONCE_SECRET_ATTRIBUTE_CODE);

        $this->moduleDataSetup->endSetup();
    }
}
