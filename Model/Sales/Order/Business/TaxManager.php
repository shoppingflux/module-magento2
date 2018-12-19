<?php

namespace ShoppingFeed\Manager\Model\Sales\Order\Business;

use Magento\Customer\Api\GroupRepositoryInterface as CustomerGroupRepository;
use Magento\Customer\Api\Data\GroupInterface as CustomerGroupInterface;
use Magento\Customer\Api\Data\GroupInterfaceFactory as CustomerGroupFactory;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Tax\Api\Data\TaxClassInterface;
use Magento\Tax\Api\Data\TaxClassInterfaceFactory as TaxClassFactory;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\Tax\Model\ResourceModel\TaxClass\CollectionFactory as TaxClassCollectionFactory;

class TaxManager
{
    const PRODUCT_TAX_CLASS_NAME = '_sfm_business_product_tax_class_';
    const CUSTOMER_TAX_CLASS_NAME = '_sfm_business_customer_tax_class_';
    const CUSTOMER_GROUP_CODE = '_sfm_business_customer_group_';

    /**
     * @var TaxClassFactory
     */
    private $taxClassFactory;

    /**
     * @var TaxClassCollectionFactory
     */
    private $taxClassCollectionFactory;

    /**
     * @var TaxClassRepositoryInterface
     */
    private $taxClassRepository;

    /**
     * @var CustomerGroupFactory
     */
    private $customerGroupFactory;

    /**
     * @var CustomerGroupCollectionFactory
     */
    private $customerGroupCollectionFactory;

    /**
     * @var CustomerGroupRepository
     */
    private $customerGroupRepository;

    /**
     * @var TaxClassInterface|null
     */
    private $productTaxClass = null;

    /**
     * @var TaxClassInterface|null
     */
    private $customerTaxClass = null;

    /**
     * @var CustomerGroupInterface|null
     */
    private $customerGroup = null;

    /**
     * @param TaxClassFactory $taxClassFactory
     * @param TaxClassCollectionFactory $taxClassCollectionFactory
     * @param TaxClassRepositoryInterface $taxClassRepository
     * @param CustomerGroupFactory $customerGroupFactory
     * @param CustomerGroupCollectionFactory $customerGroupCollectionFactory
     * @param CustomerGroupRepository $customerGroupRepository
     */
    public function __construct(
        TaxClassFactory $taxClassFactory,
        TaxClassCollectionFactory $taxClassCollectionFactory,
        TaxClassRepositoryInterface $taxClassRepository,
        CustomerGroupFactory $customerGroupFactory,
        CustomerGroupCollectionFactory $customerGroupCollectionFactory,
        CustomerGroupRepository $customerGroupRepository
    ) {
        $this->taxClassFactory = $taxClassFactory;
        $this->taxClassCollectionFactory = $taxClassCollectionFactory;
        $this->taxClassRepository = $taxClassRepository;
        $this->customerGroupFactory = $customerGroupFactory;
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
        $this->customerGroupRepository = $customerGroupRepository;
    }

    /**
     * @param string $classType
     * @param string $className
     * @return TaxClassInterface
     * @throws InputException
     * @throws LocalizedException
     */
    private function getTaxClass($classType, $className)
    {
        $taxClassCollection = $this->taxClassCollectionFactory->create();
        $taxClassCollection->setClassTypeFilter($classType);
        $taxClassCollection->addFieldToFilter('class_name', $className);

        /** @var TaxClassInterface $taxClass */
        $taxClass = $taxClassCollection->getFirstItem();

        if (!$taxClass || !$taxClass->getClassId()) {
            $taxClass = $this->taxClassFactory->create();
            $taxClass->setClassType($classType);
            $taxClass->setClassName($className);
            $this->taxClassRepository->save($taxClass);
        }

        return $taxClass;
    }

    /**
     * @return TaxClassInterface
     * @throws InputException
     * @throws LocalizedException
     */
    public function getProductTaxClass()
    {
        if (null === $this->productTaxClass) {
            $this->productTaxClass = $this->getTaxClass(
                TaxClassManagementInterface::TYPE_PRODUCT,
                self::PRODUCT_TAX_CLASS_NAME
            );
        }

        return $this->productTaxClass;
    }

    /**
     * @return TaxClassInterface
     * @throws InputException
     * @throws LocalizedException
     */
    public function getCustomerTaxClass()
    {
        if (null === $this->customerTaxClass) {
            $this->customerTaxClass = $this->getTaxClass(
                TaxClassManagementInterface::TYPE_CUSTOMER,
                self::CUSTOMER_TAX_CLASS_NAME
            );
        }

        return $this->customerTaxClass;
    }

    /**
     * @return CustomerGroupInterface
     * @throws InputException
     * @throws LocalizedException
     */
    public function getCustomerGroup()
    {
        if (null === $this->customerGroup) {
            $taxClassId = (int) $this->getCustomerTaxClass()->getClassId();

            $customerGroupCollection = $this->customerGroupCollectionFactory->create();
            $customerGroupCollection->addFieldToFilter('customer_group_code', self::CUSTOMER_GROUP_CODE);
            $this->customerGroup = $customerGroupCollection->getFirstItem();

            if (!$this->customerGroup || !$this->customerGroup->getId()) {
                $this->customerGroup = $this->customerGroupFactory->create();
                $this->customerGroup->setCode(self::CUSTOMER_GROUP_CODE);
                $this->customerGroup->setTaxClassId($taxClassId);
                $this->customerGroupRepository->save($this->customerGroup);
            } elseif ((int) $this->customerGroup->getTaxClassId() !== $taxClassId) {
                $this->customerGroup->setTaxClassId($taxClassId);
                $this->customerGroupRepository->save($this->customerGroup);
            }
        }

        return $this->customerGroup;
    }
}
