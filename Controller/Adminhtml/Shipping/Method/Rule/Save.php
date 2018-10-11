<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Shipping\Method\Rule;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory as RawResultFactory;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\DateFactory as DateFilterFactory;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\Shipping\Method\RuleRepositoryInterface;
use ShoppingFeed\Manager\Controller\Adminhtml\Shipping\Method\RuleAction;
use ShoppingFeed\Manager\Model\Shipping\Method\ApplierPoolInterface;
use ShoppingFeed\Manager\Model\Shipping\Method\RuleFactory;
use ShoppingFeed\Manager\Ui\DataProvider\Shipping\Method\Rule\Form\DataProvider as RuleFormDataProvider;

class Save extends RuleAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::shipping_method_rule_edit';

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var DateFilterFactory
     */
    private $dateFilterFactory;

    /**
     * @var ApplierPoolInterface
     */
    private $applierPool;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param RawResultFactory $rawResultFactory
     * @param PageResultFactory $pageResultFactory
     * @param RuleFactory $ruleFactory
     * @param RuleRepositoryInterface $ruleRepository
     * @param DataObjectFactory $dataObjectFactory
     * @param DateFilterFactory $dateFilterFactory
     * @param ApplierPoolInterface $applierPool
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        RawResultFactory $rawResultFactory,
        PageResultFactory $pageResultFactory,
        RuleFactory $ruleFactory,
        RuleRepositoryInterface $ruleRepository,
        DataObjectFactory $dataObjectFactory,
        DateFilterFactory $dateFilterFactory,
        ApplierPoolInterface $applierPool
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->dateFilterFactory = $dateFilterFactory;
        $this->applierPool = $applierPool;

        parent::__construct(
            $context,
            $coreRegistry,
            $rawResultFactory,
            $pageResultFactory,
            $ruleFactory,
            $ruleRepository
        );
    }

    public function execute()
    {
        $redirectResult = $this->resultRedirectFactory->create();

        try {
            $rule = $this->getRule();
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This shipping method rule does no longer exist.'));
            return $redirectResult->setPath('*/*/');
        }

        $data = (array) $this->getRequest()->getPostValue();
        $isSaveSuccessful = false;

        try {
            if (!isset($data[RuleFormDataProvider::DATA_SCOPE_RULE])
                || !is_array($data[RuleFormDataProvider::DATA_SCOPE_RULE])
            ) {
                throw new LocalizedException(__('The request is incomplete.'));
            }

            $ruleData = $data[RuleFormDataProvider::DATA_SCOPE_RULE];

            if (!isset($ruleData[RuleFormDataProvider::DATA_SCOPE_APPLIER])
                || !is_array($ruleData[RuleFormDataProvider::DATA_SCOPE_APPLIER])
                || !isset($ruleData[RuleFormDataProvider::DATA_SCOPE_APPLIER][RuleFormDataProvider::FIELD_APPLIER_CODE])
            ) {
                throw new LocalizedException(__('The request is incomplete.'));
            }

            $applierData = $ruleData[RuleFormDataProvider::DATA_SCOPE_APPLIER];
            $applierCode = $applierData[RuleFormDataProvider::FIELD_APPLIER_CODE];
            $applier = $this->applierPool->getApplierByCode($applierCode);
            $applierConfigData = $applier->getConfig()
                ->prepareFormDataForSave((array) ($applierData[$applierCode] ?? []));

            $dateFilter = $this->dateFilterFactory->create();

            $inputFilter = new \Zend_Filter_Input(
                array_intersect_key(
                    [
                        RuleFormDataProvider::FIELD_FROM_DATE => $dateFilter,
                        RuleFormDataProvider::FIELD_TO_DATE => $dateFilter,
                    ],
                    array_filter($ruleData)
                ),
                [
                    RuleFormDataProvider::FIELD_NAME => 'NotEmpty',
                    RuleFormDataProvider::FIELD_DESCRIPTION => [],
                    RuleFormDataProvider::FIELD_IS_ACTIVE => [],
                    RuleFormDataProvider::FIELD_FROM_DATE => [],
                    RuleFormDataProvider::FIELD_TO_DATE => [],
                    RuleFormDataProvider::FIELD_SORT_ORDER => 'NotEmpty',
                    RuleFormDataProvider::FIELD_CONDITIONS => [
                        \Zend_Filter_Input::PRESENCE => \Zend_Filter_Input::PRESENCE_OPTIONAL,
                    ],
                ],
                $ruleData,
                [
                    \Zend_Filter_Input::ALLOW_EMPTY => true,
                    \Zend_Filter_Input::PRESENCE => \Zend_Filter_Input::PRESENCE_REQUIRED,
                ]
            );

            $inputFilter->process();
            $ruleData = $inputFilter->getUnescaped();

            $rule->setName($ruleData[RuleFormDataProvider::FIELD_NAME]);
            $rule->setDescription($ruleData[RuleFormDataProvider::FIELD_DESCRIPTION]);
            $rule->setIsActive($ruleData[RuleFormDataProvider::FIELD_IS_ACTIVE]);
            $rule->setFromDate($ruleData[RuleFormDataProvider::FIELD_FROM_DATE]);
            $rule->setToDate($ruleData[RuleFormDataProvider::FIELD_TO_DATE]);
            $rule->setSortOrder($ruleData[RuleFormDataProvider::FIELD_SORT_ORDER]);
            $rule->setRawConditions($ruleData[RuleFormDataProvider::FIELD_CONDITIONS] ?? []);
            $rule->setApplierCode($applierCode);
            $rule->setApplierConfiguration($this->dataObjectFactory->create([ 'data' => $applierConfigData ]));

            $this->ruleRepository->save($rule);
            $isSaveSuccessful = true;
            $this->messageManager->addSuccessMessage(__('The shipping method rule has been successfully saved.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
        } catch (\Zend_Filter_Exception $e) {
            $this->messageManager->addExceptionMessage($e, __($e->getMessage()));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('An error occurred while saving the shipping method rule.')
            );
        }

        if (!$isSaveSuccessful || $this->getRequest()->getParam('back')) {
            return $redirectResult->setPath('*/*/edit', [ self::REQUEST_KEY_RULE_ID => $rule->getId() ]);
        }

        return $redirectResult->setPath('*/*/');
    }
}
