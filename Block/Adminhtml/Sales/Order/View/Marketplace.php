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

    /**
     * @var string[]
     */
    private $ignoredAdditionalDataKeys;

    public function __construct(
        Context $context,
        Registry $registry,
        AdminHelper $adminHelper,
        MarketplaceOrderRepositoryInterface $marketplaceOrderRepository,
        array $ignoredAdditionalDataKeys = self::IGNORED_ADDITIONAL_DATA_KEYS,
        array $data = []
    ) {
        $this->marketplaceOrderRepository = $marketplaceOrderRepository;
        $this->ignoredAdditionalDataKeys = $ignoredAdditionalDataKeys;
        parent::__construct($context, $registry, $adminHelper, $data);
    }

    public function getOrder()
    {
        try {
            $order = parent::getOrder();
        } catch (\Exception $e) {
            if (
                ($invoice = $this->_coreRegistry->registry('invoice'))
                || ($invoice = $this->_coreRegistry->registry('current_invoice'))
            ) {
                $order = $invoice->getOrder();
                $this->setOrder($order);
            } else {
                throw $e;
            }
        }

        return $order;
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
                if (
                    !in_array($key, $this->ignoredAdditionalDataKeys, true)
                    && is_scalar($value)
                    && ('' !== ($value = trim((string) $value)))
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
