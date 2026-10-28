<?php

namespace ShoppingFeed\Manager\Plugin\Framework;

use Magento\Framework\Url;
use ShoppingFeed\Manager\Model\Feed\SecureUrlState;

class UrlPlugin
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
     * The "_secure" parameter also prevents previously cached unsecure URLs from being returned.
     *
     * @param Url $subject
     * @param string|null $routePath
     * @param array|null $routeParams
     * @return array
     */
    public function beforeGetUrl(Url $subject, $routePath = null, $routeParams = null)
    {
        if ($this->secureUrlState->isSecureUrlForced()) {
            $routeParams = is_array($routeParams) ? $routeParams : [];
            $routeParams['_secure'] = true;
        }

        return [ $routePath, $routeParams ];
    }
}
