<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Store;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory as RawResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface;
use ShoppingFeed\Manager\Block\Adminhtml\Feed\Product\Grid as ProductGridBlock;
use ShoppingFeed\Manager\Controller\Adminhtml\Account\StoreAction;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants;


class FeedProductGrid extends StoreAction
{
    /**
     * @var RawResultFactory
     */
    private $rawResultFactory;

    /**
     * @var LayoutFactory
     */
    private $layoutFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param StoreRepositoryInterface $storeRepository
     * @param RawResultFactory $rawResultFactory
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        StoreRepositoryInterface $storeRepository,
        RawResultFactory $rawResultFactory,
        LayoutFactory $layoutFactory
    ) {
        $this->rawResultFactory = $rawResultFactory;
        $this->layoutFactory = $layoutFactory;
        parent::__construct($context, $coreRegistry, $resultPageFactory, $storeRepository);
    }

    public function execute()
    {
        try {
            $store = $this->getStore();
            $this->coreRegistry->register(RegistryConstants::CURRENT_ACCOUNT_STORE, $store);

            $layout = $this->layoutFactory->create();
            $rawResult = $this->rawResultFactory->create();
            $productGridBlock = $layout->createBlock(ProductGridBlock::class, 'sfm.feed_product.grid');

            return $rawResult->setContents($productGridBlock->toHtml());
        } catch (NoSuchEntityException $e) {
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/', [ '_current' => true, 'store_id' => null ]);
        }
    }
}
