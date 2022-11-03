<?php

declare (strict_types=1);
namespace TMS\Tamara\Request\Order;

use TMS\Tamara\Request\AbstractRequestHandler;
use TMS\Tamara\Response\Order\UpdateReferenceIdResponse;
class UpdateReferenceIdRequestHandler extends \TMS\Tamara\Request\AbstractRequestHandler
{
    private const CANCEL_ORDER_ENDPOINT = '/orders/%s/reference-id';
    public function __invoke(\TMS\Tamara\Request\Order\UpdateReferenceIdRequest $request)
    {
        $response = $this->httpClient->put(\sprintf(self::CANCEL_ORDER_ENDPOINT, $request->getOrderId()), $request->toArray());
        return new \TMS\Tamara\Response\Order\UpdateReferenceIdResponse($response);
    }
}
