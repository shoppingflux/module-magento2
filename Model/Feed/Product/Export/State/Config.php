<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Export\State;

use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Ui\Component\Form\Element\DataType\Text as UiText;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Account\Store\Config\Field\MultiSelect;
use ShoppingFeed\Manager\Model\Account\Store\Config\Field\Select;
use ShoppingFeed\Manager\Model\Account\Store\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler\Option as OptionHandler;
use ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler\PositiveInteger as PositiveIntegerHandler;
use ShoppingFeed\Manager\Model\Feed\Exporter as FeedExporter;
use ShoppingFeed\Manager\Model\Feed\Product\RefreshableConfig;


class Config extends RefreshableConfig implements ConfigInterface
{
    const SUB_SCOPE = 'export_state';

    const KEY_EXPORT_SELECTED_ONLY = 'export_selected_only';
    const KEY_EXPORTED_VISIBILITIES = 'exported_visibilities';
    const KEY_EXPORT_OUT_OF_STOCK = 'export_out_of_stock';
    const KEY_EXPORT_NOT_SALABLE = 'export_not_salable';
    const KEY_CHILDREN_EXPORT_MODE = 'children_export_mode';
    const KEY_RETAIN_PREVIOUSLY_EXPORTED = 'retain_previously_exported';
    const KEY_PREVIOUSLY_EXPORTED_RETENTION_DURATION = 'previously_exported_retention_duration';

    /**
     * @var ProductVisibility
     */
    private $productVisibility;

    /**
     * @param ProductVisibility $productVisibility
     */
    public function __construct(ProductVisibility $productVisibility)
    {
        $this->productVisibility = $productVisibility;
    }

    final public function getScopeSubPath()
    {
        return [ self::SUB_SCOPE ];
    }

    protected function getBaseFields()
    {
        return array_merge(
            [
                new Checkbox(
                    self::KEY_EXPORT_SELECTED_ONLY,
                    __('Export Only Selected Products')
                ),

                new MultiSelect(
                    self::KEY_EXPORTED_VISIBILITIES,
                    new OptionHandler(UiText::NAME, $this->productVisibility->getAllOptions()),
                    __('Export Products Visible in'),
                    true,
                    $this->productVisibility->getVisibleInSiteIds(),
                    $this->productVisibility->getVisibleInSiteIds(),
                    '',
                    [],
                    4
                ),

                new Checkbox(
                    self::KEY_EXPORT_OUT_OF_STOCK,
                    __('Export Out of Stock Products')
                ),

                new Checkbox(
                    self::KEY_EXPORT_NOT_SALABLE,
                    __('Export Not Salable Products')
                ),

                new Select(
                    self::KEY_CHILDREN_EXPORT_MODE,
                    new OptionHandler(
                        UiText::NAME,
                        [
                            [
                                'value' => FeedExporter::CHILDREN_EXPORT_MODE_NONE,
                                'label' => __('No'),
                            ],
                            [
                                'value' => FeedExporter::CHILDREN_EXPORT_MODE_SEPARATELY,
                                'label' => __('Separately'),
                            ],
                            [
                                'value' => FeedExporter::CHILDREN_EXPORT_MODE_WITHIN_PARENTS,
                                'label' => __('Within Parents'),
                            ],
                            [
                                'value' => FeedExporter::CHILDREN_EXPORT_MODE_BOTH,
                                'label' => __('Separately and Within Parents'),
                            ],
                        ],
                        true
                    ),
                    __('Export Child Products'),
                    true,
                    FeedExporter::CHILDREN_EXPORT_MODE_WITHIN_PARENTS,
                    FeedExporter::CHILDREN_EXPORT_MODE_WITHIN_PARENTS
                ),

                new Checkbox(
                    self::KEY_RETAIN_PREVIOUSLY_EXPORTED,
                    __('Retain Previously Exported Products'),
                    true,
                    '',
                    '',
                    [ self::KEY_PREVIOUSLY_EXPORTED_RETENTION_DURATION ]
                ),

                new TextBox(
                    self::KEY_PREVIOUSLY_EXPORTED_RETENTION_DURATION,
                    new PositiveIntegerHandler(),
                    __('Previously Exported Products Retention Duration'),
                    true,
                    48,
                    __('In hours.')
                ),
            ],
            parent::getBaseFields()
        );
    }

    public function getFieldsetLabel()
    {
        return __('Feed - Exportable Products');
    }

    public function shouldExportSelectedOnly(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_EXPORT_SELECTED_ONLY);
    }

    public function getExportedVisibilities(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_EXPORTED_VISIBILITIES);
    }

    public function shouldExportOutOfStock(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_EXPORT_OUT_OF_STOCK);
    }

    public function shouldExportNotSalable(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_EXPORT_NOT_SALABLE);
    }

    public function getChildrenExportMode(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_CHILDREN_EXPORT_MODE);
    }

    public function shouldRetainPreviouslyExported(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_RETAIN_PREVIOUSLY_EXPORTED);
    }

    public function getPreviouslyExportedRetentionDuration(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_PREVIOUSLY_EXPORTED_RETENTION_DURATION) * 3600;
    }
}
