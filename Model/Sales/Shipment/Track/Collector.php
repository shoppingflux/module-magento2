<?php

namespace ShoppingFeed\Manager\Model\Sales\Shipment\Track;

use Magento\Framework\DataObject;
use Magento\Sales\Api\Data\ShipmentInterface as ShipmentInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as SalesShipmentCollectionFactory;
use Magento\Shipping\Model\Order\Track as SalesOrderTrack;
use Magento\Shipping\Model\ResourceModel\Order\Track\CollectionFactory as SalesOrderTrackCollectionFactory;
use ShoppingFeed\Manager\Model\Sales\Order\Shipment\Track as ShipmentTrack;
use ShoppingFeed\Manager\Model\Sales\Order\Shipment\Track\Collector as OriginalCollector;
use ShoppingFeed\Manager\Model\Sales\Order\Shipment\TrackFactory as ShipmentTrackFactory;

class Collector extends OriginalCollector
{
    /**
     * @var SalesShipmentCollectionFactory
     */
    private $salesShipmentCollectionFactory;

    /**
     * @var SalesOrderTrackCollectionFactory|null
     */
    private $salesOrderTrackCollectionFactory;

    /**
     * @var ShipmentTrackFactory
     */
    private $shipmentTrackFactory;

    /**
     * @param SalesShipmentCollectionFactory $salesShipmentCollectionFactory
     * @param SalesOrderTrackCollectionFactory $salesOrderTrackCollectionFactory
     * @param ShipmentTrackFactory $shipmentTrackFactory
     */
    public function __construct(
        SalesShipmentCollectionFactory $salesShipmentCollectionFactory,
        SalesOrderTrackCollectionFactory $salesOrderTrackCollectionFactory,
        ShipmentTrackFactory $shipmentTrackFactory
    ) {
        $this->salesShipmentCollectionFactory = $salesShipmentCollectionFactory;
        $this->salesOrderTrackCollectionFactory = $salesOrderTrackCollectionFactory;
        $this->shipmentTrackFactory = $shipmentTrackFactory;
        parent::__construct($salesShipmentCollectionFactory, $shipmentTrackFactory);
    }

    /**
     * @param ShipmentInterface $shipment
     * @return ShipmentTrack[]
     */
    public function getShipmentTracks(ShipmentInterface $shipment)
    {
        $shipmentTracks = [];

        $salesOrderTrackCollection = $this->salesOrderTrackCollectionFactory->create();
        $salesOrderTrackCollection->setShipmentFilter($shipment->getEntityId());

        /** @var SalesOrderTrack $salesOrderTrack */
        foreach ($salesOrderTrackCollection as $salesOrderTrack) {
            $salesOrderTrack->setShipment($shipment);

            try {
                $trackingDetail = $salesOrderTrack->getNumberDetail();

                if ($trackingDetail instanceof DataObject) {
                    $carrierCode = trim($trackingDetail->getCarrier());
                    $carrierTitle = trim($trackingDetail->getCarrierTitle());
                    $trackingNumber = trim($trackingDetail->getTracking());
                    $trackingUrl = trim($trackingDetail->getUrl());
                } else {
                    throw new \Exception();
                }
            } catch (\Exception $e) {
                $carrierCode = '';
                $carrierTitle = '';
                $trackingNumber = '';
                $trackingUrl = '';
            }

            if (empty($carrierCode)) {
                $carrierCode = trim($salesOrderTrack->getCarrierCode());
            }

            if (empty($carrierTitle)) {
                $carrierTitle = trim($salesOrderTrack->getTitle());
            }

            if (empty($trackingNumber)) {
                $trackingNumber = trim($salesOrderTrack->getTrackNumber());
            }

            if (empty($trackingUrl)) {
                $trackingUrl = is_callable([ $salesOrderTrack, 'getUrl' ]) ? trim($salesOrderTrack->getUrl()) : '';
            }

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
}
