<?php

declare (strict_types=1);
namespace TMS\Tamara\Model\Order;

use TMS\Tamara\Model\Money;
class OrderItem
{
    public const REFERENCE_ID = 'reference_id', TYPE = 'type', NAME = 'name', SKU = 'sku', QUANTITY = 'quantity', TAX_AMOUNT = 'tax_amount', TOTAL_AMOUNT = 'total_amount', UNIT_PRICE = 'unit_price', DISCOUNT_AMOUNT = 'discount_amount', IMAGE_URL = 'image_url';
    /**
     * @var string
     */
    private $referenceId;
    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $sku;
    /**
     * @var int
     */
    private $quantity;
    /**
     * @var Money
     */
    private $taxAmount;
    /**
     * @var Money
     */
    private $totalAmount;
    /**
     * @var Money|null
     */
    private $unitPrice;
    /**
     * @var Money|null
     */
    private $discountAmount;
    /**
     * @var string
     */
    private $imageUrl;
    public static function fromArray(array $data) : \TMS\Tamara\Model\Order\OrderItem
    {
        $self = new self();
        $self->setName($data[self::NAME]);
        $self->setReferenceId($data[self::REFERENCE_ID]);
        $self->setSku($data[self::SKU] ?? '');
        $self->setType($data[self::TYPE] ?? '');
        $self->setQuantity((int) $data[self::QUANTITY]);
        $self->setUnitPrice(\TMS\Tamara\Model\Money::fromArray($data[self::UNIT_PRICE]));
        $self->setTotalAmount(\TMS\Tamara\Model\Money::fromArray($data[self::TOTAL_AMOUNT]));
        $self->setTaxAmount(\TMS\Tamara\Model\Money::fromArray($data[self::TAX_AMOUNT]));
        $self->setDiscountAmount(\TMS\Tamara\Model\Money::fromArray($data[self::DISCOUNT_AMOUNT]));
        $self->setImageUrl($data[self::IMAGE_URL] ?? '');
        return $self;
    }
    public function getReferenceId() : string
    {
        return $this->referenceId;
    }
    public function getType() : string
    {
        return $this->type ?? '';
    }
    public function getName() : string
    {
        return $this->name;
    }
    public function getSku() : string
    {
        return $this->sku;
    }
    public function getQuantity() : int
    {
        return $this->quantity;
    }
    public function getTaxAmount() : \TMS\Tamara\Model\Money
    {
        return $this->taxAmount;
    }
    public function getTotalAmount() : \TMS\Tamara\Model\Money
    {
        return $this->totalAmount;
    }
    /**
     * @return Money|null
     */
    public function getUnitPrice() : ?\TMS\Tamara\Model\Money
    {
        return $this->unitPrice;
    }
    /**
     * @return Money|null
     */
    public function getDiscountAmount() : ?\TMS\Tamara\Model\Money
    {
        return $this->discountAmount;
    }
    public function setReferenceId(string $referenceId) : \TMS\Tamara\Model\Order\OrderItem
    {
        $this->referenceId = $referenceId;
        return $this;
    }
    public function setType(string $type) : \TMS\Tamara\Model\Order\OrderItem
    {
        $this->type = $type;
        return $this;
    }
    public function setName(string $name) : \TMS\Tamara\Model\Order\OrderItem
    {
        $this->name = $name;
        return $this;
    }
    public function setSku(string $sku) : \TMS\Tamara\Model\Order\OrderItem
    {
        $this->sku = $sku;
        return $this;
    }
    public function setQuantity(int $quantity) : \TMS\Tamara\Model\Order\OrderItem
    {
        $this->quantity = $quantity;
        return $this;
    }
    public function setTaxAmount(\TMS\Tamara\Model\Money $taxAmount) : \TMS\Tamara\Model\Order\OrderItem
    {
        $this->taxAmount = $taxAmount;
        return $this;
    }
    public function setTotalAmount(\TMS\Tamara\Model\Money $totalAmount) : \TMS\Tamara\Model\Order\OrderItem
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }
    public function setUnitPrice(\TMS\Tamara\Model\Money $unitPrice) : \TMS\Tamara\Model\Order\OrderItem
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }
    public function setDiscountAmount(\TMS\Tamara\Model\Money $discountAmount) : \TMS\Tamara\Model\Order\OrderItem
    {
        $this->discountAmount = $discountAmount;
        return $this;
    }
    public function setImageUrl(string $imageUrl) : \TMS\Tamara\Model\Order\OrderItem
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }
    public function getImageUrl() : string
    {
        return $this->imageUrl ?? '';
    }
    public function toArray() : array
    {
        return [self::REFERENCE_ID => $this->getReferenceId(), self::TYPE => $this->getType(), self::NAME => $this->getName(), self::SKU => $this->getSku(), self::QUANTITY => $this->getQuantity(), self::TAX_AMOUNT => $this->getTaxAmount()->toArray(), self::TOTAL_AMOUNT => $this->getTotalAmount()->toArray(), self::UNIT_PRICE => $this->getUnitPrice() ? $this->getUnitPrice()->toArray() : null, self::DISCOUNT_AMOUNT => $this->getDiscountAmount() ? $this->getDiscountAmount()->toArray() : null, self::IMAGE_URL => $this->getImageUrl()];
    }
}
