<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Cron\Task;

use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Controller\Adminhtml\Cron\TaskAction;
use ShoppingFeed\Manager\Model\Cron\Task\RegistryConstants;

class Edit extends TaskAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::cron_task_edit';

    public function execute()
    {
        try {
            $task = $this->getTask(null, false);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This cron task does no longer exist.'));
            $redirectResult = $this->resultRedirectFactory->create();
            return $redirectResult->setPath('*/*/');
        }

        $this->coreRegistry->register(RegistryConstants::CURRENT_CRON_TASK, $task);
        return $this->initPage()->addBreadcrumb(__('Edit Cron Task'), __('Edit Cron Task'));
    }
}
