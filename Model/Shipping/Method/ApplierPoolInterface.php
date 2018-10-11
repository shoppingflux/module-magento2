<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method;

use Magento\Framework\Exception\LocalizedException;

interface ApplierPoolInterface
{
    /**
     * @return ApplierInterface
     */
    public function getDefaultApplier();

    /**
     * @return string[]
     */
    public function getApplierCodes();

    /**
     * @return ApplierInterface[]
     */
    public function getAppliers();

    /**
     * @return ApplierInterface[]
     */
    public function getSortedAppliers();

    /**
     * @param string $code
     * @return ApplierInterface
     * @throws LocalizedException
     */
    public function getApplierByCode($code);

    /**
     * @param ApplierInterface[] $appliers
     * @return ApplierInterface[]
     */
    public function sortAppliers(array $appliers);
}
