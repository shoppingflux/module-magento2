<?php

namespace ShoppingFeed\Manager\Model\Account;

use Magento\Catalog\Model\ResourceModel\Product\Collection as CatalogProductCollection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\DataObject;
use ShoppingFeed\Manager\DataObjectFactory;
use ShoppingFeed\Manager\Model\Account\Store\ConfigInterface as StoreConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Export\State\ConfigInterface as ExportStateConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store as StoreResource;
use ShoppingFeed\Manager\Model\ResourceModel\Account\StoreFactory as StoreResourceFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\Collection as StoreCollection;


/**
 * @method StoreResource getResource()
 * @method StoreCollection getCollection()
 */
class Store extends AbstractModel implements StoreInterface
{
    protected $_eventPrefix = 'shoppingfeed_manager_account_store';
    protected $_eventObject = 'account_store';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var ExportStateConfigInterface
     */
    private $exportStateConfig;

    /**
     * @var SectionTypePoolInterface
     */
    private $sectionTypePool;

    /**
     * @var StoreResource
     */
    private $storeResource;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param DataObjectFactory $dataObjectFactory
     * @param ExportStateConfigInterface $exportStateConfig
     * @param SectionTypePoolInterface $sectionTypePool
     * @param StoreResourceFactory $storeResourceFactory
     * @param StoreResource|null $resource
     * @param StoreCollection|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        DataObjectFactory $dataObjectFactory,
        ExportStateConfigInterface $exportStateConfig,
        SectionTypePoolInterface $sectionTypePool,
        StoreResourceFactory $storeResourceFactory,
        StoreResource $resource = null,
        StoreCollection $resourceCollection = null,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->exportStateConfig = $exportStateConfig;
        $this->sectionTypePool = $sectionTypePool;
        $this->storeResource = $storeResourceFactory->create();
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init(StoreResource::class);
    }

    public function getAccountId()
    {
        return (int) $this->getData(self::ACCOUNT_ID);
    }

    public function getBaseStoreId()
    {
        return (int) $this->getData(self::BASE_STORE_ID);
    }

    public function getBaseStore()
    {
        return $this->storeManager->getStore($this->getBaseStoreId());
    }

    public function getShoppingFeedStoreId()
    {
        return (int) $this->getData(self::SHOPPING_FEED_STORE_ID);
    }

    public function getShoppingFeedName()
    {
        return $this->getData(self::SHOPPING_FEED_NAME);
    }

    public function getConfiguration()
    {
        $data = $this->getData(self::CONFIGURATION);

        if (!$data instanceof DataObject) {
            $data = is_string($data) ? json_decode($data, true) : [];
            $data = $this->dataObjectFactory->create([ 'data' => is_array($data) ? $data : [] ]);
            $this->setConfiguration($data);
        }

        return $data;
    }

    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function getScopeConfigValue($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            StoreScopeInterface::SCOPE_STORE,
            $this->getBaseStore()->getCode()
        );
    }

    public function getSelectedFeedProductIds()
    {
        return $this->storeResource->getSelectedFeedProductIds($this->getId());
    }

    /**
     * @return CatalogProductCollection
     * @throws LocalizedException
     */
    public function getCatalogProductCollection()
    {
        return $this->storeResource->getCatalogProductCollection($this);
    }

    public function setAccountId($accountId)
    {
        return $this->setData(self::ACCOUNT_ID, (int) $accountId);
    }

    public function setBaseStoreId($baseStoreId)
    {
        return $this->setData(self::BASE_STORE_ID, (int) $baseStoreId);
    }

    public function setShoppingFeedStoreId($shoppingFeedStoreId)
    {
        return $this->setData(self::SHOPPING_FEED_STORE_ID, (int) $shoppingFeedStoreId);
    }

    public function setShoppingFeedName($shoppingFeedName)
    {
        return $this->setData(self::SHOPPING_FEED_NAME, $shoppingFeedName);
    }

    public function setConfiguration(DataObject $configuration)
    {
        return $this->setData(self::CONFIGURATION, $configuration);
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @param StoreConfigInterface $configModel
     * @param array $params
     */
    private function importSubConfigurationData(StoreConfigInterface $configModel, array $params)
    {
        $configObject = $this->getConfiguration();

        if (isset($params[$configModel->getScope()])) {
            $subScopePath = $configModel->getScopeSubPath();
            $subParams = $params[$configModel->getScope()];

            foreach ($subScopePath as $pathPart) {
                if (!empty($subParams[$pathPart]) && is_array($subParams[$pathPart])) {
                    $subParams = $subParams[$pathPart];
                } else {
                    $subParams = false;
                    break;
                }
            }

            if (is_array($subParams)) {
                foreach ($subParams as $fieldName => $fieldValue) {
                    $field = $configModel->getField($fieldName);

                    if ($field) {
                        $subParams[$fieldName] = $field->prepareFormValueForSave($fieldValue);
                    }
                }

                $configObject->setDataByPath(
                    $configModel->getScope() . '/' . implode('/', $subScopePath),
                    $subParams
                );
            }
        }
    }

    public function importConfigurationData(array $params)
    {
        $this->importSubConfigurationData($this->exportStateConfig, $params);

        foreach ($this->sectionTypePool->getTypes() as $sectionType) {
            $this->importSubConfigurationData($sectionType->getConfig(), $params);
        }

        return $this;
    }
}
