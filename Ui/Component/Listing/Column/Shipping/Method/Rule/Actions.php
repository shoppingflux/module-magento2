<?php

namespace ShoppingFeed\Manager\Ui\Component\Listing\Column\Shipping\Method\Rule;

use ShoppingFeed\Manager\Api\Data\Shipping\Method\RuleInterface;
use ShoppingFeed\Manager\Controller\Adminhtml\Shipping\Method\RuleAction;
use ShoppingFeed\Manager\Ui\Component\Listing\Column\AbstractActions;

class Actions extends AbstractActions
{
    const ACL_EDIT = 'ShoppingFeed_Manager::shipping_method_rule_edit';
    const ACL_DELETE = 'ShoppingFeed_Manager::shipping_method_rule_delete';

    const URL_PATH_EDIT = 'shoppingfeed_manager/shipping_method_rule/edit';
    const URL_PATH_DELETE = 'shoppingfeed_manager/shipping_method_rule/delete';

    public function prepareDataSource(array $dataSource)
    {
        $isEditAllowed = $this->authorizationModel->isAllowed(static::ACL_EDIT);
        $isDeleteAllowed = $this->authorizationModel->isAllowed(static::ACL_DELETE);

        if (!$isEditAllowed && !$isDeleteAllowed) {
            return $dataSource;
        }

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[RuleInterface::RULE_ID])) {
                    $name = $this->getData('name');
                    $item[$name] = [];

                    if ($isEditAllowed) {
                        $item[$name]['edit'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_EDIT,
                                [ RuleAction::REQUEST_KEY_RULE_ID => $item[RuleInterface::RULE_ID] ]
                            ),
                            'label' => __('Edit'),
                        ];
                    }

                    if ($isDeleteAllowed) {
                        $item[$name]['delete'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_DELETE,
                                [ RuleAction::REQUEST_KEY_RULE_ID => $item[RuleInterface::RULE_ID] ]
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
