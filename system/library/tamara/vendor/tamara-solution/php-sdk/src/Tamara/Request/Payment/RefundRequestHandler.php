<?php

declare (strict_types=1);
namespace TMS\Tamara\Request\Payment;

use TMS\Tamara\Request\AbstractRequestHandler;
use TMS\Tamara\Response\Payment\RefundResponse;
class RefundRequestHandler extends \TMS\Tamara\Request\AbstractRequestHandler
{
    private const CAPTURE_ENDPOINT = '/payments/refund';
    public function __invoke(\TMS\Tamara\Request\Payment\RefundRequest $request)
    {
        $response = $this->httpClient->post(self::CAPTURE_ENDPOINT, $request->toArray());
        return new \TMS\Tamara\Response\Payment\RefundResponse($response);
    }
}
