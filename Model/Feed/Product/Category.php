<?php

namespace ShoppingFeed\Manager\Model\Feed\Product;

use Magento\Framework\DataObject;
use Magento\Catalog\Model\Category as CatalogCategory;


/**
 * @method int getId()
 * @method int getParentId()
 * @method string getName()
 * @method string getUrl()
 * @method int getLevel()
 */
class Category extends DataObject
{
    /**
     * @param CatalogCategory $category
     * @return $this
     */
    public function setCatalogCategory(CatalogCategory $category)
    {
        $this->setData(
            [
                'id' => (int) $category->getId(),
                'parent_id' => (int) $category->getParentId(),
                'name' => $category->getName(),
                'url' => $category->getUrl(),
                'level' => (int) $category->getLevel(),
            ]
        );

        return $this;
    }
}
