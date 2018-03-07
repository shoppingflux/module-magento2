<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Account\Store\Edit;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Block\Adminhtml\AbstractButton as BaseButton;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants;


abstract class AbstractButton extends BaseButton
{
    /**
     * @return int|null
     */
    public function getStoreId()
    {
        /** @var StoreInterface $store */
        $store = $this->coreRegistry->registry(RegistryConstants::CURRENT_ACCOUNT_STORE);
        return $store ? $store->getId() : null;
    }
}
