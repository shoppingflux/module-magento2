<?php

namespace ShoppingFeed\Manager\Model\Sales\Shipment\Track;

use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\ShipmentInterface as ShipmentInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as SalesShipmentCollectionFactory;
use Magento\Shipping\Model\Order\Track as SalesOrderTrack;
use Magento\Shipping\Model\ResourceModel\Order\Track\CollectionFactory as SalesOrderTrackCollectionFactory;
use ShoppingFeed\Manager\Model\Sales\Order\Shipment\Track as ShipmentTrack;
use ShoppingFeed\Manager\Model\Sales\Order\Shipment\Track\Collector as OriginalCollector;
use ShoppingFeed\Manager\Model\Sales\Order\Shipment\TrackFactory as ShipmentTrackFactory;
use ShoppingFeed\Manager\Model\TimeHelper;

class Collector extends OriginalCollector
{
    /**
     * @var TimeHelper
     */
    private $timeHelper;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

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
     * @param TimeHelper $timeHelper
     * @param TimezoneInterface $localeDate
     * @param SalesShipmentCollectionFactory $salesShipmentCollectionFactory
     * @param SalesOrderTrackCollectionFactory $salesOrderTrackCollectionFactory
     * @param ShipmentTrackFactory $shipmentTrackFactory
     */
    public function __construct(
        TimeHelper $timeHelper,
        TimezoneInterface $localeDate,
        SalesShipmentCollectionFactory $salesShipmentCollectionFactory,
        SalesOrderTrackCollectionFactory $salesOrderTrackCollectionFactory,
        ShipmentTrackFactory $shipmentTrackFactory
    ) {
        $this->timeHelper = $timeHelper;
        $this->localeDate = $localeDate;
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
        $nowTimestamp = $this->timeHelper->utcTimestamp();

        /**
         * @see TimezoneInterface::scopeDate() is not reliable on M2.3.x versions >= 2.3.4.
         * See https://github.com/magento/magento2/issues/26675 for examples why.
         */
        $shipmentDate = \DateTime::createFromFormat('Y-m-d H:i:s', $shipment->getCreatedAt());

        $shipmentDelay = (int) floor(($nowTimestamp - $shipmentDate->getTimestamp()) / 3600);

        $salesOrderTrackCollection = $this->salesOrderTrackCollectionFactory->create();
        $salesOrderTrackCollection->setShipmentFilter($shipment->getEntityId());

        $shipmentTracks = [];

        /** @var SalesOrderTrack $salesOrderTrack */
        foreach ($salesOrderTrackCollection as $salesOrderTrack) {
            $salesOrderTrack->setShipment($shipment);

            try {
                $trackingDetail = $salesOrderTrack->getNumberDetail();

                if ($trackingDetail instanceof DataObject) {
                    $carrierCode = trim((string) $trackingDetail->getCarrier());
                    $carrierTitle = trim((string) $trackingDetail->getCarrierTitle());
                    $trackingNumber = trim((string) $trackingDetail->getTracking());
                    $trackingUrl = trim((string) $trackingDetail->getUrl());
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
                $carrierCode = trim((string) $salesOrderTrack->getCarrierCode());
            }

            if (empty($carrierTitle)) {
                $carrierTitle = trim((string) $salesOrderTrack->getTitle());
            }

            if (empty($trackingNumber)) {
                $trackingNumber = trim((string) $salesOrderTrack->getTrackNumber());
            }

            if (empty($trackingUrl)) {
                $trackingUrl = is_callable([ $salesOrderTrack, 'getUrl' ])
                    ? trim((string) $salesOrderTrack->getUrl())
                    : '';
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
                    'delay' => $shipmentDelay,
                ]
            );
        }

        if (empty($shipmentTracks)) {
            $shipmentTracks[] = $this->shipmentTrackFactory->create(
                [
                    'carrierCode' => static::DEFAULT_CARRIER_CODE,
                    'carrierTitle' => '',
                    'trackingNumber' => '',
                    'trackingUrl' => '',
                    'relevance' => '0',
                    'delay' => $shipmentDelay,
                ]
            );
        }

        return $shipmentTracks;
    }
}
