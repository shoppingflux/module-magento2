<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Shipping\Method\Rule;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface as DbAdapterInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb as AbstractResource;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Rule\Model\ResourceModel\Rule\Collection\AbstractCollection;
use Psr\Log\LoggerInterface;
use ShoppingFeed\Manager\Model\Shipping\Method\Rule;
use ShoppingFeed\Manager\Model\ResourceModel\Shipping\Method\Rule as RuleResource;


class Collection extends AbstractCollection
{
    protected $_idFieldName = 'rule_id';

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param EventManagerInterface $eventManager
     * @param TimezoneInterface $localeDate
     * @param DbAdapterInterface|null $connection
     * @param AbstractResource|null $resource
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        EventManagerInterface $eventManager,
        TimezoneInterface $localeDate,
        DbAdapterInterface $connection = null,
        AbstractResource $resource = null
    ) {
        $this->localeDate = $localeDate;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    protected function _construct()
    {
        $this->_init(Rule::class, RuleResource::class);
    }

    /**
     * @param string|null $date
     * @return $this
     */
    public function addEnabledAtFilter($date = null)
    {
        $date = $date ?? $this->localeDate->date()->format('Y-m-d');
        $this->addFieldToFilter(Rule::FROM_DATE, [ [ 'null' => true ], [ 'lteq' => $date ] ]);
        $this->addFieldToFilter(Rule::TO_DATE, [ [ 'null' => true ], [ 'gteq' => $date ] ]);
        return $this;
    }

    /**
     * @param string $direction
     * @return $this
     */
    public function addSortOrderOrder($direction = AbstractCollection::SORT_ORDER_ASC)
    {
        return $this->addOrder(Rule::SORT_ORDER, $direction);
    }
}
