<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Marketplace\Order\ItemRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Item as ItemResource;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\ItemFactory as ItemResourceFactory;

class ItemRepository implements ItemRepositoryInterface
{
    /**
     * @var ItemResource
     */
    protected $itemResource;

    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @param ItemResourceFactory $itemResourceFactory
     * @param ItemFactory $itemFactory
     */
    public function __construct(ItemResourceFactory $itemResourceFactory, ItemFactory $itemFactory)
    {
        $this->itemResource = $itemResourceFactory->create();
        $this->itemFactory = $itemFactory;
    }

    public function save(ItemInterface $item)
    {
        try {
            $this->itemResource->save($item);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $item;
    }

    public function getById($itemId)
    {
        $item = $this->itemFactory->create();
        $this->itemResource->load($item, $itemId);

        if (!$item->getId()) {
            throw new NoSuchEntityException(__('Marketplace order item with ID "%1" does not exist.', $itemId));
        }

        return $item;
    }
}
