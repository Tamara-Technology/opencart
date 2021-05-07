<?php

declare (strict_types=1);
namespace TMS\Tamara\Model\Payment;

use TMS\Tamara\Model\Money;
use TMS\Tamara\Model\Order\Order;
use TMS\Tamara\Model\Order\OrderItemCollection;
use TMS\Tamara\Model\ShippingInfo;
class Capture
{
    public const CAPTURE_ID = 'capture_id', SHIPPING_INFO = 'shipping_info';
    /**
     * @var string Tamara order id
     */
    private $orderId;
    /**
     * @var Money
     */
    private $totalAmount;
    /**
     * @var Money
     */
    private $shippingAmount;
    /**
     * @var Money
     */
    private $taxAmount;
    /**
     * @var Money
     */
    private $discountAmount;
    /**
     * @var OrderItemCollection
     */
    private $items;
    /**
     * @var ShippingInfo
     */
    private $shippingInfo;
    public function __construct(string $orderId, \TMS\Tamara\Model\Money $totalAmount, \TMS\Tamara\Model\Money $shippingAmount, \TMS\Tamara\Model\Money $taxAmount, \TMS\Tamara\Model\Money $discountAmount, \TMS\Tamara\Model\Order\OrderItemCollection $items, \TMS\Tamara\Model\ShippingInfo $shippingInfo)
    {
        $this->orderId = $orderId;
        $this->totalAmount = $totalAmount;
        $this->shippingAmount = $shippingAmount;
        $this->taxAmount = $taxAmount;
        $this->discountAmount = $discountAmount;
        $this->items = $items;
        $this->shippingInfo = $shippingInfo;
    }
    public function getOrderId() : string
    {
        return $this->orderId;
    }
    public function getTotalAmount() : \TMS\Tamara\Model\Money
    {
        return $this->totalAmount;
    }
    public function getShippingAmount() : \TMS\Tamara\Model\Money
    {
        return $this->shippingAmount;
    }
    public function getTaxAmount() : \TMS\Tamara\Model\Money
    {
        return $this->taxAmount;
    }
    public function getDiscountAmount() : \TMS\Tamara\Model\Money
    {
        return $this->discountAmount;
    }
    public function getItems() : \TMS\Tamara\Model\Order\OrderItemCollection
    {
        return $this->items;
    }
    public function getShippingInfo() : \TMS\Tamara\Model\ShippingInfo
    {
        return $this->shippingInfo;
    }
    public function toArray() : array
    {
        return [\TMS\Tamara\Model\Order\Order::ORDER_ID => $this->getOrderId(), \TMS\Tamara\Model\Order\Order::TOTAL_AMOUNT => $this->getTotalAmount()->toArray(), \TMS\Tamara\Model\Order\Order::ITEMS => $this->getItems()->toArray(), \TMS\Tamara\Model\Order\Order::SHIPPING_AMOUNT => $this->getShippingAmount()->toArray(), \TMS\Tamara\Model\Order\Order::TAX_AMOUNT => $this->getTaxAmount()->toArray(), \TMS\Tamara\Model\Order\Order::DISCOUNT_AMOUNT => $this->getDiscountAmount()->toArray(), self::SHIPPING_INFO => $this->getShippingInfo()->toArray()];
    }
}
