<?php

declare (strict_types=1);
namespace TMS\Tamara\Model\Order;

use TMS\Tamara\Model\Money;
class Order
{
    public const ORDER_ID = 'order_id', TOTAL_AMOUNT = 'total_amount', ITEMS = 'items', CONSUMER = 'consumer', BILLING_ADDRESS = 'billing_address', SHIPPING_ADDRESS = 'shipping_address', DISCOUNT = 'discount', TAX_AMOUNT = 'tax_amount', SHIPPING_AMOUNT = 'shipping_amount', MERCHANT_URL = 'merchant_url', PAYMENT_TYPE = 'payment_type', ORDER_REFERENCE_ID = 'order_reference_id', ORDER_NUMBER = 'order_number', DESCRIPTION = 'description', COUNTRY_CODE = 'country_code', LOCALE = 'locale', PLATFORM = 'platform', DISCOUNT_AMOUNT = 'discount_amount', RISK_ASSESSMENT = 'risk_assessment', INSTALMENTS = 'instalments', PAY_BY_INSTALMENTS = 'PAY_BY_INSTALMENTS', PAY_BY_LATER = 'PAY_BY_LATER';
    /**
     * @var string
     */
    private $orderId;
    /**
     * @var string
     */
    private $orderReferenceId;
    /**
     * @var string
     */
    private $orderNumber;
    /**
     * @var Money
     */
    private $totalAmount;
    /**
     * @var string
     */
    private $currency;
    /**
     * @var string
     */
    private $description;
    /**
     * @var string
     */
    private $countryCode;
    /**
     * @var string
     */
    private $paymentType;
    /**
     * @var null|int
     */
    private $instalments = null;
    /**
     * @var string
     */
    private $locale;
    /**
     * @var OrderItemCollection
     */
    private $items;
    /**
     * @var Consumer
     */
    private $consumer;
    /**
     * @var Address
     */
    private $billingAddress;
    /**
     * @var Address
     */
    private $shippingAddress;
    /**
     * @var Discount
     */
    private $discount;
    /**
     * @var Money
     */
    private $taxAmount;
    /**
     * @var Money
     */
    private $shippingAmount;
    /**
     * @var MerchantUrl
     */
    private $merchantUrl;
    /**
     * @var string the platform that the merchant is using such as Magento, OpenCart...
     */
    private $platform;
    /**
     * @var RiskAssessment
     */
    private $riskAssessment;
    public function getOrderId() : string
    {
        return $this->orderId;
    }
    public function setOrderId(string $orderId) : \TMS\Tamara\Model\Order\Order
    {
        $this->orderId = $orderId;
        return $this;
    }
    public function getOrderReferenceId() : string
    {
        return $this->orderReferenceId;
    }
    public function setOrderReferenceId(string $orderReferenceId) : \TMS\Tamara\Model\Order\Order
    {
        $this->orderReferenceId = $orderReferenceId;
        return $this;
    }
    public function getOrderNumber() : string
    {
        return $this->orderNumber ?? $this->getOrderReferenceId();
    }
    public function setOrderNumber(string $orderNumber) : \TMS\Tamara\Model\Order\Order
    {
        $this->orderNumber = $orderNumber;
        return $this;
    }
    public function getTotalAmount() : \TMS\Tamara\Model\Money
    {
        return $this->totalAmount;
    }
    public function setTotalAmount(\TMS\Tamara\Model\Money $totalAmount) : \TMS\Tamara\Model\Order\Order
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }
    public function getCurrency() : string
    {
        return $this->currency;
    }
    public function setCurrency(string $currency) : \TMS\Tamara\Model\Order\Order
    {
        $this->currency = $currency;
        return $this;
    }
    public function getDescription() : string
    {
        return $this->description ?? '';
    }
    public function setDescription(string $description) : \TMS\Tamara\Model\Order\Order
    {
        $this->description = $description;
        return $this;
    }
    public function getCountryCode() : string
    {
        return $this->countryCode;
    }
    public function setCountryCode(string $countryCode) : \TMS\Tamara\Model\Order\Order
    {
        $this->countryCode = $countryCode;
        return $this;
    }
    public function getPaymentType() : string
    {
        return $this->paymentType;
    }
    public function setPaymentType(string $paymentType) : \TMS\Tamara\Model\Order\Order
    {
        $this->paymentType = $paymentType;
        return $this;
    }
    public function getInstalments() : ?int
    {
        return $this->isInstalments() ? $this->instalments : null;
    }
    public function setInstalments(?int $instalments) : \TMS\Tamara\Model\Order\Order
    {
        $this->instalments = $instalments;
        return $this;
    }
    public function getLocale() : string
    {
        return $this->locale ?? '';
    }
    public function setLocale(string $locale) : \TMS\Tamara\Model\Order\Order
    {
        $this->locale = $locale;
        return $this;
    }
    public function getItems() : \TMS\Tamara\Model\Order\OrderItemCollection
    {
        return $this->items;
    }
    public function setItems(\TMS\Tamara\Model\Order\OrderItemCollection $items) : \TMS\Tamara\Model\Order\Order
    {
        $this->items = $items;
        return $this;
    }
    public function getConsumer() : \TMS\Tamara\Model\Order\Consumer
    {
        return $this->consumer;
    }
    public function setConsumer(\TMS\Tamara\Model\Order\Consumer $consumer) : \TMS\Tamara\Model\Order\Order
    {
        $this->consumer = $consumer;
        return $this;
    }
    public function getBillingAddress() : \TMS\Tamara\Model\Order\Address
    {
        return $this->billingAddress;
    }
    public function setBillingAddress(\TMS\Tamara\Model\Order\Address $billingAddress) : \TMS\Tamara\Model\Order\Order
    {
        $this->billingAddress = $billingAddress;
        return $this;
    }
    public function getShippingAddress() : \TMS\Tamara\Model\Order\Address
    {
        return $this->shippingAddress;
    }
    public function setShippingAddress(\TMS\Tamara\Model\Order\Address $shippingAddress) : \TMS\Tamara\Model\Order\Order
    {
        $this->shippingAddress = $shippingAddress;
        return $this;
    }
    public function getDiscount() : \TMS\Tamara\Model\Order\Discount
    {
        return $this->discount;
    }
    public function setDiscount(\TMS\Tamara\Model\Order\Discount $discount) : \TMS\Tamara\Model\Order\Order
    {
        $this->discount = $discount;
        return $this;
    }
    public function getTaxAmount() : \TMS\Tamara\Model\Money
    {
        return $this->taxAmount;
    }
    public function setTaxAmount(\TMS\Tamara\Model\Money $taxAmount) : \TMS\Tamara\Model\Order\Order
    {
        $this->taxAmount = $taxAmount;
        return $this;
    }
    public function getShippingAmount() : \TMS\Tamara\Model\Money
    {
        return $this->shippingAmount;
    }
    public function setShippingAmount(\TMS\Tamara\Model\Money $shippingAmount) : \TMS\Tamara\Model\Order\Order
    {
        $this->shippingAmount = $shippingAmount;
        return $this;
    }
    public function getMerchantUrl() : \TMS\Tamara\Model\Order\MerchantUrl
    {
        return $this->merchantUrl;
    }
    public function setMerchantUrl(\TMS\Tamara\Model\Order\MerchantUrl $merchantUrl) : \TMS\Tamara\Model\Order\Order
    {
        $this->merchantUrl = $merchantUrl;
        return $this;
    }
    public function getPlatform() : string
    {
        return $this->platform ?? '';
    }
    public function setPlatform(string $platform) : \TMS\Tamara\Model\Order\Order
    {
        $this->platform = $platform;
        return $this;
    }
    public function getRiskAssessment() : \TMS\Tamara\Model\Order\RiskAssessment
    {
        return $this->riskAssessment ?? new \TMS\Tamara\Model\Order\RiskAssessment([]);
    }
    public function setRiskAssessment(\TMS\Tamara\Model\Order\RiskAssessment $riskAssessment) : \TMS\Tamara\Model\Order\Order
    {
        $this->riskAssessment = $riskAssessment;
        return $this;
    }
    public function isInstalments() : bool
    {
        return self::PAY_BY_INSTALMENTS === $this->getPaymentType();
    }
    public function toArray() : array
    {
        $result = [self::ORDER_REFERENCE_ID => $this->getOrderReferenceId(), self::ORDER_NUMBER => $this->getOrderNumber(), self::TOTAL_AMOUNT => $this->getTotalAmount()->toArray(), self::DESCRIPTION => $this->getDescription(), self::COUNTRY_CODE => $this->getCountryCode(), self::PAYMENT_TYPE => $this->getPaymentType(), self::LOCALE => $this->getLocale(), self::ITEMS => $this->getItems()->toArray(), self::CONSUMER => $this->getConsumer()->toArray(), self::BILLING_ADDRESS => $this->getBillingAddress()->toArray(), self::SHIPPING_ADDRESS => $this->getShippingAddress()->toArray(), self::DISCOUNT => $this->getDiscount()->toArray(), self::TAX_AMOUNT => $this->getTaxAmount()->toArray(), self::SHIPPING_AMOUNT => $this->getShippingAmount()->toArray(), self::MERCHANT_URL => $this->getMerchantUrl()->toArray(), self::PLATFORM => $this->getPlatform(), self::RISK_ASSESSMENT => $this->getRiskAssessment()->getData()];
        if ($this->getInstalments() > 0 && $this->isInstalments()) {
            $result[self::INSTALMENTS] = $this->getInstalments();
        }
        return $result;
    }
}
