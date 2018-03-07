<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Type;

use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractType;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter\CategoriesInterface as AdapterInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\CategoriesInterface as ConfigInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Section\Type as TypeResource;


/**
 * @method AdapterInterface getAdapter()
 * @method ConfigInterface getConfig()
 */
class Categories extends AbstractType
{
    const CODE = 'categories';

    public function __construct(TypeResource $typeResource, AdapterInterface $adapter, ConfigInterface $config)
    {
        parent::__construct($typeResource, $adapter, $config);
    }

    public function getCode()
    {
        return self::CODE;
    }

    public function getLabel()
    {
        return __('Categories');
    }

    public function getSortOrder()
    {
        return 50000;
    }
}
