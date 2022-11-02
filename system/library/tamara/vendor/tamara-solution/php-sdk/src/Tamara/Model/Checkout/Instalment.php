<?php

namespace TMS\Tamara\Model\Checkout;

use TMS\Tamara\Model\Money;
use TMS\Tamara\Model\Order\Order;
class Instalment
{
    /**
     * @var int
     */
    private $instalments;
    /**
     * @var Money
     */
    private $minLimit;
    /**
     * @var Money
     */
    private $maxLimit;
    public function __construct(int $instalments, \TMS\Tamara\Model\Money $minLimit, \TMS\Tamara\Model\Money $maxLimit)
    {
        $this->instalments = $instalments;
        $this->minLimit = $minLimit;
        $this->maxLimit = $maxLimit;
    }
    public function getInstalments() : int
    {
        return $this->instalments;
    }
    public function getMinLimit() : \TMS\Tamara\Model\Money
    {
        return $this->minLimit;
    }
    public function getMaxLimit() : \TMS\Tamara\Model\Money
    {
        return $this->maxLimit;
    }
    public function toArray() : array
    {
        return [\TMS\Tamara\Model\Order\Order::INSTALMENTS => $this->getInstalments(), \TMS\Tamara\Model\Checkout\PaymentType::MIN_LIMIT => $this->getMinLimit()->toArray(), \TMS\Tamara\Model\Checkout\PaymentType::MAX_LIMIT => $this->getMaxLimit()->toArray()];
    }
}
