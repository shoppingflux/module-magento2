<?php

namespace ShoppingFeed\Manager\Block\Adminhtml;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

abstract class AbstractButton implements ButtonProviderInterface
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var string|null
     */
    private $aclResource;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $label;

    /**
     * @var string|null
     */
    private $urlRoute;

    /**
     * @var array|null
     */
    private $urlParams;

    /**
     * @var string|null
     */
    private $class;

    /**
     * @var string|null
     */
    private $sortOrder;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param string|null $aclResource
     * @param string|null $name
     * @param string|null $label
     * @param string|null $urlRoute
     * @param array|null $urlParams
     * @param string|null $class
     * @param int|null $sortOrder
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        $aclResource = null,
        $name = null,
        $label = null,
        $urlRoute = null,
        $urlParams = null,
        $class = null,
        $sortOrder = null
    ) {
        $this->context = $context;
        $this->coreRegistry = $coreRegistry;
        $this->aclResource = $aclResource;
        $this->name = $name;
        $this->label = $label;
        $this->urlRoute = $urlRoute;
        $this->urlParams = $urlParams;
        $this->class = $class;
        $this->sortOrder = $sortOrder;
    }

    /**
     * @param string|null $defaultAclResource
     * @return bool
     */
    public function isAllowed($defaultAclResource = null)
    {
        $aclResource = $this->aclResource ?? $defaultAclResource;

        return empty($aclResource) ? true : $this->context->getAuthorization()->isAllowed($aclResource);
    }

    /**
     * @param string $defaultName
     * @return string
     */
    public function getName($defaultName)
    {
        return trim($this->name ?? $defaultName);
    }

    /**
     * @param string $defaultLabel
     * @return string
     */
    public function getLabel($defaultLabel)
    {
        return __(trim($this->label ?? $defaultLabel));
    }

    /**
     * @param string $defaultRoute
     * @param array $defaultParams
     * @return string
     */
    public function getUrl($defaultRoute = '', array $defaultParams = [])
    {
        return $this->context
            ->getUrlBuilder()
            ->getUrl($this->urlRoute ?? $defaultRoute, $this->urlParams ?? $defaultParams);
    }

    /**
     * @param string $defaultClass
     * @return string
     */
    public function getClass($defaultClass = '')
    {
        return trim($this->class ?? $defaultClass);
    }

    /**
     * @param int $defaultSortOrder
     * @return int
     */
    public function getSortOrder($defaultSortOrder = 0)
    {
        return (int) ($this->sortOrder ?? $defaultSortOrder);
    }
}
