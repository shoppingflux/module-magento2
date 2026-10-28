<?php

namespace ShoppingFeed\Manager\Plugin\Store;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use ShoppingFeed\Manager\Model\Feed\SecureUrlState;

class StorePlugin
{
    /**
     * @var SecureUrlState
     */
    private $secureUrlState;

    /**
     * @param SecureUrlState $secureUrlState
     */
    public function __construct(SecureUrlState $secureUrlState)
    {
        $this->secureUrlState = $secureUrlState;
    }

    /**
     * Media URLs are built straight from the store base URL, out of reach of the "_secure" route parameter.
     *
     * @param Store $subject
     * @param string $type
     * @param bool|null $secure
     * @return array
     */
    public function beforeGetBaseUrl(Store $subject, $type = UrlInterface::URL_TYPE_LINK, $secure = null)
    {
        return [ $type, $this->secureUrlState->isSecureUrlForced() ? true : $secure ];
    }
}
