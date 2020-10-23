<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\Order;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface;
use ShoppingFeed\Manager\Api\Marketplace\OrderRepositoryInterface;
use ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\OrderAction;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as SalesOrderImporterInterface;

class Import extends OrderAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::marketplace_order_import';

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var SalesOrderImporterInterface
     */
    private $salesOrderImporter;

    /**
     * @param Context $context
     * @param PageResultFactory $pageResultFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param StoreRepositoryInterface $storeRepository
     * @param SalesOrderImporterInterface $salesOrderImporter
     */
    public function __construct(
        Context $context,
        PageResultFactory $pageResultFactory,
        OrderRepositoryInterface $orderRepository,
        StoreRepositoryInterface $storeRepository,
        SalesOrderImporterInterface $salesOrderImporter
    ) {
        $this->storeRepository = $storeRepository;
        $this->salesOrderImporter = $salesOrderImporter;
        parent::__construct($context, $pageResultFactory, $orderRepository);
    }

    public function execute()
    {
        try {
            $order = $this->getOrder();

            if ($order->getSalesOrderId()) {
                throw new LocalizedException(__('This order has already been imported.'));
            }

            $this->salesOrderImporter->importStoreOrders(
                [ $order ],
                $this->storeRepository->getById($order->getStoreId())
            );

            $this->messageManager->addSuccessMessage(__('The order has been successfully imported.'));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This marketplace order does no longer exist.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('An error occurred while importing the order.')
            );
        }

        $redirectResult = $this->resultRedirectFactory->create();

        return $redirectResult->setPath('*/*/');
    }
}
