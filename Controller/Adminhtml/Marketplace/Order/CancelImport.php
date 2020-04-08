<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\Order;

use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\OrderAction;

class CancelImport extends OrderAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::marketplace_order_cancel_import';

    public function execute()
    {
        try {
            $order = $this->getOrder();
            $order->setImportRemainingTryCount(0);
            $this->orderRepository->save($order);
            $this->messageManager->addSuccessMessage(__('The order import has been successfully cancelled.'));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This marketplace order does no longer exist.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('An error occurred while cancelling the order import.')
            );
        }

        $redirectResult = $this->resultRedirectFactory->create();
        return $redirectResult->setPath('*/*/');
    }
}
