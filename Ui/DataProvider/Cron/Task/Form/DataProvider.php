<?php

namespace ShoppingFeed\Manager\Ui\DataProvider\Cron\Task\Form;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as BaseDataProvider;
use Magento\Ui\Component\Form\Element\DataType\Text as UiText;
use ShoppingFeed\Manager\Api\Data\Cron\TaskInterface;
use ShoppingFeed\Manager\Model\Config\FieldInterface as ConfigFieldInterface;
use ShoppingFeed\Manager\Model\Config\FieldFactoryInterface as ConfigFieldFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Field\Select as ConfigSelect;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ConfigValueHandlerFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Option as ConfigOptionHandler;
use ShoppingFeed\Manager\Model\CommandPoolInterface;
use ShoppingFeed\Manager\Model\Cron\Schedule\Type\Source as ScheduleTypeSource;
use ShoppingFeed\Manager\Model\Cron\Task\RegistryConstants;

class DataProvider extends BaseDataProvider
{
    const FORM_NAMESPACE = 'sfm_cron_task_form';

    const DATA_SCOPE_TASK = 'task';
    const DATA_SCOPE_COMMAND = 'command';

    const FIELDSET_TASK_INFORMATION = 'task_information';
    const FIELDSET_COMMAND = 'command';
    const BASE_FIELDSET_COMMAND_CONFIGURATION = 'command_%s';

    const FIELD_TASK_ID = 'task_id';
    const FIELD_NAME = 'name';
    const FIELD_DESCRIPTION = 'description';
    const FIELD_COMMAND_CODE = 'command_code';
    const FIELD_SCHEDULE_TYPE = 'schedule_type';
    const FIELD_CRON_EXPRESSION = 'cron_expression';
    const FIELD_IS_ACTIVE = 'is_active';

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
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var ScheduleTypeSource
     */
    private $scheduleTypeSource;

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
     * @param CommandPoolInterface $commandPool
     * @param ScheduleTypeSource $scheduleTypeSource
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        Registry $coreRegistry,
        ConfigFieldFactoryInterface $configFieldFactory,
        ConfigValueHandlerFactoryInterface $configValueHandlerFactory,
        CommandPoolInterface $commandPool,
        ScheduleTypeSource $scheduleTypeSource,
        array $meta = [],
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->configFieldFactory = $configFieldFactory;
        $this->configValueHandlerFactory = $configValueHandlerFactory;
        $this->commandPool = $commandPool;
        $this->scheduleTypeSource = $scheduleTypeSource;

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
        $scheduleTypeSwitcherConfigRules = [];

        foreach ($this->scheduleTypeSource->toOptionArray() as $scheduleTypeOption) {
            $callback = (TaskInterface::SCHEDULE_TYPE_CUSTOM === $scheduleTypeOption['value']) ? 'show' : 'hide';

            $scheduleTypeSwitcherConfigRules[] = [
                'value' => $scheduleTypeOption['value'],
                'actions' => [
                    [
                        'target' => '${$.parentName}.' . self::FIELD_CRON_EXPRESSION,
                        'callback' => $callback,
                    ],
                ],
            ];
        }

        $commandOptions = [];
        $commandFieldsets = [];
        $commandFieldDependencies = [];

        foreach ($this->commandPool->getGroups() as $commandGroup) {
            $subCommandOptions = [];

            foreach ($commandGroup->getCommands() as $commandCode => $command) {
                $subCommandOptions[] = [
                    'value' => $commandCode,
                    'label' => $command->getLabel(),
                ];
            }

            $commandOptions[] = [
                'value' => $subCommandOptions,
                'label' => $commandGroup->getLabel(),
            ];
        }

        foreach ($this->commandPool->getCommands() as $commandCode => $command) {
            $fieldsetName = sprintf(self::BASE_FIELDSET_COMMAND_CONFIGURATION, $commandCode);

            $commandFieldsets[$fieldsetName] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType' => 'fieldset',
                            'component' => 'ShoppingFeed_Manager/js/form/components/fieldset',
                            'collapsible' => false,
                            'dataScope' => $commandCode,
                            'label' => '',
                            'additionalClasses' => 'admin__sfm-nested-fieldset',
                        ],
                    ],
                ],
                'children' => array_map(
                    [ $this, 'getConfigFieldMeta' ],
                    $command->getConfig()->getFields()
                ),
            ];

            $commandFieldDependencies[] = [
                'values' => [ $commandCode ],
                'fieldNames' => [ $fieldsetName ],
            ];
        }

        $commandSelect = $this->configFieldFactory->create(
            ConfigSelect::TYPE_CODE,
            [
                'name' => self::FIELD_COMMAND_CODE,
                'valueHandler' => $this->configValueHandlerFactory->create(
                    ConfigOptionHandler::TYPE_CODE,
                    [
                        'dataType' => UiText::NAME,
                        'optionArray' => $commandOptions,
                    ]
                ),
                'isRequired' => true,
                'label' => __('Command'),
                'dependencies' => $commandFieldDependencies,
                'sortOrder' => 0,
            ]
        );

        $meta = array_merge_recursive(
            $meta,
            [
                self::FIELDSET_TASK_INFORMATION => [
                    'children' => [
                        self::FIELD_SCHEDULE_TYPE => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'switcherConfig' => [
                                            'enabled' => true,
                                            'rules' => $scheduleTypeSwitcherConfigRules,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                self::FIELDSET_COMMAND => [
                    'children' => array_merge(
                        $commandFieldsets,
                        [ self::FIELD_COMMAND_CODE => $commandSelect->getUiMetaConfig() ]
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
        /** @var TaskInterface $task */
        $task = $this->coreRegistry->registry(RegistryConstants::CURRENT_CRON_TASK);
        $taskId = $task->getId();

        if (!empty($taskId)) {
            try {
                $commandCode = $task->getCommandCode();
                $taskCommand = $this->commandPool->getCommandByCode($commandCode);
                $commandConfigData = $taskCommand->getConfig()
                    ->prepareRawDataForForm($task->getCommandConfiguration()->getData());
            } catch (LocalizedException $e) {
                $commandCode = '';
                $commandConfigData = [];
            }

            $scheduleType = $task->getScheduleType();
            $hasCronExpression = (TaskInterface::SCHEDULE_TYPE_CUSTOM === $scheduleType);
            $cronExpression = $hasCronExpression ? $task->getCronExpression() : null;

            $data[$taskId] = array_merge(
                $data[$taskId] ?? [],
                [
                    self::FIELD_TASK_ID => $task->getId(),

                    self::DATA_SCOPE_TASK => [
                        self::FIELD_NAME => $task->getName(),
                        self::FIELD_DESCRIPTION => $task->getDescription(),
                        self::FIELD_SCHEDULE_TYPE => $scheduleType,
                        self::FIELD_CRON_EXPRESSION => $cronExpression,
                        self::FIELD_IS_ACTIVE => $task->isActive() ? '1' : '0',

                        self::DATA_SCOPE_COMMAND => array_merge(
                            [ self::FIELD_COMMAND_CODE => $commandCode ],
                            array_filter([ $commandCode => $commandConfigData ])
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
