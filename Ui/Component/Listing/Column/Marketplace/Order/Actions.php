<?php

namespace ShoppingFeed\Manager\Ui\Component\Listing\Column\Marketplace\Order;

use ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\OrderAction;
use ShoppingFeed\Manager\Ui\Component\Listing\Column\AbstractActions;


class Actions extends AbstractActions
{
    const ACL_VIEW_SALES_ORDER = 'Magento_Sales::actions_view';
    const ACL_RESET_IMPORT_TRY_COUNT = 'ShoppingFeed_Manager::marketplace_order_reset_import_try_count';

    const URL_PATH_VIEW_SALES_ORDER = 'sales/order/view';
    const URL_PATH_RESET_IMPORT_TRY_COUNT = 'shoppingfeed_manager/marketplace_order/resetImportTryCount';

    public function prepareDataSource(array $dataSource)
    {
        $isSalesOrderViewAllowed = $this->authorizationModel->isAllowed(static::ACL_VIEW_SALES_ORDER);
        $isResetImportAllowed = $this->authorizationModel->isAllowed(static::ACL_RESET_IMPORT_TRY_COUNT);

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['order_id'])) {
                    $item[$this->getData('name')] = [];
                    $salesOrderId = null;

                    if (isset($item['sales_order_id']) && $item['sales_order_id']) {
                        $salesOrderId = $item['sales_order_id'];
                    }

                    if ($salesOrderId && $isSalesOrderViewAllowed) {
                        $item[$this->getData('name')]['view_sales_order'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_VIEW_SALES_ORDER,
                                [ 'order_id' => $salesOrderId ]
                            ),
                            'label' => __('View Magento Order'),
                        ];
                    }

                    if (!$salesOrderId && $isResetImportAllowed) {
                        $item[$this->getData('name')]['reset_import_try_count'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_RESET_IMPORT_TRY_COUNT,
                                [ OrderAction::REQUEST_KEY_ORDER_ID => $item['order_id'] ]
                            ),
                            'label' => __('Reset Import Attempts'),
                            'confirm' => [
                                'title' => __('Reset Import Attempts'),
                                'message' => __('Are you sure you want to do this?'),
                            ],
                        ];
                    }
                }
            }
        }

        return $dataSource;
    }
}
