<?php

declare (strict_types=1);
namespace TMS\Tamara\Request\Order;

use TMS\Tamara\Request\AbstractRequestHandler;
use TMS\Tamara\Response\Order\AuthoriseOrderResponse;
class AuthoriseOrderRequestHandler extends \TMS\Tamara\Request\AbstractRequestHandler
{
    private const AUTHORISE_ORDER_ENDPOINT = '/orders/%s/authorise';
    public function __invoke(\TMS\Tamara\Request\Order\AuthoriseOrderRequest $request)
    {
        $response = $this->httpClient->post(\sprintf(self::AUTHORISE_ORDER_ENDPOINT, $request->getOrderId()), ['order_id' => $request->getOrderId()]);
        return new \TMS\Tamara\Response\Order\AuthoriseOrderResponse($response);
    }
}
