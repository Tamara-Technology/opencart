<?php

declare (strict_types=1);
namespace TMS\Tamara\Response\Payment;

use TMS\Tamara\Model\Order\Order;
use TMS\Tamara\Model\Payment\Capture;
use TMS\Tamara\Model\Payment\Refund;
use TMS\Tamara\Response\ClientResponse;
class RefundResponse extends \TMS\Tamara\Response\ClientResponse
{
    /**
     * @var string
     */
    private $orderId;
    /**
     * @var RefundItemResponse[]
     */
    private $refunds;
    public function getOrderId() : ?string
    {
        return $this->orderId;
    }
    /**
     * @return RefundItemResponse[]
     */
    public function getRefunds() : array
    {
        return $this->refunds;
    }
    protected function parse(array $responseData) : void
    {
        $this->orderId = $responseData[\TMS\Tamara\Model\Order\Order::ORDER_ID];
        $this->toRefunds($responseData[\TMS\Tamara\Model\Payment\Refund::REFUND_COLLECTION]);
    }
    private function toRefunds(array $refunds) : void
    {
        foreach ($refunds as $refund) {
            $this->refunds[] = new \TMS\Tamara\Response\Payment\RefundItemResponse($refund[\TMS\Tamara\Model\Payment\Capture::CAPTURE_ID], $refund[\TMS\Tamara\Model\Payment\Refund::REFUND_ID]);
        }
    }
}
