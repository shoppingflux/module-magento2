<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Type;

use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractType;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter\AttributesInterface as AdapterInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\AttributesInterface as ConfigInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Section\Type as TypeResource;

/**
 * @method AdapterInterface getAdapter()
 * @method ConfigInterface getConfig()
 */
class Attributes extends AbstractType
{
    const CODE = 'attributes';

    const ATTRIBUTE_BRAND = 'brand';
    const ATTRIBUTE_DESCRIPTION = 'description';
    const ATTRIBUTE_GTIN = 'gtin';

    const RESERVED_ATTRIBUTE_CODES = [
        'category',
        'description',
        'ecotax',
        'image',
        'link',
        'name',
        'old_price',
        'price',
        'short_description',
        'vat',
        'weight',
    ];

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
        return __('Attributes');
    }

    public function getSortOrder()
    {
        return 30000;
    }
}
