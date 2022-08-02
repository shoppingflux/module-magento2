<?php

namespace ShoppingFeed\Manager\Ui\Component\Listing\Column\Cron\Task;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use ShoppingFeed\Manager\Api\Data\Cron\TaskInterface;
use ShoppingFeed\Manager\DataObjectFactory;
use ShoppingFeed\Manager\Model\Command\ConfigInterface as CommandConfigInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use \Magento\Framework\Serialize\SerializerInterface;

class Stores extends Column
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * @var CommandConfigInterface
     */
    private $commandConfig;


    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param SerializerInterface $serializer
     * @param Escaper $escaper
     * @param DataObjectFactory $dataObjectFactory
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param CommandConfigInterface $commandConfig
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        SerializerInterface $serializer,
        Escaper $escaper,
        DataObjectFactory $dataObjectFactory,
        StoreCollectionFactory $storeCollectionFactory,
        CommandConfigInterface $commandConfig,
        array $components = [],
        array $data = []
    ) {
        $this->serializer = $serializer;
        $this->escaper = $escaper;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->commandConfig = $commandConfig;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }


    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $allStores = $this->storeCollectionFactory
                ->create()
                ->toOptionHash();

            $allStoreIds = array_keys($allStores);

            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[TaskInterface::COMMAND_CONFIGURATION])) {
                    try {
                        $configData = $this->serializer->unserialize($item[TaskInterface::COMMAND_CONFIGURATION]);

                        if (is_array($configData)) {
                            $configData = $this->dataObjectFactory->create([ 'data' => $configData ]);
                            $storeIds = $this->commandConfig->getStoreIds($configData);
                            $stores = array_intersect_key($allStores, array_flip($storeIds));

                            if (empty(array_diff($allStoreIds, $storeIds))) {
                                $item['stores'] = __('All Accounts');
                            } elseif (!empty($stores)) {
                                foreach ($stores as $storeName) {
                                    $item['stores'][] = $this->escaper->escapeHtml($storeName);
                                }

                                $item['stores'] = implode('<br />', $item['stores']);
                            } else {
                                $item['stores'] = __('None');
                            }
                        }
                    } catch (\Exception $e) {
                        // Do nothing.
                    }
                }
            }
        }

        return $dataSource;
    }
}
