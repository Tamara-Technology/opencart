<?php

declare (strict_types=1);
namespace TMS\Tamara\Request\Checkout;

use TMS\Tamara\Model\Order\Order;
class CreateCheckoutRequest
{
    /**
     * @var Order
     */
    private $order;
    public function __construct(\TMS\Tamara\Model\Order\Order $order)
    {
        $this->order = $order;
    }
    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }
}
