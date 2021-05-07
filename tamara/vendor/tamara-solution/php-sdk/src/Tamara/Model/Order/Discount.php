<?php

declare (strict_types=1);
namespace TMS\Tamara\Model\Order;

use TMS\Tamara\Model\Money;
class Discount
{
    public const NAME = 'name', AMOUNT = 'amount';
    /**
     * @var string
     */
    private $name;
    /**
     * @var Money
     */
    private $amount;
    public function __construct(string $name, \TMS\Tamara\Model\Money $amount)
    {
        $this->name = $name;
        $this->amount = $amount;
    }
    public static function fromArray(array $data) : \TMS\Tamara\Model\Order\Discount
    {
        return new self($data[self::NAME], \TMS\Tamara\Model\Money::fromArray($data[self::AMOUNT]));
    }
    public function setName(string $name) : \TMS\Tamara\Model\Order\Discount
    {
        $this->name = $name;
        return $this;
    }
    public function setAmount(\TMS\Tamara\Model\Money $amount) : \TMS\Tamara\Model\Order\Discount
    {
        $this->amount = $amount;
        return $this;
    }
    public function getName() : string
    {
        return $this->name;
    }
    public function getAmount() : \TMS\Tamara\Model\Money
    {
        return $this->amount;
    }
    public function toArray() : array
    {
        return [self::NAME => $this->getName(), self::AMOUNT => $this->getAmount()->toArray()];
    }
}
