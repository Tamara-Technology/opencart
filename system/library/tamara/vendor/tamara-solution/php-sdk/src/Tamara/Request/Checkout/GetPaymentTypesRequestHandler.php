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
        $data = ['country' => $request->getCountryCode()];
        if (!empty($request->getCurrency())) {
            $data['currency'] = $request->getCurrency();
        }
        if ($request->getOrderValue() > 0.00000001) {
            $data['order_value'] = $request->getOrderValue();
        }
        if (!empty($request->getPhoneNumber())) {
            $data['phone'] = $request->getPhoneNumber();
        }

        $response = $this->httpClient->get(self::GET_PAYMENT_TYPES_ENDPOINT, $data);
        return new \TMS\Tamara\Response\Checkout\GetPaymentTypesResponse($response);
    }
}
