<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\Order;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface;
use ShoppingFeed\Manager\Api\Marketplace\OrderRepositoryInterface;
use ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\OrderAction;
use ShoppingFeed\Manager\Model\Marketplace\Order\Manager as MarketplaceOrderManager;
use ShoppingFeed\Manager\Model\Marketplace\Order\Importer as MarketplaceOrderImporter;
use ShoppingFeed\Manager\Ui\DataProvider\Marketplace\Order\Fetch\Form\DataProvider;

class Fetch extends OrderAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::marketplace_order_fetch';

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var MarketplaceOrderManager
     */
    private $marketplaceOrderManager;

    /**
     * @var MarketplaceOrderImporter
     */
    private $marketplaceOrderImporter;

    /**
     * @param Context $context
     * @param PageResultFactory $pageResultFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param StoreRepositoryInterface $storeRepository
     * @param MarketplaceOrderManager $marketplaceOrderManager
     * @param MarketplaceOrderImporter $marketplaceOrderImporter
     */
    public function __construct(
        Context $context,
        PageResultFactory $pageResultFactory,
        OrderRepositoryInterface $orderRepository,
        StoreRepositoryInterface $storeRepository,
        MarketplaceOrderManager $marketplaceOrderManager,
        MarketplaceOrderImporter $marketplaceOrderImporter
    ) {
        $this->storeRepository = $storeRepository;
        $this->marketplaceOrderManager = $marketplaceOrderManager;
        $this->marketplaceOrderImporter = $marketplaceOrderImporter;
        parent::__construct($context, $pageResultFactory, $orderRepository);
    }

    public function execute()
    {
        $redirectResult = $this->resultRedirectFactory->create();

        if (is_array($orderData = $this->getRequest()->getParam(DataProvider::DATA_SCOPE_ORDER))) {
            try {
                if (
                    empty($orderData[DataProvider::FIELD_STORE_ID])
                    || empty($orderData[DataProvider::FIELD_CHANNEL_ID])
                    || empty($orderData[DataProvider::FIELD_ORDER_REFERENCE])
                ) {
                    throw new LocalizedException(__('Invalid request.'));
                }

                $store = $this->storeRepository->getById((int) $orderData[DataProvider::FIELD_STORE_ID] ?? 0);

                $apiOrder = $this->marketplaceOrderManager->getStoreImportableApiOrderByChannelAndReference(
                    $store,
                    (int) $orderData[DataProvider::FIELD_CHANNEL_ID],
                    trim($orderData[DataProvider::FIELD_ORDER_REFERENCE])
                );

                if (null === $apiOrder) {
                    throw new LocalizedException(
                        __('This marketplace order does not exist or has already been imported.')
                    );
                }

                $this->marketplaceOrderImporter->importApiOrder($apiOrder, $store, false);

                $this->messageManager->addSuccessMessage(__('The order has been successfully fetched.'));

                return $redirectResult->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addExceptionMessage($e, $e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('An error occurred while fetching the order.'));
            }

            return $redirectResult->setPath('*/*/fetch_form');
        }
    }
}
