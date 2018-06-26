<?php

namespace ShoppingFeed\Manager\Ui\Component\Listing\Column\Account;

use ShoppingFeed\Manager\Controller\Adminhtml\AccountAction;
use ShoppingFeed\Manager\Ui\Component\Listing\Column\AbstractActions;


class Actions extends AbstractActions
{
    const URL_PATH_DELETE = 'shoppingfeed_manager/account/delete';

    public function prepareDataSource(array $dataSource)
    {
        $isDeleteAllowed = $this->authorizationModel->isAllowed('ShoppingFeed_Manager::account_delete');

        if (!$isDeleteAllowed) {
            return $dataSource;
        }

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['account_id'])) {
                    $item[$this->getData('name')] = [
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_DELETE,
                                [ AccountAction::REQUEST_KEY_ACCOUNT_ID => $item['account_id'] ]
                            ),
                            'label' => __('Delete'),
                            'confirm' => [
                                'title' => __('Delete Account'),
                                'message' => __('Are you sure you want to delete this account?'),
                            ],
                        ],
                    ];
                }
            }
        }

        return $dataSource;
    }
}

