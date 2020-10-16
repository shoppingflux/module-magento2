<?php

namespace ShoppingFeed\Manager\Model\Marketplace;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Registry;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants as StoreRegistryConstants;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\OrderFactory as OrderResourceFactory;
use ShoppingFeed\Manager\Model\ShoppingFeed\Api\SessionManager as ApiSessionManager;
use ShoppingFeed\Manager\Model\StringHelper;

class Source implements OptionSourceInterface
{
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var ApiSessionManager
     */
    private $apiSessionManager;

    /**
     * @var StringHelper
     */
    private $stringHelper;

    /**
     * @var OrderResourceFactory
     */
    private $orderResourceFactory;

    /**
     * @var array|null
     */
    private $defaultMarketplaces = null;

    /***
     * @var array[]
     */
    private $storeMarketplaces = [];

    /**
     * @param Registry $coreRegistry
     * @param ApiSessionManager $apiSessionManager
     * @param StringHelper $stringHelper
     * @param OrderResourceFactory $orderResourceFactory
     */
    public function __construct(
        Registry $coreRegistry,
        ApiSessionManager $apiSessionManager,
        StringHelper $stringHelper,
        OrderResourceFactory $orderResourceFactory
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->apiSessionManager = $apiSessionManager;
        $this->stringHelper = $stringHelper;
        $this->orderResourceFactory = $orderResourceFactory;
    }

    /**
     * @param array $options
     * @return array
     */
    private function sortMarketplaceOptions(array $options)
    {
        usort(
            $options,
            function ($optionA, $optionB) {
                return strnatcmp($optionA['label'], $optionB['label']);
            }
        );

        return $options;
    }

    public function toOptionArray()
    {
        $store = $this->coreRegistry->registry(StoreRegistryConstants::CURRENT_ACCOUNT_STORE);

        if ($store instanceof StoreInterface) {
            $storeId = $store->getId();

            if (isset($this->storeMarketplaces[$storeId])) {
                return $this->storeMarketplaces[$storeId];
            }

            try {
                $resource = $this->apiSessionManager->getStoreApiResource($store);
                $channels = $resource->getChannelApi()->getAll();
                $this->storeMarketplaces[$storeId] = [];

                foreach ($channels as $channel) {
                    if ($channel->isInstalled()) {
                        $name = trim($channel->getName());
                        $code = $this->stringHelper->getNormalizedCode($name);

                        $this->storeMarketplaces[$storeId][$code] = [
                            'value' => $code,
                            'label' => $name,
                            'channel_id' => $channel->getId(),
                        ];
                    }
                }

                $this->storeMarketplaces[$storeId] = $this->sortMarketplaceOptions($this->storeMarketplaces[$storeId]);
            } catch (\Exception $e) {
                unset($this->storeMarketplaces[$storeId]);
            }

            if (isset($this->storeMarketplaces[$storeId])) {
                return array_values($this->storeMarketplaces[$storeId]);
            }
        }

        if (null === $this->defaultMarketplaces) {
            $orderResource = $this->orderResourceFactory->create();
            $marketplaces = $orderResource->getChannelMap();
            $this->defaultMarketplaces = [];

            foreach ($marketplaces as $channelId => $marketplaceName) {
                $this->defaultMarketplaces[$channelId] = [
                    'value' => $this->stringHelper->getNormalizedCode($marketplaceName),
                    'label' => trim($marketplaceName),
                    'channel_id' => (int) $channelId,
                ];
            }

            $this->defaultMarketplaces = $this->sortMarketplaceOptions($this->defaultMarketplaces);
        }

        return array_values($this->defaultMarketplaces);
    }
}
