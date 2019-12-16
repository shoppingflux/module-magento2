<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Feed\Product;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\Data\ProductInterface as CatalogProductInterface;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Api\Data\Feed\Product\SectionInterface as ProductSectionInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractType as AbstractSectionType;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Refresh\State\Source as RefreshStateSource;
use ShoppingFeed\Manager\Model\LabelledValue;

class Sections extends Template
{
    /**
     * @var SectionTypePoolInterface
     */
    private $sectionTypePool;

    /**
     * @var string[]
     */
    private $refreshStateLabels;

    /**
     * @var StoreInterface
     */
    private $store = null;

    /**
     * @var CatalogProductInterface|null
     */
    private $catalogProduct = null;

    /**
     * @var ProductSectionInterface[]
     */
    private $feedProductSections = [];

    /**
     * @param Context $context
     * @param SectionTypePoolInterface $sectionTypePool
     * @param RefreshStateSource $refreshStateSource
     * @param array $data
     */
    public function __construct(
        Context $context,
        SectionTypePoolInterface $sectionTypePool,
        RefreshStateSource $refreshStateSource,
        array $data = []
    ) {
        $this->sectionTypePool = $sectionTypePool;
        $this->refreshStateLabels = $refreshStateSource->toOptionHash();
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ShoppingFeed_Manager::feed/product/sections.phtml');
    }

    /**
     * @return StoreInterface
     * @throws LocalizedException
     */
    public function getStore()
    {
        if (!$this->store instanceof StoreInterface) {
            throw new LocalizedException(__('No store defined in %1.', static::class));
        }

        return $this->store;
    }

    /**
     * @param StoreInterface $store
     */
    public function setStore(StoreInterface $store)
    {
        $this->store = $store;
    }

    /**
     * @return CatalogProductInterface
     * @throws LocalizedException
     */
    public function getCatalogProduct()
    {
        if (!$this->catalogProduct instanceof CatalogProductInterface) {
            throw new LocalizedException(__('No catalog product defined in %1.', static::class));
        }

        return $this->catalogProduct;
    }

    /**
     * @param CatalogProductInterface $product
     */
    public function setCatalogProduct(CatalogProductInterface $product)
    {
        $this->catalogProduct = $product;
    }

    /**
     * @return AbstractSectionType[]
     */
    public function getFeedProductSectionTypes()
    {
        return $this->sectionTypePool->getSortedTypes();
    }

    /**
     * @return ProductSectionInterface[]
     */
    public function getFeedProductSections()
    {
        return $this->feedProductSections;
    }

    /**
     * @param ProductSectionInterface[] $sections
     */
    public function setFeedProductSections(array $sections)
    {
        $this->feedProductSections = array_filter(
            $sections,
            function ($section) {
                return $section instanceof ProductSectionInterface;
            }
        );
    }

    /**
     * @param AbstractSectionType $sectionType
     * @return ProductSectionInterface|null
     */
    public function getFeedProductSection(AbstractSectionType $sectionType)
    {
        $sectionTypeId = $sectionType->getId();

        return !isset($this->feedProductSections[$sectionTypeId])
            ? null
            : $this->feedProductSections[$sectionTypeId];
    }

    /**
     * @param AbstractSectionType $sectionType
     * @return LabelledValue[]
     */
    public function getFeedProductSectionData(AbstractSectionType $sectionType)
    {
        $section = $this->getFeedProductSection($sectionType);

        return (null === $section)
            ? []
            : $sectionType->getAdapter()
                ->describeProductData($this->getStore(), $section->getFeedData());
    }

    /**
     * @param int $refreshState
     * @return string
     */
    public function getRefreshStateLabel($refreshState)
    {
        return !isset($this->refreshStateLabels[$refreshState])
            ? (string) __('Unknown')
            : $this->refreshStateLabels[$refreshState];
    }

    /**
     * @param string $date
     * @return string
     */
    public function getFormattedDate($date)
    {
        $date = trim($date);
        return empty($date) ? __('Never') : $this->formatDate($date, \IntlDateFormatter::SHORT, true, 'UTC');
    }
}
