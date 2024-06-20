<?php

declare (strict_types=1);
namespace TMS\Tamara\Response\Payment;

use TMS\Tamara\Response\ClientResponse;
class CancelResponse extends \TMS\Tamara\Response\ClientResponse
{
    private const ORDER_ID = 'order_id', CANCEL_ID = 'cancel_id';
    /**
     * @var string|null
     */
    private $orderId;
    /**
     * @var string|null
     */
    private $cancelId;
    public function getOrderId() : ?string
    {
        return $this->orderId;
    }
    public function getCancelId() : ?string
    {
        return $this->cancelId;
    }
    protected function parse(array $responseData) : void
    {
        $this->orderId = $responseData[self::ORDER_ID];
        $this->cancelId = $responseData[self::CANCEL_ID];
    }
}
