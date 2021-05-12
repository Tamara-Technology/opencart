<?php

declare (strict_types=1);
namespace TMS\Tamara\Response\Checkout;

use TMS\Tamara\Model\Checkout\CheckoutResponse;
use TMS\Tamara\Response\ClientResponse;
class CreateCheckoutResponse extends \TMS\Tamara\Response\ClientResponse
{
    /**
     * @var CheckoutResponse|null
     */
    private $checkoutResponse;
    public function getCheckoutResponse() : ?\TMS\Tamara\Model\Checkout\CheckoutResponse
    {
        return $this->checkoutResponse;
    }
    protected function parse(array $responseData) : void
    {
        $this->checkoutResponse = new \TMS\Tamara\Model\Checkout\CheckoutResponse($responseData);
    }
}
