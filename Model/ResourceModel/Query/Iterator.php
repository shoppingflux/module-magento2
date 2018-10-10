<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Query;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select as DbSelect;
use Magento\Framework\Exception\LocalizedException;

class Iterator implements \Iterator
{
    /**
     * @var DbSelect|string
     */
    private $query;

    /**
     * @var callable
     */
    private $itemCallback;

    /**
     * @var AdapterInterface|null $connection
     */
    private $connection = null;

    /**
     * @var \Zend_Db_Statement_Interface
     */
    private $statement = null;

    /**
     * @var bool
     */
    private $hasValidItem = false;

    /**
     * @var mixed|null
     */
    private $currentItem = null;

    /**
     * @var int
     */
    private $currentItemIndex = 0;

    /**
     * @param DbSelect|string $query
     * @param callable $itemCallback
     * @param AdapterInterface|null $connection
     */
    public function __construct($query, callable $itemCallback, AdapterInterface $connection = null)
    {
        $this->query = $query;
        $this->itemCallback = $itemCallback;
        $this->connection = $connection;
    }

    public function current()
    {
        return $this->currentItem;
    }

    /**
     * @throws \Zend_Db_Statement_Exception
     */
    public function next()
    {
        if ($row = $this->statement->fetch()) {
            ++$this->currentItemIndex;
            $args = [ 'row' => $row, 'index' => $this->currentItemIndex ];
            $this->currentItem = call_user_func($this->itemCallback, $args);
            $this->hasValidItem = true;
        } else {
            $this->hasValidItem = false;
            $this->currentItem = null;
        }
    }

    public function key()
    {
        return $this->hasValidItem ? $this->currentItemIndex : null;
    }

    public function valid()
    {
        return $this->hasValidItem;
    }

    /**
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     */
    public function rewind()
    {
        if ($this->query instanceof DbSelect) {
            $this->statement = $this->query->query();
        } elseif (is_string($this->query)) {
            if (!$this->connection instanceof AdapterInterface) {
                throw new LocalizedException(__('Invalid connection.'));
            }

            $this->statement = $this->connection->query($this->query);
        } else {
            throw new LocalizedException(__('Invalid query.'));
        }

        $this->currentItemIndex = -1;
        $this->next();
    }
}
