<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Shipping\Method;

use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\ResourceModel\AbstractResource;
use ShoppingFeed\Manager\Api\Data\Shipping\Method\RuleInterface;


class Rule extends AbstractResource
{
    protected function _construct()
    {
        $this->_init('sfm_shipping_method_rule', 'rule_id');
    }

    protected function _prepareDataForSave(AbstractModel $object)
    {
        /** @var RuleInterface $object */
        $applierConfiguration = $object->getApplierConfiguration();
        $object->unsetData(RuleInterface::APPLIER_CONFIGURATION);
        $preparedData = parent::_prepareDataForSave($object);
        $preparedData[RuleInterface::APPLIER_CONFIGURATION] = json_encode($applierConfiguration->getData());
        $object->setData(RuleInterface::APPLIER_CONFIGURATION, $applierConfiguration);
        return $preparedData;
    }

    protected function prepareDataForUpdate($object)
    {
        /** @var AbstractModel $object */
        $baseApplierConfiguration = $object->getData(RuleInterface::APPLIER_CONFIGURATION);
        $jsonApplierConfiguration = '';

        if ($baseApplierConfiguration instanceof DataObject) {
            $jsonApplierConfiguration = json_encode($baseApplierConfiguration->getData());
        } elseif (is_array($baseApplierConfiguration)) {
            $jsonApplierConfiguration = json_encode($baseApplierConfiguration);
        } elseif (is_string($baseApplierConfiguration)) {
            $jsonApplierConfiguration = $baseApplierConfiguration;
        }

        $object->setData(RuleInterface::APPLIER_CONFIGURATION, $jsonApplierConfiguration);
        $preparedData = parent::prepareDataForUpdate($object);
        $object->setData(RuleInterface::APPLIER_CONFIGURATION, $baseApplierConfiguration);

        return $preparedData;
    }
}
