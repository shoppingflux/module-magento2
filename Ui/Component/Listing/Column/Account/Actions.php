<?php

namespace ShoppingFeed\Manager\Ui\Component\Listing\Column\Account;

use ShoppingFeed\Manager\Api\Data\AccountInterface;
use ShoppingFeed\Manager\Controller\Adminhtml\AccountAction;
use ShoppingFeed\Manager\Ui\Component\Listing\Column\AbstractActions;

class Actions extends AbstractActions
{
    const ACL_DELETE = 'ShoppingFeed_Manager::account_delete';
    const URL_PATH_DELETE = 'shoppingfeed_manager/account/delete';

    public function prepareDataSource(array $dataSource)
    {
        $isDeleteAllowed = $this->authorizationModel->isAllowed(static::ACL_DELETE);

        if (!$isDeleteAllowed) {
            return $dataSource;
        }

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[AccountInterface::ACCOUNT_ID])) {
                    $item[$this->getData('name')] = [
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_DELETE,
                                [ AccountAction::REQUEST_KEY_ACCOUNT_ID => $item[AccountInterface::ACCOUNT_ID] ]
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
