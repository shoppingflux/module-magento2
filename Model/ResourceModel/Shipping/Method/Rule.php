<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Shipping\Method;

use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\ResourceModel\AbstractResource;
use ShoppingFeed\Manager\Api\Data\Shipping\Method\RuleInterface;
use ShoppingFeed\Manager\Model\ResourceModel\WithSerializedData;

class Rule extends AbstractResource
{
    use WithSerializedData;

    protected function _construct()
    {
        $this->_init('sfm_shipping_method_rule', 'rule_id');
    }

    protected function _prepareDataForSave(AbstractModel $object)
    {
        return $this->prepareDataForSaveWithSerialized(
            $object,
            [ RuleInterface::APPLIER_CONFIGURATION ],
            function () use ($object) {
                return parent::_prepareDataForSave($object);
            }
        );
    }

    protected function prepareDataForUpdate($object)
    {
        return $this->prepareDataForUpdateWithSerialized(
            $object,
            [ RuleInterface::APPLIER_CONFIGURATION ],
            function () use ($object) {
                return parent::prepareDataForUpdate($object);
            }
        );
    }
}
