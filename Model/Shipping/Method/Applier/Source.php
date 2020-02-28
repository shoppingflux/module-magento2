<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method\Applier;

use Magento\Framework\Data\OptionSourceInterface;
use ShoppingFeed\Manager\Model\Shipping\Method\ApplierPoolInterface;
use ShoppingFeed\Manager\Model\Source\WithOptionHash;

class Source implements OptionSourceInterface
{
    use WithOptionHash;

    /**
     * @var ApplierPoolInterface
     */
    private $applierPool;

    /**
     * @var array|null
     */
    private $optionArray = null;

    /**
     * @param ApplierPoolInterface $applierPool
     */
    public function __construct(ApplierPoolInterface $applierPool)
    {
        $this->applierPool = $applierPool;
    }

    public function toOptionArray()
    {
        if (null === $this->optionArray) {
            $this->optionArray = [];

            foreach ($this->applierPool->getSortedAppliers() as $code => $applier) {
                $this->optionArray[] = [
                    'value' => $code,
                    'label' => $applier->getLabel(),
                ];
            }
        }

        return $this->optionArray;
    }
}
