<?php

declare (strict_types=1);

namespace TMS\Tamara\Model;

class Merchant
{
    public const SINGLE_CHECKOUT_ENABLED = 'single_checkout_enabled';

    private $singleCheckoutEnabled;

    public static function fromArray(array $data)
    {
        $self = new self();
        $self->setSingleCheckoutEnabled($data[self::SINGLE_CHECKOUT_ENABLED]);
        return $self;
    }

    public function getSingleCheckoutEnabled()
    {
        return $this->singleCheckoutEnabled;
    }

    public function setSingleCheckoutEnabled(bool $singleCheckoutEnabled)
    {
        $this->singleCheckoutEnabled = $singleCheckoutEnabled;
    }
}
