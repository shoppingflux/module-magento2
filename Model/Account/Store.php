<?php

namespace ShoppingFeed\Manager\Model\Account;

use Magento\Catalog\Model\ResourceModel\Product\Collection as CatalogProductCollection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use ShoppingFeed\Manager\Api\AccountRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\AccountInterface;
use ShoppingFeed\Manager\DataObject;
use ShoppingFeed\Manager\DataObjectFactory;
use ShoppingFeed\Manager\Model\Account\Store\ConfigInterface as StoreConfigInterface;
use ShoppingFeed\Manager\Model\Feed\ConfigInterface as FeedConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Export\State\ConfigInterface as ExportStateConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store as StoreResource;
use ShoppingFeed\Manager\Model\ResourceModel\Account\StoreFactory as StoreResourceFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\Collection as StoreCollection;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;

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
     * @var AccountRepositoryInterface
     */
    private $accountRepository;

    /**
     * @var AccountInterface|null
     */
    private $account = null;

    /**
     * @var FeedConfigInterface
     */
    private $feedGeneralConfig;

    /**
     * @var ExportStateConfigInterface
     */
    private $feedExportStateConfig;

    /**
     * @var SectionTypePoolInterface
     */
    private $sectionTypePool;

    /**
     * @var OrderConfigInterface
     */
    private $orderGeneralConfig;

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
     * @param AccountRepositoryInterface $accountRepository
     * @param FeedConfigInterface $feedGeneralConfig
     * @param ExportStateConfigInterface $feedExportStateConfig
     * @param SectionTypePoolInterface $sectionTypePool
     * @param OrderConfigInterface $orderGeneralConfig
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
        AccountRepositoryInterface $accountRepository,
        FeedConfigInterface $feedGeneralConfig,
        ExportStateConfigInterface $feedExportStateConfig,
        SectionTypePoolInterface $sectionTypePool,
        OrderConfigInterface $orderGeneralConfig,
        StoreResourceFactory $storeResourceFactory,
        StoreResource $resource = null,
        StoreCollection $resourceCollection = null,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->accountRepository = $accountRepository;
        $this->feedGeneralConfig = $feedGeneralConfig;
        $this->feedExportStateConfig = $feedExportStateConfig;
        $this->sectionTypePool = $sectionTypePool;
        $this->orderGeneralConfig = $orderGeneralConfig;
        $this->storeResource = $storeResourceFactory->create();
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init(StoreResource::class);
    }

    public function getId()
    {
        $id = parent::getId();
        return empty($id) ? null : (int) $id;
    }

    public function getAccountId()
    {
        return (int) $this->getData(self::ACCOUNT_ID);
    }

    /**
     * @return AccountInterface
     * @throws NoSuchEntityException
     */
    public function getAccount()
    {
        if (null === $this->account) {
            $this->account = $this->accountRepository->getById($this->getAccountId());
        }

        return $this->account;
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
        if (!$this->hasData('selected_feed_product_ids')) {
            $this->setData(
                'selected_feed_product_ids',
                $this->storeResource->getSelectedFeedProductIds($this->getId())
            );
        }

        return $this->getDataByKey('selected_feed_product_ids');
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
     * @param array $data
     */
    private function importSubConfigurationData(StoreConfigInterface $configModel, array $data)
    {
        $configObject = $this->getConfiguration();

        if (isset($data[$configModel->getScope()])) {
            $subScopePath = $configModel->getScopeSubPath();
            $subData = $data[$configModel->getScope()];

            foreach ($subScopePath as $pathPart) {
                if (!empty($subData[$pathPart]) && is_array($subData[$pathPart])) {
                    $subData = $subData[$pathPart];
                } else {
                    $subData = false;
                    break;
                }
            }

            if (is_array($subData)) {
                foreach ($subData as $fieldName => $fieldValue) {
                    $field = $configModel->getField($this, $fieldName);

                    if ($field) {
                        $subData[$fieldName] = $field->prepareFormValueForSave($fieldValue);
                    }
                }

                $configObject->setDataByPath(
                    $configModel->getScope() . '/' . implode('/', $subScopePath),
                    $subData
                );
            }
        }
    }

    public function importConfigurationData(array $data)
    {
        $this->importSubConfigurationData($this->feedGeneralConfig, $data);
        $this->importSubConfigurationData($this->feedExportStateConfig, $data);

        foreach ($this->sectionTypePool->getTypes() as $sectionType) {
            $this->importSubConfigurationData($sectionType->getConfig(), $data);
        }

        $this->importSubConfigurationData($this->orderGeneralConfig, $data);

        return $this;
    }
}
