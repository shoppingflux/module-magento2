<?php

namespace ShoppingFeed\Manager\Ui\Component\Listing\Column\Shipping\Method\Rule;

use ShoppingFeed\Manager\Controller\Adminhtml\Shipping\Method\RuleAction;
use ShoppingFeed\Manager\Ui\Component\Listing\Column\AbstractActions;


class Actions extends AbstractActions
{
    const URL_PATH_EDIT = 'shoppingfeed_manager/shipping_method_rule/edit';
    const URL_PATH_DELETE = 'shoppingfeed_manager/shipping_method_rule/delete';

    public function prepareDataSource(array $dataSource)
    {
        $isEditAllowed = $this->authorizationModel->isAllowed('ShoppingFeed_Manager::shipping_method_rule_edit');
        $isDeleteAllowed = $this->authorizationModel->isAllowed('ShoppingFeed_Manager::shipping_method_rule_delete');

        if (!$isEditAllowed && !$isDeleteAllowed) {
            return $dataSource;
        }

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['rule_id'])) {
                    $name = $this->getData('name');
                    $item[$name] = [];

                    if ($isEditAllowed) {
                        $item[$name]['edit'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_EDIT,
                                [ RuleAction::REQUEST_KEY_RULE_ID => $item['rule_id'] ]
                            ),
                            'label' => __('Edit'),
                        ];
                    }

                    if ($isDeleteAllowed) {
                        $item[$name]['delete'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_DELETE,
                                [ RuleAction::REQUEST_KEY_RULE_ID => $item['rule_id'] ]
                            ),
                            'label' => __('Delete'),
                            'confirm' => [
                                'title' => __('Delete Shipping Method Rule'),
                                'message' => __('Are you sure you want to delete this shipping method rule?'),
                            ],
                        ];
                    }
                }
            }
        }

        return $dataSource;
    }
}
