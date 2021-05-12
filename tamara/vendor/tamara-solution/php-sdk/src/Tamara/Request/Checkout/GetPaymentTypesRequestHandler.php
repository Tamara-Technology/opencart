<?php

declare (strict_types=1);
namespace TMS\Tamara\Request\Checkout;

use TMS\Tamara\Request\AbstractRequestHandler;
use TMS\Tamara\Response\Checkout\GetPaymentTypesResponse;
class GetPaymentTypesRequestHandler extends \TMS\Tamara\Request\AbstractRequestHandler
{
    private const GET_PAYMENT_TYPES_ENDPOINT = '/checkout/payment-types';
    public function __invoke(\TMS\Tamara\Request\Checkout\GetPaymentTypesRequest $request)
    {
        $response = $this->httpClient->get(self::GET_PAYMENT_TYPES_ENDPOINT, ['country' => $request->getCountryCode()]);
        return new \TMS\Tamara\Response\Checkout\GetPaymentTypesResponse($response);
    }
}
