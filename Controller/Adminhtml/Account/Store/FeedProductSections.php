<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Store;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface as CatalogProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Controller\Result\RawFactory as RawResultFactory;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface;
use ShoppingFeed\Manager\Block\Adminhtml\Feed\Product\Sections as FeedProductSectionsBlock;
use ShoppingFeed\Manager\Controller\Adminhtml\Account\StoreAction;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Section as FeedProductSectionResource;

class FeedProductSections extends StoreAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_store_edit';
    const REQUEST_KEY_PRODUCT_ID = 'product_id';
    
    /**
     * @var RawResultFactory
     */
    private $rawResultFactory;

    /**
     * @var LayoutFactory
     */
    private $layoutFactory;

    /**
     * @var CatalogProductRepositoryInterface
     */
    private $catalogProductRepository;

    /**
     * @var FeedProductSectionResource
     */
    private $feedProductSectionResource;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param RawResultFactory $rawResultFactory
     * @param PageResultFactory $pageResultFactory
     * @param LayoutFactory $layoutFactory
     * @param StoreRepositoryInterface $storeRepository
     * @param CatalogProductRepositoryInterface $catalogProductRepository
     * @param FeedProductSectionResource $feedProductSectionResource
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        RawResultFactory $rawResultFactory,
        PageResultFactory $pageResultFactory,
        LayoutFactory $layoutFactory,
        StoreRepositoryInterface $storeRepository,
        CatalogProductRepositoryInterface $catalogProductRepository,
        FeedProductSectionResource $feedProductSectionResource
    ) {
        $this->rawResultFactory = $rawResultFactory;
        $this->layoutFactory = $layoutFactory;
        $this->catalogProductRepository = $catalogProductRepository;
        $this->feedProductSectionResource = $feedProductSectionResource;
        parent::__construct($context, $coreRegistry, $pageResultFactory, $storeRepository);
    }

    public function execute()
    {
        $layout = $this->layoutFactory->create();
        $messagesBlock = $layout->getMessagesBlock();

        try {
            $store = $this->getStore();
        } catch (NoSuchEntityException $e) {
            $store = null;
            $messagesBlock->addError(__('This account does no longer exist.'));
        }

        $this->coreRegistry->register(RegistryConstants::CURRENT_ACCOUNT_STORE, $store);

        if ($store) {
            try {
                $product = $this->catalogProductRepository->getById(
                    (int) $this->getRequest()->getParam(static::REQUEST_KEY_PRODUCT_ID),
                    false,
                    $store->getBaseStoreId(),
                    false
                );
            } catch (NoSuchEntityException $e) {
                $product = null;
                $messagesBlock->addError(__('This product does no longer exist.'));
            }
        } else {
            $product = null;
        }


        $rawResult = $this->rawResultFactory->create();

        if ($store && $product) {
            /** @var FeedProductSectionsBlock $sectionsBlock */
            $sectionsBlock = $layout->createBlock(FeedProductSectionsBlock::class);
            $sectionsBlock->setStore($store);
            $sectionsBlock->setCatalogProduct($product);

            try {
                $sections = $this->feedProductSectionResource->getProductSections($product->getId(), $store->getId());
                $sectionsBlock->setFeedProductSections($sections);
                $rawResult->setContents($sectionsBlock->toHtml());
                return $rawResult;
            } catch (LocalizedException $e) {
                $messagesBlock->addError($e->getMessage());
            }
        }

        $rawResult->setContents($messagesBlock->getGroupedHtml());

        return $rawResult;
    }
}
