<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Shipping\Method\Rule\Edit;

use ShoppingFeed\Manager\Api\Data\Shipping\Method\RuleInterface;
use ShoppingFeed\Manager\Block\Adminhtml\AbstractButton as BaseButton;
use ShoppingFeed\Manager\Model\Shipping\Method\Rule\RegistryConstants;


abstract class AbstractButton extends BaseButton
{
    /**
     * @return int|null
     */
    public function getShippingMethodRuleId()
    {
        /** @var RuleInterface $methodRule */
        $methodRule = $this->coreRegistry->registry(RegistryConstants::CURRENT_SHIPPING_METHOD_RULE);
        return $methodRule ? $methodRule->getId() : null;
    }
}
