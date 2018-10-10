<?php

namespace ShoppingFeed\Manager\Ui\DataProvider\Shipping\Method\Rule\Form;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as BaseDataProvider;
use Magento\Ui\Component\Form\Element\DataType\Text as UiText;
use ShoppingFeed\Manager\Api\Data\Shipping\Method\RuleInterface;
use ShoppingFeed\Manager\Model\Config\FieldInterface as ConfigFieldInterface;
use ShoppingFeed\Manager\Model\Config\FieldFactoryInterface as ConfigFieldFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Field\Select as ConfigSelect;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ConfigValueHandlerFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Option as ConfigOptionHandler;
use ShoppingFeed\Manager\Model\Shipping\Method\ApplierPoolInterface as MethodApplierPoolInterface;
use ShoppingFeed\Manager\Model\Shipping\Method\Rule\RegistryConstants;

class DataProvider extends BaseDataProvider
{
    const FORM_NAMESPACE = 'sfm_shipping_method_rule_form';

    const DATA_SCOPE_RULE = 'rule';
    const DATA_SCOPE_APPLIER = 'applier';

    const FIELDSET_RULE_INFORMATION = 'rule_information';
    const FIELDSET_CONDITIONS = 'conditions';
    const FIELDSET_SHIPPING_METHOD = 'shipping_method';
    const BASE_FIELDSET_APPLIER_CONFIGURATION = 'applier_%s';

    const FIELD_RULE_ID = 'rule_id';
    const FIELD_NAME = 'name';
    const FIELD_DESCRIPTION = 'description';
    const FIELD_IS_ACTIVE = 'is_active';
    const FIELD_FROM_DATE = 'from_date';
    const FIELD_TO_DATE = 'to_date';
    const FIELD_SORT_ORDER = 'sort_order';
    const FIELD_CONDITIONS = 'conditions';
    const FIELD_APPLIER_CODE = 'code';

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var ConfigFieldFactoryInterface
     */
    private $configFieldFactory;

    /**
     * @var ConfigValueHandlerFactoryInterface
     */
    private $configValueHandlerFactory;

    /**
     * @var MethodApplierPoolInterface
     */
    private $methodApplierPool;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param Registry $coreRegistry
     * @param ConfigFieldFactoryInterface $configFieldFactory
     * @param ConfigValueHandlerFactoryInterface $configValueHandlerFactory
     * @param MethodApplierPoolInterface $methodApplierPool
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        Registry $coreRegistry,
        ConfigFieldFactoryInterface $configFieldFactory,
        ConfigValueHandlerFactoryInterface $configValueHandlerFactory,
        MethodApplierPoolInterface $methodApplierPool,
        array $meta = [],
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->configFieldFactory = $configFieldFactory;
        $this->configValueHandlerFactory = $configValueHandlerFactory;
        $this->methodApplierPool = $methodApplierPool;

        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $this->prepareMeta($meta),
            $this->prepareData($data)
        );
    }

    /**
     * @param ConfigFieldInterface $configField
     * @return array
     */
    private function getConfigFieldMeta(ConfigFieldInterface $configField)
    {
        return $configField->getUiMetaConfig();
    }

    /**
     * @param array $meta
     * @return array
     */
    private function prepareMeta(array $meta)
    {
        $applierOptions = [];
        $applierFieldsets = [];
        $applierFieldDependencies = [];

        foreach ($this->methodApplierPool->getSortedAppliers() as $applierCode => $methodApplier) {
            $fieldsetName = sprintf(self::BASE_FIELDSET_APPLIER_CONFIGURATION, $applierCode);

            $applierFieldsets[$fieldsetName] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType' => 'fieldset',
                            'component' => 'ShoppingFeed_Manager/js/form/components/fieldset',
                            'collapsible' => false,
                            'dataScope' => $applierCode,
                            'label' => '',
                        ],
                    ],
                ],
                'children' => array_map(
                    [ $this, 'getConfigFieldMeta' ],
                    $methodApplier->getConfig()->getFields()
                ),
            ];

            $applierFieldDependencies[] = [
                'values' => [ $applierCode ],
                'fieldNames' => [ $fieldsetName ],
            ];

            $applierOptions[] = [
                'value' => $applierCode,
                'label' => $methodApplier->getLabel(),
            ];
        }

        $applierSelect = $this->configFieldFactory->create(
            ConfigSelect::TYPE_CODE,
            [
                'name' => self::FIELD_APPLIER_CODE,
                'valueHandler' => $this->configValueHandlerFactory->create(
                    ConfigOptionHandler::TYPE_CODE,
                    [
                        'dataType' => UiText::NAME,
                        'optionArray' => $applierOptions,
                    ]
                ),
                'isRequired' => true,
                'label' => __('Pattern'),
                'dependencies' => $applierFieldDependencies,
                'sortOrder' => 0,
            ]
        );

        $meta = array_merge_recursive(
            $meta,
            [
                self::FIELDSET_SHIPPING_METHOD => [
                    'children' => array_merge(
                        $applierFieldsets,
                        [ self::FIELD_APPLIER_CODE => $applierSelect->getUiMetaConfig() ]
                    ),
                ],
            ]
        );

        return $meta;
    }

    /**
     * @param array $data
     * @return array
     */
    private function prepareData(array $data)
    {
        /** @var RuleInterface $rule */
        $rule = $this->coreRegistry->registry(RegistryConstants::CURRENT_SHIPPING_METHOD_RULE);
        $ruleId = $rule->getId();

        if (!empty($ruleId)) {
            try {
                $applierCode = $rule->getApplierCode();
                $ruleApplier = $this->methodApplierPool->getApplierByCode($applierCode);
                $applierConfigData = $ruleApplier->getConfig()
                    ->prepareRawDataForForm($rule->getApplierConfiguration()->getData());
            } catch (LocalizedException $e) {
                $applierCode = '';
                $applierConfigData = [];
            }

            $data[$ruleId] = array_merge(
                $data[$ruleId] ?? [],
                [
                    self::FIELD_RULE_ID => $rule->getId(),

                    self::DATA_SCOPE_RULE => [
                        self::FIELD_NAME => $rule->getName(),
                        self::FIELD_DESCRIPTION => $rule->getDescription(),
                        self::FIELD_IS_ACTIVE => $rule->isActive() ? '1' : '0',
                        self::FIELD_FROM_DATE => $rule->getFromDate(),
                        self::FIELD_TO_DATE => $rule->getToDate(),
                        self::FIELD_SORT_ORDER => $rule->getSortOrder(),
                        self::FIELD_CONDITIONS => $rule->getConditions(),

                        self::DATA_SCOPE_APPLIER => array_merge(
                            [ self::FIELD_APPLIER_CODE => $applierCode ],
                            array_filter([ $applierCode => $applierConfigData ])
                        ),
                    ],
                ]
            );
        }

        return $data;
    }

    public function getData()
    {
        return $this->data;
    }
}
