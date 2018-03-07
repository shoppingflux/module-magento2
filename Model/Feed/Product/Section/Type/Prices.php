<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Type;

use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractType;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter\PricesInterface as AdapterInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\PricesInterface as ConfigInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Section\Type as TypeResource;


/**
 * @method AdapterInterface getAdapter()
 * @method ConfigInterface getConfig()
 */
class Prices extends AbstractType
{
    const CODE = 'prices';

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
        return __('Prices');
    }

    public function getSortOrder()
    {
        return 20000;
    }
}
