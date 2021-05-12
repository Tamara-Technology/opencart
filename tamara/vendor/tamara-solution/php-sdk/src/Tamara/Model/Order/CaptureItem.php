<?php

declare (strict_types=1);
namespace TMS\Tamara\Model\Order;

use TMS\Tamara\Model\Money;
use TMS\Tamara\Model\ShippingInfo;
use DateTimeImmutable;
use DateTime;
class CaptureItem
{
    private const CAPTURE_ID = 'capture_id', ORDER_ID = 'order_id', TOTAL_AMOUNT = 'total_amount', REFUNDED_AMOUNT = 'refunded_amount', TAX_AMOUNT = 'tax_amount', SHIPPING_AMOUNT = 'shipping_amount', DISCOUNT_AMOUNT = 'discount_amount', SHIPPING_INFO = 'shipping_info', ITEMS = 'items', CREATED_AT = 'created_at';
    /**
     * @var string
     */
    private $captureId;
    /**
     * @var string
     */
    private $orderId;
    /**
     * @var Money
     */
    private $totalAmount;
    /**
     * @var Money
     */
    private $refundedAmount;
    /**
     * @var Money
     */
    private $taxAmount;
    /**
     * @var Money
     */
    private $shippingAmount;
    /**
     * @var Money
     */
    private $discountAmount;
    /**
     * @var ShippingInfo
     */
    private $shippingInfo;
    /**
     * @var OrderItemCollection
     */
    private $items;
    /**
     * @var DateTimeImmutable
     */
    private $createdAt;
    public static function fromArray(array $data) : \TMS\Tamara\Model\Order\CaptureItem
    {
        $self = new self();
        $self->setCaptureId($data[self::CAPTURE_ID]);
        $self->setOrderId($data[self::ORDER_ID]);
        $self->setTotalAmount(\TMS\Tamara\Model\Money::fromArray($data[self::TOTAL_AMOUNT]));
        $self->setRefundedAmount(\TMS\Tamara\Model\Money::fromArray($data[self::REFUNDED_AMOUNT]));
        $self->setTaxAmount(\TMS\Tamara\Model\Money::fromArray($data[self::TAX_AMOUNT]));
        $self->setShippingAmount(\TMS\Tamara\Model\Money::fromArray($data[self::SHIPPING_AMOUNT]));
        $self->setDiscountAmount(\TMS\Tamara\Model\Money::fromArray($data[self::DISCOUNT_AMOUNT]));
        $self->setShippingInfo(\TMS\Tamara\Model\ShippingInfo::fromArray($data[self::SHIPPING_INFO]));
        $self->setItems(\TMS\Tamara\Model\Order\OrderItemCollection::create($data[self::ITEMS]));
        $self->setCreatedAt(new \DateTimeImmutable($data[self::CREATED_AT]));
        return $self;
    }
    public function getCaptureId() : string
    {
        return $this->captureId;
    }
    public function setCaptureId(string $captureId) : void
    {
        $this->captureId = $captureId;
    }
    public function getOrderId() : string
    {
        return $this->orderId;
    }
    public function setOrderId(string $orderId) : void
    {
        $this->orderId = $orderId;
    }
    public function getTotalAmount() : \TMS\Tamara\Model\Money
    {
        return $this->totalAmount;
    }
    public function setTotalAmount(\TMS\Tamara\Model\Money $totalAmount) : void
    {
        $this->totalAmount = $totalAmount;
    }
    public function getRefundedAmount() : \TMS\Tamara\Model\Money
    {
        return $this->refundedAmount;
    }
    public function setRefundedAmount(\TMS\Tamara\Model\Money $refundedAmount) : void
    {
        $this->refundedAmount = $refundedAmount;
    }
    public function getTaxAmount() : \TMS\Tamara\Model\Money
    {
        return $this->taxAmount;
    }
    public function setTaxAmount(\TMS\Tamara\Model\Money $taxAmount) : void
    {
        $this->taxAmount = $taxAmount;
    }
    public function getShippingAmount() : \TMS\Tamara\Model\Money
    {
        return $this->shippingAmount;
    }
    public function setShippingAmount(\TMS\Tamara\Model\Money $shippingAmount) : void
    {
        $this->shippingAmount = $shippingAmount;
    }
    public function getDiscountAmount() : \TMS\Tamara\Model\Money
    {
        return $this->discountAmount;
    }
    public function setDiscountAmount(\TMS\Tamara\Model\Money $discountAmount) : void
    {
        $this->discountAmount = $discountAmount;
    }
    public function getShippingInfo() : \TMS\Tamara\Model\ShippingInfo
    {
        return $this->shippingInfo;
    }
    public function setShippingInfo(\TMS\Tamara\Model\ShippingInfo $shippingInfo) : void
    {
        $this->shippingInfo = $shippingInfo;
    }
    public function getItems() : \TMS\Tamara\Model\Order\OrderItemCollection
    {
        return $this->items;
    }
    public function setItems(\TMS\Tamara\Model\Order\OrderItemCollection $items) : void
    {
        $this->items = $items;
    }
    public function getCreatedAt() : \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeImmutable $createdAt) : void
    {
        $this->createdAt = $createdAt;
    }
    public function toArray() : array
    {
        return [self::CAPTURE_ID => $this->getCaptureId(), self::ORDER_ID => $this->getOrderId(), self::TOTAL_AMOUNT => $this->getTotalAmount()->toArray(), self::REFUNDED_AMOUNT => $this->getRefundedAmount()->toArray(), self::SHIPPING_AMOUNT => $this->getShippingAmount()->toArray(), self::TAX_AMOUNT => $this->getTaxAmount()->toArray(), self::DISCOUNT_AMOUNT => $this->getDiscountAmount()->toArray(), self::SHIPPING_INFO => $this->getShippingInfo()->toArray(), self::ITEMS => $this->getItems()->toArray(), self::CREATED_AT => $this->getCreatedAt()->format(\DateTime::ATOM)];
    }
}
