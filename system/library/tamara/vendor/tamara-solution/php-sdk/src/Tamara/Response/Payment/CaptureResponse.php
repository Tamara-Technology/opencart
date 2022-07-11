<?php

declare (strict_types=1);
namespace TMS\Tamara\Response\Payment;

use TMS\Tamara\Response\ClientResponse;
class CaptureResponse extends \TMS\Tamara\Response\ClientResponse
{
    private const ORDER_ID = 'order_id', CAPTURE_ID = 'capture_id';
    /**
     * @var string|null
     */
    private $orderId;
    /**
     * @var string|null
     */
    private $captureId;
    public function getOrderId() : ?string
    {
        return $this->orderId;
    }
    public function getCaptureId() : ?string
    {
        return $this->captureId;
    }
    protected function parse(array $responseData) : void
    {
        $this->orderId = $responseData[self::ORDER_ID];
        $this->captureId = $responseData[self::CAPTURE_ID];
    }
}
