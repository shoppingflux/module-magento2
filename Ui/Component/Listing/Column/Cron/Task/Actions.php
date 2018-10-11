<?php

namespace ShoppingFeed\Manager\Ui\Component\Listing\Column\Cron\Task;

use ShoppingFeed\Manager\Api\Data\Cron\TaskInterface;
use ShoppingFeed\Manager\Controller\Adminhtml\Cron\TaskAction;
use ShoppingFeed\Manager\Ui\Component\Listing\Column\AbstractActions;

class Actions extends AbstractActions
{
    const ACL_RUN = 'ShoppingFeed_Manager::cron_task_run';
    const ACL_EDIT = 'ShoppingFeed_Manager::cron_task_edit';
    const ACL_DELETE = 'ShoppingFeed_Manager::cron_task_delete';

    const URL_PATH_RUN = 'shoppingfeed_manager/cron_task/run';
    const URL_PATH_EDIT = 'shoppingfeed_manager/cron_task/edit';
    const URL_PATH_DELETE = 'shoppingfeed_manager/cron_task/delete';

    public function prepareDataSource(array $dataSource)
    {
        $isRunAllowed = $this->authorizationModel->isAllowed(static::ACL_RUN);
        $isEditAllowed = $this->authorizationModel->isAllowed(static::ACL_EDIT);
        $isDeleteAllowed = $this->authorizationModel->isAllowed(static::ACL_DELETE);

        if (!$isRunAllowed && !$isEditAllowed && !$isDeleteAllowed) {
            return $dataSource;
        }

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[TaskInterface::TASK_ID])) {
                    $name = $this->getData('name');
                    $item[$name] = [];

                    if ($isRunAllowed) {
                        $item[$name]['run'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_RUN,
                                [ TaskAction::REQUEST_KEY_TASK_ID => $item[TaskInterface::TASK_ID] ]
                            ),
                            'label' => __('Run'),
                            'confirm' => [
                                'title' => __('Run Cron Task'),
                                'message' => __(
                                    'Are you sure you want to run this cron task? (this could take a while)'
                                ),
                            ],
                        ];
                    }

                    if ($isEditAllowed) {
                        $item[$name]['edit'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_EDIT,
                                [ TaskAction::REQUEST_KEY_TASK_ID => $item[TaskInterface::TASK_ID] ]
                            ),
                            'label' => __('Edit'),
                        ];
                    }

                    if ($isDeleteAllowed) {
                        $item[$name]['delete'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_DELETE,
                                [ TaskAction::REQUEST_KEY_TASK_ID => $item[TaskInterface::TASK_ID] ]
                            ),
                            'label' => __('Delete'),
                            'confirm' => [
                                'title' => __('Delete Cron Task'),
                                'message' => __('Are you sure you want to delete this cron task?'),
                            ],
                        ];
                    }
                }
            }
        }

        return $dataSource;
    }
}
