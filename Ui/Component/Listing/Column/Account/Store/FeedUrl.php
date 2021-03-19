<?php

namespace ShoppingFeed\Manager\Ui\Component\Listing\Column\Account\Store;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Exporter as FeedExporter;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;

class FeedUrl extends Column
{
    const COLUMN_FEED_URL = 'feed_url';

    /**
     * @var FeedExporter $feedExporter
     */
    private $feedExporter;

    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * @var string[]|null
     */
    private $storeFeedUrls = null;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param FeedExporter $feedExporter
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        FeedExporter $feedExporter,
        StoreCollectionFactory $storeCollectionFactory,
        array $components = [],
        array $data = []
    ) {
        $this->feedExporter = $feedExporter;
        $this->storeCollectionFactory = $storeCollectionFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @return string[]
     */
    private function getStoreFeedUrls()
    {
        if (null === $this->storeFeedUrls) {
            $this->storeFeedUrls = [];
            $storeCollection = $this->storeCollectionFactory->create();

            /** @var StoreInterface $store */
            foreach ($storeCollection as $store) {
                $this->storeFeedUrls[$store->getId()] = [
                    'url' => $this->feedExporter->getStoreFeedUrl($store),
                    'is_available' => $this->feedExporter->isStoreFeedAvailable($store),
                ];
            }
        }

        return $this->storeFeedUrls;
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $storeFeedUrls = $this->getStoreFeedUrls();

            foreach ($dataSource['data']['items'] as &$item) {
                if (
                    isset($item[StoreInterface::STORE_ID])
                    && isset($storeFeedUrls[$item[StoreInterface::STORE_ID]])
                ) {
                    $feedUrl = $storeFeedUrls[$item[StoreInterface::STORE_ID]];

                    if ($feedUrl['is_available']) {
                        $item[self::COLUMN_FEED_URL] = [
                            'url' => $feedUrl['url'],
                            'label' => $feedUrl['url'],
                            'suffix' => '',
                        ];
                    } else {
                        $item[self::COLUMN_FEED_URL] = [
                            'url' => '#',
                            'label' => $feedUrl['url'],
                            'suffix' => __('(the feed will automatically be generated in a few moments)'),
                        ];
                    }
                }
            }
        }

        return $dataSource;
    }
}
