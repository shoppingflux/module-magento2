<?php

namespace ShoppingFeed\Manager\Ui\Component\Listing\Column\Account\Store;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Controller\Adminhtml\Account\StoreAction;
use ShoppingFeed\Manager\Controller\Adminhtml\AccountAction;
use ShoppingFeed\Manager\Ui\Component\Listing\Column\AbstractActions;

class Actions extends AbstractActions
{
    const ACL_EDIT = 'ShoppingFeed_Manager::account_store_edit';
    const ACL_DELETE = 'ShoppingFeed_Manager::account_store_delete';
    const ACL_UPDATE_TOKEN = 'ShoppingFeed_Manager::account_token_update';

    const URL_PATH_EDIT = 'shoppingfeed_manager/account_store/edit';
    const URL_PATH_DELETE = 'shoppingfeed_manager/account_store/delete';
    const URL_PATH_UPDATE_TOKEN = 'shoppingfeed_manager/account_token/form';

    public function prepareDataSource(array $dataSource)
    {
        $isEditAllowed = $this->authorizationModel->isAllowed(static::ACL_EDIT);
        $isDeleteAllowed = $this->authorizationModel->isAllowed(static::ACL_DELETE);
        $isTokenUpdateAllowed = $this->authorizationModel->isAllowed(static::ACL_UPDATE_TOKEN);

        if (!$isEditAllowed && !$isDeleteAllowed) {
            return $dataSource;
        }

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[StoreInterface::STORE_ID])) {
                    $name = $this->getData('name');
                    $item[$name] = [];

                    if ($isEditAllowed) {
                        $item[$name]['edit'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_EDIT,
                                [ StoreAction::REQUEST_KEY_STORE_ID => $item[StoreInterface::STORE_ID] ]
                            ),
                            'label' => __('Edit Configuration'),
                        ];
                    }

                    if ($isTokenUpdateAllowed) {
                        $item[$name]['update_token'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_UPDATE_TOKEN,
                                [ AccountAction::REQUEST_KEY_ACCOUNT_ID => $item[StoreInterface::ACCOUNT_ID] ]
                            ),
                            'label' => __('Update Token'),
                        ];
                    }

                    if ($isDeleteAllowed) {
                        $item[$name]['delete'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_DELETE,
                                [ StoreAction::REQUEST_KEY_STORE_ID => $item[StoreInterface::STORE_ID] ]
                            ),
                            'label' => __('Delete'),
                            'confirm' => [
                                'title' => __('Delete Account'),
                                'message' => __('Are you sure you want to delete this account?'),
                            ],
                        ];
                    }
                }
            }
        }

        return $dataSource;
    }
}
