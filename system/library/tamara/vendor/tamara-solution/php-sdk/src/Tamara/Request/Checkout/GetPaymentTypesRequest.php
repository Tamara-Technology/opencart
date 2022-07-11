<?php

declare(strict_types=1);

namespace TMS\Tamara\Request\Checkout;

class GetPaymentTypesRequest
{
    /**
     * @var string
     */
    private $countryCode;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var float
     */
    private $orderValue;

    /**
     * @var string
     */
    private $phoneNumber;

    public function __construct(string $countryCode, string $currency = '', float $orderValue = 0.00, string $phoneNumber = '')
    {
        $this->countryCode = trim($countryCode);
        $this->currency = trim($currency);
        $this->orderValue = $orderValue;
        $this->phoneNumber = $phoneNumber;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getOrderValue(): float {
        return $this->orderValue;
    }

    public function getPhoneNumber(): string {
        return $this->phoneNumber;
    }
}
