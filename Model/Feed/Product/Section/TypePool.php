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
     * @var int[]|null
     */
    private $typeIds = null;

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
                        'Section type "%1" must be of type: ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractType.',
                        $code
                    )
                );
            }

            $this->types[$code] = $type;
        }
    }

    public function getTypeIds()
    {
        if (null === $this->typeIds) {
            $this->typeIds = [];

            foreach ($this->types as $type) {
                $this->typeIds[$type->getCode()] = $type->getId();
            }
        }

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
        $code = array_search($id, $this->getTypeIds(), true);

        if (false !== $code) {
            return $this->getTypeByCode($code);
        }

        throw new LocalizedException(__('Section type for ID "%1" does not exist.', $id));
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

        throw new LocalizedException(__('Section type for code "%1" does not exist.', $code));
    }

    public function sortTypes(array $types)
    {
        uasort(
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
