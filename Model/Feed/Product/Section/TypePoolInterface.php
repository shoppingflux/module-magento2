<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section;

use Magento\Framework\Exception\LocalizedException;

interface TypePoolInterface
{
    /**
     * @return int[]
     */
    public function getTypeIds();

    /**
     * @return string[]
     */
    public function getTypeCodes();

    /**
     * @return AbstractType[]
     */
    public function getTypes();

    /**
     * @return AbstractType[]
     */
    public function getSortedTypes();

    /**
     * @param int $id
     * @return AbstractType
     * @throws LocalizedException
     */
    public function getTypeById($id);

    /**
     * @param string $code
     * @return AbstractType
     * @throws LocalizedException
     */
    public function getTypeByCode($code);

    /**
     * @param AbstractType[] $types
     * @return AbstractType[]
     */
    public function sortTypes(array $types);
}
