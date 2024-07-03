<?php

namespace ShoppingFeed\Manager\Model\Sales\Order\SalesRule;

use Magento\Customer\Api\GroupManagementInterface as CustomerGroupManagementInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\SalesRule\Api\Data\RuleInterfaceFactory as SalesRuleInterfaceFactory;
use Magento\SalesRule\Api\RuleRepositoryInterface as SalesRuleRepositoryInterface;
use Magento\SalesRule\Model\Data\Rule as RuleData;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as SalesRuleCollectionFactory;
use Magento\SalesRule\Model\Rule;
use Magento\Store\Model\StoreManagerInterface;
use ShoppingFeed\Manager\Model\SalesRule\Rule\Action\Discount\Marketplace\CartFixed;

class Applier
{
    const DEFAULT_RULE_NAMES = [
        CartFixed::ACTION_CODE => 'Shopping Feed - Marketplace Cart Discount',
    ];

    const MARKETPLACE_ACTION_CODES = [
        CartFixed::ACTION_CODE,
    ];

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerGroupManagementInterface
     */
    private $customerGroupManagement;

    /**
     * @var SalesRuleCollectionFactory
     */
    private $salesRuleCollectionFactory;

    /**
     * @var SalesRuleRepositoryInterface
     */
    private $salesRuleRepository;

    /**
     * @var SalesRuleInterfaceFactory
     */
    private $salesRuleFactory;

    /**
     * @var string[]
     */
    private $ruleNames;

    /**
     * @param StoreManagerInterface $storeManager
     * @param CustomerGroupManagementInterface $customerGroupManagement
     * @param SalesRuleCollectionFactory $salesRuleCollectionFactory
     * @param SalesRuleRepositoryInterface $salesRuleRepository
     * @param SalesRuleInterfaceFactory $salesRuleFactory
     * @param string[] $ruleNames
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CustomerGroupManagementInterface $customerGroupManagement,
        SalesRuleCollectionFactory $salesRuleCollectionFactory,
        SalesRuleRepositoryInterface $salesRuleRepository,
        SalesRuleInterfaceFactory $salesRuleFactory,
        array $ruleNames = self::DEFAULT_RULE_NAMES
    ) {
        $this->storeManager = $storeManager;
        $this->customerGroupManagement = $customerGroupManagement;
        $this->salesRuleCollectionFactory = $salesRuleCollectionFactory;
        $this->salesRuleRepository = $salesRuleRepository;
        $this->salesRuleFactory = $salesRuleFactory;
        $this->ruleNames = array_merge(self::DEFAULT_RULE_NAMES, $ruleNames);
    }

    /**
     * @param string $actionCode
     * @return string|null
     */
    public function getRuleName($actionCode)
    {
        return $this->ruleNames[$actionCode] ?? self::DEFAULT_RULE_NAMES[$actionCode] ?? null;
    }

    /**
     * @param int $ruleId
     * @return void
     */
    public function disableMarketplaceObsoleteRule($ruleId)
    {
        try {
            $rule = $this->salesRuleRepository->getById($ruleId);
            $rule->setIsActive(false);
            $this->salesRuleRepository->save($rule);
        } catch (NoSuchEntityException $e) {
            // Do nothing.
        }
    }

    /**
     * @param int[] $allCustomerGroupIds
     * @param int[] $allWebsiteIds
     * @param int|null $ruleId
     * @return void
     */
    public function setupMarketplaceCartFixedRule(array $allCustomerGroupIds, array $allWebsiteIds, $ruleId = null)
    {
        try {
            $rule = $this->salesRuleRepository->getById($ruleId);
        } catch (NoSuchEntityException $e) {
            $rule = $this->salesRuleFactory->create();
        }

        $rule->setName($this->getRuleName(CartFixed::ACTION_CODE));
        $rule->setCustomerGroupIds($allCustomerGroupIds);
        $rule->setWebsiteIds($allWebsiteIds);
        $rule->setIsActive(true);
        $rule->setFromDate(null);
        $rule->setToDate(null);
        $rule->setCouponType(RuleData::COUPON_TYPE_NO_COUPON);
        $rule->setUsesPerCoupon(0);
        $rule->setUsesPerCustomer(0);
        $rule->setCondition(null);
        $rule->setActionCondition(null);
        $rule->setSimpleAction(CartFixed::ACTION_CODE);
        $rule->setDiscountAmount(0);
        $rule->setDiscountQty(0);
        $rule->setDiscountStep(0);
        $rule->setApplyToShipping(false);
        $rule->setSimpleFreeShipping(false);
        $rule->setIsRss(false);
        $rule->setSortOrder(0);
        $rule->setStopRulesProcessing(false);

        $this->salesRuleRepository->save($rule);
    }

    /**
     * @return void
     */
    public function setupMarketplaceDiscountRules()
    {
        $marketplaceRules = $this->salesRuleCollectionFactory
            ->create()
            ->addFieldToFilter(RuleData::KEY_SIMPLE_ACTION, [ 'in' => self::MARKETPLACE_ACTION_CODES ]);

        $matchingRuleIds = [];

        /** @var Rule $rule */
        foreach ($marketplaceRules as $rule) {
            $actionCode = $rule->getSimpleAction();

            if (
                !isset($matchingRuleIds[$actionCode])
                || ($rule->getName() === ($this->ruleNames[$actionCode] ?? self::DEFAULT_RULE_NAMES[$actionCode]))
            ) {
                $matchingRuleIds[$actionCode] = (int) $rule->getId();
            }
        }

        foreach ($marketplaceRules as $rule) {
            if (!in_array((int) $rule->getId(), $matchingRuleIds, true)) {
                $this->disableMarketplaceObsoleteRule($rule->getId());
            }
        }

        $customerGroupIds = [
            $this->customerGroupManagement->getNotLoggedInGroup()->getId(),
        ];

        foreach ($this->customerGroupManagement->getLoggedInGroups() as $group) {
            $customerGroupIds[] = $group->getId();
        }

        $websiteIds = array_keys($this->storeManager->getWebsites());

        $this->setupMarketplaceCartFixedRule(
            $customerGroupIds,
            $websiteIds,
            $matchingRuleIds[CartFixed::ACTION_CODE] ?? null
        );
    }
}
