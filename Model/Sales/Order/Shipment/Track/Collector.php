<?php

namespace ShoppingFeed\Manager\Model\Sales\Order\Shipment\Track;

use Magento\Sales\Api\Data\OrderInterface as OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface as ShipmentInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as SalesShipmentCollectionFactory;
use ShoppingFeed\Manager\Model\Sales\Order\Shipment\Track as ShipmentTrack;
use ShoppingFeed\Manager\Model\Sales\Order\Shipment\TrackFactory as ShipmentTrackFactory;

class Collector
{
    const DEFAULT_CARRIER_CODE = 'sfm_carrier';

    const DEFAULT_BASE_TRACK_RELEVANCE = 0;
    const DEFAULT_FILLED_CARRIER_CODE_RELEVANCE = 200;
    const DEFAULT_FILLED_CARRIER_TITLE_RELEVANCE = 100;
    const DEFAULT_FILLED_TRACKING_NUMBER_RELEVANCE = 200;
    const DEFAULT_FILLED_TRACKING_URL_RELEVANCE = 300;

    /**
     * @var SalesShipmentCollectionFactory
     */
    private $salesShipmentCollectionFactory;

    /**
     * @var ShipmentTrackFactory
     */
    private $shipmentTrackFactory;

    /**
     * @param SalesShipmentCollectionFactory $salesShipmentCollectionFactory
     * @param ShipmentTrackFactory $shipmentTrackFactory
     */
    public function __construct(
        SalesShipmentCollectionFactory $salesShipmentCollectionFactory,
        ShipmentTrackFactory $shipmentTrackFactory
    ) {
        $this->salesShipmentCollectionFactory = $salesShipmentCollectionFactory;
        $this->shipmentTrackFactory = $shipmentTrackFactory;
    }

    /**
     * @param int[] $orderIds
     * @return ShipmentInterface[]
     */
    private function getOrderShipmentsByOrder(array $orderIds)
    {
        $shipmentCollection = $this->salesShipmentCollectionFactory->create();
        $shipmentCollection->addFieldToFilter(ShipmentInterface::ORDER_ID, [ 'in' => $orderIds ]);
        $orderShipments = [];

        /** @var ShipmentInterface $shipment */
        foreach ($shipmentCollection as $shipment) {
            $orderShipments[$shipment->getOrderId()][] = $shipment;
        }

        return $orderShipments;
    }

    /**
     * @param ShipmentInterface $shipment
     * @return ShipmentTrack[]
     */
    public function getShipmentTracks(ShipmentInterface $shipment)
    {
        $shipmentTracks = [];

        foreach ($shipment->getTracks() as $shipmentTrack) {
            $carrierCode = trim($shipmentTrack->getCarrierCode());
            $carrierTitle = trim($shipmentTrack->getTitle());
            $trackingNumber = trim($shipmentTrack->getTrackNumber());
            $trackingUrl = is_callable([ $shipmentTrack, 'getUrl' ]) ? trim($shipmentTrack->getUrl()) : '';
            $relevance = static::DEFAULT_BASE_TRACK_RELEVANCE;

            if (!empty($carrierCode)) {
                $relevance += static::DEFAULT_FILLED_CARRIER_CODE_RELEVANCE;
            } else {
                $carrierCode = static::DEFAULT_CARRIER_CODE;
            }

            if (!empty($carrierTitle)) {
                $relevance += static::DEFAULT_FILLED_CARRIER_TITLE_RELEVANCE;
            } else {
                $carrierTitle = __('Carrier');
            }

            if (!empty($trackingNumber)) {
                $relevance += static::DEFAULT_FILLED_TRACKING_NUMBER_RELEVANCE;
            }

            if (!empty($trackingUrl)) {
                $relevance += static::DEFAULT_FILLED_TRACKING_URL_RELEVANCE;
            }

            $shipmentTracks[] = $this->shipmentTrackFactory->create(
                [
                    'carrierCode' => $carrierCode,
                    'carrierTitle' => $carrierTitle,
                    'trackingNumber' => $trackingNumber,
                    'trackingUrl' => $trackingUrl,
                    'relevance' => $relevance,
                ]
            );
        }

        return $shipmentTracks;
    }

    /**
     * @param OrderInterface[]|int[] $orders
     * @return ShipmentTrack[][]
     */
    public function getOrdersShipmentTracks(array $orders)
    {
        $orderIds = [];

        foreach ($orders as $order) {
            if ($order instanceof OrderInterface) {
                $orderIds[] = (int) $order->getId();
            } else {
                $orderIds[] = (int) $order;
            }
        }

        $shipmentTracks = array_fill_keys($orderIds, []);
        $orderShipments = $this->getOrderShipmentsByOrder($orderIds);

        foreach ($orderShipments as $orderId => $shipments) {
            $orderTracks = [];

            foreach ($shipments as $shipment) {
                $orderTracks = array_merge($orderTracks, $this->getShipmentTracks($shipment));
            }

            $shipmentTracks[$orderId] = $orderTracks;
        }

        return $shipmentTracks;
    }
}
