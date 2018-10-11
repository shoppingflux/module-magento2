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
    private $label;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param string|null $label
     */
    public function __construct(Context $context, Registry $coreRegistry, $label = null)
    {
        $this->context = $context;
        $this->coreRegistry = $coreRegistry;
        $this->label = $label;
    }

    /**
     * @param string $defaultLabel
     * @return string
     */
    public function getLabel($defaultLabel)
    {
        return (null === $this->label) ? $defaultLabel : $this->label;
    }

    /**
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
