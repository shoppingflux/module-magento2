<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Shipping\Method;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page as PageResult;
use Magento\Framework\Controller\Result\RawFactory as RawResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\Data\Shipping\Method\RuleInterface;
use ShoppingFeed\Manager\Api\Shipping\Method\RuleRepositoryInterface;
use ShoppingFeed\Manager\Model\Shipping\Method\RuleFactory;

abstract class RuleAction extends Action
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::shipping_method_rules';
    const REQUEST_KEY_RULE_ID = 'rule_id';

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var RawResultFactory
     */
    protected $rawResultFactory;

    /**
     * @var PageResultFactory
     */
    protected $pageResultFactory;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param RawResultFactory $rawResultFactory
     * @param PageResultFactory $pageResultFactory
     * @param RuleFactory $ruleFactory
     * @param RuleRepositoryInterface $ruleRepository
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        RawResultFactory $rawResultFactory,
        PageResultFactory $pageResultFactory,
        RuleFactory $ruleFactory,
        RuleRepositoryInterface $ruleRepository
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->rawResultFactory = $rawResultFactory;
        $this->pageResultFactory = $pageResultFactory;
        $this->ruleFactory = $ruleFactory;
        $this->ruleRepository = $ruleRepository;
        parent::__construct($context);
    }

    /**
     * @param int|null $ruleId
     * @param bool $requireLoaded
     * @return RuleInterface
     * @throws NoSuchEntityException
     */
    protected function getRule($ruleId = null, $requireLoaded = false)
    {
        if (null === $ruleId) {
            $ruleId = (int) $this->getRequest()->getParam(static::REQUEST_KEY_RULE_ID);
        }

        if (empty($ruleId) && !$requireLoaded) {
            $rule = $this->ruleFactory->create();
        } else {
            try {
                $rule = $this->ruleRepository->getById($ruleId);
            } catch (NoSuchEntityException $e) {
                if (!$requireLoaded) {
                    $rule = $this->ruleFactory->create();
                } else {
                    throw $e;
                }
            }
        }

        return $rule;
    }

    /**
     * @return PageResult
     */
    protected function initPage()
    {
        /** @var PageResult $pageResult */
        $pageResult = $this->pageResultFactory->create();
        $pageResult->setActiveMenu('ShoppingFeed_Manager::shipping_method_rules');
        $pageResult->addBreadcrumb(__('Shopping Feed'), __('Shopping Feed'));
        $pageResult->addBreadcrumb(__('Shipping Method Rules'), __('Shipping Method Rules'));
        $pageResult->getConfig()->getTitle()->prepend(__('Shipping Method Rules'));
        return $pageResult;
    }
}
