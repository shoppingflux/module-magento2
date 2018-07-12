<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method\Applier;


class Result
{
    /**
     * @var string
     */
    private $carrierCode;

    /**
     * @var string
     */
    private $methodCode;

    /**
     * @var string
     */
    private $carrierTitle;

    /**
     * @var string
     */
    private $methodTitle;

    /**
     * @var float
     */
    private $cost;

    /**
     * @var float
     */
    private $price;

    /**
     * @param string $carrierCode
     * @param string $methodCode
     * @param string $carrierTitle
     * @param string $methodTitle
     * @param float $cost
     * @param float $price
     */
    public function __construct($carrierCode, $methodCode, $carrierTitle, $methodTitle, $cost, $price)
    {
        $this->setCarrierCode($carrierCode);
        $this->setMethodCode($methodCode);
        $this->setCarrierTitle($carrierTitle);
        $this->setMethodTitle($methodTitle);
        $this->setCost($cost);
        $this->setPrice($price);
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
    public function getMethodCode()
    {
        return $this->methodCode;
    }

    /**
     * @return string
     */
    public function getFullCode()
    {
        return $this->getCarrierCode() . '_' . $this->getMethodCode();
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
    public function getMethodTitle()
    {
        return $this->methodTitle;
    }

    /**
     * @return float
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param string $carrierCode
     * @return Result
     */
    public function setCarrierCode($carrierCode)
    {
        $this->carrierCode = trim($carrierCode);
        return $this;
    }

    /**
     * @param string $methodCode
     * @return Result
     */
    public function setMethodCode($methodCode)
    {
        $this->methodCode = trim($methodCode);
        return $this;
    }

    /**
     * @param string $carrierTitle
     * @return Result
     */
    public function setCarrierTitle($carrierTitle)
    {
        $this->carrierTitle = trim($carrierTitle);
        return $this;
    }

    /**
     * @param string $methodTitle
     * @return Result
     */
    public function setMethodTitle($methodTitle)
    {
        $this->methodTitle = trim($methodTitle);
        return $this;
    }

    /**
     * @param float $cost
     * @return Result
     */
    public function setCost($cost)
    {
        $this->cost = max(0, (float) $cost);
        return $this;
    }

    /**
     * @param float $price
     * @return Result
     */
    public function setPrice($price)
    {
        $this->price = max(0, (float) $price);
        return $this;
    }
}
