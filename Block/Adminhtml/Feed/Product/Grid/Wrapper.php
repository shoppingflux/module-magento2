<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Feed\Product\Grid;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\View\Element\Messages as MessagesBlock;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface as AccountStoreInterface;
use ShoppingFeed\Manager\Block\Adminhtml\Feed\Product\Grid as ProductGrid;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants;


class Wrapper extends Template
{
    /**
     * @var string
     */
    protected $_template = 'feed/product/grid/wrapper.phtml';

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var JsonSerializer
     */
    protected $jsonSerializer;

    /**
     * @var MessagesBlock|null
     */
    private $messagesBlock = null;

    /**
     * @var ProductGrid|null
     */
    private $gridBlock = null;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param JsonSerializer $jsonSerializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JsonSerializer $jsonSerializer,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->jsonSerializer = $jsonSerializer;
        parent::__construct($context, $data);
    }

    /**
     * @return AccountStoreInterface
     */
    public function getAccountStore()
    {
        return $this->coreRegistry->registry(RegistryConstants::CURRENT_ACCOUNT_STORE);
    }

    /**
     * @return MessagesBlock
     * @throws LocalizedException
     */
    public function getMessagesBlock()
    {
        if (null === $this->messagesBlock) {
            $this->messagesBlock = $this->getLayout()
                ->createBlock(
                    MessagesBlock::class,
                    'sfm.feed_product.grid.messages'
                );

            if ($this->getGridBlock()->hasLikelyUnsyncedProductList()) {
                $this->messagesBlock->addWarning(
                    implode(
                        "<br />",
                        [
                            __('It seems that the store product list is not in sync with the catalog product list.'),
                            __('Has the corresponding CLI command already been called?'),
                            __('Is there a corresponding cron job scheduled regularly?'),
                        ]
                    )
                );
            }
        }

        return $this->messagesBlock;
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getMessagesHtml()
    {
        return $this->getMessagesBlock()->toHtml();
    }

    /**
     * @return ProductGrid
     * @throws LocalizedException
     */
    public function getGridBlock()
    {
        if (null === $this->gridBlock) {
            $this->gridBlock = $this->getLayout()->createBlock(ProductGrid::class, 'sfm.feed_product.grid');
        }

        return $this->gridBlock;
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getGridHtml()
    {
        return $this->getGridBlock()->getHtml();
    }

    /**
     * @return string
     */
    public function getSelectedProductIdsJsonList()
    {
        return $this->jsonSerializer->serialize($this->getAccountStore()->getSelectedFeedProductIds());
    }
}
