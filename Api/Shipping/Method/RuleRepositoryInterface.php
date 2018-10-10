<?php

namespace ShoppingFeed\Manager\Api\Shipping\Method;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Data\Shipping\Method\RuleInterface;

/**
 * @api
 */
interface RuleRepositoryInterface
{
    /**
     * @param RuleInterface $rule
     * @return RuleInterface
     * @throws CouldNotSaveException
     */
    public function save(RuleInterface $rule);

    /**
     * @param int $ruleId
     * @return RuleInterface
     * @throws NoSuchEntityException
     */
    public function getById($ruleId);

    /**
     * @param RuleInterface $rule
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(RuleInterface $rule);

    /**
     * @param int $ruleId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($ruleId);
}
