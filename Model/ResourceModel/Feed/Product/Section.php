<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Feed\Product;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use ShoppingFeed\Manager\Api\Data\Feed\Product\SectionInterface as ProductSectionInterface;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFactory as ProductSectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractDb;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\ProductFilterApplier;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;
use ShoppingFeed\Manager\Model\TimeHelper;

class Section extends AbstractDb
{
    const SECTION_DATA_UPDATE_BATCH_SIZE = 250;

    /**
     * @var ProductSectionFactory
     */
    private $productSectionFactory;

    /**
     * @var int
     */
    private $sectionDataUpdateBatchSize;

    /**
     * @var array|null
     */
    private $sectionDataBatchedUpdates = null;

    /**
     * @var int
     */
    private $sectionDataBatchedUpdateCount = 0;

    /**
     * @param DbContext $context
     * @param TimeHelper $timeHelper
     * @param TableDictionary $tableDictionary
     * @param ProductFilterApplier $productFilterApplier
     * @param SectionFilterApplier $sectionFilterApplier
     * @param ProductSectionFactory $productSectionFactory
     * @param string|null $connectionName
     * @param int $sectionDataUpdateBatchSize
     */
    public function __construct(
        DbContext $context,
        TimeHelper $timeHelper,
        TableDictionary $tableDictionary,
        ProductFilterApplier $productFilterApplier,
        SectionFilterApplier $sectionFilterApplier,
        ProductSectionFactory $productSectionFactory,
        string $connectionName = null,
        $sectionDataUpdateBatchSize = self::SECTION_DATA_UPDATE_BATCH_SIZE
    ) {
        $this->productSectionFactory = $productSectionFactory;
        $this->sectionDataUpdateBatchSize = max(1, (int) $sectionDataUpdateBatchSize);

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
        $this->_init('sfm_feed_product_section', 'section_id');
    }

    /**
     * @param array $data
     * @return string
     */
    public function serializeSectionData(array $data)
    {
        return json_encode($data, JSON_FORCE_OBJECT);
    }

    /**
     * @param string $data
     * @return array
     */
    public function unserializeSectionData($data)
    {
        return ('' !== trim((string) $data)) ? (array) json_decode($data, true) : [];
    }

    /**
     * @param int $productId
     * @param int $storeId
     * @param int $sectionTypeId
     * @param array $data
     * @param int $newRefreshState
     * @return $this
     */
    public function updateSectionData($sectionTypeId, $productId, $storeId, array $data, $newRefreshState)
    {
        $connection = $this->getConnection();
        $now = $this->timeHelper->utcDate();

        $values = [
            'data' => $this->serializeSectionData($data),
            'refreshed_at' => $now,
            'refresh_state' => $newRefreshState,
            'refresh_state_updated_at' => $now,
        ];

        if (is_array($this->sectionDataBatchedUpdates)) {
            $values['type_id'] = $sectionTypeId;
            $values['product_id'] = $productId;
            $values['store_id'] = $storeId;
            $this->sectionDataBatchedUpdates[] = $values;

            if (++$this->sectionDataBatchedUpdateCount > $this->sectionDataUpdateBatchSize) {
                $this->flushSectionDataBatchedUpdates();
            }
        } else {
            $connection->update(
                $this->tableDictionary->getFeedProductSectionTableName(),
                $values,
                $connection->quoteInto('type_id = ?', $sectionTypeId)
                . ' AND '
                . $connection->quoteInto('product_id = ?', $productId)
                . ' AND '
                . $connection->quoteInto('store_id = ?', $storeId)
            );
        }

        return $this;
    }

    private function flushSectionDataBatchedUpdates()
    {
        if (is_array($this->sectionDataBatchedUpdates) && !empty($this->sectionDataBatchedUpdates)) {
            $this->getConnection()
                ->insertOnDuplicate(
                    $this->tableDictionary->getFeedProductSectionTableName(),
                    $this->sectionDataBatchedUpdates,
                    [
                        'data',
                        'refreshed_at',
                        'refresh_state',
                        'refresh_state_updated_at',
                    ]
                );

            $this->sectionDataBatchedUpdates = [];
            $this->sectionDataBatchedUpdateCount = 0;
        }
    }

    public function startSectionDataUpdateBatching()
    {
        $this->sectionDataBatchedUpdates = [];
        $this->sectionDataBatchedUpdateCount = 0;
    }

    public function stopSectionDataUpdateBatching()
    {
        $this->flushSectionDataBatchedUpdates();
        $this->sectionDataBatchedUpdates = null;
    }

    /**
     * @param int $productId
     * @param int $storeId
     * @return ProductSectionInterface[]
     * @throws LocalizedException
     */
    public function getProductSections($productId, $storeId)
    {
        $sections = [];
        $connection = $this->getConnection();

        $sectionSelect = $connection->select()
            ->from($this->getMainTable())
            ->where($connection->quoteInto(ProductSectionInterface::STORE_ID . ' = ?', $storeId))
            ->where($connection->quoteInto(ProductSectionInterface::PRODUCT_ID . ' = ?', $productId));

        foreach ($connection->fetchAll($sectionSelect) as $sectionData) {
            $section = $this->productSectionFactory->create();
            $section->addData($sectionData);
            $sections[$section->getTypeId()] = $section;
        }

        return $sections;
    }
}
