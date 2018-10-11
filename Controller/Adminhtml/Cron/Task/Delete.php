<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Cron\Task;

use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Controller\Adminhtml\Cron\TaskAction;

class Delete extends TaskAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::cron_task_delete';

    public function execute()
    {
        try {
            $task = $this->getTask();
            $this->taskRepository->delete($task);
            $this->messageManager->addSuccessMessage(__('The cron task has been successfully deleted.'));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This cron task does no longer exist.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('An error occurred while deleting the cron task.'));
        }

        $redirectResult = $this->resultRedirectFactory->create();
        return $redirectResult->setPath('*/*/');
    }
}
