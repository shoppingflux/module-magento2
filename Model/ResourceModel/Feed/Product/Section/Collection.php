<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Section;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use ShoppingFeed\Manager\Model\Feed\Product\Section;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Section as SectionResource;


/**
 * @method SectionResource getResource()
 */
class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Section::class, SectionResource::class);
    }
}
