<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method\Applier\Config;

use ShoppingFeed\Manager\Model\Shipping\Method\Applier\AbstractConfig;

class Marketplace extends AbstractConfig implements MarketplaceInterface
{
    /**
     * @return string
     */
    protected function getBaseDefaultCarrierTitle()
    {
        return __('Marketplace');
    }

    /**
     * @return string
     */
    protected function getBaseDefaultMethodTitle()
    {
        return __('Shipping Method');
    }

    protected function getBaseFields()
    {
        $fields = [];
        $relevantFieldNames = [ self::KEY_DEFAULT_CARRIER_TITLE, self::KEY_DEFAULT_METHOD_TITLE ];

        foreach (parent::getBaseFields() as $defaultField) {
            if (in_array($defaultField->getName(), $relevantFieldNames, true)) {
                $fields[] = $defaultField;
            }
        }

        return $fields;
    }
}
