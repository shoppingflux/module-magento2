<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Cron;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page as PageResult;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\Cron\TaskRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Cron\TaskInterface;
use ShoppingFeed\Manager\Api\Data\Cron\TaskInterfaceFactory;

abstract class TaskAction extends Action
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::cron_tasks';
    const REQUEST_KEY_TASK_ID = 'task_id';

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var PageResultFactory
     */
    protected $pageResultFactory;

    /**
     * @var TaskRepositoryInterface
     */
    protected $taskRepository;

    /**
     * @var TaskInterfaceFactory
     */
    protected $taskFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageResultFactory $pageResultFactory
     * @param TaskRepositoryInterface $taskRepository
     * @param TaskInterfaceFactory $taskFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageResultFactory $pageResultFactory,
        TaskRepositoryInterface $taskRepository,
        TaskInterfaceFactory $taskFactory
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->pageResultFactory = $pageResultFactory;
        $this->taskRepository = $taskRepository;
        $this->taskFactory = $taskFactory;
        parent::__construct($context);
    }

    /**
     * @param int|null $taskId
     * @param bool $requireLoaded
     * @return TaskInterface
     * @throws NoSuchEntityException
     */
    protected function getTask($taskId = null, $requireLoaded = false)
    {
        if (null === $taskId) {
            $taskId = (int) $this->getRequest()->getParam(static::REQUEST_KEY_TASK_ID);
        }

        if (empty($taskId) && !$requireLoaded) {
            $task = $this->taskFactory->create();
        } else {
            try {
                $task = $this->taskRepository->getById($taskId);
            } catch (NoSuchEntityException $e) {
                if (!$requireLoaded) {
                    $task = $this->taskFactory->create();
                } else {
                    throw $e;
                }
            }
        }

        return $task;
    }

    /**
     * @return PageResult
     */
    protected function initPage()
    {
        /** @var PageResult $pageResult */
        $pageResult = $this->pageResultFactory->create();
        $pageResult->setActiveMenu('ShoppingFeed_Manager::cron_tasks');
        $pageResult->addBreadcrumb(__('Shopping Feed'), __('Shopping Feed'));
        $pageResult->addBreadcrumb(__('Cron Tasks'), __('Cron Tasks'));
        $pageResult->getConfig()->getTitle()->prepend(__('Cron Tasks'));
        return $pageResult;
    }
}
