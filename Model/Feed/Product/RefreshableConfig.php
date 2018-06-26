<?php

namespace ShoppingFeed\Manager\Model\Feed\Product;

use Magento\Ui\Component\Form\Element\DataType\Number as UiNumber;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Config\Field\Select;
use ShoppingFeed\Manager\Model\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Option as OptionHandler;
use ShoppingFeed\Manager\Model\Config\Value\Handler\PositiveInteger as PositiveIntegerHandler;
use ShoppingFeed\Manager\Model\Feed\AbstractConfig;
use ShoppingFeed\Manager\Model\Feed\Product as FeedProduct;
use ShoppingFeed\Manager\Model\Feed\Refresher as FeedRefresher;


abstract class RefreshableConfig extends AbstractConfig implements RefreshableConfigInterface
{
    const KEY_FORCE_PRODUCT_LOAD_FOR_REFRESH = 'force_product_load_for_refresh';
    const KEY_AUTOMATIC_REFRESH_STATE = 'automatic_refresh_state';
    const KEY_AUTOMATIC_REFRESH_DELAY = 'automatic_refresh_delay';
    const KEY_ENABLE_ADVISED_REFRESH_REQUIREMENT = 'enable_advised_refresh_requirement';
    const KEY_ADVISED_REFRESH_REQUIREMENT_DELAY = 'advised_refresh_requirement_delay';

    protected function getBaseFields()
    {
        // Note: we can not use big sort orders here because each index between 1 and the defined value will
        // actually be tested on the browser side, multiple times.

        return [
            $this->fieldFactory->create(
                Checkbox::TYPE_CODE,
                [
                    'name' => self::KEY_FORCE_PRODUCT_LOAD_FOR_REFRESH,
                    'label' => __('Force Product Load for Refresh'),
                    'sortOrder' => 100010,
                ]
            ),

            $this->fieldFactory->create(
                Select::TYPE_CODE,
                [
                    'name' => self::KEY_AUTOMATIC_REFRESH_STATE,
                    'valueHandler' => $this->valueHandlerFactory->create(
                        OptionHandler::TYPE_CODE,
                        [
                            'dataType' => UiNumber::NAME,
                            'hasEmptyOption' => true,
                            'optionArray' => [
                                [ 'value' => '', 'label' => __('No') ],
                                [ 'value' => FeedProduct::REFRESH_STATE_ADVISED, 'label' => __('Advised') ],
                                [ 'value' => FeedProduct::REFRESH_STATE_REQUIRED, 'label' => __('Required') ],
                            ],
                        ]
                    ),
                    'defaultFormValue' => '',
                    'defaultUseValue' => '',
                    'label' => __('Force Automatic Refresh'),
                    'dependencies' => [
                        [
                            'values' => [
                                FeedProduct::REFRESH_STATE_ADVISED,
                                FeedProduct::REFRESH_STATE_REQUIRED,
                            ],
                            'fieldNames' => [ self::KEY_AUTOMATIC_REFRESH_DELAY ],
                        ],
                    ],
                    'sortOrder' => 100020,
                ]
            ),

            $this->fieldFactory->create(
                TextBox::TYPE_CODE,
                [
                    'name' => self::KEY_AUTOMATIC_REFRESH_DELAY,
                    'valueHandler' => $this->valueHandlerFactory->create(PositiveIntegerHandler::TYPE_CODE),
                    'isRequired' => true,
                    'label' => __('Force Automatic Refresh After'),
                    'notice' => __('In minutes.'),
                    'sortOrder' => 100030,
                ]
            ),

            // @todo (needs specific filters)
            /*
            $this->fieldFactory->create(
                Checkbox::TYPE_CODE,
                [
                    'name' => self::KEY_ENABLE_ADVISED_REFRESH_REQUIREMENT,
                    'label' => __('Require Advised Refresh'),
                    'checkedDependentFieldNames' => [ self::KEY_ADVISED_REFRESH_REQUIREMENT_DELAY ],
                    'sortOrder' => 100040,
                ]
            ),

            $this->fieldFactory->create(
                TextBox::TYPE_CODE,
                [
                    'name' => self::KEY_ADVISED_REFRESH_REQUIREMENT_DELAY,
                    'valueHandler' => $this->valueHandlerFactory->create(PositiveIntegerHandler::TYPE_CODE),
                    'required' => true,
                    'label' => __('Require Advised Refresh After'),
                    'notice' => __('In minutes.'),
                    'sortOrder' => 100050,
                ]
            ),
            */
        ];
    }

    public function shouldForceProductLoadForRefresh(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_FORCE_PRODUCT_LOAD_FOR_REFRESH);
    }

    public function getAutomaticRefreshState(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_AUTOMATIC_REFRESH_STATE);
    }

    public function getAutomaticRefreshDelay(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_AUTOMATIC_REFRESH_DELAY) * 60;
    }

    public function isAdvisedRefreshRequirementEnabled(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_ENABLE_ADVISED_REFRESH_REQUIREMENT);
    }

    public function getAdvisedRefreshRequirementDelay(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_ADVISED_REFRESH_REQUIREMENT_DELAY) * 60;
    }
}
