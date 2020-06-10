<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Sales\Order\View;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Block\Adminhtml\Order\AbstractOrder;
use Magento\Sales\Helper\Admin as AdminHelper;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface as MarketplaceAddressInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Api\Marketplace\OrderRepositoryInterface as MarketplaceOrderRepositoryInterface;

class Marketplace extends AbstractOrder
{
    const IGNORED_ADDITIONAL_DATA_KEYS = [];

    /**
     * @var MarketplaceOrderRepositoryInterface
     */
    private $marketplaceOrderRepository;

    public function __construct(
        Context $context,
        Registry $registry,
        AdminHelper $adminHelper,
        MarketplaceOrderRepositoryInterface $marketplaceOrderRepository,
        array $data = []
    ) {
        $this->marketplaceOrderRepository = $marketplaceOrderRepository;
        parent::__construct($context, $registry, $adminHelper, $data);
    }

    /**
     * @return MarketplaceOrderInterface|null
     */
    public function getMarketplaceOrder()
    {
        $marketplaceOrder = $this->_getData('marketplace_order');

        if (false === $marketplaceOrder) {
            return null;
        } elseif (!$marketplaceOrder instanceof MarketplaceOrderInterface) {
            try {
                $marketplaceOrder = $this->marketplaceOrderRepository->getBySalesOrderId($this->getOrder()->getId());
                $this->setData('marketplace_order', $marketplaceOrder);
            } catch (\Exception $e) {
                $marketplaceOrder = null;
                $this->setData('marketplace_order', false);
            }
        }

        return $marketplaceOrder;
    }

    /**
     * @return array
     */
    public function getAdditionalData()
    {
        $data = [];

        if ($marketplaceOrder = $this->getMarketplaceOrder()) {
            foreach ($marketplaceOrder->getAdditionalFields()->getData() as $key => $value) {
                if (!in_array($key, static::IGNORED_ADDITIONAL_DATA_KEYS, true)
                    && is_scalar($value)
                    && ('' !== ($value = trim($value)))
                ) {
                    $data[$key] = $value;
                }
            }

            /** @var MarketplaceAddressInterface[] $addresses */
            $addresses = array_filter(
                [
                    'Billing' => $marketplaceOrder->getBillingAddress(),
                    'Shipping' => $marketplaceOrder->getShippingAddress(),
                ]
            );

            foreach ($addresses as $key => $address) {
                if ('' !== ($value = $address->getMiscData())) {
                    $data[(string) __($key)] = $value;
                }
            }
        }

        return $data;
    }
}
