<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\Order;

use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\OrderAction;

class ResetImportTryCount extends OrderAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::marketplace_order_reset_import_try_count';

    public function execute()
    {
        try {
            $order = $this->getOrder();
            $order->resetImportRemainingTryCount();
            $this->orderRepository->save($order);
            $this->messageManager->addSuccessMessage(__('The order import attempts have been successfully reset.'));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This marketplace order does no longer exist.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('An error occurred while resetting the order import attempts.')
            );
        }

        $redirectResult = $this->resultRedirectFactory->create();
        return $redirectResult->setPath('*/*/');
    }
}
