<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Account\Edit;

use ShoppingFeed\Manager\Block\Adminhtml\AbstractButton as BaseButton;
use ShoppingFeed\Manager\Model\Account\RegistryConstants;


abstract class AbstractButton extends BaseButton
{
    /**
     * @return int|null
     */
    public function getAccountId()
    {
        $account = $this->coreRegistry->registry(RegistryConstants::CURRENT_ACCOUNT);
        return $account ? $account->getId() : null;
    }
}
