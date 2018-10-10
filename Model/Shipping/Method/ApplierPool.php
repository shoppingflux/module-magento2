<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method;

use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Model\StringHelper;

class ApplierPool implements ApplierPoolInterface
{
    /**
     * @var StringHelper
     */
    private $stringHelper;

    /**
     * @var ApplierInterface
     */
    private $defaultApplier;

    /**
     * @var ApplierInterface[]
     */
    private $appliers = [];

    /**
     * @var ApplierInterface[]
     */
    private $sortedAppliers;

    /**
     * @param StringHelper $stringHelper
     * @param string $defaultApplierCode
     * @param ApplierInterface[] $appliers
     * @throws LocalizedException
     */
    public function __construct(StringHelper $stringHelper, $defaultApplierCode, array $appliers = [])
    {
        $this->stringHelper = $stringHelper;

        foreach ($appliers as $code => $applier) {
            if (!$applier instanceof ApplierInterface) {
                throw new LocalizedException(
                    __(
                        'Shipping method applier "%1" must be of type: ShoppingFeed\Manager\Model\Shipping\Method\ApplierInterface.',
                        $code
                    )
                );
            }

            $this->appliers[$code] = $applier;
        }

        if (!isset($this->appliers[$defaultApplierCode])) {
            throw new LocalizedException(__('Default shipping method applier with code "%1" does not exist.'));
        }

        $this->defaultApplier = $this->appliers[$defaultApplierCode];
    }

    public function getDefaultApplier()
    {
        return $this->defaultApplier;
    }

    public function getApplierCodes()
    {
        return array_keys($this->appliers);
    }

    public function getAppliers()
    {
        return $this->appliers;
    }

    public function getSortedAppliers()
    {
        if (!is_array($this->sortedAppliers)) {
            $this->sortedAppliers = $this->sortAppliers($this->getAppliers());
        }

        return $this->sortedAppliers;
    }

    /**
     * @param string $code
     * @return ApplierInterface
     * @throws LocalizedException
     */
    public function getApplierByCode($code)
    {
        if (isset($this->appliers[$code])) {
            return $this->appliers[$code];
        }

        throw new LocalizedException(__('Shipping method applier for code "%1" does not exist.', $code));
    }

    public function sortAppliers(array $appliers)
    {
        uasort(
            $appliers,
            function ($applierA, $applierB) {
                /**
                 * @var ApplierInterface $applierA
                 * @var ApplierInterface $applierB
                 */
                return $this->stringHelper->strcmp($applierA->getLabel(), $applierB->getLabel());
            }
        );

        return $appliers;
    }
}
