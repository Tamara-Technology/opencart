<?php

declare (strict_types=1);
namespace TMS\Tamara\Request\Order;

use TMS\Tamara\Request\AbstractRequestHandler;
use TMS\Tamara\Response\Order\GetOrderByReferenceIdResponse;
class GetOrderByReferenceIdRequestHandler extends \TMS\Tamara\Request\AbstractRequestHandler
{
    private const GET_ORDER_BY_REFERENCE_ID_URL = '/merchants/orders/reference-id/%s';
    public function __invoke(\TMS\Tamara\Request\Order\GetOrderByReferenceIdRequest $request)
    {
        $response = $this->httpClient->get(\sprintf(self::GET_ORDER_BY_REFERENCE_ID_URL, $request->getReferenceId()));
        return new \TMS\Tamara\Response\Order\GetOrderByReferenceIdResponse($response);
    }
}
