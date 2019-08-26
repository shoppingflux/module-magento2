<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Source;

use Magento\Catalog\Model\ResourceModel\ProductFactory as CatalogProductResourceFactory;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\AbstractSource;

class Full extends AbstractSource
{
    /**
     * @var CatalogProductResourceFactory
     */
    private $catalogProductResourceFactory;

    /**
     * @var array|null
     */
    private $attributes = null;

    /**
     * @param CatalogProductResourceFactory $catalogProductResourceFactory
     */
    public function __construct(CatalogProductResourceFactory $catalogProductResourceFactory)
    {
        $this->catalogProductResourceFactory = $catalogProductResourceFactory;
    }

    /**
     * @return AbstractAttribute[]
     */
    public function getAttributesByCode()
    {
        if (!is_array($this->attributes)) {
            $catalogProductResource = $this->catalogProductResourceFactory->create();
            $this->attributes = $catalogProductResource->getAttributesByCode();

            if (empty($this->attributes)) {
                $catalogProductResource->loadAllAttributes();
                $this->attributes = $catalogProductResource->getAttributesByCode();
            }
        }

        return $this->attributes;
    }
}
