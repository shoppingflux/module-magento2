<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Account;

use Magento\Catalog\Model\ResourceModel\Product as CatalogProductResource;
use Magento\Catalog\Model\ResourceModel\Product\Collection as CatalogProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as CatalogProductCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface as FeedProductInterface;
use ShoppingFeed\Manager\DataObject;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractDb;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Filter\Applier as ProductFilterApplier;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Section\Filter\Applier as SectionFilterApplier;
use ShoppingFeed\Manager\Model\Time\Helper as TimeHelper;


class Store extends AbstractDb
{
    /**
     * @var SectionTypePoolInterface
     */
    protected $sectionTypePool;

    /**
     * @var CatalogProductResource
     */
    protected $catalogProductResource;

    /**
     * @var CatalogProductCollectionFactory
     */
    private $catalogProductCollectionFactory;

    /**
     * @param DbContext $context
     * @param TimeHelper $timeHelper
     * @param ProductFilterApplier $productFilterApplier
     * @param SectionFilterApplier $sectionFilterApplier
     * @param SectionTypePoolInterface $sectionTypePool
     * @param CatalogProductResource $catalogProductResource
     * @param CatalogProductCollectionFactory $catalogProductCollectionFactory
     * @param string|null $connectionName
     */
    public function __construct(
        DbContext $context,
        TimeHelper $timeHelper,
        ProductFilterApplier $productFilterApplier,
        SectionFilterApplier $sectionFilterApplier,
        SectionTypePoolInterface $sectionTypePool,
        CatalogProductResource $catalogProductResource,
        CatalogProductCollectionFactory $catalogProductCollectionFactory,
        $connectionName = null
    ) {
        $this->sectionTypePool = $sectionTypePool;
        $this->catalogProductResource = $catalogProductResource;
        $this->catalogProductCollectionFactory = $catalogProductCollectionFactory;
        parent::__construct($context, $timeHelper, $productFilterApplier, $sectionFilterApplier, $connectionName);
    }

    protected function _construct()
    {
        $this->_init('sfm_account_store', 'store_id');
    }

    protected function _prepareDataForSave(AbstractModel $object)
    {
        /** @var StoreInterface $object */
        $preparedData = parent::_prepareDataForSave($object);
        $preparedData[StoreInterface::CONFIGURATION] = json_encode($object->getConfiguration()->getData());
        return $preparedData;
    }

    protected function prepareDataForUpdate($object)
    {
        /** @var AbstractModel $object */
        $baseConfiguration = $object->getData(StoreInterface::CONFIGURATION);
        $jsonConfiguration = '';

        if ($baseConfiguration instanceof DataObject) {
            $jsonConfiguration = json_encode($baseConfiguration->getData());
        } elseif (is_array($baseConfiguration)) {
            $jsonConfiguration = json_encode($baseConfiguration);
        } elseif (is_string($baseConfiguration)) {
            $jsonConfiguration = $baseConfiguration;
        }

        $object->setData(StoreInterface::CONFIGURATION, $jsonConfiguration);
        $preparedData = parent::prepareDataForUpdate($object);
        $object->setData(StoreInterface::CONFIGURATION, $baseConfiguration);

        return $preparedData;
    }

    /**
     * @param StoreInterface $store
     * @return CatalogProductCollection
     * @throws LocalizedException
     */
    public function getCatalogProductCollection(StoreInterface $store)
    {
        /** @var CatalogProductCollection $productCollection */
        $productCollection = $this->catalogProductCollectionFactory->create();

        $productCollection->joinTable(
            [ 'feed_product_table' => $this->getFeedProductTable() ],
            'product_id = entity_id',
            [ FeedProductInterface::EXPORT_STATE, FeedProductInterface::CHILD_EXPORT_STATE ],
            [ FeedProductInterface::STORE_ID => $store->getId() ]
        );

        $productCollection->setStoreId($store->getBaseStoreId());
        return $productCollection;
    }

    /**
     * @param int $storeId
     * @return $this
     */
    public function synchronizeFeedProductList($storeId)
    {
        $connection = $this->getConnection();

        $existingListSelect = $connection->select()
            ->from($this->getFeedProductTable(), [ 'product_id' ])
            ->where('store_id = ?', $storeId);

        $entityIdFieldName = $this->catalogProductResource->getEntityIdField();

        $insertableListSelect = $connection->select()
            ->from(
                $this->catalogProductResource->getEntityTable(),
                [
                    'product_id' => $entityIdFieldName,
                    'store_id' => new \Zend_Db_Expr($storeId),
                ]
            )
            ->where(
                $connection->quoteIdentifier($entityIdFieldName) . ' NOT IN (?)',
                new \Zend_Db_Expr($existingListSelect->assemble())
            );

        $connection->query(
            $connection->insertFromSelect(
                $insertableListSelect,
                $this->getFeedProductTable(),
                [ 'product_id', 'store_id' ]
            )
        );

        foreach ($this->sectionTypePool->getTypeIds() as $sectionTypeId) {
            $existingListSelect = $connection->select()
                ->from($this->getFeedProductSectionTable(), [ 'product_id' ])
                ->where('type_id = ?', $sectionTypeId)
                ->where('store_id = ?', $storeId);

            $insertableListSelect = $connection->select()
                ->from(
                    $this->getFeedProductTable(),
                    [
                        'type_id' => new \Zend_Db_Expr($sectionTypeId),
                        'product_id' => 'product_id',
                        'store_id' => 'store_id',
                    ]
                )
                ->where('store_id = ?', $storeId)
                ->where('product_id NOT IN (?)', new \Zend_Db_Expr($existingListSelect->assemble()));

            $connection->query(
                $connection->insertFromSelect(
                    $insertableListSelect,
                    $this->getFeedProductSectionTable(),
                    [ 'type_id', 'product_id', 'store_id' ]
                )
            );
        }

        return $this;
    }

    /**
     * @param int $storeId
     * @return int[]
     */
    public function getSelectedFeedProductIds($storeId)
    {
        $connection = $this->getConnection();

        $idsSelect = $connection->select()
            ->from($this->getFeedProductTable(), [ 'product_id' ])
            ->where('store_id = ?', $storeId)
            ->where('is_selected = ?', 1);

        return array_map('intval', $connection->fetchCol($idsSelect));
    }

    /**
     * @param int $storeId
     * @param int[] $productIds
     * @throws \Exception
     */
    public function updateSelectedFeedProducts($storeId, array $productIds)
    {
        $connection = $this->getConnection();
        $connection->beginTransaction();

        try {
            $connection->update(
                $this->getFeedProductTable(),
                [ 'is_selected' => 0 ],
                $connection->quoteInto('store_id = ?', $storeId)
            );

            $idChunks = array_chunk($productIds, 2000);

            foreach ($idChunks as $productIds) {
                $connection->update(
                    $this->getFeedProductTable(),
                    [ 'is_selected' => 1 ],
                    $connection->quoteInto('product_id IN (?)', $productIds)
                );

            }

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * @param int $productId
     * @param int[] $storeIds
     * @throws \Exception
     */
    public function updateFeedProductSelectedState($productId, array $storeIds)
    {
        $connection = $this->getConnection();
        $connection->beginTransaction();

        try {
            $connection->update(
                $this->getFeedProductTable(),
                [ 'is_selected' => 1 ],
                $connection->quoteInto('product_id = ?', $productId)
                . ' AND '
                . $connection->quoteInto('store_id IN (?)', $storeIds)
            );

            $connection->update(
                $this->getFeedProductTable(),
                [ 'is_selected' => 0 ],
                $connection->quoteInto('product_id = ?', $productId)
                . ' AND '
                . $connection->quoteInto('store_id NOT IN (?)', $storeIds)
            );
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
