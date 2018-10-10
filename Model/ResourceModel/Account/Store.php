<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Account;

use Magento\Catalog\Model\ResourceModel\Product as CatalogProductResource;
use Magento\Catalog\Model\ResourceModel\Product\Collection as CatalogProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as CatalogProductCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface as FeedProductInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractDb;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\ProductFilterApplier;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\SectionFilterApplier;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;
use ShoppingFeed\Manager\Model\TimeHelper;

class Store extends AbstractDb
{
    const DATA_OBJECT_FIELD_NAMES = [ StoreInterface::CONFIGURATION ];

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
     * @param TableDictionary $tableDictionary
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
        TableDictionary $tableDictionary,
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

        parent::__construct(
            $context,
            $timeHelper,
            $tableDictionary,
            $productFilterApplier,
            $sectionFilterApplier,
            $connectionName
        );
    }

    protected function _construct()
    {
        $this->_init('sfm_account_store', StoreInterface::STORE_ID);
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
            [ 'feed_product_table' => $this->tableDictionary->getFeedProductTableName() ],
            'product_id = entity_id',
            [ FeedProductInterface::EXPORT_STATE, FeedProductInterface::CHILD_EXPORT_STATE ],
            [ FeedProductInterface::STORE_ID => $store->getId() ]
        );

        $productCollection->setStoreId($store->getBaseStoreId());
        return $productCollection;
    }

    /**
     * @return int[]
     * @throws LocalizedException
     */
    public function getStoreIds()
    {
        $connection = $this->getConnection();

        return array_map(
            'intval',
            $connection->fetchCol(
                $connection->select()
                    ->from($this->getMainTable(), [ StoreInterface::STORE_ID ])
            )
        );
    }

    /**
     * @param int $storeId
     * @param int[]|null $productIds
     * @return $this
     */
    public function synchronizeFeedProductList($storeId, $productIds = null)
    {
        $connection = $this->getConnection();

        $existingListSelect = $connection->select()
            ->from($this->tableDictionary->getFeedProductTableName(), [ 'product_id' ])
            ->where('store_id = ?', $storeId);

        if (is_array($productIds)) {
            $existingListSelect->where('product_id IN (?)', $productIds);
        }

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
                $this->tableDictionary->getFeedProductTableName(),
                [ 'product_id', 'store_id' ]
            )
        );

        foreach ($this->sectionTypePool->getTypeIds() as $sectionTypeId) {
            $existingListSelect = $connection->select()
                ->from($this->tableDictionary->getFeedProductSectionTableName(), [ 'product_id' ])
                ->where('type_id = ?', $sectionTypeId)
                ->where('store_id = ?', $storeId);

            if (is_array($productIds)) {
                $existingListSelect->where('product_id IN (?)', $productIds);
            }

            $insertableListSelect = $connection->select()
                ->from(
                    $this->tableDictionary->getFeedProductTableName(),
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
                    $this->tableDictionary->getFeedProductSectionTableName(),
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
            ->from($this->tableDictionary->getFeedProductTableName(), [ 'product_id' ])
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
                $this->tableDictionary->getFeedProductTableName(),
                [ 'is_selected' => 0 ],
                $connection->quoteInto('store_id = ?', $storeId)
            );

            $idChunks = array_chunk($productIds, 2000);

            foreach ($idChunks as $productIds) {
                $connection->update(
                    $this->tableDictionary->getFeedProductTableName(),
                    [ 'is_selected' => 1 ],
                    implode(
                        ' AND ',
                        [
                            $connection->quoteInto('store_id = ?', $storeId),
                            $connection->quoteInto('product_id IN (?)', $productIds),
                        ]
                    )
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
                $this->tableDictionary->getFeedProductTableName(),
                [ 'is_selected' => 1 ],
                $connection->quoteInto('product_id = ?', $productId)
                . ' AND '
                . $connection->quoteInto('store_id IN (?)', $storeIds)
            );

            $connection->update(
                $this->tableDictionary->getFeedProductTableName(),
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
