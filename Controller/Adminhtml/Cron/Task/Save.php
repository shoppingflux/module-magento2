<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Cron\Task;

use Magento\Backend\App\Action\Context;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\Cron\TaskRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Cron\TaskInterfaceFactory;
use ShoppingFeed\Manager\Controller\Adminhtml\Cron\TaskAction;
use ShoppingFeed\Manager\Model\CommandPoolInterface;
use ShoppingFeed\Manager\Ui\DataProvider\Cron\Task\Form\DataProvider as TaskFormDataProvider;

class Save extends TaskAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::cron_task_edit';

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageResultFactory $pageResultFactory
     * @param TaskRepositoryInterface $taskRepository
     * @param TaskInterfaceFactory $taskFactory
     * @param DataObjectFactory $dataObjectFactory
     * @param CommandPoolInterface $commandPool
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageResultFactory $pageResultFactory,
        TaskRepositoryInterface $taskRepository,
        TaskInterfaceFactory $taskFactory,
        DataObjectFactory $dataObjectFactory,
        CommandPoolInterface $commandPool
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->commandPool = $commandPool;
        parent::__construct($context, $coreRegistry, $pageResultFactory, $taskRepository, $taskFactory);
    }

    public function execute()
    {
        $redirectResult = $this->resultRedirectFactory->create();

        try {
            $task = $this->getTask();
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This cron task does no longer exist.'));
            return $redirectResult->setPath('*/*/');
        }

        $data = (array) $this->getRequest()->getPostValue();
        $isSaveSuccessful = false;

        try {
            if (!isset($data[TaskFormDataProvider::DATA_SCOPE_TASK])
                || !is_array($data[TaskFormDataProvider::DATA_SCOPE_TASK])
            ) {
                throw new LocalizedException(__('The request is incomplete.'));
            }

            $taskData = $data[TaskFormDataProvider::DATA_SCOPE_TASK];

            if (!isset($taskData[TaskFormDataProvider::FIELD_NAME])
                || empty($taskData[TaskFormDataProvider::FIELD_NAME])
                || !isset($taskData[TaskFormDataProvider::DATA_SCOPE_COMMAND])
                || !is_array($taskData[TaskFormDataProvider::DATA_SCOPE_COMMAND])
                || !isset($taskData[TaskFormDataProvider::DATA_SCOPE_COMMAND][TaskFormDataProvider::FIELD_COMMAND_CODE])
            ) {
                throw new LocalizedException(__('The request is incomplete.'));
            }

            $commandData = $taskData[TaskFormDataProvider::DATA_SCOPE_COMMAND];
            $commandCode = $commandData[TaskFormDataProvider::FIELD_COMMAND_CODE];
            $command = $this->commandPool->getCommandByCode($commandCode);
            $commandConfigData = $command->getConfig()
                ->prepareFormDataForSave((array) ($commandData[$commandCode] ?? []));

            $task->setName($taskData[TaskFormDataProvider::FIELD_NAME]);
            $task->setDescription($taskData[TaskFormDataProvider::FIELD_DESCRIPTION] ?? '');
            $task->setCommandCode($commandCode);
            $task->setCommandConfiguration($this->dataObjectFactory->create([ 'data' => $commandConfigData ]));
            $task->setCronExpression($taskData[TaskFormDataProvider::FIELD_CRON_EXPRESSION]);
            $task->setScheduleType($taskData[TaskFormDataProvider::FIELD_SCHEDULE_TYPE]);
            $task->setIsActive($taskData[TaskFormDataProvider::FIELD_IS_ACTIVE]);

            $this->taskRepository->save($task);
            $isSaveSuccessful = true;
            $this->messageManager->addSuccessMessage(__('The cron task has been successfully saved.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('An error occurred while saving the cron task.'));
        }

        if (!$isSaveSuccessful || $this->getRequest()->getParam('back')) {
            return $redirectResult->setPath('*/*/edit', [ self::REQUEST_KEY_TASK_ID => $task->getId() ]);
        }

        return $redirectResult->setPath('*/*/');
    }
}
