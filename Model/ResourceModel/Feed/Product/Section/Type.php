<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Section;

use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractDb;

class Type extends AbstractDb
{
    /**
     * @var array|null
     */
    private $codeIds = null;

    protected function _construct()
    {
        $this->_init('sfm_feed_product_section_type', 'type_id');
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getAllIds()
    {
        if (null === $this->codeIds) {
            $connection = $this->getConnection();

            $this->codeIds = $connection->fetchPairs(
                $connection->select()
                    ->from($this->getMainTable(), [ 'code', 'type_id' ])
            );
        }

        return $this->codeIds;
    }

    /**
     * @param string $code
     * @param bool $createUnexisting
     * @return int
     * @throws LocalizedException
     */
    public function getCodeId($code, $createUnexisting = false)
    {
        $codeId = null;
        $codeIds = $this->getAllIds();

        if (isset($codeIds[$code])) {
            $codeId = $codeIds[$code];
        } elseif ($createUnexisting) {
            $connection = $this->getConnection();
            $connection->insertOnDuplicate($this->getMainTable(), [ 'code' => $code ], [ 'code' ]);
            $codeId = $connection->fetchOne($connection->select()->from($this->getMainTable(), [ 'type_id' ]));
            $this->codeIds[$code] = $codeId;
        }

        return (int) $codeId;
    }
}
