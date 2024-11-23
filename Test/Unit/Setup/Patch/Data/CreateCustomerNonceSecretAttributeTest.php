<?php
declare(strict_types=1);

namespace Unit\Setup\Patch\Data;

use Laminas\Db\Adapter\AdapterInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Visus\CustomerTfa\Model\Config;
use Visus\CustomerTfa\Setup\Patch\Data\CreateCustomerNonceSecretAttribute;

class CreateCustomerNonceSecretAttributeTest extends TestCase
{
    /**
     * @var CreateCustomerNonceSecretAttribute
     */
    private CreateCustomerNonceSecretAttribute $patch;

    /**
     * @var CustomerSetupFactory|MockObject
     */
    private readonly CustomerSetupFactory|MockObject $customerSetupFactoryMock;

    /**
     * @var ModuleDataSetupInterface|MockObject
     */
    private readonly ModuleDataSetupInterface|MockObject $moduleDataSetupMock;

    /**
     * @var SetFactory|MockObject
     */
    private readonly SetFactory|MockObject $setFactoryMock;

    protected function setUp(): void
    {
        $this->customerSetupFactoryMock = $this->createPartialMock(CustomerSetupFactory::class, ['create']);
        $this->moduleDataSetupMock = $this->getMockForAbstractClass(ModuleDataSetupInterface::class);
        $this->setFactoryMock = $this->createPartialMock(SetFactory::class, ['create']);

        $this->patch = new CreateCustomerNonceSecretAttribute(
            $this->customerSetupFactoryMock,
            $this->moduleDataSetupMock,
            $this->setFactoryMock
        );
    }

    public function testApply(): void
    {
        $entityTypeMock = $this->createPartialMock(Type::class, ['getDefaultAttributeSetId']);
        $entityTypeMock->expects(self::once())
            ->method('getDefaultAttributeSetId')
            ->willReturn(1);

        $eavConfigMock = $this->createPartialMock(\Magento\Eav\Model\Config::class, ['getEntityType']);
        $eavConfigMock->expects(self::once())
            ->method('getEntityType')
            ->willReturn($entityTypeMock);

        $customerSetupMock = $this->createPartialMock(
            CustomerSetup::class,
            [
                'addAttribute',
                'addAttributeToSet',
                'getAttributeId',
                'getEavConfig'
            ]
        );

        $customerSetupMock->expects(self::once())
            ->method('getEavConfig')
            ->willReturn($eavConfigMock);

        $customerSetupMock->expects(self::exactly(2))
            ->method('getAttributeId')
            ->with(Customer::ENTITY, Config::CUSTOMER_NONCE_SECRET_ATTRIBUTE_CODE)
            ->willReturnOnConsecutiveCalls(null, 99);

        $connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->addMethods(['endSetup', 'getConnection', 'startSetup'])
            ->getMockForAbstractClass();

        $attributeSetMock = $this->createPartialMock(Set::class, ['getDefaultGroupId']);
        $attributeSetMock->expects(self::once())
            ->method('getDefaultGroupId')
            ->willReturn(1);

        $this->moduleDataSetupMock->expects(self::exactly(2))
            ->method('getConnection')
            ->willReturn($connectionMock);

        $connectionMock->expects(self::once())
            ->method('startSetup')
            ->willReturnSelf();

        $this->customerSetupFactoryMock->expects(self::once())
            ->method('create')
            ->with(['setup' => $this->moduleDataSetupMock])
            ->willReturn($customerSetupMock);

        $this->setFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($attributeSetMock);

        $customerSetupMock->expects(self::once())
            ->method('addAttribute')
            ->with(
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
            )
            ->willReturnSelf();

        $customerSetupMock->expects(self::once())
            ->method('addAttributeToSet')
            ->with(Customer::ENTITY, 1, 1, 99)
            ->willReturnSelf();

        $connectionMock->expects(self::once())
            ->method('endSetup')
            ->willReturnSelf();

        $this->patch->apply();
    }

    public function testGetAliases(): void
    {
        $this->assertIsArray($this->patch->getAliases());
        $this->assertEmpty($this->patch->getAliases());
    }

    public function testGetDependencies(): void
    {
        $this->assertIsArray($this->patch->getDependencies());
        $this->assertEmpty($this->patch->getDependencies());
    }

    public function testRevert(): void
    {
        $this->moduleDataSetupMock->expects(self::once())
            ->method('startSetup')
            ->willReturnSelf();

        $customerSetupMock = $this->createPartialMock(CustomerSetup::class, ['removeAttribute']);

        $this->customerSetupFactoryMock->expects(self::once())
            ->method('create')
            ->with(['setup' => $this->moduleDataSetupMock])
            ->willReturn($customerSetupMock);

        $customerSetupMock->expects(self::once())
            ->method('removeAttribute')
            ->with(Customer::ENTITY, Config::CUSTOMER_NONCE_SECRET_ATTRIBUTE_CODE)
            ->willReturnSelf();

        $this->moduleDataSetupMock->expects(self::once())
            ->method('endSetup')
            ->willReturnSelf();

        $this->patch->revert();
    }
}
