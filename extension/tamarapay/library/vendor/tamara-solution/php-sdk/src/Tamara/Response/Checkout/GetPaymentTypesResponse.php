<?php

namespace TMS\Tamara\Response\Checkout;

use TMS\Tamara\Model\Checkout\PaymentTypeCollection;
use TMS\Tamara\Response\ClientResponse;
class GetPaymentTypesResponse extends \TMS\Tamara\Response\ClientResponse
{
    /**
     * @var array|PaymentTypeCollection
     */
    private $paymentTypes;
    /**
     * @return PaymentTypeCollection|null
     */
    public function getPaymentTypes() : ?\TMS\Tamara\Model\Checkout\PaymentTypeCollection
    {
        return $this->isSuccess() ? $this->paymentTypes : null;
    }
    protected function parse(array $responseData) : void
    {
        $this->paymentTypes = new \TMS\Tamara\Model\Checkout\PaymentTypeCollection($responseData);
    }
}
