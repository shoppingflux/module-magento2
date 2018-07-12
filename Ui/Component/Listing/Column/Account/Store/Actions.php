<?php

namespace ShoppingFeed\Manager\Ui\Component\Listing\Column\Account\Store;

use ShoppingFeed\Manager\Controller\Adminhtml\Account\StoreAction;
use ShoppingFeed\Manager\Ui\Component\Listing\Column\AbstractActions;


class Actions extends AbstractActions
{
    const ACL_EDIT = 'ShoppingFeed_Manager::account_store_edit';
    const ACL_DELETE = 'ShoppingFeed_Manager::account_store_delete';
    
    const URL_PATH_EDIT = 'shoppingfeed_manager/account_store/edit';
    const URL_PATH_DELETE = 'shoppingfeed_manager/account_store/delete';

    public function prepareDataSource(array $dataSource)
    {
        $isEditAllowed = $this->authorizationModel->isAllowed(static::ACL_EDIT);
        $isDeleteAllowed = $this->authorizationModel->isAllowed(static::ACL_DELETE);

        if (!$isEditAllowed && !$isDeleteAllowed) {
            return $dataSource;
        }

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['store_id'])) {
                    $name = $this->getData('name');
                    $item[$name] = [];

                    if ($isEditAllowed) {
                        $item[$name]['edit'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_EDIT,
                                [ StoreAction::REQUEST_KEY_STORE_ID => $item['store_id'] ]
                            ),
                            'label' => __('Edit'),
                        ];
                    }

                    if ($isDeleteAllowed) {
                        $item[$name]['delete'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_DELETE,
                                [ StoreAction::REQUEST_KEY_STORE_ID => $item['store_id'] ]
                            ),
                            'label' => __('Delete'),
                            'confirm' => [
                                'title' => __('Delete Account Store'),
                                'message' => __('Are you sure you want to delete this account store?'),
                            ],
                        ];
                    }
                }
            }
        }

        return $dataSource;
    }
}
