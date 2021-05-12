<?php

declare (strict_types=1);
namespace TMS\Tamara\Request\Order;

use TMS\Tamara\Request\AbstractRequestHandler;
use TMS\Tamara\Response\Payment\CancelResponse;
class CancelOrderRequestHandler extends \TMS\Tamara\Request\AbstractRequestHandler
{
    private const CANCEL_ORDER_ENDPOINT = '/orders/%s/cancel';
    public function __invoke(\TMS\Tamara\Request\Order\CancelOrderRequest $request)
    {
        $response = $this->httpClient->post(\sprintf(self::CANCEL_ORDER_ENDPOINT, $request->getOrderId()), $request->toArray());
        return new \TMS\Tamara\Response\Payment\CancelResponse($response);
    }
}
