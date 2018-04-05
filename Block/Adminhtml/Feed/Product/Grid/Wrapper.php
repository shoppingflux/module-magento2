<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Feed\Product\Grid;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
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
        return '[]'; // @todo
    }
}
