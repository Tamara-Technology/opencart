<?php

declare (strict_types=1);
namespace TMS\Tamara\Model\Payment;

use TMS\Tamara\Model\Money;
use TMS\Tamara\Model\Order\Order;
use TMS\Tamara\Model\Order\OrderItemCollection;
class Refund
{
    public const REFUND_ID = 'refund_id', REFUND_COLLECTION = 'refunds';
    /**
     * @var string Tamara capture id
     */
    private $captureId;
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
    public function __construct(string $captureId, \TMS\Tamara\Model\Money $totalAmount, \TMS\Tamara\Model\Money $shippingAmount, \TMS\Tamara\Model\Money $taxAmount, \TMS\Tamara\Model\Money $discountAmount, \TMS\Tamara\Model\Order\OrderItemCollection $items)
    {
        $this->captureId = $captureId;
        $this->totalAmount = $totalAmount;
        $this->shippingAmount = $shippingAmount;
        $this->taxAmount = $taxAmount;
        $this->discountAmount = $discountAmount;
        $this->items = $items;
    }
    public function getCaptureId() : string
    {
        return $this->captureId;
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
    public function toArray() : array
    {
        return [\TMS\Tamara\Model\Payment\Capture::CAPTURE_ID => $this->getCaptureId(), \TMS\Tamara\Model\Order\Order::TOTAL_AMOUNT => $this->getTotalAmount()->toArray(), \TMS\Tamara\Model\Order\Order::ITEMS => $this->getItems()->toArray(), \TMS\Tamara\Model\Order\Order::SHIPPING_AMOUNT => $this->getShippingAmount()->toArray(), \TMS\Tamara\Model\Order\Order::TAX_AMOUNT => $this->getTaxAmount()->toArray(), \TMS\Tamara\Model\Order\Order::DISCOUNT_AMOUNT => $this->getDiscountAmount()->toArray()];
    }
}
