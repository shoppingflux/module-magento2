<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\Order\Log;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use Magento\Ui\Component\MassAction\Filter as MassActionFilter;
use Psr\Log\LoggerInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\LogInterface;
use ShoppingFeed\Manager\Api\Marketplace\Order\LogRepositoryInterface;
use ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\Order\LogAction;
use ShoppingFeed\Manager\Model\Marketplace\Order\Notification\UnreadLogs;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Log\CollectionFactory as LogCollectionFactory;

class MassMarkAsRead extends LogAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::marketplace_order_log_mark_as_read';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var MassActionFilter
     */
    private $massActionFilter;

    /**
     * @var LogCollectionFactory
     */
    private $logCollectionFactory;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param PageResultFactory $pageResultFactory
     * @param CacheInterface $cache
     * @param MassActionFilter $massActionFilter
     * @param LogRepositoryInterface $logRepository
     * @param LogCollectionFactory $logCollectionFactory
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        PageResultFactory $pageResultFactory,
        CacheInterface $cache,
        MassActionFilter $massActionFilter,
        LogRepositoryInterface $logRepository,
        LogCollectionFactory $logCollectionFactory
    ) {
        $this->logger = $logger;
        $this->cache = $cache;
        $this->massActionFilter = $massActionFilter;
        $this->logCollectionFactory = $logCollectionFactory;
        parent::__construct($context, $pageResultFactory, $logRepository);
    }

    public function execute()
    {
        try {
            $logCollection = $this->massActionFilter->getCollection($this->logCollectionFactory->create());

            $readCount = 0;
            $errorCount = 0;

            /** @var LogInterface $log */
            foreach ($logCollection as $log) {
                try {
                    $log->setIsRead(true);
                    $this->logRepository->save($log);
                    ++$readCount;
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage(), $e->getTrace());
                    ++$errorCount;
                }
            }

            $this->cache->remove(UnreadLogs::CACHE_KEY);

            $unknownCount = $logCollection->count() - $readCount - $errorCount;

            if ($readCount > 0) {
                $this->messageManager->addSuccessMessage(
                    __('%1 order logs have been marked as read.', $readCount)
                );
            }

            if ($errorCount > 0) {
                $this->messageManager->addErrorMessage(
                    __('%1 order logs could not be marked as read (see error log for details).', $errorCount)
                );
            }

            if ($unknownCount > 0) {
                $this->messageManager->addWarningMessage(
                    __('%1 order logs could not be found.', $unknownCount)
                );
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('An error occurred while marking the order logs as read.')
            );
        }

        $redirectResult = $this->resultRedirectFactory->create();

        return $redirectResult->setPath('*/*/');
    }
}
