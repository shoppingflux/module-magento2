<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Cron\Task;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\Cron\TaskRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Cron\TaskInterfaceFactory;
use ShoppingFeed\Manager\Controller\Adminhtml\Cron\TaskAction;
use ShoppingFeed\Manager\Model\Cron\Task\Runner as TaskRunner;

class Run extends TaskAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::cron_task_run';

    /**
     * @var TaskRunner
     */
    private $taskRunner;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageResultFactory $pageResultFactory
     * @param TaskRepositoryInterface $taskRepository
     * @param TaskInterfaceFactory $taskFactory
     * @param TaskRunner $taskRunner
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageResultFactory $pageResultFactory,
        TaskRepositoryInterface $taskRepository,
        TaskInterfaceFactory $taskFactory,
        TaskRunner $taskRunner
    ) {
        $this->taskRunner = $taskRunner;
        parent::__construct($context, $coreRegistry, $pageResultFactory, $taskRepository, $taskFactory);
    }

    public function execute()
    {
        try {
            $task = $this->getTask();
            $this->taskRunner->runTask($task);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This cron task does no longer exist.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('An error occurred while running the cron task.'));
        }

        $redirectResult = $this->resultRedirectFactory->create();
        return $redirectResult->setPath('*/*/');
    }
}
