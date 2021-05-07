<?php

declare (strict_types=1);
namespace TMS\Tamara\Request\Order;

use TMS\Tamara\Model\Money;
use TMS\Tamara\Model\Order\OrderItemCollection;
class CancelOrderRequest
{
    private const TOTAL_AMOUNT = 'total_amount', TAX_AMOUNT = 'tax_amount', SHIPPING_AMOUNT = 'shipping_amount', DISCOUNT_AMOUNT = 'discount_amount', ITEMS = 'items';
    /**
     * @var string
     */
    private $orderId;
    /**
     * @var Money
     */
    private $totalAmount;
    /**
     * @var OrderItemCollection
     */
    private $items;
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
    public function __construct(string $orderId, \TMS\Tamara\Model\Money $totalAmount, \TMS\Tamara\Model\Order\OrderItemCollection $items, \TMS\Tamara\Model\Money $shippingAmount, \TMS\Tamara\Model\Money $taxAmount, \TMS\Tamara\Model\Money $discountAmount)
    {
        $this->orderId = $orderId;
        $this->totalAmount = $totalAmount;
        $this->items = $items;
        $this->shippingAmount = $shippingAmount;
        $this->taxAmount = $taxAmount;
        $this->discountAmount = $discountAmount;
    }
    public function getOrderId() : string
    {
        return $this->orderId;
    }
    public function getTotalAmount() : \TMS\Tamara\Model\Money
    {
        return $this->totalAmount;
    }
    public function getItems() : \TMS\Tamara\Model\Order\OrderItemCollection
    {
        return $this->items;
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
    public function toArray() : array
    {
        return [self::TOTAL_AMOUNT => $this->getTotalAmount()->toArray(), self::TAX_AMOUNT => $this->getTaxAmount()->toArray(), self::SHIPPING_AMOUNT => $this->getShippingAmount()->toArray(), self::DISCOUNT_AMOUNT => $this->getDiscountAmount()->toArray(), self::ITEMS => $this->getItems()->toArray()];
    }
}
