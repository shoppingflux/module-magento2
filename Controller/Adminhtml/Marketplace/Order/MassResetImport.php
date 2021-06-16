<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\Order;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use Magento\Ui\Component\MassAction\Filter as MassActionFilter;
use Psr\Log\LoggerInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface;
use ShoppingFeed\Manager\Api\Marketplace\OrderRepositoryInterface;
use ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\OrderAction;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\CollectionFactory as OrderCollectionFactory;

class MassResetImport extends OrderAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::marketplace_order_reset_import_try_count';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MassActionFilter
     */
    private $massActionFilter;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param PageResultFactory $pageResultFactory
     * @param MassActionFilter $massActionFilter
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderCollectionFactory $orderCollectionFactory
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        PageResultFactory $pageResultFactory,
        MassActionFilter $massActionFilter,
        OrderRepositoryInterface $orderRepository,
        OrderCollectionFactory $orderCollectionFactory
    ) {
        $this->logger = $logger;
        $this->massActionFilter = $massActionFilter;
        $this->orderCollectionFactory = $orderCollectionFactory;
        parent::__construct($context, $pageResultFactory, $orderRepository);
    }

    public function execute()
    {
        try {
            $orderCollection = $this->massActionFilter->getCollection($this->orderCollectionFactory->create());

            $errorCount = 0;
            $resetCount = 0;
            $importedCount = 0;

            /** @var OrderInterface $order */
            foreach ($orderCollection as $order) {
                try {
                    if (!$order->getSalesOrderId()) {
                        $order->resetImportRemainingTryCount();
                        $this->orderRepository->save($order);
                        ++$resetCount;
                    } else {
                        ++$importedCount;
                    }
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage(), $e->getTrace());
                    ++$errorCount;
                }
            }

            $unknownCount = $orderCollection->count() - $resetCount - $importedCount - $errorCount;

            if ($resetCount > 0) {
                $this->messageManager->addSuccessMessage(
                    __('The import attempts of %1 orders have been reset.', $resetCount)
                );
            }

            if ($errorCount > 0) {
                $this->messageManager->addErrorMessage(
                    __('The import attempts of %1 orders could not be reset (see error log for details).', $errorCount)
                );
            }

            if ($importedCount > 0) {
                $this->messageManager->addNoticeMessage(
                    __('%1 orders have already been imported.', $importedCount)
                );
            }

            if ($unknownCount > 0) {
                $this->messageManager->addWarningMessage(
                    __('%1 orders could not be found.', $unknownCount)
                );
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('An error occurred while resetting the orders import attempts.')
            );
        }

        $redirectResult = $this->resultRedirectFactory->create();

        return $redirectResult->setPath('*/*/');
    }
}
