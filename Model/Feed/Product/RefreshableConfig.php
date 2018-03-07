<?php

namespace ShoppingFeed\Manager\Model\Feed\Product;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Account\Store\Config\Field\Select;
use ShoppingFeed\Manager\Model\Account\Store\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler\Option as OptionHandler;
use ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler\PositiveInteger as PositiveIntegerHandler;
use ShoppingFeed\Manager\Model\Feed\AbstractConfig;
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
        return [
            new Checkbox(
                self::KEY_FORCE_PRODUCT_LOAD_FOR_REFRESH,
                __('Force Product Load for Refresh')
            ),

            new Select(
                self::KEY_AUTOMATIC_REFRESH_STATE,
                new OptionHandler(
                    'number',
                    [
                        [ 'value' => '', 'label' => __('No') ],
                        [ 'value' => FeedRefresher::REFRESH_STATE_ADVISED, 'label' => __('Advised') ],
                        [ 'value' => FeedRefresher::REFRESH_STATE_REQUIRED, 'label' => __('Required') ],
                    ],
                    true
                ),
                __('Force Automatic Refresh'),
                false,
                '',
                '',
                '',
                [
                    [
                        'values' => [
                            FeedRefresher::REFRESH_STATE_ADVISED,
                            FeedRefresher::REFRESH_STATE_REQUIRED,
                        ],
                        'field_names' => [ self::KEY_AUTOMATIC_REFRESH_DELAY ],
                    ],
                ]
            ),

            new TextBox(
                self::KEY_AUTOMATIC_REFRESH_DELAY,
                new PositiveIntegerHandler(),
                __('Force Automatic Refresh After'),
                true,
                null,
                null,
                __('In minutes.')
            ),

            // @todo (needs specific filters)
            /*
            new Checkbox(
                self::KEY_ENABLE_ADVISED_REFRESH_REQUIREMENT,
                __('Require Advised Refresh'),
                false,
                '',
                '',
                [ self::KEY_ADVISED_REFRESH_REQUIREMENT_DELAY ]
            ),

            new TextBox(
                self::KEY_ADVISED_REFRESH_REQUIREMENT_DELAY,
                new PositiveIntegerHandler(),
                __('Require Advised Refresh After'),
                true,
                null,
                null,
                __('In minutes.')
            ),
            */
        ];
    }

    public function shouldForceProductLoadForRefresh(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_FORCE_PRODUCT_LOAD_FOR_REFRESH);
    }

    public function getAutomaticRefreshState(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_AUTOMATIC_REFRESH_STATE);
    }

    public function getAutomaticRefreshDelay(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_AUTOMATIC_REFRESH_DELAY) * 60;
    }

    public function isAdvisedRefreshRequirementEnabled(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_ENABLE_ADVISED_REFRESH_REQUIREMENT);
    }

    public function getAdvisedRefreshRequirementDelay(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_ADVISED_REFRESH_REQUIREMENT_DELAY) * 60;
    }
}
