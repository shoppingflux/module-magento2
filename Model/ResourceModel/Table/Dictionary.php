<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Table;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;


class Dictionary extends AbstractDb
{
    protected function _construct()
    {
    }

    /**
     * @return string
     */
    public function getAccountTableCode()
    {
        return 'sfm_account';
    }

    /**
     * @return string
     */
    public function getAccountTableName()
    {
        return $this->getTable($this->getAccountTableCode());
    }

    /**
     * @return string
     */
    public function getAccountStoreTableCode()
    {
        return 'sfm_account_store';
    }

    /**
     * @return string
     */
    public function getAccountStoreTableName()
    {
        return $this->getTable($this->getAccountStoreTableCode());
    }

    /**
     * @return string
     */
    public function getFeedProductTableCode()
    {
        return 'sfm_feed_product';
    }

    /**
     * @return string
     */
    public function getFeedProductTableName()
    {
        return $this->getTable($this->getFeedProductTableCode());
    }

    /**
     * @return string
     */
    public function getFeedProductSectionTableCode()
    {
        return 'sfm_feed_product_section';
    }

    /**
     * @return string
     */
    public function getFeedProductSectionTableName()
    {
        return $this->getTable($this->getFeedProductSectionTableCode());
    }

    /**
     * @return string
     */
    public function getFeedProductSectionTypeTableCode()
    {
        return 'sfm_feed_product_section_type';
    }

    /**
     * @return string
     */
    public function getFeedProductSectionTypeTableName()
    {
        return $this->getTable($this->getFeedProductSectionTypeTableCode());
    }

    /**
     * @return string
     */
    public function getMarketplaceOrderTableCode()
    {
        return 'sfm_marketplace_order';
    }

    /**
     * @return string
     */
    public function getMarketplaceOrderTableName()
    {
        return $this->getTable($this->getMarketplaceOrderTableCode());
    }

    /**
     * @return string
     */
    public function getMarketplaceOrderAddressTableCode()
    {
        return 'sfm_marketplace_order_address';
    }

    /**
     * @return string
     */
    public function getMarketplaceOrderAddressTableName()
    {
        return $this->getTable($this->getMarketplaceOrderAddressTableCode());
    }

    /**
     * @return string
     */
    public function getMarketplaceOrderItemTableCode()
    {
        return 'sfm_marketplace_order_item';
    }

    /**
     * @return string
     */
    public function getMarketplaceOrderItemTableName()
    {
        return $this->getTable($this->getMarketplaceOrderItemTableCode());
    }

    /**
     * @return string
     */
    public function getMarketplaceOrderTicketTableCode()
    {
        return 'sfm_marketplace_order_ticket';
    }

    /**
     * @return string
     */
    public function getMarketplaceOrderTicketTableName()
    {
        return $this->getTable($this->getMarketplaceOrderTicketTableCode());
    }


    /**
     * @return string
     */
    public function getMarketplaceOrderLogTableCode()
    {
        return 'sfm_marketplace_order_log';
    }

    /**
     * @return string
     */
    public function getMarketplaceOrderLogTableName()
    {
        return $this->getTable($this->getMarketplaceOrderLogTableCode());
    }

    /**
     * @return string
     */
    public function getShippingMethodRuleTableCode()
    {
        return 'sfm_shipping_method_rule';
    }

    /**
     * @return string
     */
    public function getShippingMethodRuleTableName()
    {
        return $this->getTable($this->getShippingMethodRuleTableCode());
    }

    /**
     * @return string
     */
    public function getStoreTableCode()
    {
        return 'store';
    }

    /**
     * @return string
     */
    public function getWebsiteTableCode()
    {
        return 'website';
    }

    /**
     * @return string
     */
    public function getCatalogCategoryTableCode()
    {
        return 'catalog_category_entity';
    }

    /**
     * @return string
     */
    public function getCatalogProductTableCode()
    {
        return 'catalog_product_entity';
    }

    /**
     * @return string
     */
    public function getConfigurableProductAttributeTableCode()
    {
        return 'catalog_product_super_attribute';
    }

    /**
     * @return string
     */
    public function getConfigurableProductAttributeTableName()
    {
        return $this->getTable($this->getConfigurableProductAttributeTableCode());
    }

    /**
     * @return string
     */
    public function getConfigurableProductLinkTableCode()
    {
        return 'catalog_product_super_link';
    }

    /**
     * @return string
     */
    public function getConfigurableProductLinkTableName()
    {
        return $this->getTable($this->getConfigurableProductLinkTableCode());
    }

    /**
     * @return string
     */
    public function getEavAttributeTableCode()
    {
        return 'eav_attribute';
    }

    /**
     * @return string
     */
    public function getEavAttributeTableName()
    {
        return $this->getTable($this->getEavAttributeTableCode());
    }

    /**
     * @return string
     */
    public function getSalesOrderTableCode()
    {
        return 'sales_order';
    }

    /**
     * @return string
     */
    public function getSalesOrderTableName()
    {
        return $this->getTable($this->getSalesOrderTableCode());
    }

    /**
     * @return string
     */
    public function getSalesShipmentTableCode()
    {
        return 'sales_shipment';
    }

    /**
     * @return string
     */
    public function getSalesShipmentTableName()
    {
        return $this->getTable($this->getSalesShipmentTableCode());
    }
}
