<?php

namespace ShoppingFeed\Manager\Ui\Component\Listing\Column;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;


abstract class AbstractActions extends Column
{
    /**
     * @var AuthorizationInterface
     */
    protected $authorizationModel;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param AuthorizationInterface $authorizationModel
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        AuthorizationInterface $authorizationModel,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->authorizationModel = $authorizationModel;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
}
