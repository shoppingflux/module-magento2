<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section;

use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Section\Type as TypeResource;


abstract class AbstractType
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param TypeResource $typeResource
     * @param AdapterInterface $adapter
     * @param ConfigInterface $config
     * @throws LocalizedException
     */
    public function __construct(TypeResource $typeResource, AdapterInterface $adapter, ConfigInterface $config)
    {
        $this->id = $typeResource->getCodeId($this->getCode(), true);
        $this->adapter = $adapter;
        $this->config = $config;
        $this->adapter->setType($this);
        $this->config->setType($this);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    abstract public function getCode();

    /**
     * @return string
     */
    abstract public function getLabel();
    
    /**
     * @return int
     */
    abstract public function getSortOrder();

    /**
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @return ConfigInterface
     */
    public function getConfig()
    {
        return $this->config;
    }
}
