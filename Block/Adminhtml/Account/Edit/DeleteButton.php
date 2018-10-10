<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Account\Edit;

use ShoppingFeed\Manager\Block\Adminhtml\AbstractButton;
use ShoppingFeed\Manager\Model\Account\RegistryConstants;

class DeleteButton extends AbstractButton
{
    public function getButtonData()
    {
        $data = [];

        if ($this->getAccountId()) {
            $data = [
                'label' => __('Delete Account'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm('
                    . '\'' . __('Are you sure you want to do this?') . '\', '
                    . '\'' . $this->getDeleteUrl() . '\''
                    . ')',
                'sort_order' => 2000,
            ];
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', [ 'account_id' => $this->getAccountId() ]);
    }

    /**
     * @return int|null
     */
    public function getAccountId()
    {
        $account = $this->coreRegistry->registry(RegistryConstants::CURRENT_ACCOUNT);
        return $account ? $account->getId() : null;
    }
}
