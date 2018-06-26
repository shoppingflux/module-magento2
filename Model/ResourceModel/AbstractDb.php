<?php

namespace ShoppingFeed\Manager\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb as BaseDb;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use ShoppingFeed\Manager\Model\Time\Helper as TimeHelper;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Filter\Applier as ProductFilterApplier;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Section\Filter\Applier as SectionFilterApplier;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;


abstract class AbstractDb extends BaseDb
{
    /**
     * @var TimeHelper
     */
    protected $timeHelper;

    /**
     * @var TableDictionary
     */
    protected $tableDictionary;

    /**
     * @var ProductFilterApplier
     */
    protected $productFilterApplier;

    /**
     * @var SectionFilterApplier
     */
    protected $sectionFilterApplier;

    /**
     * @param DbContext $context
     * @param TimeHelper $timeHelper
     * @param TableDictionary $tableDictionary
     * @param ProductFilterApplier $productFilterApplier
     * @param SectionFilterApplier $sectionFilterApplier
     * @param string|null $connectionName
     */
    public function __construct(
        DbContext $context,
        TimeHelper $timeHelper,
        TableDictionary $tableDictionary,
        ProductFilterApplier $productFilterApplier,
        SectionFilterApplier $sectionFilterApplier,
        $connectionName = null
    ) {
        $this->timeHelper = $timeHelper;
        $this->tableDictionary = $tableDictionary;
        $this->productFilterApplier = $productFilterApplier;
        $this->sectionFilterApplier = $sectionFilterApplier;
        parent::__construct($context, $connectionName);
    }
}
