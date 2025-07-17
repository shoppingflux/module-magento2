<?php

namespace ShoppingFeed\Manager\Observer;

use Magento\Catalog\Helper\Product\Edit\Action\Attribute as AttributeActionHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use ShoppingFeed\Manager\Block\Adminhtml\Catalog\Product\Edit\Action\Attribute\Tab\FeedAttributes as FeedAttributesTab;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\ProductFactory as FeedProductResourceFactory;

class SaveProductsAttributesObserver implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * @var FeedProductResourceFactory
     */
    private $feedProductResourceFactory;

    /**
     * @var AttributeActionHelper
     */
    private $attributeActionHelper;

    /**
     * @var bool
     */
    private $hasSavedProductAttributes = false;

    /**
     * @param RequestInterface $request
     * @param FeedProductResourceFactory $feedProductResourceFactory
     * @param AttributeActionHelper $attributeActionHelper
     * @param MessageManagerInterface|null $messageManager
     */
    public function __construct(
        RequestInterface $request,
        FeedProductResourceFactory $feedProductResourceFactory,
        AttributeActionHelper $attributeActionHelper,
        ?MessageManagerInterface $messageManager = null
    ) {
        $this->request = $request;
        $this->feedProductResourceFactory = $feedProductResourceFactory;
        $this->attributeActionHelper = $attributeActionHelper;
        $this->messageManager = $messageManager ?? ObjectManager::getInstance()->get(MessageManagerInterface::class);
    }

    public function execute(Observer $observer)
    {
        /**
         * Implementing async update while preserving compatibility with all M2.3 and M2.4 versions is impossible,
         * unless resorting to dirty hacks (message queues configuration has significantly changed between versions).
         */

        $params = $this->request->getParams();
        $productIds = $this->attributeActionHelper->getProductIds();

        if (
            !$this->hasSavedProductAttributes
            && isset($params[FeedAttributesTab::DATA_SCOPE])
            && is_array($params[FeedAttributesTab::DATA_SCOPE])
            && !empty($productIds)
        ) {
            $this->hasSavedProductAttributes = true;
            $productIdsChunks = array_chunk($productIds, 500);
            $feedProductResource = $this->feedProductResourceFactory->create();

            foreach ($params[FeedAttributesTab::DATA_SCOPE] as $storeId => $storeFeedAttributes) {
                foreach ($productIdsChunks as $chunkIds) {
                    $feedProductResource->updateProductFeedAttributes(
                        $chunkIds,
                        (int) $storeId,
                        $storeFeedAttributes[FeedAttributesTab::FIELD_IS_SELECTED] ?? null,
                        $storeFeedAttributes[FeedAttributesTab::FIELD_SELECTED_CATEGORY_ID] ?? null
                    );
                }
            }

            $this->messageManager->addSuccessMessage(
                __('The Shopping Feed product attributes have been saved.')
            );
        }
    }
}
