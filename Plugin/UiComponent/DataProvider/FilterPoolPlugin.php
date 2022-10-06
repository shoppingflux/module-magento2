<?php

namespace ShoppingFeed\Manager\Plugin\UiComponent\DataProvider;

use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\DB\Select as DbSelect;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\View\Element\UiComponent\DataProvider\FilterPool;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;

class FilterPoolPlugin
{
    /**
     * @var StringUtils
     */
    private $stringHelper;

    /**
     * @param StringUtils $stringHelper
     */
    public function __construct(StringUtils $stringHelper)
    {
        $this->stringHelper = $stringHelper;
    }

    /**
     * @param FilterPool $subject
     * @param mixed $result
     * @param Collection|null $collection
     * @param SearchCriteriaInterface|null $criteria
     * @return mixed
     */
    public function afterApplyFilters(
        FilterPool $subject,
        $result,
        $collection = null,
        $criteria = null
    ) {
        if ($collection instanceof OrderGridCollection) {
            /**
             * Make sure that the "created_at" field is qualified in all conditions.
             *
             * This is mostly a fix for
             * @see \Magento\Sales\Plugin\Model\ResourceModel\Order\OrderGridCollectionFilter::aroundAddFieldToFilter(),
             * which was added in Magento 2.4.5 and does not use qualified field names in the conditions it applies.
             */

            $select = $collection->getSelect();
            $connection = $collection->getConnection();

            $rawCreatedAtField = 'created_at';
            $quotedCreatedAtField = $connection->quoteIdentifier($rawCreatedAtField);
            $qualifiedCreatedAtField = $connection->quoteIdentifier('main_table.created_at');

            $conditions = $select->getPart(DbSelect::WHERE);

            foreach ($conditions as &$condition) {
                foreach ([ $quotedCreatedAtField, $rawCreatedAtField ] as $createdAtField) {
                    preg_match_all(
                        '/(^|\s|\()(' . preg_quote($createdAtField, '/') . ')/',
                        $condition,
                        $matches,
                        PREG_SET_ORDER | PREG_OFFSET_CAPTURE
                    );

                    $baseFieldLength = $this->stringHelper->strlen($createdAtField);

                    if (!empty($matches)) {
                        foreach (array_reverse($matches) as $match) {
                            $condition = $this->stringHelper->substr($condition, 0, $match[2][1])
                                . $qualifiedCreatedAtField
                                . $this->stringHelper->substr($condition, $match[2][1] + $baseFieldLength);
                        }
                    }
                }
            }

            $select->setPart(DbSelect::WHERE, $conditions);
        }

        return $result;
    }
}
