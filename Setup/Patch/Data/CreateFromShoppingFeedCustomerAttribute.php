<?php

namespace ShoppingFeed\Manager\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\AttributeFactory as CustomerAttributeResourceFactory;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use ShoppingFeed\Manager\Model\Sales\Order\Customer\Importer as CustomerImporter;

class CreateFromShoppingFeedCustomerAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @var CustomerAttributeResourceFactory
     */
    private $customerAttributeResourceFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        CustomerAttributeResourceFactory $customerAttributeResourceFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->customerAttributeResourceFactory = $customerAttributeResourceFactory;
    }

    public function apply()
    {
        $customerSetup = $this->customerSetupFactory->create([ 'setup' => $this->moduleDataSetup ]);

        if ($customerSetup->getAttribute(Customer::ENTITY, CustomerImporter::CUSTOMER_FROM_SHOPPING_ATTRIBUTE_CODE)) {
            return;
        }

        $customerSetup->addAttribute(
            Customer::ENTITY,
            CustomerImporter::CUSTOMER_FROM_SHOPPING_ATTRIBUTE_CODE,
            [
                'type' => 'int',
                'label' => 'From Shopping Feed',
                'input' => 'select',
                'required' => false,
                'visible' => true,
                'system' => false,
                'user_defined' => true,
                'default' => false,
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'position' => 1000,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
            ]
        );

        $attribute = $customerSetup->getEavConfig()
            ->getAttribute(
                Customer::ENTITY,
                CustomerImporter::CUSTOMER_FROM_SHOPPING_ATTRIBUTE_CODE
            );

        $attributeSetId = $customerSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $attributeGroupId = $customerSetup->getDefaultAttributeGroupId(Customer::ENTITY, $attributeSetId);

        $attribute->addData(
            [
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => [ 'adminhtml_customer' ],
            ]
        );

        $customerAttributeResource = $this->customerAttributeResourceFactory->create();

        $customerAttributeResource->save($attribute);
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
