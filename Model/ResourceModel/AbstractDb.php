<?php

namespace ShoppingFeed\Manager\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb as BaseDb;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use ShoppingFeed\Manager\Model\Time\Helper as TimeHelper;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Filter\Applier as ProductFilterApplier;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Section\Filter\Applier as SectionFilterApplier;


abstract class AbstractDb extends BaseDb
{
    /**
     * @var TimeHelper
     */
    protected $timeHelper;

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
     * @param ProductFilterApplier $productFilterApplier
     * @param SectionFilterApplier $sectionFilterApplier
     * @param string|null $connectionName
     */
    public function __construct(
        DbContext $context,
        TimeHelper $timeHelper,
        ProductFilterApplier $productFilterApplier,
        SectionFilterApplier $sectionFilterApplier,
        $connectionName = null
    ) {
        $this->timeHelper = $timeHelper;
        $this->productFilterApplier = $productFilterApplier;
        $this->sectionFilterApplier = $sectionFilterApplier;
        parent::__construct($context, $connectionName);
    }

    /**
     * @return string
     */
    public function getAccountTable()
    {
        return $this->getTable('sfm_account');
    }

    /**
     * @return string
     */
    public function getAccountStoreTable()
    {
        return $this->getTable('sfm_account_store');
    }

    /**
     * @return string
     */
    public function getFeedProductTable()
    {
        return $this->getTable('sfm_feed_product');
    }

    /**
     * @return string
     */
    public function getFeedProductSectionTable()
    {
        return $this->getTable('sfm_feed_product_section');
    }

    /**
     * @return string
     */
    public function getFeedProductSectionTypeTable()
    {
        return $this->getTable('sfm_feed_product_section_type');
    }

    /**
     * @return string
     */
    public function getConfigurableProductLinkTable()
    {
        return $this->getTable('catalog_product_super_link');
    }
}
