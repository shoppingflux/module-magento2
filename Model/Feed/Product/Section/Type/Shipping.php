<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Type;

use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractType;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter\ShippingInterface as AdapterInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\ShippingInterface as ConfigInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Section\Type as TypeResource;


/**
 * @method AdapterInterface getAdapter()
 * @method ConfigInterface getConfig()
 */
class Shipping extends AbstractType
{
    const CODE = 'shipping';

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
        return __('Shipping');
    }
    
    public function getSortOrder()
    {
        return 60000;
    }
}
