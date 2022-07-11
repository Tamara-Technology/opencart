<?php

declare (strict_types=1);
namespace TMS\Tamara\Request\Payment;

use TMS\Tamara\Model\Order\Order;
use TMS\Tamara\Model\Payment\Refund;
class RefundRequest
{
    /**
     * @var string
     */
    private $orderId;
    /**
     * @var Refund[]
     */
    private $refunds;
    public function __construct(string $orderId, array $refunds = [])
    {
        $this->orderId = $orderId;
        $this->refunds = $refunds;
    }
    public function getOrderId() : string
    {
        return $this->orderId;
    }
    public function addRefund(\TMS\Tamara\Model\Payment\Refund $refund) : void
    {
        $this->refunds[] = $refund;
    }
    /**
     * @return Refund[]
     */
    public function getRefunds() : array
    {
        return $this->refunds;
    }
    public function toArray() : array
    {
        $refunds = [];
        foreach ($this->getRefunds() as $refund) {
            $refunds[] = $refund->toArray();
        }
        return [\TMS\Tamara\Model\Order\Order::ORDER_ID => $this->getOrderId(), \TMS\Tamara\Model\Payment\Refund::REFUND_COLLECTION => $refunds];
    }
}
