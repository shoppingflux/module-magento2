<?php

namespace ShoppingFeed\Manager\Model\Feed;

use Magento\Framework\Url\RouteParamsPreprocessorInterface;

class SecureUrlRouteParamsPreprocessor implements RouteParamsPreprocessorInterface
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
     * @param string $areaCode
     * @param string|null $routePath
     * @param array|null $routeParams
     * @return array|null
     */
    public function execute($areaCode, $routePath, $routeParams)
    {
        if ($this->secureUrlState->isSecureUrlForced()) {
            $routeParams = is_array($routeParams) ? $routeParams : [];
            $routeParams['_secure'] = true;
        }

        return $routeParams;
    }
}
