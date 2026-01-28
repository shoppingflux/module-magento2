<?php

namespace ShoppingFeed\Manager\Model\Sales\Order\Shipment;

use Magento\Sales\Api\Data\ShipmentInterface;

class Track
{
    /**
     * @var string
     */
    private $carrierCode;

    /**
     * @var string
     */
    private $carrierTitle;

    /**
     * @var string
     */
    private $trackingNumber;

    /**
     * @var string
     */
    private $trackingUrl;

    /**
     * @var int
     */
    private $relevance;

    /**
     * @var int
     */
    private $delay;

    /**
     * @var ShipmentInterface|null
     */
    private $shipment = null;

    /**
     * @param string $carrierCode
     * @param string $carrierTitle
     * @param string $trackingNumber
     * @param string $trackingUrl
     * @param int $relevance
     * @param int $delay
     * @param ShipmentInterface|null $shipment
     */
    public function __construct(
        $carrierCode,
        $carrierTitle,
        $trackingNumber,
        $trackingUrl,
        $relevance,
        $delay = 0,
        $shipment = null
    ) {
        $this->setCarrierCode($carrierCode);
        $this->setCarrierTitle($carrierTitle);
        $this->setTrackingNumber($trackingNumber);
        $this->setTrackingUrl($trackingUrl);
        $this->setRelevance($relevance);
        $this->setDelay($delay);
        $this->setShipment($shipment);
    }

    /**
     * @return string
     */
    public function getCarrierCode()
    {
        return $this->carrierCode;
    }

    /**
     * @return string
     */
    public function getCarrierTitle()
    {
        return $this->carrierTitle;
    }

    /**
     * @return string
     */
    public function getTrackingNumber()
    {
        return $this->trackingNumber;
    }

    /**
     * @return string
     */
    public function getTrackingUrl()
    {
        return $this->trackingUrl;
    }

    /**
     * @return int
     */
    public function getRelevance()
    {
        return $this->relevance;
    }

    /**
     * @return int|null
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * @return ShipmentInterface|null
     */
    public function getShipment()
    {
        return $this->shipment;
    }

    /**
     * @return bool
     */
    public function hasTrackingData()
    {
        return !empty($this->getTrackingNumber()) || !empty($this->getTrackingUrl());
    }

    /**
     * @param string $carrierCode
     * @return $this
     */
    public function setCarrierCode($carrierCode)
    {
        $this->carrierCode = $carrierCode;
        return $this;
    }

    /**
     * @param string $carrierTitle
     * @return $this
     */
    public function setCarrierTitle($carrierTitle)
    {
        $this->carrierTitle = $carrierTitle;
        return $this;
    }

    /**
     * @param string $trackingNumber
     * @return $this
     */
    public function setTrackingNumber($trackingNumber)
    {
        $this->trackingNumber = $trackingNumber;
        return $this;
    }

    /**
     * @param string $trackingUrl
     * @return $this
     */
    public function setTrackingUrl($trackingUrl)
    {
        $this->trackingUrl = $trackingUrl;
        return $this;
    }

    /**
     * @param int $relevance
     * @return $this
     */
    public function setRelevance($relevance)
    {
        $this->relevance = max(0, (int) $relevance);
        return $this;
    }

    /**
     * @param int $delay
     * @return $this
     */
    public function setDelay($delay)
    {
        $this->delay = max(0, (int) $delay);
        return $this;
    }

    /**
     * @param ShipmentInterface|null $shipment
     * @return $this
     */
    public function setShipment(?ShipmentInterface $shipment)
    {
        $this->shipment = $shipment;
        return $this;
    }
}
