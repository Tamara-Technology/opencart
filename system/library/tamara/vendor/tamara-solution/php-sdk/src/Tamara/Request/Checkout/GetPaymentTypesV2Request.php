<?php

namespace TMS\Tamara\Request\Checkout;

use TMS\Tamara\Model\Money;
use TMS\Tamara\Model\Order\Address;
use TMS\Tamara\Model\Order\Consumer;
use TMS\Tamara\Model\Order\Order;
use TMS\Tamara\Model\Order\OrderItemCollection;
use TMS\Tamara\Model\Order\RiskAssessment;
class GetPaymentTypesV2Request
{
    /**
     * @var Money
     */
    private $totalAmount;
    /**
     * @var string
     */
    private $countryCode;
    /**
     * @var OrderItemCollection|null
     */
    private $items;
    /**
     * @var Consumer|null
     */
    private $consumer;
    /**
     * @var null|Address
     */
    private $shippingAddress;
    /**
     * @var null|RiskAssessment
     */
    private $riskAssessment;
    /**
     * @var array
     */
    private $additionalData = [];
    public function __construct(\TMS\Tamara\Model\Money $totalAmount, string $countryCode, ?\TMS\Tamara\Model\Order\OrderItemCollection $items = null, ?\TMS\Tamara\Model\Order\Consumer $consumer = null, ?\TMS\Tamara\Model\Order\Address $shippingAddress = null, ?\TMS\Tamara\Model\Order\RiskAssessment $riskAssessment = null, ?array $additionalData = [])
    {
        $this->totalAmount = $totalAmount;
        $this->countryCode = $countryCode;
        $this->items = $items;
        $this->consumer = $consumer;
        $this->shippingAddress = $shippingAddress;
        $this->riskAssessment = $riskAssessment;
        $this->additionalData = $additionalData;
    }
    public function getTotalAmount() : \TMS\Tamara\Model\Money
    {
        return $this->totalAmount;
    }
    public function setTotalAmount(\TMS\Tamara\Model\Money $totalAmount) : \TMS\Tamara\Request\Checkout\GetPaymentTypesV2Request
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }
    public function getCountryCode() : string
    {
        return $this->countryCode;
    }
    public function setCountryCode(string $countryCode) : \TMS\Tamara\Request\Checkout\GetPaymentTypesV2Request
    {
        $this->countryCode = $countryCode;
        return $this;
    }
    public function getItems() : ?\TMS\Tamara\Model\Order\OrderItemCollection
    {
        return $this->items ?? null;
    }
    public function setItems(?\TMS\Tamara\Model\Order\OrderItemCollection $items) : \TMS\Tamara\Request\Checkout\GetPaymentTypesV2Request
    {
        $this->items = $items;
        return $this;
    }
    public function getConsumer() : ?\TMS\Tamara\Model\Order\Consumer
    {
        return $this->consumer ?? null;
    }
    public function setConsumer(?\TMS\Tamara\Model\Order\Consumer $consumer) : \TMS\Tamara\Request\Checkout\GetPaymentTypesV2Request
    {
        $this->consumer = $consumer;
        return $this;
    }
    public function getShippingAddress() : ?\TMS\Tamara\Model\Order\Address
    {
        return $this->shippingAddress ?? null;
    }
    public function setShippingAddress(?\TMS\Tamara\Model\Order\Address $shippingAddress) : \TMS\Tamara\Request\Checkout\GetPaymentTypesV2Request
    {
        $this->shippingAddress = $shippingAddress;
        return $this;
    }
    public function getRiskAssessment() : ?\TMS\Tamara\Model\Order\RiskAssessment
    {
        return $this->riskAssessment ?? null;
    }
    public function setRiskAssessment(?\TMS\Tamara\Model\Order\RiskAssessment $riskAssessment) : \TMS\Tamara\Request\Checkout\GetPaymentTypesV2Request
    {
        $this->riskAssessment = $riskAssessment;
        return $this;
    }
    public function getAdditionalData() : ?array
    {
        return $this->additionalData ?? [];
    }
    public function setAdditionalData(?array $additionalData) : \TMS\Tamara\Request\Checkout\GetPaymentTypesV2Request
    {
        $this->additionalData = $additionalData;
        return $this;
    }
    public function toArray() : array
    {
        return [\TMS\Tamara\Model\Order\Order::TOTAL_AMOUNT => $this->getTotalAmount()->toArray(), \TMS\Tamara\Model\Order\Order::COUNTRY_CODE => $this->getCountryCode(), \TMS\Tamara\Model\Order\Order::ITEMS => $this->getItems() ? $this->getItems()->toArray() : null, \TMS\Tamara\Model\Order\Order::CONSUMER => $this->getConsumer() ? $this->getConsumer()->toArray() : null, \TMS\Tamara\Model\Order\Order::SHIPPING_ADDRESS => $this->getShippingAddress() ? $this->getShippingAddress()->toArray() : null, \TMS\Tamara\Model\Order\Order::RISK_ASSESSMENT => $this->getRiskAssessment() ? $this->getRiskAssessment()->getData() : null, \TMS\Tamara\Model\Order\Order::ADDITIONAL_DATA => $this->getAdditionalData()];
    }
}
