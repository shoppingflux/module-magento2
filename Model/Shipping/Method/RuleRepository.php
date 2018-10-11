<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Data\Shipping\Method\RuleInterface;
use ShoppingFeed\Manager\Api\Shipping\Method\RuleRepositoryInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Shipping\Method\Rule as RuleResource;
use ShoppingFeed\Manager\Model\ResourceModel\Shipping\Method\RuleFactory as RuleResourceFactory;

class RuleRepository implements RuleRepositoryInterface
{
    /**
     * @var RuleResource
     */
    private $ruleResource;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @param RuleResourceFactory $ruleResourceFactory
     * @param RuleFactory $ruleFactory
     */
    public function __construct(RuleResourceFactory $ruleResourceFactory, RuleFactory $ruleFactory)
    {
        $this->ruleResource = $ruleResourceFactory->create();
        $this->ruleFactory = $ruleFactory;
    }

    public function save(RuleInterface $rule)
    {
        try {
            $this->ruleResource->save($rule);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $rule;
    }

    public function getById($ruleId)
    {
        $rule = $this->ruleFactory->create();
        $this->ruleResource->load($rule, $ruleId);

        if (!$rule->getId()) {
            throw new NoSuchEntityException(__('Shipping method rule with ID "%1" does not exist.', $ruleId));
        }

        return $rule;
    }

    public function delete(RuleInterface $rule)
    {
        try {
            $this->ruleResource->delete($rule);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    public function deleteById($ruleId)
    {
        return $this->delete($this->getById($ruleId));
    }
}
