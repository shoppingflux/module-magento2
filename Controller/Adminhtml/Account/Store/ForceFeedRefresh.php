<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Store;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface as FeedProductInterface;
use ShoppingFeed\Manager\Controller\Adminhtml\Account\StoreAction;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilterFactory as FeedSectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\ProductFilterFactory as FeedProductFilterFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Refresher as FeedRefresherResource;

class ForceFeedRefresh extends StoreAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_store_edit';

    /**
     * @var FeedProductFilterFactory
     */
    private $feedProductFilterFactory;

    /**
     * @var FeedSectionFilterFactory
     */
    private $feedSectionFilterFactory;

    /**
     * @var FeedRefresherResource
     */
    private $feedRefresherResource;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageResultFactory $pageResultFactory
     * @param StoreRepositoryInterface $storeRepository
     * @param FeedProductFilterFactory $feedProductFilterFactory
     * @param FeedSectionFilterFactory $feedSectionFilterFactory
     * @param FeedRefresherResource $feedRefresherResource
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageResultFactory $pageResultFactory,
        StoreRepositoryInterface $storeRepository,
        FeedProductFilterFactory $feedProductFilterFactory,
        FeedSectionFilterFactory $feedSectionFilterFactory,
        FeedRefresherResource $feedRefresherResource
    ) {
        $this->feedProductFilterFactory = $feedProductFilterFactory;
        $this->feedSectionFilterFactory = $feedSectionFilterFactory;
        $this->feedRefresherResource = $feedRefresherResource;
        parent::__construct($context, $coreRegistry, $pageResultFactory, $storeRepository);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        $redirectResult = $this->resultRedirectFactory->create();
        $redirectResult->setPath('*/*/');

        try {
            $store = $this->getStore();

            $productFilter = $this->feedProductFilterFactory
                ->create()
                ->setStoreIds([ $store->getId() ]);

            if (!empty($params['export_state'])) {
                $this->feedRefresherResource->forceProductExportStateRefresh(
                    FeedProductInterface::REFRESH_STATE_REQUIRED,
                    $productFilter
                );
            }

            if (!empty($params['section_type'])) {
                $sectionsFilter = $this->feedSectionFilterFactory
                    ->create()
                    ->setStoreIds([ $store->getId() ]);

                if ('all' !== $params['section_type']) {
                    $sectionsFilter->setTypeIds([ (int) $params['section_type'] ]);
                }

                $this->feedRefresherResource->forceProductSectionRefresh(
                    FeedProductInterface::REFRESH_STATE_REQUIRED,
                    $sectionsFilter,
                    $productFilter
                );
            }

            $this->messageManager->addSuccessMessage(__('The requested feed data has been flagged for refresh.'));

            $redirectResult->setPath('*/*/edit', [ 'store_id' => $store->getId() ]);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This account does no longer exist.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('An error occurred while flagging the feed data for refresh.')
            );
        }

        return $redirectResult;
    }
}
