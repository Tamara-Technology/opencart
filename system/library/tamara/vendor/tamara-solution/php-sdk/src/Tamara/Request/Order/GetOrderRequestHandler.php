<?php

declare (strict_types=1);
namespace TMS\Tamara\Request\Order;

use TMS\Tamara\Request\AbstractRequestHandler;
use TMS\Tamara\Response\Order\GetOrderResponse;
class GetOrderRequestHandler extends \TMS\Tamara\Request\AbstractRequestHandler
{
    private const GET_ORDER_URL = '/merchants/orders/%s';
    public function __invoke(\TMS\Tamara\Request\Order\GetOrderRequest $request)
    {
        $response = $this->httpClient->get(\sprintf(self::GET_ORDER_URL, $request->getOrderId()));
        return new \TMS\Tamara\Response\Order\GetOrderResponse($response);
    }
}
