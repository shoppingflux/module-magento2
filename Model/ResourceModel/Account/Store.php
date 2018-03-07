<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Account;

use Magento\Catalog\Model\ResourceModel\Product as CatalogProductResource;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
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
     * @param DbContext $context
     * @param TimeHelper $timeHelper
     * @param ProductFilterApplier $productFilterApplier
     * @param SectionFilterApplier $sectionFilterApplier
     * @param SectionTypePoolInterface $sectionTypePool
     * @param CatalogProductResource $catalogProductResource
     * @param string|null $connectionName
     */
    public function __construct(
        DbContext $context,
        TimeHelper $timeHelper,
        ProductFilterApplier $productFilterApplier,
        SectionFilterApplier $sectionFilterApplier,
        SectionTypePoolInterface $sectionTypePool,
        CatalogProductResource $catalogProductResource,
        $connectionName = null
    ) {
        $this->sectionTypePool = $sectionTypePool;
        $this->catalogProductResource = $catalogProductResource;
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
     * @param int[] $productIds
     * @throws \Exception
     */
    public function updateFeedProductSelection($storeId, array $productIds)
    {
        $connection = $this->getConnection();
        $connection->beginTransaction();

        try {
            $connection->update(
                $this->getFeedProductTable(),
                [ 'is_selected' => 0 ],
                $connection->quoteInto('feed_id = ?', $storeId)
            );

            $idChunks = array_chunk($productIds, 1000);

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
}
