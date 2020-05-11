<?php

namespace ShoppingFeed\Manager\Ui\Component\Listing\Column\Marketplace\Order;

use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface;
use ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\OrderAction;
use ShoppingFeed\Manager\Ui\Component\Listing\Column\AbstractActions;

class Actions extends AbstractActions
{
    const ACL_VIEW_SALES_ORDER = 'Magento_Sales::actions_view';
    const ACL_IMPORT = 'ShoppingFeed_Manager::marketplace_order_import';
    const ACL_CANCEL_IMPORT = 'ShoppingFeed_Manager::marketplace_order_cancel_import';
    const ACL_RESET_IMPORT_TRY_COUNT = 'ShoppingFeed_Manager::marketplace_order_reset_import_try_count';

    const URL_PATH_VIEW_SALES_ORDER = 'sales/order/view';
    const URL_PATH_IMPORT = 'shoppingfeed_manager/marketplace_order/import';
    const URL_PATH_CANCEL_IMPORT = 'shoppingfeed_manager/marketplace_order/cancelImport';
    const URL_PATH_RESET_IMPORT_TRY_COUNT = 'shoppingfeed_manager/marketplace_order/resetImportTryCount';

    public function prepareDataSource(array $dataSource)
    {
        $isSalesOrderViewAllowed = $this->authorizationModel->isAllowed(static::ACL_VIEW_SALES_ORDER);
        $isImportAllowed = $this->authorizationModel->isAllowed(static::ACL_IMPORT);
        $isCancelImportAllowed = $this->authorizationModel->isAllowed(static::ACL_CANCEL_IMPORT);
        $isResetImportAllowed = $this->authorizationModel->isAllowed(static::ACL_RESET_IMPORT_TRY_COUNT);

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[OrderInterface::ORDER_ID])) {
                    $item[$this->getData('name')] = [];
                    $salesOrderId = null;

                    if (isset($item[OrderInterface::SALES_ORDER_ID]) && $item[OrderInterface::SALES_ORDER_ID]) {
                        $salesOrderId = $item[OrderInterface::SALES_ORDER_ID];
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

                    if (!$salesOrderId) {
                        if ($isImportAllowed) {
                            $item[$this->getData('name')]['import'] = [
                                'href' => $this->urlBuilder->getUrl(
                                    static::URL_PATH_IMPORT,
                                    [ OrderAction::REQUEST_KEY_ORDER_ID => $item[OrderInterface::ORDER_ID] ]
                                ),
                                'label' => __('Import'),
                                'confirm' => [
                                    'title' => __('Import'),
                                    'message' => __('Are you sure you want to do this?'),
                                ],
                            ];
                        }

                        if ($isCancelImportAllowed) {
                            $item[$this->getData('name')]['cancel_import'] = [
                                'href' => $this->urlBuilder->getUrl(
                                    static::URL_PATH_CANCEL_IMPORT,
                                    [ OrderAction::REQUEST_KEY_ORDER_ID => $item[OrderInterface::ORDER_ID] ]
                                ),
                                'label' => __('Cancel Import'),
                                'confirm' => [
                                    'title' => __('Cancel Import'),
                                    'message' => __('Are you sure you want to do this?'),
                                ],
                            ];
                        }

                        if ($isResetImportAllowed) {
                            $item[$this->getData('name')]['reset_import_try_count'] = [
                                'href' => $this->urlBuilder->getUrl(
                                    static::URL_PATH_RESET_IMPORT_TRY_COUNT,
                                    [ OrderAction::REQUEST_KEY_ORDER_ID => $item[OrderInterface::ORDER_ID] ]
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
        }

        return $dataSource;
    }
}
