<?php

namespace TMS\Tamara\Request\Checkout;

use TMS\Tamara\Request\AbstractRequestHandler;
use TMS\Tamara\Response\Checkout\GetPaymentTypesResponse;
class GetPaymentTypesV2RequestHandler extends \TMS\Tamara\Request\AbstractRequestHandler
{
    private const GET_PAYMENT_TYPES_V2_ENDPOINT = '/checkout/credit-pre-check';
    public function __invoke(\TMS\Tamara\Request\Checkout\GetPaymentTypesV2Request $request) : \TMS\Tamara\Response\Checkout\GetPaymentTypesResponse
    {
        $response = $this->httpClient->post(self::GET_PAYMENT_TYPES_V2_ENDPOINT, $request->toArray());
        return new \TMS\Tamara\Response\Checkout\GetPaymentTypesResponse($response);
    }
}
