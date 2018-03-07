<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section;

use Magento\Framework\Exception\LocalizedException;


class TypePool implements TypePoolInterface
{
    /**
     * @var AbstractType[]
     */
    private $types = [];

    /**
     * @var int[]
     */
    private $typeIds = [];

    /**
     * @var AbstractType[]
     */
    private $sortedTypes;

    /**
     * @param AbstractType[] $types
     * @throws LocalizedException
     */
    public function __construct(array $types = [])
    {
        foreach ($types as $code => $type) {
            if (!$type instanceof AbstractType) {
                throw new LocalizedException(
                    __(
                        'Section type %1 must be of type: ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractType',
                        $code
                    )
                );
            }

            $this->types[$code] = $type;
            $this->typeIds[$code] = $type->getId();
        }
    }

    public function getTypeIds()
    {
        return $this->typeIds;
    }

    public function getTypeCodes()
    {
        return array_keys($this->types);
    }

    public function getTypes()
    {
        return $this->types;
    }

    public function getSortedTypes()
    {
        if (!is_array($this->sortedTypes)) {
            $this->sortedTypes = $this->sortTypes($this->getTypes());
        }

        return $this->sortedTypes;
    }

    /**
     * @param int $id
     * @return AbstractType
     * @throws LocalizedException
     */
    public function getTypeById($id)
    {
        if (false !== ($code = array_search($id, $this->typeIds, true))) {
            return $this->getTypeByCode($code);
        }

        throw new LocalizedException(__('Section type for ID %1 does not exist', $id));
    }

    /**
     * @param string $code
     * @return AbstractType
     * @throws LocalizedException
     */
    public function getTypeByCode($code)
    {
        if (isset($this->types[$code])) {
            return $this->types[$code];
        }

        throw new LocalizedException(__('Section type for code %1 does not exist', $code));
    }

    public function sortTypes(array $types)
    {
        usort(
            $types,
            function ($typeA, $typeB) {
                /**
                 * @var AbstractType $typeA
                 * @var AbstractType $typeB
                 */
                return $typeA->getSortOrder() <=> $typeB->getSortOrder();
            }
        );

        return $types;
    }
}
