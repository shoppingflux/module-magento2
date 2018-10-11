<?php

namespace ShoppingFeed\Manager\Model\Account\Store;

use Magento\Framework\Data\OptionSourceInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;

class Source implements OptionSourceInterface
{
    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * @var array|null
     */
    private $optionArray = null;

    /**
     * @param StoreCollectionFactory $storeCollectionFactory
     */
    public function __construct(StoreCollectionFactory $storeCollectionFactory)
    {
        $this->storeCollectionFactory = $storeCollectionFactory;
    }

    public function toOptionArray()
    {
        if (null === $this->optionArray) {
            $this->optionArray = $this->storeCollectionFactory->create()->toOptionArray();
        }

        return $this->optionArray;
    }
}
