<?php

declare (strict_types=1);

namespace TMS\Tamara\Request\Checkout;

use TMS\Tamara\Model\Checkout\PaymentOptionsAvailability;

class CheckPaymentOptionsAvailabilityRequest
{

    private $paymentOptionsAvailability;

    public function __construct(PaymentOptionsAvailability $paymentOptionsAvailability)
    {
        $this->paymentOptionsAvailability = $paymentOptionsAvailability;
    }

    public function getPaymentOptionAvailability()
    {
        return $this->paymentOptionsAvailability;
    }
}
