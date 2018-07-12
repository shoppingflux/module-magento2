<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Data\Collection\AbstractDb as AbstractCollection;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\Quote;
use Magento\Rule\Model\AbstractModel;
use ShoppingFeed\Manager\Model\Shipping\Method\Rule\Condition\CombineFactory as CombinedConditionFactory;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Api\Data\Shipping\Method\RuleInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Shipping\Method\Rule as RuleResource;
use ShoppingFeed\Manager\Model\ResourceModel\Shipping\Method\Rule\Collection as RuleCollection;


/**
 * @method RuleResource getResource()
 * @method RuleCollection getCollection()
 */
class Rule extends AbstractModel implements RuleInterface
{
    protected $_eventPrefix = 'shoppingfeed_manager_shipping_method_rule';
    protected $_eventObject = 'shipping_method_rule';

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var CombinedConditionFactory
     */
    private $combinedConditionFactory;

    /**
     * @var ApplierPoolInterface
     */
    private $applierPool;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param DataObjectFactory $dataObjectFactory
     * @param FormFactory $formFactory
     * @param TimezoneInterface $localeDate
     * @param CombinedConditionFactory $combinedConditionFactory
     * @param ApplierPoolInterface $applierPool
     * @param AbstractResource|null $resource
     * @param AbstractCollection|null $resourceCollection
     * @param array $data
     * @param ExtensionAttributesFactory|null $extensionFactory
     * @param AttributeValueFactory|null $customAttributeFactory
     * @param JsonSerializer|null $serializer
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DataObjectFactory $dataObjectFactory,
        FormFactory $formFactory,
        TimezoneInterface $localeDate,
        CombinedConditionFactory $combinedConditionFactory,
        ApplierPoolInterface $applierPool,
        AbstractResource $resource = null,
        AbstractCollection $resourceCollection = null,
        array $data = [],
        ExtensionAttributesFactory $extensionFactory = null,
        AttributeValueFactory $customAttributeFactory = null,
        JsonSerializer $serializer = null
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->combinedConditionFactory = $combinedConditionFactory;
        $this->applierPool = $applierPool;

        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $localeDate,
            $resource,
            $resourceCollection,
            $data,
            $extensionFactory,
            $customAttributeFactory,
            $serializer
        );
    }

    protected function _construct()
    {
        parent::_construct();
        $this->_init(RuleResource::class);
    }

    public function getName()
    {
        return trim($this->getDataByKey(self::NAME));
    }

    public function getDescription()
    {
        return trim($this->getDataByKey(self::DESCRIPTION));
    }

    public function isActive()
    {
        return (bool) $this->getDataByKey(self::IS_ACTIVE);
    }

    public function getFromDate()
    {
        return $this->getDataByKey(self::FROM_DATE);
    }

    public function getToDate()
    {
        return $this->getDataByKey(self::TO_DATE);
    }

    public function getSortOrder()
    {
        return (int) $this->getDataByKey(self::SORT_ORDER);
    }

    public function getConditionsInstance()
    {
        return $this->combinedConditionFactory->create();
    }

    public function getConditionsSerialized()
    {
        return $this->getDataByKey(self::CONDITIONS_SERIALIZED);
    }

    public function getActionsInstance()
    {
        return null;
    }

    public function getActions()
    {
        return null;
    }

    public function getApplierCode()
    {
        return $this->getDataByKey(self::APPLIER_CODE);
    }

    public function getApplierConfiguration()
    {
        $data = $this->getData(self::APPLIER_CONFIGURATION);

        if (!$data instanceof DataObject) {
            $data = is_string($data) ? json_decode($data, true) : [];
            $data = $this->dataObjectFactory->create([ 'data' => is_array($data) ? $data : [] ]);
            $this->setData(self::APPLIER_CONFIGURATION, $data);
        }

        return $data;
    }

    public function getApplier()
    {
        return $this->applierPool->getApplierByCode($this->getApplierCode());
    }

    public function getCreatedAt()
    {
        return $this->getDataByKey(self::CREATED_AT);
    }

    public function getUpdatedAt()
    {
        return $this->getDataByKey(self::UPDATED_AT);
    }

    public function setName($name)
    {
        return $this->setData(self::NAME, trim($name));
    }

    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, trim($description));
    }

    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, (bool) $isActive);
    }

    public function setFromDate($fromDate)
    {
        return $this->setData(self::FROM_DATE, $fromDate);
    }

    public function setToDate($toDate)
    {
        return $this->setData(self::TO_DATE, $toDate);
    }

    public function setSortOrder($sortOrder)
    {
        return $this->setData(self::SORT_ORDER, (int) $sortOrder);
    }

    public function setRawConditions(array $rawConditions)
    {
        $this->loadPost([ 'conditions' => $rawConditions ]);
        return $this;
    }

    public function setConditionsSerialized($conditions)
    {
        return $this->setData(self::CONDITIONS_SERIALIZED, $conditions);
    }

    /**
     * @param string $code
     * @param array $configData
     * @return RuleInterface
     * @throws LocalizedException
     */
    public function setApplier($code, array $configData)
    {
        $applier = $this->applierPool->getApplierByCode($code);
        $this->setData(self::APPLIER_CODE, $code);
        $configObject = $this->dataObjectFactory->create();

        foreach ($applier->getConfig()->getFields() as $field) {
            $fieldName = $field->getName();

            if (isset($configData[$fieldName])) {
                $configObject->setData($fieldName, $field->prepareFormValueForSave($configData[$fieldName]));
            }
        }

        $this->setData(self::APPLIER_CONFIGURATION, $configObject);
        return $this;
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    public function isAppliableToQuote(Quote $quote, MarketplaceOrderInterface $marketplaceOrder)
    {
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setData(self::KEY_VALIDATED_MARKETPLACE_ORDER, $marketplaceOrder);
        return $this->validate($shippingAddress);
    }
}
