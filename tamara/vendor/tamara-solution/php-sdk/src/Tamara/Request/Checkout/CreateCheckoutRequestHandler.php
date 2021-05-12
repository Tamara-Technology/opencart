<?php

declare (strict_types=1);
namespace TMS\Tamara\Request\Checkout;

use TMS\Tamara\Request\AbstractRequestHandler;
use TMS\Tamara\Response\Checkout\CreateCheckoutResponse;
class CreateCheckoutRequestHandler extends \TMS\Tamara\Request\AbstractRequestHandler
{
    private const CHECKOUT_ENDPOINT = '/checkout';
    public function __invoke(\TMS\Tamara\Request\Checkout\CreateCheckoutRequest $request)
    {
        $response = $this->httpClient->post(self::CHECKOUT_ENDPOINT, $request->getOrder()->toArray());
        return new \TMS\Tamara\Response\Checkout\CreateCheckoutResponse($response);
    }
}
